<?php

namespace App\DataTables;

use App\Models\BillProduct;
use App\Models\BillAccount;
use App\Models\Utility;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ExpensesByVendorSummary extends DataTable
{
    public function dataTable($query)
    {
        // 1. Fetch Bill Products (from main query passed to dataTable)
        $billProducts = collect($query->get());

        // 2. Fetch Bill Accounts (Split expenses)
        $billAccounts = $this->getBillAccounts();

        // 3. Fetch Vendor Credit Products
        $vendorCreditProducts = $this->getVendorCreditProducts();

        // 4. Fetch Vendor Credit Accounts
        $vendorCreditAccounts = $this->getVendorCreditAccounts();

        // 5. Merge all collections
        $data = $billProducts
            ->concat($billAccounts)
            ->concat($vendorCreditProducts)
            ->concat($vendorCreditAccounts);
        
        $finalData = collect();
        $grandTotal = 0;

        // First sort entire dataset globally by Vendor then Date
        $data = $data->sortBy([
            ['vendor_name', 'asc'],
            ['transaction_date', 'asc'],
        ]);
        
        // Group by vendor
        $vendors = $data->groupBy('vendor_name');
        
        // Sort keys (Vendor names) alphabetically
        $vendors = $vendors->sortKeys();

        // -------------------------------------------------------
        // Sort items INSIDE each Vendor group by Date
        // -------------------------------------------------------
        $vendors = $vendors->map(function ($items) {
            return $items->sortBy([
                ['vendor_name', 'asc'],
                ['transaction_date', 'asc'],
            ]);
        });

        foreach ($vendors as $vendor => $rows) {
            $vendorTotal = 0;

            foreach ($rows as $row) {
                // Calculate line amount: (price * quantity) - discount + tax
                // Note: Accounts usually have quantity 1 and 0 discount/tax calculated in query
                $amount = ($row->price * $row->quantity) - ($row->discount ?? 0) + ($row->tax_amount ?? 0);
                
                // Apply Sign logic:
                // Vendor Credits = Negative (-1)
                // Bills = Positive (1)
                $multiplier = $this->getSignMultiplier($row->bill_type);
                
                $vendorTotal += ($amount * $multiplier);
            }

            $finalData->push((object) [
                'vendor_name' => $vendor,
                'total' => $vendorTotal,
                'isDetail' => true,
            ]);

            $grandTotal += $vendorTotal;
        }

        // Add grand total row
        $finalData->push((object) [
            'vendor_name' => "<strong>Grand Total</strong>",
            'total' => $grandTotal,
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->editColumn('total', fn($row) => number_format((float) $row->total, 2))
            ->setRowClass(function ($row) {
                if (isset($row->isGrandTotal)) {
                    return 'grandtotal-row';
                }
                return 'detail-row';
            })
            ->rawColumns(['vendor_name']);
    }

    /**
     * Get sign multiplier based on bill type
     */
    protected function getSignMultiplier($billType)
    {
        // Vendor Credits subtract from the expense total
        $creditTypes = ['Vendor Credit', 'Credit Card Credit', 'vendor credit', 'credit'];
        
        if (in_array($billType, $creditTypes)) {
            return -1;
        }
        
        // Bills and Expenses add to the total
        return 1;
    }

    /**
     * Query bill_products (Main query source)
     */
    public function query(BillProduct $model)
    {
              
        $start = request()->get('start_date')
            ?? request()->get('startDate')
            ?? Carbon::now()->startOfYear()->format('Y-m-d');

        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');
        $excludedTypes = ['Credit Card Expense', 'Credit Card Credit', 'Credit Card'];
        return $model->newQuery()
            ->select(
                'bill_products.id',
                'bill_products.price',
                'bill_products.quantity',
                'bill_products.discount',
                'bills.bill_date as transaction_date',
                'bills.type as bill_type',
                'venders.name as vendor_name',
                DB::raw('(SELECT IFNULL(SUM((bill_products.price * bill_products.quantity) * (taxes.rate / 100)),0)
                        FROM taxes
                        WHERE FIND_IN_SET(taxes.id, bill_products.tax) > 0) AS tax_amount')
            )
            ->join('bills', 'bills.id', '=', 'bill_products.bill_id')
            ->join('venders', 'venders.id', '=', 'bills.vender_id')
            ->whereRaw('LOWER(bills.user_type) = ?', ['vendor'])
            // ----------------------------------------------
            // SKIP BILLS THAT ARE CONNECTED TO ANY PO (txn_id)
            // ----------------------------------------------
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                ->from('purchases')
                ->whereRaw("FIND_IN_SET(bills.id, purchases.txn_id)");
            })
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->whereBetween('bills.bill_date', [$start, $end])
            ->whereNotIn('bills.type', $excludedTypes);
    }

    /**
     * Get bill accounts (Chart of Accounts based expenses)
     */
    protected function getBillAccounts()
    {
        $excludedTypes = ['Credit Card Expense', 'Credit Card Credit', 'Credit Card'];
        $accountPayable = Utility::getAccountPayableAccount(\Auth::user()->creatorId());
        $accountCreditCard = Utility::getAllowedExpenseAccounts(\Auth::user()->creatorId());
        $start = request()->get('start_date')
            ?? request()->get('startDate')
            ?? Carbon::now()->startOfYear()->format('Y-m-d');

        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return DB::table('bill_accounts')
            ->select(
                'bill_accounts.id',
                'bill_accounts.price',
                DB::raw('1 as quantity'), 
                DB::raw('0 as discount'), 
                DB::raw('0 as tax_amount'),
                'bills.bill_date as transaction_date',
                'bills.type as bill_type',
                'venders.name as vendor_name'
            )
            ->join('bills', 'bills.id', '=', 'bill_accounts.ref_id')
            ->join('venders', 'venders.id', '=', 'bills.vender_id')
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->whereRaw('LOWER(bills.user_type) = ?', ['vendor'])
            ->whereBetween('bills.bill_date', [$start, $end])
            ->where('bill_accounts.chart_account_id', '!=', $accountPayable->id)
            ->whereIn('bill_accounts.chart_account_id', $accountCreditCard)
            // ->whereNotIn('bills.type', $excludedTypes)
            ->get();
     
    }

    /**
     * Get Vendor Credit Products
     */
    protected function getVendorCreditProducts()
    {
        $start = request()->get('start_date')
            ?? request()->get('startDate')
            ?? Carbon::now()->startOfYear()->format('Y-m-d');

        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return DB::table('vendor_credit_products')
            ->select(
                'vendor_credit_products.id',
                'vendor_credit_products.price',
                'vendor_credit_products.quantity',
                'vendor_credits.date as transaction_date',
                DB::raw("'Vendor Credit' as bill_type"), 
                'venders.name as vendor_name',
                DB::raw('(SELECT IFNULL(SUM((vendor_credit_products.price * vendor_credit_products.quantity) * (taxes.rate / 100)),0)
                        FROM taxes
                        WHERE FIND_IN_SET(taxes.id, vendor_credit_products.tax) > 0) AS tax_amount')
            )
            ->join('vendor_credits', 'vendor_credits.id', '=', 'vendor_credit_products.vendor_credit_id')
            ->join('venders', 'venders.id', '=', 'vendor_credits.vender_id') // Assuming vender_id matches bills table
            ->where('vendor_credits.created_by', \Auth::user()->creatorId())
            ->whereBetween('vendor_credits.date', [$start, $end])
            ->get();
    }

    /**
     * Get Vendor Credit Accounts
     */
    protected function getVendorCreditAccounts()
    {
        $accountPayable = Utility::getAccountPayableAccount(\Auth::user()->creatorId());
        $start = request()->get('start_date')
            ?? request()->get('startDate')
            ?? Carbon::now()->startOfYear()->format('Y-m-d');

        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return DB::table('vendor_credit_accounts')
            ->select(
                'vendor_credit_accounts.id',
                'vendor_credit_accounts.price',
                DB::raw('1 as quantity'), 
                DB::raw('0 as discount'), 
                DB::raw('0 as tax_amount'),
                'vendor_credits.date as transaction_date',
                DB::raw("'Vendor Credit' as bill_type"),
                'venders.name as vendor_name'
            )
            // Note: Using ref_id here to match bill_accounts pattern
            ->join('vendor_credits', 'vendor_credits.id', '=', 'vendor_credit_accounts.vendor_credit_id')
            ->join('venders', 'venders.id', '=', 'vendor_credits.vender_id')
            ->where('vendor_credits.created_by', \Auth::user()->creatorId())
            ->whereBetween('vendor_credits.date', [$start, $end])
            ->where('vendor_credit_accounts.chart_account_id', '!=', $accountPayable->id)
            ->get();
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
                'responsive' => true,
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('vendor_name')->title('Vendor'),
            Column::make('total')->title('Total'),
        ];
    }
}