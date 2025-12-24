<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\SalesReceipt;
use App\Models\Proposal;
use App\Models\CreditNote;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class SalesTransactionsAllTypesController extends Controller
{
    /**
     * Display the sales transactions page with all transaction types.
     */
    public function index(Request $request)
    {
        if (!Auth::user()->can('manage transaction')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $user = Auth::user();
        $companyId = $user->creatorId();
        
        // Get filter values
        $type = $request->get('type', 'all');
        $dateRange = $request->get('date_range', 'last_12_months');
        $customerId = $request->get('customer', '');
        $status = $request->get('status', 'all');

        // Calculate date range
        $dateFilters = $this->getDateRange($dateRange);
        $startDate = $dateFilters['start'];
        $endDate = $dateFilters['end'];

        // Get customers for dropdown
        $customers = Customer::where('created_by', $companyId)
            ->pluck('name', 'id')
            ->prepend(__('Search'), '');

        // Calculate summary metrics
        $salesData = $this->calculateSalesMetrics($companyId, $startDate, $endDate);

        // Get transactions
        $transactions = $this->getTransactions($companyId, $type, $startDate, $endDate, $customerId, $status);

        // Type options for filter
        $typeOptions = [
            'all' => __('All transactions'),
            'invoice' => __('Invoice'),
            'payment' => __('Payment'),
            'estimate' => __('Estimate'),
            'sales_receipt' => __('Sales Receipt'),
            'credit_memo' => __('Credit Memo'),
        ];

        // Date range options (matches QBO exactly)
        $dateRangeOptions = [
            'all' => __('All'),
            'custom' => __('Custom dates'),
            'today' => __('Today'),
            'yesterday' => __('Yesterday'),
            'this_week' => __('This week'),
            'last_week' => __('Last week'),
            'this_month' => __('This month'),
            'last_month' => __('Last month'),
            'last_30_days' => __('Last 30 days'),
            'this_quarter' => __('This quarter'),
            'last_quarter' => __('Last quarter'),
            'last_3_months' => __('Last 3 months'),
            'last_6_months' => __('Last 6 months'),
            'last_12_months' => __('Last 12 months'),
            'year_to_date' => __('Year to date'),
            'this_year' => __('This year'),
            'last_year' => (string)Carbon::now()->subYear()->year,
        ];

        // Status options
        $statusOptions = [
            'all' => __('All statuses'),
            'open' => __('Open'),
            'overdue' => __('Overdue'),
            'paid' => __('Paid'),
        ];

        return view('SalesTransactionsAllTypes.SalesTransactionsAllTypes', compact(
            'salesData',
            'transactions',
            'customers',
            'typeOptions',
            'dateRangeOptions',
            'statusOptions',
            'type',
            'dateRange',
            'customerId',
            'status'
        ));
    }

    /**
     * Get all transactions based on filters.
     */
    private function getTransactions($companyId, $type, $startDate, $endDate, $customerId, $status)
    {
        $transactions = collect();

        // Invoices
        if ($type === 'all' || $type === 'invoice') {
            $invoices = Invoice::where('created_by', $companyId)
                ->whereBetween('issue_date', [$startDate, $endDate])
                ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
                ->when($status !== 'all', fn($q) => $this->applyInvoiceStatusFilter($q, $status))
                ->with('customer')
                ->get();

            foreach ($invoices as $inv) {
                $statusText = $this->getInvoiceStatusText($inv);
                $transactions->push([
                    'id' => $inv->id,
                    'date' => $inv->issue_date,
                    'type' => __('Invoice'),
                    'no' => Auth::user()->invoiceNumberFormat($inv->invoice_id),
                    'customer' => optional($inv->customer)->name ?? '-',
                    'memo' => $inv->memo ?? $inv->ref_number ?? '',
                    'amount' => $inv->total_amount ?? (method_exists($inv, 'getTotal') ? $inv->getTotal() : 0),
                    'status' => $statusText,
                    'view_url' => route('invoice.show', Crypt::encrypt($inv->id)),
                ]);
            }
        }

        // Payments (Invoice Payments)
        if ($type === 'all' || $type === 'payment') {
            $payments = InvoicePayment::whereHas('invoice', function($q) use ($companyId) {
                    $q->where('created_by', $companyId);
                })
                ->whereBetween('date', [$startDate, $endDate])
                ->when($customerId, function($q) use ($customerId) {
                    $q->whereHas('invoice', function($iq) use ($customerId) {
                        $iq->where('customer_id', $customerId);
                    });
                })
                ->with(['invoice.customer'])
                ->get();

            foreach ($payments as $pay) {
                $transactions->push([
                    'id' => $pay->id,
                    'date' => $pay->date,
                    'type' => __('Payment'),
                    'no' => '#' . $pay->id,
                    'customer' => optional(optional($pay->invoice)->customer)->name ?? '-',
                    'memo' => $pay->description ?? '',
                    'amount' => $pay->amount,
                    'status' => __('Closed'),
                    'view_url' => route('receive-payment.show', $pay->id),
                ]);
            }
        }

        // Estimates (Proposals)
        if ($type === 'all' || $type === 'estimate') {
            $proposals = Proposal::where('created_by', $companyId)
                ->whereBetween('issue_date', [$startDate, $endDate])
                ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
                ->with('customer')
                ->get();

            foreach ($proposals as $prop) {
                $statusText = Proposal::$statues[$prop->status] ?? '-';
                $transactions->push([
                    'id' => $prop->id,
                    'date' => $prop->issue_date,
                    'type' => __('Estimate'),
                    'no' => '#' . $prop->proposal_id,
                    'customer' => optional($prop->customer)->name ?? '-',
                    'memo' => '',
                    'amount' => $prop->total_amount ?? (method_exists($prop, 'getTotal') ? $prop->getTotal() : 0),
                    'status' => __($statusText),
                    'view_url' => route('proposal.show', Crypt::encrypt($prop->id)),
                ]);
            }
        }

        // Sales Receipts
        if ($type === 'all' || $type === 'sales_receipt') {
            $salesReceipts = SalesReceipt::where('created_by', $companyId)
                ->whereBetween('issue_date', [$startDate, $endDate])
                ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
                ->with('customer')
                ->get();

            foreach ($salesReceipts as $sr) {
                $statusText = SalesReceipt::$statues[$sr->status] ?? '-';
                $transactions->push([
                    'id' => $sr->id,
                    'date' => $sr->issue_date,
                    'type' => __('Sales Receipt'),
                    'no' => '#' . $sr->ref_number,
                    'customer' => optional($sr->customer)->name ?? '-',
                    'memo' => $sr->memo ?? '',
                    'amount' => $sr->total_amount ?? 0,
                    'status' => __($statusText),
                    'view_url' => route('sales-receipt.show', $sr->id),
                ]);
            }
        }

        // Credit Memos (Credit Notes)
        if ($type === 'all' || $type === 'credit_memo') {
            $creditNotes = CreditNote::where('created_by', $companyId)
                ->whereBetween('date', [$startDate, $endDate])
                ->when($customerId, fn($q) => $q->where('customer', $customerId))
                ->get();

            foreach ($creditNotes as $cn) {
                $transactions->push([
                    'id' => $cn->id,
                    'date' => $cn->date,
                    'type' => __('Credit Memo'),
                    'no' => '#' . ($cn->credit_note_id ?? $cn->id),
                    'customer' => '-',
                    'memo' => $cn->description ?? '',
                    'amount' => -$cn->amount,
                    'status' => __('Applied'),
                    'view_url' => route('credit.note', $cn->id),
                ]);
            }
        }

        // Sort by date descending
        return $transactions->sortByDesc('date')->values()->toArray();
    }

    /**
     * Calculate date range based on the selected filter.
     */
    private function getDateRange($dateRange)
    {
        $now = Carbon::now();
        
        switch ($dateRange) {
            case 'all':
                return [
                    'start' => '1970-01-01',
                    'end' => $now->copy()->addYear()->toDateString(),
                ];
            case 'custom':
                // For custom dates, we'll use a wide range (can be enhanced with date pickers)
                return [
                    'start' => $now->copy()->subYear()->toDateString(),
                    'end' => $now->copy()->toDateString(),
                ];
            case 'today':
                return [
                    'start' => $now->copy()->toDateString(),
                    'end' => $now->copy()->toDateString(),
                ];
            case 'yesterday':
                return [
                    'start' => $now->copy()->subDay()->toDateString(),
                    'end' => $now->copy()->subDay()->toDateString(),
                ];
            case 'this_week':
                return [
                    'start' => $now->copy()->startOfWeek()->toDateString(),
                    'end' => $now->copy()->endOfWeek()->toDateString(),
                ];
            case 'last_week':
                return [
                    'start' => $now->copy()->subWeek()->startOfWeek()->toDateString(),
                    'end' => $now->copy()->subWeek()->endOfWeek()->toDateString(),
                ];
            case 'this_month':
                return [
                    'start' => $now->copy()->startOfMonth()->toDateString(),
                    'end' => $now->copy()->endOfMonth()->toDateString(),
                ];
            case 'last_month':
                return [
                    'start' => $now->copy()->subMonth()->startOfMonth()->toDateString(),
                    'end' => $now->copy()->subMonth()->endOfMonth()->toDateString(),
                ];
            case 'last_30_days':
                return [
                    'start' => $now->copy()->subDays(30)->toDateString(),
                    'end' => $now->copy()->toDateString(),
                ];
            case 'this_quarter':
                return [
                    'start' => $now->copy()->firstOfQuarter()->toDateString(),
                    'end' => $now->copy()->lastOfQuarter()->toDateString(),
                ];
            case 'last_quarter':
                return [
                    'start' => $now->copy()->subQuarter()->firstOfQuarter()->toDateString(),
                    'end' => $now->copy()->subQuarter()->lastOfQuarter()->toDateString(),
                ];
            case 'last_3_months':
                return [
                    'start' => $now->copy()->subMonths(3)->toDateString(),
                    'end' => $now->copy()->toDateString(),
                ];
            case 'last_6_months':
                return [
                    'start' => $now->copy()->subMonths(6)->toDateString(),
                    'end' => $now->copy()->toDateString(),
                ];
            case 'year_to_date':
                return [
                    'start' => $now->copy()->startOfYear()->toDateString(),
                    'end' => $now->copy()->toDateString(),
                ];
            case 'this_year':
                return [
                    'start' => $now->copy()->startOfYear()->toDateString(),
                    'end' => $now->copy()->endOfYear()->toDateString(),
                ];
            case 'last_year':
                return [
                    'start' => $now->copy()->subYear()->startOfYear()->toDateString(),
                    'end' => $now->copy()->subYear()->endOfYear()->toDateString(),
                ];
            case 'last_12_months':
            default:
                return [
                    'start' => $now->copy()->subMonths(12)->toDateString(),
                    'end' => $now->copy()->toDateString(),
                ];
        }
    }

    /**
     * Calculate sales metrics for the summary bar.
     */
    private function calculateSalesMetrics($companyId, $startDate, $endDate)
    {
        // Estimates (Proposals)
        $estimatesQuery = Proposal::where('created_by', $companyId)
            ->whereBetween('issue_date', [$startDate, $endDate]);
        $estimatesCount = $estimatesQuery->count();
        $estimatesAmount = $estimatesQuery->sum('total_amount') ?? 0;

        // Unbilled income - Invoices in draft status
        $unbilledAmount = Invoice::where('created_by', $companyId)
            ->where('status', 0) // Draft
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->sum('total_amount') ?? 0;

        // Overdue invoices
        $overdueQuery = Invoice::where('created_by', $companyId)
            ->whereIn('status', [1, 2, 3]) // Sent, Unpaid, Partially Paid
            ->where('due_date', '<', Carbon::now()->toDateString())
            ->whereBetween('issue_date', [$startDate, $endDate]);
        $overdueCount = $overdueQuery->count();
        $overdueAmount = $overdueQuery->sum('total_amount') ?? 0;

        // Open invoices and credits
        $openQuery = Invoice::where('created_by', $companyId)
            ->whereIn('status', [1, 2, 3]) // Sent, Unpaid, Partially Paid
            ->whereBetween('issue_date', [$startDate, $endDate]);
        $openCount = $openQuery->count();
        $openAmount = $openQuery->sum('total_amount') ?? 0;

        // Recently paid (last 30 days)
        $paidQuery = Invoice::where('created_by', $companyId)
            ->where('status', 4) // Paid
            ->whereBetween('issue_date', [Carbon::now()->subDays(30)->toDateString(), Carbon::now()->toDateString()]);
        $paidCount = $paidQuery->count();
        $paidAmount = $paidQuery->sum('total_amount') ?? 0;

        return [
            'estimates' => [
                'count' => $estimatesCount,
                'amount' => $estimatesAmount,
            ],
            'unbilled' => [
                'amount' => $unbilledAmount,
            ],
            'overdue' => [
                'count' => $overdueCount,
                'amount' => $overdueAmount,
            ],
            'open' => [
                'count' => $openCount,
                'amount' => $openAmount,
            ],
            'paid' => [
                'count' => $paidCount,
                'amount' => $paidAmount,
            ],
        ];
    }

    /**
     * Get invoice status text based on due date and payment status.
     */
    private function getInvoiceStatusText($invoice)
    {
        if ($invoice->status == 4) {
            return __('Paid');
        }

        if ($invoice->status == 0) {
            return __('Draft');
        }

        $dueDate = Carbon::parse($invoice->due_date);
        $today = Carbon::now()->startOfDay();
        $diff = $today->diffInDays($dueDate, false);

        if ($diff < 0) {
            return __('Overdue :d days', ['d' => abs($diff)]);
        } elseif ($diff == 0) {
            return __('Due today');
        } else {
            return __('Due in :d days', ['d' => $diff]);
        }
    }

    /**
     * Apply status filter to invoice query.
     */
    private function applyInvoiceStatusFilter($query, $status)
    {
        switch ($status) {
            case 'open':
                return $query->whereIn('status', [1, 2, 3]);
            case 'overdue':
                return $query->whereIn('status', [1, 2, 3])
                    ->where('due_date', '<', Carbon::now()->toDateString());
            case 'paid':
                return $query->where('status', 4);
            default:
                return $query;
        }
    }
}
