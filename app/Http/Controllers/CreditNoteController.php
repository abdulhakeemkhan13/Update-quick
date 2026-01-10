<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Utility;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\WorkFlow;
use App\Models\Notification;
use App\Models\WorkFlowAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Auth;

class CreditNoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {

        if(\Auth::user()->can('manage credit note'))
        {
            $invoices = Invoice::where('created_by', \Auth::user()->creatorId())->get();

            return view('creditNote.index', compact('invoices'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create($invoice_id)
    {

        if(\Auth::user()->can('create credit note'))
        {

            $invoiceDue = Invoice::where('id', $invoice_id)->first();

            return view('creditNote.create', compact('invoiceDue', 'invoice_id'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function creditmemoIndex()
    {
        return view('creditmemo.index');
    }

    public function creditmemoCreate($customerId)
    {
        if (\Auth::user()->can('create invoice')) {
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())
                ->where('module', '=', 'invoice')->get();
            $invoice_number = \Auth::user()->invoiceNumberFormat($this->invoiceNumber());

            $customers = Customer::where($column, $ownerId)->get()->pluck('name', 'id')->toArray();
            $customers = ['__add__' => '➕ Add new customer'] + ['' => 'Select Customer'] + $customers;

            $category = ProductServiceCategory::where($column, $ownerId)
                ->where('type', 'income')->get()->pluck('name', 'id')->toArray();
            $category = ['__add__' => '➕ Add new category'] + ['' => 'Select Category'] + $category;

            $product_services = ProductService::where($column, $ownerId)->get()->pluck('name', 'id');
            $product_services->prepend('--', '');
            
            // Get taxes for tax dropdown
            $taxes = \App\Models\Tax::where('created_by', \Auth::user()->creatorId())->get();

            return view('creditmemo.create', compact(
                'customers',
                'invoice_number',
                'product_services',
                'category',
                'customFields',
                'customerId',
                'taxes'
            ));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function invoiceNumber()
    {
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = $user->type == 'company' ? 'created_by' : 'owned_by';
        $latest = Invoice::where($column, '=', $ownerId)->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->invoice_id + 1;
    }

    public function store(Request $request, $invoice_id)
    {
         dd($request->all(),'ss');
        \DB::beginTransaction();
        try {
        if(\Auth::user()->can('create credit note'))
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'amount' => 'required|numeric',
                                   'date' => 'required',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $invoiceDue = Invoice::where('id', $invoice_id)->first();
            if($request->amount > $invoiceDue->getDue())
            {
                return redirect()->back()->with('error', 'Maximum ' . \Auth::user()->priceFormat($invoiceDue->getDue()) . ' credit limit of this invoice.');
            }
            $invoice = Invoice::where('id', $invoice_id)->first();

            $credit              = new CreditNote();
            $credit->invoice     = $invoice_id;
            $credit->customer    = $invoice->customer_id;
            $credit->date        = $request->date;
            $credit->amount      = $request->amount;
            $credit->description = $request->description;
            $credit->save();

            $customer = Customer::find($invoice->customer_id);
            $balance = 0;
            if($customer->credit_balance != 0)
            {
                $balance = $customer->credit_balance + $request->amount;
            }

            $customer->credit_balance = $balance;
            $customer->save();

            Utility::updateUserBalance('credit', $invoice->customer_id, $request->amount, 'debit');

             // // WorkFlow get which is active
             $us_mail = 'false';
             $us_notify = 'false';
             $us_approve = 'false';
             $usr_Notification = [];
             $workflow = WorkFlow::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'accounts')->where('status', 1)->first();
             if ($workflow) {
                 $workflowaction = WorkFlowAction::where('workflow_id', $workflow->id)->where('status', 1)->get();
                 foreach ($workflowaction as $action) {
                     $useraction = json_decode($action->assigned_users);
                     if (strtolower('issue-credit-note') == $action->node_id) {
                         // Pick that stage user assign or change on lead
                         if (@$useraction != '') {
                             $useraction = json_decode($useraction);
                             foreach ($useraction as $anyaction) {
                                 // make new user array
                                 if ($anyaction->type == 'user') {
                                     $usr_Notification[] = $anyaction->id;
                                 }
                             }
                         }
                         $raw_json = trim($action->applied_conditions, '"');
                         $cleaned_json = stripslashes($raw_json);
                         $applied_conditions = json_decode($cleaned_json, true);

                         if (isset($applied_conditions['conditions']) && is_array($applied_conditions['conditions'])) {
                             $arr = [
                                 'invoice' => 'invoice_invoice_id',
                                 'customer' => 'customer_name',
                                 'amount' => 'amount',
                             ];
                             $relate = [
                                'invoice_invoice_id' => 'invoice',
                                'customer_name' => 'customer',
                             ];

                             foreach ($applied_conditions['conditions'] as $conditionGroup) {

                                 if (in_array($conditionGroup['action'], ['send_email', 'send_notification', 'send_approval'])) {
                                     $query = CreditNote::where('id', $credit->id);
                                     foreach ($conditionGroup['conditions'] as $condition) {
                                         $field = $condition['field'];
                                         $operator = $condition['operator'];
                                         $value = $condition['value'];
                                         if (isset($arr[$field], $relate[$arr[$field]])) {
                                             $relatedField = strpos($arr[$field], '_') !== false ? explode('_', $arr[$field], 2)[1] : $arr[$field];
                                             $relation = $relate[$arr[$field]];
                                             // Apply condition to the related model
                                             $query->whereHas($relation, function ($relatedQuery) use ($relatedField, $operator, $value) {
                                                 $relatedQuery->where($relatedField, $operator, $value);
                                            });
                                         } else {
                                             // Apply condition directly to the contract model
                                             $query->where($arr[$field], $operator, $value);
                                         }
                                     }
                                     $result = $query->first();

                                     if (!empty($result)) {
                                         if ($conditionGroup['action'] === 'send_email') {
                                             $us_mail = 'true';
                                         } elseif ($conditionGroup['action'] === 'send_notification') {
                                             $us_notify = 'true';
                                         } elseif ($conditionGroup['action'] === 'send_approval') {
                                             $us_approve = 'true';
                                         }
                                     }
                                 }
                             }
                         }
                         if ($us_mail == 'true') {
                             // email send
                         }
                         if ($us_notify == 'true' || $us_approve == 'true') {
                             // notification generate
                            if (count($usr_Notification) > 0) {
                                $usr_Notification[] = Auth::user()->creatorId();
                                foreach ($usr_Notification as $usrLead) {
                                    $data = [
                                        "updated_by" => Auth::user()->id,
                                        "data_id" => $credit->id,
                                        "name" => '',
                                    ];
                                    if($us_notify == 'true'){
                                        Utility::makeNotification($usrLead,'create_credit',$data,$credit->id,'create Credit');
                                    }elseif($us_approve == 'true'){
                                        Utility::makeNotification($usrLead,'approve_credit',$data,$credit->id,'For Approval Credit Note');
                                    }
                                }
                            }
                         }
                     }
                 }
             }
            \DB::commit();
            return redirect()->back()->with('success', __('Credit Note successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        } catch (\Exception $e) {
            \DB::rollback();
            dd($e);
            return redirect()->back()->with('error', $e);
        }
    }


    public function edit($invoice_id, $creditNote_id)
    {
        if(\Auth::user()->can('edit credit note'))
        {

            $creditNote = CreditNote::find($creditNote_id);

            return view('creditNote.edit', compact('creditNote'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function update(Request $request, $invoice_id, $creditNote_id)
    {

        if(\Auth::user()->can('edit credit note'))
        {

            $validator = \Validator::make(
                $request->all(), [
                                   'amount' => 'required|numeric',
                                   'date' => 'required',
                               ]
            );

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $invoiceDue = Invoice::where('id', $invoice_id)->first();
            $credit = CreditNote::find($creditNote_id);
            if($request->amount > $invoiceDue->getDue()+$credit->amount)
            {
                return redirect()->back()->with('error', 'Maximum ' . \Auth::user()->priceFormat($invoiceDue->getDue()) . ' credit limit of this invoice.');
            }


            Utility::updateUserBalance('customer', $invoiceDue->customer_id, $credit->amount, 'credit');

            $credit->date        = $request->date;
            $credit->amount      = $request->amount;
            $credit->description = $request->description;
            $credit->save();

            Utility::updateUserBalance('customer', $invoiceDue->customer_id, $request->amount, 'debit');


            return redirect()->back()->with('success', __('Credit Note successfully updated.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy($invoice_id, $creditNote_id)
    {
        if(\Auth::user()->can('delete credit note'))
        {

            $creditNote = CreditNote::find($creditNote_id);
            $creditNote->delete();

            Utility::updateUserBalance('customer', $creditNote->customer, $creditNote->amount, 'credit');

            return redirect()->back()->with('success', __('Credit Note successfully deleted.'));

        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function customCreate()
    {
        if(\Auth::user()->can('create credit note'))
        {

            $invoices = Invoice::where('created_by', \Auth::user()->creatorId())->get()->pluck('invoice_id', 'id');

            return view('creditNote.custom_create', compact('invoices'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function customStore(Request $request)
    {
        dd($request->all(),'asd');
        if(\Auth::user()->can('create credit note'))
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'invoice' => 'required|numeric',
                                   'amount' => 'required|numeric',
                                   'date' => 'required',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $invoice_id = $request->invoice;
            $invoiceDue = Invoice::where('id', $invoice_id)->first();

            if($request->amount > $invoiceDue->getDue())
            {
                return redirect()->back()->with('error', 'Maximum ' . \Auth::user()->priceFormat($invoiceDue->getDue()) . ' credit limit of this invoice.');
            }
            $invoice             = Invoice::where('id', $invoice_id)->first();
            $credit              = new CreditNote();
            $credit->invoice     = $invoice_id;
            $credit->customer    = $invoice->customer_id;
            $credit->date        = $request->date;
            $credit->amount      = $request->amount;
            $credit->description = $request->description;
            $credit->save();

            Utility::updateUserBalance('customer', $invoice->customer_id, $request->amount, 'debit');

            return redirect()->back()->with('success', __('Credit Note successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function getinvoice(Request $request)
    {
        $invoice = Invoice::where('id', $request->id)->first();

        echo json_encode($invoice->getDue());
    }

    /**
     * Create Journal Voucher for Credit Memo
     * Credit Memo Accounting:
     * - DEBIT: Sales/Income accounts (reversing sales)
     * - DEBIT: Tax Liability accounts (reversing tax collected)
     * - CREDIT: Accounts Receivable (reducing what customer owes)
     * - CREDIT: Discount account (if discount was given, remove it)
     */
    private function createCreditMemoJournalVoucher($creditMemo)
    {
        // Import required models
        $JournalEntry = \App\Models\JournalEntry::class;
        $JournalItem = \App\Models\JournalItem::class;
        $ChartOfAccount = \App\Models\ChartOfAccount::class;
        $ChartOfAccountType = \App\Models\ChartOfAccountType::class;
        $ChartOfAccountSubType = \App\Models\ChartOfAccountSubType::class;
        $ProductService = \App\Models\ProductService::class;
        $Tax = \App\Models\Tax::class;
        $Utility = \App\Models\Utility::class;
        $TransactionLines = \App\Models\TransactionLines::class;

        // Get next journal ID
        $latest = $JournalEntry::where('created_by', '=', $creditMemo->created_by)
            ->where('voucher_type', 'JV')
            ->orderBy('id', 'Desc')
            ->first();
        $journalId = $latest ? $latest->journal_id + 1 : 1;

        // Create Journal Entry
        $journal = new $JournalEntry();
        $journal->journal_id = $journalId;
        $journal->date = $creditMemo->issue_date ?? now()->format('Y-m-d');
        $journal->reference = $creditMemo->credit_memo_id ?? $creditMemo->id;
        $journal->description = 'Credit Memo No: ' . ($creditMemo->credit_memo_id ?? $creditMemo->id);
        $journal->reference_id = $creditMemo->id;
        $journal->category = 'Credit Memo';
        $journal->voucher_type = 'JV';
        $journal->owned_by = $creditMemo->owned_by;
        $journal->created_by = $creditMemo->created_by;
        $journal->save();

        $totalCredits = 0; // A/R + Discount
        $totalDebits = 0;  // Sales + Tax

        // Get customer info for journal items
        $customer = \App\Models\Customer::find($creditMemo->customer_id);
        $customerName = $customer ? $customer->name : '';
        $customerId = $creditMemo->customer_id;

        // ============================================================
        // 1. Debit entries for each product (Reversing Sales Revenue)
        // ============================================================
        if (method_exists($creditMemo, 'items') || property_exists($creditMemo, 'items')) {
            $creditMemoProducts = $creditMemo->items;
            foreach ($creditMemoProducts as $product) {
                if (!$product->product_id) continue;

                $productService = $ProductService::find($product->product_id);
                if (!$productService || !$productService->sale_chartaccount_id) continue;

                // Calculate product amount (qty * price - line discount)
                $lineAmount = (floatval($product->quantity) * floatval($product->price)) - floatval($product->discount);

                // Debit Sales/Income account (reversing sale)
                $journalItem = new $JournalItem();
                $journalItem->journal = $journal->id;
                $journalItem->account = $productService->sale_chartaccount_id;
                $journalItem->product_ids = $product->id;
                $journalItem->description = 'Credit Memo - ' . ($productService->name ?? 'Product');
                $journalItem->credit = 0;
                $journalItem->debit = $lineAmount;
                $journalItem->type = 'Credit Memo';
                $journalItem->name = $customerName;
                $journalItem->customer_id = $customerId;
                $journalItem->save();
                $totalDebits += $lineAmount;

                // Create transaction line
                $Utility::addTransactionLines([
                    'account_id' => $productService->sale_chartaccount_id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $lineAmount,
                    'reference' => 'Credit Memo Journal',
                    'reference_id' => $journal->id,
                    'reference_sub_id' => $journalItem->id,
                    'date' => $journal->date,
                    'product_id' => $creditMemo->id,
                    'product_type' => 'Credit Memo',
                    'product_item_id' => $product->id,
                ], 'create');

                // ============================================================
                // 2. Debit entry for item-level tax (if any)
                // ============================================================
                $itemTax = floatval($product->item_tax_price ?? 0);
                if ($itemTax > 0 && $product->tax) {
                    $taxModel = $Tax::find($product->tax);
                    $taxAccountId = $taxModel ? $taxModel->chart_account_id : null;

                    if (!$taxAccountId) {
                        $taxAccountId = $this->getOrCreateTaxLiabilityAccount($creditMemo->created_by);
                    }

                    if ($taxAccountId) {
                        $journalItem = new $JournalItem();
                        $journalItem->journal = $journal->id;
                        $journalItem->account = $taxAccountId;
                        $journalItem->prod_tax_id = $product->id;
                        $journalItem->description = 'Tax on Credit Memo No: ' . ($creditMemo->credit_memo_id ?? $creditMemo->id);
                        $journalItem->credit = 0;
                        $journalItem->debit = $itemTax;
                        $journalItem->type = 'Credit Memo';
                        $journalItem->name = $customerName;
                        $journalItem->customer_id = $customerId;
                        $journalItem->save();
                        $totalDebits += $itemTax;

                        $Utility::addTransactionLines([
                            'account_id' => $taxAccountId,
                            'transaction_type' => 'Debit',
                            'transaction_amount' => $itemTax,
                            'reference' => 'Credit Memo Journal',
                            'reference_id' => $journal->id,
                            'reference_sub_id' => $journalItem->id,
                            'date' => $journal->date,
                            'product_id' => $creditMemo->id,
                            'product_type' => 'Credit Memo Tax',
                            'product_item_id' => $product->id,
                        ], 'create');
                    }
                }
            }
        }

        // ============================================================
        // 3. Debit entry for invoice-level Sales Tax (if any)
        // ============================================================
        $invoiceTax = floatval($creditMemo->sales_tax_amount ?? 0);
        if ($invoiceTax > 0) {
            $taxAccountId = null;
            if ($creditMemo->sales_tax_rate) {
                $taxModel = $Tax::find($creditMemo->sales_tax_rate);
                $taxAccountId = $taxModel ? $taxModel->chart_account_id : null;
            }
            if (!$taxAccountId) {
                $taxAccountId = $this->getOrCreateTaxLiabilityAccount($creditMemo->created_by);
            }

            if ($taxAccountId) {
                $journalItem = new $JournalItem();
                $journalItem->journal = $journal->id;
                $journalItem->account = $taxAccountId;
                $journalItem->description = 'Sales Tax on Credit Memo No: ' . ($creditMemo->credit_memo_id ?? $creditMemo->id);
                $journalItem->credit = 0;
                $journalItem->debit = $invoiceTax;
                $journalItem->type = 'Credit Memo';
                $journalItem->name = $customerName;
                $journalItem->customer_id = $customerId;
                $journalItem->save();
                $totalDebits += $invoiceTax;

                $Utility::addTransactionLines([
                    'account_id' => $taxAccountId,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $invoiceTax,
                    'reference' => 'Credit Memo Journal',
                    'reference_id' => $journal->id,
                    'reference_sub_id' => $journalItem->id,
                    'date' => $journal->date,
                    'product_id' => $creditMemo->id,
                    'product_type' => 'Credit Memo Sales Tax',
                ], 'create');
            }
        }

        // ============================================================
        // 4. Credit entry for Discount (if any) - Reversing discount given
        // ============================================================
        $totalDiscount = floatval($creditMemo->total_discount ?? 0);
        if ($totalDiscount > 0) {
            $discountAccountId = $this->getOrCreateSalesDiscountAccount($creditMemo->created_by);

            if ($discountAccountId) {
                $journalItem = new $JournalItem();
                $journalItem->journal = $journal->id;
                $journalItem->account = $discountAccountId;
                $journalItem->description = 'Discount on Credit Memo No: ' . ($creditMemo->credit_memo_id ?? $creditMemo->id);
                $journalItem->credit = $totalDiscount;
                $journalItem->debit = 0;
                $journalItem->type = 'Credit Memo';
                $journalItem->name = $customerName;
                $journalItem->customer_id = $customerId;
                $journalItem->save();
                $totalCredits += $totalDiscount;

                $Utility::addTransactionLines([
                    'account_id' => $discountAccountId,
                    'transaction_type' => 'Credit',
                    'transaction_amount' => $totalDiscount,
                    'reference' => 'Credit Memo Journal',
                    'reference_id' => $journal->id,
                    'reference_sub_id' => $journalItem->id,
                    'date' => $journal->date,
                    'product_id' => $creditMemo->id,
                    'product_type' => 'Credit Memo Discount',
                ], 'create');
            }
        }

        // ============================================================
        // 5. Credit entry for Accounts Receivable (Reducing customer debt)
        // ============================================================
        $receivableAccountId = $this->getOrCreateAccountReceivable($creditMemo->created_by);

        // The A/R credit should equal the total credit memo amount
        $creditMemoTotal = floatval($creditMemo->total_amount ?? 0);

        if ($receivableAccountId && $creditMemoTotal > 0) {
            $journalItem = new $JournalItem();
            $journalItem->journal = $journal->id;
            $journalItem->account = $receivableAccountId;
            $journalItem->description = 'Credit applied to customer for Credit Memo No: ' . ($creditMemo->credit_memo_id ?? $creditMemo->id);
            $journalItem->credit = $creditMemoTotal;
            $journalItem->debit = 0;
            $journalItem->type = 'Credit Memo';
            $journalItem->name = $customerName;
            $journalItem->customer_id = $customerId;
            $journalItem->save();
            $totalCredits += $creditMemoTotal;

            $Utility::addTransactionLines([
                'account_id' => $receivableAccountId,
                'transaction_type' => 'Credit',
                'transaction_amount' => $creditMemoTotal,
                'reference' => 'Credit Memo Journal',
                'reference_id' => $journal->id,
                'reference_sub_id' => $journalItem->id,
                'date' => $journal->date,
                'product_id' => $creditMemo->id,
                'product_type' => 'Credit Memo A/R',
            ], 'create');
        }

        // Save voucher ID to credit memo
        $creditMemo->voucher_id = $journal->id;
        $creditMemo->save();

        return $journal->id;
    }

    /**
     * Get or create Tax Liability account
     */
    private function getOrCreateTaxLiabilityAccount($createdBy)
    {
        $ChartOfAccount = \App\Models\ChartOfAccount::class;
        $ChartOfAccountType = \App\Models\ChartOfAccountType::class;
        $ChartOfAccountSubType = \App\Models\ChartOfAccountSubType::class;

        $types = $ChartOfAccountType::where('created_by', '=', $createdBy)->where('name', 'Liabilities')->first();
        if (!$types) return null;

        $subType = $ChartOfAccountSubType::where('type', $types->id)->where('name', 'Current Liabilities')->first();
        if (!$subType) return null;

        $account = $ChartOfAccount::where('code', 'TAX-LIAB')
            ->where('created_by', $createdBy)
            ->first();

        if (!$account) {
            $account = new $ChartOfAccount();
            $account->code = 'TAX-LIAB';
            $account->name = 'Sales Tax Payable';
            $account->type = $types->id;
            $account->sub_type = $subType->id;
            $account->is_enabled = 1;
            $account->created_by = $createdBy;
            $account->save();
        }

        return $account->id;
    }

    /**
     * Get or create Sales Discount account
     */
    private function getOrCreateSalesDiscountAccount($createdBy)
    {
        $ChartOfAccount = \App\Models\ChartOfAccount::class;
        $ChartOfAccountType = \App\Models\ChartOfAccountType::class;
        $ChartOfAccountSubType = \App\Models\ChartOfAccountSubType::class;

        $types = $ChartOfAccountType::where('created_by', '=', $createdBy)->where('name', 'Expenses')->first();
        if (!$types) return null;

        $subType = $ChartOfAccountSubType::where('type', $types->id)->first();
        if (!$subType) return null;

        $account = $ChartOfAccount::where('code', 'SALES-DISC')
            ->where('created_by', $createdBy)
            ->first();

        if (!$account) {
            $account = new $ChartOfAccount();
            $account->code = 'SALES-DISC';
            $account->name = 'Sales Discounts';
            $account->type = $types->id;
            $account->sub_type = $subType->id;
            $account->is_enabled = 1;
            $account->created_by = $createdBy;
            $account->save();
        }

        return $account->id;
    }

    /**
     * Get or create Accounts Receivable account
     */
    private function getOrCreateAccountReceivable($createdBy)
    {
        $ChartOfAccount = \App\Models\ChartOfAccount::class;
        $ChartOfAccountType = \App\Models\ChartOfAccountType::class;
        $ChartOfAccountSubType = \App\Models\ChartOfAccountSubType::class;

        $types = $ChartOfAccountType::where('created_by', '=', $createdBy)->where('name', 'Assets')->first();
        if (!$types) return null;

        $subType = $ChartOfAccountSubType::where('type', $types->id)->where('name', 'Current Assets')->first();
        if (!$subType) return null;

        $account = $ChartOfAccount::where('name', 'Accounts Receivable')
            ->where('created_by', $createdBy)
            ->first();

        if (!$account) {
            $account = new $ChartOfAccount();
            $account->code = 'AR';
            $account->name = 'Accounts Receivable';
            $account->type = $types->id;
            $account->sub_type = $subType->id;
            $account->is_enabled = 1;
            $account->created_by = $createdBy;
            $account->save();
        }

        return $account->id;
    }

}

