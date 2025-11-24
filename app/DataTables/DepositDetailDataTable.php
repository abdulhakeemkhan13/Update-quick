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
            // Test data availability
            $invoicePaymentCount = DB::table('invoice_payments')
                ->join('invoices', 'invoices.id', '=', 'invoice_payments.invoice_id')
                ->where('invoices.created_by', $ownerId)
                ->count();

            $revenueCount = DB::table('revenues')
                ->where('created_by', $ownerId)
                ->count();

            \Log::info('Data availability check', [
                'invoice_payments' => $invoicePaymentCount,
                'revenues' => $revenueCount
            ]);

            // Build the main query for invoice payments (customer deposits)
            $invoicePaymentsQuery = DB::table('invoice_payments')
                ->join('invoices', 'invoices.id', '=', 'invoice_payments.invoice_id')
                ->join('customers', 'customers.id', '=', 'invoices.customer_id')
                ->where('invoices.created_by', $ownerId)
                ->whereBetween('invoice_payments.date', [$start, $end])
                ->select([
                    'invoice_payments.date as transaction_date',
                    DB::raw("'Payment' as transaction_type"),
                    'invoices.invoice_id as num',
                    'customers.name as customer_full_name',
                    DB::raw("'-' as vendor"),
                    DB::raw('COALESCE(invoice_payments.description, CONCAT("Payment for Invoice #", invoices.invoice_id)) as memo_description'),
                    DB::raw("CASE WHEN invoice_payments.add_receipt IS NOT NULL THEN 'Cleared' ELSE 'Uncleared' END as cleared"),
                    'invoice_payments.amount as amount'
                ]);

            // Build query for other revenues (direct deposits)
            $revenuesQuery = DB::table('revenues')
                ->leftJoin('customers', 'customers.id', '=', 'revenues.customer_id')
                ->where('revenues.created_by', $ownerId)
                ->whereBetween('revenues.date', [$start, $end])
                ->select([
                    'revenues.date as transaction_date',
                    DB::raw("'Deposit' as transaction_type"),
                    'revenues.reference as num',
                    DB::raw('COALESCE(customers.name, "Direct Deposit") as customer_full_name'),
                    DB::raw("'-' as vendor"),
                    'revenues.description as memo_description',
                    DB::raw("'Cleared' as cleared"),
                    'revenues.amount as amount'
                ]);

            // Combine both queries using UNION
            $query = $invoicePaymentsQuery->unionAll($revenuesQuery);

            // Apply customer filter
            if (!empty($selectedCustomer)) {
                $query->where(function ($q) use ($selectedCustomer) {
                    $q->where('customers.name', 'LIKE', '%' . $selectedCustomer . '%');
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
                'order' => [[0, 'asc']], // Sort by Transaction Date ascending
                'colReorder' => true,
                'fixedHeader' => true,
                'scrollY' => '420px',
                'scrollX' => true,
                'scrollCollapse' => true,
                'columnDefs' => [
                    [
                        'targets' => [7], // Amount column
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