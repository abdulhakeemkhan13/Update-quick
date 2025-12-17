<?php

namespace App\DataTables;

use App\Models\Invoice;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Column;
use Carbon\Carbon;

class AgingSummaryDataTable extends DataTable
{
    public function dataTable($query)
    {
        $data = collect();

        $entries = $query->get();

        // Track totals
        $currentTotal = 0;
        $days30Total = 0;
        $days60Total = 0;
        $days90Total = 0;
        $daysMore90Total = 0;
        $grandTotal = 0;

        if ($entries->count() > 0) {
            foreach ($entries as $entry) {
                $customerName = $entry->customer_name;
                
                // Buckets with tax included (from SQL)
                $buckets = [
                    'current'      => $entry->current ?? 0,
                    'days_1_30'    => $entry->days_1_30 ?? 0,
                    'days_31_60'   => $entry->days_31_60 ?? 0,
                    'days_61_90'   => $entry->days_61_90 ?? 0,
                    'days_90_plus' => $entry->days_90_plus ?? 0,
                ];

                $payPrice = $entry->pay_price ?? 0;
                $customerCreditAmount = $entry->customer_credit_amount ?? 0;
                $creditNoteAmount = $entry->credit_note_amount ?? 0;
                $bucketTotal = array_sum($buckets);

                // Actual payment = total payments - overpayments (customer credit)
                // Overpayments are stored in transactions table as 'Customer Credit'
                $actualPayment = $payPrice - $customerCreditAmount;

                // Total deductions = actual payments + credit notes applied to invoices
                $totalDeductions = $actualPayment + $creditNoteAmount;

                // Allocate payments and credit notes proportionally
                if ($bucketTotal > 0 && $totalDeductions > 0) {
                    foreach ($buckets as $key => $val) {
                        $share = $val / $bucketTotal;
                        $buckets[$key] = max(0, $val - ($totalDeductions * $share));
                    }
                }

                $totalDue = array_sum($buckets);

                // Note: Overpayments are already included in invoice_payments amount
                // so we should NOT subtract them again from the bucket totals.
                // Unapplied credit notes are not linked to any invoice, so they also
                // should not affect the aging buckets.

                // Skip customers with zero balance
                if (round($totalDue, 2) == 0.00) {
                    continue;
                }

                // Add row
                $data->push([
                    'customer_name' => $customerName,
                    'current'       => number_format($buckets['current'], 2),
                    'days_1_30'     => number_format($buckets['days_1_30'], 2),
                    'days_31_60'    => number_format($buckets['days_31_60'], 2),
                    'days_61_90'    => number_format($buckets['days_61_90'], 2),
                    'days_90_plus'  => number_format($buckets['days_90_plus'], 2),
                    'total_due'     => '<strong>' . number_format($totalDue, 2) . '</strong>',
                ]);

                // Update totals
                $currentTotal    += $buckets['current'];
                $days30Total     += $buckets['days_1_30'];
                $days60Total     += $buckets['days_31_60'];
                $days90Total     += $buckets['days_61_90'];
                $daysMore90Total += $buckets['days_90_plus'];
                $grandTotal      += $totalDue;
            }

            if ($data->count() > 0) {
                // Totals row
                $data->push([
                    'customer_name' => '<strong>Total</strong>',
                    'current'       => '<strong>' . number_format($currentTotal, 2) . '</strong>',
                    'days_1_30'     => '<strong>' . number_format($days30Total, 2) . '</strong>',
                    'days_31_60'    => '<strong>' . number_format($days60Total, 2) . '</strong>',
                    'days_61_90'    => '<strong>' . number_format($days90Total, 2) . '</strong>',
                    'days_90_plus'  => '<strong>' . number_format($daysMore90Total, 2) . '</strong>',
                    'total_due'     => '<strong>' . number_format($grandTotal, 2) . '</strong>',
                    'DT_RowClass'   => 'summary-total'
                ]);
            } else {
                $data->push([
                    'customer_name' => 'No data found for the selected period.',
                    'current'       => '',
                    'days_1_30'     => '',
                    'days_31_60'    => '',
                    'days_61_90'    => '',
                    'days_90_plus'  => '',
                    'total_due'     => '',
                    'DT_RowClass'   => 'no-data-row'
                ]);
            }
        } else {
            $data->push([
                'customer_name' => 'No data found for the selected period.',
                'current'       => '',
                'days_1_30'     => '',
                'days_31_60'    => '',
                'days_61_90'    => '',
                'days_90_plus'  => '',
                'total_due'     => '',
                'DT_RowClass'   => 'no-data-row'
            ]);
        }

        return datatables()
            ->collection($data)
            ->rawColumns([
                'customer_name',
                'current',
                'days_1_30',
                'days_31_60',
                'days_61_90',
                'days_90_plus',
                'total_due'
            ]);
    }

    public function query(Invoice $model)
    {
        // dd(request()->all(), request()->get('startDate'), request()->get('endDate'));
        $start = request()->get('startDate') ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end   = request()->get('endDate') ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return $model->newQuery()
            ->select('customers.name as customer_name')

            // Current
            ->selectRaw("
                SUM(CASE 
                    WHEN DATEDIFF('$end', invoices.due_date) <= 0
                    THEN (
                        (invoice_products.price * invoice_products.quantity - invoice_products.discount)
                        + COALESCE((
                            SELECT SUM((ip.price * ip.quantity - ip.discount) * (t.rate / 100))
                            FROM invoice_products ip
                            LEFT JOIN taxes t ON FIND_IN_SET(t.id, ip.tax) > 0
                            WHERE ip.id = invoice_products.id
                        ),0)
                    )
                    ELSE 0 END
                ) as current
            ")

            // 1–30 Days
            ->selectRaw("
                SUM(CASE 
                    WHEN DATEDIFF('$end', invoices.due_date) BETWEEN 1 AND 30
                    THEN (
                        (invoice_products.price * invoice_products.quantity - invoice_products.discount)
                        + COALESCE((
                            SELECT SUM((ip.price * ip.quantity - ip.discount) * (t.rate / 100))
                            FROM invoice_products ip
                            LEFT JOIN taxes t ON FIND_IN_SET(t.id, ip.tax) > 0
                            WHERE ip.id = invoice_products.id
                        ),0)
                    )
                    ELSE 0 END
                ) as days_1_30
            ")

            // 31–60
            ->selectRaw("
                SUM(CASE 
                    WHEN DATEDIFF('$end', invoices.due_date) BETWEEN 31 AND 60
                    THEN (
                        (invoice_products.price * invoice_products.quantity - invoice_products.discount)
                        + COALESCE((
                            SELECT SUM((ip.price * ip.quantity - ip.discount) * (t.rate / 100))
                            FROM invoice_products ip
                            LEFT JOIN taxes t ON FIND_IN_SET(t.id, ip.tax) > 0
                            WHERE ip.id = invoice_products.id
                        ),0)
                    )
                    ELSE 0 END
                ) as days_31_60
            ")

            // 61–90
            ->selectRaw("
                SUM(CASE 
                    WHEN DATEDIFF('$end', invoices.due_date) BETWEEN 61 AND 90
                    THEN (
                        (invoice_products.price * invoice_products.quantity - invoice_products.discount)
                        + COALESCE((
                            SELECT SUM((ip.price * ip.quantity - ip.discount) * (t.rate / 100))
                            FROM invoice_products ip
                            LEFT JOIN taxes t ON FIND_IN_SET(t.id, ip.tax) > 0
                            WHERE ip.id = invoice_products.id
                        ),0)
                    )
                    ELSE 0 END
                ) as days_61_90
            ")

            // >90
            ->selectRaw("
                SUM(CASE 
                    WHEN DATEDIFF('$end', invoices.due_date) > 90
                    THEN (
                        (invoice_products.price * invoice_products.quantity - invoice_products.discount)
                        + COALESCE((
                            SELECT SUM((ip.price * ip.quantity - ip.discount) * (t.rate / 100))
                            FROM invoice_products ip
                            LEFT JOIN taxes t ON FIND_IN_SET(t.id, ip.tax) > 0
                            WHERE ip.id = invoice_products.id
                        ),0)
                    )
                    ELSE 0 END
                ) as days_90_plus
            ")

            // Payments total (not per bucket – allocated in PHP)
            ->selectRaw("(
                SELECT COALESCE(SUM(ipay.amount), 0)
                FROM invoice_payments ipay
                WHERE ipay.invoice_id = invoices.id
            ) as pay_price")

            // Customer credit (overpayment) from transactions table
            // Using JOINs instead of nested subquery for better performance
            ->selectRaw("(
                SELECT COALESCE(SUM(tr_credit.amount), 0)
                FROM transactions tr_credit
                JOIN transactions tr_inv ON tr_inv.payment_no = tr_credit.payment_no
                JOIN invoice_payments ipay ON tr_inv.payment_id = ipay.id
                WHERE ipay.invoice_id = invoices.id
                AND tr_inv.category = 'Invoice'
                AND tr_credit.category = 'Customer Credit'
            ) as customer_credit_amount")

            // Credit notes applied to invoices (only those with payment_id > 0, meaning they're applied)
            ->selectRaw("(
                SELECT COALESCE(SUM(cn.amount), 0)
                FROM credit_notes cn
                WHERE cn.invoice = invoices.id
                AND cn.payment_id > 0
            ) as credit_note_amount")

            ->leftJoin('customers', 'customers.id', '=', 'invoices.customer_id')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', '=', 'invoices.id')
            ->where('invoices.created_by', \Auth::user()->creatorId())
            ->where('invoices.issue_date', '<=' ,$end)
            ->groupBy('customers.name');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('aging-summary-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'desc')
            ->parameters([
                'paging'         => false,
                'searching'      => false,
                'info'           => false,
                'ordering'       => false,
                'scrollY'        => '500px',
                'colReorder'     => true,
                'scrollCollapse' => true,
                'createdRow'     => "function(row, data) {
                    $('td:eq(1), td:eq(2), td:eq(3), td:eq(4), td:eq(5), td:eq(6)', row).addClass('text-center');
                    if ($(row).hasClass('summary-total')) {
                        $(row).addClass('font-weight-bold bg-light');
                    }
                }"
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('customer_name')->title('Customer Name'),
            Column::make('current')->title('Current'),
            Column::make('days_1_30')->title('1-30 DAYS'),
            Column::make('days_31_60')->title('31-60 DAYS'),
            Column::make('days_61_90')->title('61-90 DAYS'),
            Column::make('days_90_plus')->title('> 90 DAYS'),
            Column::make('total_due')->title('Total'),
        ];
    }
}
