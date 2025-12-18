<?php

namespace App\DataTables;

use App\Models\Invoice;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class InvoicesandReceivedPaymentsDataTable extends DataTable
{
    public function dataTable($query)
    {
        $data = collect($query);

        $finalData = collect();


        $grandTotal = 0;

        $grouped = $data->groupBy('name');

        // 🔹 Merge per customer
        // $customers = $invoices->pluck('name')
        //     ->merge($payments->pluck('name'))
        //     ->unique();


        foreach ($grouped as $customer => $rows) {
            $subtotal = 0;
            
            // Count total entries (invoices + payments + credit notes)
            $entryCount = 0;
            foreach ($rows as $row) {
                $entryCount++; // Count the invoice
                if (!empty($row->payments)) {
                    $entryCount += count($row->payments); // Count payments and credit notes
                }
            }
            
            $finalData->push((object) [
                // 'transaction' => '<strong>' . $customer . '</strong>',
                'customer' => $customer,
                'transaction' => '<span class="" data-bucket="' . \Str::slug($customer) . '"> <span class="icon">▼</span> <strong>' . $customer . '</strong> <span class="text-muted">(' . $entryCount . ')</span></span>',
                'issue_date' => '',
                'type' => '',
                'total_amount' => '',
                'memo' => '',
                'isPlaceholder' => true,
                'isParent' => true
            ]);

            foreach ($rows as $row) {
                // 👇 Push payments first (if any)
                if (!empty($row->payments)) {
                    foreach ($row->payments as $pay) {
                        $subtotal += $pay->total_amount;
                        $grandTotal += $pay->total_amount;
                        $row->customer = $customer;
                        $finalData->push($pay);
                    }
                }

                // 👇 Then push the invoice row itself
                $subtotal += $row->total_amount;
                $grandTotal += $row->total_amount;
                $row->customer = $customer;
                $finalData->push($row);
            }

            // Subtotal row
            // $finalData->push((object) [
            //     'customer' => $customer,
            //     'transaction' => '<strong>Subtotal for ' . $customer . '</strong>',
            //     'issue_date' => '',
            //     'type' => '',
            //     'total_amount' => $subtotal,
            //     'memo' => '',
            //     'isSubtotal' => true,
            // ]);

            // Spacer row
            $finalData->push((object) [
                'transaction' => '',
                'issue_date' => '',
                'type' => '',
                'total_amount' => '',
                'memo' => '',
                'isPlaceholder' => true,
            ]);
        }


        // Grand total
        // $finalData->push((object) [
        //     'transaction' => '<strong>Grand Total</strong>',
        //     'issue_date' => '',
        //     'type' => '',
        //     'total_amount' => $grandTotal,
        //     'memo' => '',
        //     'isGrandTotal' => true,
        // ]);

        return datatables()
            ->collection($finalData)
            ->addColumn('transaction', function ($row) {
                if (isset($row->isSubtotal) || isset($row->isGrandTotal) || isset($row->isPlaceholder)) {
                    return $row->transaction ?? '';
                }

                return \Auth::user()->invoiceNumberFormat($row->invoice ?? ($row->id ?? ''));
            })
            ->editColumn('transaction', function ($row) {
                return $row->transaction ?? '';
            })


            ->addColumn('due_date', fn($row) => $row->due_date ?? '')
            // ->addColumn('past_due', fn($row) => $row->past_due ?? '')
            ->editColumn(
                'type',
                fn($row) =>
                (isset($row->isPlaceholder) || isset($row->isSubtotal) || isset($row->isGrandTotal))
                ? ''
                : $row->type
            )
            ->addColumn('issue_date', fn($row) => $row->issue_date ?? '')
            ->editColumn('total_amount', function ($row) {
                if (isset($row->isPlaceholder)) {
                    return '';
                }
                return number_format($row->total_amount ?? 0, 2);
            })

            ->addColumn('open_balance', function ($row) {
                if (isset($row->isPlaceholder)) {
                    return '';
                }
                if (isset($row->isSubtotal) || isset($row->isGrandTotal)) {
                    return number_format($row->balance_due ?? 0);
                }
                return number_format($row->balance_due ?? 0);
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

    public function query(Invoice $model)
    {
        $start = request()->get('start_date')
            ?? request()->get('startDate')
            ?? Carbon::now()->startOfMonth()->format('Y-m-d');

        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');

        $creatorId = \Auth::user()->creatorId();

        // 🔹 Get payments within date range first, then get linked invoices
        $payments = DB::table('invoice_payments')
            ->join('invoices', 'invoices.id', '=', 'invoice_payments.invoice_id')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->where('invoices.created_by', $creatorId)
            ->whereBetween('invoice_payments.date', [$start, $end])
            ->select(
                'invoice_payments.id as payment_id',
                'invoice_payments.invoice_id',
                'invoice_payments.date as payment_date',
                'invoice_payments.amount as payment_amount',
                'invoice_payments.description as payment_description',
                'invoices.id as inv_id',
                'invoices.invoice_id as invoice_number',
                'invoices.issue_date',
                'invoices.ref_number',
                'customers.name'
            )
            ->orderBy('invoice_payments.date', 'asc')
            ->orderBy('invoice_payments.id', 'asc')
            ->get();

        // Group payments by invoice
        $groupedByInvoice = $payments->groupBy('invoice_id');

        // Build result collection
        $result = collect();

        foreach ($groupedByInvoice as $invoiceId => $invoicePayments) {
            $firstPayment = $invoicePayments->first();
            $customerName = $firstPayment->name;

            // Get invoice details with totals
            $invoice = DB::table('invoices')
                ->leftJoin('invoice_products', 'invoice_products.invoice_id', '=', 'invoices.id')
                ->where('invoices.id', $invoiceId)
                ->select(
                    'invoices.id',
                    'invoices.invoice_id as invoice',
                    'invoices.issue_date',
                    'invoices.ref_number',
                    DB::raw('SUM((invoice_products.price * invoice_products.quantity) - invoice_products.discount) as subtotal'),
                    DB::raw('(SELECT IFNULL(SUM((price * quantity - discount) * (taxes.rate / 100)),0) 
                      FROM invoice_products 
                      LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
                      WHERE invoice_products.invoice_id = invoices.id) as total_tax')
                )
                ->groupBy('invoices.id')
                ->first();

            // Build payments collection for this invoice
            $paymentsCollection = collect();
            foreach ($invoicePayments as $pay) {
                // Get linked credit note amount for this payment
                $linkedCreditAmount = DB::table('credit_notes')
                    ->where('payment_id', $pay->payment_id)
                    ->sum('amount') ?? 0;

                // Get customer credit (overpayment) from transactions table
                // Step 1: Get payment_no from transactions table using payment_id
                $paymentNo = DB::table('transactions')
                    ->where('payment_id', $pay->payment_id)
                    ->where('category', 'Invoice')
                    ->value('payment_no');
                
                // Step 2: Search transactions again for Customer Credit entries with that payment_no
                $customerCreditAmount = 0;
                if ($paymentNo) {
                    $customerCreditAmount = DB::table('transactions')
                        ->where('payment_no', $paymentNo)
                        ->where('category', 'Customer Credit')
                        ->sum('amount') ?? 0;
                }

                // Actual payment = payment amount - linked credit amount - customer credit (overpayment)
                $actualPaymentAmount = $pay->payment_amount - $linkedCreditAmount + $customerCreditAmount;

                $paymentsCollection->push((object) [
                    'id' => $pay->payment_id,
                    'issue_date' => $pay->payment_date,
                    'transaction' => "Payment #{$pay->payment_id}",
                    'type' => 'Payment',
                    'total_amount' => $actualPaymentAmount,
                    'memo' => $pay->payment_description,
                    'customer' => $customerName
                ]);
            }

            // Get linked credit notes for this invoice's payments (not open/draft)
            $linkedCreditNotes = DB::table('credit_notes')
                ->join('invoice_payments', 'invoice_payments.id', '=', 'credit_notes.payment_id')
                ->where('invoice_payments.invoice_id', $invoiceId)
                ->whereNotNull('credit_notes.payment_id')
                ->where('credit_notes.payment_id', '>', 0)
                ->whereBetween('credit_notes.date', [$start, $end])
                ->select(
                    'credit_notes.id',
                    'credit_notes.credit_note_id',
                    'credit_notes.date',
                    'credit_notes.amount',
                    'credit_notes.description'
                )
                ->get()
                ->map(function ($credit) use ($customerName) {
                    return (object) [
                        'id' => $credit->id,
                        'issue_date' => $credit->date,
                        'transaction' => "Credit Memo #" . ($credit->credit_note_id ?? $credit->id),
                        'type' => 'Credit Memo',
                        'total_amount' => -$credit->amount,
                        'memo' => $credit->description,
                        'customer' => $customerName
                    ];
                });

            // Merge payments and linked credit notes
            $paymentsCollection = $paymentsCollection->merge($linkedCreditNotes);

            $result->push((object) [
                'id' => $invoice->id ?? $invoiceId,
                'name' => $customerName,
                'issue_date' => $invoice->issue_date ?? '',
                'transaction' => \Auth::user()->invoiceNumberFormat($invoice->invoice ?? $invoiceId),
                'type' => 'Invoice',
                'total_amount' => ($invoice->subtotal ?? 0) + ($invoice->total_tax ?? 0),
                'memo' => $invoice->ref_number ?? '',
                'payments' => $paymentsCollection,
            ]);
        }

        return $result;
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
            Column::make('memo')->title('Memo/Description'),
            Column::make('type')->title('Type'),
            Column::make('total_amount')->title('Amount'),
        ];
    }

}