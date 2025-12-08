<?php

namespace App\DataTables;

use App\Models\Bill;
use Carbon\Carbon;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB; 

class VendorBalanceSummary extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $entries = $query->get()->toArray();
        $mergedArray = [];
        $totalBalance = 0;
        $grandTotal = 0; 

        foreach ($entries as $item) {
            // Use the vendor name for grouping
            $name = $item['name'];

            if (!isset($mergedArray[$name])) {
                $mergedArray[$name] = [
                    'name' => $name,
                    'price' => 0.0,
                    'pay_price' => 0.0,
                    'total_tax' => 0.0,
                    'debit_price' => 0.0,
                ];
            }

            // Ensure float conversion for accurate summation
            $mergedArray[$name]['price'] += (float)($item['price'] ?? 0);
            $mergedArray[$name]['pay_price'] += (float)($item['pay_price'] ?? 0);
            $mergedArray[$name]['total_tax'] += (float)($item['total_tax'] ?? 0);
            $mergedArray[$name]['debit_price'] += (float)($item['debit_price'] ?? 0);
        }

        $data = collect();
        foreach ($mergedArray as $row) {
            // Calculate Total Bill Amount (Subtotal + Tax)
            $vendorTotal = $row['price'] + $row['total_tax'];
            
            // Calculate Vendor Open Balance (Total Bill - Payments)
            $vendorOpenBalance = $vendorTotal - $row['pay_price'];
            
            // Calculate Final Balance (Open Balance - Debit Notes)
            $balance = $vendorOpenBalance - $row['debit_price'];

            $totalBalance += $balance;
            $grandTotal = $totalBalance; 

            $data->push([
                'name' => $row['name'],
                // FIX: Use number_format(value, 2) to ensure decimals are shown
                'total' => number_format($balance, 2), 
            ]);
        }

        // Add total row
        $data->push([
            'name' => '<strong>Grand Total</strong>', 
            // FIX: Use number_format(value, 2) to ensure decimals are shown
            'total' => '<strong>' . number_format($grandTotal, 2) . '</strong>',
            'DT_RowClass' => 'summary-total'
        ]);

        // Only render the 'name' and 'total' columns, allowing raw HTML for formatting
        return datatables()->collection($data)->rawColumns(['name', 'total']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Bill $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Bill $model)
    {
        $end = request()->get('endDate') ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return $model->newQuery()
            ->select(
                'venders.name',
                // 👇 FIX: COMBINE PRODUCT SUBTOTAL AND ACCOUNT TOTAL (bill_accounts)
                DB::raw('(
                    SELECT COALESCE(SUM(
                        (bp.price * bp.quantity) - COALESCE(bp.discount, 0)
                    ), 0)
                    FROM bill_products bp
                    WHERE bp.bill_id = bills.id
                ) + (
                    SELECT COALESCE(SUM(ba.price), 0)
                    FROM bill_accounts ba
                    WHERE ba.ref_id = bills.id
                ) as price'), // 'price' now includes bill_products AND bill_accounts
                // 👆 FIX END

                DB::raw('(
                    SELECT COALESCE(SUM(
                        (bp2.price * bp2.quantity - COALESCE(bp2.discount, 0)) * (t.rate / 100)
                    ), 0) 
                    FROM bill_products bp2
                    LEFT JOIN taxes t ON FIND_IN_SET(t.id, bp2.tax) > 0
                    WHERE bp2.bill_id = bills.id
                ) as total_tax'), // Total Tax

                DB::raw('(
                    SELECT COALESCE(SUM(amount), 0)
                    FROM bill_payments
                    WHERE bill_payments.bill_id = bills.id
                ) as pay_price'), // Total Payments

                DB::raw('(
                    SELECT COALESCE(SUM(debit_notes.amount), 0) 
                    FROM debit_notes
                    WHERE debit_notes.bill = bills.id
                ) as debit_price') // Total Debit Notes
            )
            ->leftJoin('venders', 'venders.id', '=', 'bills.vender_id')
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->where('bills.type', 'bill')
            ->where('bills.status', '!=', '4') 
            ->where('bills.bill_date', '<=', $end)
            ->groupBy('bills.id', 'venders.name'); 
    }

    /**
     * Optional method if you want to use the html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('vendor-balance-summary-table') 
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'desc')
            ->parameters([
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'scrollY' => '500px',
                'scrollCollapse' => true,
                'colReorder' => true,
                'createdRow' => "function(row, data) {
                    $('td:eq(1)', row).addClass('text-left'); 
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
        // Only return the two required columns
        return [
            Column::make('name')->title('Vendor Name'),
            Column::make('total')->title('Total Balance')->addClass('text-left'), 
        ];
    }
}