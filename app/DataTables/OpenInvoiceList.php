<?php

namespace App\DataTables;

use App\Models\Invoice;
use App\Models\CreditNote;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class OpenInvoiceList extends DataTable
{
    public function dataTable($query)
    {
        // Get invoice data from query
        $invoiceData = collect($query->get())->filter(function ($row) {
            $balance = isset($row->balance_due) ? (float)$row->balance_due : 0;
            return round($balance, 2) != 0.00;
        })->map(function ($row) {
            $row->txn_type = 'Invoice';
            $row->open_amount = $row->balance_due;
            return $row;
        });

        // Get unapplied deposits (customer payments/deposits)
        // $depositData = $this->getUnappliedDeposits();

        // Get unapplied credit notes (credit notes not linked to invoices or payments)
        $creditNoteData = $this->getUnappliedCreditNotes();

        // Get overpayments (customer credits from excess payments)
        $overpaymentData = $this->getCustomerOverpayments();

        // Merge all data: invoices + overpayments (negative)
        // Merge all data: invoices + overpayments (negative) + credit notes
        $allData = $invoiceData;
            // ->merge($overpaymentData)
            // ->merge($creditNoteData);
            // ->merge($depositData);

        $grandTotalAmount = 0;
        $grandBalanceDue = 0;

        // Group by Customer Name
        $groupedData = $allData->groupBy('name')->sortKeys();

        $finalData = collect();

        foreach ($groupedData as $customer => $rows) {
            $subtotalAmount = 0;
            $subtotalDue = 0;

            if (empty($customer)) {
                $customer = 'Unknown Customer';
            }

            // Calculate subtotals first
            $customerRows = collect();
            foreach ($rows as $row) {
                $amount = $row->open_amount ?? $row->balance_due ?? 0;
                $subtotalAmount += abs($amount);
                $subtotalDue += (float)$amount;
                
                $row->customer = $customer;
                $row->past_due = isset($row->age) && $row->age > 0 ? $row->age . ' Days' : '-';
                $customerRows->push($row);
            }

            // Skip customers with 0 or near-zero balance (after netting invoices, deposits, credits)
            if (round($subtotalDue, 2) == 0.00) {
                continue;
            }

            // Header row for this customer
            $finalData->push((object) [
                'customer' => $customer,
                'transaction' => '<span class="" data-bucket="' . \Str::slug($customer) . '"> <span class="icon">▼</span> <strong>' . $customer . '</strong></span>',
                'due_date' => '',
                'past_due' => null,
                'type' => null,
                'status_label' => '',
                'total_amount' => null,
                'balance_due' => null,
                'isPlaceholder' => true,
                'open_balance' => null,
                'isSubtotal' => true,
                'isParent' => true
            ]);

            // Add all rows for this customer
            foreach ($customerRows as $row) {
                $finalData->push($row);
            }

            // Subtotal row for this customer
            $finalData->push((object) [
                'customer' => $customer,
                'transaction' => '<strong>Subtotal for ' . $customer . '</strong>',
                'due_date' => '',
                'past_due' => '',
                'type' => '',
                'status_label' => '',
                'total_amount' => $subtotalAmount,
                'balance_due' => $subtotalDue,
                'isSubtotal' => true,
            ]);

            $grandTotalAmount += $subtotalAmount;
            $grandBalanceDue += $subtotalDue;
        }

        // Grand total row
        $finalData->push((object) [
            'transaction' => '<strong>Grand Total</strong>',
            'due_date' => '',
            'past_due' => '',
            'type' => '',
            'status_label' => '',
            'age' => '',
            'total_amount' => $grandTotalAmount,
            'balance_due' => $grandBalanceDue,
            'isGrandTotal' => true,
        ]);

        return datatables()
            ->collection($finalData)
            ->addColumn('transaction', function ($row) {
                if (isset($row->isSubtotal) || isset($row->isGrandTotal) || isset($row->isPlaceholder)) {
                    return $row->transaction ?? '';
                }

                $txnType = $row->txn_type ?? 'Invoice';
                if ($txnType === 'Invoice') {
                    return \Auth::user()->invoiceNumberFormat($row->invoice ?? ($row->id ?? ''));
                } elseif ($txnType === 'Payment') {
                    return 'Payment #' . ($row->payment_no ?? $row->id ?? '');
                } elseif ($txnType === 'Deposit') {
                    return 'Deposit #' . ($row->doc_number ?? $row->id ?? '');
                } elseif ($txnType === 'Credit Memo') {
                    return 'Credit Memo #' . ($row->credit_note_id ?? $row->id ?? '');
                }
                return $row->id ?? '';
            })
            ->addColumn('due_date', fn($row) => $row->due_date ?? '')
            ->addColumn('type', function ($row) {
                if (isset($row->isSubtotal) || isset($row->isGrandTotal)) {
                    return '';
                }
                return $row->txn_type ?? 'Invoice';
            })
            ->addColumn('issue_date', fn($row) => $row->issue_date ?? '')
            ->addColumn('open_balance', function ($row) {
                if (isset($row->isPlaceholder)) {
                    return '';
                }
                $amount = $row->open_amount ?? $row->balance_due ?? 0;
                return number_format($amount, 2);
            })
            ->setRowClass(function ($row) {
                if (property_exists($row, 'isParent') && $row->isParent) {
                    return 'parent-row toggle-bucket bucket-' . \Str::slug($row->customer ?? 'na');
                }

                if (property_exists($row, 'isSubtotal') && $row->isSubtotal && !property_exists($row, 'isGrandTotal')) {
                    return 'subtotal-row bucket-' . \Str::slug($row->customer ?? 'na');
                }

                if (
                    !property_exists($row, 'isParent') &&
                    !property_exists($row, 'isSubtotal') &&
                    !property_exists($row, 'isGrandTotal') &&
                    !property_exists($row, 'isPlaceholder')
                ) {
                    return 'child-row bucket-' . \Str::slug($row->customer ?? 'na');
                }

                if (property_exists($row, 'isGrandTotal') && $row->isGrandTotal) {
                    return 'grandtotal-row';
                }

                return '';
            })
            ->rawColumns(['customer', 'transaction', 'status_label']);
    }

    /**
     * Get customer overpayments (excess payments) as negative rows
     * These are stored in transactions table with category = 'customer credit'
     */
    private function getCustomerOverpayments()
    {
        $creatorId = \Auth::user()->creatorId();
        
        // Get overpayments from transactions where category is 'customer credit'
        // user_id is the customer_id and user_type is 'customer'
        // payment_no references invoice_payments.txn_id
        $overpayments = DB::table('transactions')
            ->leftJoin('customers', 'customers.id', '=', 'transactions.user_id')
            ->leftJoin('invoice_payments', 'invoice_payments.txn_id', '=', 'transactions.payment_no')
            ->where('transactions.category', 'customer credit')
            ->where('transactions.user_type', 'customer')
            ->where('transactions.created_by', $creatorId)
            ->select(
                'transactions.id',
                'transactions.payment_no',
                'transactions.date as issue_date',
                'transactions.amount',
                'transactions.description',
                'customers.name'
            )
            ->get();

        return $overpayments->map(function ($payment) {
            return (object) [
                'id' => $payment->id,
                'payment_no' => $payment->payment_no,
                'issue_date' => $payment->issue_date,
                'due_date' => '',
                'name' => $payment->name ?? 'Unknown Customer',
                'txn_type' => 'Payment',
                'description' => $payment->description,
                'open_amount' => -1 * abs($payment->amount), // Negative amount (customer credit)
                'balance_due' => -1 * abs($payment->amount),
                'age' => 0,
            ];
        });
    }

    /**
     * Get unapplied deposits as negative rows
     */
    private function getUnappliedDeposits()
    {
        $creatorId = \Auth::user()->creatorId();
        
        // Get deposits with customer links
        $deposits = DB::table('deposits')
            ->join('deposit_lines', 'deposits.id', '=', 'deposit_lines.deposit_id')
            ->leftJoin('customers', 'customers.id', '=', 'deposit_lines.customer_id')
            // ->where('deposits.created_by', $creatorId)
            ->where('deposit_lines.customer_id', '>', 0)
            ->select(
                'deposits.id',
                'deposits.deposit_id',
                'deposits.doc_number',
                'deposits.txn_date as issue_date',
                'deposit_lines.amount',
                'customers.name'
            )
            ->get();

        return $deposits->map(function ($deposit) {
            return (object) [
                'id' => $deposit->id,
                'doc_number' => $deposit->doc_number ?? $deposit->deposit_id,
                'issue_date' => $deposit->issue_date,
                'due_date' => '',
                'name' => $deposit->name ?? 'Unknown Customer',
                'txn_type' => 'Deposit',
                'open_amount' => -1 * abs($deposit->amount), // Negative amount
                'balance_due' => -1 * abs($deposit->amount),
                'age' => 0,
            ];
        });
    }

    /**
     * Get unapplied credit notes as negative rows
     */
    private function getUnappliedCreditNotes()
    {
        $creatorId = \Auth::user()->creatorId();
        
        // Credit notes that are not linked to any invoice (invoice = 0 or null) 
        $creditNotes = DB::table('credit_notes')
            ->leftJoin('customers', 'customers.id', '=', 'credit_notes.customer')
            ->where(function ($q) {
                $q->whereNull('credit_notes.invoice')
                  ->orWhere('credit_notes.invoice', 0);
            })
            ->select(
                'credit_notes.id',
                'credit_notes.id as credit_note_id', // Use ID as credit_note_id since column is missing/problematic
                'credit_notes.date as issue_date',
                'credit_notes.amount',
                'customers.name'
            )
            ->get();



        return $creditNotes->map(function ($credit) {
            return (object) [
                'id' => $credit->id,
                'credit_note_id' => $credit->credit_note_id,
                'issue_date' => $credit->issue_date,
                'due_date' => '',
                'name' => $credit->name ?? 'Unknown Customer',
                'txn_type' => 'Credit Memo',
                'open_amount' => -1 * abs($credit->amount),
                'balance_due' => -1 * abs($credit->amount),
                'age' => 0,
            ];
        });
    }

    public function query(Invoice $model)
    {
        $start = request()->get('start_date')
            ?? request()->get('startDate')
            ?? Carbon::now()->startOfYear()->format('Y-m-d');

        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return $model->newQuery()
            ->select(
                'invoices.id',
                'invoices.invoice_id as invoice',
                'invoices.issue_date',
                'invoices.due_date',
                'invoices.status',
                'customers.name',

                // Subtotal (without tax)
                DB::raw('(
                    SELECT IFNULL(SUM((price * quantity) - discount), 0)
                    FROM invoice_products
                    WHERE invoice_products.invoice_id = invoices.id
                ) AS subtotal'),

                // Tax - use sales_tax_amount directly from invoices table
                DB::raw('IFNULL(invoices.sales_tax_amount, 0) AS total_tax'),

                // Payments (already includes credit memo applications from QBO)
                DB::raw('(
                    SELECT IFNULL(SUM(amount), 0)
                    FROM invoice_payments
                    WHERE invoice_payments.invoice_id = invoices.id
                ) AS pay_price'),

                // balance_due = subtotal + tax - payments
                DB::raw('(
                    (
                        (SELECT IFNULL(SUM((price * quantity) - discount), 0)
                         FROM invoice_products
                         WHERE invoice_products.invoice_id = invoices.id)
                        +
                        IFNULL(invoices.sales_tax_amount, 0)
                    )
                    -
                    (SELECT IFNULL(SUM(amount), 0)
                     FROM invoice_payments
                     WHERE invoice_payments.invoice_id = invoices.id)
                ) AS balance_due'),

                DB::raw('GREATEST(DATEDIFF(CURDATE(), invoices.due_date), 0) AS age')
            )
            ->leftJoin('customers', 'customers.id', '=', 'invoices.customer_id')
            ->where('invoices.created_by', \Auth::user()->creatorId())
            ->where('invoices.issue_date', '<', $end)
            ->where('invoices.status', '!=', 4)
            ->orderBy('customers.name', 'asc')
            ->orderBy('invoices.issue_date', 'asc');
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
                'rowGroup' => [
                    'dataSrc' => 'customer',
                ],
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('issue_date')->title('Date'),
            Column::make('transaction')->title('Transaction'),
            Column::make('type')->title('Type'),
            Column::make('due_date')->title('Due Date'),
            Column::make('open_balance')->title('Open Balance'),
        ];
    }
}
