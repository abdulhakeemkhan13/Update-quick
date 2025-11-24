<?php

namespace App\DataTables;

use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class OpenPurchaseOrderList extends DataTable
{
    public function dataTable($query)
    {
        $data = collect($query->get());

        $grandTotal = [
            'amount' => 0,
            'open_balance' => 0,
        ];

        $finalData = collect();
        $vendors = $data->groupBy('vendor_name');

        foreach ($vendors as $vendor => $rowsByVendor) {
            $vendorTotals = [
                'amount' => 0,
                'open_balance' => 0,
            ];

            // Vendor header row
            $finalData->push((object) [
                'transaction_date' => '',
                'vendor' => $vendor,
                'transaction' => '<span class="" data-bucket="' . \Str::slug($vendor) . '">
                    <span class="icon">▼</span> <strong>' . $vendor . '</strong></span>',
                'memo' => '',
                'ship_via' => '',
                'amount' => '',
                'open_balance' => '',
                'isParent' => true,
                'isVendor' => true,
            ]);

            foreach ($rowsByVendor as $row) {
                // Show purchase number instead of bill
                $row->transaction = \Auth::user()->purchaseNumberFormat($row->purchase_number ?? $row->purchase_id);
                $row->memo = $row->memo ?? '';
                $row->ship_via = $row->ship_via ?? '';

                $vendorTotals['amount'] += $row->amount;
                $vendorTotals['open_balance'] += $row->open_balance;

                $row->vendor = $vendor;
                $finalData->push($row);
            }

            // Vendor subtotal
            $finalData->push((object) [
                'vendor' => $vendor,
                'transaction_date' => '',
                'transaction' => "<strong>Subtotal for {$vendor}</strong>",
                'memo' => '',
                'ship_via' => '',
                'amount' => $vendorTotals['amount'],
                'open_balance' => $vendorTotals['open_balance'],
                'isSubtotal' => true,
            ]);

            // Update grand totals
            foreach ($vendorTotals as $key => $val) {
                $grandTotal[$key] += $val;
            }

            // Spacing row
            $finalData->push((object) [
                'vendor' => $vendor,
                'transaction_date' => '',
                'transaction' => '',
                'memo' => '',
                'ship_via' => '',
                'amount' => '',
                'open_balance' => '',
                'isPlaceholder' => true,
            ]);
        }

        // Grand total
        $finalData->push((object) [
            'transaction_date' => '',
            'transaction' => '<strong>Grand Total</strong>',
            'memo' => '',
            'ship_via' => '',
            'amount' => $grandTotal['amount'],
            'open_balance' => $grandTotal['open_balance'],
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->editColumn(
                'transaction_date',
                fn($row) =>
                isset($row->isSubtotal) || isset($row->isParent) || isset($row->isGrandTotal) || isset($row->isPlaceholder)
                ? ''
                : ($row->transaction_date ? Carbon::parse($row->transaction_date)->format('Y-m-d') : '')
            )
            ->setRowClass(function ($row) {
                if (property_exists($row, 'isParent') && $row->isParent)
                    return 'parent-row toggle-bucket bucket-' . \Str::slug($row->vendor ?? 'na');

                if (property_exists($row, 'isSubtotal') && $row->isSubtotal)
                    return 'subtotal-row bucket-' . \Str::slug($row->vendor ?? 'na');

                if (property_exists($row, 'isGrandTotal') && $row->isGrandTotal)
                    return 'grandtotal-row';

                if (property_exists($row, 'isPlaceholder') && $row->isPlaceholder)
                    return 'placeholder-row';

                return 'child-row bucket-' . \Str::slug($row->vendor ?? 'na');
            })
            ->rawColumns(['transaction']);
    }

    public function query()
    {
        $start = request()->get('start_date')
            ?? request()->get('startDate')
            ?? Carbon::now()->startOfYear()->format('Y-m-d');

        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return DB::table('purchases')
            ->select(
                'purchases.id',
                'purchases.purchase_id',
                'purchases.purchase_number',
                'purchases.purchase_date as transaction_date',
                'venders.name as vendor_name',
                // Total amount (sum of product lines + taxes)
                DB::raw('(
                SELECT IFNULL(SUM(
                    (pp.price * pp.quantity)
                    - IFNULL(pp.discount, 0)
                    + IFNULL(
                        (SELECT IFNULL(SUM((pp2.price * pp2.quantity - pp2.discount) * (taxes.rate / 100)), 0)
                         FROM purchase_products pp2
                         LEFT JOIN taxes ON FIND_IN_SET(taxes.id, pp2.tax) > 0
                         WHERE pp2.purchase_id = purchases.id),
                    0)
                ), 0)
                FROM purchase_products pp
                WHERE pp.purchase_id = purchases.id
            ) as amount'),
                // Total payments applied to this purchase
                DB::raw('(
                SELECT IFNULL(SUM(amount), 0)
                FROM purchase_payments
                WHERE purchase_id = purchases.id
            ) as payments_total'),
                // Open balance = amount - payments_total
                DB::raw('(
                (SELECT IFNULL(SUM(
                    (pp.price * pp.quantity)
                    - IFNULL(pp.discount, 0)
                    + IFNULL(
                        (SELECT IFNULL(SUM((pp2.price * pp2.quantity - pp2.discount) * (taxes.rate / 100)), 0)
                         FROM purchase_products pp2
                         LEFT JOIN taxes ON FIND_IN_SET(taxes.id, pp2.tax) > 0
                         WHERE pp2.purchase_id = purchases.id),
                    0)
                ), 0)
                FROM purchase_products pp
                WHERE pp.purchase_id = purchases.id)
                -
                (SELECT IFNULL(SUM(amount), 0) FROM purchase_payments WHERE purchase_id = purchases.id)
            ) as open_balance')
            )
            ->join('venders', 'venders.id', '=', 'purchases.vender_id')
            ->where('purchases.created_by', \Auth::user()->creatorId())
            ->whereBetween('purchases.purchase_date', [$start, $end])
            ->orderBy('purchases.purchase_date', 'asc');
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
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('transaction_date')->title('Date'),
            Column::make('transaction')->title('Purchase #'),
            Column::make('memo')->title('Memo / Description'),
            Column::make('ship_via')->title('Ship Via')->addClass('default-hidden'),
            Column::make('amount')->title('Amount'),
            Column::make('open_balance')->title('PO Open Balance'),
        ];
    }
}
