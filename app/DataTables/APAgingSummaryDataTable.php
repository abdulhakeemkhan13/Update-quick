<?php

// app/DataTables/APAgingSummaryDataTable.php
namespace App\DataTables;

use App\Models\Bill;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Column;
use Carbon\Carbon;

class APAgingSummaryDataTable extends DataTable
{
    public function dataTable($query)
    {
        $data = collect();
        $entries = $query->get();

        // Track totals
        $currentTotal = $days30Total = $days60Total = $days90Total = $days90PlusTotal = $grandTotal = 0;

        if ($entries->count() > 0) {
            foreach ($entries as $entry) {
                $buckets = [
                    'current' => $entry->current ?? 0,
                    'days_1_30' => $entry->days_1_30 ?? 0,
                    'days_31_60' => $entry->days_31_60 ?? 0,
                    'days_61_90' => $entry->days_61_90 ?? 0,
                    'days_90_plus' => $entry->days_90_plus ?? 0,
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

                $data->push([
                    'vendor_name' => $entry->vendor_name,
                    'current' => number_format($buckets['current'], 2),
                    'days_1_30' => number_format($buckets['days_1_30'], 2),
                    'days_31_60' => number_format($buckets['days_31_60'], 2),
                    'days_61_90' => number_format($buckets['days_61_90'], 2),
                    'days_90_plus' => number_format($buckets['days_90_plus'], 2),
                    'total_due' => '<strong>' . number_format($totalDue, 2) . '</strong>',
                ]);

                // Update totals
                $currentTotal += $buckets['current'];
                $days30Total += $buckets['days_1_30'];
                $days60Total += $buckets['days_31_60'];
                $days90Total += $buckets['days_61_90'];
                $days90PlusTotal += $buckets['days_90_plus'];
                $grandTotal += $totalDue;
            }

            // Totals row
            $data->push([
                'vendor_name' => '<strong>Total</strong>',
                'current' => '<strong>' . number_format($currentTotal, 2) . '</strong>',
                'days_1_30' => '<strong>' . number_format($days30Total, 2) . '</strong>',
                'days_31_60' => '<strong>' . number_format($days60Total, 2) . '</strong>',
                'days_61_90' => '<strong>' . number_format($days90Total, 2) . '</strong>',
                'days_90_plus' => '<strong>' . number_format($days90PlusTotal, 2) . '</strong>',
                'total_due' => '<strong>' . number_format($grandTotal, 2) . '</strong>',
                'DT_RowClass' => 'summary-total'
            ]);
        } else {
            $data->push([
                'vendor_name' => 'No data found for the selected period.',
                'current' => '',
                'days_1_30' => '',
                'days_31_60' => '',
                'days_61_90' => '',
                'days_90_plus' => '',
                'total_due' => '',
                'DT_RowClass' => 'no-data-row'
            ]);
        }

        return datatables()
            ->collection($data)
            ->rawColumns([
                'vendor_name',
                'current',
                'days_1_30',
                'days_31_60',
                'days_61_90',
                'days_90_plus',
                'total_due'
            ]);
    }

    public function query(Bill $model)
    {
        $start = request()->get('start_date')
            ?? request()->get('startDate')
            ?? Carbon::now()->startOfYear()->format('Y-m-d');

        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return $model->newQuery()
            ->select('venders.name as vendor_name')

            // Current
            ->selectRaw("
                SUM(CASE
                WHEN DATEDIFF('$end', bills.due_date) <= 0 THEN ( (bill_products.price * bill_products.quantity -
                    bill_products.discount) + COALESCE(( SELECT SUM((bp.price * bp.quantity - bp.discount) * (t.rate / 100)) FROM
                    bill_products bp LEFT JOIN taxes t ON FIND_IN_SET(t.id, bp.tax)> 0
                    WHERE bp.id = bill_products.id
                    ),0)
                    )
                    ELSE 0 END
                    ) as current
                    ")

            // 1–30
            ->selectRaw("
                SUM(CASE
                WHEN DATEDIFF('$end', bills.due_date) BETWEEN 1 AND 30
                THEN (
                (bill_products.price * bill_products.quantity - bill_products.discount)
                + COALESCE((
                SELECT SUM((bp.price * bp.quantity - bp.discount) * (t.rate / 100))
                FROM bill_products bp
                LEFT JOIN taxes t ON FIND_IN_SET(t.id, bp.tax) > 0
                WHERE bp.id = bill_products.id
                ),0)
                )
                ELSE 0 END
                ) as days_1_30
                ")

                        // 31–60
                ->selectRaw("
                SUM(CASE
                WHEN DATEDIFF('$end', bills.due_date) BETWEEN 31 AND 60
                THEN (
                (bill_products.price * bill_products.quantity - bill_products.discount)
                + COALESCE((
                SELECT SUM((bp.price * bp.quantity - bp.discount) * (t.rate / 100))
                FROM bill_products bp
                LEFT JOIN taxes t ON FIND_IN_SET(t.id, bp.tax) > 0
                WHERE bp.id = bill_products.id
                ),0)
                )
                ELSE 0 END
                ) as days_31_60
                ")

            // 61–90
            ->selectRaw("
            SUM(CASE
            WHEN DATEDIFF('$end', bills.due_date) BETWEEN 61 AND 90
            THEN (
            (bill_products.price * bill_products.quantity - bill_products.discount)
            + COALESCE((
            SELECT SUM((bp.price * bp.quantity - bp.discount) * (t.rate / 100))
            FROM bill_products bp
            LEFT JOIN taxes t ON FIND_IN_SET(t.id, bp.tax) > 0
            WHERE bp.id = bill_products.id
            ),0)
            )
            ELSE 0 END
            ) as days_61_90
            ")

                    // >90
                    ->selectRaw("
            SUM(CASE
            WHEN DATEDIFF('$end', bills.due_date) > 90
            THEN (
            (bill_products.price * bill_products.quantity - bill_products.discount)
            + COALESCE((
            SELECT SUM((bp.price * bp.quantity - bp.discount) * (t.rate / 100))
            FROM bill_products bp
            LEFT JOIN taxes t ON FIND_IN_SET(t.id, bp.tax) > 0
            WHERE bp.id = bill_products.id
            ),0)
            )
            ELSE 0 END
            ) as days_90_plus
            ")

                    // Payments total
                    ->selectRaw("(
            SELECT COALESCE(SUM(bpay.amount), 0)
            FROM bill_payments bpay
            WHERE bpay.bill_id = bills.id
            ) as pay_price")

            ->leftJoin('venders', 'venders.id', '=', 'bills.vender_id')
            ->leftJoin('bill_products', 'bill_products.bill_id', '=', 'bills.id')
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->where('bills.bill_date', '<=', $end)->groupBy('venders.name');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('aging-summary-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'desc')
            ->parameters([
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'scrollY' => '500px',
                'colReorder' => true,
                'scrollCollapse' => true,
                'createdRow' => "function(row, data) {
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
            Column::make('vendor_name')->title('Vendor Name'),
            Column::make('current')->title('Current'),
            Column::make('days_1_30')->title('1–30 DAYS'),
            Column::make('days_31_60')->title('31–60 DAYS'),
            Column::make('days_61_90')->title('61–90 DAYS'),
            Column::make('days_90_plus')->title('91 AND OVER'),
            Column::make('total_due')->title('Total'),
        ];
    }
}
