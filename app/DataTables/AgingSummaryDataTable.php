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
        $days15Total = 0;
        $days30Total = 0;
        $days45Total = 0;
        $daysMore45Total = 0;
        $grandTotal = 0;

        if ($entries->count() > 0) {
            foreach ($entries as $entry) {
                // Buckets with tax included (from SQL)
                $buckets = [
                    'current'      => $entry->current ?? 0,
                    'days_1_15'    => $entry->days_1_15 ?? 0,
                    'days_16_30'   => $entry->days_16_30 ?? 0,
                    'days_31_45'   => $entry->days_31_45 ?? 0,
                    'days_45_plus' => $entry->days_45_plus ?? 0,
                ];

                $payPrice = $entry->pay_price ?? 0;
                $bucketTotal = array_sum($buckets);

                // Allocate payments proportionally
                if ($bucketTotal > 0 && $payPrice > 0) {
                    foreach ($buckets as $key => $val) {
                        $share = $val / $bucketTotal;
                        $buckets[$key] = max(0, $val - ($payPrice * $share));
                    }
                }

                $totalDue = array_sum($buckets);

                // Add row
                $data->push([
                    'customer_name' => $entry->customer_name,
                    'current'       => number_format($buckets['current'], 2),
                    'days_1_15'     => number_format($buckets['days_1_15'], 2),
                    'days_16_30'    => number_format($buckets['days_16_30'], 2),
                    'days_31_45'    => number_format($buckets['days_31_45'], 2),
                    'days_45_plus'  => number_format($buckets['days_45_plus'], 2),
                    'total_due'     => '<strong>' . number_format($totalDue, 2) . '</strong>',
                ]);

                // Update totals
                $currentTotal    += $buckets['current'];
                $days15Total     += $buckets['days_1_15'];
                $days30Total     += $buckets['days_16_30'];
                $days45Total     += $buckets['days_31_45'];
                $daysMore45Total += $buckets['days_45_plus'];
                $grandTotal      += $totalDue;
            }

            // Totals row
            $data->push([
                'customer_name' => '<strong>Total</strong>',
                'current'       => '<strong>' . number_format($currentTotal, 2) . '</strong>',
                'days_1_15'     => '<strong>' . number_format($days15Total, 2) . '</strong>',
                'days_16_30'    => '<strong>' . number_format($days30Total, 2) . '</strong>',
                'days_31_45'    => '<strong>' . number_format($days45Total, 2) . '</strong>',
                'days_45_plus'  => '<strong>' . number_format($daysMore45Total, 2) . '</strong>',
                'total_due'     => '<strong>' . number_format($grandTotal, 2) . '</strong>',
                'DT_RowClass'   => 'summary-total'
            ]);
        } else {
            $data->push([
                'customer_name' => 'No data found for the selected period.',
                'current'       => '',
                'days_1_15'     => '',
                'days_16_30'    => '',
                'days_31_45'    => '',
                'days_45_plus'  => '',
                'total_due'     => '',
                'DT_RowClass'   => 'no-data-row'
            ]);
        }

        return datatables()
            ->collection($data)
            ->rawColumns([
                'customer_name',
                'current',
                'days_1_15',
                'days_16_30',
                'days_31_45',
                'days_45_plus',
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

            // 1–15 Days
            ->selectRaw("
                SUM(CASE 
                    WHEN DATEDIFF('$end', invoices.due_date) BETWEEN 1 AND 15
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
                ) as days_1_15
            ")

            // 16–30
            ->selectRaw("
                SUM(CASE 
                    WHEN DATEDIFF('$end', invoices.due_date) BETWEEN 16 AND 30
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
                ) as days_16_30
            ")

            // 31–45
            ->selectRaw("
                SUM(CASE 
                    WHEN DATEDIFF('$end', invoices.due_date) BETWEEN 31 AND 45
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
                ) as days_31_45
            ")

            // >45
            ->selectRaw("
                SUM(CASE 
                    WHEN DATEDIFF('$end', invoices.due_date) > 45
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
                ) as days_45_plus
            ")

            // Payments total (not per bucket – allocated in PHP)
            ->selectRaw("(
                SELECT COALESCE(SUM(ipay.amount), 0)
                FROM invoice_payments ipay
                WHERE ipay.invoice_id = invoices.id
            ) as pay_price")

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
            Column::make('days_1_15')->title('1-15 DAYS'),
            Column::make('days_16_30')->title('16-30 DAYS'),
            Column::make('days_31_45')->title('31-45 DAYS'),
            Column::make('days_45_plus')->title('> 45 DAYS'),
            Column::make('total_due')->title('Total'),
        ];
    }
}
