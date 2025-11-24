<?php

namespace App\DataTables;

use App\Models\Bill;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class BillPaymentList extends DataTable
{
    public function dataTable($query)
    {
        $data = collect($query->get());

        $grandTotalAmount = 0;

        // ✅ Group by bank account name
        $groupedData = $data->groupBy(function ($row) {
            return $row->bank_name ?? 'Unknown Bank';
        });

        $finalData = collect();

        foreach ($groupedData as $bank => $rows) {
            // Skip empty or zero groups entirely
            if ($rows->count() == 0) {
                continue;
            }

            // Compute subtotal using real payment amount
            $subtotalAmount = $rows->sum('total_amount');

            // If subtotal is 0 (no actual payments), skip this bank group
            if ($subtotalAmount == 0) {
                continue;
            }

            // ✅ Header row for this bank
            $finalData->push((object) [
                'bank_name' => $bank,
                'vendor' => '',
                'id' => null,
                'bill_date' => '',
                'transaction' => '<span class="" data-bucket="' . \Str::slug($bank) . '"> <span class="icon">▼</span> <strong>' . $bank . ' (' . $rows->count() . ')</strong></span>',
                'type' => '',
                'total_amount' => null,
                'isPlaceholder' => true,
                'isSubtotal' => false,
                'isParent' => true
            ]);

            foreach ($rows as $row) {
                $row->bank_name = $bank;
                $finalData->push($row);
            }

            // ✅ Subtotal row
            $finalData->push((object) [
                'bank_name' => $bank,
                'vendor' => '',
                'id' => null,
                'bill_date' => '',
                'transaction' => '<strong>Subtotal for ' . $bank . '</strong>',
                'type' => '',
                'total_amount' => $subtotalAmount,
                'isSubtotal' => true,
            ]);

            // Empty placeholder row for spacing
            $finalData->push((object) [
                'bank_name' => $bank,
                'vendor' => '',
                'id' => null,
                'bill_date' => '',
                'transaction' => '',
                'type' => '',
                'total_amount' => '',
                'isPlaceholder' => true,
            ]);

            $grandTotalAmount += $subtotalAmount;
        }


        // ✅ Grand total row
        $finalData->push((object) [
            'bank_name' => '',
            'vendor' => '',
            'id' => null,
            'bill_date' => '',
            'transaction' => '<strong>Grand Total</strong>',
            'type' => '',
            'total_amount' => $grandTotalAmount,
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->addColumn('bill_date', fn($row) => isset($row->isSubtotal) || isset($row->isGrandTotal) ? '' : $row->bill_date)
            ->addColumn('transaction', function ($row) {
                if (isset($row->isSubtotal) || isset($row->isGrandTotal) || (isset($row->isPlaceholder) && $row->isPlaceholder)) {
                    return $row->transaction;
                }

                // 👇 Show Payment ID instead of Bill Number
                return 'PAY-' . str_pad($row->payment_id, 5, '0', STR_PAD_LEFT);
            })
            ->addColumn('vendor', fn($row) => (isset($row->isSubtotal) || isset($row->isGrandTotal) || isset($row->isPlaceholder)) ? '' : ($row->vendor_name ?? ''))
            ->addColumn('type', function ($row) {
                if (isset($row->isSubtotal) || isset($row->isGrandTotal) || (isset($row->isPlaceholder) && $row->isPlaceholder)) {
                    return '';
                }
                return 'Payment';
            })
            ->editColumn('total_amount', function ($row) {
                if (isset($row->isPlaceholder))
                    return '';

                if (isset($row->isSubtotal) || isset($row->isGrandTotal))
                    return number_format($row->total_amount ?? 0);

                // ✅ For actual payment rows, show payment amount
                return number_format($row->total_amount ?? 0);
            })

            ->setRowClass(function ($row) {
                if (property_exists($row, 'isParent') && $row->isParent) {
                    return 'parent-row toggle-bucket bucket-' . \Str::slug($row->bank_name ?? 'na');
                }
                if (property_exists($row, 'isSubtotal') && $row->isSubtotal && !property_exists($row, 'isGrandTotal')) {
                    return 'subtotal-row bucket-' . \Str::slug($row->bank_name ?? 'na');
                }
                if (!property_exists($row, 'isParent') && !property_exists($row, 'isSubtotal') && !property_exists($row, 'isGrandTotal') && !property_exists($row, 'isPlaceholder')) {
                    return 'child-row bucket-' . \Str::slug($row->bank_name ?? 'na');
                }
                if (property_exists($row, 'isGrandTotal') && $row->isGrandTotal) {
                    return 'grandtotal-row';
                }
                return '';
            })
            ->rawColumns(['transaction']);
    }

    // public function query(Bill $model)
    // {
    //     $start = request()->get('start_date') ?? request()->get('startDate') ?? Carbon::now()->startOfYear()->format('Y-m-d');
    //     $end = request()->get('end_date') ?? request()->get('endDate') ?? Carbon::now()->endOfDay()->format('Y-m-d');

    //     return $model->newQuery()
    //         ->select(
    //             'bills.id',
    //             'bills.bill_id as bill',
    //             'bills.bill_date',
    //             'bills.status',
    //             'venders.name',
    //             'bank_accounts.bank_name',
    //             DB::raw('SUM((bill_products.price * bill_products.quantity) - bill_products.discount) as subtotal'),
    //             DB::raw('(SELECT IFNULL(SUM((price * quantity - discount) * (taxes.rate / 100)),0) 
    //                 FROM bill_products 
    //                 LEFT JOIN taxes ON FIND_IN_SET(taxes.id, bill_products.tax) > 0
    //                 WHERE bill_products.bill_id = bills.id) as total_tax')
    //         )
    //         ->leftJoin('venders', 'venders.id', '=', 'bills.vender_id')
    //         ->leftJoin('bill_products', 'bill_products.bill_id', '=', 'bills.id')
    //         ->leftJoin('bill_payments', 'bill_payments.bill_id', '=', 'bills.id')
    //         ->leftJoin('bank_accounts', 'bank_accounts.id', '=', 'bill_payments.account_id')
    //         ->where('bills.created_by', \Auth::user()->creatorId())
    //         ->whereIn('bills.status', ['3', '4'])
    //         ->whereBetween('bills.bill_date', [$start, $end])
    //         ->groupBy('bills.id', 'bank_accounts.id');
    // }


    public function query(Bill $model)
    {
        $start = request()->get('start_date')
            ?? request()->get('startDate')
            ?? Carbon::now()->startOfYear()->format('Y-m-d');

        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return DB::table('bill_payments')
            ->select(
                'bill_payments.id as payment_id',
                'bill_payments.date as bill_date',
                'bill_payments.amount as total_amount',
                'bill_payments.reference',
                'bill_payments.description',
                'bills.bill_id as bill',
                'venders.name as vendor_name',
                'bank_accounts.bank_name'
            )
            ->leftJoin('bills', 'bills.id', '=', 'bill_payments.bill_id')
            ->leftJoin('venders', 'venders.id', '=', 'bills.vender_id')
            ->leftJoin('bank_accounts', 'bank_accounts.id', '=', 'bill_payments.account_id')
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->whereBetween('bill_payments.date', [$start, $end])
            ->orderBy('bill_payments.date', 'asc');
    }


    public function html()
    {
        return $this->builder()
            ->setTableId('customer-balance-table') // ✅ unchanged
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
            Column::make('bill_date')->title('Date'),
            Column::make('transaction')->title('Transaction'),
            Column::make('vendor')->title('Vendor'), // ✅ added vendor column
            Column::make('type')->title('Type'),
            Column::make('total_amount')->title('Amount'),
        ];
    }
}
