<?php

namespace App\DataTables;

use App\Models\Bill;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class PurchaseList extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        // Execute the query and map the results
        $data = collect($query->get())->map(function ($row) {
            
            // Calculate open_balance in PHP using the selected columns (Kept for consistency, though not displayed)
            $row->open_balance = $row->total_amount - $row->payments_total;

            // 1. Format Date
            $row->date = $row->transaction_date
                ? Carbon::parse($row->transaction_date)->format('m/d/Y') 
                : '';
            
            // 2. Format Type & Numbers
            // $row->type_display = $this->getBillTypeDisplay($row->type); // REMOVED
            $row->num = $row->bill_id ?? $row->id; // Transaction ID
            $row->payee = $row->vendor_name; // Name
            $row->memo = $row->notes ?? '';
            
            // 3. Status Logic (Kept internally, though not displayed)
            if ($row->total_amount < 0) { 
                 $row->status_display = '<span class="badge badge-success">Closed</span>'; 
                 $row->open_balance = 0;
            } else {
                 $row->status_display = $this->getStatusDisplay($row->status, $row->open_balance);
            }

            // 4. Format Money
            $row->amount_display = number_format((float) $row->total_amount, 2);
            // $row->balance_display = number_format((float) $row->open_balance, 2); // REMOVED
            
            // 5. Format Tax Fields (NEW)
            $row->tax_amount_display = number_format((float) $row->tax_amount, 2);
            $row->tax_name_display = $row->tax_name ?? '';
            
            return $row;
        });

        // Initialize Grand Total for aggregation
        $grandTotal = [
            'amount' => $data->sum('total_amount'),
            'tax_amount' => $data->sum('tax_amount'), // Sum tax amount for grand total
        ];

        // Add Grand Total Row
        $data->push((object) [
            'num' => '',
            'payee' => '<strong>Total</strong>',
            'date' => '',
            'memo' => '',
            'amount_display' => '<strong>' . number_format($grandTotal['amount'], 2) . '</strong>',
            'tax_amount_display' => '<strong>' . number_format($grandTotal['tax_amount'], 2) . '</strong>', // Grand Total for Tax
            'tax_name_display' => '',
        ]);

        return datatables()
            ->collection($data)
            ->setRowClass(function ($row) {
                return isset($row->isGrandTotal) ? 'font-weight-bold bg-light' : '';
            })
            ->rawColumns(['payee', 'amount_display', 'tax_amount_display']); // Updated raw columns
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Bill $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Bill $model)
    {
        $start = request()->get('start_date') ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end = request()->get('end_date') ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return $model->newQuery()
            ->select(
                'bills.id',
                'bills.bill_id',
                'bills.bill_date as transaction_date',
                'bills.type', 
                'bills.status',
                'bills.notes',
                'venders.name as vendor_name',
                
                // --- TAX NAME (NEW) ---
                DB::raw('(
                    SELECT GROUP_CONCAT(t.name) 
                    FROM bill_products bp
                    JOIN taxes t ON FIND_IN_SET(t.id, bp.tax)
                    WHERE bp.bill_id = bills.id 
                    GROUP BY bp.bill_id 
                    LIMIT 1
                ) as tax_name'),

                // --- 1. TOTAL AMOUNT CALCULATION ---
                DB::raw('(
                    /* Sum of Products + Tax - Discount */
                    COALESCE((
                        SELECT SUM(
                            (bp.price * bp.quantity) 
                            - COALESCE(bp.discount, 0)
                            + ((bp.price * bp.quantity - COALESCE(bp.discount,0)) * (COALESCE(t.rate, 0) / 100))
                        )
                        FROM bill_products bp
                        LEFT JOIN taxes t ON FIND_IN_SET(t.id, bp.tax) > 0
                        WHERE bp.bill_id = bills.id
                    ), 0)
                    +
                    /* Sum of Accounts (Expenses) */
                    COALESCE((
                        SELECT SUM(ba.price) 
                        FROM bill_accounts ba
                        WHERE ba.ref_id = bills.id
                    ), 0)
                ) as total_amount'), 

                // --- 2. PAYMENTS TOTAL (For internal open_balance check) ---
                DB::raw('(
                    SELECT COALESCE(SUM(amount), 0)
                    FROM bill_payments
                    WHERE bill_id = bills.id
                ) as payments_total'),

                // --- 3. TAX AMOUNT (NEW) ---
                DB::raw('(
                    SELECT COALESCE(SUM(
                        (bp.price * bp.quantity - COALESCE(bp.discount,0)) * (COALESCE(t.rate, 0) / 100)
                    ), 0)
                    FROM bill_products bp
                    LEFT JOIN taxes t ON FIND_IN_SET(t.id, bp.tax) > 0
                    WHERE bp.bill_id = bills.id
                ) as tax_amount')
            )
            ->join('venders', 'venders.id', '=', 'bills.vender_id')
            
            // ==========================================================
            // 👇 MODIFICATION TO EXCLUDE ATTACHED PURCHASES 👇
            // ==========================================================
            ->leftJoin('purchases', 'purchases.txn_id', '=', 'bills.id')
            ->whereNull('purchases.txn_id')
            // ==========================================================
            
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->whereBetween('bills.bill_date', [$start, $end])
            ->orderBy('bills.bill_date', 'desc');
    
    }

    // ... (getBillTypeDisplay and getStatusDisplay helper methods remain unchanged)
    
    protected function getBillTypeDisplay($type)
    {
        $types = [
            'Bill'          => 'Bill',
            'Expense'       => 'Expense',
            'Check'         => 'Check',
            'Credit Card'   => 'Credit Card Expense',
        ];
        return $types[$type] ?? $type;
    }

    protected function getStatusDisplay($status, $openBalance)
    {
        if ($openBalance <= 0) {
            return '<span class="badge badge-success">Paid</span>';
        }
        if ($status == 0) {
            return '<span class="badge badge-secondary">Draft</span>';
        }
        return '<span class="badge badge-warning">Open</span>';
    }


    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        // Columns matching the user's requested display format
        return [
            Column::make('num')->title('Transaction ID'),
            Column::make('payee')->title('Name'), 
            Column::make('date')->title('Transaction date'),
            Column::make('memo')->title('Memo/Description'),
            Column::make('amount_display')->title('Amount')->addClass('text-right'),
            Column::make('tax_amount_display')->title('Tax amount')->addClass('text-right'),
            Column::make('tax_name_display')->title('Tax name'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'PurchaseList_' . date('YmdHis');
    }
    
    /**
     * Get the html builder software.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('bill-list-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'desc')
            ->parameters([
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
            ]);
            }
}