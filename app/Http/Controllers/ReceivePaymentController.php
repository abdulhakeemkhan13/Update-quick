<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Transaction;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceivePaymentController extends Controller
{
    /**
     * Display a listing of received payments.
     */
    public function index()
    {
        if (Auth::user()->can('create payment invoice')) {
            $user = Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            
            // Get all invoice payments with relationships
            $payments = InvoicePayment::with(['invoice.customer', 'bankAccount'])
                ->whereHas('invoice', function ($query) use ($ownerId, $user) {
                    $column = $user->type == 'company' ? 'created_by' : 'owned_by';
                    $query->where($column, $ownerId);
                })
                ->orderBy('created_at', 'desc')
                ->get();
            
            return view('receive-payment.index', compact('payments'));
        }
        
        return redirect()->back()->with('error', __('Permission denied.'));
    }

    /**
     * Show the form for creating a new payment.
     */
    public function create($customerId = null)
    {
        if (Auth::user()->can('create payment invoice')) {
            $user = Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = $user->type == 'company' ? 'created_by' : 'owned_by';

            // Get customers
            $customers = Customer::where($column, $ownerId)->get()->pluck('name', 'id')->toArray();
            $customers = ['' => 'Select Customer'] + $customers;

            // Get bank accounts for deposit
            $bankAccounts = BankAccount::where('created_by', Auth::user()->creatorId())
                ->get()
                ->mapWithKeys(function ($account) {
                    $displayName = !empty($account->bank_name) ? $account->bank_name :
                        (!empty($account->institution_name) ? $account->institution_name : $account->holder_name);
                    return [$account->id => $displayName];
                });
            $bankAccounts = ['' => 'Undeposited Funds'] + $bankAccounts->toArray();

            // Get outstanding invoices if customer is selected
            $outstandingInvoices = collect();
            $customerBalance = 0;
            $selectedCustomer = null;
            $preSelectedInvoice = null;

            if ($customerId && $customerId != 0) {
                $selectedCustomer = Customer::find($customerId);
                if ($selectedCustomer) {
                    $customerBalance = $selectedCustomer->balance ?? 0;
                    $outstandingInvoices = Invoice::where('customer_id', $customerId)
                        ->where($column, $ownerId)
                        ->whereIn('status', [1, 3, 6]) // Sent, Partial, Approved
                        ->where(function ($query) {
                            $query->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM invoice_payments WHERE invoice_payments.invoice_id = invoices.id) < invoices.total_amount');
                        })
                        ->orderBy('due_date', 'asc')
                        ->get();

                    // Check if there's a pre-selected invoice from query parameter
                    $invoiceId = request('invoice_id');
                    if ($invoiceId) {
                        $preSelectedInvoice = Invoice::where('id', $invoiceId)
                            ->where('customer_id', $customerId)
                            ->where($column, $ownerId)
                            ->whereIn('status', [1, 3, 6])
                            ->where(function ($query) {
                                $query->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM invoice_payments WHERE invoice_payments.invoice_id = invoices.id) < invoices.total_amount');
                            })
                            ->first();
                    }
                }
            }

            return view('receive-payment.create', compact(
                'customers',
                'bankAccounts',
                'outstandingInvoices',
                'customerBalance',
                'selectedCustomer',
                'customerId',
                'preSelectedInvoice'
            ));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    /**
     * Get outstanding invoices for a customer via AJAX
     */
    public function getOutstandingInvoices(Request $request)
    {
        $user = Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = $user->type == 'company' ? 'created_by' : 'owned_by';

        $customerId = $request->customer_id;
        $invoiceNo = $request->invoice_no;

        // If searching by invoice number
        if ($invoiceNo) {
            $invoice = Invoice::where($column, $ownerId)
                ->where(function($q) use ($invoiceNo) {
                    $q->where('invoice_id', $invoiceNo)
                      ->orWhere('invoice_id', 'LIKE', '%' . $invoiceNo . '%');
                })
                ->whereIn('status', [1, 3, 6])
                ->first();

            if ($invoice && $invoice->getDue() > 0) {
                $customer = Customer::find($invoice->customer_id);
                $customerBalance = $customer ? ($customer->balance ?? 0) : 0;

                return response()->json([
                    'invoices' => [[
                        'id' => $invoice->id,
                        'invoice_id' => $user->invoiceNumberFormat($invoice->invoice_id),
                        'issue_date' => $user->dateFormat($invoice->issue_date),
                        'due_date' => $user->dateFormat($invoice->due_date),
                        'total' => $invoice->getTotal(),
                        'total_formatted' => $user->priceFormat($invoice->getTotal()),
                        'due' => $invoice->getDue(),
                        'due_formatted' => $user->priceFormat($invoice->getDue()),
                    ]],
                    'customer_id' => $invoice->customer_id,
                    'customer_balance' => $customerBalance,
                    'customer_balance_formatted' => $user->priceFormat($customerBalance),
                ]);
            }

            return response()->json([
                'invoices' => [],
                'customer_id' => null,
                'customer_balance' => 0,
                'customer_balance_formatted' => $user->priceFormat(0),
            ]);
        }

        // Normal customer-based lookup
        $customer = Customer::find($customerId);
        $customerBalance = $customer ? ($customer->balance ?? 0) : 0;

        $invoices = Invoice::where('customer_id', $customerId)
            ->where($column, $ownerId)
            ->whereIn('status', [1, 3, 6]) // Sent, Partial, Approved
            ->orderBy('due_date', 'asc')
            ->get()
            ->filter(function ($invoice) {
                return $invoice->getDue() > 0;
            })
            ->map(function ($invoice) use ($user) {
                return [
                    'id' => $invoice->id,
                    'invoice_id' => $user->invoiceNumberFormat($invoice->invoice_id),
                    'issue_date' => $user->dateFormat($invoice->issue_date),
                    'due_date' => $user->dateFormat($invoice->due_date),
                    'total' => $invoice->getTotal(),
                    'total_formatted' => $user->priceFormat($invoice->getTotal()),
                    'due' => $invoice->getDue(),
                    'due_formatted' => $user->priceFormat($invoice->getDue()),
                ];
            });

        return response()->json([
            'invoices' => $invoices->values(),
            'customer_balance' => $customerBalance,
            'customer_balance_formatted' => $user->priceFormat($customerBalance),
        ]);
    }

    /**
     * Store a newly created payment in storage.
     */
    public function store(Request $request)
    {
        if (!Auth::user()->can('create payment invoice')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = \Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'payment_date' => 'required|date',
            'amount_received' => 'required|numeric|min:0.01',
            'deposit_to' => 'nullable|exists:bank_accounts,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        \DB::beginTransaction();
        try {
            $payments = $request->input('payments', []);
            $totalApplied = 0;
            $customer = Customer::find($request->customer_id);

            foreach ($payments as $invoiceId => $amount) {
                if ($amount > 0) {
                    $invoice = Invoice::find($invoiceId);
                    if (!$invoice) continue;

                    $due = $invoice->getDue();
                    $paymentAmount = min($amount, $due);
                    $totalApplied += $paymentAmount;

                    // Create invoice payment
                    $invoicePayment = new InvoicePayment();
                    $invoicePayment->invoice_id = $invoiceId;
                    $invoicePayment->date = $request->payment_date;
                    $invoicePayment->amount = $paymentAmount;
                    $invoicePayment->account_id = $request->deposit_to;
                    $invoicePayment->payment_method = 0;
                    $invoicePayment->reference = $request->reference_no;
                    $invoicePayment->description = $request->memo;
                    $invoicePayment->save();

                    // Update invoice status
                    $newDue = $invoice->getDue();
                    if ($newDue <= 0) {
                        $invoice->status = 4; // Paid
                    } else {
                        $invoice->status = 3; // Partial
                    }
                    if ($invoice->status == 0) {
                        $invoice->send_date = date('Y-m-d');
                    }
                    $invoice->save();

                    // Add transaction
                    $invoicePayment->user_id = $invoice->customer_id;
                    $invoicePayment->user_type = 'Customer';
                    $invoicePayment->type = 'Partial';
                    $invoicePayment->created_by = Auth::user()->id;
                    $invoicePayment->owned_by = Auth::user()->ownedId();
                    $invoicePayment->payment_id = $invoicePayment->id;
                    $invoicePayment->category = 'Invoice';
                    $invoicePayment->account = $request->deposit_to;
                    $invoicePayment->payment_no = $paymentNo;

                    Transaction::addTransaction($invoicePayment);
                    // Update customer balance
                    Utility::updateUserBalance('customer', $invoice->customer_id, $paymentAmount, 'credit');

                    // Update bank account balance
                    if ($request->deposit_to) {
                        Utility::bankAccountBalance($request->deposit_to, $paymentAmount, 'credit');

                        // Create voucher entry
                        $bankAccount = BankAccount::find($request->deposit_to);
                        if ($bankAccount && $bankAccount->chart_account_id) {
                            $data = [
                                'id' => $invoiceId,
                                'no' => $invoice->invoice_id,
                                'date' => $invoicePayment->date,
                                'reference' => $invoicePayment->reference,
                                'description' => $invoicePayment->description,
                                'amount' => $paymentAmount,
                                'prod_id' => $invoicePayment->id,
                                'category' => 'Invoice',
                                'owned_by' => Auth::user()->ownedId(),
                                'created_by' => Auth::user()->creatorId(),
                                'created_at' => date('Y-m-d H:i:s', strtotime($invoicePayment->date)),
                                'account_id' => $bankAccount->chart_account_id,
                            ];

                            if (preg_match('/\bcash\b/i', $bankAccount->bank_name) ||
                                preg_match('/\bcash\b/i', $bankAccount->holder_name)) {
                                $voucherId = Utility::crv_entry($data);
                            } else {
                                $voucherId = Utility::brv_entry($data);
                            }

                            $invoicePayment->voucher_id = $voucherId;
                            $invoicePayment->save();
                        }
                    }
                }
            }

            \DB::commit();
            return redirect()->route('receive-payment.index')
                ->with('success', __('Payment recorded successfully.'));
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->back()->with('error', __('Error: ') . $e->getMessage());
        }
    }

    /**
     * Display the specified payment.
     */
    public function show($id)
    {
        $payment = InvoicePayment::with(['invoice.customer', 'bankAccount'])->find($id);
        if (!$payment) {
            return redirect()->back()->with('error', __('Payment not found.'));
        }
        return view('receive-payment.show', compact('payment'));
    }

    /**
     * Create payment for invoice - Payment Voucher scenario
     */
    public function createPayment(Request $request, $invoice_id = null)
    {
        \Log::info('createPayment started', ['request' => $request->all()]);
        \DB::beginTransaction();
        try {
            if (Auth::user()->can('create payment invoice')) {
                // Generate unique payment number for grouping transactions
                $paymentNo = $this->paymentNumber();

                $paymentType = $request->input('payment_type', 'record_payment'); // record_payment, charge, or credit

                // Handle bulk payments from receive-payment form
                $payments = $request->input('payments', []);
                $totalAmountReceived = $request->input('amount_received', 0);
                $totalPaymentsApplied = !empty($payments) ? array_sum($payments) : 0;
                $creditAmount = $totalAmountReceived - $totalPaymentsApplied; // Excess amount to credit to customer

                // Validation based on payment type
                $validatorRules = [
                    'payment_date' => 'required|date',
                    'customer_id' => 'required|exists:customers,id',
                ];

                // Add amount validation based on whether it's bulk or single payment
                if (!empty($payments)) {
                    // Bulk payment - amount_received can be more than sum of payments (excess goes to customer credit)
                    $validatorRules['amount_received'] = 'required|numeric|min:0.01';
                } elseif ($invoice_id) {
                    // Single invoice payment
                    $validatorRules['amount'] = 'required|numeric|min:0.01';
                    $totalAmountReceived = $request->input('amount', 0);
                    $totalPaymentsApplied = $totalAmountReceived;
                    $creditAmount = 0; // No credit for single payments
                } else {
                    // Credit payment without invoice
                    $validatorRules['amount_received'] = 'required|numeric|min:0.01';
                    $creditAmount = $totalAmountReceived;
                }

                if ($paymentType === 'record_payment') {
                    $validatorRules['payment_method'] = 'required';
                    $validatorRules['deposit_to'] = 'required|exists:bank_accounts,id';
                } elseif ($paymentType === 'charge') {
                    // No additional validation for charge payments - no payment method or deposit to
                } elseif ($paymentType === 'credit') {
                    // Only customer and amount required for credit payments - no payment method or deposit to
                } else {
                    return redirect()->back()->with('error', __('Invalid payment type selected.'));
                }

                // Validate invoices exist for bulk payments (no amount validation - overpayments allowed)
                if (!empty($payments)) {
                    foreach ($payments as $invId => $amount) {
                        if ($amount > 0) {
                            $invoice = Invoice::find($invId);
                            if (!$invoice) {
                                return redirect()->back()->with('error', __('Invoice not found: ') . $invId);
                            }
                        }
                    }
                }

                // If single invoice payment, validate invoice exists (no amount validation - overpayments allowed)
                if ($invoice_id && empty($payments)) {
                    $invoice = Invoice::find($invoice_id);
                    if (!$invoice) {
                        return redirect()->back()->with('error', __('Invoice not found.'));
                    }
                }

                // Explicit validation for record_payment requirements
                if ($paymentType === 'record_payment') {
                    if (!$request->has('payment_method') || empty($request->payment_method)) {
                        return redirect()->back()->with('error', __('Payment method is required when recording a payment.'));
                    }
                    if (!$request->has('deposit_to') || empty($request->deposit_to)) {
                        return redirect()->back()->with('error', __('Deposit to account is required when recording a payment.'));
                    }
                }

                $validator = \Validator::make($request->all(), $validatorRules);
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    // Provide specific error messages for record_payment requirements
                    if ($paymentType === 'record_payment') {
                        if ($messages->has('payment_method')) {
                            return redirect()->back()->with('error', __('Payment method is required when recording a payment.'));
                        }
                        if ($messages->has('deposit_to')) {
                            return redirect()->back()->with('error', __('Deposit to account is required when recording a payment.'));
                        }
                    }

                    return redirect()->back()->with('error', $messages->first());
                }

                // Handle different payment types
                if ($paymentType === 'credit') {
                    // Payment without invoice - just credit to customer balance
                    Utility::updateUserBalance('customer', $request->customer_id, $totalAmountReceived, 'credit');

                    // Create transaction record for credit payment
                    $transaction = new Transaction();
                    $transaction->user_id = $request->customer_id;
                    $transaction->user_type = 'Customer';
                    $transaction->type = 'credit';
                    $transaction->amount = $totalAmountReceived;
                    $transaction->date = $request->payment_date;
                    $transaction->created_by = Auth::user()->id;
                    $transaction->payment_id = 0; // No payment ID for credit
                    $transaction->category = 'Customer Credit';
                    $transaction->description = $request->memo ?? 'Customer credit payment';
                    $transaction->payment_no = $paymentNo;
                    $transaction->save();

                    Utility::makeActivityLog(Auth::user()->id, 'Customer Credit', $request->customer_id, 'Customer Credit Added', 'Amount: ' . $totalAmountReceived);

                    \DB::commit();
                    return redirect()->back()->with('success', __('Customer credit added successfully.'));

                } elseif ($paymentType === 'charge') {
                    // Charge payment - no reference, no payment method, just update customer balance
                    Utility::updateUserBalance('customer', $request->customer_id, $totalAmountReceived, 'debit');

                    // Create transaction record for charge payment
                    $transaction = new Transaction();
                    $transaction->user_id = $request->customer_id;
                    $transaction->user_type = 'Customer';
                    $transaction->type = 'debit';
                    $transaction->amount = $totalAmountReceived;
                    $transaction->date = $request->payment_date;
                    $transaction->created_by = Auth::user()->id;
                    $transaction->payment_id = 0; // No payment ID for charge
                    $transaction->category = 'Customer Charge';
                    $transaction->description = $request->memo ?? 'Customer charge payment';
                    $transaction->payment_no = $paymentNo;
                    $transaction->save();

                    Utility::makeActivityLog(Auth::user()->id, 'Customer Charge', $request->customer_id, 'Customer Charge Added', 'Amount: ' . $totalAmountReceived);

                    \DB::commit();
                    return redirect()->back()->with('success', __('Customer charge processed successfully.'));

                } else {
                    // Record payment - full invoice payment processing (bulk or single)
                    $totalApplied = 0;

                    if (!empty($payments)) {
                        // Bulk payment processing - QBO style
                        // Step 1: Calculate total applied and update invoice statuses
                        $firstInvoiceId = null;
                        $firstInvoice = null;
                        $customerId = null;
                        
                        foreach ($payments as $invId => $amount) {
                            if ($amount > 0) {
                                $invoice = Invoice::find($invId);
                                if (!$invoice) continue;
                                
                                // Track first invoice for the combined payment entry
                                if ($firstInvoiceId === null) {
                                    $firstInvoiceId = $invId;
                                    $firstInvoice = $invoice;
                                    $customerId = $invoice->customer_id;
                                }
                                
                                // Update invoice status
                                $due = $invoice->getDue();
                                if ($invoice->status == 0) {
                                    $invoice->send_date = date('Y-m-d');
                                }
                                
                                if ($due <= $amount) {
                                    $invoice->status = 4; // Paid
                                } else {
                                    $invoice->status = 3; // Partial
                                }
                                $invoice->save();
                                
                                // Update customer balance (credit the payment amount)
                                Utility::updateUserBalance('customer', $invoice->customer_id, $amount, 'credit');
                                
                                $totalApplied += $amount;
                            }
                        }
                        
                        // Step 2: Create ONE invoice_payment entry with total applied amount
                        if ($totalApplied > 0 && $firstInvoiceId) {
                            $invoicePayment = new InvoicePayment();
                            $invoicePayment->invoice_id = $firstInvoiceId;
                            $invoicePayment->date = $request->payment_date;
                            $invoicePayment->amount = $totalApplied;
                            $invoicePayment->account_id = $request->deposit_to;
                            $invoicePayment->payment_method = $request->payment_method;
                            $invoicePayment->reference = $request->reference_no ?? null;
                            $invoicePayment->description = $request->memo;
                            $invoicePayment->txn_id = $paymentNo; // Store payment_no for reference
                            $invoicePayment->save();
                            
                            // Step 3: Create Invoice transaction for applied amount
                            $invoicePayment->user_id = $customerId;
                            $invoicePayment->user_type = 'Customer';
                            $invoicePayment->type = 'Payment';
                            $invoicePayment->created_by = Auth::user()->id;
                            $invoicePayment->owned_by = Auth::user()->ownedId();
                            $invoicePayment->payment_id = $invoicePayment->id;
                            $invoicePayment->category = 'Invoice';
                            $invoicePayment->account = $request->deposit_to;
                            $invoicePayment->payment_no = $paymentNo;
                            
                            Transaction::addTransaction($invoicePayment);
                            
                            // Set customer variable for later use
                            $customer = Customer::find($customerId);
                        }
                    } else {
                        // Single invoice payment processing
                        $paymentAmount = $request->input('amount', 0);
                        $invoicePayment = new InvoicePayment();
                        $invoicePayment->invoice_id = $invoice_id;
                        $invoicePayment->date = $request->payment_date;
                        $invoicePayment->amount = $paymentAmount;
                        $invoicePayment->account_id = $request->deposit_to;
                        $invoicePayment->payment_method = $request->payment_method;
                        $invoicePayment->reference = $request->reference ?? null;
                        $invoicePayment->description = $request->description;
                        $invoicePayment->txn_id = $paymentNo; // Store payment_no for reference

                        if (!empty($request->add_receipt)) {
                            $image_size = $request->file('add_receipt')->getSize();
                            $result = Utility::updateStorageLimit(Auth::user()->creatorId(), $image_size);
                            if ($result == 1) {
                                $fileName = time() . '_' . $request->add_receipt->getClientOriginalName();
                                $request->add_receipt->storeAs('uploads/payment', $fileName);
                                $invoicePayment->add_receipt = $fileName;
                            }
                        }

                        $invoicePayment->save();

                        $invoice = Invoice::where('id', $invoice_id)->first();
                        $due = $invoice->getDue();

                        if ($invoice->status == 0) {
                            $invoice->send_date = date('Y-m-d');
                            $invoice->save();
                        }

                        if ($due <= $paymentAmount) {
                            $invoice->status = 4; // Paid
                            $invoice->save();
                        } else {
                            $invoice->status = 3; // Partial
                            $invoice->save();
                        }

                        $invoicePayment->user_id = $invoice->customer_id;
                        $invoicePayment->user_type = 'Customer';
                        $invoicePayment->type = 'Partial';
                        $invoicePayment->created_by = Auth::user()->id;
                        $invoicePayment->owned_by = Auth::user()->ownedId();
                        $invoicePayment->payment_id = $invoicePayment->id;
                        $invoicePayment->category = 'Invoice';
                        $invoicePayment->account = $request->deposit_to;
                        $invoicePayment->payment_no = $paymentNo;

                        Transaction::addTransaction($invoicePayment);
                        $customer = Customer::where('id', $invoice->customer_id)->first();

                        // Update customer balance (credit the payment amount)
                        Utility::updateUserBalance('customer', $invoice->customer_id, $paymentAmount, 'credit');

                        $totalApplied = $paymentAmount;
                    }

                    // Update bank account balance (credit the total amount received)
                    Utility::bankAccountBalance($request->deposit_to, $totalAmountReceived, 'credit');

                    // Handle credit amount if any (amount_received > sum of payments)
                    if ($creditAmount > 0) {
                        // Credit excess amount to customer balance
                        Utility::updateUserBalance('customer', $request->customer_id, $creditAmount, 'credit');

                        // Create transaction record for the credit amount
                        $creditTransaction = new Transaction();
                        $creditTransaction->user_id = $request->customer_id;
                        $creditTransaction->user_type = 'Customer';
                        $creditTransaction->type = 'credit';
                        $creditTransaction->amount = $creditAmount;
                        $creditTransaction->date = $request->payment_date;
                        $creditTransaction->created_by = Auth::user()->id;
                        // Use same payment_id as the Invoice transaction for grouping
                        $creditTransaction->payment_id = isset($invoicePayment) ? $invoicePayment->id : 0;
                        $creditTransaction->category = 'Customer Credit';
                        $creditTransaction->description = $request->memo ?? 'Excess payment credit';
                        $creditTransaction->payment_no = $paymentNo;
                        $creditTransaction->save();

                        Utility::makeActivityLog(Auth::user()->id, 'Customer Credit', $request->customer_id, 'Excess Payment Credit', 'Amount: ' . $creditAmount);
                    }

                    $bankAccount = BankAccount::find($request->deposit_to);
                    if ($bankAccount && ($bankAccount->chart_account_id != 0 || $bankAccount->chart_account_id != null)) {
                        $data = [
                            'id' => !empty($payments) ? array_key_first($payments) : $invoice_id, // Use first invoice ID for bulk payments
                            'no' => !empty($payments) ? ($firstInvoice ? $firstInvoice->invoice_id : 'BULK-' . date('YmdHis')) : ($invoice ? $invoice->invoice_id : 'PAYMENT-' . date('YmdHis')),
                            'date' => $request->payment_date,
                            'reference' => $request->reference_no,
                            'description' => $request->memo,
                            'amount' => $totalAmountReceived, // Total amount received (including credit)
                            'prod_id' => 0, // Bulk payment, no single product ID
                            'category' => 'Invoice',
                            'owned_by' => Auth::user()->ownedId(),
                            'created_by' => Auth::user()->creatorId(),
                            'created_at' => date('Y-m-d H:i:s', strtotime($request->payment_date)),
                            'account_id' => $bankAccount->chart_account_id,
                        ];

                        if (preg_match('/\bcash\b/i', $bankAccount->bank_name) || preg_match('/\bcash\b/i', $bankAccount->holder_name)) {
                            $voucherId = Utility::crv_entry($data);
                        } else {
                            $voucherId = Utility::brv_entry($data);
                        }

                        // Update voucher_id for the invoice_payment entry using direct query
                        // (avoiding model save() which would try to save transaction properties)
                        if (isset($invoicePayment) && $invoicePayment->id) {
                            InvoicePayment::where('id', $invoicePayment->id)->update([
                                'voucher_id' => $voucherId,
                            ]);
                        }
                    } else {
                        return redirect()->back()->with('error', __('Please select chart of account in bank account.'));
                    }

                    // Send Email notifications for each payment
                    $setings = Utility::settings();
                    if (isset($setings['new_invoice_payment']) && $setings['new_invoice_payment'] == 1) {
                        $customer = $customer ?? Customer::find($request->customer_id);
                        if ($customer) {
                            $invoicePaymentArr = [
                                'invoice_payment_name' => $customer->name,
                                'invoice_payment_amount' => Auth::user()->priceFormat($totalApplied),
                                'invoice_payment_date' => Auth::user()->dateFormat($request->payment_date),
                                'payment_dueAmount' => Auth::user()->priceFormat($customer->balance ?? 0),
                            ];

                            $resp = Utility::sendEmailTemplate('new_invoice_payment', [$customer->id => $customer->email], $invoicePaymentArr);
                        }
                    }

                    //webhook
                    $module = 'New Invoice Payment';
                    $webhook = Utility::webhookSetting($module);
                    if ($webhook) {
                        $parameter = json_encode(['payments' => $payments, 'total' => $totalApplied, 'customer_id' => $request->customer_id]);
                        $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                        if ($status != true) {
                            \DB::commit();
                            return redirect()->back()->with('error', __('Webhook call failed.'));
                        }
                    }

                    //activity log
                    $customer = $customer ?? Customer::find($request->customer_id);
                    Utility::makeActivityLog(Auth::user()->id, 'Invoice Payment', 0, 'Create Bulk Invoice Payment', 'Customer: ' . ($customer ? $customer->name : 'Unknown') . ', Total Amount: ' . $totalApplied);

                    \DB::commit();
                    return redirect()->back()->with('success', __('Payment successfully recorded.'));
                }
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }
    public function paymentNumber()
    {
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = $user->type == 'company' ? 'created_by' : 'owned_by';
        
        // Get the latest payment_no with 'pay-' prefix to avoid conflicts with QBO imports
        $latest = Transaction::where($column, '=', $ownerId)
            ->where('payment_no', 'LIKE', 'pay-%')
            ->latest()
            ->first();
        
        if (!$latest) {
            return 'pay-1';
        }

        // Extract numeric part from 'pay-XXXX' format
        $numericPart = (int) str_replace('pay-', '', $latest->payment_no);
        return 'pay-' . ($numericPart + 1);
    }
    /**
     * Remove the specified payment from storage.
     */
    public function destroy($id)
    {
        if (!Auth::user()->can('delete payment invoice')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $payment = InvoicePayment::find($id);
        if (!$payment) {
            return redirect()->back()->with('error', __('Payment not found.'));
        }

        \DB::beginTransaction();
        try {
            $invoice = Invoice::find($payment->invoice_id);

            // Reverse bank account balance
            if ($payment->account_id) {
                Utility::bankAccountBalance($payment->account_id, $payment->amount, 'debit');
            }

            // Reverse customer balance
            if ($invoice) {
                Utility::updateUserBalance('customer', $invoice->customer_id, $payment->amount, 'debit');

                // Update invoice status
                $invoice->status = 3; // Partial or check if fully reversed
                $invoice->save();
            }

            // Delete transaction
            Transaction::where('payment_id', $payment->id)
                ->where('category', 'Invoice')
                ->delete();

            $payment->delete();

            \DB::commit();
            return redirect()->route('receive-payment.index')
                ->with('success', __('Payment deleted successfully.'));
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->back()->with('error', __('Error: ') . $e->getMessage());
        }
    }
}


