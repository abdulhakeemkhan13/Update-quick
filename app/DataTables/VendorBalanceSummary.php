<?php

namespace App\DataTables;

use App\Models\Bill;
use Carbon\Carbon;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;

class VendorBalanceSummary extends DataTable
{
    public function dataTable($query)
    {
        $data = collect($query->get());

        $grandTotal = 0;
        $finalData = collect();

        // Group by vendor and sum balances
        $vendors = $data->groupBy('vendor_name');

        foreach ($vendors as $vendor => $rows) {
            $vendorBalance = $rows->sum('open_balance');
            
            // Only show vendors with non-zero balance (like QuickBooks)
            // if (abs($vendorBalance) < 0.01) {
            //     continue;
            // }

            $finalData->push((object) [
                'name' => $vendor ?: 'Unknown Vendor',
                'total' => $vendorBalance,
                'isDetail' => true,
            ]);

            $grandTotal += $vendorBalance;
        }

        // Sort by vendor name
        $finalData = $finalData->sortBy('name')->values();

        // Add grand total row
        $finalData->push((object) [
            'name' => '<strong>TOTAL</strong>',
            'total' => $grandTotal,
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->editColumn('total', function ($row) {
                $value = number_format((float) $row->total, 2);
                if (isset($row->isGrandTotal)) {
                    return '<strong>' . $value . '</strong>';
                }
                return $value;
            })
            ->setRowClass(function ($row) {
                if (isset($row->isGrandTotal)) {
                    return 'grandtotal-row font-weight-bold bg-light';
                }
                return 'detail-row';
            })
            ->rawColumns(['name', 'total']);
    }

    public function query(Bill $model)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(600);
        $userId = \Auth::user()->creatorId();
        $end = request()->get('end_date') ?? request()->get('endDate') ?? Carbon::now()->endOfDay()->format('Y-m-d');
        // 1. Bills - Open Balance 
        // FIX: We added "AND date <= '$end'" to payments and debit notes subqueries
        // FIX: Use COALESCE with bills.total for Check/Expense types
        $bills = DB::table('bills')
            ->select(
                'venders.name as vendor_name',
                DB::raw('(
                    COALESCE(
                        NULLIF(
                            (SELECT IFNULL(SUM(bp.price * bp.quantity - IFNULL(bp.discount, 0)), 0) FROM bill_products bp WHERE bp.bill_id = bills.id)
                            + (SELECT IFNULL(SUM(ba.price), 0) FROM bill_accounts ba WHERE ba.ref_id = bills.id),
                            0
                        ),
                        bills.total
                    ) - (
                        SELECT IFNULL(SUM(amount), 0) FROM bill_payments 
                        WHERE bill_payments.bill_id = bills.id 
                        AND bill_payments.date <= "' . $end . '"  
                    ) - (
                        SELECT IFNULL(SUM(debit_notes.amount), 0) FROM debit_notes 
                        WHERE debit_notes.bill = bills.id 
                        AND debit_notes.date <= "' . $end . '"
                    )
                ) as open_balance')
            )
            ->join('venders', 'venders.id', '=', 'bills.vender_id')
            // ->where('venders.is_active', '1') // Temporarily disabled for debugging
            ->where('bills.created_by', $userId)
            ->whereRaw('LOWER(bills.type) IN (?, ?, ?)', ['bill', 'check', 'expense'])
            ->where('bills.bill_date', '<=', $end); // Bill must exist before this date

        // 2. Vendor Credits
        // FIX: Ensure we only count credits created before the end date
        $vendorCredits = DB::table('vendor_credits')
            ->select(
                'venders.name as vendor_name',
                DB::raw('-1 * (
                    (SELECT IFNULL(SUM(vcp.price * vcp.quantity), 0) FROM vendor_credit_products vcp WHERE vcp.vendor_credit_id = vendor_credits.id)
                    + (SELECT IFNULL(SUM(vca.price), 0) FROM vendor_credit_accounts vca WHERE vca.vendor_credit_id = vendor_credits.id)
                ) as open_balance')
            )
            ->join('venders', 'venders.id', '=', 'vendor_credits.vender_id')
            ->where('venders.is_active', '1')
            ->where('vendor_credits.created_by', $userId)
            ->where('vendor_credits.date', '<=', $end);

        // 3. Unapplied Payments - Negative balance (money paid but not applied to bills)
        $unappliedPayments = DB::table('unapplied_payments')
            ->select(
                'venders.name as vendor_name',
                DB::raw('-1 * unapplied_payments.unapplied_amount as open_balance')
            )
            ->join('venders', 'venders.id', '=', 'unapplied_payments.vendor_id')
            ->where('venders.is_active', '1')
            ->where('unapplied_payments.created_by', $userId)
            // ->where('unapplied_payments.txn_date', '<=', $end)
            ->where('unapplied_payments.unapplied_amount', '>', 0);

        $combined = $bills->unionAll($vendorCredits)->unionAll($unappliedPayments);
        return DB::query()->fromSub($combined, 'balances')
            ->orderBy('vendor_name', 'asc');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('vendor-balance-summary-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'asc')
            ->parameters([
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'dom' => 't',
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('name')->title('Vendor'),
            Column::make('total')->title('Balance')->addClass('text-right'),
        ];
    }
}