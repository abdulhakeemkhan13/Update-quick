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
    $end = request()->get('end_date')
        ?? request()->get('endDate')
        ?? Carbon::now()->endOfDay()->format('Y-m-d');

    /* -------------------------------------------------
     | Aggregates
     |--------------------------------------------------*/

    // Bill products + accounts total
    $billAmounts = DB::table('bills as b')
        ->leftJoin('bill_products as bp', 'bp.bill_id', '=', 'b.id')
        ->leftJoin('bill_accounts as ba', 'ba.ref_id', '=', 'b.id')
        ->select(
            'b.id',
            DB::raw('
                SUM(
                    IFNULL(bp.price * bp.quantity - IFNULL(bp.discount,0),0)
                ) + SUM(IFNULL(ba.price,0)) as bill_amount
            ')
        )
        ->groupBy('b.id');

    // Payments till end date
    $billPayments = DB::table('bill_payments')
        ->select(
            'bill_id',
            DB::raw('SUM(amount) as paid_amount')
        )
        ->where('date', '<=', $end)
        ->groupBy('bill_id');

    // Debit notes till end date
    $debitNotes = DB::table('debit_notes')
        ->select(
            'bill',
            DB::raw('SUM(amount) as debit_amount')
        )
        ->where('date', '<=', $end)
        ->groupBy('bill');

    /* -------------------------------------------------
     | 1. Bills / Checks / Expenses
     |--------------------------------------------------*/
    $bills = DB::table('bills as b')
        ->join('venders as v', 'v.id', '=', 'b.vender_id')
        ->leftJoinSub($billAmounts, 'ba', 'ba.id', '=', 'b.id')
        ->leftJoinSub($billPayments, 'bp', 'bp.bill_id', '=', 'b.id')
        ->leftJoinSub($debitNotes, 'dn', 'dn.bill', '=', 'b.id')
        ->select(
            'v.name as vendor_name',
            DB::raw('
                (
                    COALESCE(NULLIF(ba.bill_amount,0), b.total)
                    - IFNULL(bp.paid_amount,0)
                    - IFNULL(dn.debit_amount,0)
                ) as open_balance
            ')
        )
        ->where('b.created_by', $userId)
        ->whereIn(DB::raw('LOWER(b.type)'), ['bill', 'check', 'expense'])
        ->where('b.bill_date', '<=', $end);

    /* -------------------------------------------------
     | 2. Vendor Credits
     |--------------------------------------------------*/
    $vendorCredits = DB::table('vendor_credits as vc')
        ->join('venders as v', 'v.id', '=', 'vc.vender_id')
        ->leftJoin('vendor_credit_products as vcp', 'vcp.vendor_credit_id', '=', 'vc.id')
        ->leftJoin('vendor_credit_accounts as vca', 'vca.vendor_credit_id', '=', 'vc.id')
        ->select(
            'v.name as vendor_name',
            DB::raw('
                -1 * (
                    SUM(IFNULL(vcp.price * vcp.quantity,0))
                    + SUM(IFNULL(vca.price,0))
                ) as open_balance
            ')
        )
        ->where('vc.created_by', $userId)
        ->where('vc.date', '<=', $end)
        ->where('v.is_active', 1)
        ->groupBy('vc.id', 'v.name');

    /* -------------------------------------------------
     | 3. Unapplied Payments
     |--------------------------------------------------*/
    $unappliedPayments = DB::table('unapplied_payments as up')
        ->join('venders as v', 'v.id', '=', 'up.vendor_id')
        ->select(
            'v.name as vendor_name',
            DB::raw('-1 * up.unapplied_amount as open_balance')
        )
        ->where('up.created_by', $userId)
        ->where('up.unapplied_amount', '>', 0)
        ->where('v.is_active', 1);

    /* -------------------------------------------------
     | 4. Vendor Credit Transactions (from transactions table)
     |    Grouped by payment_id where category = 'Vendor Credit'
     |--------------------------------------------------*/
    $vendorCreditTransactions = DB::table('transactions as t')
        ->join('venders as v', function ($join) {
            $join->on('v.id', '=', 't.user_id')
                 ->where('t.user_type', '=', 'Vender');
        })
        ->select(
            'v.name as vendor_name',
            DB::raw('-1 * SUM(t.amount) as open_balance')
        )
        ->where('t.created_by', $userId)
        ->where('t.category', 'Vendor Credit')
        ->where('t.date', '<=', $end)
        ->where('v.is_active', 1)
        ->groupBy('t.payment_id', 'v.name');

    /* -------------------------------------------------
     | Combine & Filter
     |--------------------------------------------------*/
    $combined = $bills
        ->unionAll($vendorCredits)
        ->unionAll($unappliedPayments)
        ->unionAll($vendorCreditTransactions);

    return DB::query()
        ->fromSub($combined, 'balances')
        ->whereRaw('ROUND(open_balance,2) <> 0')
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