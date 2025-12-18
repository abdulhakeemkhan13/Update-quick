<?php

namespace App\DataTables;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CheckDetailReport extends DataTable
{
    public function dataTable($query)
    {
        // Get all data (Parents and Children mixed)
        $data = collect($query->get());

        $grandTotal = 0;
        $finalData = collect();

        // 1. Group by Bank Account
        $bankAccounts = $data->groupBy('bank_account_name');

        foreach ($bankAccounts as $bankAccount => $bankRows) {
            $bankSubtotal = 0;
            
            // Get bank details from the first row in this group
            $firstRow = $bankRows->first();
            $bankAccountNumber = $firstRow->bank_account_number ?? '';
            $bankAccountDisplay = $bankAccount ?: 'Unknown Account';
            
            // Count unique checks (count unique bill_ids where row_type is parent)
            $checkCount = $bankRows->where('row_type', 'parent')->count();

            // --- LEVEL 1: BANK HEADER ---
            $finalData->push((object) [
                'transaction_date' => '<span class="toggle-bucket" data-bucket="' . \Str::slug($bankAccountDisplay) . '"><span class="icon">▼</span> <strong>' . e($bankAccountDisplay) . ' (' . $bankAccountNumber . ')</strong></span>',
                'transaction_type' => '',
                'num' => '',
                'name' => '',
                'memo' => '',
                'cleared' => '',
                'amount' => 0,
                'bank_account_name' => $bankAccount,
                'check_number' => '',
                'isBankHeader' => true,
            ]);

            // 2. Group by Bill ID (to keep splits with their check)
            $billGroups = $bankRows->groupBy('bill_id');

            foreach ($billGroups as $billId => $rows) {
                // Separate Parent (Check Total) from Children (Splits)
                $parentRow = $rows->where('row_type', 'parent')->first();
                $childRows = $rows->where('row_type', 'child');

                if (!$parentRow) continue;

                $checkNumber = $parentRow->check_number;
                $checkDisplay = $checkNumber ?: 'No Check #';
                $checkTotal = (float) $parentRow->amount;
                
                // Add to bank running total
                $bankSubtotal += $checkTotal;

                // --- LEVEL 2: CHECK HEADER (Collapsible Wrapper) ---
                $finalData->push((object) [
                    'transaction_date' => '<span class="toggle-check" data-check="' . \Str::slug($bankAccountDisplay . '-' . $billId) . '"><span class="icon">▼</span> <strong>' . e($checkDisplay) . '</strong></span>',
                    'transaction_type' => '',
                    'num' => '',
                    'name' => '',
                    'memo' => '',
                    'cleared' => '',
                    'amount' => 0, // Header usually doesn't show amount in your example, or could show sum
                    'bank_account_name' => $bankAccount,
                    'check_number' => $checkNumber,
                    'bill_id' => $billId,
                    'isCheckHeader' => true,
                ]);

                // --- LEVEL 3: THE PARENT CHECK ROW ---
                $finalData->push((object) [
                    'transaction_date' => $parentRow->transaction_date,
                    'transaction_type' => 'Check',
                    'num' => $parentRow->check_number, // Show Check # on Parent
                    'name' => $parentRow->payee_name,
                    'memo' => $parentRow->memo, // Main Bill Note
                    'cleared' => 'Reconciled', // Placeholder based on image
                    'amount' => $checkTotal,
                    'bank_account_name' => $bankAccount,
                    'check_number' => $checkNumber,
                    'bill_id' => $billId,
                    'row_class' => 'font-weight-bold', // Make parent text bold
                    'isDetail' => true,
                ]);

                // --- LEVEL 4: THE SPLIT ROWS (Children) ---
                foreach ($childRows as $child) {
                    $finalData->push((object) [
                        'transaction_date' => $child->transaction_date,
                        'transaction_type' => 'Check', // Or 'Split'
                        'num' => $child->check_number,
                        'name' => $child->payee_name, // Or empty if you prefer
                        'memo' => $child->memo, // This contains the Line Item Description
                        'cleared' => '-', 
                        'amount' => (float) $child->amount, // Positive Value
                        'bank_account_name' => $bankAccount,
                        'check_number' => $checkNumber,
                        'bill_id' => $billId,
                        'isSplit' => true, // Marker for styling indentation
                        'isDetail' => true,
                    ]);
                }
            }

            // --- BANK SUBTOTAL ---
            $finalData->push((object) [
                'transaction_date' => "<strong>Total for {$bankAccountDisplay}</strong>",
                'transaction_type' => '',
                'num' => '',
                'name' => '',
                'memo' => '',
                'cleared' => '',
                'amount' => $bankSubtotal,
                'bank_account_name' => $bankAccount,
                'isSubtotal' => true,
            ]);

            // Spacing Row
            $finalData->push((object) [
                'transaction_date' => '', 'transaction_type' => '', 'num' => '', 'name' => '', 'memo' => '', 'cleared' => '', 'amount' => '', 
                'bank_account_name' => $bankAccount, 'isPlaceholder' => true,
            ]);

            $grandTotal += $bankSubtotal;
        }

        // Grand total
        $finalData->push((object) [
            'transaction_date' => '<strong>Grand Total</strong>',
            'transaction_type' => '',
            'num' => '',
            'name' => '',
            'memo' => '',
            'cleared' => '',
            'amount' => $grandTotal,
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->editColumn('transaction_date', function ($row) {
                if (isset($row->isDetail)) {
                    return $row->transaction_date ? Carbon::parse($row->transaction_date)->format('m/d/Y') : '';
                }
                return $row->transaction_date;
            })
            ->editColumn('amount', function ($row) {
                if (isset($row->isBankHeader) || isset($row->isCheckHeader) || isset($row->isPlaceholder))
                    return '';
                return number_format((float) $row->amount, 2);
            })
            ->setRowClass(function ($row) {
                $bankSlug = isset($row->bank_account_name) ? \Str::slug($row->bank_account_name) : 'no-bank';
                $checkSlug = isset($row->bill_id) ? \Str::slug($row->bank_account_name . '-' . $row->bill_id) : '';

                if (isset($row->isBankHeader))
                    return 'parent-row toggle-bucket bucket-' . $bankSlug;
                
                if (isset($row->isCheckHeader))
                    return 'check-header-row bucket-' . $bankSlug . ' check-' . $checkSlug;

                if (isset($row->isSubtotal)) return 'subtotal-row bucket-' . $bankSlug;
                if (isset($row->isGrandTotal)) return 'grandtotal-row';
                if (isset($row->isPlaceholder)) return 'placeholder-row bucket-' . $bankSlug;

                // Details
                $classes = 'child-row bucket-' . $bankSlug . ' check-' . $checkSlug;
                
                if(isset($row->isSplit)) {
                    $classes .= ' text-muted pl-5'; // Add padding/indentation and gray text for splits
                } else {
                    $classes .= ' font-weight-bold'; // Bold for the main check row
                }

                return $classes;
            })
            ->rawColumns(['transaction_date']);
    }

   public function query()
{
    $userId = \Auth::user()->creatorId();
    $start = request()->get('start_date') ?? request()->get('startDate') ?? Carbon::now()->startOfYear()->format('Y-m-d');
    $end = request()->get('end_date') ?? request()->get('endDate') ?? Carbon::now()->endOfDay()->format('Y-m-d');

    // 1. Payee Name Logic
    $payeeSelect = 'CASE 
        WHEN bills.user_type = "vendor" THEN (SELECT name FROM venders WHERE id = bills.vender_id LIMIT 1)
        WHEN bills.user_type = "customer" THEN (SELECT name FROM customers WHERE id = bills.vender_id LIMIT 1)
        WHEN bills.user_type = "employee" THEN (SELECT CONCAT(name) FROM employees WHERE id = bills.vender_id LIMIT 1)
        ELSE (SELECT name FROM venders WHERE id = bills.vender_id LIMIT 1)
    END as payee_name';

    // ---------------------------------------------------------
    // 1. PARENT ROW (The Main Check Total)
    // ---------------------------------------------------------
    // This remains always negative (Outflow)
    $parentQuery = DB::table('bills')
        ->select(
            'bills.id as bill_id',
            'bills.bill_date as transaction_date',
            'bills.bill_id as check_number',
            'bills.notes as memo',
            'bill_payments.reference as reference_num',
            'bank_accounts.bank_name as bank_account_name',
            'bank_accounts.account_number as bank_account_number',
            DB::raw($payeeSelect),
            'bills.created_at',
            // Sum of all items * -1
            DB::raw('(
                (SELECT IFNULL(SUM(bp.price * bp.quantity - IFNULL(bp.discount, 0)), 0) FROM bill_products bp WHERE bp.bill_id = bills.id)
                + (SELECT IFNULL(SUM(ba.price), 0) FROM bill_accounts ba WHERE ba.ref_id = bills.id)
            ) * -1 as amount'),
            DB::raw("'parent' as row_type"),
            DB::raw("'Reconciled' as cleared_status") // Parent status
        )
        ->leftJoin('bill_payments', 'bill_payments.bill_id', '=', 'bills.id')
        ->leftJoin('bank_accounts', 'bank_accounts.id', '=', 'bill_payments.account_id')
        ->where('bills.created_by', $userId)
        ->where('bills.type', 'Check')
        ->whereBetween('bills.bill_date', [$start, $end]);

    // ---------------------------------------------------------
    // 2. CHILD ROWS - PRODUCTS
    // ---------------------------------------------------------
    $productQuery = DB::table('bill_products')
        ->join('bills', 'bills.id', '=', 'bill_products.bill_id')
        ->leftJoin('bill_payments', 'bill_payments.bill_id', '=', 'bills.id')
        ->leftJoin('bank_accounts', 'bank_accounts.id', '=', 'bill_payments.account_id')
        ->select(
            'bills.id as bill_id',
            'bills.bill_date as transaction_date',
            'bills.bill_id as check_number',
            'bill_products.description as memo',
            'bill_payments.reference as reference_num',
            'bank_accounts.bank_name as bank_account_name',
            'bank_accounts.account_number as bank_account_number',
            DB::raw($payeeSelect),
            'bills.created_at',

            // DYNAMIC CALCULATION BASED ON BILLABLE STATUS
            DB::raw('CASE 
                WHEN bill_products.billable = 0 THEN (bill_products.price * bill_products.quantity - IFNULL(bill_products.discount, 0)) * -1
                ELSE (bill_products.price * bill_products.quantity - IFNULL(bill_products.discount, 0))
            END as amount'),

            DB::raw("'child' as row_type"),

            // DYNAMIC STATUS
            DB::raw('CASE 
                WHEN bill_products.billable = 0 THEN "Uncleared"
                ELSE "-" 
            END as cleared_status')
        )
        ->where('bills.created_by', $userId)
        ->where('bills.type', 'Check')
        ->whereBetween('bills.bill_date', [$start, $end]);

    // ---------------------------------------------------------
    // 3. CHILD ROWS - ACCOUNTS (CATEGORIES)
    // ---------------------------------------------------------
    $accountQuery = DB::table('bill_accounts')
        ->join('bills', 'bills.id', '=', 'bill_accounts.ref_id')
        ->leftJoin('bill_payments', 'bill_payments.bill_id', '=', 'bills.id')
        ->leftJoin('bank_accounts', 'bank_accounts.id', '=', 'bill_payments.account_id')
        ->select(
            'bills.id as bill_id',
            'bills.bill_date as transaction_date',
            'bills.bill_id as check_number',
            'bill_accounts.description as memo',
            'bill_payments.reference as reference_num',
            'bank_accounts.bank_name as bank_account_name',
            'bank_accounts.account_number as bank_account_number',
            DB::raw($payeeSelect),
            'bills.created_at',

            // DYNAMIC CALCULATION BASED ON BILLABLE STATUS
            DB::raw("
                CASE
                    WHEN bill_accounts.billable = 0 THEN 
                        CASE 
                            WHEN bill_accounts.price > 0 THEN bill_accounts.price * -1
                            ELSE bill_accounts.price   -- already negative, keep it negative
                        END
                    ELSE bill_accounts.price
                END as amount
            "),

            DB::raw("'child' as row_type"),

            // DYNAMIC STATUS
            DB::raw('CASE 
                WHEN bill_accounts.billable = 0 THEN "Uncleared"
                ELSE "-" 
            END as cleared_status')
        )
        ->where('bills.created_by', $userId)
        ->where('bills.type', 'Check')
        ->whereBetween('bills.bill_date', [$start, $end]);

    // Combine
    $combined = $parentQuery->unionAll($productQuery)->unionAll($accountQuery);

    return DB::query()->fromSub($combined, 'checks')
        ->orderBy('bank_account_name', 'asc')
        ->orderBy('transaction_date', 'asc')
        ->orderBy('bill_id', 'asc')
        ->orderBy('row_type', 'desc'); // Ensures Parent (p) is above Child (c)
}

    public function html()
    {
        return $this->builder()
            ->setTableId('check-detail-table')
            ->columns($this->getColumns())
            ->ajax([
                'url' => route('expenses.check_detail'),
                'type' => 'GET',
                'headers' => [
                    'X-CSRF-TOKEN' => csrf_token(),
                ],
            ])
            ->orderBy(0, 'asc')
            ->parameters([
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'dom' => 't', // Simple table only
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('transaction_date')->title('Date'),
            Column::make('transaction_type')->title('Type'),
            Column::make('num')->title('Num'),
            Column::make('name')->title('Payee'), // Changed title based on image
            Column::make('memo')->title('Memo/Description'),
            Column::make('cleared')->title('Clr'), // Shortened based on standard checks
            Column::make('amount')->title('Amount')->class('text-right'),
        ];
    }
}