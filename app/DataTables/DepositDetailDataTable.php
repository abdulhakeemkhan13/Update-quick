<?php

namespace App\DataTables;

use App\Models\Customer;
use App\Models\Vender;
use App\Models\Payment;
use App\Models\Revenue;
use App\Models\InvoicePayment;
use App\Models\BillPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class DepositDetailDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->of($query)
            ->editColumn('transaction_date', function ($row) {
                return isset($row->transaction_date) ? date('m/d/Y', strtotime($row->transaction_date)) : '-';
            })
            ->editColumn('transaction_type', function ($row) {
                $type = $row->transaction_type ?? 'Deposit';
                // Add badges for different transaction types
                $badgeClass = $type === 'Payment' ? 'badge-successs' : 'badge-infos';
                return '<span class="badges ' . $badgeClass . '">' . $type . '</span>';
            })
            ->editColumn('num', function ($row) {
                return $row->num ?? '-';
            })
            ->editColumn('customer_full_name', function ($row) {
                $name = $row->customer_full_name ?? 'Unknown Customer';
                return '<strong>' . htmlspecialchars($name) . '</strong>';
            })
            ->editColumn('vendor', function ($row) {
                return $row->vendor ?? '-';
            })
            ->editColumn('memo_description', function ($row) {
                $memo = $row->memo_description ?? 'No description';
                return htmlspecialchars($memo);
            })
            ->editColumn('cleared', function ($row) {
                $cleared = $row->cleared ?? 'Uncleared';
                $badgeClass = $cleared === 'Cleared' ? 'badge-successs' : 'badge-warnings';
                return '<span class="badges ' . $badgeClass . '">' . $cleared . '</span>';
            })
            ->editColumn('amount', function ($row) {
                try {
                    $amount = is_object($row) ? ($row->amount ?? 0) : (isset($row['amount']) ? $row['amount'] : 0);
                    $formattedAmount = \Auth::user()->priceFormat((float) $amount);
                    return '<span class="text-success font-weight-bold">' . $formattedAmount . '</span>';
                } catch (\Exception $e) {
                    return '<span class="text-muted">$0.00</span>';
                }
            })
            ->rawColumns(['transaction_type', 'customer_full_name', 'cleared', 'amount']);
    }

    public function query()
    {
        $user = Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();

        // Get start and end dates from request, fallback to defaults
        $start = request()->get('start_date')
            ?? request()->get('startDate')
            ?? date('Y-01-01');
        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? date('Y-m-d');
        $selectedCustomer = request('customer_name', '');
        $selectedVendor = request('vendor_name', '');

        // Debug logging
        \Log::info('DepositDetail Query Start', [
            'user_id' => $user->id,
            'user_type' => $user->type,
            'owner_id' => $ownerId,
            'start_date' => $start,
            'end_date' => $end,
            'selected_customer' => $selectedCustomer,
            'selected_vendor' => $selectedVendor
        ]);

        try {
           
            // Build the main query for invoice payments (customer deposits)
            $invoicePaymentsQuery = DB::table('invoice_payments')
    ->join('invoices', 'invoices.id', '=', 'invoice_payments.invoice_id')
    ->join('customers', 'customers.id', '=', 'invoices.customer_id')
    ->leftJoin('bank_accounts', 'bank_accounts.id', '=', 'invoice_payments.account_id')
    ->where('invoices.created_by', $ownerId)
    ->whereBetween('invoice_payments.date', [$start, $end])
    ->select([
        'invoice_payments.date as transaction_date',
        DB::raw("'Payment' as transaction_type"),
        'invoices.invoice_id as num',
        'customers.name as customer_full_name',
        DB::raw("'-' as vendor"),
        DB::raw("
            COALESCE(
                invoice_payments.description,
                CONCAT('Payment for Invoice #', invoices.invoice_id)
            ) as memo_description
        "),
        DB::raw("
            CASE
                WHEN EXISTS (
                    SELECT 1
                    FROM transactions t_credit
                    JOIN transactions t_invoice
                        ON t_credit.payment_no = t_invoice.payment_no
                    WHERE t_credit.category = 'Customer Credit'
                      AND t_invoice.category = 'Invoice'
                      AND t_invoice.payment_id = invoice_payments.id
                      AND t_credit.amount > 0
                )
                THEN 'Uncleared'
                WHEN invoice_payments.add_receipt IS NOT NULL
                THEN 'Cleared'
                ELSE 'Uncleared'
            END as cleared
        "),
        DB::raw("
            (
                invoice_payments.amount
                - COALESCE(
                    (
                        SELECT SUM(t_credit.amount)
                        FROM transactions t_credit
                        JOIN transactions t_invoice
                            ON t_credit.payment_no = t_invoice.payment_no
                        WHERE t_credit.category = 'Customer Credit'
                          AND t_invoice.category = 'Invoice'
                          AND t_invoice.payment_id = invoice_payments.id
                    ),
                    0
                )
            ) AS amount
        "),
        'bank_accounts.bank_name as bank'
    ]);

                // dd($invoicePaymentsQuery->get());
            // Build query for Customer Credits (Extra paid amounts from transactions)
            // Linked to Invoice Payments via payment_no to ensure correct Owner/Context
            $customerCreditsQuery = DB::table('transactions as t_credit')
                ->join('transactions as t_link', 't_link.payment_no', '=', 't_credit.payment_no')
                ->join('invoice_payments as ip', 'ip.id', '=', 't_link.payment_id')
                ->join('invoices as i', 'i.id', '=', 'ip.invoice_id')
                ->join('customers', 'customers.id', '=', 't_credit.user_id')
                ->where('t_credit.category', 'Customer Credit')
                ->where('t_link.category', 'Invoice')
                ->where('i.created_by', $ownerId)
                ->whereBetween('t_credit.date', [$start, $end])
                ->select([
                    't_credit.date as transaction_date',
                    DB::raw("'Customer Credit' as transaction_type"),
                    't_credit.payment_no as num',
                    'customers.name as customer_full_name',
                    DB::raw("'-' as vendor"),
                    't_credit.description as memo_description',
                    DB::raw("'Uncleared' as cleared"),
                    't_credit.amount as amount',
                    DB::raw("'-' as bank")
                ])
                ->distinct();

            // Build query for Credit Memos (Negative amounts)
            // "subtract credit_mamoes amount" - User Request
            $creditNotesQuery = DB::table('credit_notes')
                ->join('customers', 'customers.id', '=', 'credit_notes.customer')
                ->where('credit_notes.created_by', $ownerId)
                ->whereBetween('credit_notes.date', [$start, $end])
                ->select([
                    'credit_notes.date as transaction_date',
                    DB::raw("'Credit Memo' as transaction_type"),
                    DB::raw("CONCAT('CN-', credit_notes.id) as num"), // Or credit_notes.id if integer preferred
                    'customers.name as customer_full_name',
                    DB::raw("'-' as vendor"),
                    'credit_notes.description as memo_description',
                    DB::raw("'Uncleared' as cleared"), // Credit notes are uncleared until applied
                    DB::raw("(-1 * credit_notes.amount) as amount"),
                    DB::raw("'-' as bank")
                ]);

            // Build query for Deposits
            $depositsQuery = DB::table('deposits')
                ->leftJoin('customers', 'customers.id', '=', 'deposits.customer_id')
                ->leftJoin('bank_accounts', 'bank_accounts.id', '=', 'deposits.bank_id')
                ->whereBetween('deposits.txn_date', [$start, $end])
                ->select([
                    'deposits.txn_date as transaction_date',
                    DB::raw("'Deposit' as transaction_type"),
                    'deposits.doc_number as num',
                    DB::raw('COALESCE(customers.name, "Direct Deposit") as customer_full_name'),
                    DB::raw("'-' as vendor"),
                    'deposits.private_note as memo_description',
                    DB::raw("'Cleared' as cleared"),
                    DB::raw('-deposits.total_amt as amount'),
                    'bank_accounts.bank_name as bank'
                ]);

            // Combine all queries using UNION
            $query = $invoicePaymentsQuery
                ->unionAll($depositsQuery);

            // Apply customer filter
            if (!empty($selectedCustomer)) {
                $query->where(function ($q) use ($selectedCustomer) {
                    $q->where('customer_full_name', 'LIKE', '%' . $selectedCustomer . '%');
                });
            }

            // For union queries, we need to wrap in a subquery to apply ORDER BY
            $finalQuery = DB::table(DB::raw("({$query->toSql()}) as combined_deposits"))
                ->mergeBindings($query)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('customer_full_name', 'asc');

            \Log::info('Final query SQL', [
                'sql' => $finalQuery->toSql(),
                'bindings' => $finalQuery->getBindings()
            ]);

            $resultCount = $finalQuery->count();
            \Log::info('Query result count: ' . $resultCount);

            if ($resultCount > 0) {
                $sample = $finalQuery->limit(3)->get();
                \Log::info('Sample results', ['data' => $sample]);
            }

            return $finalQuery;

        } catch (\Exception $e) {
            \Log::error('DepositDetail Query Exception', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return empty query to prevent 500 error
            return DB::table('invoice_payments')
                ->select([
                    DB::raw('NULL as transaction_date'),
                    DB::raw('NULL as transaction_type'),
                    DB::raw('NULL as num'),
                    DB::raw('NULL as customer_full_name'),
                    DB::raw('NULL as vendor'),
                    DB::raw('NULL as memo_description'),
                    DB::raw('NULL as cleared'),
                    DB::raw('0 as amount')
                ])
                ->whereRaw('1 = 0'); // Return empty results
        }
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('customer-balance-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('rt')
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'order' => [[0, 'asc'], [1, 'asc']], // Sort by Bank then Transaction Date ascending
                'colReorder' => true,
                'fixedHeader' => true,
                'scrollY' => '420px',
                'scrollX' => true,
                'scrollCollapse' => true,
                'rowGroup' => [
                    'dataSrc' => 'bank',
                    'startRender' => 'function ( rows, group ) {
                        return \'<tr class="group-header" data-group="\' + group + \'"><td colspan="9"><i class="fa fa-plus"></i> \' + group + \'</td></tr>\';
                    }'
                ],
                'initComplete' => 'function() {
                    var table = this.api();
                    $(\'tr.group-header\').each(function() {
                        var group = $(this).data(\'group\');
                        var groupRows = table.rows().nodes().to$().filter(function() {
                            return $(this).find(\'td:first\').text() === group;
                        });
                        groupRows.hide();
                    });
                    $(\'tr.group-header\').on(\'click\', function() {
                        var group = $(this).data(\'group\');
                        var groupRows = table.rows().nodes().to$().filter(function() {
                            return $(this).find(\'td:first\').text() === group;
                        });
                        groupRows.toggle();
                        var icon = $(this).find(\'i\');
                        icon.toggleClass(\'fa-plus fa-minus\');
                    });
                }',
                'columnDefs' => [
                    [
                        'targets' => [8], // Amount column
                        'className' => 'text-right'
                    ]
                ],
                'language' => [
                    'emptyTable' => 'No data found for the selected period.',
                    'zeroRecords' => 'No data found.'
                ]
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('bank')->title(__('Bank'))->visible(true)->width('10%'),
            Column::make('transaction_date')->title(__('Transaction Date'))->visible(true)->width('12%'),
            Column::make('transaction_type')->title(__('Transaction Type'))->visible(true)->width('12%'),
            Column::make('num')->title(__('Num'))->visible(true)->width('10%'),
            Column::make('customer_full_name')->title(__('Customer Full Name'))->visible(true)->width('18%'),
            Column::make('vendor')->title(__('Vendor'))->visible(true)->width('15%'),
            Column::make('memo_description')->title(__('Memo/Description'))->visible(true)->width('18%'),
            Column::make('cleared')->title(__('Cleared'))->visible(true)->width('10%'),
            Column::make('amount')->title(__('Amount'))->visible(true)->addClass('text-right')->width('10%'),
        ];
    }

    protected function filename(): string
    {
        return 'DepositDetail_' . date('YmdHis');
    }
}