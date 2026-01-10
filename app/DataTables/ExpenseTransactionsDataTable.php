<?php

namespace App\DataTables;

use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\CreditCardPayment;
use App\Models\Purchase;
use App\Models\VendorCredit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

/**
 * Helper class for expense transactions data.
 * This follows the same pattern as SalesTransactionsAllTypesController.
 */
class ExpenseTransactionsDataTable
{
    public $type = 'all';
    public $startDate;
    public $endDate;
    public $vendorId = '';
    public $status = 'all';

    public function __construct()
    {
        $this->startDate = Carbon::now()->subMonths(12)->toDateString();
        $this->endDate = Carbon::now()->toDateString();
    }

    /**
     * Get all expense transactions based on filters.
     */
    public function getTransactions()
    {
        $user = Auth::user();
        $companyId = $user->creatorId();
        
        $type = $this->type;
        $startDate = $this->startDate;
        $endDate = $this->endDate;
        $vendorId = $this->vendorId;
        $status = $this->status;

        $transactions = collect();

        // Expenses (Bill model with type='Expense')
        if ($type === 'all' || $type === 'expense') {
            $expenses = Bill::where('created_by', $companyId)
                ->where('type', 'Expense')
                ->whereBetween('bill_date', [$startDate, $endDate])
                ->when($vendorId, fn($q) => $q->where('vender_id', $vendorId))
                ->with(['vender', 'accounts.chartAccount'])
                ->get();

            foreach ($expenses as $exp) {
                $statusText = $this->getExpenseStatus($exp);
                $category = $this->getCategory($exp);
                
                $transactions->push([
                    'id' => $exp->id,
                    'date' => $exp->bill_date,
                    'type' => __('Expense'),
                    'type_key' => 'expense',
                    'no' => $exp->ref_number ?? '',
                    'payee' => optional($exp->vender)->name ?? '-',
                    'class' => $this->hasMultiple($exp) ? '--Split--' : $category,
                    'location' => '-',
                    'status' => $statusText,
                    'method' => '-',
                    'source' => '-',
                    'category' => $this->hasMultiple($exp) ? '--Split--' : $category,
                    'memo' => $exp->notes ?? '',
                    'due_date' => '',
                    'balance' => 0,
                    'total' => $exp->getTotal(),
                    'attachments' => '',
                    'view_url' => route('expense.edit', Crypt::encrypt($exp->id)),
                    'edit_url' => route('expense.edit', Crypt::encrypt($exp->id)),
                ]);
            }
        }
        if ($type === 'all' || $type === 'credit_card') {
            $expenses = Bill::where('created_by', $companyId)
                ->where('type', 'Credit Card Credit')
                ->whereBetween('bill_date', [$startDate, $endDate])
                ->when($vendorId, fn($q) => $q->where('vender_id', $vendorId))
                ->with(['vender', 'accounts.chartAccount'])
                ->get();

            foreach ($expenses as $exp) {
                $statusText = $this->getExpenseStatus($exp);
                $category = $this->getCategory($exp);
                
                $transactions->push([
                    'id' => $exp->id,
                    'date' => $exp->bill_date,
                    'type' => __('Credit Card Credit'),
                    'type_key' => 'credit_card',
                    'no' => $exp->ref_number ?? '',
                    'payee' => optional($exp->vender)->name ?? '-',
                    'class' => $this->hasMultiple($exp) ? '--Split--' : $category,
                    'location' => '-',
                    'status' => $statusText,
                    'method' => '-',
                    'source' => '-',
                    'category' => $this->hasMultiple($exp) ? '--Split--' : $category,
                    'memo' => $exp->notes ?? '',
                    'due_date' => '',
                    'balance' => 0,
                    'total' => -$exp->getTotal(),
                    'attachments' => '',
                    'view_url' => route('creditcreditcard.edit', Crypt::encrypt($exp->id)),
                    'edit_url' => route('creditcreditcard.edit', Crypt::encrypt($exp->id)),
                ]);
            }
        }

        // Bills
        if ($type === 'all' || $type === 'bill') {
            $bills = Bill::where('created_by', $companyId)
                ->where('type', 'Bill')
                ->whereBetween('bill_date', [$startDate, $endDate])
                ->when(!empty($vendorId), function ($q) use ($vendorId) {
                    return $q->where('vender_id', $vendorId);
                })
                ->with(['vender', 'accounts.chartAccount', 'items'])
                ->get();

            \Log::info('Bills Query Debug', [
                'count' => $bills->count(),
                'ids' => $bills->pluck('id')->toArray(),
            ]);

            foreach ($bills as $bill) {
                $statusText = $this->getBillStatus($bill);
                $category = $this->getCategory($bill);
                $balance = $bill->getDue();
                
                $transactions->push([
                    'id' => $bill->id,
                    'date' => $bill->bill_date,
                    'type' => __('Bill'),
                    'type_key' => 'bill',
                    'no' => $bill->ref_number ?? '',
                    'payee' => optional($bill->vender)->name ?? '-',
                    'class' => $this->hasMultiple($bill) ? '--Split--' : '-',
                    'location' => '-',
                    'status' => $statusText,
                    'method' => '-',
                    'source' => '-',
                    'category' => $this->hasMultiple($bill) ? '--Split--' : $category,
                    'memo' => $bill->notes ?? '',
                    'due_date' => $bill->due_date ? Auth::user()->dateFormat($bill->due_date) : '',
                    'balance' => $balance,
                    'total' => $bill->getTotal(),
                    'attachments' => '',
                    'view_url' => route('bill.edit', Crypt::encrypt($bill->id)),
                    'edit_url' => route('bill.edit', Crypt::encrypt($bill->id)),
                ]);
            }
        }

        // Bill Payments
        if ($type === 'all' || $type === 'bill_payment') {
            $billPayments = BillPayment::whereHas('bill', function($q) use ($companyId) {
                    $q->where('created_by', $companyId)
                    ->whereNotIn('type', ['Expense', 'Credit Card Credit']);
                })
                ->whereBetween('date', [$startDate, $endDate])
                ->with(['bill.vender'])
                ->get();

            foreach ($billPayments as $payment) {
                $paymentType = __('Bill Payment');
                
                $transactions->push([
                    'id' => $payment->id,
                    'date' => $payment->date,
                    'type' => $paymentType,
                    'type_key' => 'bill_payment',
                    'no' => $payment->id,
                    'payee' => optional(optional($payment->bill)->vender)->name ?? '-',
                    'class' => '-',
                    'location' => '-',
                    'status' => __('Applied'),
                    'method' => '-',
                    'source' => '-',
                    'category' => '',
                    'memo' => $payment->description ?? '',
                    'due_date' => '',
                    'balance' => 0,
                    'total' => -$payment->amount, // Negative for payments
                    'attachments' => '',
                    'view_url' => route('bill.payment', $payment->bill_id),
                    'edit_url' => '',
                ]);
            }
        }

        // Checks (Bill model with type='Check')
        if ($type === 'all' || $type === 'check') {
            $checks = Bill::where('created_by', $companyId)
                ->where('type', 'Check')
                ->whereBetween('bill_date', [$startDate, $endDate])
                ->when($vendorId, fn($q) => $q->where('vender_id', $vendorId))
                ->with(['vender', 'accounts.chartAccount'])
                ->get();

            foreach ($checks as $check) {
                $category = $this->getCategory($check);
                
                $transactions->push([
                    'id' => $check->id,
                    'date' => $check->bill_date,
                    'type' => __('Check'),
                    'type_key' => 'check',
                    'no' => $check->ref_number ?? '',
                    'payee' => optional($check->vender)->name ?? '-',
                    'class' => $this->hasMultiple($check) ? '--Split--' : '-',
                    'location' => '-',
                    'status' => __('Paid'),
                    'method' => '-',
                    'source' => '-',
                    'category' => $this->hasMultiple($check) ? '--Split--' : $category,
                    'memo' => $check->notes ?? '',
                    'due_date' => '',
                    'balance' => 0,
                    'total' => $check->getTotal(),
                    'attachments' => '',
                    'view_url' => route('expense.show', Crypt::encrypt($check->id)),
                    'edit_url' => route('expense.edit', Crypt::encrypt($check->id)),
                ]);
            }
        }

        // Purchase Orders - use purchase_date column (correct column name)
        if ($type === 'all' || $type === 'purchase_order') {
            $purchases = Purchase::where('created_by', $companyId)
                ->whereBetween('purchase_date', [$startDate, $endDate])
                ->when($vendorId, fn($q) => $q->where('vender_id', $vendorId))
                ->with(['vender'])
                ->get();

            foreach ($purchases as $po) {
                $statusText = Purchase::$statues[$po->status] ?? '-';
                
                $transactions->push([
                    'id' => $po->id,
                    'date' => $po->purchase_date,
                    'type' => __('Purchase Order'),
                    'type_key' => 'purchase_order',
                    'no' => $po->purchase_id ?? '',
                    'payee' => optional($po->vender)->name ?? '-',
                    'class' => '--Split--',
                    'location' => '-',
                    'status' => __($statusText),
                    'method' => '-',
                    'source' => '-',
                    'category' => '--Split--',
                    'memo' => $po->notes ?? '',
                    'due_date' => $po->expected_date ? Auth::user()->dateFormat($po->expected_date) : '',
                    'balance' => 0,
                    'total' => $po->getTotal(),
                    'attachments' => '',
                    'view_url' => route('purchase.edit', Crypt::encrypt($po->id)),
                    'edit_url' => route('purchase.edit', Crypt::encrypt($po->id)),
                ]);
            }
        }

        // Vendor Credits
        if ($type === 'all' || $type === 'vendor_credit') {
            $vendorCredits = VendorCredit::where('created_by', $companyId)
                ->whereBetween('date', [$startDate, $endDate])
                ->when($vendorId, fn($q) => $q->where('vendor_id', $vendorId))
                ->with(['vendor'])
                ->get();

            foreach ($vendorCredits as $vc) {
                $transactions->push([
                    'id' => $vc->id,
                    'date' => $vc->date,
                    'type' => __('Vendor Credit'),
                    'type_key' => 'vendor_credit',
                    'no' => $vc->credit_number ?? '',
                    'payee' => optional($vc->vendor)->name ?? '-',
                    'class' => '-',
                    'location' => '-',
                    'status' => $vc->getRemainingAmountAttribute() > 0 ? __('Unapplied') : __('Applied'),
                    'method' => '-',
                    'source' => '-',
                    'category' => '',
                    'memo' => $vc->memo ?? '',
                    'due_date' => '',
                    'balance' => 0,
                    'total' => -$vc->getRemainingAmountAttribute(), // Negative for credits
                    'attachments' => '',
                    'view_url' => route('vendor-credit.edit', $vc->id),
                    'edit_url' => route('vendor-credit.edit', $vc->id),
                ]);
            }
        }
        // Vendor Credits
        if ($type === 'all' || $type === 'pay_down_credit_card') {
            $vendorCredits = CreditCardPayment::where('created_by', $companyId)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->when($vendorId, function ($q) use ($vendorId) {
                $q->where(function ($qq) use ($vendorId) {
                    $qq->where('payee_id', $vendorId)
                    ->orWhereNull('payee_id');
                });
            })
            ->with('payee')
            ->get();

            foreach ($vendorCredits as $vc) {
                $transactions->push([
                    'id' => $vc->id,
                    'date' => $vc->payment_date,
                    'type' => __('Pay Down Credit Card'),
                    'type_key' => 'pay_down_credit_card',
                    'no' => $vc->credit_number ?? '',
                    'payee' => optional($vc->payee)->name ?? '-',
                    'class' => '-',
                    'location' => '-',
                    'status' => $vc->status == 0 ? __('Draft') : __('Cleared'),
                    'method' => '-',
                    'source' => '-',
                    'category' => '',
                    'memo' => $vc->memo ?? '',
                    'due_date' => '',
                    'balance' => 0,
                    'total' => $vc->amount, // Negative for credits
                    'attachments' => '',
                    'view_url' => route('paydowncreditcard.edit', $vc->id),
                    'edit_url' => route('paydowncreditcard.edit', $vc->id),
                ]);
            }
        }

        // Sort by date descending and return as array
        $result = $transactions->sortByDesc('date')->values()->toArray();
        
        
        
        return $result;
    }

    /**
     * Get expense status text.
     */
    private function getExpenseStatus($expense)
    {
        $status = $expense->status ?? 0;
        if ($status == 4) {
            return __('Paid');
        }
        return __('Paid'); // Expenses are typically paid immediately
    }

    /**
     * Get bill status text.
     */
    private function getBillStatus($bill)
    {
        $status = $bill->status ?? 0;
        
        if ($status == 4) {
            return __('Paid');
        }
        
        if ($status == 0) {
            return __('Draft');
        }

        // Check if overdue
        if ($bill->due_date) {
            $dueDate = Carbon::parse($bill->due_date);
            $today = Carbon::now()->startOfDay();
            
            if ($dueDate->lt($today) && $bill->getDue() > 0) {
                return __('Overdue');
            }
        }

        if ($status == 3) {
            return __('Partial');
        }

        return Bill::$statues[$status] ?? __('Open');
    }

    /**
     * Get category from first account.
     */
    private function getCategory($bill)
    {
        $firstAccount = $bill->accounts->first();
        return $firstAccount && $firstAccount->chartAccount ? $firstAccount->chartAccount->name : '-';
    }

    /**
     * Check if bill has multiple line items.
     */
    private function hasMultiple($bill)
    {
        $accountCount = $bill->accounts->count();
        $itemCount = $bill->items ? $bill->items->count() : 0;
        return ($accountCount + $itemCount) > 1;
    }

    /**
     * Calculate total amount from transactions.
     */
    public function calculateTotal($transactions)
    {
        $total = 0;
        foreach ($transactions as $txn) {
            $total += $txn['total'] ?? 0;
        }
        return $total;
    }
}
