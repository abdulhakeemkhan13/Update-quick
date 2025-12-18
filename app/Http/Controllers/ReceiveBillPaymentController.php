<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Transaction;
use App\Models\Utility;
use App\Models\Vender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceiveBillPaymentController extends Controller
{
    /**
     * Display a listing of bill payments.
     */
    public function index()
    {
        if (Auth::user()->can('manage bill')) {
            $user = Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            
            // Get all bill payments with relationships
            $payments = BillPayment::with(['bill.vender', 'bankAccount'])
                ->whereHas('bill', function ($query) use ($ownerId, $user) {
                    $column = $user->type == 'company' ? 'created_by' : 'owned_by';
                    $query->where($column, $ownerId);
                })
                ->orderBy('created_at', 'desc')
                ->get();
            
            return view('receive-bill-payments.index', compact('payments'));
        }
        
        return redirect()->back()->with('error', __('Permission denied.'));
    }

    /**
     * Show the form for creating a new bill payment.
     */
    public function create($vendorId = null)
    {
        if (Auth::user()->can('manage bill')) {
            $user = Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = $user->type == 'company' ? 'created_by' : 'owned_by';

            // Get vendors
            $vendors = Vender::where($column, $ownerId)->get()->pluck('name', 'id')->toArray();
            $vendors = ['' => 'Select Vendor'] + $vendors;

            // Get bank accounts for payment
            $bankAccounts = BankAccount::where('created_by', Auth::user()->creatorId())
                ->get()
                ->mapWithKeys(function ($account) {
                    $displayName = !empty($account->bank_name) ? $account->bank_name :
                        (!empty($account->institution_name) ? $account->institution_name : $account->holder_name);
                    return [$account->id => $displayName];
                });
            $bankAccounts = ['' => 'Select Bank Account'] + $bankAccounts->toArray();

            // Get outstanding bills if vendor is selected
            $outstandingBills = collect();
            $vendorBalance = 0;
            $selectedVendor = null;
            $preSelectedBill = null;

            if ($vendorId && $vendorId != 0) {
                $selectedVendor = Vender::find($vendorId);
                if ($selectedVendor) {
                    $vendorBalance = $selectedVendor->getDueAmount() ?? 0;
                    $outstandingBills = Bill::where('vender_id', $vendorId)
                        ->where($column, $ownerId)
                        ->where('type', 'Bill')
                        ->whereIn('status', [1, 2, 3, 6]) // Sent, Unpaid, Partial, Approved
                        ->where(function ($query) {
                            $query->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM bill_payments WHERE bill_payments.bill_id = bills.id) < bills.total');
                        })
                        ->orderBy('due_date', 'asc')
                        ->get();

                    // Check if there's a pre-selected bill from query parameter
                    $billId = request('bill_id');
                    if ($billId) {
                        $preSelectedBill = Bill::where('id', $billId)
                            ->where('vender_id', $vendorId)
                            ->where($column, $ownerId)
                            ->whereIn('status', [1, 2, 3, 6])
                            ->where(function ($query) {
                                $query->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM bill_payments WHERE bill_payments.bill_id = bills.id) < bills.total');
                            })
                            ->first();
                    }
                }
            }

            return view('receive-bill-payments.create', compact(
                'vendors',
                'bankAccounts',
                'outstandingBills',
                'vendorBalance',
                'selectedVendor',
                'vendorId',
                'preSelectedBill'
            ));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    /**
     * Get outstanding bills for a vendor via AJAX
     */
    public function getOutstandingBills(Request $request)
    {
        $user = Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = $user->type == 'company' ? 'created_by' : 'owned_by';

        $vendorId = $request->vendor_id;
        $billNo = $request->bill_no;

        // If searching by bill number
        if ($billNo) {
            $bill = Bill::where($column, $ownerId)
                ->where('type', 'Bill')
                ->where(function($q) use ($billNo) {
                    $q->where('bill_id', $billNo)
                      ->orWhere('bill_id', 'LIKE', '%' . $billNo . '%');
                })
                ->whereIn('status', [1, 2, 3, 6])
                ->first();

            if ($bill && $bill->getDue() > 0) {
                $vendor = Vender::find($bill->vender_id);
                $vendorBalance = $vendor ? ($vendor->getDueAmount() ?? 0) : 0;

                return response()->json([
                    'bills' => [[
                        'id' => $bill->id,
                        'bill_id' => $user->billNumberFormat($bill->bill_id),
                        'bill_date' => $user->dateFormat($bill->bill_date),
                        'due_date' => $user->dateFormat($bill->due_date),
                        'total' => $bill->getTotal(),
                        'total_formatted' => $user->priceFormat($bill->getTotal()),
                        'due' => $bill->getDue(),
                        'due_formatted' => $user->priceFormat($bill->getDue()),
                    ]],
                    'vendor_id' => $bill->vender_id,
                    'vendor_balance' => $vendorBalance,
                    'vendor_balance_formatted' => $user->priceFormat($vendorBalance),
                ]);
            }

            return response()->json([
                'bills' => [],
                'vendor_id' => null,
                'vendor_balance' => 0,
                'vendor_balance_formatted' => $user->priceFormat(0),
            ]);
        }

        // Normal vendor-based lookup
        $vendor = Vender::find($vendorId);
        $vendorBalance = $vendor ? ($vendor->getDueAmount() ?? 0) : 0;

        $bills = Bill::where('vender_id', $vendorId)
            ->where($column, $ownerId)
            ->where('type', 'Bill')
            ->whereIn('status', [1, 2, 3, 6]) // Sent, Unpaid, Partial, Approved
            ->orderBy('due_date', 'asc')
            ->get()
            ->filter(function ($bill) {
                return $bill->getDue() > 0;
            })
            ->map(function ($bill) use ($user) {
                return [
                    'id' => $bill->id,
                    'bill_id' => $user->billNumberFormat($bill->bill_id),
                    'bill_date' => $user->dateFormat($bill->bill_date),
                    'due_date' => $user->dateFormat($bill->due_date),
                    'total' => $bill->getTotal(),
                    'total_formatted' => $user->priceFormat($bill->getTotal()),
                    'due' => $bill->getDue(),
                    'due_formatted' => $user->priceFormat($bill->getDue()),
                ];
            });

        return response()->json([
            'bills' => $bills->values(),
            'vendor_balance' => $vendorBalance,
            'vendor_balance_formatted' => $user->priceFormat($vendorBalance),
        ]);
    }

    /**
     * Create payment for bill - Payment processing
     */
    public function createPayment(Request $request, $bill_id = null)
    {
        \Log::info('createBillPayment started', ['request' => $request->all()]);
        \DB::beginTransaction();
        try {
            if (Auth::user()->can('manage bill')) {
                // Generate unique payment number for grouping transactions
                $paymentNo = $this->paymentNumber();

                // Handle bulk payments from receive-bill-payment form
                $payments = $request->input('payments', []);
                $totalAmountPaid = $request->input('amount_paid', 0);
                $totalPaymentsApplied = !empty($payments) ? array_sum($payments) : 0;
                $creditAmount = $totalAmountPaid - $totalPaymentsApplied; // Excess amount as vendor credit

                // Validation rules
                $validatorRules = [
                    'payment_date' => 'required|date',
                    'vendor_id' => 'required|exists:venders,id',
                ];

                // Add amount validation based on whether it's bulk or single payment
                if (!empty($payments)) {
                    $validatorRules['amount_paid'] = 'required|numeric|min:0.01';
                } elseif ($bill_id) {
                    $validatorRules['amount'] = 'required|numeric|min:0.01';
                    $totalAmountPaid = $request->input('amount', 0);
                    $totalPaymentsApplied = $totalAmountPaid;
                    $creditAmount = 0;
                } else {
                    $validatorRules['amount_paid'] = 'required|numeric|min:0.01';
                    $creditAmount = $totalAmountPaid;
                }

                // Require payment account
                $validatorRules['payment_account'] = 'required|exists:bank_accounts,id';

                // Validate bills exist for bulk payments
                if (!empty($payments)) {
                    foreach ($payments as $bId => $amount) {
                        if ($amount > 0) {
                            $bill = Bill::find($bId);
                            if (!$bill) {
                                return redirect()->back()->with('error', __('Bill not found: ') . $bId);
                            }
                        }
                    }
                }

                // If single bill payment, validate bill exists
                if ($bill_id && empty($payments)) {
                    $bill = Bill::find($bill_id);
                    if (!$bill) {
                        return redirect()->back()->with('error', __('Bill not found.'));
                    }
                }

                $validator = \Validator::make($request->all(), $validatorRules);
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }

                // Process bill payments
                $totalApplied = 0;

                if (!empty($payments)) {
                    // Bulk payment processing - QBO style
                    $firstBillId = null;
                    $firstBill = null;
                    $vendorId = null;
                    
                    foreach ($payments as $bId => $amount) {
                        if ($amount > 0) {
                            $bill = Bill::find($bId);
                            if (!$bill) continue;
                            
                            // Track first bill for the combined payment entry
                            if ($firstBillId === null) {
                                $firstBillId = $bId;
                                $firstBill = $bill;
                                $vendorId = $bill->vender_id;
                            }
                            
                            // Update bill status
                            $due = $bill->getDue();
                            if ($bill->status == 0) {
                                $bill->send_date = date('Y-m-d');
                            }
                            
                            if ($due <= $amount) {
                                $bill->status = 4; // Paid
                            } else {
                                $bill->status = 3; // Partial
                            }
                            $bill->save();
                            
                            // Update vendor balance (debit the payment amount - reducing what we owe)
                            Utility::updateUserBalance('vendor', $bill->vender_id, $amount, 'debit');
                            
                            $totalApplied += $amount;
                        }
                    }
                    
                    // Create ONE bill_payment entry with total applied amount
                    if ($totalApplied > 0 && $firstBillId) {
                        $billPayment = new BillPayment();
                        $billPayment->bill_id = $firstBillId;
                        $billPayment->date = $request->payment_date;
                        $billPayment->amount = $totalApplied;
                        $billPayment->account_id = $request->payment_account;
                        $billPayment->payment_method = $request->payment_method ?? 0;
                        $billPayment->reference = $request->reference_no ?? null;
                        $billPayment->description = $request->memo;
                        $billPayment->save();
                        
                        // Create Bill Payment transaction for applied amount
                        $billPayment->user_id = $vendorId;
                        $billPayment->user_type = 'Vendor';
                        $billPayment->type = 'Payment';
                        $billPayment->created_by = Auth::user()->id;
                        $billPayment->owned_by = Auth::user()->ownedId();
                        $billPayment->payment_id = $billPayment->id;
                        $billPayment->category = 'Bill';
                        $billPayment->account = $request->payment_account;
                        $billPayment->payment_no = $paymentNo;
                        
                        Transaction::addTransaction($billPayment);
                        
                        $vendor = Vender::find($vendorId);
                    }
                } else {
                    // Single bill payment processing
                    $paymentAmount = $request->input('amount', 0);
                    $billPayment = new BillPayment();
                    $billPayment->bill_id = $bill_id;
                    $billPayment->date = $request->payment_date;
                    $billPayment->amount = $paymentAmount;
                    $billPayment->account_id = $request->payment_account;
                    $billPayment->payment_method = $request->payment_method ?? 0;
                    $billPayment->reference = $request->reference ?? null;
                    $billPayment->description = $request->description;
                    $billPayment->save();

                    $bill = Bill::where('id', $bill_id)->first();
                    $due = $bill->getDue();

                    if ($bill->status == 0) {
                        $bill->send_date = date('Y-m-d');
                        $bill->save();
                    }

                    if ($due <= $paymentAmount) {
                        $bill->status = 4; // Paid
                        $bill->save();
                    } else {
                        $bill->status = 3; // Partial
                        $bill->save();
                    }

                    $billPayment->user_id = $bill->vender_id;
                    $billPayment->user_type = 'Vendor';
                    $billPayment->type = 'Partial';
                    $billPayment->created_by = Auth::user()->id;
                    $billPayment->owned_by = Auth::user()->ownedId();
                    $billPayment->payment_id = $billPayment->id;
                    $billPayment->category = 'Bill';
                    $billPayment->account = $request->payment_account;
                    $billPayment->payment_no = $paymentNo;

                    Transaction::addTransaction($billPayment);
                    $vendor = Vender::where('id', $bill->vender_id)->first();

                    // Update vendor balance
                    Utility::updateUserBalance('vendor', $bill->vender_id, $paymentAmount, 'debit');

                    $totalApplied = $paymentAmount;
                }

                // Update bank account balance (debit - money going out)
                Utility::bankAccountBalance($request->payment_account, $totalAmountPaid, 'debit');

                // Handle credit amount if any (amount_paid > sum of payments)
                if ($creditAmount > 0) {
                    // Create a second bill_payment entry for vendor credit
                    $creditPayment = new BillPayment();
                    $creditPayment->bill_id = isset($firstBillId) ? $firstBillId : $bill_id;
                    $creditPayment->date = $request->payment_date;
                    $creditPayment->amount = $creditAmount;
                    $creditPayment->account_id = $request->payment_account;
                    $creditPayment->payment_method = $request->payment_method ?? 0;
                    $creditPayment->reference = $request->reference_no ?? null;
                    $creditPayment->description = 'Vendor Credit - ' . ($request->memo ?? 'Excess payment');
                    $creditPayment->payment_type = 'credit'; // Mark as credit type
                    $creditPayment->save();

                    // Credit vendor balance (excess amount as vendor credit)
                    Utility::updateUserBalance('vendor', $request->vendor_id, $creditAmount, 'credit');

                    Utility::makeActivityLog(Auth::user()->id, 'Vendor Credit', $request->vendor_id, 'Excess Payment Credit', 'Amount: ' . $creditAmount);
                }

                // Create voucher entry for bank account (optional - skip if chart accounts not set up)
                $bankAccount = BankAccount::find($request->payment_account);
                if ($bankAccount && !empty($bankAccount->chart_account_id) && $bankAccount->chart_account_id != 0) {
                    try {
                        $data = [
                            'id' => !empty($payments) ? array_key_first($payments) : $bill_id,
                            'no' => !empty($payments) ? ($firstBill ? $firstBill->bill_id : 'BULK-' . date('YmdHis')) : (isset($bill) && $bill ? $bill->bill_id : 'PAYMENT-' . date('YmdHis')),
                            'date' => $request->payment_date,
                            'reference' => $request->reference_no,
                            'description' => $request->memo ?? 'Bill Payment',
                            'amount' => $totalAmountPaid,
                            'prod_id' => 0,
                            'category' => 'Bill',
                            'owned_by' => Auth::user()->ownedId(),
                            'created_by' => Auth::user()->creatorId(),
                            'created_at' => date('Y-m-d H:i:s', strtotime($request->payment_date)),
                            'account_id' => $bankAccount->chart_account_id,
                        ];

                        if (preg_match('/\bcash\b/i', $bankAccount->bank_name ?? '') || preg_match('/\bcash\b/i', $bankAccount->holder_name ?? '')) {
                            $voucherId = Utility::cpv_entry($data);
                        } else {
                            $voucherId = Utility::bpv_entry($data);
                        }

                        // Update voucher_id for the bill_payment entry
                        if (isset($billPayment) && $billPayment->id && isset($voucherId)) {
                            BillPayment::where('id', $billPayment->id)->update([
                                'voucher_id' => $voucherId,
                            ]);
                        }
                    } catch (\Exception $voucherException) {
                        // Log voucher error but don't fail the payment
                        \Log::warning('Voucher entry failed for bill payment: ' . $voucherException->getMessage());
                    }
                }

                // Activity log
                $vendor = $vendor ?? Vender::find($request->vendor_id);
                Utility::makeActivityLog(Auth::user()->id, 'Bill Payment', 0, 'Create Bill Payment', 'Vendor: ' . ($vendor ? $vendor->name : 'Unknown') . ', Total Amount: ' . $totalApplied);

                \DB::commit();
                return redirect()->route('receive-bill-payment.index')->with('success', __('Bill payment successfully recorded.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Bill payment error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', __('Error processing payment: ') . $e->getMessage());
        }
    }

    /**
     * Generate payment number
     */
    public function paymentNumber()
    {
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = $user->type == 'company' ? 'created_by' : 'owned_by';
        
        // Get the latest payment_no with 'bpay-' prefix
        $latest = Transaction::where($column, '=', $ownerId)
            ->where('payment_no', 'LIKE', 'bpay-%')
            ->latest()
            ->first();
        
        if (!$latest) {
            return 'bpay-1';
        }

        // Extract numeric part from 'bpay-XXXX' format
        $numericPart = (int) str_replace('bpay-', '', $latest->payment_no);
        return 'bpay-' . ($numericPart + 1);
    }

    /**
     * Remove the specified payment from storage.
     */
    public function destroy($id)
    {
        if (!Auth::user()->can('manage bill')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $payment = BillPayment::find($id);
        if (!$payment) {
            return redirect()->back()->with('error', __('Payment not found.'));
        }

        \DB::beginTransaction();
        try {
            $bill = Bill::find($payment->bill_id);

            // Reverse bank account balance
            if ($payment->account_id) {
                Utility::bankAccountBalance($payment->account_id, $payment->amount, 'credit');
            }

            // Reverse vendor balance
            if ($bill) {
                Utility::updateUserBalance('vendor', $bill->vender_id, $payment->amount, 'credit');

                // Update bill status
                $bill->status = 3; // Partial or check if fully reversed
                $bill->save();
            }

            // Delete transaction
            Transaction::where('payment_id', $payment->id)
                ->where('category', 'Bill')
                ->delete();

            $payment->delete();

            \DB::commit();
            return redirect()->route('receive-bill-payment.index')
                ->with('success', __('Payment deleted successfully.'));
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->back()->with('error', __('Error: ') . $e->getMessage());
        }
    }
}
