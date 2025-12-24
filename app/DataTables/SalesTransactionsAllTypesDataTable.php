<?php

namespace App\DataTables;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\SalesReceipt;
use App\Models\Proposal;
use App\Models\CreditNote;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class SalesTransactionsAllTypesDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->collection($query)
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="form-check-input row-checkbox" value="' . ($row['id'] ?? '') . '" data-type="' . ($row['type'] ?? '') . '">';
            })
            ->editColumn('date', function ($row) {
                return Auth::user()->dateFormat($row['date'] ?? '');
            })
            ->editColumn('amount', function ($row) {
                $amount = $row['amount'] ?? 0;
                // Payments and Credit Memos show as positive in the list
                return Auth::user()->priceFormat(abs($amount));
            })
            ->addColumn('status_badge', function ($row) {
                $status = $row['status'] ?? '';
                $statusClass = $this->getStatusBadgeClass($status, $row['type'] ?? '');
                return '<span class="badge ' . $statusClass . ' p-2 px-3 rounded">' . $status . '</span>';
            })
            ->addColumn('action', function ($row) {
                $actions = '<div class="d-flex">';
                
                if (!empty($row['view_url'])) {
                    $actions .= '<div class="action-btn bg-info ms-2">
                        <a href="' . $row['view_url'] . '" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="' . __('View') . '">
                            <i class="ti ti-eye text-white"></i>
                        </a>
                    </div>';
                }
                
                if (!empty($row['edit_url'])) {
                    $actions .= '<div class="action-btn bg-primary ms-2">
                        <a href="' . $row['edit_url'] . '" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="' . __('Edit') . '">
                            <i class="ti ti-pencil text-white"></i>
                        </a>
                    </div>';
                }
                
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['checkbox', 'status_badge', 'action']);
    }

    public function query()
    {
        $user = Auth::user();
        $companyId = $user->creatorId();
        
        $type = $this->type ?? 'all';
        $startDate = $this->startDate ?? Carbon::now()->subMonths(12)->toDateString();
        $endDate = $this->endDate ?? Carbon::now()->toDateString();
        $customerId = $this->customerId ?? '';
        $status = $this->status ?? 'all';

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
                    'type_key' => 'invoice',
                    'no' => Auth::user()->invoiceNumberFormat($inv->invoice_id),
                    'customer' => optional($inv->customer)->name ?? '-',
                    'memo' => $inv->memo ?? $inv->ref_number ?? '',
                    'amount' => $inv->total_amount ?? $inv->getTotal(),
                    'status' => $statusText,
                    'view_url' => route('invoice.show', Crypt::encrypt($inv->id)),
                    'edit_url' => route('invoice.edit', Crypt::encrypt($inv->id)),
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
                    'type_key' => 'payment',
                    'no' => '#' . $pay->id,
                    'customer' => optional(optional($pay->invoice)->customer)->name ?? '-',
                    'memo' => $pay->description ?? '',
                    'amount' => $pay->amount,
                    'status' => __('Closed'),
                    'view_url' => route('receive-payment.show', $pay->id),
                    'edit_url' => '',
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
                    'type_key' => 'estimate',
                    'no' => '#' . $prop->proposal_id,
                    'customer' => optional($prop->customer)->name ?? '-',
                    'memo' => '',
                    'amount' => $prop->total_amount ?? $prop->getTotal(),
                    'status' => __($statusText),
                    'view_url' => route('proposal.show', Crypt::encrypt($prop->id)),
                    'edit_url' => route('proposal.edit', Crypt::encrypt($prop->id)),
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
                    'type_key' => 'sales_receipt',
                    'no' => '#' . $sr->ref_number,
                    'customer' => optional($sr->customer)->name ?? '-',
                    'memo' => $sr->memo ?? '',
                    'amount' => $sr->total_amount ?? 0,
                    'status' => __($statusText),
                    'view_url' => route('sales-receipt.show', $sr->id),
                    'edit_url' => route('sales-receipt.edit', $sr->id),
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
                    'type_key' => 'credit_memo',
                    'no' => '#' . ($cn->credit_note_id ?? $cn->id),
                    'customer' => '-', // Credit note has customer ID, would need to fetch
                    'memo' => $cn->description ?? '',
                    'amount' => -$cn->amount, // Negative for credit
                    'status' => __('Applied'),
                    'view_url' => route('credit.note', $cn->id),
                    'edit_url' => '',
                ]);
            }
        }

        // Sort by date descending
        return $transactions->sortByDesc('date')->values();
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
            case 'closed':
                return $query->where('status', 4);
            default:
                return $query;
        }
    }

    /**
     * Get badge class based on status.
     */
    private function getStatusBadgeClass($status, $type)
    {
        $status = strtolower($status);
        
        if (str_contains($status, 'overdue')) {
            return 'bg-danger';
        }
        if (str_contains($status, 'due in') || str_contains($status, 'due today')) {
            return 'bg-warning';
        }
        if ($status === 'paid' || $status === 'closed' || $status === 'applied') {
            return 'bg-success';
        }
        if ($status === 'draft') {
            return 'bg-secondary';
        }
        if ($status === 'open' || $status === 'sent') {
            return 'bg-info';
        }
        if ($status === 'accepted' || $status === 'approved') {
            return 'bg-success';
        }
        if ($status === 'declined' || $status === 'rejected') {
            return 'bg-danger';
        }
        
        return 'bg-secondary';
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('sales-transactions-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1, 'desc')
            ->parameters([
                'dom' => 'Bfrtip',
                'paging' => true,
                'searching' => true,
                'info' => true,
                'pageLength' => 25,
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'language' => [
                    'paginate' => [
                        'first' => 'First',
                        'last' => 'Last',
                        'next' => 'Next',
                        'previous' => 'Previous',
                    ],
                    'info' => '_START_-_END_ of _TOTAL_',
                ],
                'drawCallback' => 'function() { $("[data-bs-toggle=tooltip]").tooltip(); }',
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::computed('checkbox')
                ->title('<input type="checkbox" class="form-check-input" id="selectAll">')
                ->width(30)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-center'),
            Column::make('date')->title(__('DATE')),
            Column::make('type')->title(__('TYPE')),
            Column::make('no')->title(__('NO.')),
            Column::make('customer')->title(__('CUSTOMER')),
            Column::make('memo')->title(__('MEMO')),
            Column::make('amount')->title(__('AMOUNT'))->addClass('text-end'),
            Column::computed('status_badge')->title(__('STATUS')),
            Column::computed('action')
                ->title(__('ACTION'))
                ->orderable(false)
                ->searchable(false)
                ->width(100)
                ->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'SalesTransactions_' . date('YmdHis');
    }
}
