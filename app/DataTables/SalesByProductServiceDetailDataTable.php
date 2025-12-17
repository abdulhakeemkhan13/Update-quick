<?php

namespace App\DataTables;

use App\Models\InvoiceProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class SalesByProductServiceDetailDataTable extends DataTable
{
    // public function dataTable($query)
    // {
    //     $user = Auth::user();

    //     // Get all rows first (for running balance + total row)
    //     $rows = $query->get();

    //     $data = collect();
    //     $runningBalance = 0;
    //     $totalAmount = 0;
    //     $totalQuantity = 0;

    //     foreach ($rows as $r) {
    //         // Calculate amount = (price * quantity) - discount + tax
    //         $baseAmount = ($r->price ?? 0) * ($r->quantity ?? 0);
    //         $discount = $r->discount ?? 0;
    //         $taxAmount = $this->calculateTaxAmount($r);
    //         $amount = $baseAmount - $discount + $taxAmount;

    //         // Update totals
    //         $runningBalance += $amount;
    //         $totalAmount += $amount;
    //         $totalQuantity += ($r->quantity ?? 0);

    //         // Add each transaction row
    //         $data->push([
    //             'transaction_date' => \Carbon\Carbon::parse($r->transaction_date)->format('m/d/Y'),
    //             'transaction_type' => $r->transaction_type ?? 'Invoice',
    //             'num' => $r->invoice_number ?? '-',
    //             'customer_full_name' => $r->customer_name ?? '-',
    //             'memo_description' => $r->description ?? '-',
    //             'quantity' => number_format($r->quantity ?? 0, 2),
    //             'sales_price' => number_format($r->price ?? 0),
    //             'amount' => number_format($amount, 2),
    //             'balance' => number_format($runningBalance, 2),
    //         ]);
    //     }

    //     // Add total row (all bold, empty placeholders)
    //     if ($rows->count() > 0) {
    //         $data->push([
    //             'transaction_date' => '<strong>Total</strong>',
    //             'transaction_type' => '<strong></strong>',
    //             'num' => '<strong></strong>',
    //             'customer_full_name' => '<strong></strong>',
    //             'memo_description' => '<strong></strong>',
    //             'quantity' => '<strong>' . number_format($totalQuantity, 2) . '</strong>',
    //             'sales_price' => '<strong></strong>',
    //             'amount' => '<strong>' . number_format($totalAmount, 2) . '</strong>',
    //             'balance' => '<strong>' . number_format($runningBalance, 2) . '</strong>',
    //             'DT_RowClass' => 'summary-total'
    //         ]);
    //     } else {
    //         $data->push([
    //             'transaction_date' => 'No records found.',
    //             'transaction_type' => '',
    //             'num' => '',
    //             'customer_full_name' => '',
    //             'memo_description' => '',
    //             'quantity' => '',
    //             'sales_price' => '',
    //             'amount' => '',
    //             'balance' => '',
    //             'DT_RowClass' => 'no-data-row'
    //         ]);
    //     }

    //     return datatables()
    //         ->collection($data)
    //         ->rawColumns([
    //             'transaction_date',
    //             'transaction_type',
    //             'num',
    //             'customer_full_name',
    //             'memo_description',
    //             'quantity',
    //             'sales_price',
    //             'amount',
    //             'balance',
    //         ]);
    // }

    public function dataTable($query)
    {
        $rows = $query->get();

        // Group by product_id to avoid name differences (trailing spaces / unicode etc.)
        $grouped = $rows->groupBy('product_id');

        $finalData = collect();

        $grandTotalQuantity = 0.0;
        $grandTotalAmount = 0.0;

        $currencySymbol = \Auth::user()->currencySymbol();
        
        $formatWithCurrency = function ($val) use ($currencySymbol) {
            $formatted = \Auth::user()->priceFormat($val);
            if (strpos($formatted, $currencySymbol) === false) {
                 return $currencySymbol . ' ' . $formatted;
            }
            return $formatted;
        };

        foreach ($grouped as $productId => $transactions) {
            // Use product_id as stable key
            $groupKey = 'product-' . (string) $productId;

            // Display name from first transaction
            $displayName = $transactions->first()->product_name ?? 'Unknown';

            // --- Calculate group total using numeric arithmetic (no formatted strings) ---
            $groupTotalRaw = 0.0;
            $groupTotalQuantity = 0.0;

            foreach ($transactions as $t) {
                // Ensure values are fully numeric before logic
                $price = (float) $t->price;
                $qty = (float) $t->quantity;
                $discount = (float) ($t->discount ?? 0);
                $taxAmount = (float) $this->calculateTaxAmount($t);

                $amountRaw = ($price * $qty) - $discount + $taxAmount;

                $groupTotalRaw += $amountRaw;
                
                // User filter: don't add quantity if price and amount are both 0
                if (!($price == 0 && $amountRaw == 0)) {
                    $groupTotalQuantity += $qty;
                }
            }

            // Update Grand Totals
            $grandTotalQuantity += $groupTotalQuantity;
            $grandTotalAmount += $groupTotalRaw;

            // --- Push Group Header ---
            // Header still contains the totals as requested for collapsed view, 
            // but formatted with priceFormat for currency fields.
            $finalData->push([
                'group_key' => $groupKey,
                'group' => $displayName,
                'transaction_date' => '',
                'transaction_type' => '',
                'num' => '',
                'customer_full_name' => '',
                'memo_description' => '',
                'quantity' => number_format($groupTotalQuantity, 2),
                'sales_price' => '',
                'amount' => $formatWithCurrency($groupTotalRaw),
                'balance' => $formatWithCurrency($groupTotalRaw),
                'is_group_header' => true,
                'is_grand_total' => false,
            ]);

            // --- Push Detail Rows ---
            $runningBalance = 0.0;
            foreach ($transactions as $transaction) {
                $price = (float) $transaction->price;
                $qty = (float) $transaction->quantity;
                $discount = (float) ($transaction->discount ?? 0);
                $taxAmount = (float) $this->calculateTaxAmount($transaction);

                $amountRaw = ($price * $qty) - $discount + $taxAmount;
                $runningBalance += $amountRaw;

                $finalData->push([
                    'group_key' => $groupKey,
                    'group' => $displayName,
                    'transaction_date' => Carbon::parse($transaction->transaction_date)->format('m/d/Y'),
                    'transaction_type' => $transaction->transaction_type ?? 'Invoice',
                    'num' => $transaction->invoice_number ?? '-',
                    'customer_full_name' => $transaction->customer_name ?? '-',
                    'memo_description' => $transaction->description ?? '-',
                    'quantity' => number_format($qty, 2),
                    'sales_price' => $formatWithCurrency($price),
                    'amount' => $formatWithCurrency($amountRaw),
                    'balance' => $formatWithCurrency($runningBalance),
                    'is_group_header' => false,
                    'is_grand_total' => false,
                ]);
            }

            // --- Add Product Total Row (Summary Footer) ---
            $finalData->push([
                'group_key' => $groupKey,
                'group' => $displayName,
                'transaction_date' => '<strong>Total for ' . e($displayName) . '</strong>',
                'transaction_type' => '',
                'num' => '',
                'customer_full_name' => '',
                'memo_description' => '',
                'quantity' => '<strong>' . number_format($groupTotalQuantity, 2) . '</strong>',
                'sales_price' => '',
                'amount' => '<strong>' . $formatWithCurrency($groupTotalRaw) . '</strong>',
                'balance' => '<strong>' . $formatWithCurrency($groupTotalRaw) . '</strong>',
                'is_group_header' => false,
                'is_grand_total' => false,
                'is_total_row' => true, // New flag for styling if needed
            ]);
        }

        // --- Add Grand Total Row ---
        if ($finalData->isNotEmpty()) {
            $finalData->push([
                'group_key' => 'grand-total',
                'group' => 'Grand Total',
                'transaction_date' => 'Total',
                'transaction_type' => '',
                'num' => '',
                'customer_full_name' => '',
                'memo_description' => '',
                'quantity' => number_format($grandTotalQuantity, 2),
                'sales_price' => '',
                'amount' => $formatWithCurrency($grandTotalAmount),
                'balance' => $formatWithCurrency($grandTotalAmount),
                'is_group_header' => true, 
                'is_grand_total' => true,
            ]);
        }

        return datatables()
            ->collection($finalData)
            ->addColumn('transaction_date', function ($r) {
                if (($r['is_total_row'] ?? false) || ($r['is_grand_total'] ?? false)) {
                     // For total rows, return the label directly (it might have HTML)
                     return $r['transaction_date'];
                }
                if ($r['is_group_header']) {
                    return '<span class="group-toggle" data-group="' . e($r['group_key']) . '">
                                <i class="fas fa-chevron-right me-2"></i>
                                <strong>' . e($r['group']) . '</strong>
                            </span>';
                }
                return e($r['transaction_date']);
            })
            ->addColumn('transaction_type', fn($r) => $r['is_group_header'] ? '' : e($r['transaction_type']))
            ->addColumn('num', fn($r) => $r['is_group_header'] ? '' : e($r['num']))
            ->addColumn('customer_full_name', fn($r) => $r['is_group_header'] ? '' : e($r['customer_full_name']))
            ->addColumn('memo_description', fn($r) => $r['is_group_header'] ? '' : e($r['memo_description']))
            ->addColumn('quantity', fn($r) => $r['quantity']) // Already formatted or styled string
            ->addColumn('sales_price', fn($r) => $r['is_group_header'] ? '' : $r['sales_price'])
            ->addColumn('amount', fn($r) => $r['amount']) // Already formatted or styled string
            ->addColumn('balance', fn($r) => $r['balance']) // Already formatted or styled string
            ->setRowAttr([
                'class' => function ($r) {
                    if ($r['is_grand_total'] ?? false) {
                        return 'summary-total';
                    }
                    if ($r['is_total_row'] ?? false) {
                        return 'group-row group-' . $r['group_key'] . ' font-weight-bold'; // Add bold class
                    }
                    return $r['is_group_header'] ? 'group-header' : 'group-row group-' . $r['group_key'];
                },
                'data-group' => function ($r) {
                    return $r['group_key'];
                },
                'style' => function ($r) {
                    if ($r['is_grand_total'] ?? false) {
                         return 'background-color:#e2e8f0; font-weight:700;';
                    }
                     if ($r['is_total_row'] ?? false) {
                         // Starts hidden like other detail rows, usually? 
                         // "as row expand the quantity and total are not hiding" -> this implies expanding shows them.
                         // So this row should behave like a detail row (hidden initially).
                         return 'background-color:#fefeff; font-weight:700; display:none;';
                    }
                    return $r['is_group_header']
                        ? 'background-color:#f8f9fa; font-weight:600; cursor:pointer;'
                        : 'display:none;';
                },
            ])
            ->rawColumns(['transaction_date', 'quantity', 'amount', 'balance']);
    }



    private function calculateTaxAmount($transaction)
    {
        if (empty($transaction->tax)) {
            return 0.0;
        }

        $taxIds = explode(',', $transaction->tax);
        $totalTaxRate = 0.0;

        foreach ($taxIds as $taxId) {
            $tax = DB::table('taxes')->where('id', $taxId)->first();
            if ($tax && isset($tax->rate)) {
                $totalTaxRate += (float) $tax->rate;
            }
        }

        $baseAmount = ((float) $transaction->price * (float) $transaction->quantity) - ((float) ($transaction->discount ?? 0));
        $taxValue = ($baseAmount * $totalTaxRate) / 100.0;
        return (float) round($taxValue, 2);
    }

    public function query()
    {
        $user = Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();

        $startDate = request()->get('start_date') ?? request()->get('startDate') ?? date('Y-01-01');
        $endDate = request()->get('end_date') ?? request()->get('endDate') ?? date('Y-m-d');
        $reportPeriod = request('report_period', 'all_dates');

        if ($reportPeriod && $reportPeriod !== 'all_dates' && $reportPeriod !== 'custom') {
            $dates = $this->calculateDateRange($reportPeriod);
            $startDate = $dates['start'];
            $endDate = $dates['end'];
        }

        // Query 1: Invoice Products
        $invoiceQuery = DB::table('invoice_products')
            ->join('invoices as i', 'i.id', '=', 'invoice_products.invoice_id')
            ->join('product_services as ps', 'ps.id', '=', 'invoice_products.product_id')
            ->join('customers as c', 'c.id', '=', 'i.customer_id')
            ->select([
                'invoice_products.id',
                'invoice_products.product_id',
                'invoice_products.quantity',
                'invoice_products.price',
                'invoice_products.discount',
                'invoice_products.tax',
                'invoice_products.description',
                'i.issue_date as transaction_date',
                'i.invoice_id as invoice_number',
                'ps.name as product_name',
                'c.name as customer_name',
                DB::raw("'Invoice' as transaction_type"),
            ])
            ->where('i.created_by', $ownerId)
            ->where('i.status', '!=', 0);

        // Query 2: Credit Note Products
        $creditMemoQuery = DB::table('credit_note_products')
            ->join('credit_notes as cn', 'cn.id', '=', 'credit_note_products.credit_note_id')
            ->join('product_services as ps', 'ps.id', '=', 'credit_note_products.product_id')
            ->join('customers as c', 'c.id', '=', 'cn.customer')
            ->select([
                'credit_note_products.id',
                'credit_note_products.product_id',
                DB::raw('(credit_note_products.quantity * -1) as quantity'),
                'credit_note_products.price',
                'credit_note_products.discount',
                'credit_note_products.tax',
                'credit_note_products.description',
                'cn.date as transaction_date',
                'cn.credit_note_id as invoice_number',
                'ps.name as product_name',
                'c.name as customer_name',
                DB::raw("'Credit Memo' as transaction_type"),
            ])
            ->where('cn.created_by', $ownerId);

        // Apply filters to both
        if ($startDate) {
            $invoiceQuery->whereDate('i.issue_date', '>=', $startDate);
            $creditMemoQuery->whereDate('cn.date', '>=', $startDate);
        }
        if ($endDate) {
            $invoiceQuery->whereDate('i.issue_date', '<=', $endDate);
            $creditMemoQuery->whereDate('cn.date', '<=', $endDate);
        }
        if (request()->filled('product_name')) {
            $search = '%' . request('product_name') . '%';
            $invoiceQuery->where('ps.name', 'like', $search);
            $creditMemoQuery->where('ps.name', 'like', $search);
        }
        if (request()->filled('customer_name')) {
            $search = '%' . request('customer_name') . '%';
            $invoiceQuery->where('c.name', 'like', $search);
            $creditMemoQuery->where('c.name', 'like', $search);
        }
        if (request()->filled('category')) {
            $cat = request('category');
            $invoiceQuery->where('ps.category_id', $cat);
            $creditMemoQuery->where('ps.category_id', $cat);
        }
        if (request()->filled('type')) {
            $type = request('type');
            $invoiceQuery->where('ps.type', $type);
            $creditMemoQuery->where('ps.type', $type);
        }

        // Union
        $invoiceQuery->unionAll($creditMemoQuery);

        return $invoiceQuery->orderBy('transaction_date', 'desc')->orderBy('invoice_number', 'desc');
    }



    private function calculateDateRange($period)
    {
        $today = Carbon::today();

        switch ($period) {
            case 'today':
                return ['start' => $today->format('Y-m-d'), 'end' => $today->format('Y-m-d')];
            case 'this_week':
                return ['start' => $today->startOfWeek()->format('Y-m-d'), 'end' => $today->endOfWeek()->format('Y-m-d')];
            case 'this_month':
                return ['start' => $today->startOfMonth()->format('Y-m-d'), 'end' => $today->endOfMonth()->format('Y-m-d')];
            case 'this_quarter':
                return ['start' => $today->startOfQuarter()->format('Y-m-d'), 'end' => $today->endOfQuarter()->format('Y-m-d')];
            case 'this_year':
                return ['start' => $today->startOfYear()->format('Y-m-d'), 'end' => $today->endOfYear()->format('Y-m-d')];
            case 'last_week':
                $lastWeek = $today->subWeek();
                return ['start' => $lastWeek->startOfWeek()->format('Y-m-d'), 'end' => $lastWeek->endOfWeek()->format('Y-m-d')];
            case 'last_month':
                $lastMonth = $today->subMonth();
                return ['start' => $lastMonth->startOfMonth()->format('Y-m-d'), 'end' => $lastMonth->endOfMonth()->format('Y-m-d')];
            case 'last_quarter':
                $lastQuarter = $today->subQuarter();
                return ['start' => $lastQuarter->startOfQuarter()->format('Y-m-d'), 'end' => $lastQuarter->endOfQuarter()->format('Y-m-d')];
            case 'last_year':
                $lastYear = $today->subYear();
                return ['start' => $lastYear->startOfYear()->format('Y-m-d'), 'end' => $lastYear->endOfYear()->format('Y-m-d')];
            case 'last_7_days':
                return ['start' => $today->subDays(7)->format('Y-m-d'), 'end' => Carbon::today()->format('Y-m-d')];
            case 'last_30_days':
                return ['start' => $today->subDays(30)->format('Y-m-d'), 'end' => Carbon::today()->format('Y-m-d')];
            case 'last_90_days':
                return ['start' => $today->subDays(90)->format('Y-m-d'), 'end' => Carbon::today()->format('Y-m-d')];
            case 'last_12_months':
                return ['start' => $today->subMonths(12)->format('Y-m-d'), 'end' => Carbon::today()->format('Y-m-d')];
            default:
                return ['start' => null, 'end' => null];
        }
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('sales-by-product-detail-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('rt')
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'scrollY' => '400px',
                'scrollX' => true,
                'scrollCollapse' => true,
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('transaction_date')->title(__('Product / Transaction Date'))->addClass('text-left'),
            Column::make('transaction_type')->title(__('Type'))->addClass('text-left'),
            Column::make('num')->title(__('Num'))->addClass('text-left'),
            Column::make('customer_full_name')->title(__('Customer'))->addClass('text-left'),
            Column::make('memo_description')->title(__('Description'))->addClass('text-left'),
            Column::make('quantity')->title(__('Qty'))->addClass('text-right'),
            Column::make('sales_price')->title(__('Price'))->addClass('text-right'),
            Column::make('amount')->title(__('Amount'))->addClass('text-right'),
            Column::make('balance')->title(__('Balance'))->addClass('text-right'),
        ];
    }

    protected function filename(): string
    {
        return 'SalesByProductServiceDetail_' . date('YmdHis');
    }
}