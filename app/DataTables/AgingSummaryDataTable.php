<?php

namespace App\DataTables;

use App\Models\Invoice;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Column;
use Carbon\Carbon;

class AgingSummaryDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $data = collect();

        // Fetches the data, which includes invoice aging buckets, payments,
        // and aggregated unapplied credits/overpayments (per customer subqueries).
        $entries = $query->get();

        // Track totals
        $currentTotal = 0;
        $days30Total = 0;
        $days60Total = 0;
        $days90Total = 0;
        $daysMore90Total = 0;
        $grandTotal = 0;

        if ($entries->count() > 0) {
            // Group the results by customer name to correctly aggregate invoice data,
            // and use the single customer-level credit/overpayment values.
            $customerData = $entries->groupBy('customer_name')->map(function ($invoices) {
                // Sum all aging buckets and payments across all invoices for this customer
                $buckets = [
                    'current'      => $invoices->sum('current'),
                    'day_1_30'     => $invoices->sum('day_1_30'),
                    'day_30_60'    => $invoices->sum('day_30_60'),
                    'days_61_90'   => $invoices->sum('days_61_90'),
                    'days_90_plus' => $invoices->sum('days_90_plus'),
                ];

                $payPrice = $invoices->sum('pay_price');
                $bucketTotal = array_sum($buckets);

                // Allocate payments proportionally to the positive aging buckets
                if ($bucketTotal > 0 && $payPrice > 0) {
                    foreach ($buckets as $key => $val) {
                        $share = $val / $bucketTotal;
                        // Subtract the payment share, ensuring the result is not negative
                        $buckets[$key] = max(0, $val - ($payPrice * $share));
                    }
                }

                // Calculate Total Due: Sum of aged invoices (after payments) MINUS unapplied credits
                $unappliedCredits = $invoices->first()->unapplied_credits ?? 0;
                $totalDue = array_sum($buckets) - $unappliedCredits;

                return [
                    'customer_name' => $invoices->first()->customer_name,
                    'unapplied_credits' => $unappliedCredits,
                    'buckets'       => $buckets,
                    'total_due'     => $totalDue,
                ];
            });


            foreach ($customerData as $entry) {
                // Add row
                $data->push([
                    'customer_name' => $entry['customer_name'],
                    'current'       => number_format($entry['buckets']['current'], 2),
                    'day_1_30'      => number_format($entry['buckets']['day_1_30'], 2),
                    'day_30_60'     => number_format($entry['buckets']['day_30_60'], 2),
                    'days_61_90'    => number_format($entry['buckets']['days_61_90'], 2),
                    'days_90_plus'  => number_format($entry['buckets']['days_90_plus'], 2),
                    'total_due'     => '<strong>' . number_format($entry['total_due'], 2) . '</strong>',
                ]);

                // Update totals
                $currentTotal      += $entry['buckets']['current'];
                $days15Total       += $entry['buckets']['day_1_30'];
                $days30Total       += $entry['buckets']['day_30_60'];
                $days45Total       += $entry['buckets']['days_61_90'];
                $daysMore45Total   += $entry['buckets']['days_90_plus'];
                $grandTotal        += $entry['total_due'];
            }

            // Totals row
            $data->push([
                'customer_name' => '<strong>Total</strong>',
                'current'       => '<strong>' . number_format($currentTotal, 2) . '</strong>',
                'day_1_30'      => '<strong>' . number_format($days15Total, 2) . '</strong>',
                'day_30_60'     => '<strong>' . number_format($days30Total, 2) . '</strong>',
                'days_61_90'    => '<strong>' . number_format($days45Total, 2) . '</strong>',
                'days_90_plus'  => '<strong>' . number_format($daysMore45Total, 2) . '</strong>',
                'total_due'     => '<strong>' . number_format($grandTotal, 2) . '</strong>',
                'DT_RowClass'   => 'summary-total'
            ]);
        } else {
            $data->push([
                'customer_name' => 'No data found for the selected period.',
                'current'       => '',
                'day_1_30'      => '',
                'day_30_60'     => '',
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
                'day_1_30',
                'day_30_60',
                'days_61_90',
                'days_90_plus',
                'total_due'
            ]);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Invoice $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Invoice $model)
    {
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
                ) as day_1_30
            ")

            // 31–60 Days
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
                ) as day_30_60
            ")

            // 61–90 Days
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

            // 91+ Days
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

            // Payments total
            ->selectRaw("(
                SELECT COALESCE(SUM(ipay.amount), 0)
                FROM invoice_payments ipay
                WHERE ipay.invoice_id = invoices.id
            ) as pay_price")

            // Unapplied credit notes
            ->selectRaw("(
                SELECT COALESCE(SUM(cn.amount), 0)
                FROM credit_notes cn
                WHERE cn.customer = invoices.customer_id
                  AND cn.date <= '$end'
                  AND cn.created_by = invoices.created_by
                  AND (cn.invoice IS NULL OR cn.invoice = 0)
            ) as unapplied_credits")


            
            ->leftJoin('customers', 'customers.id', '=', 'invoices.customer_id')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', '=', 'invoices.id')
            ->where('invoices.created_by', \Auth::user()->creatorId())
            ->where('invoices.issue_date', '<=' ,$end)
            // Group by all non-aggregated columns, including invoice ID to preserve
            // the distinct pay_price and aging buckets before PHP aggregation.
            ->groupBy('customers.name', 'invoices.customer_id', 'invoices.created_by', 'invoices.id');
    }

    /**
     * Optional method if you want to use the html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('aging-summary-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'desc')
            ->parameters([
                'paging'          => false,
                'searching'       => false,
                'info'            => false,
                'ordering'        => false,
                'scrollY'         => '500px',
                'colReorder'      => true,
                'scrollCollapse'  => true,
                'createdRow'      => "function(row, data) {
                    $('td:eq(1), td:eq(2), td:eq(3), td:eq(4), td:eq(5), td:eq(6)', row).addClass('text-center');
                    if ($(row).hasClass('summary-total')) {
                        $(row).addClass('font-weight-bold bg-light');
                    }
                }"
            ]);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            Column::make('customer_name')->title('Customer Name'),
            Column::make('current')->title('Current'),
            Column::make('day_1_30')->title('1-30 DAYS'),
            Column::make('day_30_60')->title('31-60 DAYS'),
            Column::make('days_61_90')->title('61-90 DAYS'),
            Column::make('days_90_plus')->title('91 AND OVER'),
            Column::make('total_due')->title('Total'),
        ];
    }
}