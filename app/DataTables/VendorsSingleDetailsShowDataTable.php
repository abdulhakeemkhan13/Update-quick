<?php

namespace App\DataTables;

use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Models\VendorCredit;
use App\Models\UnappliedPayment;
use App\Models\Vender;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class VendorsSingleDetailsShowDataTable extends DataTable
{
    /**
     * Get encrypted route param (works with vender/{ids} or vender/{vender})
     */
    protected function getEncryptedVendorParam(): ?string
    {
        $route = request()->route();
        if (!$route) return null;

        $params = $route->parameters() ?? [];

        // Try known keys first, otherwise take the first param
        return $params['vender'] ?? $params['vendor'] ?? $params['ids'] ?? (count($params) ? reset($params) : null);
    }

    /**
     * Decrypt vendor id from route param (encrypted) or fallback numeric
     */
    protected function resolveVendorId(): ?int
    {
        $param = $this->getEncryptedVendorParam();
        if (!$param) return null;

        // If already numeric (someone hit /vender/12), accept it
        if (is_numeric($param)) return (int) $param;

        // Otherwise decrypt
        try {
            return (int) Crypt::decrypt($param);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function dataTable($query)
    {
        return datatables()
            ->collection($query)
            ->setRowId('id')
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="form-check-input row-checkbox" value="' . e($row['id']) . '">';
            })
            ->editColumn('date', function ($row) {
                $raw = $row['date'] ?? null;
                $display = $raw ? Auth::user()->dateFormat($raw) : '-';
                return '<span data-order="' . e($raw) . '" style="color:#333;">' . e($display) . '</span>';
            })
            ->editColumn('type', function ($row) {
                return '<span style="color:#0077c5;">' . e($row['type'] ?? '-') . '</span>';
            })
            ->editColumn('number', function ($row) {
                $url = $row['url'] ?? '#';
                $num = $row['number'] ?? '-';
                return '<a href="' . e($url) . '" style="color:#0077c5; text-decoration:none; font-weight:500;">' . e($num) . '</a>';
            })
            ->editColumn('payee', function ($row) {
                return '<span style="color:#0077c5;">' . e($row['payee'] ?? '-') . '</span>';
            })
            ->editColumn('category', function ($row) {
                $categoryName = $row['category'] ?? '-';
                if ($categoryName !== '-') {
                    return '<select class="form-select form-select-sm" style="width:auto; display:inline-block; font-size:12px;">
                                <option>' . e($categoryName) . '</option>
                            </select>';
                }
                return '-';
            })
            ->editColumn('total', function ($row) {
                $type = $row['type'] ?? '';
                $prefix = ($type === 'Bill Payment' || $type === 'Vendor Credit') ? '-' : '';
                $total = (float) ($row['total'] ?? 0);
                return '<span style="font-weight:500;">' . $prefix . '$ ' . number_format($total, 2) . '</span>';
            })
            ->addColumn('action', function ($row) {
                $actions = '<div class="d-flex justify-content-end align-items-center">
                                <div class="dropdown">
                                    <a class="text-secondary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        ' . __('View/Edit') . ' <i class="ti ti-chevron-down"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">';

                if (!empty($row['url']) && $row['url'] !== '#') {
                    $actions .= '<li><a class="dropdown-item" href="' . e($row['url']) . '">' . __('View') . '</a></li>';
                }
                if (!empty($row['edit_url'])) {
                    $actions .= '<li><a class="dropdown-item" href="' . e($row['edit_url']) . '">' . __('Edit') . '</a></li>';
                }

                $actions .= '</ul></div></div>';
                return $actions;
            })
            ->rawColumns(['checkbox', 'date', 'type', 'number', 'payee', 'category', 'total', 'action']);
    }

    public function query()
    {
        $transactions = collect();

        $vendorId = $this->resolveVendorId();
        if (!$vendorId) return $transactions;

        $creatorId = Auth::user()->creatorId();
        $transactionType = request()->get('transaction_type', '');

        // Default "Last 12 months" if request didn't send dates
        $dateFrom = request()->get('date_from') ?: now()->subYear()->toDateString();
        $dateTo   = request()->get('date_to') ?: now()->toDateString();

        $status = request()->get('status');
        $categoryId = request()->get('category');

        $vendorName = Vender::where('id', $vendorId)->value('name') ?? '-';

        // --------------------------------------------------------
        // 1) BILLS
        // --------------------------------------------------------
        if (empty($transactionType) || $transactionType === 'bill') {
            $bills = Bill::with('category')
                ->where('vender_id', $vendorId)
                ->where('created_by', $creatorId)
                ->where('type', 'Bill')
                ->when($dateFrom, fn($q) => $q->whereDate('bill_date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('bill_date', '<=', $dateTo))
                ->when($status !== null && $status !== '', fn($q) => $q->where('status', $status))
                ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
                ->get();

            foreach ($bills as $bill) {
                $transactions->push([
                    'id' => 'bill_' . $bill->id,
                    'date' => $bill->bill_date,
                    'type' => 'Bill',
                    'number' => '#' . Auth::user()->billNumberFormat($bill->bill_id),
                    'payee' => $vendorName,
                    'category' => optional($bill->category)->name ?? '-',
                    'total' => $bill->getTotal(),
                    'url' => route('bill.show', Crypt::encrypt($bill->id)),
                    'edit_url' => route('bill.edit', Crypt::encrypt($bill->id)),
                ]);
            }
        }

        // --------------------------------------------------------
        // 2) EXPENSE / CREDIT CARD / CHECK (Bill model)
        // --------------------------------------------------------
        if (empty($transactionType) || $transactionType === 'expense') {
            $expenses = Bill::with('category')
                ->where('vender_id', $vendorId)
                ->where('created_by', $creatorId)
                ->where(function ($q) {
                    $q->where('type', 'Expense')
                      ->orWhere('type', 'Credit Card')
                      ->orWhere('type', 'Check');
                })
                ->when($dateFrom, fn($q) => $q->whereDate('bill_date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('bill_date', '<=', $dateTo))
                ->when($status !== null && $status !== '', fn($q) => $q->where('status', $status))
                ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
                ->get();

            foreach ($expenses as $expense) {
                $transactions->push([
                    'id' => 'expense_' . $expense->id,
                    'date' => $expense->bill_date,
                    'type' => $expense->type,
                    'number' => Auth::user()->billNumberFormat($expense->bill_id),
                    'payee' => $vendorName,
                    'category' => optional($expense->category)->name ?? '-',
                    'total' => $expense->getTotal(),
                    'url' => route('bill.show', Crypt::encrypt($expense->id)),
                    'edit_url' => route('bill.edit', Crypt::encrypt($expense->id)),
                ]);
            }
        }

    }

    // --------------------------------------------------------
    // 3. BILL PAYMENTS (grouped by reference)
    // --------------------------------------------------------
    if (empty($transactionType) || $transactionType == 'bill_payment') {
        // Query bill payments grouped by reference
        $paymentsQuery = BillPayment::selectRaw('
                bill_payments.reference,
                MIN(bill_payments.date) as date,
                SUM(bill_payments.amount) as total_amount,
                MIN(bill_payments.id) as first_payment_id,
                GROUP_CONCAT(bill_payments.id) as payment_ids
            ')
            ->whereHas('bill', function ($q) use ($vendorId) {
                $q->where('vender_id', $vendorId)
                  ->where('type', 'Bill');
            })
            ->groupBy('bill_payments.reference');

        if ($dateFrom) $paymentsQuery->whereDate('bill_payments.date', '>=', $dateFrom);
        if ($dateTo) $paymentsQuery->whereDate('bill_payments.date', '<=', $dateTo);

        $payments = $paymentsQuery->get();

        foreach ($payments as $payment) {
            // Get payment IDs in this group
            $paymentIds = explode(',', $payment->payment_ids);
            
            // Get vendor credit amount for all payments in this group
            $vendorCreditAmount = 0;
            
            foreach ($paymentIds as $paymentId) {
                // Get the payment_no from transactions table for this payment
                $paymentTransaction = Transaction::where('payment_id', $paymentId)
                    ->whereNotNull('payment_no')
                    ->first();

                if ($paymentTransaction && $paymentTransaction->payment_no) {
                    // Find all transactions with the same payment_no where category = 'Vendor Credit'
                    $vendorCreditAmount += Transaction::where('payment_no', $paymentTransaction->payment_no)
                        ->where('category', 'Vendor Credit')
                        ->sum('amount');
                }

            }

            // Total = bill payment amount + vendor credit amount
            $totalAmount = $payment->total_amount + $vendorCreditAmount;

            $transactions->push([
                'id' => 'payment_ref_' . $payment->reference,
                'date' => $payment->date,
                'type' => 'Bill Payment',
                'number' => $payment->reference ?: Auth::user()->paymentNumberFormat($payment->first_payment_id),
                'payee' => $vendorName,
                'category' => '-',
                'total' => $totalAmount,
                'status' => 'Paid',
                'url' => '#',
                'edit_url' => null,
            ]);
        }

        // --------------------------------------------------------
        // 4) PURCHASE ORDERS
        // --------------------------------------------------------
        if (empty($transactionType) || $transactionType === 'purchase_order') {
            $purchases = Purchase::with('category')
                ->where('vender_id', $vendorId)
                ->where('created_by', $creatorId)
                ->when($dateFrom, fn($q) => $q->whereDate('purchase_date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('purchase_date', '<=', $dateTo))
                ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
                ->get();

            foreach ($purchases as $purchase) {
                $transactions->push([
                    'id' => 'purchase_' . $purchase->id,
                    'date' => $purchase->purchase_date,
                    'type' => 'Purchase Order',
                    'number' => '#' . ($purchase->purchase_number ?? $purchase->id),
                    'payee' => $vendorName,
                    'category' => optional($purchase->category)->name ?? '-',
                    'total' => $purchase->getTotal(),
                    'url' => route('purchase.show', Crypt::encrypt($purchase->id)),
                    'edit_url' => route('purchase.edit', Crypt::encrypt($purchase->id)),
                ]);
            }
        }


    // --------------------------------------------------------
    // 5. VENDOR CREDITS (from vendor_credits table)
    // --------------------------------------------------------
    if (empty($transactionType) || $transactionType == 'vendor_credit') {
        try {
            $creditsQuery = VendorCredit::where('vender_id', $vendorId)
                ->where('created_by', $creatorId);

            if ($dateFrom) $creditsQuery->whereDate('date', '>=', $dateFrom);
            if ($dateTo) $creditsQuery->whereDate('date', '<=', $dateTo);


        // --------------------------------------------------------
        // 6) GENERAL TRANSACTIONS (Checks/Others)
        // --------------------------------------------------------
        if (empty($transactionType) || $transactionType === 'expense' || $transactionType === 'check') {
            $transQuery = Transaction::where('user_id', $vendorId)
                ->where('user_type', 'Vender')
                ->where('created_by', $creatorId)
                ->when($dateFrom, fn($q) => $q->whereDate('date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('date', '<=', $dateTo));

            if ($transactionType === 'expense') {
                $transQuery->where('category', 'Bill');
            } elseif ($transactionType === 'check') {
                $transQuery->where('type', 'check');
            }

            foreach ($transQuery->get() as $t) {
                $type = ucfirst($t->type ?? 'Expense');
                if ($t->category === 'Bill') $type = 'Expense';


            foreach ($credits as $credit) {
                // Calculate total from vendor_credit_products and vendor_credit_accounts
                $creditTotal = 0;
                $creditTotal += \DB::table('vendor_credit_products')
                    ->where('vendor_credit_id', $credit->id)
                    ->sum(\DB::raw('price * quantity'));
                $creditTotal += \DB::table('vendor_credit_accounts')
                    ->where('vendor_credit_id', $credit->id)
                    ->sum('price');

                $transactions->push([
                    'id' => 'credit_' . $credit->id,
                    'date' => $credit->date,
                    'type' => 'Vendor Credit',
                    'number' => $credit->vendor_credit_id ?? ('#VC-' . $credit->id),
                    'payee' => $vendorName,
                    'category' => '-',
                    'total' => $creditTotal ?: ($credit->amount ?? 0),
                    'status' => 'Open',
                    'url' => '#',
                    'edit_url' => null,
                ]);
            }

        } catch (\Exception $e) {
            // VendorCredit table may not exist, skip silently
        }
    }

    // --------------------------------------------------------
    // 5b. UNAPPLIED PAYMENTS
    // --------------------------------------------------------
    if (empty($transactionType) || $transactionType == 'unapplied_payment') {
        try {
            $unappliedQuery = UnappliedPayment::where('vendor_id', $vendorId)
                ->where('created_by', $creatorId)
                ->where('unapplied_amount', '>', 0);

            if ($dateFrom) $unappliedQuery->whereDate('txn_date', '>=', $dateFrom);
            if ($dateTo) $unappliedQuery->whereDate('txn_date', '<=', $dateTo);

            $unappliedPayments = $unappliedQuery->get();

            foreach ($unappliedPayments as $unapplied) {
                $transactions->push([
                    'id' => 'unapplied_' . $unapplied->id,
                    'date' => $unapplied->txn_date,
                    'type' => 'Unapplied Payment',
                    'number' => $unapplied->reference ?? ('#UP-' . $unapplied->id),
                    'payee' => $vendorName,
                    'category' => '-',
                    'total' => $unapplied->unapplied_amount,
                    'status' => 'Unapplied',
                    'url' => '#',
                    'edit_url' => null,
                ]);
            }
        } catch (\Exception $e) {
            // UnappliedPayment table may not exist, skip silently
        }
    }

    // --------------------------------------------------------
    // 6. GENERAL TRANSACTIONS (Checks/Others)
    // --------------------------------------------------------
    if (empty($transactionType) || $transactionType == 'expense' || $transactionType == 'check') {
        $transQuery = Transaction::where('user_id', $vendorId)
            ->where('user_type', 'Vender')
            ->where('created_by', $creatorId);

        if ($transactionType == 'expense') {
            $transQuery->where('category', 'Bill');
        } elseif ($transactionType == 'check') {
            $transQuery->where('type', 'check');

        }

        // --------------------------------------------------------
        // 7) RECENTLY PAID
        // --------------------------------------------------------
        if ($transactionType === 'recently_paid') {
            $recentBills = Bill::with('category')
                ->where('vender_id', $vendorId)
                ->where('created_by', $creatorId)
                ->where('status', 4) // Paid
                ->where('updated_at', '>=', now()->subDays(30))
                ->get();

            foreach ($recentBills as $bill) {
                $transactions->push([
                    'id' => 'bill_' . $bill->id,
                    'date' => $bill->bill_date,
                    'type' => 'Bill',
                    'number' => '#' . Auth::user()->billNumberFormat($bill->bill_id),
                    'payee' => $vendorName,
                    'category' => optional($bill->category)->name ?? '-',
                    'total' => $bill->getTotal(),
                    'url' => route('bill.show', Crypt::encrypt($bill->id)),
                    'edit_url' => route('bill.edit', Crypt::encrypt($bill->id)),
                ]);
            }
        }

        return $transactions->sortByDesc('date')->values();
    }

    public function html()
    {
        return $this->builder()

                    ->setTableId('vendor-transactions-table')
                    ->columns($this->getColumns())
                    ->ajax([
                        // Missing required parameter for [Route: vender.show] [URI: vender/{vender}] [Missing parameter: vender].
                        'url' => route('vender.show',['vender'=>$this->vendor_id]),
                        'type' => 'GET',
                        //token
                        'headers' => [
                            'X-CSRF-TOKEN' => csrf_token(),
                        ],
                    ])
                    ->dom('t')
                    ->orderBy(1, 'desc')
                    ->parameters([
                        "dom" =>  "<'row'<'col-sm-12'tr>>",
                        'language' => [
                            'paginate' => [
                                'next' => '<i class="ti ti-chevron-right"></i>',
                                'previous' => '<i class="ti ti-chevron-left"></i>'
                            ]
                        ],
                        'drawCallback' => "function() {
                            $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
                        }"
                    ]);
    }

    protected function getColumns()
    {
        return [
            Column::computed('checkbox')
                ->title('<input type="checkbox" class="form-check-input" id="select-all">')
                ->exportable(false)->printable(false)->width(20)
                ->addClass('text-center align-middle'),

            Column::make('date')->title('DATE')->addClass('align-middle'),
            Column::make('type')->title('TYPE')->addClass('align-middle'),
            Column::make('number')->title('NO.')->addClass('align-middle'),
            Column::make('payee')->title('PAYEE')->addClass('align-middle'),
            Column::make('category')->title('CATEGORY')->addClass('align-middle'),
            Column::make('total')->title('TOTAL')->addClass('text-end align-middle'),

            Column::computed('action')
                ->exportable(false)->printable(false)->width(100)
                ->addClass('text-end align-middle')
                ->title('ACTION'),
        ];
    }

    protected function filename(): string
    {
        return 'VendorTransactions_' . date('YmdHis');
    }
}
