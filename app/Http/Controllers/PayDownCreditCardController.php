<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\CreditCardPayment;
use App\Models\JournalEntry;
use App\Models\Utility;
use App\Models\Vender;
use App\Services\JournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PayDownCreditCardController extends Controller
{
    /**
     * Display a listing of credit card payments
     */
    public function index(Request $request)
    {
        if (!Auth::user()->can('manage bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $companyId = Auth::user()->creatorId();
        
        $payments = CreditCardPayment::where('created_by', $companyId)
            ->with(['creditCardAccount', 'bankAccount'])
            ->orderBy('payment_date', 'desc')
            ->get();

        return view('payDownCreditCard.index', compact('payments'));
    }

    /**
     * Show the form for creating a new credit card payment
     */
    public function create()
    {
        if (!Auth::user()->can('create bill')) {
            return response()->json(['error' => __('Permission denied.')], 401);
        }

        $companyId = Auth::user()->creatorId();

        // Get credit card accounts (Chart of Accounts with sub_type = credit card)
        $creditCardAccounts = ChartOfAccount::where('created_by', $companyId)
            ->whereHas('subType', function($q) {
                $q->whereIn('name', ['Credit Card', 'CreditCard', 'Credit card']);
            })
            ->get()
            ->pluck('name', 'id')
            ->toArray();
        $creditCardAccounts = ['' => __('Select credit card')] + $creditCardAccounts;

        // Get bank accounts for payment
        $bankAccountsQuery = BankAccount::where('created_by', $companyId)->get();
        $bankAccounts = [];
        $bankAccounts[''] = __('Select bank account');
        foreach ($bankAccountsQuery as $account) {
            $bankAccounts[$account->id] = $account->bank_name . ' ' . $account->holder_name;
        }

        // Get vendors as payees
        $vendors = Vender::where('created_by', $companyId)
            ->get()
            ->pluck('name', 'id')
            ->toArray();
        $vendors = ['' => __('Choose a payee')] + $vendors;

        return view('payDownCreditCard.create', compact('creditCardAccounts', 'bankAccounts', 'vendors'));
    }

    /**
     * Store a newly created credit card payment
     */
    public function store(Request $request)
    {
        if (!Auth::user()->can('create bill')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = \Validator::make($request->all(), [
            'credit_card_account_id' => 'required|exists:chart_of_accounts,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $payment = new CreditCardPayment();
            $payment->credit_card_account_id = $request->credit_card_account_id;
            $payment->bank_account_id = $request->bank_account_id;
            $payment->amount = $request->amount;
            $payment->payment_date = $request->payment_date;
            $payment->memo = $request->memo;
            $payment->reference = $request->reference;
            
            // Payee (optional)
            if ($request->payee_id) {
                $payment->payee_id = $request->payee_id;
                $payment->payee_type = 'vendor';
            }
            
            $payment->status = 1; // Cleared by default
            $payment->created_by = Auth::user()->creatorId();
            $payment->owned_by = Auth::user()->ownedId();
            $payment->save();

            // Handle attachments
            if ($request->hasFile('attachments')) {
                $attachments = [];
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('credit_card_payments', 'public');
                    $attachments[] = $path;
                }
                $payment->attachments = $attachments;
                $payment->save();
            }

            // Update bank account balance (decrease)
            Utility::bankAccountBalance($request->bank_account_id, $request->amount, 'debit');

            // Create journal entry for the payment
            
            $this->createPayDownJournalEntry($payment);

            DB::commit();

            Utility::makeActivityLog(Auth::user()->id, 'Pay Down Credit Card', $payment->id, 'Create Pay Down Credit Card', 'Credit Card Payment Created');

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => __('Credit card payment recorded successfully.')]);
            }

            // Determine redirect based on save action
            if ($request->has('save_action') && $request->save_action === 'save_new') {
                return redirect()->route('paydowncreditcard.create')->with('success', __('Credit card payment recorded successfully.'));
            }

            return redirect()->route('expense.index')->with('success', __('Credit card payment recorded successfully.'));
        } catch (\Exception $e) {
            DB::rollback();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified credit card payment
     */
    public function show($id)
    {
        if (!Auth::user()->can('manage bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        try {
            $id = Crypt::decrypt($id);
        } catch (\Exception $e) {
            // ID might not be encrypted
        }

        $payment = CreditCardPayment::where('created_by', Auth::user()->creatorId())
            ->with(['creditCardAccount', 'bankAccount'])
            ->findOrFail($id);

        return view('payDownCreditCard.show', compact('payment'));
    }

    /**
     * Show the form for editing the specified credit card payment
     */
    public function edit($id)
    {
        if (!Auth::user()->can('edit bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        try {
            $id = Crypt::decrypt($id);
        } catch (\Exception $e) {
            // ID might not be encrypted
        }

        $companyId = Auth::user()->creatorId();

        $payment = CreditCardPayment::where('created_by', $companyId)
            ->with(['creditCardAccount', 'bankAccount'])
            ->findOrFail($id);

        // Get credit card accounts
        $creditCardAccounts = ChartOfAccount::where('created_by', $companyId)
            ->whereHas('subType', function($q) {
                $q->whereIn('name', ['Credit Card', 'CreditCard', 'Credit card']);
            })
            ->get()
            ->pluck('name', 'id')
            ->toArray();
        $creditCardAccounts = ['' => __('Select credit card')] + $creditCardAccounts;

        // Get bank accounts
        $bankAccountsQuery = BankAccount::where('created_by', $companyId)->get();
        $bankAccounts = [];
        $bankAccounts[''] = __('Select bank account');
        foreach ($bankAccountsQuery as $account) {
            $bankAccounts[$account->id] = $account->bank_name . ' ' . $account->holder_name;
        }

        // Get vendors as payees
        $vendors = Vender::where('created_by', $companyId)
            ->get()
            ->pluck('name', 'id')
            ->toArray();
        $vendors = ['' => __('Choose a payee')] + $vendors;

        return view('payDownCreditCard.edit', compact('payment', 'creditCardAccounts', 'bankAccounts', 'vendors'));
    }

    /**
     * Update the specified credit card payment
     */
    public function update(Request $request, $id)
    {
        if (!Auth::user()->can('edit bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        try {
            $id = Crypt::decrypt($id);
        } catch (\Exception $e) {
            // ID might not be encrypted
        }

        $validator = \Validator::make($request->all(), [
            'credit_card_account_id' => 'required|exists:chart_of_accounts,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $payment = CreditCardPayment::where('created_by', Auth::user()->creatorId())->findOrFail($id);
            
            $oldAmount = $payment->amount;
            $oldBankAccountId = $payment->bank_account_id;

            // Reverse old bank transaction
            Utility::bankAccountBalance($oldBankAccountId, $oldAmount, 'credit');

            // Update payment
            $payment->credit_card_account_id = $request->credit_card_account_id;
            $payment->bank_account_id = $request->bank_account_id;
            $payment->amount = $request->amount;
            $payment->payment_date = $request->payment_date;
            $payment->memo = $request->memo;
            $payment->reference = $request->reference;
            
            if ($request->payee_id) {
                $payment->payee_id = $request->payee_id;
                $payment->payee_type = 'vendor';
            } else {
                $payment->payee_id = null;
                $payment->payee_type = null;
            }
            
            $payment->save();

            // Handle new attachments
            if ($request->hasFile('attachments')) {
                $attachments = $payment->attachments ?? [];
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('credit_card_payments', 'public');
                    $attachments[] = $path;
                }
                $payment->attachments = $attachments;
                $payment->save();
            }

            // Apply new bank transaction
            Utility::bankAccountBalance($request->bank_account_id, $request->amount, 'debit');

            // Update or create journal entry
            $journalEntry = JournalEntry::where('reference_id', $payment->id)
                ->where('module', 'pay_down_credit_card')
                ->first();
            
            if ($journalEntry) {
                $this->updatePayDownJournalEntry($payment, $journalEntry);
            } else {
                $this->createPayDownJournalEntry($payment);
            }

            DB::commit();

            Utility::makeActivityLog(Auth::user()->id, 'Pay Down Credit Card', $payment->id, 'Update Pay Down Credit Card', 'Credit Card Payment Updated');

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => __('Credit card payment updated successfully.')]);
            }

            return redirect()->route('expense.index')->with('success', __('Credit card payment updated successfully.'));
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Pay Down Credit Card Update Error: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified credit card payment
     */
    public function destroy($id)
    {
        if (!Auth::user()->can('delete bill')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        try {
            $id = Crypt::decrypt($id);
        } catch (\Exception $e) {
            // ID might not be encrypted
        }

        DB::beginTransaction();
        try {
            $payment = CreditCardPayment::where('created_by', Auth::user()->creatorId())->findOrFail($id);
            
            // Reverse bank transaction
            Utility::bankAccountBalance($payment->bank_account_id, $payment->amount, 'credit');

            // Delete attachments
            if ($payment->attachments) {
                foreach ($payment->attachments as $attachment) {
                    Storage::disk('public')->delete($attachment);
                }
            }

            $payment->delete();

            DB::commit();

            return redirect()->route('expense.index')->with('success', __('Credit card payment deleted successfully.'));
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', $e->getMessage());
        }
        
    }

    /**
     * Create journal entry for Pay Down Credit Card
     * Debit: Credit Card Account (decreases liability)
     * Credit: Bank Account (decreases asset)
     * 
     * @param CreditCardPayment $payment
     * @return JournalEntry
     */
    private function createPayDownJournalEntry($payment)
    {
        $journalItems = [];

        // Get the vendor/payee name
        $payeeName = 'Unknown';
        // if ($payment->payee_id && $payment->payee_type == 'vendor') {
            $vendor = Vender::find($payment->payee_id);
            $payeeName = $vendor ? $vendor->name : 'Unknown';
        // }

        // Get bank account info
        $bank = BankAccount::find($payment->bank_account_id);
        $bankAccountId = $bank ? $bank->chart_account_id : null;
        
        // Get credit card account info
        $creditCardAccount = ChartOfAccount::find($payment->credit_card_account_id);

        // Journal entry for Pay Down Credit Card:
        // Debit Credit Card Account (reduce liability)
        // Credit Bank Account (reduce asset)

        // Debit entry - Credit Card Account (liability decreases)
        
        $journalItems[] = [
            'account_id' => $payment->credit_card_account_id,
            'debit' => $payment->amount,
            'credit' => 0,
            'description' => 'Pay Down Credit Card - ' . ($creditCardAccount ? $creditCardAccount->name : 'Credit Card'),
            'type' => 'Pay Down Credit Card',
            'sub_type' => 'credit card payment',
            'name' => $payeeName,
            'ref_number' => $payment->reference,
            'vendor_id' => $payment->payee_type == 'vendor' ? $payment->payee_id : null,
            'customer_id' => null,
            'created_user' => Auth::user()->id,
            'created_by' => Auth::user()->creatorId(),
            'company_id' => Auth::user()->ownedId(),
            'created_at' => date('Y-m-d H:i:s', strtotime($payment->payment_date)),
        ];
// dd($journalItems,$payment->payment_date);
        // Create journal entry using JournalService
        $journalEntry = JournalService::createJournalEntry([
            'date' => date('Y-m-d', strtotime($payment->payment_date)),
            'backdate' => true,
            'reference' => 'PDCC-' . $payment->id,
            'description' => 'Pay Down Credit Card - ' . ($creditCardAccount ? $creditCardAccount->name : 'Credit Card'),
            'journal_id' => Utility::journalNumber(),
            'voucher_type' => 'JV',
            'reference_id' => $payment->id,
            'prod_id' => null,
            'category' => 'Pay Down Credit Card',
            'module' => 'pay_down_credit_card',
            'source' => 'pay_down_credit_card_creation',
            'created_user' => Auth::user()->id,
            'created_by' => Auth::user()->creatorId(),
            'owned_by' => Auth::user()->ownedId(),
            'ref_number' => $payment->reference,
            'user_type' => $payment->payee_type,
            'vendor_id' => $payment->payee_type == 'vendor' ? $payment->payee_id : null,
            'company_id' => Auth::user()->ownedId(),
            'bill_id' => $payment->id,
            'items' => $journalItems,
            'ap_name' => $payeeName,
            'ap_account_id' => $bankAccountId,
            'ap_amount' => $payment->amount,
            'ap_sub_type' => 'bank payment',
            'ap_description' => 'Bank Payment for Credit Card - ' . ($bank ? $bank->bank_name : 'Bank'),
            'created_at' => date('Y-m-d H:i:s', strtotime($payment->payment_date)),
        ]);

        \Log::info('Journal entry created for Pay Down Credit Card', [
            'payment_id' => $payment->id,
            'journal_entry_id' => $journalEntry->id,
        ]);
        
        return $journalEntry;
    }

    /**
     * Update journal entry for Pay Down Credit Card
     * 
     * @param CreditCardPayment $payment
     * @param JournalEntry $journalEntry
     * @return JournalEntry
     */
    private function updatePayDownJournalEntry($payment, $journalEntry)
    {
        $journalItems = [];

        // Get the vendor/payee name
        $payeeName = 'Unknown';
        // if ($payment->payee_id && $payment->payee_type == 'vendor') {
            $vendor = Vender::find($payment->payee_id);
            $payeeName = $vendor ? $vendor->name : 'Unknown';
        // }

        // Get bank account info
        $bank = BankAccount::find($payment->bank_account_id);
        $bankAccountId = $bank ? $bank->chart_account_id : null;
        
        // Get credit card account info
        $creditCardAccount = ChartOfAccount::find($payment->credit_card_account_id);

        // Debit entry - Credit Card Account (liability decreases)
        $journalItems[] = [
            'account_id' => $payment->credit_card_account_id,
            'debit' => $payment->amount,
            'credit' => 0,
            'description' => 'Pay Down Credit Card - ' . ($creditCardAccount ? $creditCardAccount->name : 'Credit Card'),
            'type' => 'Pay Down Credit Card',
            'sub_type' => 'credit card payment',
            'name' => $payeeName,
            'ref_number' => $payment->reference,
            'vendor_id' => $payment->payee_type == 'vendor' ? $payment->payee_id : null,
            'customer_id' => null,
            'created_user' => Auth::user()->id,
            'created_by' => Auth::user()->creatorId(),
            'company_id' => Auth::user()->ownedId(),
            'created_at' => date('Y-m-d H:i:s', strtotime($payment->payment_date)),            
        ];

        // Update journal entry using JournalService
        $updatedJournalEntry = JournalService::updateJournalEntry($journalEntry->id, [
            'date' => date('Y-m-d', strtotime($payment->payment_date)),
            'backdate' => true,
            'reference' => 'PDCC-' . $payment->id,
            'description' => 'Pay Down Credit Card - ' . ($creditCardAccount ? $creditCardAccount->name : 'Credit Card'),
            'reference_id' => $payment->id,
            'category' => 'Pay Down Credit Card',
            'module' => 'pay_down_credit_card',
            'source' => 'pay_down_credit_card_update',
            'user_type' => $payment->payee_type,
            'ref_number' => $payment->reference,
            'vendor_id' => $payment->payee_type == 'vendor' ? $payment->payee_id : null,
            'bill_id' => $payment->id,
            'items' => $journalItems,
            'ap_name' => $payeeName,
            'ap_account_id' => $bankAccountId,
            'ap_amount' => $payment->amount,
            'ap_sub_type' => 'bank payment',
            'ap_description' => 'Bank Payment for Credit Card - ' . ($bank ? $bank->bank_name : 'Bank'),
            'created_at' => date('Y-m-d H:i:s', strtotime($payment->payment_date)),            
        ]);

        \Log::info('Journal entry updated for Pay Down Credit Card', [
            'payment_id' => $payment->id,
            'journal_entry_id' => $updatedJournalEntry->id,
        ]);
        
        return $updatedJournalEntry;
    }


}
