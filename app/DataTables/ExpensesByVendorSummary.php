<?php

namespace App\DataTables;

use App\Models\BillProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ExpensesByVendorSummary extends DataTable
{
    public function dataTable($query)
    {
        // Fetch bill products and accounts, then merge
        $products = collect($query->get());
        $accounts = $this->getBillAccounts();
        // Merge products and accounts
        $data = $products->concat($accounts);
        
        $finalData = collect();
        $grandTotal = 0;
        
        // Group by vendor
        $vendors = $data->groupBy('vendor_name');


        foreach ($vendors as $vendor => $rows) {
            $vendorTotal = 0;

            foreach ($rows as $row) {
                // Calculate line amount: (price * quantity) - discount + tax
                $amount = ($row->price * $row->quantity) - ($row->discount ?? 0) + ($row->tax_amount ?? 0);
                
                // Apply QuickBooks sign logic
                // Credits (Vendor Credit, Credit Card Credit) = negative
                // Expenses (Expense, Cash Expense, Check, Credit Card Expense) = positive
                $multiplier = $this->getSignMultiplier($row->bill_type);
                
                $vendorTotal += ($amount * $multiplier);
            }

            $finalData->push((object) [
                'vendor_name' => $vendor,
                'total' => $vendorTotal,
                'isDetail' => true,
            ]);

            $grandTotal += $vendorTotal;
        }

        // Add grand total row
        $finalData->push((object) [
            'vendor_name' => "<strong>Grand Total</strong>",
            'total' => $grandTotal,
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->editColumn('total', fn($row) => number_format((float) $row->total, 2))
            ->setRowClass(function ($row) {
                if (isset($row->isGrandTotal)) {
                    return 'grandtotal-row';
                }
                return 'detail-row';
            })
            ->rawColumns(['vendor_name']);
    }

    /**
     * Get sign multiplier based on bill type
     * QuickBooks: Credits are negative, Expenses are positive
     */
    protected function getSignMultiplier($billType)
    {
        // Vendor Credits and Credit Card Credits subtract from total
        $creditTypes = ['Vendor Credit', 'Credit Card Credit', 'vendor credit', 'credit'];
        
        if (in_array($billType, $creditTypes)) {
            return -1;
        }
        
        // All other types (Expense, Cash Expense, Check, Credit Card Expense, Bill) add to total
        return 1;
    }

    /**
     * Query bill_products with bill information
     */
    public function query(BillProduct $model)
    {
        $start = request()->get('start_date')
            ?? request()->get('startDate')
            ?? Carbon::now()->startOfYear()->format('Y-m-d');

        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return $model->newQuery()
            ->select(
                'bill_products.id',
                'bill_products.price',
                'bill_products.quantity',
                'bill_products.discount',
                'bills.bill_date as transaction_date',
                'bills.type as bill_type',
                'venders.name as vendor_name',
                DB::raw('(SELECT IFNULL(SUM((bill_products.price * bill_products.quantity) * (taxes.rate / 100)),0)
                        FROM taxes
                        WHERE FIND_IN_SET(taxes.id, bill_products.tax) > 0) AS tax_amount')
            )
            ->join('bills', 'bills.id', '=', 'bill_products.bill_id')
            ->join('venders', 'venders.id', '=', 'bills.vender_id')
            // ----------------------------------------------
            // SKIP BILLS THAT ARE CONNECTED TO ANY PO (txn_id)
            // ----------------------------------------------
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                ->from('purchases')
                ->whereRaw("FIND_IN_SET(bills.id, purchases.txn_id)");
            })
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->whereBetween('bills.bill_date', [$start, $end]);
            }

    /**
     * Get bill accounts (account-based expenses from split transactions)
     */
    protected function getBillAccounts()
    {
        $start = request()->get('start_date')
            ?? request()->get('startDate')
            ?? Carbon::now()->startOfYear()->format('Y-m-d');

        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return DB::table('bill_accounts')
            ->select(
                'bill_accounts.id',
                'bill_accounts.price',
                DB::raw('1 as quantity'), // Accounts don't have quantity
                DB::raw('0 as discount'), // Accounts don't have discount
                DB::raw('0 as tax_amount'), // Price already includes tax for accounts
                'bills.bill_date as transaction_date',
                'bills.type as bill_type',
                'venders.name as vendor_name'
            )
            ->join('bills', 'bills.id', '=', 'bill_accounts.ref_id')
            ->join('venders', 'venders.id', '=', 'bills.vender_id')
            // ->where('bill_accounts.type', 'Bill') // Only bill-related accounts
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->whereBetween('bills.bill_date', [$start, $end])
            ->get();
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('customer-balance-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'asc')
            ->parameters([
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'responsive' => true,
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('vendor_name')->title('Vendor'),
            Column::make('total')->title('Total'),
        ];
    }
}
