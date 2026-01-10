<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\BillAccount;
use App\Models\BillPayment;
use App\Models\BillProduct;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountSubType;
use App\Models\ChartOfAccountType;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\StockReport;
use App\Models\TransactionLines;
use App\Models\Utility;
use App\Models\Vender;
use App\Models\WorkFlow;
use App\Models\Notification;
use App\Models\Tax;
use App\Models\WorkFlowAction;
use App\Models\Project;
use App\Services\JournalServicecredit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Auth;

class CreditCreditCardController extends Controller
{

    public function billNumber()
    {
        $latest = Bill::where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 'Bill')->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->bill_id + 1;
    }

    public function creditCreditCardNumber()
    {
        $latest = Bill::where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 'Credit Card Credit')->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->bill_id + 1;
    }

    public function employee(Request $request)
    {
        $employee = Employee::where('id', '=', $request->id)->first();

        return view('expense.employee_detail', compact('employee'));
    }

    public function vender(Request $request)
    {
        $vender = Vender::where('id', '=', $request->id)->first();

        return view('expense.vender_detail', compact('vender'));
    }
    public function customer(Request $request)
    {
        $customer = Customer::where('id', '=', $request->id)->first();
        return view('expense.customer_detail', compact('customer'));
    }

    public function product(Request $request)
    {
        $data['product'] = $product = ProductService::find($request->product_id);
        $data['unit'] = !empty($product->unit) ? $product->unit->name : '';
        $data['taxRate'] = $taxRate = !empty($product->tax_id) ? $product->taxRate($product->tax_id) : 0;
        $data['taxes'] = !empty($product->tax_id) ? $product->tax($product->tax_id) : 0;
        $salePrice = $product->purchase_price;
        $quantity = 1;
        $taxPrice = ($taxRate / 100) * ($salePrice * $quantity);
        $data['totalAmount'] = ($salePrice * $quantity);

        return json_encode($data);
    }

    public function index(Request $request)
    {

        if (\Auth::user()->can('manage bill')) {

            $vender = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $vender->prepend('Select Vendor', '');

            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                ->whereNotIn('type', ['product & service', 'income'])
                ->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');

            $status = Bill::$statues;

            $query = Bill::where('type', '=', 'Credit Card Credit ')
                ->where('created_by', '=', \Auth::user()->creatorId());
            if (!empty($request->vender)) {
                $query->where('vender_id', '=', $request->vender);
            }
            if (count(explode('to', $request->bill_date)) > 1) {
                $date_range = explode(' to ', $request->bill_date);
                $query->whereBetween('bill_date', $date_range);
            } elseif (!empty($request->bill_date)) {
                $date_range = [$request->date, $request->bill_date];
                $query->whereBetween('bill_date', $date_range);
            }

            if (!empty($request->category)) {
                $query->where('category_id', '=', $request->category);
            }

            $expenses = $query->with(['category'])->get();

            return view('expense.creditCreditCard-index', compact('expenses', 'vender', 'status', 'category'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function create($Id)
    {
        if (\Auth::user()->can('create bill')) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'bill')->get();
            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                ->whereNotIn('type', ['product & service', 'income'])
                ->get()->pluck('name', 'id')->toArray();
            $category = ['__add__' => '➕ Add New category'] + ['' => 'Select Category'] + $category;

            $expense_number = \Auth::user()->expenseNumberFormat($this->creditCreditCardNumber());

            $employees = Employee::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();

            $customers = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();

            $venders = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
            
            // Build vendorOptions with address and terms info
            $vendersQuery = Vender::where('created_by', \Auth::user()->creatorId())->get();
            $vendorOptions = [];
            $vendorOptions[''] = 'Select Vendor';
            $vendorOptions['add'] = 'Add new vendor';

            foreach ($vendersQuery as $vender) {
                // Build billing address
                $address = "";
                if ($vender->billing_name) $address .= $vender->billing_name . "\n";
                if ($vender->billing_address) $address .= $vender->billing_address . "\n";
                if ($vender->billing_city || $vender->billing_state || $vender->billing_zip) {
                    $address .= trim($vender->billing_city . " " . $vender->billing_state . " " . $vender->billing_zip) . "\n";
                }
                if ($vender->billing_country) $address .= $vender->billing_country . "\n";
                if ($vender->billing_phone) $address .= "Phone: " . $vender->billing_phone;

                $vendorOptions[$vender->id] = [
                    'name'    => $vender->name,
                    'address' => trim($address),
                    'terms'   => $vender->terms ?? 'Net 30',
                    'balance' => $vender->getDueAmount() ?? 0,
                ];
            }

            $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $product_services->prepend('Select Item', '');

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');

            $subAccounts = ChartOfAccount::select('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_account_parents.account');
            $subAccounts->leftjoin('chart_of_account_parents', 'chart_of_accounts.parent', 'chart_of_account_parents.id');
            $subAccounts->where('chart_of_accounts.parent', '!=', 0);
            $subAccounts->where('chart_of_accounts.created_by', \Auth::user()->creatorId());
            $subAccounts = $subAccounts->get()->toArray();

            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))
                ->where('created_by', \Auth::user()->creatorId())
                ->get()->pluck('name', 'id')->toArray();
            $accounts = ['__add__' => '➕ Add New Account'] + ['' => 'Select Account'] + $accounts;

            $vendorId = $Id;
            
            // Get payment terms data for the form
            $paymentTerms = \App\Models\PaymentTerm::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $termsData = \App\Models\PaymentTerm::where('created_by', \Auth::user()->creatorId())->get();

            return view('expense.creditCreditCard-create', compact('employees', 'customers', 'venders', 'expense_number', 'product_services', 'category', 'customFields', 'Id', 'chartAccounts', 'accounts', 'subAccounts', 'vendorId', 'vendorOptions', 'termsData', 'paymentTerms'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        // dd($request->all());
        \DB::beginTransaction();
        try {
            if (\Auth::user()->can('create bill')) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'bill_date' => 'required',
                        'account_id' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages3 = $validator->getMessageBag();
                    // Return JSON for AJAX requests
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'status' => 'error',
                            'message' => $messages3->first()
                        ], 422);
                    }
                    return redirect()->back()->with('error', $messages3->first());
                }

                // Parse payee - prioritize vender_id, fallback to combined payee format
                $payeeType = null;
                $payeeId = null;
                
                if ($request->has('vender_id') && !empty($request->vender_id)) {
                    $payeeId = $request->vender_id;
                    $payeeType = 'vendor';
                } elseif ($request->has('payee') && !empty($request->payee)) {
                    $payeeParts = explode('_', $request->payee);
                    if (count($payeeParts) === 2) {
                        $payeeType = $payeeParts[0];
                        $payeeId = $payeeParts[1];
                    }
                }

                // Validate billable items have customers
                $billableValidationError = null;
                
                // Check category items (note: payload uses 'categories' not 'category')
                $categoriesData = $request->has('categories') ? $request->categories : ($request->has('category') ? $request->category : []);
                if (is_array($categoriesData)) {
                    foreach ($categoriesData as $index => $categoryData) {
                        // billable might be an array like ["1"] or a simple value
                        $isBillable = false;
                        if (isset($categoryData['billable'])) {
                            if (is_array($categoryData['billable'])) {
                                $isBillable = !empty($categoryData['billable'][0]);
                            } else {
                                $isBillable = (bool)$categoryData['billable'];
                            }
                        }
                        
                        if ($isBillable && empty($categoryData['customer_id'])) {
                            $billableValidationError = __('Select a customer for each billable split line.') . ' (Category row ' . ($index + 1) . ')';
                            break;
                        }
                    }
                }
                
                // Check item details
                if (!$billableValidationError && $request->has('items') && is_array($request->items)) {
                    foreach ($request->items as $index => $itemData) {
                        // billable might be an array like ["1"] or a simple value
                        $isBillable = false;
                        if (isset($itemData['billable'])) {
                            if (is_array($itemData['billable'])) {
                                $isBillable = !empty($itemData['billable'][0]);
                            } else {
                                $isBillable = (bool)$itemData['billable'];
                            }
                        }
                        
                        if ($isBillable && empty($itemData['customer_id'])) {
                            $billableValidationError = __('Select a customer for each billable split line.') . ' (Item row ' . ($index + 1) . ')';
                            break;
                        }
                    }
                }
                
                if ($billableValidationError) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'status' => 'error',
                            'message' => $billableValidationError
                        ], 422);
                    }
                    return redirect()->back()->with('error', $billableValidationError);
                }

                // Create Credit Card Credit 
                $expense = new Bill();
                $expense->bill_id = $this->creditCreditCardNumber();
                
                // Set vendor based on parsed payee type and ID
                $expense->vender_id = $payeeId;
                $expense->user_type = $payeeType;
                
                $expense->bill_date = $request->bill_date;
                $expense->due_date = $request->bill_date;
                $expense->status = 0; // Draft
                $expense->type = 'Credit Card Credit ';
                $expense->ref_number = $request->order_number;
                // New QBO fields
                $expense->notes = $request->memo ?? $request->notes;
                $expense->subtotal = $request->subtotal ?? 0;
                $expense->total = $request->total ?? 0;
                
                $expense->category_id = !empty($request->category_id) ? $request->category_id : 0;
                $expense->order_number = 0;
                $expense->created_by = \Auth::user()->creatorId();
                $expense->owned_by = \Auth::user()->ownedId();
                $expense->save();

                // Save Custom Fields
                if ($request->has('customField')) {
                    CustomField::saveData($expense, $request->customField);
                }

                $newitems = [];

                // Process CATEGORY DETAILS (Account-based expenses)
                // Note: The payload uses 'categories' not 'category'
                if (is_array($categoriesData) && !empty($categoriesData)) {
                    foreach ($categoriesData as $index => $categoryData) {
                        // Skip empty rows
                        if (empty($categoryData['account_id']) && empty($categoryData['amount'])) {
                            continue;
                        }

                        $billAccount = new BillAccount();
                        $billAccount->ref_id = $expense->id;
                        $billAccount->type = 'Credit Card Credit ';
                        $billAccount->chart_account_id = $categoryData['account_id'] ?? null;
                        $billAccount->description = $categoryData['description'] ?? '';
                        $billAccount->price = $categoryData['amount'] ?? 0;
                        
                        // Handle billable (could be array or simple value)
                        if (isset($categoryData['billable'])) {
                            if (is_array($categoryData['billable'])) {
                                $billAccount->billable = !empty($categoryData['billable'][0]) ? 1 : 0;
                            } else {
                                $billAccount->billable = $categoryData['billable'] ? 1 : 0;
                            }
                        } else {
                            $billAccount->billable = 0;
                        }
                        
                        $billAccount->customer_id = $categoryData['customer_id'] ?? null;
                        
                        // Handle tax checkbox (store as 1 or 0)
                        $billAccount->tax = isset($categoryData['tax']) ? 1 : 0;
                        
                        // IMPORTANT: Save order to maintain exact row position
                        $billAccount->order = $index;
                        
                        $billAccount->save();
                        
                        $newitems[] = [
                            'bill_account_id' => $billAccount->id,
                            'chart_account_id' => $billAccount->chart_account_id,
                            'amount' => $billAccount->price,
                            'description' => $billAccount->description,
                            'order' => $index,
                        ];
                    }
                }

                // Process ITEM DETAILS (Product/Service-based)
                if ($request->has('items') && is_array($request->items)) {
                    foreach ($request->items as $index => $itemData) {
                        // Skip empty rows
                        // Note: payload uses 'item_id' not 'product_id', and 'rate' not 'price'
                        $itemId = $itemData['item_id'] ?? $itemData['product_id'] ?? null;
                        $itemRate = $itemData['rate'] ?? $itemData['price'] ?? 0;
                        
                        if (empty($itemId) || empty($itemData['quantity']) && empty($itemRate)) {
                            continue;
                        }
                        $product = ProductService::find($itemId);
                        $account = null;
                        if ($product) {
                            if($product->type == 'product'){
                                $account = $product->asset_chartaccount_id ?? $product->expense_chartaccount_id;
                            }else{
                                $account = $product->expense_chartaccount_id;
                            }
                        }

                        $billProduct = new BillProduct();
                        $billProduct->bill_id = $expense->id;
                        $billProduct->product_id = $itemId;
                        $billProduct->description = $itemData['description'] ?? '';
                        $billProduct->quantity = $itemData['quantity'] ?? 1;
                        $billProduct->price = $itemRate;
                        $billProduct->discount = $itemData['discount'] ?? 0;
                        $billProduct->account_id = $account;
                        
                        // Handle tax checkbox (store as 1 or 0)
                        $billProduct->tax = isset($itemData['tax']) ? 1 : 0;
                        
                        // Calculate line total
                        $billProduct->line_total = $itemData['amount'] ?? ($billProduct->quantity * $billProduct->price);
                        
                        // Handle billable (could be array or simple value)
                        if (isset($itemData['billable'])) {
                            if (is_array($itemData['billable'])) {
                                $billProduct->billable = !empty($itemData['billable'][0]) ? 1 : 0;
                            } else {
                                $billProduct->billable = $itemData['billable'] ? 1 : 0;
                            }
                        } else {
                            $billProduct->billable = 0;
                        }
                        
                        $billProduct->customer_id = $itemData['customer_id'] ?? null;
                        
                        // IMPORTANT: Save order to maintain exact row position
                        $billProduct->order = $index;
                        
                        $billProduct->save();
                        
                        $newitems[] = [
                            'prod_id' => $billProduct->id,
                            'product_id' => $billProduct->product_id,
                            'quantity' => $billProduct->quantity,
                            'price' => $billProduct->price,
                            'order' => $index,
                        ];
                    }
                }

                // Store payment details
                $bank = BankAccount::find($request->account_id);
                if (!$bank || !$bank->chart_account_id) {
                    \DB::rollback();
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'status' => 'error',
                            'message' => __('Please select chart of account in bank account.')
                        ], 422);
                    }
                    return redirect()->back()->with('error', __('Please select chart of account in bank account.'));
                }

                $expensePayment = new BillPayment();
                $expensePayment->bill_id = $expense->id;
                $expensePayment->date = $request->bill_date;
                $expensePayment->amount = $request->total ?? 0;
                $expensePayment->account_id = $request->account_id;
                $expensePayment->coa_account = $bank ? $bank->chart_account_id : null;
                $expensePayment->payment_type = $request->payment_type;
                $expensePayment->payment_method = 0;
                $expensePayment->reference = $request->reference ?? '';
                $expensePayment->description = $request->memo ?? '';
                $expensePayment->save();

                // Get user for notifications
                if ($payeeType == 'employee') {
                    $user = Employee::find($payeeId);
                    $contact = $user->phone ?? '';
                } else if ($payeeType == 'customer') {
                    $user = Customer::find($payeeId);
                    $contact = $user->contact ?? '';
                } else {
                    $user = Vender::find($payeeId);
                    $contact = $user->contact ?? '';
                }

                // Notifications
                $setting = Utility::settings(\Auth::user()->creatorId());
                $expenseNotificationArr = [
                    'expense_number' => \Auth::user()->expenseNumberFormat($expense->bill_id),
                    'user_name' => \Auth::user()->name,
                    'bill_date' => $expense->bill_date,
                    'bill_due_date' => $expense->due_date,
                    'vendor_name' => $user->name ?? 'N/A',
                ];

                if (isset($setting['bill_notification']) && $setting['bill_notification'] == 1) {
                    Utility::send_slack_msg('new_bill', $expenseNotificationArr);
                }
                if (isset($setting['telegram_bill_notification']) && $setting['telegram_bill_notification'] == 1) {
                    Utility::send_telegram_msg('new_bill', $expenseNotificationArr);
                }
                if (isset($setting['twilio_bill_notification']) && $setting['twilio_bill_notification'] == 1) {
                    Utility::send_twilio_msg($contact, 'new_bill', $expenseNotificationArr);
                }

                // Auto-approve and create journal entry for company users
                // if (Auth::user()->type == 'company') {
                    $expense->status = 6; // Approved
                    $expense->save();
                    
                    // Create journal entry using JournalService
                    $this->createExpenseJournalEntry($expense);
                    
                    Utility::makeActivityLog(\Auth::user()->id, 'Credit Card Credit ', $expense->id, 'Create Credit Card Credit ', 'Credit Card Credit  Created & Approved');
                // }
                
                // Webhook
                $module = 'New Bill';
                $webhook = Utility::webhookSetting($module);
                if ($webhook) {
                    $parameter = json_encode($expense);
                    $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);

                    if ($status == true) {
                        Utility::makeActivityLog(\Auth::user()->id, 'Credit Card Credit ', $expense->id, 'Create Credit Card Credit ', 'Credit Card Credit  Created');
                        \DB::commit();
                        if ($request->ajax() || $request->wantsJson()) {
                            return response()->json([
                                'status' => 'success',
                                'message' => __('Credit Card Credit  successfully created.'),
                                'expense_id' => $expense->id
                            ], 200);
                        }
                        return redirect()->route('creditcreditcard.index')->with('success', __('Credit Card Credit  successfully created.'));
                    } else {
                        \DB::commit();
                        if ($request->ajax() || $request->wantsJson()) {
                            return response()->json([
                                'status' => 'error',
                                'message' => __('Webhook call failed.')
                            ], 500);
                        }
                        return redirect()->back()->with('error', __('Webhook call failed.'));
                    }
                }

                Utility::makeActivityLog(\Auth::user()->id, 'Credit Card Credit ', $expense->id, 'Create Credit Card Credit ', 'Credit Card Credit  Created');
                \DB::commit();
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'status' => 'success',
                        'message' => __('Credit Card Credit  successfully created.'),
                        'expense_id' => $expense->id
                    ], 200);
                }
                return redirect()->route('creditcreditcard.index')->with('success', __('Credit Card Credit  successfully created.'));
            } else {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => __('Permission denied.')
                    ], 403);
                }
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } catch (\Exception $e) {
            \DB::rollback();
            \Log::error('Credit Card Credit  Store Error: ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // private function createExpenseVoucher(Bill $expense)
    // {
    //     $expenseProducts = BillProduct::where('bill_id', $expense->id)->get();
    //     $expensePayment = BillPayment::where('bill_id', $expense->id)->first();
    //     $bank = BankAccount::find($expensePayment->account_id);

    //     $newitems = [];
    //     foreach ($expenseProducts as $product) {
    //         $newitems[] = [
    //             'prod_id' => $product->id,
    //             'item' => $product->product_id,
    //             'quantity' => $product->quantity,
    //             'price' => $product->price,
    //             'discount' => $product->discount,
    //             'itemTaxPrice' => $product->tax,
    //             'description' => $product->description,
    //         ];
    //     }
    //     //bill accounts
    //     $bill_accounts = BillAccount::where('ref_id', $expense->id)->get();
    //     foreach ($bill_accounts as $account) {
    //         $newitems[] = [
    //             'prod_id' => $account->id,
    //             'chart_account_id' => $account->chart_account_id,
    //             'amount' => $account->price,
    //             'description' => $account->description,
    //             'itemTaxPrice' => 0,
    //         ];
    //     }
    //     $data = [
    //         'id' => $expense->id,
    //         'no' => $expense->bill_id,
    //         'date' => $expense->bill_date,
    //         'created_at' => date('Y-m-d H:i:s', strtotime($expense->bill_date)),
    //         'reference' => $expense->ref_number,
    //         'category' => 'Expanse',
    //         'owned_by' => $expense->owned_by,
    //         'created_by' => $expense->created_by,
    //         'prod_id' => $expensePayment->id,
    //         'amount' => $expensePayment->amount,
    //         'items' => $newitems,
    //         'account_id' => $bank->chart_account_id,
    //     ];
    //     // dd($data);
    //     // Create CPV or BPV based on bank account
    //     if (preg_match('/\bcash\b/i', $bank->bank_name) || preg_match('/\bcash\b/i', $bank->holder_name)) {
    //         $voucherId = Utility::cpv_entry($data); // Cash Payment Voucher (CPV)
    //     } else {
    //         $voucherId = Utility::bpv_entry($data); // Bank Payment Voucher (BPV)
    //     }

    //     // Update payment with voucher ID
    //     $expensePayment->voucher_id = $voucherId;
    //     $expensePayment->save();

    //     // Update expense with voucher ID
    //     $expense->voucher_id = $voucherId;
    //     $expense->save();

    //     return $voucherId;
    // }

    /**
     * Create journal entry for an expense using JournalService
     * 
     * @param Bill $expense
     * @return JournalEntry
     * @throws Exception
     */
    private function createExpenseJournalEntry($expense)
    {
        // Build journal items from expense categories and products
        $journalItems = [];

        $vendor = null;
        if ($expense->user_type == 'vendor') {
            $vendor = Vender::find($expense->vender_id);
        } elseif ($expense->user_type == 'employee') {
            $vendor = Employee::find($expense->vender_id);
        } elseif ($expense->user_type == 'customer') {
            $vendor = Customer::find($expense->vender_id);
        }
        
        $vendorName = $vendor ? $vendor->name : 'Unknown';

        // Add category-based expenses (BillAccount with type='Credit Card Credit ')
        $expenseAccounts = BillAccount::where('ref_id', $expense->id)->where('type', 'Credit Card Credit ')->get();
        foreach ($expenseAccounts as $expenseAccount) {
            $journalItems[] = [
                'account_id' => $expenseAccount->chart_account_id,
                'debit' => $expenseAccount->price,
                'credit' => 0,
                'description' => $expenseAccount->description ?: 'Credit Card Credit',
                'type' => 'Credit Card Credit',
                'sub_type' => 'creditCardCredit account',
                'name' => $vendorName,
                'ref_number' => $expense->ref_number,
                'user_type' => $expense->user_type,
                'vendor_id' => $expense->vender_id,
                'customer_id' => null,
                'created_user' => \Auth::user()->id,
                'created_by' => \Auth::user()->creatorId(),
                'company_id' => \Auth::user()->ownedId(),
                'created_at' => date('Y-m-d H:i:s', strtotime($expense->bill_date)),
            ];
        }

        // Add product/service items (BillProduct)
        $expenseProducts = BillProduct::where('bill_id', $expense->id)->get();
        foreach ($expenseProducts as $expenseProduct) {
            $product = $expenseProduct->product;
            
            // Determine account ID based on product type
            $accountId = null;
            if ($product) {
                $accountId = $expenseProduct->account_id ?: $product->expense_chartaccount_id;
            }
            
            $journalItems[] = [
                'account_id' => $accountId,
                'debit' => $expenseProduct->line_total ?: ($expenseProduct->quantity * $expenseProduct->price),
                'credit' => 0,
                'description' => $expenseProduct->description ?: ($product ? $product->name : 'Product'),
                'product_id' => $expenseProduct->product_id,
                'type' => 'Credit Card Credit',
                'sub_type' => 'creditCardCredit item',
                'name' => $vendorName,
                'ref_number' => $expense->ref_number,
                'user_type' => $expense->user_type,
                'vendor_id' => $expense->vender_id,
                'customer_id' => null,
                'created_user' => \Auth::user()->id,
                'created_by' => \Auth::user()->creatorId(),
                'company_id' => \Auth::user()->ownedId(),
                'created_at' => date('Y-m-d H:i:s', strtotime($expense->bill_date)),
            ];
        }

        $billPayment = BillPayment::where('bill_id', $expense->id)->first();
        $bank = BankAccount::find($billPayment->account_id);
        if($bank){
            $accountPayable = ChartOfAccount::where('id', $bank->chart_account_id)->first();
        }else{
            $accountPayable = Utility::getAccountPayableAccount($bank->creatorId());
        }

        // Calculate total amount
        $totalAmount = 0;
        foreach ($journalItems as $item) {
            $totalAmount += $item['debit'];
        }

        // Create journal entry using JournalService
        $journalEntry = JournalServicecredit::createJournalEntry([
            'date' => $expense->bill_date,
            'backdate' => true,
            'reference' => \Auth::user()->expenseNumberFormat($expense->bill_id),
            'description' => 'Credit Card Credit from ' . $vendorName,
            'journal_id' => Utility::journalNumber(),
            'voucher_type' => 'JV',
            'reference_id' => $expense->id,
            'prod_id' => null,
            'category' => 'Credit Card Credit',
            'module' => 'creditCardCredit',
            'source' => 'creditCardCredit_creation',
            'created_user' => \Auth::user()->id,
            'created_by' => \Auth::user()->creatorId(),
            'owned_by' => \Auth::user()->ownedId(),
            'ref_number' => $expense->ref_number,
            'user_type' => $expense->user_type,
            'vendor_id' => $expense->vender_id,
            'company_id' => \Auth::user()->ownedId(),
            'bill_id' => $expense->id,
            'items' => $journalItems,
            'ap_name' => $vendorName,
            'ap_account_id' => $accountPayable->id,
            'ap_amount' => $totalAmount,
            'ap_sub_type' => 'expense payment',
            'ap_description' => 'Expense Payment - ' . \Auth::user()->expenseNumberFormat($expense->bill_id),
        ]);

        \Log::info('Journal entry created for expense', [
            'expense_id' => $expense->id,
            'journal_entry_id' => $journalEntry->id,
        ]);
        
        return $journalEntry;
    }

    /**
     * Update journal entry for an expense using JournalService
     * 
     * @param Bill $expense
     * @param JournalEntry $journalEntry
     * @return JournalEntry
     * @throws Exception
     */
    private function updateExpenseJournalEntry($expense, $journalEntry)
    {
        // Build journal items from expense categories and products
        $journalItems = [];

        $vendor = null;
        if ($expense->user_type == 'vendor') {
            $vendor = Vender::find($expense->vender_id);
        } elseif ($expense->user_type == 'employee') {
            $vendor = Employee::find($expense->vender_id);
        } elseif ($expense->user_type == 'customer') {
            $vendor = Customer::find($expense->vender_id);
        }
        
        $vendorName = $vendor ? $vendor->name : 'Unknown';
 
        // Add category-based expenses (BillAccount)
        $expenseAccounts = BillAccount::where('ref_id', $expense->id)->where('type', 'Credit Card Credit')->get();
        foreach ($expenseAccounts as $expenseAccount) {
            $journalItems[] = [
                'account_id' => $expenseAccount->chart_account_id,
                'debit' => $expenseAccount->price,
                'credit' => 0,
                'description' => $expenseAccount->description ?: 'Credit Card Credit',
                'type' => 'Credit Card Credit',
                'sub_type' => 'creditCardCredit account',
                'name' => $vendorName,
                'vendor_id' => $expense->vender_id,
                'user_type' => $expense->user_type,
                'ref_number' => $expense->ref_number,
                'customer_id' => null,
                'created_user' => \Auth::user()->id,
                'created_by' => \Auth::user()->creatorId(),
                'company_id' => \Auth::user()->ownedId(),
                'created_at' => date('Y-m-d H:i:s', strtotime($expense->bill_date)),
            ];
        }

        // Add product/service items (BillProduct)
        $expenseProducts = BillProduct::where('bill_id', $expense->id)->get();
        foreach ($expenseProducts as $expenseProduct) {
            $product = $expenseProduct->product;
            
            // Determine account ID based on product type
            $accountId = null;
            if ($product) {
                $accountId = $expenseProduct->account_id ?: $product->expense_chartaccount_id;
            }
            
            $journalItems[] = [
                'account_id' => $accountId,
                'debit' => $expenseProduct->line_total ?: ($expenseProduct->quantity * $expenseProduct->price),
                'credit' => 0,
                'description' => $expenseProduct->description ?: ($product ? $product->name : 'Product'),
                'product_id' => $expenseProduct->product_id,
                'type' => 'Credit Card Credit',
                'sub_type' => 'creditCardCredit item',
                'user_type' => $expense->user_type,
                'ref_number' => $expense->ref_number,
                'name' => $vendorName,
                'vendor_id' => $expense->vender_id,
                'customer_id' => null,
                'created_user' => \Auth::user()->id,
                'created_by' => \Auth::user()->creatorId(),
                'company_id' => \Auth::user()->ownedId(),
                'created_at' => date('Y-m-d H:i:s', strtotime($expense->bill_date)),
            ];
        }

        $billPayment = BillPayment::where('bill_id', $expense->id)->first();
        $bank = BankAccount::find($billPayment->account_id);
        if($bank){
            $accountPayable = ChartOfAccount::where('id', $bank->chart_account_id)->first();
        }else{
            $accountPayable = Utility::getAccountPayableAccount($bank->creatorId());
        }

        // Calculate total amount
        $totalAmount = 0;
        foreach ($journalItems as $item) {
            $totalAmount += $item['debit'];
        }

        // Update journal entry using JournalService
        $updatedJournalEntry = JournalServicecredit::updateJournalEntry($journalEntry->id, [
            'date' => $expense->bill_date,
            'backdate' => true,
            'reference' => \Auth::user()->expenseNumberFormat($expense->bill_id),
            'description' => 'Credit Card Credit from ' . $vendorName,
            'reference_id' => $expense->id,
            'category' => 'Credit Card Credit',
            'module' => 'creditCardCredit',
            'source' => 'creditCardCredit_update',
            'user_type' => $expense->user_type,
            'ref_number' => $expense->ref_number,
            'vendor_id' => $expense->vender_id,
            'bill_id' => $expense->id,
            'items' => $journalItems,
            'ap_name' => $vendorName,
            'ap_account_id' => $accountPayable->id,
            'ap_amount' => $totalAmount,
            'ap_sub_type' => 'creditCardCredit payment',
            'ap_description' => 'Credit Card Credit Payment - ' . \Auth::user()->expenseNumberFormat($expense->bill_id),
        ]);

        \Log::info('Journal entry updated for expense', [
            'expense_id' => $expense->id,
            'journal_entry_id' => $updatedJournalEntry->id,
        ]);
        
        return $updatedJournalEntry;
    }

    public function approveExpense($id)
    {
        \DB::beginTransaction();
        try {
            $expense = Bill::findOrFail($id);

            // Check if already approved
            if ($expense->status == 4) {
                return redirect()->back()->with('error', __('Expense already approved.'));
            }

            // Check if in pending approval status
            if ($expense->status != 5 && $expense->status != 0) {
                return redirect()->back()->with('error', __('Expense must be in pending approval status.'));
            }

            // Get payment details
            $expensePayment = BillPayment::where('bill_id', $expense->id)->first();
            if (!$expensePayment) {
                return redirect()->back()->with('error', __('Expense payment not found.'));
            }

            // Update bank balance (deduct from bank)
            Utility::bankAccountBalance($expensePayment->account_id, $expensePayment->amount, 'debit');

            // Update vendor balance
            Utility::updateUserBalance('vendor', $expense->vender_id, $expensePayment->amount, 'credit');

            // Create journal entry using JournalService
            $this->createExpenseJournalEntry($expense);

            // Update status to Approved
            $expense->status = 4;
            $expense->save();

            Utility::makeActivityLog(\Auth::user()->id, 'Credit Card Credit ', $expense->id, 'Approve Credit Card Credit ', 'Credit Card Credit  approved and journal entry created');

            // Send notification to expense creator
            $data = [
                "updated_by" => \Auth::user()->id,
                "data_id" => $expense->id,
                "name" => '',
            ];
            Utility::makeNotification($expense->created_by, 'creditCardCredit_approved', $data, $expense->id, 'Credit Card Credit  Approved');

            \DB::commit();
            return redirect()->route('expense.index')->with('success', __('Credit Card Credit  approved successfully and journal entry created.'));
        } catch (\Exception $e) {
            \DB::rollBack();
            // dd($e);
            \Log::error('Expense Approval Error: ' . $e->getMessage());
            return redirect()->back()->with('error', __('Error approving expense: ' . $e->getMessage()));
        }
    }
    // send for approval
    public function requestApproval($id)
    {
        \DB::beginTransaction();
        try {
            $expense = Bill::findOrFail($id);

            // Check if already approved
            if ($expense->status == 4) {
                return redirect()->back()->with('error', __('Expense already approved.'));
            }

            // Check if already in pending approval
            if ($expense->status == 5) {
                return redirect()->back()->with('error', __('Expense already sent for approval.'));
            }

            // Update status to Pending Approval (5)
            $expense->status = 5;   // Pending Approval
            $expense->save();

            Utility::makeActivityLog(\Auth::user()->id, 'Expense', $expense->id, 'Request Approval', 'Expense sent for approval');

            // Clear old notifications
            Notification::where('data_id', $expense->id)
                ->where('type', 'create_expanse')
                ->where('is_read', 0)
                ->delete();

            // Send notification to approver (creator or designated approver)
            $usrLead = \Auth::user()->creatorId();
            $data = [
                "updated_by" => \Auth::user()->id,
                "data_id" => $expense->id,
                "name" => '',
            ];
            Utility::makeNotification($usrLead, 'create_expanse', $data, $expense->id, 'create Expanse');

            \DB::commit();
            return redirect()->route('expense.index')->with('success', __('Expense sent for approval successfully.'));
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Request Approval Error: ' . $e->getMessage());
            return redirect()->back()->with('error', __('Error requesting approval: ' . $e->getMessage()));
        }
    }
    public function rejectExpense($id)
    {
        \DB::beginTransaction();
        try {
            $expense = Bill::findOrFail($id);

            // Check if already approved or rejected
            if ($expense->status == 4) {
                return redirect()->back()->with('error', __('Cannot reject an approved expense.'));
            }

            if ($expense->status == 7) {
                return redirect()->back()->with('error', __('Expense already rejected.'));
            }

            // Check if in pending approval status
            if ($expense->status != 5 && $expense->status != 0) {
                return redirect()->back()->with('error', __('Expense must be in pending approval status.'));
            }

            // Update status to Rejected (7)
            $expense->status = 7;   // Rejected
            $expense->save();

            Utility::makeActivityLog(\Auth::user()->id, 'Expense', $expense->id, 'Reject Expense', 'Expense rejected');

            \DB::commit();
            return redirect()->route('expense.index')->with('success', __('Expense rejected successfully.'));
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Expense Rejection Error: ' . $e->getMessage());
            return redirect()->back()->with('error', __('Error rejecting expense: ' . $e->getMessage()));
        }
    }
    public function show($ids)
    {

        if (\Auth::user()->can('show bill')) {
            try {
                $id = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Expense Not Found.'));
            }

            $id = Crypt::decrypt($ids);

            $expense = Bill::with('debitNote', 'payments.bankAccount', 'items.product.unit')->find($id);

            if (!empty($expense) && $expense->created_by == \Auth::user()->creatorId()) {
                $expensePayment = BillPayment::where('bill_id', $expense->id)->first();

                if ($expense->user_type == 'employee') {
                    $user = $expense->employee;
                } elseif ($expense->user_type == 'customer') {
                    $user = $expense->customer;
                } else {
                    $user = $expense->vender;
                }

                $item = $expense->items;
                $accounts = $expense->accounts;
                $items = [];
                if (!empty($item) && count($item) > 0) {
                    foreach ($item as $k => $val) {
                        if (!empty($accounts[$k])) {
                            $val['chart_account_id'] = $accounts[$k]['chart_account_id'];
                            $val['account_id'] = $accounts[$k]['id'];
                            $val['amount'] = $accounts[$k]['price'];
                        }
                        $items[] = $val;
                    }
                } else {

                    foreach ($accounts as $k => $val) {
                        $val1['chart_account_id'] = $accounts[$k]['chart_account_id'];
                        $val1['account_id'] = $accounts[$k]['id'];
                        $val1['amount'] = $accounts[$k]['price'];
                        $items[] = $val1;
                    }
                }

                return view('expense.view', compact('expense', 'user', 'items', 'expensePayment'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function items(Request $request)
    {
        $items = BillProduct::where('bill_id', $request->bill_id)->where('product_id', $request->product_id)->first();
        return json_encode($items);
    }

    public function edit($ids)
    {

        if (\Auth::user()->can('edit bill')) {
            try {
                $id = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Expense Not Found.'));
            }

            $id = Crypt::decrypt($ids);
            $bill = Bill::find($id); // Renamed to $bill to match view expectation (create view uses manual vars, but typically edit views use model binding or specific var)
            // Wait, existing code used $expense. I should check view. create view didn't use $expense. edit view usually uses $bill or $expense.
            // The existing creditCreditCard-edit.blade.php uses $bill at line 2.
            $expense = $bill;

            $bankAccountQuery = BillPayment::where('bill_id', $id)->first();
            $bankAccount = $bankAccountQuery ? BankAccount::find($bankAccountQuery->account_id) : null;
            
             $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))
                ->where('created_by', \Auth::user()->creatorId())
                ->get()->pluck('name', 'id')->toArray();
            $accounts = ['__add__' => '➕ Add New Account'] + ['' => 'Select Account'] + $accounts;
            
            if (!empty($expense)) {
                $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                    ->whereNotIn('type', ['product & service', 'income'])
                    ->get()->pluck('name', 'id')->toArray();
                $category = ['__add__' => '➕ Add New category'] + ['' => 'Select Category'] + $category;
                $expense_number = \Auth::user()->expenseNumberFormat($expense->bill_id);

                $venders = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();

                $employees = Employee::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();

                $customers = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();

                // Build vendorOptions
                $vendersQuery = Vender::where('created_by', \Auth::user()->creatorId())->get();
                $vendorOptions = [];
                $vendorOptions[''] = 'Select Vendor';
                $vendorOptions['add'] = 'Add new vendor';
                foreach ($vendersQuery as $vender) {
                    $address = "";
                    if ($vender->billing_name) $address .= $vender->billing_name . "\n";
                    if ($vender->billing_address) $address .= $vender->billing_address . "\n";
                    if ($vender->billing_city || $vender->billing_state || $vender->billing_zip) {
                        $address .= trim($vender->billing_city . " " . $vender->billing_state . " " . $vender->billing_zip) . "\n";
                    }
                    if ($vender->billing_country) $address .= $vender->billing_country . "\n";
                    if ($vender->billing_phone) $address .= "Phone: " . $vender->billing_phone;

                    $vendorOptions[$vender->id] = [
                        'name'    => $vender->name,
                        'address' => trim($address),
                        'terms'   => $vender->terms ?? 'Net 30',
                        'balance' => $vender->getDueAmount() ?? 0,
                    ];
                }

                $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

                $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                    ->where('created_by', \Auth::user()->creatorId())->get()
                    ->pluck('code_name', 'id');
                $chartAccounts->prepend('Select Account', '');

                $subAccounts = ChartOfAccount::select('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_account_parents.account');
                $subAccounts->leftjoin('chart_of_account_parents', 'chart_of_accounts.parent', 'chart_of_account_parents.id');
                $subAccounts->where('chart_of_accounts.parent', '!=', 0);
                $subAccounts->where('chart_of_accounts.created_by', \Auth::user()->creatorId());
                $subAccounts = $subAccounts->get()->toArray();

                $bank_Account = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))
                    ->where('created_by', \Auth::user()->creatorId())
                    ->get()->pluck('name', 'id')->toArray();
                $bank_Account = ['__add__' => '➕ Add New Bank Account'] + ['' => 'Select Bank Account'] + $bank_Account;

                //for item and account show in repeater
                $billProducts = $expense->items; 
                $billAccounts = $expense->accounts; 
                $categoryDetails = $billAccounts; // Alias for view compatibility 
                
                // Use raw collections for view (view expects object notation)
                $items = $billProducts;
                
                // Prepare categories array (account-based)
                $categoriesAccountData = [];
                foreach ($billAccounts as $account) {
                    $categoriesAccountData[] = [
                        'id' => $account->id,
                        'chart_account_id' => $account->chart_account_id,
                        'description' => $account->description,
                        'amount' => $account->price,
                        'billable' => $account->billable,
                        'tax' => $account->tax,
                        'customer_id' => $account->customer_id,
                        'order' => $account->order,
                    ];
                }
                
                $Id = $expense->id;
                
                // Get selected payee for dropdown
                $selected_payee = $expense->getSelectedPayee();

                // Payment terms for compat
                 $paymentTerms = \App\Models\PaymentTerm::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

                return view('expense.creditCreditCard-edit', compact(
                    'employees',
                    'customers',
                    'venders',
                    'product_services',
                    'expense',
                    'bill',
                    'expense_number',
                    'category',
                    'bank_Account',
                    'chartAccounts',
                    'items',
                    'categoriesAccountData',
                    'categoryDetails',
                    'bankAccount',
                    'subAccounts',
                    'Id',
                    'accounts',
                    'selected_payee',
                    'vendorOptions',
                    'paymentTerms'
                ));
            } else {
                return redirect()->back()->with('error', __('Expense Not Found.'));
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        \DB::beginTransaction();
        try {
            if (!\Auth::user()->can('edit bill')) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            $expense = Bill::find($id);

            if (!$expense || $expense->created_by != \Auth::user()->creatorId()) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            // Validation
            // Validation
            $validator = \Validator::make($request->all(), [
                'bill_date' => 'required',
                'account_id' => 'required',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->with('error', $validator->errors()->first());
            }

            // Parse payee - prioritize explicit vender_id
            $payeeId = null;
            $payeeType = null;

            if ($request->has('vender_id') && !empty($request->vender_id)) {
                $payeeId = $request->vender_id;
                // user_type is not typically strictly required for Bills if logic depends on vender_id, 
                // but good to keep clean if schema supports it. Leaving as null or default.
            } elseif ($request->has('payee') && !empty($request->payee)) {
                $payeeParts = explode('_', $request->payee);
                if (count($payeeParts) === 2) {
                    $payeeType = $payeeParts[0]; // 'employee', 'customer', or 'vendor'
                    $payeeId = $payeeParts[1];
                }
            }

            // Update expense main fields
            $expense->vender_id = $payeeId;
            $expense->user_type = $payeeType;
            $expense->bill_date = $request->bill_date;
            $expense->due_date = $request->bill_date; // Same as bill date for credits
            $expense->category_id = $request->category_id; // Keeping as is (Bank account?)
            // Note: account_id is handled in BillPayment update below
            
            $expense->ref_number = $request->order_number; // View uses order_number (Ref no). Request uses order_number?
            // create view used `name="order_number"`. 
            // update controller (original) used `$request->reference_no`.
            // I should use `$request->order_number` to match view.
            
            $expense->notes = $request->memo ?? null;
            $expense->total = $request->total ?? $expense->total; // Preserve existing total if not sent
            $expense->save();

            // ===================================
            // PROCESS CATEGORY DETAILS (Account-based expenses)
            // ===================================
            $existingCategoryIds = [];

            if ($request->has('categories') && is_array($request->categories)) {
                
                foreach ($request->categories as $index => $categoryData) {
                    if (empty($categoryData['account_id']) || empty($categoryData['amount'])) {
                        continue;
                    }

                    $billAccountId = $categoryData['id'] ?? null;
                    
                    if ($billAccountId) {
                        // Update existing
                        $billAccount = BillAccount::find($billAccountId);
                        if ($billAccount && $billAccount->ref_id == $expense->id) {
                            $existingCategoryIds[] = $billAccountId;
                        } else {
                            $billAccount = null;
                        }
                    } else {
                        $billAccount = null;
                    }
                    if (!$billAccount) {
                        // Create new
                        $billAccount = new BillAccount();
                        $billAccount->ref_id = $expense->id;
                        $billAccount->type = $expense->type; // Dynamic type
                    }

                    $billAccount->chart_account_id = $categoryData['account_id'] ?? null;
                    $billAccount->price = $categoryData['amount'] ?? 0;
                    $billAccount->description = $categoryData['description'] ?? '';
                    $billAccount->billable = isset($categoryData['billable']) && is_array($categoryData['billable']) ? 1 : 0;
                    $billAccount->tax = isset($categoryData['tax']) ? 1 : 0;
                    $billAccount->customer_id = $categoryData['customer_id'] ?? null;
                    $billAccount->order = $index;
                    $billAccount->save();
                    if (!in_array($billAccount->id, $existingCategoryIds)) {
                        $existingCategoryIds[] = $billAccount->id;
                    }
                }
            }

            // Delete removed categories (but check billable status first)
            $categoriesToDelete = BillAccount::where('ref_id', $expense->id)
                ->where('type', $expense->type) // Dynamic type
                ->whereNotIn('id', $existingCategoryIds)
                ->get();

            foreach ($categoriesToDelete as $categoryToDelete) {
                // Check if billable and linked to invoice
                if ($categoryToDelete->billable == 1 && $categoryToDelete->status == 1) {
                    \DB::rollBack();
                    return redirect()->back()->with('error', __('Cannot delete billable category that is linked to an invoice.'));
                }
                $categoryToDelete->delete();
            }

            // ===================================
            // PROCESS ITEM DETAILS (Product/Service-based)
            // ===================================
            $existingItemIds = [];
            
           if ($request->has('items') && is_array($request->items)) {

                foreach ($request->items as $index => $itemData) {

                    if(empty($itemData['product_id']) || empty($itemData['quantity']) || empty($itemData['price'])){
                        continue;
                    }

                    $billProductId = $itemData['id'] ?? null;
                    $billProduct   = null;

                    // ✅ Update only if item exists AND belongs to this expense
                    if ($billProductId) {
                        $billProduct = BillProduct::where('id', $billProductId)
                                                ->where('bill_id', $expense->id)
                                                ->first();

                        if ($billProduct) {
                            $existingItemIds[] = $billProductId;  // Mark as valid existing
                        }
                    }

                    // ✅ If no existing → create new
                    if (!$billProduct) {
                        $billProduct = new BillProduct();
                        $billProduct->bill_id = $expense->id;
                    }
                    // dd($itemData);
                    // ✅ Assign values
                    $billProduct->product_id  = $itemData['product_id'] ?? null;
                    $billProduct->quantity    = $itemData['quantity'] ?? 1;
                    $billProduct->price       = $itemData['price'] ?? 0;
                    $billProduct->discount    = $itemData['discount'] ?? 0;
                    $billProduct->line_total  = $itemData['quantity'] * $itemData['price'] ?? 0;
                    $billProduct->description = $itemData['description'] ?? '';
                    $billProduct->tax         = !empty($itemData['tax']) ? 1 : 0;
                    $billProduct->billable    = !empty($itemData['billable']) ? 1 : 0;

                    $billProduct->customer_id = $itemData['customer_id'] ?? null;
                    $billProduct->order       = $index;

                    // ✅ Calculate line total
                    $billProduct->line_total =
                        ($billProduct->quantity * $billProduct->price) - $billProduct->discount;

                    $billProduct->save();

                    // Ensure ID is added only once
                    if (!in_array($billProduct->id, $existingItemIds)) {
                        $existingItemIds[] = $billProduct->id;
                    }
                }
            }


            // Delete removed items (but check billable status first)
            $itemsToDelete = BillProduct::where('bill_id', $expense->id)
                ->whereNotIn('id', $existingItemIds)
                ->get();

            foreach ($itemsToDelete as $itemToDelete) {
                // Check if billable and linked to invoice
                if ($itemToDelete->billable == 1 && $itemToDelete->status == 1) {
                    \DB::rollBack();
                    return redirect()->back()->with('error', __('Cannot delete billable item that is linked to an invoice.'));
                }
                $itemToDelete->delete();
            }

            // ===================================
            // UPDATE EXPENSE PAYMENT
            // ===================================
            $expensePayment = BillPayment::where('bill_id', $expense->id)->first();
            $bank = BankAccount::find($request->account_id);

            if (!$expensePayment) {
                $expensePayment = new BillPayment();
                $expensePayment->bill_id = $expense->id;
            }

            $expensePayment->date = $request->payment_date;
            $expensePayment->amount = $request->total ?? 0;
            $expensePayment->account_id = $request->account_id;
            $expensePayment->coa_account = $bank ? $bank->chart_account_id : null;
            $expensePayment->payment_type = $request->payment_type;
            $expensePayment->payment_method = 0;
            $expensePayment->reference = $request->reference ?? '';
            $expensePayment->description = $request->payment_description ?? '';
            $expensePayment->save();

            // ===================================
            // UPDATE JOURNAL ENTRY using JournalService
            // ===================================
            $journalEntry = JournalEntry::where('reference_id', $expense->id)
                ->where('module', 'creditCardCredit')
                ->first();
            if ($journalEntry) {
                // Update existing journal entry
                $this->updateExpenseJournalEntry($expense, $journalEntry);
            } else {
                // Create new journal entry if doesn't exist
                $this->createExpenseJournalEntry($expense);
            }

            // Activity Log
            Utility::makeActivityLog(\Auth::user()->id, 'Credit Card Credit ', $expense->id, 'Update ', 'Credit Card Credit Updated');

            \DB::commit();
            return response()->json(['success' => __('Expense successfully updated.')]);
            
        } catch (\Exception $e) {
            \DB::rollback();
            \Log::error('Expense Update Error: ' . $e->getMessage());
            return response()->json(['error' => __('Error updating expense: ') . $e->getMessage()], 500);
        }
    }


    public function expense($expense_id)
    {

        $settings = Utility::settings();
        try {
            $expenseId = Crypt::decrypt($expense_id);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Bill Not Found.'));
        }
        $expenseId = Crypt::decrypt($expense_id);

        $expense = Bill::where('id', $expenseId)->first();
        $data = DB::table('settings');
        $data = $data->where('created_by', '=', $expense->created_by);
        $data1 = $data->get();

        foreach ($data1 as $row) {
            $settings[$row->name] = $row->value;
        }

        $vendor = $expense->vender;

        $totalTaxPrice = 0;
        $totalQuantity = 0;
        $totalRate = 0;
        $totalDiscount = 0;
        $taxesData = [];
        $items = [];

        foreach ($expense->items as $product) {

            $item = new \stdClass();
            $item->name = !empty($product->product()) ? $product->product()->name : '';
            $item->quantity = $product->quantity;
            $item->tax = $product->tax;
            $item->discount = $product->discount;
            $item->price = $product->price;
            $item->description = $product->description;

            $totalQuantity += $item->quantity;
            $totalRate += $item->price;
            $totalDiscount += $item->discount;

            $taxes = Utility::tax($product->tax);
            $itemTaxes = [];
            if (!empty($item->tax)) {
                foreach ($taxes as $tax) {
                    $taxPrice = Utility::taxRate($tax->rate, $item->price, $item->quantity, $item->discount);
                    $totalTaxPrice += $taxPrice;

                    $itemTax['name'] = $tax->name;
                    $itemTax['rate'] = $tax->rate . '%';
                    $itemTax['price'] = Utility::priceFormat($settings, $taxPrice);
                    $itemTax['tax_price'] = $taxPrice;
                    $itemTaxes[] = $itemTax;

                    if (array_key_exists($tax->name, $taxesData)) {
                        $taxesData[$tax->name] = $taxesData[$tax->name] + $taxPrice;
                    } else {
                        $taxesData[$tax->name] = $taxPrice;
                    }
                }

                $item->itemTax = $itemTaxes;
            } else {
                $item->itemTax = [];
            }
            $items[] = $item;
        }

        $expense->itemData = $items;
        $expense->totalTaxPrice = $totalTaxPrice;
        $expense->totalQuantity = $totalQuantity;
        $expense->totalRate = $totalRate;
        $expense->totalDiscount = $totalDiscount;
        $expense->taxesData = $taxesData;
        $expense->customField = CustomField::getData($expense, 'bill');

        $logo = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $settings_data = \App\Models\Utility::settingsById($expense->created_by);
        $expense_logo = $settings_data['bill_logo'];
        if (isset($expense_logo) && !empty($expense_logo)) {
            $img = Utility::get_file('bill_logo/') . $expense_logo;
        } else {
            $img = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        if ($expense) {
            $color = '#' . $settings['bill_color'];
            $font_color = Utility::getFontColor($color);

            return view('bill.templates.' . $settings['bill_template'], compact('expense', 'color', 'settings', 'vendor', 'img', 'font_color'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function productDestroy(Request $request)
    {

        if (\Auth::user()->can('delete bill product')) {
            $expenseProduct = BillProduct::find($request->id);
            $expense = Bill::find($expenseProduct->bill_id);
            $payment = BillPayment::where('bill_id', $expense->id)->first();

            $productService = ProductService::find($expenseProduct->product_id);
            $b_ac = BillAccount::where('id', '=', $request->account_id)->first();
            $prod_id = TransactionLines::where('reference_id', $payment->voucher_id)->where('product_item_id', $expenseProduct->id)->where('reference', 'Expense Journal')->where('product_type', 'Expense Product')->first();
            if ($b_ac) {
                $prod_account = TransactionLines::where('reference_id', $payment->voucher_id)->where('product_item_id', $b_ac->id)->where('reference', 'Expense Journal')->where('product_type', 'Expense Account')->first();
            } else {
                $prod_account = '';
            }
            $prod_tax = TransactionLines::where('reference_id', $payment->voucher_id)->where('product_item_id', $expenseProduct->id)->where('reference', 'Expense Journal')->where('product_type', 'Expense Tax')->first();
            $inv_receviable = TransactionLines::where('reference_id', $payment->voucher_id)->where('reference', 'Expense Journal')->where('product_type', 'Expense Payable')->first();
            $inv_receviable->credit = $inv_receviable->credit - (@$prod_id->debit + @$prod_tax->debit + @$prod_account->debit);
            $inv_receviable->save();
            $payment->amount = $payment->amount - (@$prod_id->debit + @$prod_tax->debit + @$prod_account->debit);
            $payment->save();
            // dd($inv_receviable,$prod_id,$prod_account,$prod_tax);
            if ($prod_id) {
                @$prod_id->delete();
            }
            if ($prod_account) {
                @$prod_account->delete();
            }
            if ($prod_tax) {
                @$prod_tax->delete();
            }
            TransactionLines::where('reference_sub_id', $productService->id)->where('reference', 'Expense Product')->delete();

            $journal_item = JournalItem::where('journal', $payment->voucher_id)->where('product_ids', $expenseProduct->id)->first();
            if ($b_ac) {
                $journal_account = JournalItem::where('journal', $payment->voucher_id)->where('account', $b_ac->chart_account_id)->where('product_ids', '=', $b_ac->id)->first();
            } else {
                $journal_account = '';
            }
            $journal_tax = JournalItem::where('journal', $payment->voucher_id)->where('prod_tax_id', $expenseProduct->id)->first();
            $types = ChartOfAccountType::where('created_by', '=', $expense->created_by)->where('name', 'Assets')->first();
            if ($types) {
                $sub_type = ChartOfAccountSubType::where('type', $types->id)->where('name', 'Current Asset')->first();
                $account = ChartOfAccount::where('type', $types->id)->where('sub_type', $sub_type->id)->where('name', 'Account Payables')->first();
            }
            if ($account) {
                $item_last = JournalItem::where('journal', $payment->voucher_id)->where('account', $account->id)->first();
                $item_last->credit = $item_last->credit - (@$journal_item->debit + @$journal_tax->debit + @$journal_account->debit);
                $item_last->save();
            } else {
                $item_last = JournalItem::where('journal', $payment->voucher_id)->where('id', $inv_receviable->reference_sub_id)->first();
                $item_last->credit = $item_last->credit - ($journal_item->debit + @$journal_tax->debit + @$journal_account->debit);
                $item_last->save();
            }

            if ($journal_item) {
                @$journal_item->delete();
            }
            if ($journal_account) {
                @$journal_account->delete();
            }
            if ($journal_tax) {
                @$journal_tax->delete();
            }

            Utility::updateUserBalance('vendor', $expense->vender_id, $request->amount, 'credit');

            BillProduct::where('id', '=', $request->id)->delete();
            BillAccount::where('id', '=', $request->account_id)->delete();

            return redirect()->back()->with('success', __('Expense product successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy($id)
    {
        if (\Auth::user()->can('delete bill')) {
            $expense = Bill::find($id);
            if ($expense->created_by == \Auth::user()->creatorId()) {
                
                // ========================================
                // VALIDATION: Check if any billable items are linked to invoices
                // ========================================
                
                // Check ExpenseProducts (BillProducts with expense)
                $billableProducts = BillProduct::where('bill_id', $expense->id)
                    ->where('billable', 1)
                    ->where('status', 1)
                    ->count();
                
                if ($billableProducts > 0) {
                    return redirect()->back()->with('error', __('This expense cannot be deleted as it has billable items linked to invoices. Please remove those items from invoices first.'));
                }
                
                // Check ExpenseAccounts (BillAccounts with expense)
                $billableAccounts = BillAccount::where('ref_id', $expense->id)
                    ->where('type', 'Credit Card Credit ')
                    ->where('billable', 1)
                    ->where('status', 1)
                    ->count();
                
                if ($billableAccounts > 0) {
                    return redirect()->back()->with('error', __('This expense cannot be deleted as it has billable categories linked to invoices. Please remove those categories from invoices first.'));
                }
                
                // ========================================
                // DELETE RELATED RECORDS
                // ========================================
                
                $expensepayments = $expense->payments;

                foreach ($expensepayments as $key => $value) {
                    Utility::bankAccountBalance($value->account_id, $value->amount, 'credit');

                    $expensepayment = BillPayment::find($value->id)->first();
                    if (@$value->voucher_id != 0 || @$value->voucher_id != null) {
                        JournalEntry::where('id', $value->voucher_id)->where('category', 'Credit Card Credit')->delete();
                        JournalItem::where('journal', $value->voucher_id)->delete();
                    }
                    $expensepayment->delete();
                }
                
                // Delete journal entry and related records for this expense
                $journalEntry = JournalEntry::where('category', 'Credit Card Credit')
                    ->where('reference_id', $expense->id)
                    ->where('voucher_type', 'JV')
                    ->first();
                
                if ($journalEntry) {
                    // Delete journal items
                    JournalItem::where('journal', $journalEntry->id)->delete();
                    
                    // Delete transaction lines related to this journal
                    TransactionLines::where('reference_id', $journalEntry->id)
                        ->where('reference', 'Expense Journal')
                        ->delete();
                    
                    // Delete the journal entry itself
                    $journalEntry->delete();
                }
                
                $expense->delete();

                if ($expense->vender_id != 0 && $expense->status != 0) {
                    Utility::updateUserBalance('vendor', $expense->vender_id, $expense->getDue(), 'credit');
                }
                
                // Delete expense products
                BillProduct::where('bill_id', '=', $expense->id)->delete();

                // Delete expense accounts
                BillAccount::where('ref_id', '=', $expense->id)->delete();

                // Delete transaction lines related directly to expense
                TransactionLines::where('product_id', $expense->id)->where('reference', 'Credit Card Credit Journal')->delete();
                TransactionLines::where('reference_id', $expense->id)->where('reference', 'Credit Card Credit Journal')->delete();
                TransactionLines::where('reference_id', $expense->id)->where('reference', 'Credit Card Credit Payment')->delete();
                TransactionLines::where('reference_id', $expense->id)->where('reference', 'Credit Card Credit')->delete();
                TransactionLines::where('reference_id', $expense->id)->where('reference', 'Credit Card Credit Account')->delete();

                return redirect()->route('expense.index')->with('success', __('Expense successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function timeActivityCreate()
    {
        if (\Auth::user()->can('create bill')) {
            $employees = Employee::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $venders = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            
            $customers = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $projects = Project::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('project_name', 'id');

            $services = ProductService::where('created_by', \Auth::user()->creatorId())->where('type', 'service')->get()->pluck('name', 'id');

            return view('expense.create_time_activity', compact('employees', 'venders', 'customers', 'projects', 'services'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function storeTimeActivity(Request $request)
    {
        if (\Auth::user()->can('create bill')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'date' => 'required',
                    'user_id' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $timeActivity = new \App\Models\TimeActivity();
            $timeActivity->user_type = $request->user_type;
            $timeActivity->user_id = $request->user_id;
            $timeActivity->customer_id = $request->customer_id;
            $timeActivity->service_id = $request->service_id;
            $timeActivity->date = $request->date;
            $timeActivity->start_time = $request->start_time;
            $timeActivity->end_time = $request->end_time;
            $timeActivity->duration = $request->duration;
            $timeActivity->break_duration = $request->break_duration;
            $timeActivity->billable = $request->has('billable') ? 1 : 0;
            $timeActivity->rate = $request->rate;
            $timeActivity->taxable = $request->has('taxable') ? 1 : 0;
            $timeActivity->notes = $request->notes;
            $timeActivity->created_by = \Auth::user()->creatorId();
            $timeActivity->save();

            return redirect()->route('timeActivity.create')->with('success', __('Time Activity successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function updateTimeActivity(Request $request, $id)
    {
        if (\Auth::user()->can('edit bill')) {
            $timeActivity = \App\Models\TimeActivity::find($id);
            if ($timeActivity->created_by == \Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'date' => 'required',
                        'user_id' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }

                $timeActivity->user_type = $request->user_type;
                $timeActivity->user_id = $request->user_id;
                $timeActivity->customer_id = $request->customer_id;
                $timeActivity->service_id = $request->service_id;
                $timeActivity->date = $request->date;
                $timeActivity->start_time = $request->start_time;
                $timeActivity->end_time = $request->end_time;
                $timeActivity->duration = $request->duration;
                $timeActivity->break_duration = $request->break_duration;
                $timeActivity->billable = $request->has('billable') ? 1 : 0;
                $timeActivity->rate = $request->rate;
                $timeActivity->taxable = $request->has('taxable') ? 1 : 0;
                $timeActivity->notes = $request->notes;
                $timeActivity->save();

                return redirect()->route('timeActivity.create')->with('success', __('Time Activity successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function checksCreate($Id = 0)
    {
        if (\Auth::user()->can('create bill')) {
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'bill')->get();
            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                ->whereNotIn('type', ['product & service', 'income'])
                ->get()->pluck('name', 'id')->toArray();
            $category = ['__add__' => '➕ Add New category'] + ['' => 'Select Category'] + $category;

            $expense_number = \Auth::user()->expenseNumberFormat($this->expenseNumber());

            $employees = Employee::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
            $employees = ['__add__' => '➕ Add New employee'] + ['' => 'Select Employee'] + $employees;

            $customers = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
            // $customers = ['__add__' => '➕ Add New customer'] + ['' => 'Select Customer'] + $customers;

            $venders = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
            $venders = ['__add__' => '➕ Add New vendor'] + ['' => 'Select Vendor'] + $venders;

            $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $product_services->prepend('Select Item', '');

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');

            $subAccounts = ChartOfAccount::select('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_account_parents.account');
            $subAccounts->leftjoin('chart_of_account_parents', 'chart_of_accounts.parent', 'chart_of_account_parents.id');
            $subAccounts->where('chart_of_accounts.parent', '!=', 0);
            $subAccounts->where('chart_of_accounts.created_by', \Auth::user()->creatorId());
            $subAccounts = $subAccounts->get()->toArray();

            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))
                ->where('created_by', \Auth::user()->creatorId())
                ->get()->pluck('name', 'id')->toArray();
            $accounts = ['__add__' => '➕ Add New Account'] + ['' => 'Select Account'] + $accounts;
            // $customers = Customer::where($column, $ownerId)->get();

            return view('expense.checks', compact('employees', 'customers', 'venders', 'expense_number', 'product_services', 'category', 'customFields', 'Id', 'chartAccounts', 'accounts', 'subAccounts'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    /**
     * Get address for a payee (customer, vendor, or employee)
     */
    public function getPayeeAddress(Request $request)
    {
        $payeeValue = $request->payee;
        
        if (empty($payeeValue)) {
            return response()->json(['address' => '']);
        }

        // Parse the payee value (format: type_id, e.g., "vendor_5")
        $parts = explode('_', $payeeValue, 2);
        if (count($parts) !== 2) {
            return response()->json(['address' => '']);
        }

        $type = $parts[0];
        $id = $parts[1];

        $address = '';

        switch ($type) {
            case 'vendor':
                $vendor = Vender::find($id);
                if ($vendor) {
                    $addressParts = array_filter([
                        $vendor->billing_address,
                        $vendor->billing_address_2,
                        $vendor->billing_city,
                        $vendor->billing_state,
                        $vendor->billing_zip,
                        $vendor->billing_country
                    ]);
                    $address = implode(', ', $addressParts);
                }
                break;
            case 'customer':
                $customer = Customer::find($id);
                if ($customer) {
                    $addressParts = array_filter([
                        $customer->billing_address,
                        $customer->billing_city,
                        $customer->billing_state,
                        $customer->billing_zip,
                        $customer->billing_country
                    ]);
                    $address = implode(', ', $addressParts);
                }
                break;
            case 'employee':
                $employee = Employee::find($id);
                if ($employee) {
                    $address = $employee->address ?? '';
                }
                break;
        }

        return response()->json(['address' => $address]);
    }
}
