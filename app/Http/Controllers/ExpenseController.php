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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Auth;

class ExpenseController extends Controller
{

    public function billNumber()
    {
        $latest = Bill::where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 'Bill')->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->bill_id + 1;
    }

    public function expenseNumber()
    {
        $latest = Bill::where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 'Expense')->latest()->first();
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

            $query = Bill::where('type', '=', 'Expense')
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

            return view('expense.index', compact('expenses', 'vender', 'status', 'category'));
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

            $expense_number = \Auth::user()->expenseNumberFormat($this->expenseNumber());

            $employees = Employee::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
            $employees = ['__add__' => '➕ Add New employee'] + ['' => 'Select Employee'] + $employees;

            $customers = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
            $customers = ['__add__' => '➕ Add New customer'] + ['' => 'Select Customer'] + $customers;

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

            return view('expense.create', compact('employees', 'customers', 'venders', 'expense_number', 'product_services', 'category', 'customFields', 'Id', 'chartAccounts', 'accounts', 'subAccounts'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    // public function store(Request $request)
    // {
    //     \DB::beginTransaction();
    //     try {
    //         if (\Auth::user()->can('create bill')) {

    //             $validator = \Validator::make(
    //                 $request->all(),
    //                 [
    //                     //                    'vender_id' => 'required',
    //                     'payment_date' => 'required',
    //                 ]
    //             );
    //             if ($validator->fails()) {
    //                 $messages3 = $validator->getMessageBag();
    //                 return redirect()->back()->with('error', $messages3->first());
    //             }

    //             if (!empty($request->items) && empty($request->items[0]['item']) && empty($request->items[0]['chart_account_id']) && empty($request->items[0]['amount'])) {
    //                 $itemValidator = \Validator::make(
    //                     $request->all(),
    //                     [
    //                         'item' => 'required',
    //                     ]
    //                 );
    //                 if ($itemValidator->fails()) {
    //                     $messages1 = $itemValidator->getMessageBag();
    //                     return redirect()->back()->with('error', $messages1->first());
    //                 }
    //             }

    //             if (!empty($request->items) && empty($request->items[0]['chart_account_id']) && !empty($request->items[0]['amount'])) {
    //                 $accountValidator = \Validator::make(
    //                     $request->all(),
    //                     [
    //                         'chart_account_id' => 'required',
    //                     ]
    //                 );
    //                 if ($accountValidator->fails()) {
    //                     $messages2 = $accountValidator->getMessageBag();
    //                     return redirect()->back()->with('error', $messages2->first());
    //                 }
    //             }

    //             $expense = new Bill();
    //             $expense->bill_id = $this->expenseNumber();
    //             if ($request->type == 'employee') {
    //                 $expense->vender_id = $request->employee_id;
    //             } elseif ($request->type == 'customer') {
    //                 $expense->vender_id = $request->customer_id;
    //             } else {
    //                 $expense->vender_id = $request->vender_id;
    //             }
    //             $expense->bill_date = $request->payment_date;
    //             $expense->status = 4;
    //             $expense->type = 'Expense';
    //             $expense->user_type = $request->type;
    //             $expense->due_date = $request->payment_date;
    //             $expense->category_id = !empty($request->category_id) ? $request->category_id : 0;
    //             $expense->order_number = 0;
    //             $expense->created_by = \Auth::user()->creatorId();
    //             $expense->owned_by = \Auth::user()->ownedId();
    //             $expense->save();

    //             $products = $request->items;
    //             $newitems = $request->items;
    //             $total_amount = 0;

    //             for ($i = 0; $i < count($products); $i++) {
    //                 if (!empty($products[$i]['item'])) {
    //                     $expenseProduct = new BillProduct();
    //                     $expenseProduct->bill_id = $expense->id;
    //                     $expenseProduct->product_id = $products[$i]['item'];
    //                     $expenseProduct->quantity = $products[$i]['quantity'];
    //                     $expenseProduct->tax = $products[$i]['tax'];
    //                     $expenseProduct->discount = $products[$i]['discount'];
    //                     $expenseProduct->price = $products[$i]['price'];
    //                     $expenseProduct->description = $products[$i]['description'];
    //                     $expenseProduct->save();
    //                     $newitems[$i]['prod_id'] = $expenseProduct->id;
    //                 }

    //                 $expenseTotal = 0;
    //                 if (!empty($products[$i]['chart_account_id'])) {
    //                     $expenseAccount = new BillAccount();
    //                     $expenseAccount->chart_account_id = $products[$i]['chart_account_id'];
    //                     $expenseAccount->price = $products[$i]['amount'] ? $products[$i]['amount'] : 0;
    //                     $expenseAccount->description = $products[$i]['description'];
    //                     $expenseAccount->type = 'Bill';
    //                     $expenseAccount->ref_id = $expense->id;
    //                     $expenseAccount->save();
    //                     $expenseTotal = $expenseAccount->price;
    //                     $newitems[$i]['bill_account_id'] = $expenseAccount->id;
    //                 }

    //                 //inventory management (Quantity)
    //                 if (!empty($expenseProduct)) {
    //                     Utility::total_quantity('plus', $expenseProduct->quantity, $expenseProduct->product_id);
    //                 }

    //                 //Product Stock Redashboardrt
    //                 if (!empty($products[$i]['item'])) {
    //                     $type = 'bill';
    //                     $type_id = $expense->id;
    //                     $description = $products[$i]['quantity'] . '  ' . __('quantity purchase in bill') . ' ' . \Auth::user()->expenseNumberFormat($expense->bill_id);
    //                     Utility::addProductStock($products[$i]['item'], $products[$i]['quantity'], $type, $description, $type_id);
    //                     $total_amount += ($expenseProduct->quantity * $expenseProduct->price) + $expenseTotal;
    //                 }
    //             }
    //             $bank = BankAccount::find($request->account_id);
    //             if ($bank && $bank->chart_account_id != 0 || $bank->chart_account_id != null) {
    //                 $data['account_id'] = $bank->chart_account_id;
    //             } else {
    //                 return redirect()->back()->with('error', __('Please select chart of account in bank account.'));
    //             }

    //             $expensePayment = new BillPayment();
    //             $expensePayment->bill_id = $expense->id;
    //             $expensePayment->date = $request->payment_date;
    //             $expensePayment->amount = $request->totalAmount;
    //             $expensePayment->account_id = $request->account_id;
    //             $expensePayment->payment_method = 0;
    //             $expensePayment->reference = 'NULL';
    //             $expensePayment->description = 'NULL';
    //             $expensePayment->add_receipt = 'NULL';
    //             $expensePayment->save();

    //             if (!empty($request->chart_account_id)) {

    //                 $expenseaccount = ProductServiceCategory::find($request->category_id);
    //                 $chart_account = ChartOfAccount::find($expenseaccount->chart_account_id);
    //                 $expenseAccount = new BillAccount();
    //                 $expenseAccount->chart_account_id = $chart_account['id'];
    //                 $expenseAccount->price = $total_amount;
    //                 $expenseAccount->description = $request->description;
    //                 $expenseAccount->type = 'Bill Category';
    //                 $expenseAccount->ref_id = $expense->id;
    //                 $expenseAccount->save();
    //             }

    //             Utility::bankAccountBalance($request->account_id, $request->totalAmount, 'debit');

    //             Utility::updateUserBalance('vendor', $expense->vender_id, $request->totalAmount, 'credit');

    //             //For Notification
    //             $setting = Utility::settings(\Auth::user()->creatorId());

    //             if ($request->type == 'employee') {
    //                 $user = Employee::find($request->employee_id);
    //                 $contact = $user->phone;
    //             } else if ($request->type == 'customer') {
    //                 $user = Customer::find($request->customer_id);
    //                 $contact = $user->contact;
    //             } else {
    //                 $user = Vender::find($request->vender_id);
    //                 $contact = $user->contact;
    //             }

    //             $bill_products = BillProduct::where('bill_id', $expense->id)->get();
    //             foreach ($bill_products as $bill_product) {
    //                 $product = ProductService::find($bill_product->product_id);
    //                 $totalTaxPrice = 0;
    //                 if ($bill_product->tax != null) {
    //                     $taxes = \App\Models\Utility::tax($bill_product->tax);
    //                     foreach ($taxes as $tax) {
    //                         $taxPrice = \App\Models\Utility::taxRate($tax->rate, $bill_product->price, $bill_product->quantity, $bill_product->discount);
    //                         $totalTaxPrice += $taxPrice;
    //                     }
    //                 }

    //                 $itemAmount = ($bill_product->price * $bill_product->quantity) - ($bill_product->discount) + $totalTaxPrice;

    //                 // $data = [
    //                 //     'account_id' => $product->expense_chartaccount_id,
    //                 //     'transaction_type' => 'Debit',
    //                 //     'transaction_amount' => $itemAmount,
    //                 //     'reference' => 'Expense',
    //                 //     'reference_id' => $expense->id,
    //                 //     'reference_sub_id' => $product->id,
    //                 //     'date' => $expense->bill_date,
    //                 // ];
    //                 // Utility::addTransactionLines($data , 'create');
    //             }

    //             $bill_accounts = BillAccount::where('ref_id', $expense->id)->get();
    //             // foreach ($bill_accounts as $bill_product) {
    //             //     $data = [
    //             //         'account_id' => $bill_product->chart_account_id,
    //             //         'transaction_type' => 'Debit',
    //             //         'transaction_amount' => $bill_product->price,
    //             //         'reference' => 'Expense Account',
    //             //         'reference_id' => $bill_product->ref_id,
    //             //         'reference_sub_id' => $bill_product->id,
    //             //         'date' => $expense->bill_date,
    //             //     ];
    //             //     Utility::addTransactionLines($data , 'create');
    //             // }

    //             $billPayments = BillPayment::where('bill_id', $expense->id)->get();
    //             // foreach ($billPayments as $billPayment) {
    //             //     $accountId = BankAccount::find($billPayment->account_id);

    //             //     $data = [
    //             //         'account_id' => $accountId->chart_account_id,
    //             //         'transaction_type' => 'Debit',
    //             //         'transaction_amount' => $billPayment->amount,
    //             //         'reference' => 'Expense Payment',
    //             //         'reference_id' => $expense->id,
    //             //         'reference_sub_id' => $billPayment->id,
    //             //         'date' => $billPayment->date,
    //             //     ];
    //             //     Utility::addTransactionLines($data , 'create');
    //             // }

    //             // // WorkFlow get which is active
    //             $us_mail = 'false';
    //             $us_notify = 'false';
    //             $us_approve = 'false';
    //             $usr_Notification = [];
    //             $workflow = WorkFlow::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'accounts')->where('status', 1)->first();
    //             if ($workflow) {
    //                 $workflowaction = WorkFlowAction::where('workflow_id', $workflow->id)->where('status', 1)->get();
    //                 foreach ($workflowaction as $action) {
    //                     $useraction = json_decode($action->assigned_users);
    //                     if (strtolower('create-expense') == $action->node_id) {
    //                         // Pick that stage user assign or change on lead
    //                         if (@$useraction != '') {
    //                             $useraction = json_decode($useraction);
    //                             foreach ($useraction as $anyaction) {
    //                                 // make new user array
    //                                 if ($anyaction->type == 'user') {
    //                                     $usr_Notification[] = $anyaction->id;
    //                                 }
    //                             }
    //                         }
    //                         $raw_json = trim($action->applied_conditions, '"');
    //                         $cleaned_json = stripslashes($raw_json);
    //                         $applied_conditions = json_decode($cleaned_json, true);

    //                         if (isset($applied_conditions['conditions']) && is_array($applied_conditions['conditions'])) {
    //                             $arr = [
    //                                 'bill_date' => 'bill_date',
    //                                 'due_date' => 'due_date',
    //                                 'order_number' => 'order_number',
    //                             ];
    //                             $relate = [];
    //                             foreach ($applied_conditions['conditions'] as $conditionGroup) {

    //                                 if (in_array($conditionGroup['action'], ['send_email', 'send_notification', 'send_approval'])) {
    //                                     $query = Bill::where('id', $expense->id);
    //                                     foreach ($conditionGroup['conditions'] as $condition) {
    //                                         $field = $condition['field'];
    //                                         $operator = $condition['operator'];
    //                                         $value = $condition['value'];
    //                                         if (isset($arr[$field], $relate[$arr[$field]])) {
    //                                             $relatedField = strpos($arr[$field], '_') !== false ? explode('_', $arr[$field], 2)[1] : $arr[$field];
    //                                             $relation = $relate[$arr[$field]];

    //                                             // Apply condition to the related model
    //                                             $query->whereHas($relation, function ($relatedQuery) use ($relatedField, $operator, $value) {
    //                                                 $relatedQuery->where($relatedField, $operator, $value);
    //                                             });
    //                                         } else {
    //                                             // Apply condition directly to the contract model
    //                                             $query->where($arr[$field], $operator, $value);
    //                                         }
    //                                     }
    //                                     $result = $query->first();

    //                                     if (!empty($result)) {
    //                                         if ($conditionGroup['action'] === 'send_email') {
    //                                             $us_mail = 'true';
    //                                         } elseif ($conditionGroup['action'] === 'send_notification') {
    //                                             $us_notify = 'true';
    //                                         } elseif ($conditionGroup['action'] === 'send_approval') {
    //                                             $us_approve = 'true';
    //                                         }
    //                                     }
    //                                 }
    //                             }
    //                         }
    //                         if ($us_mail == 'true') {
    //                             // email send
    //                         }
    //                         if ($us_notify == 'true' || $us_approve == 'true') {
    //                             // notification generate
    //                             if (count($usr_Notification) > 0) {
    //                                 $usr_Notification[] = Auth::user()->creatorId();
    //                                 foreach ($usr_Notification as $usrLead) {
    //                                     $data = [
    //                                         "updated_by" => Auth::user()->id,
    //                                         "data_id" => $expense->id,
    //                                         "name" => '',
    //                                     ];
    //                                     if ($us_notify == 'true') {
    //                                         Utility::makeNotification($usrLead, 'create_expanse', $data, $expense->id, 'create Expanse');
    //                                     } elseif ($us_approve == 'true') {
    //                                         Utility::makeNotification($usrLead, 'approve_expanse', $data, $expense->id, 'For Approval Expanse');
    //                                     }
    //                                 }
    //                             }
    //                         }
    //                     }
    //                 }
    //             }

    //             $expenseNotificationArr = [
    //                 'expense_number' => \Auth::user()->expenseNumberFormat($expense->bill_id),
    //                 'user_name' => \Auth::user()->name,
    //                 'bill_date' => $expense->bill_date,
    //                 'bill_due_date' => $expense->due_date,
    //                 'vendor_name' => $user->name,
    //             ];

    //             //Slack Notification
    //             if (isset($setting['bill_notification']) && $setting['bill_notification'] == 1) {
    //                 Utility::send_slack_msg('new_bill', $expenseNotificationArr);
    //             }
    //             //Telegram Notification
    //             if (isset($setting['telegram_bill_notification']) && $setting['telegram_bill_notification'] == 1) {
    //                 Utility::send_telegram_msg('new_bill', $expenseNotificationArr);
    //             }
    //             //Twilio Notification
    //             if (isset($setting['twilio_bill_notification']) && $setting['twilio_bill_notification'] == 1) {
    //                 Utility::send_twilio_msg($contact, 'new_bill', $expenseNotificationArr);
    //             }

    //             $data['id'] = $expense->id;
    //             $data['no'] = $expense->bill_id;
    //             $data['date'] = $expense->bill_date;
    //             $data['created_at'] = date('Y-m-d', strtotime($expense->bill_date)) . ' ' . date('h:i:s');
    //             $data['reference'] = $expense->ref_number;
    //             $data['category'] = 'Expanse';
    //             $data['owned_by'] = $expense->owned_by;
    //             $data['created_by'] = $expense->created_by;
    //             $data['prod_id'] = $expensePayment->id;
    //             $data['amount'] = $expensePayment->amount;
    //             $data['items'] = $newitems;
    //             $data['created_at'] = date('Y-m-d', strtotime($expense->bill_date)) . ' ' . date('h:i:s');
    //             if (preg_match('/\bcash\b/i', $bank->bank_name) || preg_match('/\bcash\b/i', $bank->holder_name)) {
    //                 $dataret  = Utility::cpv_entry($data); // Cash Payment Voucher (CPV)
    //             } else {
    //                 $dataret  = Utility::bpv_entry($data); // Bill Payment Voucher (BPV)
    //             }
    //             $billPayments = BillPayment::find($expensePayment->id);
    //             $billPayments->voucher_id = $dataret;
    //             $billPayments->save();
    //             $expense->voucher_id = $dataret;
    //             $expense->save();

    //             //webhook
    //             $module = 'New Bill';
    //             $webhook = Utility::webhookSetting($module);
    //             if ($webhook) {
    //                 $parameter = json_encode($expense);
    //                 $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);

    //                 if ($status == true) {
    //                     \DB::commit();
    //                     return redirect()->route('expense.index', $expense->id)->with('success', __('Expense successfully created.'));
    //                 } else {
    //                     \DB::commit();
    //                     return redirect()->back()->with('error', __('Webhook call failed.'));
    //                 }
    //             }
    //             \DB::commit();
    //             return redirect()->route('expense.index', $expense->id)->with('success', __('Expense successfully created.'));
    //         } else {
    //             return redirect()->back()->with('error', __('Permission denied.'));
    //         }
    //     } catch (\Exception $e) {
    //         \DB::rollback();
    //         return redirect()->back()->with('error', $e);
    //     }
    // }
    public function store(Request $request)
    {
        \DB::beginTransaction();
        try {
            if (\Auth::user()->can('create bill')) {

                $validator = \Validator::make(
                    $request->all(),
                    [
                        'payment_date' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages3 = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages3->first());
                }

                if (!empty($request->items) && empty($request->items[0]['item']) && empty($request->items[0]['chart_account_id']) && empty($request->items[0]['amount'])) {
                    $itemValidator = \Validator::make(
                        $request->all(),
                        [
                            'item' => 'required',
                        ]
                    );
                    if ($itemValidator->fails()) {
                        $messages1 = $itemValidator->getMessageBag();
                        return redirect()->back()->with('error', $messages1->first());
                    }
                }

                if (!empty($request->items) && empty($request->items[0]['chart_account_id']) && !empty($request->items[0]['amount'])) {
                    $accountValidator = \Validator::make(
                        $request->all(),
                        [
                            'chart_account_id' => 'required',
                        ]
                    );
                    if ($accountValidator->fails()) {
                        $messages2 = $accountValidator->getMessageBag();
                        return redirect()->back()->with('error', $messages2->first());
                    }
                }

                $expense = new Bill();
                $expense->bill_id = $this->expenseNumber();
                if ($request->type == 'employee') {
                    $expense->vender_id = $request->employee_id;
                } elseif ($request->type == 'customer') {
                    $expense->vender_id = $request->customer_id;
                } else {
                    $expense->vender_id = $request->vender_id;
                }
                $expense->bill_date = $request->payment_date;
                $expense->status = 0; // Draft status
                $expense->type = 'Expense';
                $expense->user_type = $request->type;
                $expense->due_date = $request->payment_date;
                $expense->category_id = !empty($request->category_id) ? $request->category_id : 0;
                $expense->order_number = 0;
                $expense->created_by = \Auth::user()->creatorId();
                $expense->owned_by = \Auth::user()->ownedId();
                $expense->save();

                $products = $request->items;
                $newitems = $request->items;
                $total_amount = 0;

                for ($i = 0; $i < count($products); $i++) {
                    if (!empty($products[$i]['item'])) {
                        $expenseProduct = new BillProduct();
                        $expenseProduct->bill_id = $expense->id;
                        $expenseProduct->product_id = $products[$i]['item'];
                        $expenseProduct->quantity = $products[$i]['quantity'];
                        $expenseProduct->tax = $products[$i]['tax'];
                        $expenseProduct->discount = $products[$i]['discount'];
                        $expenseProduct->price = $products[$i]['price'];
                        $expenseProduct->description = $products[$i]['description'];
                        $expenseProduct->save();
                        $newitems[$i]['prod_id'] = $expenseProduct->id;
                    }

                    $expenseTotal = 0;
                    if (!empty($products[$i]['chart_account_id'])) {
                        $expenseAccount = new BillAccount();
                        $expenseAccount->chart_account_id = $products[$i]['chart_account_id'];
                        $expenseAccount->price = $products[$i]['amount'] ? $products[$i]['amount'] : 0;
                        $expenseAccount->description = $products[$i]['description'];
                        $expenseAccount->type = 'Bill';
                        $expenseAccount->ref_id = $expense->id;
                        $expenseAccount->save();
                        $expenseTotal = $expenseAccount->price;
                        $newitems[$i]['bill_account_id'] = $expenseAccount->id;
                    }

                    // Inventory management (Quantity)
                    if (!empty($expenseProduct)) {
                        Utility::total_quantity('plus', $expenseProduct->quantity, $expenseProduct->product_id);
                    }

                    // Product Stock Report
                    if (!empty($products[$i]['item'])) {
                        $type = 'bill';
                        $type_id = $expense->id;
                        $description = $products[$i]['quantity'] . '  ' . __('quantity purchase in bill') . ' ' . \Auth::user()->expenseNumberFormat($expense->bill_id);
                        Utility::addProductStock($products[$i]['item'], $products[$i]['quantity'], $type, $description, $type_id);

                        $total_amount = (float)$total_amount + ((float)$expenseProduct->quantity * (float)$expenseProduct->price) + (float)$expenseTotal;

                    }
                }

                $bank = BankAccount::find($request->account_id);
                if ($bank && $bank->chart_account_id != 0 || $bank->chart_account_id != null) {
                    $data['account_id'] = $bank->chart_account_id;
                } else {
                    return redirect()->back()->with('error', __('Please select chart of account in bank account.'));
                }

                // Store payment details but don't process yet
                $expensePayment = new BillPayment();
                $expensePayment->bill_id = $expense->id;
                $expensePayment->date = $request->payment_date;
                $expensePayment->amount = $request->totalAmount;
                $expensePayment->account_id = $request->account_id;
                $expensePayment->payment_method = 0;
                $expensePayment->reference = 'NULL';
                $expensePayment->description = 'NULL';
                $expensePayment->add_receipt = 'NULL';
                $expensePayment->save();

                if (!empty($request->chart_account_id)) {
                    $expenseaccount = ProductServiceCategory::find($request->category_id);
                    $chart_account = ChartOfAccount::find($expenseaccount->chart_account_id);
                    $expenseAccount = new BillAccount();
                    $expenseAccount->chart_account_id = $chart_account['id'];
                    $expenseAccount->price = $total_amount;
                    $expenseAccount->description = $request->description;
                    $expenseAccount->type = 'Bill Category';
                    $expenseAccount->ref_id = $expense->id;
                    $expenseAccount->save();
                }

                // DON'T process bank balance and user balance yet - wait for approval
                // Utility::bankAccountBalance($request->account_id, $request->totalAmount, 'debit');
                // Utility::updateUserBalance('vendor', $expense->vender_id, $request->totalAmount, 'credit');

                // For Notification
                $setting = Utility::settings(\Auth::user()->creatorId());

                if ($request->type == 'employee') {
                    $user = Employee::find($request->employee_id);
                    $contact = $user->phone;
                } else if ($request->type == 'customer') {
                    $user = Customer::find($request->customer_id);
                    $contact = $user->contact;
                } else {
                    $user = Vender::find($request->vender_id);
                    $contact = $user->contact;
                }

                // DON'T create transaction lines yet - wait for approval
                // $bill_products = BillProduct::where('bill_id', $expense->id)->get();
                // foreach ($bill_products as $bill_product) {
                //     ... transaction line code
                // }

                // WorkFlow - Check if approval is required
                $us_mail = 'false';
                $us_notify = 'false';
                $us_approve = 'false';
                $usr_Notification = [];
                $workflow = WorkFlow::where('created_by', '=', \Auth::user()->creatorId())
                    ->where('module', '=', 'accounts')
                    ->where('status', 1)
                    ->first();

                if ($workflow) {
                    $workflowaction = WorkFlowAction::where('workflow_id', $workflow->id)
                        ->where('status', 1)
                        ->get();

                    foreach ($workflowaction as $action) {
                        $useraction = json_decode($action->assigned_users);
                        if (strtolower('create-expense') == $action->node_id) {
                            if (@$useraction != '') {
                                $useraction = json_decode($useraction);
                                foreach ($useraction as $anyaction) {
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
                                    'bill_date' => 'bill_date',
                                    'due_date' => 'due_date',
                                    'order_number' => 'order_number',
                                ];
                                $relate = [];

                                foreach ($applied_conditions['conditions'] as $conditionGroup) {
                                    if (in_array($conditionGroup['action'], ['send_email', 'send_notification', 'send_approval'])) {
                                        $query = Bill::where('id', $expense->id);
                                        foreach ($conditionGroup['conditions'] as $condition) {
                                            $field = $condition['field'];
                                            $operator = $condition['operator'];
                                            $value = $condition['value'];
                                            if (isset($arr[$field], $relate[$arr[$field]])) {
                                                $relatedField = strpos($arr[$field], '_') !== false ? explode('_', $arr[$field], 2)[1] : $arr[$field];
                                                $relation = $relate[$arr[$field]];

                                                $query->whereHas($relation, function ($relatedQuery) use ($relatedField, $operator, $value) {
                                                    $relatedQuery->where($relatedField, $operator, $value);
                                                });
                                            } else {
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
                                if (count($usr_Notification) > 0) {
                                    $usr_Notification[] = Auth::user()->creatorId();
                                    foreach ($usr_Notification as $usrLead) {
                                        $data = [
                                            "updated_by" => Auth::user()->id,
                                            "data_id" => $expense->id,
                                            "name" => '',
                                        ];
                                        if ($us_notify == 'true') {
                                            Utility::makeNotification($usrLead, 'create_expanse', $data, $expense->id, 'create Expanse');
                                            $expense->status = 5; // Under Approval
                                            $expense->save();
                                        } elseif ($us_approve == 'true') {
                                            Utility::makeNotification($usrLead, 'approve_expanse', $data, $expense->id, 'For Approval Expanse');
                                            $expense->status = 5; // Under Approval
                                            $expense->save();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $expenseNotificationArr = [
                    'expense_number' => \Auth::user()->expenseNumberFormat($expense->bill_id),
                    'user_name' => \Auth::user()->name,
                    'bill_date' => $expense->bill_date,
                    'bill_due_date' => $expense->due_date,
                    'vendor_name' => $user->name,
                ];

                // Slack Notification
                if (isset($setting['bill_notification']) && $setting['bill_notification'] == 1) {
                    Utility::send_slack_msg('new_bill', $expenseNotificationArr);
                }
                // Telegram Notification
                if (isset($setting['telegram_bill_notification']) && $setting['telegram_bill_notification'] == 1) {
                    Utility::send_telegram_msg('new_bill', $expenseNotificationArr);
                }
                // Twilio Notification
                if (isset($setting['twilio_bill_notification']) && $setting['twilio_bill_notification'] == 1) {
                    Utility::send_twilio_msg($contact, 'new_bill', $expenseNotificationArr);
                }

                // DON'T create voucher yet - wait for approval
                // The voucher (CPV/BPV) will be created in the approveExpense method
                if(Auth::user()->type == 'company')
                {
                    $this->createExpenseVoucher($expense);
                    $expense->status = 6;
                    $expense->save();
                    Utility::makeActivityLog(\Auth::user()->id, 'Expense', $expense->id, 'Create Expense', 'Expense Created & Approved');

                }
                // Webhook
                $module = 'New Bill';
                $webhook = Utility::webhookSetting($module);
                if ($webhook) {
                    $parameter = json_encode($expense);
                    $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);

                    if ($status == true) {
                        \DB::commit();
                        return redirect()->route('expense.index', $expense->id)->with('success', __('Expense successfully created and waiting for approval.'));
                    } else {
                        \DB::commit();
                        return redirect()->back()->with('error', __('Webhook call failed.'));
                    }
                }

                Utility::makeActivityLog(\Auth::user()->id, 'Expense', $expense->id, 'Create Expense', 'Expense Created (Pending Approval)');
                // dd($expense);
                $expense->save();
                \DB::commit();
                return redirect()->route('expense.index', $expense->id)->with('success', __('Expense successfully created and waiting for approval.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } catch (\Exception $e) {
            dd($e);
            \DB::rollback();
            dd($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function createExpenseVoucher(Bill $expense)
    {
        $expenseProducts = BillProduct::where('bill_id', $expense->id)->get();
        $expensePayment = BillPayment::where('bill_id', $expense->id)->first();
        $bank = BankAccount::find($expensePayment->account_id);

        $newitems = [];
        foreach ($expenseProducts as $product) {
            $newitems[] = [
                'prod_id' => $product->id,
                'item' => $product->product_id,
                'quantity' => $product->quantity,
                'price' => $product->price,
                'discount' => $product->discount,
                'itemTaxPrice' => $product->tax,
                'description' => $product->description,
            ];
        }
        //bill accounts
        $bill_accounts = BillAccount::where('ref_id', $expense->id)->get();
        foreach ($bill_accounts as $account) {
            $newitems[] = [
                'prod_id' => $account->id,
                'chart_account_id' => $account->chart_account_id,
                'amount' => $account->price,
                'description' => $account->description,
                'itemTaxPrice' => 0,
            ];
        }
        $data = [
            'id' => $expense->id,
            'no' => $expense->bill_id,
            'date' => $expense->bill_date,
            'created_at' => date('Y-m-d H:i:s', strtotime($expense->bill_date)),
            'reference' => $expense->ref_number,
            'category' => 'Expanse',
            'owned_by' => $expense->owned_by,
            'created_by' => $expense->created_by,
            'prod_id' => $expensePayment->id,
            'amount' => $expensePayment->amount,
            'items' => $newitems,
            'account_id' => $bank->chart_account_id,
        ];
        // dd($data);
        // Create CPV or BPV based on bank account
        if (preg_match('/\bcash\b/i', $bank->bank_name) || preg_match('/\bcash\b/i', $bank->holder_name)) {
            $voucherId = Utility::cpv_entry($data); // Cash Payment Voucher (CPV)
        } else {
            $voucherId = Utility::bpv_entry($data); // Bank Payment Voucher (BPV)
        }

        // Update payment with voucher ID
        $expensePayment->voucher_id = $voucherId;
        $expensePayment->save();

        // Update expense with voucher ID
        $expense->voucher_id = $voucherId;
        $expense->save();

        return $voucherId;
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

            // Create transaction lines
            $bill_products = BillProduct::where('bill_id', $expense->id)->get();
            foreach ($bill_products as $bill_product) {
                $product = ProductService::find($bill_product->product_id);
                $totalTaxPrice = 0;
                if ($bill_product->tax != null) {
                    $taxes = \App\Models\Utility::tax($bill_product->tax);
                    foreach ($taxes as $tax) {
                        $taxPrice = \App\Models\Utility::taxRate($tax->rate, $bill_product->price, $bill_product->quantity, $bill_product->discount);
                        $totalTaxPrice += $taxPrice;
                    }
                }

                $itemAmount = ($bill_product->price * $bill_product->quantity) - ($bill_product->discount) + $totalTaxPrice;

                $data = [
                    'account_id' => $product->expense_chartaccount_id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $itemAmount,
                    'reference' => 'Expense',
                    'reference_id' => $expense->id,
                    'reference_sub_id' => $product->id,
                    'date' => $expense->bill_date,
                ];
                Utility::addTransactionLines($data, 'create');
            }

            // Create transaction lines for bill accounts
            $bill_accounts = BillAccount::where('ref_id', $expense->id)->get();
            foreach ($bill_accounts as $bill_account) {
                $data = [
                    'account_id' => $bill_account->chart_account_id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $bill_account->price,
                    'reference' => 'Expense Account',
                    'reference_id' => $bill_account->ref_id,
                    'reference_sub_id' => $bill_account->id,
                    'date' => $expense->bill_date,
                ];
                Utility::addTransactionLines($data, 'create');
            }

            // Create transaction lines for payment
            $accountId = BankAccount::find($expensePayment->account_id);
            $data = [
                'account_id' => $accountId->chart_account_id,
                'transaction_type' => 'Credit',
                'transaction_amount' => $expensePayment->amount,
                'reference' => 'Expense Payment',
                'reference_id' => $expense->id,
                'reference_sub_id' => $expensePayment->id,
                'date' => $expensePayment->date,
            ];
            Utility::addTransactionLines($data, 'create');
            if($expense->voucher_id != null){
                return redirect()->route('expense.index')->with('success', __('Expense approved successfully and voucher posted.'));   
            }
            // Create voucher (CPV or BPV)
            $voucherId = $this->createExpenseVoucher($expense);
            $expense->voucher_id = $voucherId;
            // Update status to Approved
            $expense->status = 4;
            $expense->save();

            Utility::makeActivityLog(\Auth::user()->id, 'Expense', $expense->id, 'Approve Expense', 'Expense approved and voucher posted');

            // Send notification to expense creator
            $data = [
                "updated_by" => \Auth::user()->id,
                "data_id" => $expense->id,
                "name" => '',
            ];
            Utility::makeNotification($expense->created_by, 'expense_approved', $data, $expense->id, 'Expense Approved');

            \DB::commit();
            return redirect()->route('expense.index')->with('success', __('Expense approved successfully and voucher posted.'));
        } catch (\Exception $e) {
            \DB::rollBack();
            dd($e);
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
            $expense = Bill::find($id);

            $bankAccount = BillPayment::where('bill_id', $id)->first();

            $bankAccount = BankAccount::find($bankAccount->account_id);

            if (!empty($expense)) {
                $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())
                    ->whereNotIn('type', ['product & service', 'income'])
                    ->get()->pluck('name', 'id')->toArray();
                $category = ['__add__' => '➕ Add New category'] + ['' => 'Select Category'] + $category;
                $expense_number = \Auth::user()->expenseNumberFormat($expense->bill_id);

                $venders = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
                $venders = ['__add__' => '➕ Add New vendor'] + ['' => 'Select Vendor'] + $venders;

                $employees = Employee::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
                $employees = ['__add__' => '➕ Add New employee'] + ['' => 'Select Employee'] + $employees;

                $customers = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
                $customers = ['__add__' => '➕ Add New customer'] + ['' => 'Select Customer'] + $customers;

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

                return view('expense.edit', compact(
                    'employees',
                    'customers',
                    'venders',
                    'product_services',
                    'expense',
                    'expense_number',
                    'category',
                    'bank_Account',
                    'chartAccounts',
                    'items',
                    'bankAccount',
                    'subAccounts'
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
        \DB::beginTransaction();
        try {
            if (\Auth::user()->can('edit bill')) {
                $expense = Bill::find($id);

                if ($expense->created_by == \Auth::user()->creatorId()) {

                    $validator = \Validator::make(
                        $request->all(),
                        [
                            //                        'vender_id' => 'required',
                            'bill_date' => 'required',
                        ]
                    );
                    if ($validator->fails()) {
                        $messages = $validator->getMessageBag();

                        return redirect()->route('expense.index')->with('error', $messages->first());
                    }
                    $expense->vender_id = $request->vender_id;

                    if ($request->type == 'employee') {
                        $expense->vender_id = $request->employee_id;
                    } elseif ($request->type == 'customer') {
                        $expense->vender_id = $request->customer_id;
                    } else {
                        $expense->vender_id = $request->vender_id;
                    }

                    $expense->bill_date = $request->bill_date;
                    $expense->due_date = $request->bill_date;
                    $expense->order_number = 0;
                    $expense->category_id = $request->category_id;
                    $expense->save();
                    $products = $request->items;

                    $total_amount = 0;

                    for ($i = 0; $i < count($products); $i++) {
                        $expenseProduct = BillProduct::find($products[$i]['id']);
                        if ($expenseProduct == null) {
                            $expenseProduct = new BillProduct();
                            $expenseProduct->bill_id = $expense->id;

                            if (isset($products[$i]['items'])) {
                                Utility::total_quantity('plus', $products[$i]['quantity'], $products[$i]['items']);
                            }

                            $updatePrice = ($products[$i]['price'] * $products[$i]['quantity']) + ($products[$i]['itemTaxPrice']) - ($products[$i]['discount']);
                            // Utility::updateUserBalance('vendor', $request->vender_id, $updatePrice, 'debit');

                        } else {

                            Utility::total_quantity('minus', $expenseProduct->quantity, $expenseProduct->product_id);
                        }

                        if (isset($products[$i]['items'])) {
                            $expenseProduct->product_id = $products[$i]['items'];
                            $expenseProduct->quantity = $products[$i]['quantity'];
                            $expenseProduct->tax = $products[$i]['tax'];
                            $expenseProduct->discount = $products[$i]['discount'];
                            $expenseProduct->price = $products[$i]['price'];
                            $expenseProduct->description = $products[$i]['description'];
                            $expenseProduct->save();
                        }

                        $expenseTotal = 0;
                        if (!empty($products[$i]['chart_account_id'])) {
                            $expenseAccount = BillAccount::find($products[$i]['account_id']);

                            if ($expenseAccount == null) {
                                $expenseAccount = new BillAccount();
                                $expenseAccount->chart_account_id = $products[$i]['chart_account_id'];
                            } else {
                                $expenseAccount->chart_account_id = $products[$i]['chart_account_id'];
                            }
                            $expenseAccount->price = $products[$i]['amount'] ? $products[$i]['amount'] : 0;
                            $expenseAccount->description = $products[$i]['description'];
                            $expenseAccount->type = 'Expense';
                            $expenseAccount->ref_id = $expense->id;
                            $expenseAccount->save();
                            $expenseTotal = $expenseAccount->price;
                        }

                        if ($products[$i]['id'] > 0) {
                            Utility::total_quantity('plus', $products[$i]['quantity'], $expenseProduct->product_id);
                        }

                        //Product Stock Report
                        $type = 'bill';
                        $type_id = $expense->id;
                        StockReport::where('type', '=', 'bill')->where('type_id', '=', $expense->id)->delete();
                        $description = $products[$i]['quantity'] . '  ' . __(' quantity purchase in bill') . ' ' . \Auth::user()->expenseNumberFormat($expense->bill_id);

                        if (isset($products[$i]['items'])) {
                            Utility::addProductStock($products[$i]['items'], $products[$i]['quantity'], $type, $description, $type_id);
                        }

                        $total_amount += ($expenseProduct->quantity * $expenseProduct->price) + $expenseTotal;
                    }

                    $expensePayment = BillPayment::where('bill_id', $expense->id)->first();

                    Utility::bankAccountBalance($expensePayment->account_id, $expensePayment->amount, 'credit');
                    Utility::bankAccountBalance($request->account_id, $request->totalAmount, 'debit');
                    Utility::updateUserBalance('vendor', $expense->vender_id, $request->totalAmount, 'credit');

                    if ($expensePayment == null) {
                        $expensePayment = new BillPayment();
                        $expensePayment->bill_id = $expense->id;
                    } else {
                        $expensePayment->bill_id = $expense->id;
                    }

                    $expensePayment->date = $request->bill_date;
                    $expensePayment->amount = $request->totalAmount;
                    $expensePayment->account_id = $request->account_id;
                    $expensePayment->payment_method = 0;
                    $expensePayment->reference = 'NULL';
                    $expensePayment->description = 'NULL';
                    $expensePayment->add_receipt = 'NULL';
                    $expensePayment->save();

                    if (!empty($request->chart_account_id)) {
                        $expenseaccount = ProductServiceCategory::find($request->category_id);
                        $chart_account = ChartOfAccount::find($expenseaccount->chart_account_id);
                        $expenseAccount = new BillAccount();
                        $expenseAccount->chart_account_id = $chart_account['id'];
                        $expenseAccount->price = $total_amount;
                        $expenseAccount->description = $request->description;
                        $expenseAccount->type = 'Bill Category';
                        $expenseAccount->ref_id = $expense->id;
                        $expenseAccount->save();
                    }

                    $voucher = JournalEntry::where('id', $expensePayment->voucher_id)->where('reference_id', $expense->id)->first();
                    if ($voucher) {
                        JournalItem::where('journal', $voucher->id)->delete();
                        $prod_id = TransactionLines::where('reference_id', $expensePayment->voucher_id)->where('reference', 'Expense Journal')->where('product_type', 'Expense Product')->delete();
                        $prod_tax = TransactionLines::where('reference_id', $expensePayment->voucher_id)->where('reference', 'Expense Journal')->where('product_type', 'Expense Tax')->delete();
                        $prod_account = TransactionLines::where('reference_id', $expensePayment->voucher_id)->where('reference', 'Expense Journal')->where('product_type', 'Expense Account')->delete();
                        $inv_receviable = TransactionLines::where('reference_id', $expensePayment->voucher_id)->where('reference', 'Expense Journal')->where('product_type', 'Expense Payable')->delete();
                    }
                    $bill_products = BillProduct::where('bill_id', $expense->id)->get();
                    $tax = 0;
                    $payable = 0;
                    foreach ($bill_products as $bill_product) {
                        $product = ProductService::find($bill_product->product_id);
                        $totalTaxPrice = 0;
                        if ($bill_product->tax != null) {

                            $taxes = \App\Models\Utility::tax($bill_product->tax);
                            foreach ($taxes as $tax) {
                                $taxPrice = \App\Models\Utility::taxRate($tax->rate, $bill_product->price, $bill_product->quantity, $bill_product->discount);
                                $totalTaxPrice += $taxPrice;
                            }
                        }

                        $itemAmount = ($bill_product->price * $bill_product->quantity) - ($bill_product->discount) + $totalTaxPrice;

                        // $data = [
                        //     'account_id' => $product->expense_chartaccount_id,
                        //     'transaction_type' => 'Debit',
                        //     'transaction_amount' => $itemAmount,
                        //     'reference' => 'Expense',
                        //     'reference_id' => $expense->id,
                        //     'reference_sub_id' => $product->id,
                        //     'date' => $expense->bill_date,
                        // ];
                        // Utility::addTransactionLines($data, 'edit');

                        $journalItem = new JournalItem();
                        $journalItem->journal = $voucher->id;
                        $journalItem->account = @$product->expense_chartaccount_id;
                        $journalItem->product_ids = @$bill_product->id;
                        $journalItem->description = @$bill_product->description;
                        $journalItem->debit = (($bill_product->quantity * $bill_product->price) - $bill_product->discount);
                        $journalItem->credit = 0;
                        $journalItem->save();
                        $journalItem->created_at = date('Y-m-d H:i:s', strtotime($expense->created_at));
                        $journalItem->updated_at = date('Y-m-d H:i:s', strtotime($expense->created_at));
                        $journalItem->save();
                        // calculate tax manul function
                        $tax_rate = Tax::where('id', $bill_product->tax)->first();
                        if ($tax_rate) {
                            $tax = ($tax_rate->rate / 100) * (($bill_product->price * $bill_product->quantity) - $bill_product->discount);
                        } else {
                            $tax = 0;
                        }
                        $payable += ((floatval($bill_product->quantity) * floatval($bill_product->price)) - floatval($bill_product->discount)) + floatval($tax);

                        $dataline = [
                            'account_id' => $product->expense_chartaccount_id,
                            'transaction_type' => 'Debit',
                            'transaction_amount' => $journalItem->debit,
                            'reference' => 'Expense Journal',
                            'reference_id' => $expensePayment->voucher_id,
                            'reference_sub_id' => $journalItem->id,
                            'date' => $expense->bill_date,
                            'created_at' => date('Y-m-d H:i:s', strtotime($expensePayment->created_at)),
                            'product_id' => $expense->id,
                            'product_type' => 'Expense Product',
                            'product_item_id' => $bill_product->id,
                        ];
                        Utility::addTransactionLines($dataline, 'create');

                        if ($tax != 0) {
                            $accounttax = Tax::where('id', $product->tax_id)->first();
                            $account_tax = ChartOfAccount::where('id', $accounttax->account_id)->first();
                            if (!$account_tax) {
                                $types_t = ChartOfAccountType::where('created_by', '=', $expense->created_by)->where('name', 'Liabilities')->first();
                                if ($types_t) {
                                    $sub_type_t = ChartOfAccountSubType::where('type', $types_t->id)->where('name', 'Current Liabilities')->first();
                                    $account_tax = ChartOfAccount::where('type', $types_t->id)->where('sub_type', $sub_type_t->id)->where('name', 'TAX')->first();
                                    if (!$account_tax) {
                                        $account_tax = ChartOfAccount::create([
                                            'name' => 'TAX',
                                            'code' => '10000',
                                            'type' => $types_t->id,
                                            'sub_type' => $sub_type_t->id,
                                            'is_enabled' => 1,
                                            'created_by' => $expense->created_by,
                                        ]);
                                    }
                                }
                            }

                            if ($account_tax) {
                                $journalItem = new JournalItem();
                                $journalItem->journal = $voucher->id;
                                $journalItem->account = @$account_tax->id;
                                $journalItem->prod_tax_id = $bill_product->id;
                                $journalItem->description = 'Tax on Bill No : ' . @$expense->bill_no;
                                $journalItem->debit = $tax;
                                $journalItem->credit = 0;
                                $journalItem->save();
                                $journalItem->created_at = date('Y-m-d H:i:s', strtotime($expense->created_at));
                                $journalItem->updated_at = date('Y-m-d H:i:s', strtotime($expense->created_at));
                                $journalItem->save();

                                $dataline = [
                                    'account_id' => $account_tax->id,
                                    'transaction_type' => 'Debit',
                                    'transaction_amount' => $journalItem->debit,
                                    'reference' => 'Expense Journal',
                                    'reference_id' => $expensePayment->voucher_id,
                                    'reference_sub_id' => $journalItem->id,
                                    'date' => $expense->bill_date,
                                    'created_at' => date('Y-m-d H:i:s', strtotime($expense->created_at)),
                                    'product_id' => $expense->id,
                                    'product_type' => 'Expense Tax',
                                    'product_item_id' => $bill_product->id,
                                ];
                                Utility::addTransactionLines($dataline, 'create');
                            }
                        }

                    }

                    $bill_accounts = BillAccount::where('ref_id', $expense->id)->get();
                    foreach ($bill_accounts as $bill_product) {
                        // $data = [
                        //     'account_id' => $bill_product->chart_account_id,
                        //     'transaction_type' => 'Debit',
                        //     'transaction_amount' => $bill_product->price,
                        //     'reference' => 'Expense Account',
                        //     'reference_id' => $bill_product->ref_id,
                        //     'reference_sub_id' => $bill_product->id,
                        //     'date' => $expense->bill_date,
                        // ];
                        // Utility::addTransactionLines($data, 'edit');
                        $journalItem = new JournalItem();
                        $journalItem->journal = $voucher->id;
                        $journalItem->account = $bill_product->chart_account_id;
                        $journalItem->product_ids = $bill_product->id;
                        $journalItem->description = $bill_product->description;
                        $journalItem->debit = $bill_product->price;
                        $journalItem->credit = 0;
                        $journalItem->save();
                        $journalItem->created_at = date('Y-m-d H:i:s', strtotime($expense->created_at));
                        $journalItem->updated_at = date('Y-m-d H:i:s', strtotime($expense->created_at));
                        $journalItem->save();
                        $dataline = [
                            'account_id' => $journalItem->account,
                            'transaction_type' => 'Debit',
                            'transaction_amount' => $journalItem->debit,
                            'reference' => 'Expense Journal',
                            'reference_id' => $expense->id,
                            'reference_sub_id' => $journalItem->id,
                            'date' => $expense->bill_date,
                            'created_at' => date('Y-m-d H:i:s', strtotime($expense->created_at)),
                            'product_id' => $expense->id,
                            'product_type' => 'Expense Account',
                            'product_item_id' => $bill_product->id,
                        ];
                        Utility::addTransactionLines($dataline, 'create');
                        $payable += $bill_product->price;
                    }

                    $billPayments = BillPayment::where('bill_id', $expense->id)->get();
                    foreach ($billPayments as $billPayment) {
                        $accountId = BankAccount::find($billPayment->account_id);

                        // $data = [
                        //     'account_id' => $accountId->chart_account_id,
                        //     'transaction_type' => 'Debit',
                        //     'transaction_amount' => $billPayment->amount,
                        //     'reference' => 'Expense Payment',
                        //     'reference_id' => $expense->id,
                        //     'reference_sub_id' => $billPayment->id,
                        //     'date' => $billPayment->date,
                        // ];
                        // Utility::addTransactionLines($data, 'edit');
                        $types = ChartOfAccountType::where('created_by', '=', $expense->created_by)->where('name', 'Liabilities')->first();
                        if ($types) {
                            $sub_type = ChartOfAccountSubType::where('type', $types->id)->where('name', 'Current Liabilities')->first();
                            $account = ChartOfAccount::where('type', $types->id)->where('sub_type', $sub_type->id)->where('name', 'Account Payable')->first();
                        }
                        $journalItem = new JournalItem();
                        $journalItem->journal = $voucher->id;
                        $journalItem->account = $account->id;
                        $journalItem->description = 'Account Payable on Expense No : ' . $expense->bill_no;
                        $journalItem->debit = 0;
                        $journalItem->credit = $payable;
                        $journalItem->save();
                        $journalItem->created_at = date('Y-m-d H:i:s', strtotime($expense->created_at));
                        $journalItem->updated_at = date('Y-m-d H:i:s', strtotime($expense->created_at));
                        $journalItem->save();

                        $dataline = [
                            'account_id' => $account->id,
                            'transaction_type' => 'Credit',
                            'transaction_amount' => $journalItem->credit,
                            'reference' => 'Expense Journal',
                            'reference_id' => $voucher->id,
                            'reference_sub_id' => $journalItem->id,
                            'date' => $expense->bill_date,
                            'created_at' => date('Y-m-d H:i:s', strtotime($expense->created_at)),
                            'product_id' => $expense->id,
                            'product_type' => 'Expense Payable',
                            'product_item_id' => 0,
                        ];
                        Utility::addTransactionLines($dataline, 'create');
                    }

                    Utility::makeActivityLog(\Auth::user()->id, 'Expense', $expense->id, 'Update Expense', $expense->type);
                    \DB::commit();
                    return redirect()->route('expense.index')->with('success', __('Expense successfully updated.'));
                } else {
                    return redirect()->back()->with('error', __('Permission denied.'));
                }
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } catch (\Exception $e) {
            \DB::rollback();
            return redirect()->back()->with('error', $e);
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
                $expensepayments = $expense->payments;

                foreach ($expensepayments as $key => $value) {
                    Utility::bankAccountBalance($value->account_id, $value->amount, 'credit');

                    $expensepayment = BillPayment::find($value->id)->first();
                    if (@$value->voucher_id != 0 || @$value->voucher_id != null) {
                        JournalEntry::where('id', $value->voucher_id)->where('category', 'Expense')->delete();
                        JournalItem::where('journal', $value->voucher_id)->delete();
                    }
                    $expensepayment->delete();
                }
                if (@$expense->voucher_id != 0 || @$expense->voucher_id != null) {
                    JournalEntry::where('id', $expense->voucher_id)->where('category', 'Expense')->delete();
                    JournalItem::where('journal', $expense->voucher_id)->delete();
                }
                $expense->delete();

                if ($expense->vender_id != 0 && $expense->status != 0) {
                    Utility::updateUserBalance('vendor', $expense->vender_id, $expense->getDue(), 'credit');
                }
                BillProduct::where('bill_id', '=', $expense->id)->delete();

                BillAccount::where('ref_id', '=', $expense->id)->delete();

                TransactionLines::where('product_id', $expense->id)->where('reference', 'Expense Journal')->delete();
                TransactionLines::where('reference_id', $expense->id)->where('reference', 'Expense Journal')->delete();
                TransactionLines::where('reference_id', $expense->id)->where('reference', 'Expense Payment')->delete();
                TransactionLines::where('reference_id', $expense->id)->where('reference', 'Expense')->delete();
                TransactionLines::where('reference_id', $expense->id)->where('reference', 'Expense Account')->delete();

                return redirect()->route('expense.index')->with('success', __('Expense successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
