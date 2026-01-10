<?php

namespace App\Http\Controllers;

use App\Exports\BillExport;
use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\BillAccount;
use App\Models\BillPayment;
use App\Models\BillProduct;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountSubType;
use App\Models\ChartOfAccountType;
use App\Models\CustomField;
use App\Models\Customer;
use App\Models\DebitNote;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Settings;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\StockReport;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Utility;
use App\Models\Vender;
use App\Models\WorkFlow;
use App\Models\Notification;
use App\Models\Tax;
use App\Models\WorkFlowAction;
use App\Models\TransactionLines;
use App\Services\JournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Auth;

use App\Models\PaymentTerm;

class BillController extends Controller
{

    // public function index(Request $request)
    // {
    //     if (\Auth::user()->can('manage bill')) {
    //         $user = \Auth::user();
    //         $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
    //         $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
    //         $vender = Vender::where($column, '=', $ownerId)->get()->pluck('name', 'id');
    //         $vender->prepend('Select Vendor', '');

    //         $status = Bill::$statues;

    //         $query = Bill::where('type', '=', 'Bill')->where($column, '=', $ownerId);
    //         if (!empty($request->vender)) {
    //             $query->where('vender_id', '=', $request->vender);
    //         }
    //         //            if(!empty($request->bill_date))
    //         //            {
    //         //                $date_range = explode('to', $request->bill_date);
    //         //                $query->whereBetween('bill_date', $date_range);
    //         //            }

    //         if (count(explode('to', $request->bill_date)) > 1) {
    //             $date_range = explode(' to ', $request->bill_date);
    //             $query->whereBetween('bill_date', $date_range);
    //         } elseif (!empty($request->bill_date)) {
    //             $date_range = [$request->date, $request->bill_date];
    //             $query->whereBetween('bill_date', $date_range);
    //         }

    //         if ($request->status != null) {
    //             $query->where('status', '=', $request->status);
    //         }
    //         $bills = $query->with('category')->get();

    //         return view('bill.index', compact('bills', 'vender', 'status'));
    //     } else {
    //         return redirect()->back()->with('error', __('Permission Denied.'));
    //     }
    // }

public function index(Request $request)
    {
        if (\Auth::user()->can('manage bill')) {
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
            $vender = Vender::where($column, '=', $ownerId)->get()->pluck('name', 'id');
            $vender->prepend('Select Vendor', '');

            $status = Bill::$statues;

            $query = Bill::where('type', '=', 'Bill')->where($column, '=', $ownerId);
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

            if ($request->status != null) {
                $query->where('status', '=', $request->status);
            }

            // load bills
            $bills = $query->with('category')->paginate(10)->appends($request->all());

            // Ensure amount and open_balance exist as properties for blade; use fallbacks if model fields differ
            // If your model uses different field names, replace 'amount' / 'open_balance' below.
            $bills->transform(function ($bill) {
                // try common fields, fall back to 0
                if (!isset($bill->amount)) {
                    // try alternate field names
                    if (isset($bill->total)) {
                        $bill->amount = $bill->total;
                    } elseif (isset($bill->grand_total)) {
                        $bill->amount = $bill->grand_total;
                    } else {
                        $bill->amount = 0;
                    }
                }

                if (!isset($bill->open_balance)) {
                    if (isset($bill->due_amount)) {
                        $bill->open_balance = $bill->due_amount;
                    } elseif (isset($bill->balance)) {
                        $bill->open_balance = $bill->balance;
                    } else {
                        // default open_balance to amount (if nothing else)
                        $bill->open_balance = $bill->amount;
                    }
                }

                // Ensure numeric values (float)
                $bill->amount = (float) $bill->amount;
                $bill->open_balance = (float) $bill->open_balance;

                return $bill;
            });

            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            return view('bill.index', compact('bills', 'vender', 'status', 'accounts'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    public function create($vendorId)
    {

        if (\Auth::user()->can('create bill')) {
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'bill')->get();
            $category = ProductServiceCategory::where($column, $ownerId)
                ->whereNotIn('type', ['product & service', 'income',])
                ->get()->pluck('name', 'id')->toArray();
            $category = ['_add_' => '➕ Add new category'] + ['' => 'Select Category'] + $category;

            $bill_number = \Auth::user()->billNumberFormat($this->billNumber());
            $venders = Vender::where($column, $ownerId)->get()->pluck('name', 'id')->toArray();
            $venders = ['_add_' => '➕ Add new vendor'] + ['' => 'Select Vendor'] + $venders;

            $vendersQuery = Vender::where($column, $ownerId)->get();

            $vendorOptions = [];
            $vendorOptions[''] = 'Select Vendor';
            $vendorOptions['add'] = 'Add new vendor';

            foreach ($vendersQuery as $vender) {
                // Full billing address banao
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
                    'terms'   => $vender->terms ?? 'Net 30',  // agar column hai toh use karo
                    'balance' => $vender->getDueAmount() ?? 0, // ya jo method tumhare paas hai
                ];
            }

            $product_services = ProductService::where($column, $ownerId)->get()->pluck('name', 'id');
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
            $customers = Customer::where($column, $ownerId)->get();


            // Get taxes for the form
            $taxes = Tax::where('created_by', $ownerId)->get()->pluck('name', 'id');

            // Get customers for billable items (plucked for dropdown)
            $customers = Customer::where($column, $ownerId)->orderBy('name')->get()->pluck('name', 'id')->toArray();

            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            $paymentTerms = PaymentTerm::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $termsData = PaymentTerm::where('created_by', \Auth::user()->creatorId())->get();

            return view('bill.create', compact('venders', 'bill_number', 'product_services', 'category', 'customFields', 'vendorId', 'chartAccounts', 'subAccounts', 'taxes', 'customers', 'vendorOptions', 'accounts', 'paymentTerms', 'termsData'));

        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    // public function store(Request $request)
    // {
    //     \DB::beginTransaction();
    //     try {
    //     if(\Auth::user()->can('create bill'))
    //     {

    //         $validator = \Validator::make(
    //             $request->all(), [
    //                 'vender_id' => 'required',
    //                 'bill_date' => 'required',
    //                 'due_date' => 'required'
    //             ]
    //         );
    //         if ($validator->fails()) {
    //             $messages3 = $validator->getMessageBag();
    //             return redirect()->back()->with('error', $messages3->first());
    //         }

    //         if (!empty($request->items) && empty($request->items[0]['item']) && empty($request->items[0]['chart_account_id']) && empty($request->items[0]['amount']))
    //         {
    //             $itemValidator = \Validator::make(
    //                 $request->all(), [
    //                     'item' => 'required'
    //                 ]
    //             );
    //             if ($itemValidator->fails()) {
    //                 $messages1 = $itemValidator->getMessageBag();
    //                 return redirect()->back()->with('error', $messages1->first());
    //             }
    //         }

    //         if (!empty($request->items) && empty($request->items[0]['chart_account_id'])  && !empty($request->items[0]['amount']) )
    //         {
    //             $accountValidator = \Validator::make(
    //                 $request->all(), [
    //                     'chart_account_id' => 'required'
    //                 ]
    //             );
    //             if ($accountValidator->fails()) {
    //                 $messages2 = $accountValidator->getMessageBag();
    //                 return redirect()->back()->with('error', $messages2->first());
    //             }

    //         }

    //         $bill            = new Bill();
    //         $bill->bill_id   = $this->billNumber();
    //         $bill->vender_id = $request->vender_id;;
    //         $bill->bill_date      = $request->bill_date;
    //         $bill->status         = 0;
    //         $bill->type         =  'Bill';
    //         $bill->user_type         =  'vendor';
    //         $bill->due_date       = $request->due_date;
    //         $bill->category_id    = !empty($request->category_id) ? $request->category_id :0;
    //         $bill->order_number   = !empty($request->order_number) ? $request->order_number : 0;
    //         $bill->created_by     = \Auth::user()->creatorId();
    //         $bill->owned_by     = \Auth::user()->ownedId();
    //         $bill->save();

    //         CustomField::saveData($bill, $request->customField);
    //         $products = $request->items;
    //         $newitems = $request->items;

    //         $total_amount=0;

    //         for($i = 0; $i < count($products); $i++)
    //         {
    //             if(!empty($products[$i]['item']))
    //             {
    //                 $billProduct              = new BillProduct();
    //                 $billProduct->bill_id     = $bill->id;
    //                 $billProduct->product_id  = $products[$i]['item'];
    //                 $billProduct->quantity    = $products[$i]['quantity'];
    //                 $billProduct->tax         = $products[$i]['tax'];
    //                 $billProduct->discount    = $products[$i]['discount'];
    //                 $billProduct->price       = $products[$i]['price'];
    //                 $billProduct->description = $products[$i]['description'];
    //                 $billProduct->save();
    //                 $newitems[$i]['prod_id'] = $billProduct->id;
    //             }

    //             $billTotal=0;
    //             if(!empty($products[$i]['chart_account_id'])){
    //                 $billAccount                    = new BillAccount();
    //                 $billAccount->chart_account_id  = $products[$i]['chart_account_id'];
    //                 $billAccount->price             = $products[$i]['amount'] ? $products[$i]['amount'] : 0;
    //                 $billAccount->description       = $products[$i]['description'];
    //                 $billAccount->type              = 'Bill';
    //                 $billAccount->ref_id            = $bill->id;
    //                 $billAccount->save();
    //                 $newitems[$i]['bill_account_id'] = $billAccount->id;
    //                 $billTotal= $billAccount->price;
    //             }

    //             //inventory management (Quantity)
    //             if(!empty($billProduct))
    //             {
    //                 Utility::total_quantity('plus',$billProduct->quantity,$billProduct->product_id);
    //             }

    //             //Product Stock Report
    //             if(!empty($products[$i]['item']))
    //             {
    //                 $type='bill';
    //                 $type_id = $bill->id;
    //                 $description=$products[$i]['quantity'].'  '.__('quantity purchase in bill').' '. \Auth::user()->billNumberFormat($bill->bill_id);
    //                 Utility::addProductStock( $products[$i]['item'],$products[$i]['quantity'],$type,$description,$type_id);
    //                 // $total_amount += ($billProduct->quantity * $billProduct->price)+$billTotal ;
    //                 $total_amount += ((float)$billProduct->quantity * (float)$billProduct->price) + (float)$billTotal;

    //             }

    //         }

    //         if(!empty($request->chart_account_id))
    //         {

    //             $billaccount= ProductServiceCategory::find($request->category_id);
    //             $chart_account = ChartOfAccount::find($billaccount->chart_account_id);
    //             $billAccount                    = new BillAccount();
    //             $billAccount->chart_account_id  = $chart_account['id'];
    //             $billAccount->price             = $total_amount;
    //             $billAccount->description       = $request->description;
    //             $billAccount->type              = 'Bill Category';
    //             $billAccount->ref_id            = $bill->id;
    //             $billAccount->save();
    //         }

    //         // // WorkFlow get which is active
    //         $us_mail = 'false';
    //         $us_notify = 'false';
    //         $us_approve = 'false';
    //         $usr_Notification = [];
    //         $workflow = WorkFlow::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'accounts')->where('status', 1)->first();
    //         if ($workflow) {
    //             $workflowaction = WorkFlowAction::where('workflow_id', $workflow->id)->where('status', 1)->get();
    //             foreach ($workflowaction as $action) {
    //                 $useraction = json_decode($action->assigned_users);
    //                 if (strtolower('create-bill') == $action->node_id) {
    //                     // Pick that stage user assign or change on lead
    //                     if (@$useraction != '') {
    //                         $useraction = json_decode($useraction);
    //                         foreach ($useraction as $anyaction) {
    //                             // make new user array
    //                             if ($anyaction->type == 'user') {
    //                                 $usr_Notification[] = $anyaction->id;
    //                             }
    //                         }
    //                     }
    //                     $raw_json = trim($action->applied_conditions, '"');
    //                     $cleaned_json = stripslashes($raw_json);
    //                     $applied_conditions = json_decode($cleaned_json, true);

    //                     if (isset($applied_conditions['conditions']) && is_array($applied_conditions['conditions'])) {
    //                         $arr = [
    //                             'bill_date' => 'bill_date',
    //                             'due_date' => 'due_date',
    //                             'order_number' => 'order_number',
    //                         ];
    //                         $relate = [
    //                         ];

    //                         foreach ($applied_conditions['conditions'] as $conditionGroup) {

    //                             if (in_array($conditionGroup['action'], ['send_email', 'send_notification', 'send_approval'])) {
    //                                 $query = Bill::where('id', $bill->id);
    //                                 foreach ($conditionGroup['conditions'] as $condition) {
    //                                     $field = $condition['field'];
    //                                     $operator = $condition['operator'];
    //                                     $value = $condition['value'];
    //                                     if (isset($arr[$field], $relate[$arr[$field]])) {
    //                                         $relatedField = strpos($arr[$field], '_') !== false ? explode('_', $arr[$field], 2)[1] : $arr[$field];
    //                                         $relation = $relate[$arr[$field]];

    //                                         // Apply condition to the related model
    //                                         $query->whereHas($relation, function ($relatedQuery) use ($relatedField, $operator, $value) {
    //                                             $relatedQuery->where($relatedField, $operator, $value);
    //                                         });
    //                                     } else {
    //                                         // Apply condition directly to the contract model
    //                                         $query->where($arr[$field], $operator, $value);
    //                                     }
    //                                 }
    //                                 $result = $query->first();

    //                                 if (!empty($result)) {
    //                                     if ($conditionGroup['action'] === 'send_email') {
    //                                         $us_mail = 'true';
    //                                     } elseif ($conditionGroup['action'] === 'send_notification') {
    //                                         $us_notify = 'true';
    //                                     } elseif ($conditionGroup['action'] === 'send_approval') {
    //                                         $us_approve = 'true';
    //                                     }
    //                                 }
    //                             }
    //                         }
    //                     }
    //                     if ($us_mail == 'true') {
    //                         // email send
    //                     }
    //                     if ($us_notify == 'true' || $us_approve == 'true') {
    //                         // notification generate
    //                         if (count($usr_Notification) > 0) {
    //                             $usr_Notification[] = Auth::user()->creatorId();
    //                             foreach ($usr_Notification as $usrLead) {
    //                                 $data = [
    //                                     "updated_by" => Auth::user()->id,
    //                                     "data_id" => $bill->id,
    //                                     "name" => '',
    //                                 ];
    //                                 if($us_notify == 'true'){
    //                                     Utility::makeNotification($usrLead,'create_bill',$data,$bill->id,'create Bill');
    //                                 }elseif($us_approve == 'true'){
    //                                     Utility::makeNotification($usrLead,'approve_bill',$data,$bill->id,'For Approval Bill');
    //                                 }
    //                             }
    //                         }
    //                     }
    //                 }
    //             }
    //         }


    //         //For Notification
    //         $setting  = Utility::settings(\Auth::user()->creatorId());
    //         $vendor = Vender::find($request->vender_id);
    //         $billNotificationArr = [
    //             'bill_number' => \Auth::user()->billNumberFormat($bill->bill_id),
    //             'user_name' => \Auth::user()->name,
    //             'bill_date' => $bill->bill_date,
    //             'bill_due_date' => $bill->due_date,
    //             'vendor_name' => $vendor->name,
    //         ];
    //         //Slack Notification
    //         if(isset($setting['bill_notification']) && $setting['bill_notification'] ==1)
    //         {
    //             Utility::send_slack_msg('new_bill', $billNotificationArr);
    //         }
    //         //Telegram Notification
    //         if(isset($setting['telegram_bill_notification']) && $setting['telegram_bill_notification'] ==1)
    //         {
    //             Utility::send_telegram_msg('new_bill', $billNotificationArr);
    //         }
    //         //Twilio Notification
    //         if(isset($setting['twilio_bill_notification']) && $setting['twilio_bill_notification'] ==1)
    //         {
    //             Utility::send_twilio_msg($vendor->contact,'new_bill', $billNotificationArr);
    //         }
    //         $data['id'] = $bill->id;
    //         $data['no'] = $bill->bill_id;
    //         $data['date'] = $bill->bill_date;
    //         $data['created_at'] = date('Y-m-d', strtotime($bill->bill_date)) . ' ' . date('h:i:s');
    //         $data['reference'] = $bill->ref_number;
    //         $data['category'] = 'Bill';
    //         $data['owned_by'] = $bill->owned_by;
    //         $data['created_by'] = $bill->created_by;
    //         $data['prod_id'] = $billProduct->product_id;
    //         $data['items'] = $newitems;
    //         $data['created_at'] = date('Y-m-d', strtotime($bill->bill_date)) . ' ' . date('h:i:s');
    //         $dataret  = Utility::jr_exp_entry($data);
    //         $bill->voucher_id = $dataret;
    //         $bill->save();
    //         //webhook
    //         $module ='New Bill';
    //         $webhook =  Utility::webhookSetting($module);
    //         if($webhook)
    //         {
    //             $parameter = json_encode($bill);
    //             $status = Utility::WebhookCall($webhook['url'],$parameter,$webhook['method']);

    //             if($status == true)
    //             {
    //                 Utility::makeActivityLog(\Auth::user()->id,'Bill',$bill->id,'Create Bill',$bill->type);
    //                 \DB::commit();
    //                 return redirect()->route('bill.index', $bill->id)->with('success', __('Bill successfully created.'));
    //             }
    //             else
    //             {
    //                 \DB::commit();
    //                 return redirect()->back()->with('error', __('Webhook call failed.'));
    //             }
    //         }

    //         Utility::makeActivityLog(\Auth::user()->id,'Bill',$bill->id,'Create Bill',$bill->type);
    //         \DB::commit();
    //         return redirect()->route('bill.index', $bill->id)->with('success', __('Bill successfully created.'));
    //     }
    //     else
    //     {
    //         return redirect()->back()->with('error', __('Permission denied.'));
    //     }
    //     } catch (\Exception $e) {
    //         \DB::rollback();
    //         return redirect()->back()->with('error', $e);
    //     }
    // }
    /**
     * Store Bill - Create bill without posting JV (wait for approval)
     */
    public function store(Request $request)
    {
        \DB::beginTransaction();
        try {
            if (\Auth::user()->can('create bill')) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'vender_id' => 'required',
                        'bill_date' => 'required',
                        'due_date' => 'required'
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

                // Validate billable items have customers
                $billableValidationError = null;
                
                // Check category items
                if ($request->has('categories') && is_array($request->categories)) {
                    foreach ($request->categories as $index => $categoryData) {
                        if (isset($categoryData['billable']) && $categoryData['billable']) {
                            if (empty($categoryData['customer_id'])) {
                                $billableValidationError = __('Select a customer for each billable split line.') . ' (Category row ' . ($index + 1) . ')';
                                break;
                            }
                        }
                    }
                }
                
                // Check item details
                if (!$billableValidationError && $request->has('items') && is_array($request->items)) {
                    foreach ($request->items as $index => $itemData) {
                        if (isset($itemData['billable']) && $itemData['billable']) {
                            if (empty($itemData['customer_id'])) {
                                $billableValidationError = __('Select a customer for each billable split line.') . ' (Item row ' . ($index + 1) . ')';
                                break;
                            }
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

                // Create Bill
                $bill = new Bill();
                $bill->bill_id = $this->billNumber();
                $bill->vender_id = $request->vender_id;
                $bill->bill_date = $request->bill_date;
                $bill->due_date = $request->due_date;
                $bill->status = 0; // Draft
                $bill->type = 'Bill';
                $bill->user_type = 'vendor';
                
                // New QBO fields
                $bill->terms = $request->terms;
                $bill->notes = $request->notes;
                $bill->subtotal = $request->subtotal ?? 0;
                $bill->total = $request->total ?? 0;
                
                $bill->category_id = !empty($request->category_id) ? $request->category_id : 0;
                $bill->order_number = !empty($request->order_number) ? $request->order_number : 0;
                $bill->created_by = \Auth::user()->creatorId();
                $bill->owned_by = \Auth::user()->ownedId();
                $bill->save();

                // Save Custom Fields
                if ($request->has('customField')) {
                    CustomField::saveData($bill, $request->customField);
                }

                $newitems = [];

                // Process CATEGORY DETAILS (Account-based expenses)
                if ($request->has('categories') && is_array($request->categories)) {
                    foreach ($request->categories as $index => $categoryData) {
                        // Skip empty rows
                        if (empty($categoryData['account_id']) && empty($categoryData['amount'])) {
                            continue;
                        }

                        $billAccount = new BillAccount();
                        $billAccount->ref_id = $bill->id;
                        $billAccount->type = 'Bill';
                        $billAccount->chart_account_id = $categoryData['account_id'] ?? null;
                        $billAccount->description = $categoryData['description'] ?? '';
                        $billAccount->price = $categoryData['amount'] ?? 0;
                        
                        // Handle billable and customer fields
                        $billAccount->billable = isset($categoryData['billable']) ? 1 : 0;
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
                        // Skip rows without a product_id (required field)
                        if (empty($itemData['product_id'])) {
                            continue;
                        }
                        
                        $account = null;
                        $product = ProductService::find($itemData['product_id']);
                        if ($product) {
                            if($product->type == 'product'){
                                $account = $product->asset_chartaccount_id ?? $product->expense_chartaccount_id;
                            }else{
                                $account = $product->expense_chartaccount_id;
                            }
                        }

                        $billProduct = new BillProduct();
                        $billProduct->bill_id = $bill->id;
                        $billProduct->product_id = $itemData['product_id'];
                        $billProduct->description = $itemData['description'] ?? '';
                        $billProduct->quantity = $itemData['quantity'] ?? 1;
                        $billProduct->price = $itemData['amount'] ?? 0;
                        $billProduct->discount = $itemData['discount'] ?? 0;
                        $billProduct->account_id = $account;
                        
                        // Handle tax checkbox (store as 1 or 0)
                        $billProduct->tax = isset($itemData['tax']) ? 1 : 0;
                        
                        // Calculate line total
                        $billProduct->line_total = $itemData['amount'] ?? ($billProduct->quantity * $billProduct->price);
                        
                        // QBO specific fields - billable and customer
                        $billProduct->billable = isset($itemData['billable']) ? 1 : 0;
                        $billProduct->customer_id = $itemData['customer_id'] ?? null;
                        
                        // IMPORTANT: Save order to maintain exact row position
                        // This allows the same product to appear in multiple rows
                        $billProduct->order = $index;
                        
                        $billProduct->save();
                        
                        // Inventory management (Quantity) - increase stock for bills
                        if ($billProduct->product_id) {
                            Utility::total_quantity('plus', $billProduct->quantity, $billProduct->product_id);
                            
                            // Product Stock Report
                            $type = 'bill';
                            $type_id = $bill->id;
                            $description = $billProduct->quantity . ' ' . __('quantity purchase in bill') . ' ' . \Auth::user()->billNumberFormat($bill->bill_id);
                            Utility::addProductStock($billProduct->product_id, $billProduct->quantity, $type, $description, $type_id);
                        }
                        
                        $newitems[] = [
                            'prod_id' => $billProduct->id,
                            'product_id' => $billProduct->product_id,
                            'quantity' => $billProduct->quantity,
                            'price' => $billProduct->price,
                            'order' => $index,
                        ];
                    }
                }

                // Notifications
                $setting = Utility::settings(\Auth::user()->creatorId());
                $vendor = Vender::find($request->vender_id);
                $billNotificationArr = [
                    'bill_number' => \Auth::user()->billNumberFormat($bill->bill_id),
                    'user_name' => \Auth::user()->name,
                    'bill_date' => $bill->bill_date,
                    'bill_due_date' => $bill->due_date,
                    'vendor_name' => $vendor->name,
                ];

                if (isset($setting['bill_notification']) && $setting['bill_notification'] == 1) {
                    Utility::send_slack_msg('new_bill', $billNotificationArr);
                }
                if (isset($setting['telegram_bill_notification']) && $setting['telegram_bill_notification'] == 1) {
                    Utility::send_telegram_msg('new_bill', $billNotificationArr);
                }
                if (isset($setting['twilio_bill_notification']) && $setting['twilio_bill_notification'] == 1) {
                    Utility::send_twilio_msg($vendor->contact, 'new_bill', $billNotificationArr);
                }

                // Auto-approve and create journal entry for company users
                if (Auth::user()->type == 'company') {
                    $bill->status = 6; // Approved
                    $bill->save();
                    
                    // Create journal entry using JournalService
                    $this->createBillJournalEntry($bill);
                    
                    Utility::makeActivityLog(\Auth::user()->id, 'Bill', $bill->id, 'Create Bill', 'Bill Created & Approved');
                }
                
                // Webhook
                $module = 'New Bill';
                $webhook = Utility::webhookSetting($module);
                if ($webhook) {
                    $parameter = json_encode($bill);
                    $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);

                    if ($status == true) {
                        Utility::makeActivityLog(\Auth::user()->id, 'Bill', $bill->id, 'Create Bill', 'Bill Created (Pending Approval)');
                        \DB::commit();
                        if ($request->ajax() || $request->wantsJson()) {
                            return response()->json([
                                'status' => 'success',
                                'message' => __('Bill successfully created.'),
                                'bill_id' => $bill->id
                            ], 200);
                        }
                        return redirect()->route('bill.index', $bill->id)->with('success', __('Bill successfully created and waiting for approval.'));
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

                Utility::makeActivityLog(\Auth::user()->id, 'Bill', $bill->id, 'Create Bill', 'Bill Created (Pending Approval)');
                \DB::commit();
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'status' => 'success',
                        'message' => __('Bill successfully created.'),
                        'bill_id' => $bill->id
                    ], 200);
                }
                return redirect()->route('bill.index', $bill->id)->with('success', __('Bill successfully created and waiting for approval.'));
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
            // dd($e);
            \Log::error('Bill Store Error: ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Create Bill Journal Voucher (JV)
     */
    private function createBillJournalVoucher(Bill $bill)
    {
        $billProducts = BillProduct::where('bill_id', $bill->id)->get();
        $billAccounts = BillAccount::where('ref_id', $bill->id)->where('type', 'Bill')->get();
        $newitems = [];

        // Add Bill Products to items array
        foreach ($billProducts as $product) {
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

        // Add Bill Accounts to items array
        foreach ($billAccounts as $account) {
            $newitems[] = [
                'bill_account_id' => $account->id,
                'chart_account_id' => $account->chart_account_id,
                'amount' => $account->price,
                'description' => $account->description,
                'itemTaxPrice' => 0,

            ];
        }

        $data = [
            'id' => $bill->id,
            'no' => $bill->bill_id,
            'date' => $bill->bill_date,
            'created_at' => date('Y-m-d H:i:s', strtotime($bill->bill_date)),
            'reference' => $bill->ref_number,
            'category' => 'Bill',
            'owned_by' => $bill->owned_by,
            'created_by' => $bill->created_by,
            'prod_id' => $billProducts->first()->product_id ?? null,
            'items' => $newitems,
        ];
        // dd($data);
        $voucherId = Utility::jr_exp_entry($data);
        $bill->voucher_id = $voucherId;
        $bill->save();

        return $voucherId;
    }

    /**
     * Send Bill for Approval
     */
    public function sendForApproval($id)
    {
        \DB::beginTransaction();
        try {
            $bill = Bill::findOrFail($id);

            // Check if bill is in draft or rejected status
            if ($bill->status != 0 && $bill->status != 7) {
                return redirect()->back()->with('error', __('Only draft or rejected bills can be sent for approval.'));
            }

            // Check if bill has items
            $billProducts = BillProduct::where('bill_id', $bill->id)->count();
            $billAccounts = BillAccount::where('ref_id', $bill->id)->count();

            if ($billProducts == 0 && $billAccounts == 0) {
                return redirect()->back()->with('error', __('Cannot send bill for approval without items.'));
            }
            // Send notification to bill creator
            //delete previous notification
            Notification::where('data_id', $bill->id)->delete();
            $usrId = $bill->created_by;
            $data = [
                "updated_by" => \Auth::user()->id,
                "data_id" => $bill->id,
                "name" => '',
            ];

            Utility::makeNotification($usrId, 'request_approve_bill', $data, $bill->id, 'For Approval Bill');
            // Update status to Pending Approval
            $bill->status = 5;
            $bill->save();

            Utility::makeActivityLog(\Auth::user()->id, 'Bill', $bill->id, 'Send for Approval', 'Bill sent for approval');

            \DB::commit();
            return redirect()->back()->with('success', __('Bill sent for approval successfully.'));
        } catch (\Exception $e) {
            \DB::rollBack();
            // dd($e);
            \Log::error('Send Bill for Approval Error: ' . $e->getMessage());
            return redirect()->back()->with('error', __('Error sending bill for approval: ' . $e->getMessage()));
        }
    }

    /**
     * Approve Bill
     */
    public function approveBill($id)
    {
        \DB::beginTransaction();
        try {
            $bill = Bill::findOrFail($id);

            // Check if already approved
            if ($bill->status == 6) {
                return redirect()->back()->with('error', __('Bill already approved.'));
            }

            // Check if in pending approval status
            if ($bill->status != 5) {
                return redirect()->back()->with('error', __('Bill must be in pending approval status.'));
            }

            // Create Journal Voucher
            $this->createBillJournalVoucher($bill);

            // Update status to Approved
            $bill->status = 6;
            $bill->save();

            Utility::makeActivityLog(\Auth::user()->id, 'Bill', $bill->id, 'Approve Bill', 'Bill approved and JV posted');


            \DB::commit();
            return redirect()->route('bill.index')->with('success', __('Bill approved successfully and JV posted.'));
        } catch (\Exception $e) {
            \DB::rollBack();
            // dd($e);
            \Log::error('Bill Approval Error: ' . $e->getMessage());
            return redirect()->back()->with('error', __('Error approving bill: ' . $e->getMessage()));
        }
    }

    /**
     * Reject Bill
     */
    public function rejectBill(Request $request, $id)
    {
        \DB::beginTransaction();
        try {
            $bill = Bill::findOrFail($id);
            // Check if bill is in pending approval status
            if ($bill->status != 5) {
                return redirect()->back()->with('error', __('Only bills under approval can be rejected.'));
            }

            $bill->status = 7; // Rejected
            $bill->save();

            Utility::makeActivityLog(\Auth::user()->id, 'Bill', $bill->id, 'Reject Bill', 'Bill rejected: ' . $request->rejection_reason);
            \DB::commit();
            return redirect()->route('bill.index')->with('success', __('Bill rejected successfully.'));
        } catch (\Exception $e) {
            \DB::rollBack();
            // dd($e);
            \Log::error('Reject Bill Error: ' . $e->getMessage());
            return redirect()->back()->with('error', __('Error rejecting bill: ' . $e->getMessage()));
        }
    }
    function venderNumber()
    {
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
        $latest = Vender::where($column, '=', $ownerId)->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->customer_id + 1;
    }

    public function show($ids)
    {

        if (\Auth::user()->can('show bill')) {
            try {
                $id = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Bill Not Found.'));
            }

            $id = Crypt::decrypt($ids);
            $bill = Bill::with('debitNote', 'payments.bankAccount', 'items.product.unit')->find($id);

            if (!empty($bill) && $bill->created_by == \Auth::user()->creatorId()) {
                $billPayment = BillPayment::where('bill_id', $bill->id)->first();
                $vendor = $bill->vender;

                $item = $bill->items;
                $accounts = $bill->accounts;
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

                $bill->customField = CustomField::getData($bill, 'bill');
                $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'bill')->get();

                return view('bill.view', compact('bill', 'vendor', 'items', 'billPayment', 'customFields'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit($ids)
    {
        
        if (\Auth::user()->can('edit bill')) {
            try {
                $id = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Bill Not Found.'));
            }

            $id = Crypt::decrypt($ids);
            $bill = Bill::find($id);

            if (!empty($bill)) {
                $user = \Auth::user();
                $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
                $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
                $category = ProductServiceCategory::where($column, $ownerId)
                    ->whereNotIn('type', ['product & service', 'income',])
                    ->get()->pluck('name', 'id')->toArray();
                $category = ['__add__' => '➕ Add new category'] + ['' => 'Select Category'] + $category;

                $bill_number = \Auth::user()->billNumberFormat($bill->bill_id);
                $venders = Vender::where($column, $ownerId)->get()->pluck('name', 'id')->toArray();
                $venders = ['__add__' => '➕ Add new vendor'] + ['' => 'Select Vendor'] + $venders;

                // Compute vendor options with address for JS
                $vendersQuery = Vender::where($column, $ownerId)->get();
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
                        'terms'   => $vender->terms ?? '', 
                    ];
                }

                $product_services = ProductService::where($column, $ownerId)->get()->pluck('name', 'id');

                $bill->customField = CustomField::getData($bill, 'bill');
                $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'bill')->get();

                $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                    ->where('created_by', \Auth::user()->creatorId())->get()
                    ->pluck('code_name', 'id');
                $chartAccounts->prepend('Select Account', '');

                $subAccounts = ChartOfAccount::select('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_account_parents.account');
                $subAccounts->leftjoin('chart_of_account_parents', 'chart_of_accounts.parent', 'chart_of_account_parents.id');
                $subAccounts->where('chart_of_accounts.parent', '!=', 0);
                $subAccounts->where('chart_of_accounts.created_by', \Auth::user()->creatorId());
                $subAccounts = $subAccounts->get()->toArray();

                // Get taxes for the form
                $taxes = Tax::where('created_by', $ownerId)->get()->pluck('name', 'id');

                // Get customers for billable items (plucked for dropdown)
                $customers = Customer::where($column, $ownerId)->orderBy('name')->get()->pluck('name', 'id')->toArray();

                // Separate category details and items (QBO style)
                $categoryDetails = $bill->accounts; // BillAccount records
                $items = $bill->items; // BillProduct records

                $paymentTerms = PaymentTerm::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $termsData = PaymentTerm::where('created_by', \Auth::user()->creatorId())->get();

                return view('bill.edit', compact(
                    'venders',
                    'product_services',
                    'bill',
                    'bill_number',
                    'category',
                    'customFields',
                    'chartAccounts',
                    'categoryDetails',
                    'items',
                    'subAccounts',
                    'taxes',
                    'customers',
                    'vendorOptions',
                    'paymentTerms',
                    'termsData'
                ));
            } else {
                return redirect()->back()->with('error', __('Bill Not Found.'));
            }

        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    // public function update(Request $request, Bill $bill)
    // {
    //     \DB::beginTransaction();
    //     try {
    //         if (\Auth::user()->can('edit bill')) {

    //             if ($bill->created_by == \Auth::user()->creatorId()) {
    //                 $validator = \Validator::make(
    //                     $request->all(),
    //                     [
    //                         'vender_id' => 'required',
    //                         'bill_date' => 'required',
    //                         'due_date' => 'required',
    //                     ]
    //                 );
    //                 if ($validator->fails()) {
    //                     $messages = $validator->getMessageBag();

    //                     return redirect()->route('bill.index')->with('error', $messages->first());
    //                 }
    //                 $bill->vender_id = $request->vender_id;
    //                 $bill->bill_date = $request->bill_date;
    //                 $bill->due_date = $request->due_date;
    //                 $bill->user_type = 'vendor';
    //                 $bill->order_number = $request->order_number;
    //                 $bill->category_id = $request->category_id;
    //                 $bill->save();
    //                 CustomField::saveData($bill, $request->customField);
    //                 $products = $request->items;
    //                 $total_amount = 0;

    //                 for ($i = 0; $i < count($products); $i++) {
    //                     $billProduct = BillProduct::find($products[$i]['id']);
    //                     if ($billProduct == null) {
    //                         $billProduct = new BillProduct();
    //                         $billProduct->bill_id = $bill->id;

    //                         if (isset($products[$i]['items'])) {
    //                             Utility::total_quantity('plus', $products[$i]['quantity'], $products[$i]['items']);
    //                         }

    //                         $updatePrice = ($products[$i]['price'] * $products[$i]['quantity']) + ($products[$i]['itemTaxPrice']) - ($products[$i]['discount']);
    //                         Utility::updateUserBalance('vendor', $request->vender_id, $updatePrice, 'debit');

    //                     } else {

    //                         Utility::total_quantity('minus', $billProduct->quantity, $billProduct->product_id);
    //                     }

    //                     if (isset($products[$i]['items'])) {
    //                         $billProduct->product_id = $products[$i]['items'];
    //                         $billProduct->quantity = $products[$i]['quantity'];
    //                         $billProduct->tax = $products[$i]['tax'];
    //                         $billProduct->discount = $products[$i]['discount'];
    //                         $billProduct->price = $products[$i]['price'];
    //                         $billProduct->description = $products[$i]['description'];
    //                         $billProduct->save();
    //                     }


    //                     $billTotal = 0;
    //                     if (!empty($products[$i]['chart_account_id'])) {
    //                         $billAccount = BillAccount::find($products[$i]['account_id']);

    //                         if ($billAccount == null) {
    //                             $billAccount = new BillAccount();
    //                             $billAccount->chart_account_id = $products[$i]['chart_account_id'];
    //                         } else {
    //                             $billAccount->chart_account_id = $products[$i]['chart_account_id'];
    //                         }
    //                         $billAccount->price = $products[$i]['amount'] ? $products[$i]['amount'] : 0;
    //                         $billAccount->description = $products[$i]['description'];
    //                         $billAccount->type = 'Bill';
    //                         $billAccount->ref_id = $bill->id;
    //                         $billAccount->save();
    //                         $billTotal = $billAccount->price;
    //                     }

    //                     if ($products[$i]['id'] > 0) {
    //                         Utility::total_quantity('plus', $products[$i]['quantity'], $billProduct->product_id);
    //                     }

    //                     //Product Stock Report
    //                     $type = 'bill';
    //                     $type_id = $bill->id;
    //                     StockReport::where('type', '=', 'bill')->where('type_id', '=', $bill->id)->delete();
    //                     $description = $products[$i]['quantity'] . '  ' . __(' quantity purchase in bill') . ' ' . \Auth::user()->billNumberFormat($bill->bill_id);

    //                     if (isset($products[$i]['items'])) {
    //                         Utility::addProductStock($products[$i]['items'], $products[$i]['quantity'], $type, $description, $type_id);
    //                     }

    //                     $total_amount += ($billProduct->quantity * $billProduct->price) + $billTotal;

    //                 }

    //                 if (!empty($request->chart_account_id)) {
    //                     $billaccount = ProductServiceCategory::find($request->category_id);
    //                     $chart_account = ChartOfAccount::find($billaccount->chart_account_id);
    //                     $billAccount = new BillAccount();
    //                     $billAccount->chart_account_id = $chart_account['id'];
    //                     $billAccount->price = $total_amount;
    //                     $billAccount->description = $request->description;
    //                     $billAccount->type = 'Bill Category';
    //                     $billAccount->ref_id = $bill->id;
    //                     $billAccount->save();
    //                 }

    //                 TransactionLines::where('reference_id', $bill->id)->where('reference', 'Bill')->delete();
    //                 TransactionLines::where('reference_id', $bill->id)->where('reference', 'Bill Account')->delete();
    //                 $voucher = JournalEntry::where('category', 'Bill')->where('reference_id', $bill->id)->where('voucher_type', 'JV')->first();
    //                 if ($voucher) {
    //                     JournalItem::where('journal', $voucher->id)->delete();
    //                     $prod_id = TransactionLines::where('reference_id', $bill->voucher_id)->where('reference', 'Bill Journal')->where('product_type', 'Bill Product')->delete();
    //                     $prod_tax = TransactionLines::where('reference_id', $bill->voucher_id)->where('reference', 'Bill Journal')->where('product_type', 'Bill Tax')->delete();
    //                     $prod_account = TransactionLines::where('reference_id', $bill->voucher_id)->where('reference', 'Bill Journal')->where('product_type', 'Bill Account')->delete();
    //                     $inv_receviable = TransactionLines::where('reference_id', $bill->voucher_id)->where('reference', 'Bill Journal')->where('product_type', 'Bill Payable')->delete();
    //                 }

    //                 $bill_products = BillProduct::where('bill_id', $bill->id)->get();
    //                 $tax = 0;
    //                 $payable = 0;
    //                 foreach ($bill_products as $bill_product) {
    //                     $tax = 0;
    //                     $product = ProductService::find($bill_product->product_id);
    //                     // $totalTaxPrice = 0;
    //                     // if($bill_product->tax != null){

    //                     // $taxes = \App\Models\Utility::tax($bill_product->tax);
    //                     // foreach ($taxes as $tax) {
    //                     //     $taxPrice = \App\Models\Utility::taxRate($tax->rate, $bill_product->price, $bill_product->quantity, $bill_product->discount);
    //                     //     $totalTaxPrice += $taxPrice;
    //                     // }
    //                     $journalItem = new JournalItem();
    //                     $journalItem->journal = $voucher->id;
    //                     $journalItem->account = @$product->expense_chartaccount_id;
    //                     $journalItem->product_ids = @$bill_product->id;
    //                     $journalItem->description = @$bill_product->description;
    //                     $journalItem->debit = (($bill_product->quantity * $bill_product->price) - $bill_product->discount);
    //                     $journalItem->credit = 0;
    //                     $journalItem->save();
    //                     $journalItem->created_at = date('Y-m-d H:i:s', strtotime($bill->created_at));
    //                     $journalItem->updated_at = date('Y-m-d H:i:s', strtotime($bill->created_at));
    //                     $journalItem->save();
    //                     // calculate tax manul function
    //                     $tax_rate = Tax::where('id', $bill_product->tax)->first();
    //                     if ($tax_rate) {
    //                         $tax = ($tax_rate->rate / 100) * (($bill_product->price * $bill_product->quantity) - $bill_product->discount);
    //                     } else {
    //                         $tax = 0;
    //                     }
    //                     $payable += ((floatval($bill_product->quantity) * floatval($bill_product->price)) - floatval($bill_product->discount)) + floatval($tax);

    //                     $dataline = [
    //                         'account_id' => $product->expense_chartaccount_id,
    //                         'transaction_type' => 'Debit',
    //                         'transaction_amount' => $journalItem->debit,
    //                         'reference' => 'Bill Journal',
    //                         'reference_id' => $bill->voucher_id,
    //                         'reference_sub_id' => $journalItem->id,
    //                         'date' => $bill->bill_date,
    //                         'created_at' => date('Y-m-d H:i:s', strtotime($bill->created_at)),
    //                         'product_id' => $bill->id,
    //                         'product_type' => 'Bill Product',
    //                         'product_item_id' => $bill_product->id,
    //                     ];
    //                     Utility::addTransactionLines($dataline, 'create');

    //                     if ($tax != 0) {
    //                         $accounttax = Tax::where('id', $product->tax_id)->first();
    //                         $account_tax = ChartOfAccount::where('id', $accounttax->account_id)->first();
    //                         if (!$account_tax) {
    //                             $types_t = ChartOfAccountType::where('created_by', '=', $bill->created_by)->where('name', 'Liabilities')->first();
    //                             if ($types_t) {
    //                                 $sub_type_t = ChartOfAccountSubType::where('type', $types_t->id)->where('name', 'Current Liabilities')->first();
    //                                 $account_tax = ChartOfAccount::where('type', $types_t->id)->where('sub_type', $sub_type_t->id)->where('name', 'TAX')->first();
    //                                 if (!$account_tax) {
    //                                     $account_tax = ChartOfAccount::create([
    //                                         'name' => 'TAX',
    //                                         'code' => '10000',
    //                                         'type' => $types_t->id,
    //                                         'sub_type' => $sub_type_t->id,
    //                                         'is_enabled' => 1,
    //                                         'created_by' => $bill->created_by,
    //                                     ]);
    //                                 }
    //                             }
    //                         }

    //                         if ($account_tax) {
    //                             $journalItem = new JournalItem();
    //                             $journalItem->journal = $voucher->id;
    //                             $journalItem->account = @$account_tax->id;
    //                             $journalItem->prod_tax_id = $bill_product->id;
    //                             $journalItem->description = 'Tax on Bill No : ' . @$bill->bill_id;
    //                             $journalItem->debit = $tax;
    //                             $journalItem->credit = 0;
    //                             $journalItem->save();
    //                             $journalItem->created_at = date('Y-m-d H:i:s', strtotime($bill->created_at));
    //                             $journalItem->updated_at = date('Y-m-d H:i:s', strtotime($bill->created_at));
    //                             $journalItem->save();

    //                             $dataline = [
    //                                 'account_id' => $account_tax->id,
    //                                 'transaction_type' => 'Debit',
    //                                 'transaction_amount' => $journalItem->debit,
    //                                 'reference' => 'Bill Journal',
    //                                 'reference_id' => $bill->voucher_id,
    //                                 'reference_sub_id' => $journalItem->id,
    //                                 'date' => $bill->bill_date,
    //                                 'created_at' => date('Y-m-d H:i:s', strtotime($bill->created_at)),
    //                                 'product_id' => $bill->id,
    //                                 'product_type' => 'Bill Tax',
    //                                 'product_item_id' => $bill_product->id,
    //                             ];
    //                             Utility::addTransactionLines($dataline, 'create');
    //                         }
    //                     }

    //                     // }
    //                     // $itemAmount = ($bill_product->price * $bill_product->quantity) - ($bill_product->discount) + $totalTaxPrice;

    //                     // $data = [
    //                     //     'account_id' => $product->expense_chartaccount_id,
    //                     //     'transaction_type' => 'Debit',
    //                     //     'transaction_amount' => $itemAmount,
    //                     //     'reference' => 'Bill',
    //                     //     'reference_id' => $bill->id,
    //                     //     'reference_sub_id' => $product->id,
    //                     //     'date' => $bill->bill_date,
    //                     // ];
    //                     // Utility::addTransactionLines($data , 'edit');
    //                 }

    //                 $bill_accounts = BillAccount::where('ref_id', $bill->id)->get();
    //                 foreach ($bill_accounts as $bill_product) {
    //                     // $data = [
    //                     //     'account_id' => $bill_product->chart_account_id,
    //                     //     'transaction_type' => 'Debit',
    //                     //     'transaction_amount' => $bill_product->price,
    //                     //     'reference' => 'Bill Account',
    //                     //     'reference_id' => $bill_product->ref_id,
    //                     //     'reference_sub_id' => $bill_product->id,
    //                     //     'date' => $bill->bill_date,
    //                     // ];
    //                     // Utility::addTransactionLines($data , 'edit');
    //                     $journalItem = new JournalItem();
    //                     $journalItem->journal = $voucher->id;
    //                     $journalItem->account = $bill_product->chart_account_id;
    //                     $journalItem->product_ids = $bill_product->id;
    //                     $journalItem->description = $bill_product->description;
    //                     $journalItem->debit = $bill_product->price;
    //                     $journalItem->credit = 0;
    //                     $journalItem->save();
    //                     $journalItem->created_at = date('Y-m-d H:i:s', strtotime($bill->created_at));
    //                     $journalItem->updated_at = date('Y-m-d H:i:s', strtotime($bill->created_at));
    //                     $journalItem->save();
    //                     $dataline = [
    //                         'account_id' => $journalItem->account,
    //                         'transaction_type' => 'Debit',
    //                         'transaction_amount' => $journalItem->debit,
    //                         'reference' => 'Bill Journal',
    //                         'reference_id' => $bill->voucher_id,
    //                         'reference_sub_id' => $journalItem->id,
    //                         'date' => $bill->bill_date,
    //                         'created_at' => date('Y-m-d H:i:s', strtotime($bill->created_at)),
    //                         'product_id' => $bill->id,
    //                         'product_type' => 'Bill Account',
    //                         'product_item_id' => $bill_product->id,
    //                     ];
    //                     Utility::addTransactionLines($dataline, 'create');
    //                     $payable += $bill_product->price;
    //                 }

    //                 $types = ChartOfAccountType::where('created_by', '=', $bill->created_by)->where('name', 'Liabilities')->first();
    //                 if ($types) {
    //                     $sub_type = ChartOfAccountSubType::where('type', $types->id)->where('name', 'Current Liabilities')->first();
    //                     $account = ChartOfAccount::where('type', $types->id)->where('sub_type', $sub_type->id)->where('name', 'Account Payable')->first();
    //                 }
    //                 $journalItem = new JournalItem();
    //                 $journalItem->journal = $voucher->id;
    //                 $journalItem->account = $account->id;
    //                 $journalItem->description = 'Account Payable on Bill No : ' . $bill->bill_no;
    //                 $journalItem->debit = 0;
    //                 $journalItem->credit = $payable;
    //                 $journalItem->save();
    //                 $journalItem->created_at = date('Y-m-d H:i:s', strtotime($bill->created_at));
    //                 $journalItem->updated_at = date('Y-m-d H:i:s', strtotime($bill->created_at));
    //                 $journalItem->save();

    //                 $dataline = [
    //                     'account_id' => $account->id,
    //                     'transaction_type' => 'Credit',
    //                     'transaction_amount' => $journalItem->credit,
    //                     'reference' => 'Bill Journal',
    //                     'reference_id' => $voucher->id,
    //                     'reference_sub_id' => $journalItem->id,
    //                     'date' => $bill->bill_date,
    //                     'created_at' => date('Y-m-d H:i:s', strtotime($bill->created_at)),
    //                     'product_id' => $bill->id,
    //                     'product_type' => 'Bill Payable',
    //                     'product_item_id' => 0,
    //                 ];
    //                 Utility::addTransactionLines($dataline, 'create');

    //                 //utility activity log
    //                 Utility::makeActivityLog(\Auth::user()->id, 'Bill', $bill->id, 'Update Bill', $bill->type);
    //                 \DB::commit();
    //                 return redirect()->route('bill.index')->with('success', __('Bill successfully updated.'));
    //             } else {
    //                 return redirect()->back()->with('error', __('Permission denied.'));
    //             }
    //         } else {
    //             return redirect()->back()->with('error', __('Permission denied.'));
    //         }
    //     } catch (\Exception $e) {
    //         dd($e);
    //         \DB::rollBack();
    //         return redirect()->back()->with('error', __($e->getMessage()));
    //     }
    // }
    public function update(Request $request, Bill $bill)
    {
        \DB::beginTransaction();
        try {
            if (\Auth::user()->can('edit bill')) {
                if ($bill->created_by == \Auth::user()->creatorId()) {
                    $validator = \Validator::make(
                        $request->all(),
                        [
                            'vender_id' => 'required',
                            'bill_date' => 'required',
                            'due_date' => 'required',
                        ]
                    );
                    if ($validator->fails()) {
                        $messages = $validator->getMessageBag();
                        return response()->json([
                            'status' => 'error',
                            'message' => $validator->errors()->first()
                        ], 422);
                        // return redirect()->route('bill.index')->with('error', $messages->first());
                    }

                    // Validate billable items have customers
                    $billableValidationError = null;
                    
                    // Check category items
                    if ($request->has('categories') && is_array($request->categories)) {
                        foreach ($request->categories as $index => $categoryData) {
                            if (isset($categoryData['billable']) && $categoryData['billable']) {
                                if (empty($categoryData['customer_id'])) {
                                    $billableValidationError = __('Select a customer for each billable split line.') . ' (Category row ' . ($index + 1) . ')';
                                    break;
                                }
                            }
                        }
                    }
                    
                    // Check item details
                    if (!$billableValidationError && $request->has('items') && is_array($request->items)) {
                        foreach ($request->items as $index => $itemData) {
                            if (isset($itemData['billable']) && $itemData['billable']) {
                                if (empty($itemData['customer_id'])) {
                                    $billableValidationError = __('Select a customer for each billable split line.') . ' (Item row ' . ($index + 1) . ')';
                                    break;
                                }
                            }
                        }
                    }
                    
                    if ($billableValidationError) {
                        return response()->json([
                            'status' => 'error',
                            'message' => $billableValidationError
                        ], 422);
                    }

                    // Update bill basic details
                    $bill->vender_id = $request->vender_id;
                    $bill->bill_date = $request->bill_date;
                    $bill->due_date = $request->due_date;
                    $bill->user_type = 'vendor';
                    $bill->order_number = $request->order_number;
                    $bill->category_id = $request->category_id;
                    
                    // Update QBO fields
                    $bill->terms = $request->terms;
                    $bill->notes = $request->notes;
                    $bill->subtotal = $request->subtotal ?? 0;
                    $bill->total = $request->total ?? 0;
                    
                    $bill->save();

                    CustomField::saveData($bill, $request->customField);

                    // Check if bill has been approved (has voucher)
                    $voucher = JournalEntry::where('category', 'Bill')
                        ->where('reference_id', $bill->id)
                        ->where('voucher_type', 'JV')
                        ->first();

                    $isApproved = !is_null($voucher);

                    if ($isApproved) {
                        // SCENARIO 1: Bill is approved - Update journal entries
                        $updateResult = $this->updateApprovedBill($bill, $voucher, $request);
                        
                        // Check if updateApprovedBill returned an error response
                        if ($updateResult instanceof \Illuminate\Http\JsonResponse) {
                            \DB::rollBack();
                            return $updateResult;
                        }
                        
                        // Update the journal entry to reflect bill changes
                        $this->updateBillJournalEntry($bill, $voucher);
                    } else {
                        // SCENARIO 2: Bill is not approved yet - Just update bill products and categories
                        $this->updateDraftBill($bill, $request);
                    }

                    // Utility activity log
                    Utility::makeActivityLog(\Auth::user()->id, 'Bill', $bill->id, 'Update Bill', $bill->type);
                    \DB::commit();
                    return response()->json([
                        'status' => 'success',
                        'message' => __('Bill successfully updated.'),
                        'redirect' => url()->previous()
                    ], 200);
                    // return redirect()->route('bill.index')->with('success', __('Bill successfully updated.'));
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => __('Permission denied.')
                    ], 403);
                    // return redirect()->back()->with('error', __('Permission denied.'));
                }
            } else {
                 return response()->json([
                        'status' => 'error',
                        'message' => __('Permission denied.')
                    ], 403);
                // return redirect()->back()->with('error', __('Permission denied.'));
            }
        } catch (\Exception $e) {
            dd($e);
            \DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => __($e->getMessage())
            ], 500);
            // return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    /**
     * Update Draft Bill (Before Approval)
     */
    private function updateDraftBill($bill, $request)
    {
        // Delete old stock reports for this bill
        StockReport::where('type', '=', 'bill')->where('type_id', '=', $bill->id)->delete();

        // ========================================
        // HANDLE CATEGORY DETAILS (Account-based expenses)
        // Delete ALL old category details and replace with new ones
        // ========================================
        BillAccount::where('ref_id', $bill->id)->where('type', 'Bill')->delete();

        if ($request->has('category') && is_array($request->category)) {
            foreach ($request->category as $index => $categoryData) {
                // Skip empty rows
                if (empty($categoryData['account_id']) && empty($categoryData['amount'])) {
                    continue;
                }

                $billAccount = new BillAccount();
                $billAccount->ref_id = $bill->id;
                $billAccount->type = 'Bill';
                $billAccount->chart_account_id = $categoryData['account_id'] ?? null;
                $billAccount->description = $categoryData['description'] ?? '';
                $billAccount->price = $categoryData['amount'] ?? 0;
                
                // Handle billable and customer fields
                $billAccount->billable = isset($categoryData['billable']) ? 1 : 0;
                $billAccount->customer_id = $categoryData['customer_id'] ?? null;
                
                // Handle tax checkbox
                $billAccount->tax = isset($categoryData['tax']) ? 1 : 0;
                
                // IMPORTANT: Save order to maintain exact row position
                $billAccount->order = $index;
                
                $billAccount->save();
            }
        }

        // ========================================
        // HANDLE ITEM DETAILS (Product/Service-based)
        // Update existing or create new items
        // Note: We allow the same product in multiple rows (no merging)
        // ========================================
        $existingItemIds = [];
        
        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $index => $itemData) {
                // Skip empty rows
                if (empty($itemData['product_id']) && empty($itemData['quantity']) && empty($itemData['amount'])) {
                    continue;
                }

                // Check if updating existing item or creating new
                $billProduct = null;
                if (!empty($itemData['id'])) {
                    $billProduct = BillProduct::find($itemData['id']);
                    if ($billProduct && $billProduct->bill_id == $bill->id) {
                        $existingItemIds[] = $billProduct->id;
                        // Restore old quantity before updating
                        Utility::total_quantity('minus', $billProduct->quantity, $billProduct->product_id);
                    } else {
                        $billProduct = null;
                    }
                }

                // Create new product if not found
                if ($billProduct == null) {
                    $billProduct = new BillProduct();
                    $billProduct->bill_id = $bill->id;
                }

                // Update product details
                $billProduct->product_id = $itemData['product_id'] ?? null;
                $billProduct->description = $itemData['description'] ?? '';
                $billProduct->quantity = $itemData['quantity'] ?? 1;
                $billProduct->price = $itemData['price'] ?? 0;
                $billProduct->discount = $itemData['discount'] ?? 0;
                
                // Handle tax checkbox
                $billProduct->tax = isset($itemData['tax']) ? 1 : 0;
                
                // Calculate line total
                $billProduct->line_total = $itemData['amount'] ?? ($billProduct->quantity * $billProduct->price);
                
                // QBO specific fields - billable and customer
                $billProduct->billable = isset($itemData['billable']) ? 1 : 0;
                $billProduct->customer_id = $itemData['customer_id'] ?? null;
                
                // IMPORTANT: Save order to maintain exact row position
                // This allows the same product to appear in multiple rows with different settings
                $billProduct->order = $index;
                
                $billProduct->save();
                $product = ProductService::find($billProduct->product_id);
                if ($product) {
                    if($product->type == 'product'){
                        $account = $product->asset_chartaccount_id ?? $product->expense_chartaccount_id;
                    }else{
                        $account = $product->expense_chartaccount_id;
                    }
                }
                $billProduct->account_id = $account;
                $billProduct->save();
                $existingItemIds[] = $billProduct->id;

                // Update Inventory if product exists
                if (!empty($billProduct->product_id)) {
                    Utility::total_quantity('plus', $billProduct->quantity, $billProduct->product_id);
                    $type = 'bill';
                    $type_id = $bill->id;
                    $description = $billProduct->quantity . '  ' . __('quantity purchase in bill') . ' ' . \Auth::user()->billNumberFormat($bill->bill_id);
                    Utility::addProductStock($billProduct->product_id, $billProduct->quantity, $type, $description, $type_id);
                }
            }
        }

        // Delete items that were removed (not in the existingItemIds array)
        if (!empty($existingItemIds)) {
            $deletedProducts = BillProduct::where('bill_id', $bill->id)
                ->whereNotIn('id', $existingItemIds)
                ->get();
            
            foreach ($deletedProducts as $deletedProduct) {
                // Restore inventory
                if (!empty($deletedProduct->product_id)) {
                    Utility::total_quantity('minus', $deletedProduct->quantity, $deletedProduct->product_id);
                }
                $deletedProduct->delete();
            }
        } else {
            // If no items remain, delete all
            $deletedProducts = BillProduct::where('bill_id', $bill->id)->get();
            foreach ($deletedProducts as $deletedProduct) {
                // Restore inventory
                if (!empty($deletedProduct->product_id)) {
                    Utility::total_quantity('minus', $deletedProduct->quantity, $deletedProduct->product_id);
                }
                $deletedProduct->delete();
            }
        }
    }

    /**
     * Update Approved Bill (After Approval - with Journal Entries)
     * Note: Journal entries are now handled by updateBillJournalEntry() using JournalService
     */
    private function updateApprovedBill($bill, $voucher, $request)
    {
        // Delete old stock reports
        StockReport::where('type', '=', 'bill')->where('type_id', '=', $bill->id)->delete();

        // Delete old transaction lines (if any exist from old system)
        TransactionLines::where('reference_id', $bill->id)->where('reference', 'Bill')->delete();
        TransactionLines::where('reference_id', $bill->id)->where('reference', 'Bill Account')->delete();

        if ($voucher) {
            // Note: Journal items will be recreated by JournalService::updateJournalEntry
            // which is called after this method in the update() function
            TransactionLines::where('reference_id', $voucher->id)->where('reference', 'Bill Journal')->delete();
        }

        // ========================================
        // HANDLE CATEGORY DETAILS (Account-based expenses)
        // Update existing or create new, delete only removed ones
        // ========================================
        $existingAccountIds = [];
        
        if ($request->has('categories') && is_array($request->categories)) {
            foreach ($request->categories as $index => $categoryData) {
                // Skip empty rows
                if (empty($categoryData['account_id']) && empty($categoryData['amount'])) {
                    continue;
                }

                // Check if updating existing account or creating new
                $billAccount = null;
                if (!empty($categoryData['id'])) {
                    $billAccount = BillAccount::find($categoryData['id']);
                    if ($billAccount && $billAccount->ref_id == $bill->id && $billAccount->type == 'Bill') {
                        $existingAccountIds[] = $billAccount->id;
                    } else {
                        $billAccount = null;
                    }
                }
                
                // Create new account if not found
                if ($billAccount == null) {
                    $billAccount = new BillAccount();
                    $billAccount->ref_id = $bill->id;
                    $billAccount->type = 'Bill';
                }
                
                $billAccount->chart_account_id = $categoryData['account_id'] ?? null;
                $billAccount->description = $categoryData['description'] ?? '';
                $billAccount->price = $categoryData['amount'] ?? 0;
                
                // Handle billable and customer fields
                $billAccount->billable = isset($categoryData['billable']) ? 1 : 0;
                $billAccount->customer_id = $categoryData['customer_id'] ?? null;
                
                // Handle tax checkbox
                $billAccount->tax = isset($categoryData['tax']) ? 1 : 0;
                
                // IMPORTANT: Save order to maintain exact row position
                $billAccount->order = $index;
                
                $billAccount->save();
                $existingAccountIds[] = $billAccount->id;
            }
        }
        
      

        // Delete accounts that were removed (not in the existingAccountIds array)
        if (!empty($existingAccountIds)) {
            $deletedAccounts = BillAccount::where('ref_id', $bill->id)
                ->where('type', 'Bill')
                ->whereNotIn('id', $existingAccountIds)
                ->get();
               
            
            // Check if any being deleted are billable and linked to invoices
            foreach ($deletedAccounts as $deletedAccount) {
                if ($deletedAccount->billable == 1 && $deletedAccount->status == 1) {
                    // \Log::warning('BillAccount validation triggered', [
                    //     'account_id' => $deletedAccount->id,
                    //     'billable' => $deletedAccount->billable,
                    //     'status' => $deletedAccount->status,
                    //     'account' => $deletedAccount->toArray()
                    // ]);
                    
                    \DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => __('This transaction is linked to an invoice. Saving this transaction would make the invoice no longer valid. In order to make this change, edit the invoice first, remove this transaction from it and remake this change.')
                    ], 400);
                }
            }
            
            foreach ($deletedAccounts as $deletedAccount) {
                $deletedAccount->delete();
            }
        } else {
            // If no accounts remain, delete all - check if any are billable first
            $deletedAccounts = BillAccount::where('ref_id', $bill->id)->where('type', 'Bill')->get();
            foreach ($deletedAccounts as $deletedAccount) {
                if ($deletedAccount->billable == 1 && $deletedAccount->status == 1) {
                    // \Log::warning('BillAccount validation triggered (all deleted)', [
                    //     'account_id' => $deletedAccount->id,
                    //     'billable' => $deletedAccount->billable,
                    //     'status' => $deletedAccount->status
                    // ]);
                    
                    \DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => __('This transaction is linked to an invoice. Saving this transaction would make the invoice no longer valid. In order to make this change, edit the invoice first, remove this transaction from it and remake this change.')
                    ], 400);
                }
            }
            
            foreach ($deletedAccounts as $deletedAccount) {
                $deletedAccount->delete();
            }
        }

        // ========================================
        // HANDLE ITEM DETAILS (Product/Service-based)
        // Update existing or create new items
        // Note: We allow the same product in multiple rows (no merging)
        // ========================================
        $existingItemIds = [];
        
        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $index => $itemData) {
                // Skip empty rows
                if (empty($itemData['product_id']) && empty($itemData['quantity']) && empty($itemData['price'])) {
                    continue;
                }
                // Check if updating existing item or creating new
                $billProduct = null;
                if (!empty($itemData['id'])) {
                    $billProduct = BillProduct::find($itemData['id']);
                    if ($billProduct && $billProduct->bill_id == $bill->id) {
                        $existingItemIds[] = $billProduct->id;
                        // Restore old quantity before updating
                        Utility::total_quantity('minus', $billProduct->quantity, $billProduct->product_id);
                    } else {
                        $billProduct = null;
                    }
                }

                // Create new product if not found
                if ($billProduct == null) {
                    $billProduct = new BillProduct();
                    $billProduct->bill_id = $bill->id;
                }

                // Update product details
                $billProduct->product_id = $itemData['product_id'] ?? null;
                $billProduct->description = $itemData['description'] ?? '';
                $billProduct->quantity = $itemData['quantity'] ?? 1;
                $billProduct->price = $itemData['price'] ?? 0;
                $billProduct->discount = $itemData['discount'] ?? 0;
                
                // Handle tax checkbox
                $billProduct->tax = isset($itemData['tax']) ? 1 : 0;
                
                // Calculate line total
                $billProduct->line_total = $itemData['amount'] ?? ($billProduct->quantity * $billProduct->price);
                
                // QBO specific fields - billable and customer
                $billProduct->billable = isset($itemData['billable']) ? 1 : 0;
                $billProduct->customer_id = $itemData['customer_id'] ?? null;
                
                // IMPORTANT: Save order to maintain exact row position
                // This allows the same product to appear in multiple rows with different settings
                $billProduct->order = $index;
                
                $billProduct->save();
                $product = ProductService::find($billProduct->product_id);
                if ($product) {
                    if($product->type == 'product'){
                        $account = $product->asset_chartaccount_id ?? $product->expense_chartaccount_id;
                    }else{
                        $account = $product->expense_chartaccount_id;
                    }
                }
                $billProduct->account_id = $account;
                $billProduct->save();
                $existingItemIds[] = $billProduct->id;

                // Update Inventory if product exists
                if (!empty($billProduct->product_id)) {
                    Utility::total_quantity('plus', $billProduct->quantity, $billProduct->product_id);
                    $type = 'bill';
                    $type_id = $bill->id;
                    $description = $billProduct->quantity . '  ' . __('quantity purchase in bill') . ' ' . \Auth::user()->billNumberFormat($bill->bill_id);
                    Utility::addProductStock($billProduct->product_id, $billProduct->quantity, $type, $description, $type_id);
                }
            }
        }

        // Delete items that were removed (not in the existingItemIds array)
        if (!empty($existingItemIds)) {
            $deletedProducts = BillProduct::where('bill_id', $bill->id)
                ->whereNotIn('id', $existingItemIds)
                ->get();
            
            // Log all products being deleted to debug
            // \Log::info('Products being deleted', [
            //     'bill_id' => $bill->id,
            //     'deleted_products' => $deletedProducts->map(function($p) {
            //         return [
            //             'id' => $p->id,
            //             'product_id' => $p->product_id,
            //             'billable' => $p->billable,
            //             'status' => $p->status,
            //             'description' => $p->description
            //         ];
            //     })->toArray()
            // ]);
            
            // Check if any being deleted are billable and linked to invoices
            foreach ($deletedProducts as $deletedProduct) {
                if ($deletedProduct->billable == 1 && $deletedProduct->status == 1) {
                    // \Log::warning('BillProduct validation triggered', [
                    //     'product_id' => $deletedProduct->id,
                    //     'billable' => $deletedProduct->billable,
                    //     'status' => $deletedProduct->status,
                    //     'product' => $deletedProduct->toArray()
                    // ]);
                    
                    return response()->json([
                        'status' => 'error',
                        'message' => __('This transaction is linked to an invoice. Saving this transaction would make the invoice no longer valid. In order to make this change, edit the invoice first, remove this transaction from it and remake this change.')
                    ], 400);
                }
            }
            
            foreach ($deletedProducts as $deletedProduct) {
                // Restore inventory
                if (!empty($deletedProduct->product_id)) {
                    Utility::total_quantity('minus', $deletedProduct->quantity, $deletedProduct->product_id);
                }
                $deletedProduct->delete();
            }
        } else {
            // If no items remain, delete all - check if any are billable first
            $deletedProducts = BillProduct::where('bill_id', $bill->id)->get();
            foreach ($deletedProducts as $deletedProduct) {
                if ($deletedProduct->billable == 1 && $deletedProduct->status == 1) {
                    return response()->json([
                        'status' => 'error',
                        'message' => __('This transaction is linked to an invoice. Saving this transaction would make the invoice no longer valid. In order to make this change, edit the invoice first, remove this transaction from it and remake this change.')
                    ], 400);
                    // throw new \Exception('This transaction is linked to an invoice. Saving this transaction would make the invoice no longer valid. In order to make this change, edit the invoice first, remove this transaction from it and remake this change.');
                }
            }
            
            foreach ($deletedProducts as $deletedProduct) {
                // Restore inventory
                if (!empty($deletedProduct->product_id)) {
                    Utility::total_quantity('minus', $deletedProduct->quantity, $deletedProduct->product_id);
                }
                $deletedProduct->delete();
            }
        }

        // Note: Journal entries and items will be created/updated by
        // JournalService::updateJournalEntry() which is called in the update() method
    }

    public function destroy_old(Bill $bill){
        TransactionLines::where('reference_id', $bill->id)->where('reference', 'Bill')->delete();
        TransactionLines::where('reference_id', $bill->id)->where('reference', 'Bill Account')->delete();

        if ($voucher) {
            JournalItem::where('journal', $voucher->id)->delete();
            TransactionLines::where('reference_id', $bill->voucher_id)->where('reference', 'Bill Journal')->where('product_type', 'Bill Product')->delete();
            TransactionLines::where('reference_id', $bill->voucher_id)->where('reference', 'Bill Journal')->where('product_type', 'Bill Tax')->delete();
            TransactionLines::where('reference_id', $bill->voucher_id)->where('reference', 'Bill Journal')->where('product_type', 'Bill Account')->delete();
            TransactionLines::where('reference_id', $bill->voucher_id)->where('reference', 'Bill Journal')->where('product_type', 'Bill Payable')->delete();
        }

        $payable = 0;

        // ========================================
        // HANDLE CATEGORY DETAILS (Account-based expenses)
        // Delete ALL old category details and replace with new ones
        // ========================================
        BillAccount::where('ref_id', $bill->id)->where('type', 'Bill')->delete();

        if ($request->has('category') && is_array($request->category)) {
            foreach ($request->category as $index => $categoryData) {
                // Skip empty rows
                if (empty($categoryData['account_id']) && empty($categoryData['amount'])) {
                    continue;
                }

                $billAccount = new BillAccount();
                $billAccount->ref_id = $bill->id;
                $billAccount->type = 'Bill';
                $billAccount->chart_account_id = $categoryData['account_id'] ?? null;
                $billAccount->description = $categoryData['description'] ?? '';
                $billAccount->price = $categoryData['amount'] ?? 0;
                
                // Handle billable and customer fields
                $billAccount->billable = isset($categoryData['billable']) ? 1 : 0;
                $billAccount->customer_id = $categoryData['customer_id'] ?? null;
                
                // Handle tax checkbox
                $billAccount->tax = isset($categoryData['tax']) ? 1 : 0;
                
                // IMPORTANT: Save order to maintain exact row position
                $billAccount->order = $index;
                
                $billAccount->save();
            }
        }

        // ========================================
        // HANDLE ITEM DETAILS (Product/Service-based)
        // Update existing or create new items
        // Note: We allow the same product in multiple rows (no merging)
        // ========================================
        $existingItemIds = [];
        
        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $index => $itemData) {
                // Skip empty rows
                if (empty($itemData['product_id']) && empty($itemData['quantity']) && empty($itemData['price'])) {
                    continue;
                }

                // Check if updating existing item or creating new
                $billProduct = null;
                if (!empty($itemData['id'])) {
                    $billProduct = BillProduct::find($itemData['id']);
                    if ($billProduct && $billProduct->bill_id == $bill->id) {
                        $existingItemIds[] = $billProduct->id;
                        // Restore old quantity before updating
                        Utility::total_quantity('minus', $billProduct->quantity, $billProduct->product_id);
                    } else {
                        $billProduct = null;
                    }
                }

                // Create new product if not found
                if ($billProduct == null) {
                    $billProduct = new BillProduct();
                    $billProduct->bill_id = $bill->id;
                }

                // Update product details
                $billProduct->product_id = $itemData['product_id'] ?? null;
                $billProduct->description = $itemData['description'] ?? '';
                $billProduct->quantity = $itemData['quantity'] ?? 1;
                $billProduct->price = $itemData['price'] ?? 0;
                $billProduct->discount = $itemData['discount'] ?? 0;
                
                // Handle tax checkbox
                $billProduct->tax = isset($itemData['tax']) ? 1 : 0;
                
                // Calculate line total
                $billProduct->line_total = $itemData['amount'] ?? ($billProduct->quantity * $billProduct->price);
                
                // QBO specific fields - billable and customer
                $billProduct->billable = isset($itemData['billable']) ? 1 : 0;
                $billProduct->customer_id = $itemData['customer_id'] ?? null;
                
                // IMPORTANT: Save order to maintain exact row position
                // This allows the same product to appear in multiple rows with different settings
                $billProduct->order = $index;
                
                $billProduct->save();
                $product = ProductService::find($billProduct->product_id);
                if ($product) {
                    if($product->type == 'product'){
                        $account = $product->asset_chartaccount_id ?? $product->expense_chartaccount_id;
                    }else{
                        $account = $product->expense_chartaccount_id;
                    }
                }
                $billProduct->account_id = $account;
                $billProduct->save();
                $existingItemIds[] = $billProduct->id;

                // Update Inventory if product exists
                if (!empty($billProduct->product_id)) {
                    Utility::total_quantity('plus', $billProduct->quantity, $billProduct->product_id);
                    $type = 'bill';
                    $type_id = $bill->id;
                    $description = $billProduct->quantity . '  ' . __('quantity purchase in bill') . ' ' . \Auth::user()->billNumberFormat($bill->bill_id);
                    Utility::addProductStock($billProduct->product_id, $billProduct->quantity, $type, $description, $type_id);
                }
            }
        }

        // Delete items that were removed (not in the existingItemIds array)
        if (!empty($existingItemIds)) {
            $deletedProducts = BillProduct::where('bill_id', $bill->id)
                ->whereNotIn('id', $existingItemIds)
                ->get();
            
            foreach ($deletedProducts as $deletedProduct) {
                // Restore inventory
                if (!empty($deletedProduct->product_id)) {
                    Utility::total_quantity('minus', $deletedProduct->quantity, $deletedProduct->product_id);
                }
                $deletedProduct->delete();
            }
        } else {
            // If no items remain, delete all
            $deletedProducts = BillProduct::where('bill_id', $bill->id)->get();
            foreach ($deletedProducts as $deletedProduct) {
                // Restore inventory
                if (!empty($deletedProduct->product_id)) {
                    Utility::total_quantity('minus', $deletedProduct->quantity, $deletedProduct->product_id);
                }
                $deletedProduct->delete();
            }
        }

        // ========================================
        // RECREATE JOURNAL ENTRIES FOR PRODUCTS
        // ========================================
        $bill_products = BillProduct::where('bill_id', $bill->id)->get();
        foreach ($bill_products as $bill_product) {
            $tax = 0;
            $product = ProductService::find($bill_product->product_id);

            // Create journal item for product
            $journalItem = new JournalItem();
            $journalItem->journal = $voucher->id;
            $journalItem->account = @$product->expense_chartaccount_id;
            $journalItem->product_ids = @$bill_product->id;
            $journalItem->description = @$bill_product->description;
            $journalItem->debit = (($bill_product->quantity * $bill_product->price) - $bill_product->discount);
            $journalItem->credit = 0;
            $journalItem->save();
            $journalItem->created_at = date('Y-m-d H:i:s', strtotime($bill->created_at));
            $journalItem->updated_at = date('Y-m-d H:i:s', strtotime($bill->created_at));
            $journalItem->save();

            // Calculate tax
            $tax_rate = Tax::where('id', $bill_product->tax)->first();
            if ($tax_rate) {
                $tax = ($tax_rate->rate / 100) * (($bill_product->price * $bill_product->quantity) - $bill_product->discount);
            } else {
                $tax = 0;
            }
            $payable += ((floatval($bill_product->quantity) * floatval($bill_product->price)) - floatval($bill_product->discount)) + floatval($tax);

            // Create transaction line for product
            // Handle tax
            if ($tax != 0) {
                $accounttax = Tax::where('id', $product->tax_id)->first();
                $account_tax = ChartOfAccount::where('id', $accounttax->account_id)->first();
                if (!$account_tax) {
                    $types_t = ChartOfAccountType::where('created_by', '=', $bill->created_by)->where('name', 'Liabilities')->first();
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
                                'created_by' => $bill->created_by,
                            ]);
                        }
                    }
                }

                if ($account_tax) {
                    $journalItem = new JournalItem();
                    $journalItem->journal = $voucher->id;
                    $journalItem->account = @$account_tax->id;
                    $journalItem->prod_tax_id = $bill_product->id;
                    $journalItem->description = 'Tax on Bill No : ' . @$bill->bill_id;
                    $journalItem->debit = $tax;
                    $journalItem->credit = 0;
                    $journalItem->save();
                    $journalItem->created_at = date('Y-m-d H:i:s', strtotime($bill->created_at));
                    $journalItem->updated_at = date('Y-m-d H:i:s', strtotime($bill->created_at));
                    $journalItem->save();

                    $dataline = [
                        'account_id' => $account_tax->id,
                        'transaction_type' => 'Debit',
                        'transaction_amount' => $journalItem->debit,
                        'reference' => 'Bill Journal',
                        'reference_id' => $voucher->id,
                        'reference_sub_id' => $journalItem->id,
                        'date' => $bill->bill_date,
                        'created_at' => date('Y-m-d H:i:s', strtotime($bill->created_at)),
                        'product_id' => $bill->id,
                        'product_type' => 'Bill Tax',
                        'product_item_id' => $bill_product->id,
                    ];
                    Utility::addTransactionLines($dataline, 'create');
                }
            }
        }

        // ========================================
        // RECREATE JOURNAL ENTRIES FOR BILL ACCOUNTS (CATEGORIES)
        // ========================================
        $bill_accounts = BillAccount::where('ref_id', $bill->id)->where('type', 'Bill')->get();
        foreach ($bill_accounts as $bill_account) {
            $journalItem = new JournalItem();
            $journalItem->journal = $voucher->id;
            $journalItem->account = $bill_account->chart_account_id;
            $journalItem->product_ids = $bill_account->id;
            $journalItem->description = $bill_account->description;
            $journalItem->debit = $bill_account->price;
            $journalItem->credit = 0;
            $journalItem->save();
            $journalItem->created_at = date('Y-m-d H:i:s', strtotime($bill->created_at));
            $journalItem->updated_at = date('Y-m-d H:i:s', strtotime($bill->created_at));
            $journalItem->save();

            $dataline = [
                'account_id' => $journalItem->account,
                'transaction_type' => 'Debit',
                'transaction_amount' => $journalItem->debit,
                'reference' => 'Bill Journal',
                'reference_id' => $voucher->id,
                'reference_sub_id' => $journalItem->id,
                'date' => $bill->bill_date,
                'created_at' => date('Y-m-d H:i:s', strtotime($bill->created_at)),
                'product_id' => $bill->id,
                'product_type' => 'Bill Account',
                'product_item_id' => $bill_account->id,
            ];
            Utility::addTransactionLines($dataline, 'create');
            $payable += $bill_account->price;
        }

        // ========================================
        // CREATE ACCOUNT PAYABLE ENTRY
        // ========================================
        $types = ChartOfAccountType::where('created_by', '=', $bill->created_by)->where('name', 'Liabilities')->first();
        if ($types) {
            $sub_type = ChartOfAccountSubType::where('type', $types->id)->where('name', 'Current Liabilities')->first();
            $account = ChartOfAccount::where('type', $types->id)->where('sub_type', $sub_type->id)->where('name', 'Account Payable')->first();
        }

        $journalItem = new JournalItem();
        $journalItem->journal = $voucher->id;
        $journalItem->account = $account->id;
        $journalItem->description = 'Account Payable on Bill No : ' . $bill->bill_id;
        $journalItem->debit = 0;
        $journalItem->credit = $payable;
        $journalItem->save();
        $journalItem->created_at = date('Y-m-d H:i:s', strtotime($bill->created_at));
        $journalItem->updated_at = date('Y-m-d H:i:s', strtotime($bill->created_at));
        $journalItem->save();

        $dataline = [
            'account_id' => $account->id,
            'transaction_type' => 'Credit',
            'transaction_amount' => $journalItem->credit,
            'reference' => 'Bill Journal',
            'reference_id' => $voucher->id,
            'reference_sub_id' => $journalItem->id,
            'date' => $bill->bill_date,
            'created_at' => date('Y-m-d H:i:s', strtotime($bill->created_at)),
            'product_id' => $bill->id,
            'product_type' => 'Bill Payable',
            'product_item_id' => 0,
        ];
        Utility::addTransactionLines($dataline, 'create');
    }
    public function destroy(Bill $bill)
    {
        if (\Auth::user()->can('delete bill')) {
            if ($bill->created_by == \Auth::user()->creatorId()) {
                
                // ========================================
                // VALIDATION: Check if any billable items are linked to invoices
                // ========================================
                
                // Check BillProducts
                $billableProducts = BillProduct::where('bill_id', $bill->id)
                    ->where('billable', 1)
                    ->where('status', 1)
                    ->count();
                
                if ($billableProducts > 0) {
                    return redirect()->back()->with('error', __('This bill cannot be deleted as it has billable items linked to invoices. Please remove those items from invoices first.'));
                }
                
                // Check BillAccounts
                $billableAccounts = BillAccount::where('ref_id', $bill->id)
                    ->where('type', 'Bill')
                    ->where('billable', 1)
                    ->where('status', 1)
                    ->count();
                
                if ($billableAccounts > 0) {
                    return redirect()->back()->with('error', __('This bill cannot be deleted as it has billable categories linked to invoices. Please remove those categories from invoices first.'));
                }
                
                // ========================================
                // DELETE BILL - Proceed with deletion
                // ========================================
                
                $billpayments = $bill->payments;

                foreach ($billpayments as $key => $value) {
                    Utility::bankAccountBalance($value->account_id, $value->amount, 'credit');
                    $transaction = Transaction::where('payment_id', $value->id)->first();
                    $transaction->delete();

                    $billpayment = BillPayment::find($value->id)->first();
                    $billpayment->delete();
                    if (@$value->voucher_id != 0 || @$value->voucher_id != null) {
                        JournalEntry::where('id', $value->voucher_id)->where('category', 'Bill')->delete();
                        JournalItem::where('journal', $value->voucher_id)->delete();
                    }
                }

                //log
                Utility::makeActivityLog(\Auth::user()->id, 'Bill', $bill->id, 'Delete Bill', $bill->type);
                
                // Delete journal entry and related records for this bill
                $journalEntry = JournalEntry::where('category', 'Bill')
                    ->where('reference_id', $bill->id)
                    ->where('voucher_type', 'JV')
                    ->first();
                
                if ($journalEntry) {
                    // Delete journal items
                    JournalItem::where('journal', $journalEntry->id)->delete();
                    
                    // Delete transaction lines related to this journal
                    TransactionLines::where('reference_id', $journalEntry->id)
                        ->where('reference', 'Bill Journal')
                        ->delete();
                    
                    // Delete the journal entry itself
                    $journalEntry->delete();
                }
                
                $bill->delete();

                if ($bill->vender_id != 0 && $bill->status != 0) {
                    Utility::updateUserBalance('vendor', $bill->vender_id, $bill->getDue(), 'credit');
                }
                BillProduct::where('bill_id', '=', $bill->id)->delete();

                BillAccount::where('ref_id', '=', $bill->id)->delete();

                DebitNote::where('bill', '=', $bill->id)->delete();

                TransactionLines::where('product_id', $bill->id)->where('reference', 'Bill Journal')->delete();
                TransactionLines::where('reference_id', $bill->id)->where('reference', 'Bill Journal')->delete();
                TransactionLines::where('reference_id', $bill->id)->where('reference', 'Bill')->delete();
                TransactionLines::where('reference_id', $bill->id)->where('reference', 'Bill Account')->delete();
                TransactionLines::where('reference_id', $bill->id)->where('reference', 'Bill Payment')->delete();

                return redirect()->route('bill.index')->with('success', __('Bill successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

    }

    function billNumber()
    {
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
        $latest = Bill::where($column, '=', $ownerId)->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->bill_id + 1;
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

    public function productDestroy(Request $request)
    {
        \DB::beginTransaction();
        try {
            if (\Auth::user()->can('delete bill product')) {
                $billProduct = BillProduct::find($request->id);
                $bill = Bill::find($billProduct->bill_id);
                Utility::updateUserBalance('vendor', $bill->vender_id, $request->amount, 'credit');

                $productService = ProductService::find($billProduct->product_id);
                $b_ac = BillAccount::where('id', '=', $request->account_id)->first();
                if ($bill->status != 0 && $bill->status != 7 && $bill->status != 5) {
                    $prod_id = TransactionLines::where('reference_id', $bill->voucher_id)->where('product_item_id', $billProduct->id)->where('reference', 'Bill Journal')->where('product_type', 'Bill Product')->first();
                    if ($b_ac) {
                        $prod_account = TransactionLines::where('reference_id', $bill->voucher_id)->where('product_item_id', $b_ac->id)->where('reference', 'Bill Journal')->where('product_type', 'Bill Account')->first();
                    } else {
                        $prod_account = '';
                    }
                    $prod_tax = TransactionLines::where('reference_id', $bill->voucher_id)->where('product_item_id', $billProduct->id)->where('reference', 'Bill Journal')->where('product_type', 'Bill Tax')->first();
                    $inv_receviable = TransactionLines::where('reference_id', $bill->voucher_id)->where('reference', 'Bill Journal')->where('product_type', 'Bill Payable')->first();
                    $inv_receviable->credit = $inv_receviable->credit - (@$prod_id->debit + @$prod_tax->debit + @$prod_account->debit);
                    $inv_receviable->save();
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
                    TransactionLines::where('reference_sub_id', $productService->id)->where('reference', 'Bill Product')->delete();

                    $journal_item = JournalItem::where('journal', $bill->voucher_id)->where('product_ids', $billProduct->id)->first();
                    if ($b_ac) {
                        $journal_account = JournalItem::where('journal', $bill->voucher_id)->where('account', $b_ac->chart_account_id)->where('product_ids', '=', $b_ac->id)->first();
                    } else {
                        $journal_account = '';
                    }
                    $journal_tax = JournalItem::where('journal', $bill->voucher_id)->where('prod_tax_id', $billProduct->id)->first();
                    $types = ChartOfAccountType::where('created_by', '=', $bill->created_by)->where('name', 'Assets')->first();
                    if ($types) {
                        $sub_type = ChartOfAccountSubType::where('type', $types->id)->where('name', 'Current Asset')->first();
                        $account = ChartOfAccount::where('type', $types->id)->where('sub_type', $sub_type->id)->where('name', 'Account Payables')->first();
                    }
                    if ($account) {
                        $item_last = JournalItem::where('journal', $bill->voucher_id)->where('account', $account->id)->first();
                        $item_last->credit = $item_last->credit - (@$journal_item->debit + @$journal_tax->debit + @$journal_account->debit);
                        $item_last->save();
                    } else {
                        $item_last = JournalItem::where('journal', $bill->voucher_id)->where('id', $inv_receviable->reference_sub_id)->first();
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
                }
                BillProduct::where('id', '=', $request->id)->delete();
                BillAccount::where('id', '=', $request->account_id)->delete();
                Utility::makeActivityLog(\Auth::user()->id, 'Bill', $billProduct->id, 'Delete Bill Products', $billProduct->description);
                \DB::commit();
                return redirect()->back()->with('success', __('Bill product successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } catch (\Exception $e) {
            dd($e);
            \DB::rollBack();
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function sent($id)
    {
        if (\Auth::user()->can('send bill')) {
            $bill = Bill::where('id', $id)->first();
            $bill->send_date = date('Y-m-d');
            $bill->status = 1;
            $bill->save();

            $vender = Vender::where('id', $bill->vender_id)->first();

            $bill->name = !empty($vender) ? $vender->name : '';
            $bill->bill = \Auth::user()->billNumberFormat($bill->bill_id);

            $billId = Crypt::encrypt($bill->id);
            $bill->url = route('bill.pdf', $billId);
            Utility::updateUserBalance('vendor', $vender->id, $bill->getTotal(), 'debit');


            $vendorArr = [
                'vender_bill_name' => $bill->name,
                'vender_bill_number' => $bill->bill,
                'vender_bill_url' => $bill->url,

            ];

            $bill_products = BillProduct::where('bill_id', $bill->id)->get();
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
                //     'reference' => 'Bill',
                //     'reference_id' => $bill->id,
                //     'reference_sub_id' => $product->id,
                //     'date' => $bill->bill_date,
                // ];
                // Utility::addTransactionLines($data , 'create');
            }

            $bill_accounts = BillAccount::where('ref_id', $bill->id)->get();
            foreach ($bill_accounts as $bill_product) {
                $data = [
                    'account_id' => $bill_product->chart_account_id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $bill_product->price,
                    'reference' => 'Bill Account',
                    'reference_id' => $bill_product->ref_id,
                    'reference_sub_id' => $bill_product->id,
                    'date' => $bill->bill_date,
                ];
                Utility::addTransactionLines($data, 'create');
            }

            //            dd($vendorArr);
            $resp = Utility::sendEmailTemplate('vender_bill_sent', [$vender->id => $vender->email], $vendorArr);
            return redirect()->back()->with('success', __('Bill successfully sent.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

    }

    public function resent($id)
    {
        //        if(\Auth::user()->can('send bill'))
//        {

        // Send Email
        $setings = Utility::settings();

        if ($setings['bill_resent'] == 1) {
            $bill = Bill::where('id', $id)->first();
            $vender = Vender::where('id', $bill->vender_id)->first();
            $bill->name = !empty($vender) ? $vender->name : '';
            $bill->bill = \Auth::user()->billNumberFormat($bill->bill_id);
            $billId = Crypt::encrypt($bill->id);
            $bill->url = route('bill.pdf', $billId);
            $billResendArr = [
                'vender_name' => $vender->name,
                'vender_email' => $vender->email,
                'bill_name' => $bill->name,
                'bill_number' => $bill->bill,
                'bill_url' => $bill->url,
            ];
            $resp = Utility::sendEmailTemplate('bill_resent', [$vender->id => $vender->email], $billResendArr);

        }

        return redirect()->back()->with('success', __('Bill successfully sent.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
        //        }
//        else
//        {
//            return redirect()->back()->with('error', __('Permission denied.'));
//        }

    }

    public function payment($bill_id)
    {
        if (\Auth::user()->can('create payment bill')) {
            $bill = Bill::where('id', $bill_id)->first();
            $venders = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            $categories = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            return view('bill.payment', compact('venders', 'categories', 'accounts', 'bill'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));

        }
    }

    public function createPayment(Request $request, $bill_id)
    {
        \DB::beginTransaction();
        try {
            if (\Auth::user()->can('create payment bill')) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'date' => 'required',
                        'amount' => 'required',
                        'account_id' => 'required',

                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $billPayment = new BillPayment();
                $billPayment->bill_id = $bill_id;
                $billPayment->date = $request->date;
                $billPayment->amount = $request->amount;
                $billPayment->account_id = $request->account_id;
                $billPayment->payment_method = 0;
                $billPayment->reference = $request->reference;
                $billPayment->description = $request->description;

                if (!empty($request->add_receipt)) {
                    //storage limit
                    $image_size = $request->file('add_receipt')->getSize();
                    $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);
                    if ($result == 1) {
                        if ($billPayment->add_receipt) {
                            $path = storage_path('uploads/payment' . $billPayment->add_receipt);
                            if (file_exists($path)) {
                                \File::delete($path);
                            }
                        }
                        $fileName = time() . "_" . $request->add_receipt->getClientOriginalName();
                        $billPayment->add_receipt = $fileName;
                        $dir = 'uploads/payment';
                        $path = Utility::upload_file($request, 'add_receipt', $fileName, $dir, []);
                        if ($path['flag'] == 0) {
                            return redirect()->back()->with('error', __($path['msg']));
                        }

                    }

                }
                $billPayment->save();

                $bill = Bill::where('id', $bill_id)->first();
                $due = $bill->getDue();
                $total = $bill->getTotal();

                if ($bill->status == 0) {
                    $bill->send_date = date('Y-m-d');
                    $bill->save();
                }

                if ($due <= 0) {
                    $bill->status = 4;
                    $bill->save();
                } else {
                    $bill->status = 3;
                    $bill->save();
                }
                $billPayment->user_id = $bill->vender_id;
                $billPayment->user_type = 'Vender';
                $billPayment->type = 'Partial';
                $billPayment->created_by = \Auth::user()->id;
                $billPayment->payment_id = $billPayment->id;
                $billPayment->category = 'Bill';
                $billPayment->account = $request->account_id;
                Transaction::addTransaction($billPayment);

                $vender = Vender::where('id', $bill->vender_id)->first();

                $payment = new BillPayment();
                $payment->name = $vender['name'];
                $payment->method = '-';
                $payment->date = \Auth::user()->dateFormat($request->date);
                $payment->amount = \Auth::user()->priceFormat($request->amount);
                $payment->bill = 'bill ' . \Auth::user()->billNumberFormat($billPayment->bill_id);

                //            Utility::userBalance('vendor', $bill->vender_id, $request->amount, 'debit');
                Utility::updateUserBalance('vendor', $bill->vender_id, $request->amount, 'credit');


                Utility::bankAccountBalance($request->account_id, $request->amount, 'debit');

                // $billPayments = BillPayment::where('bill_id', $bill->id)->get();
                // foreach ($billPayments as $billPayment) {
                //     $accountId = BankAccount::find($billPayment->account_id);

                //     $data = [
                //         'account_id' => $accountId->chart_account_id,
                //         'transaction_type' => 'Debit',
                //         'transaction_amount' => $billPayment->amount,
                //         'reference' => 'Bill Payment',
                //         'reference_id' => $bill->id,
                //         'reference_sub_id' => $billPayment->id,
                //         'date' => $billPayment->date,
                //     ];
                //     Utility::addTransactionLines($data , 'create');
                // }
                $bankAccount = BankAccount::find($request->account_id);
                if ($bankAccount && $bankAccount->chart_account_id != 0 || $bankAccount->chart_account_id != null) {
                    $data['account_id'] = $bankAccount->chart_account_id;
                } else {
                    return redirect()->back()->with('error', __('Please select chart of account in bank account.'));
                }

                $data['id'] = $billPayment->id;
                $data['no'] = $bill->bill_id;
                $data['date'] = $billPayment->date;
                $data['reference'] = $billPayment->reference;
                $data['description'] = $billPayment->description;
                $data['amount'] = $billPayment->amount;
                $data['prod_id'] = $billPayment->id;
                // $data['result'] = $result;
                $data['category'] = 'Bill';
                $data['owned_by'] = $billPayment->owned_by;
                $data['created_by'] = \Auth::user()->creatorId();
                $data['created_at'] = date('Y-m-d', strtotime($billPayment->date)) . ' ' . date('h:i:s');


                if (preg_match('/\bcash\b/i', $bankAccount->bank_name) || preg_match('/\bcash\b/i', $bankAccount->holder_name)) {
                    $dataret = Utility::cpv_entry($data); // Cash Payment Voucher (CPV)
                } else {
                    $dataret = Utility::bpv_entry($data); // Bill Payment Voucher (BPV)
                }
                $billPayments = BillPayment::find($billPayment->id);
                $billPayments->voucher_id = $dataret;
                $billPayments->save();

                // Send Email
                $setings = Utility::settings();
                if ($setings['new_bill_payment'] == 1) {

                    $vender = Vender::where('id', $bill->vender_id)->first();
                    $billPaymentArr = [
                        'vender_name' => $vender->name,
                        'vender_email' => $vender->email,
                        'payment_name' => $payment->name,
                        'payment_amount' => $payment->amount,
                        'payment_bill' => $payment->bill,
                        'payment_date' => $payment->date,
                        'payment_method' => $payment->method,
                        'company_name' => $payment->method,

                    ];


                    $resp = Utility::sendEmailTemplate('new_bill_payment', [$vender->id => $vender->email], $billPaymentArr);
                    Utility::makeActivityLog(\Auth::user()->id, 'Bill apyment', $billPayment->id, 'Create Bill apyment', $billPayment->reference);
                    \DB::commit();
                    return redirect()->back()->with('success', __('Payment successfully added.') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : '') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));

                }
                Utility::makeActivityLog(\Auth::user()->id, 'Bill apyment', $billPayment->id, 'Create Bill apyment', $billPayment->reference);
                \DB::commit();
                return redirect()->back()->with('success', __('Payment successfully added.') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));


            }
        } catch (\Exception $e) {
            \DB::rollback();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }


    public function bulkPayment(Request $request)
    {
        \DB::beginTransaction();
        try {
            if (!\Auth::user()->can('create payment bill')) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            $validator = \Validator::make(
                $request->all(),
                [
                    'date' => 'required|date',
                    'account_id' => 'required|integer',
                    'bill_ids' => 'required|array|min:1',
                    'bill_ids.*' => 'integer|exists:bills,id',
                    'payment_amounts' => 'required|array|min:1',
                    'payment_amounts.*' => 'numeric|min:0',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $billIds = $request->bill_ids;
            $amounts = $request->payment_amounts;
            $account_id = $request->account_id;
            $date = $request->date;

            foreach ($request->bill_ids as $billId) {
                $paymentAmount = $request->payment_amounts[$billId] ?? 0;
                if ($paymentAmount <= 0)
                    continue;

                $subRequest = new Request([
                    'date' => $request->date,
                    'amount' => $paymentAmount,
                    'account_id' => $request->account_id,
                ]);

                $this->createPayment($subRequest, $billId);
            }


            \DB::commit();
            return redirect()->back()->with('success', __('Bulk payments successfully processed.'));

        } catch (\Exception $e) {
            \DB::rollback();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }



    public function paymentDestroy(Request $request, $bill_id, $payment_id)
    {

        if (\Auth::user()->can('delete payment bill')) {
            $payment = BillPayment::find($payment_id);
            BillPayment::where('id', '=', $payment_id)->delete();

            $bill = Bill::where('id', $bill_id)->first();

            $due = $bill->getDue();
            $total = $bill->getTotal();

            if ($due > 0 && $total != $due) {
                $bill->status = 3;

            } else {
                $bill->status = 2;
            }
            TransactionLines::where('reference_sub_id', $payment_id)->where('reference', 'Bill Payment')->delete();
            if (@$payment->voucher_id != 0 || @$payment->voucher_id != null) {
                JournalEntry::where('id', $payment->voucher_id)->where('category', 'Bill')->delete();
                JournalItem::where('journal', $payment->voucher_id)->delete();
            }

            //            Utility::userBalance('vendor', $bill->vender_id, $payment->amount, 'credit');
            Utility::updateUserBalance('vendor', $bill->vender_id, $payment->amount, 'debit');

            Utility::bankAccountBalance($payment->account_id, $payment->amount, 'credit');

            if (!empty($payment->add_receipt)) {
                //storage limit
                $file_path = '/uploads/payment/' . $payment->add_receipt;
                $result = Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);

            }

            $bill->save();
            $type = 'Partial';
            $user = 'Vender';
            Transaction::destroyTransaction($payment_id, $type, $user);
            Utility::makeActivityLog(\Auth::user()->id, 'Bill apyment', $payment_id, 'Delete Bill apyment', $payment->reference);
            return redirect()->back()->with('success', __('Payment successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    public function billpayments(Request $request)
    {
        // dd($request->all());
        if (\Auth::user()->can('create payment bill')) {
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

            $vender = Vender::where($column, '=', $ownerId)->get()->pluck('name', 'id');
            $vender->prepend('Select Vendor', '');

            $account = BankAccount::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('holder_name', 'id');
            $account->prepend('Select Account', '');

            $category = ProductServiceCategory::where($column, '=', $ownerId)->where('type', '=', 'expense')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');
            $query = BillPayment::with('bankAccount');
            if (!empty($request->date)) {
                $date_range = explode(' - ', $request->date);
                $query->whereBetween('date', $date_range);
            }
            if (!empty($request->vender)) {
                $query->where('vender_id', '=', $request->vender);
            }
            //category
            if (!empty($request->category)) {
                $query->where('category_id', '=', $request->category);
            }
            $billpayments = $query->get();
            return view('bill.payments', compact('billpayments','vender', 'account', 'category'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    public function venderBill(Request $request)
    {
        if (\Auth::user()->can('manage vender bill')) {

            $status = Bill::$statues;
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
            $query = Bill::where('vender_id', '=', \Auth::user()->vender_id)->where('status', '!=', '0')->where($column, $ownerId);

            if (!empty($request->vender)) {
                $query->where('id', '=', $request->vender);
            }
            if (!empty($request->bill_date)) {
                $date_range = explode(' - ', $request->bill_date);
                $query->whereBetween('bill_date', $date_range);
            }

            if (!empty($request->status)) {
                $query->where('status', '=', $request->status);
            }
            $bills = $query->get();


            return view('bill.index', compact('bills', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function venderBillShow($id)
    {
        if (\Auth::user()->can('show bill')) {
            $bill_id = Crypt::decrypt($id);
            $bill = Bill::where('id', $bill_id)->first();

            if ($bill->created_by == \Auth::user()->creatorId()) {
                $vendor = $bill->vender;
                $iteams = $bill->items;

                return view('bill.view', compact('bill', 'vendor', 'iteams'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function vender(Request $request)
    {
        $vendor = Vender::find($request->id);
        return response()->json([
            'address' => $vendor->billing_address . "\n" . $vendor->city . ", " . $vendor->zip,
        ]);
    }

    public function venderBillSend($bill_id)
    {
        return view('vender.bill_send', compact('bill_id'));
    }

    public function venderBillSendMail(Request $request, $bill_id)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'email' => 'required|email',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $email = $request->email;
        $bill = Bill::where('id', $bill_id)->first();

        $vender = Vender::where('id', $bill->vender_id)->first();
        $bill->name = !empty($vender) ? $vender->name : '';
        $bill->bill = \Auth::user()->billNumberFormat($bill->bill_id);

        $billId = Crypt::encrypt($bill->id);
        $bill->url = route('bill.pdf', $billId);

        try {
            //            Mail::to($email)->send(new VenderBillSend($bill));
        } catch (\Exception $e) {
            $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
        }
        //log
        Utility::makeActivityLog(\Auth::user()->id, 'Send Mail', $bill->id, 'send Bill mail', $bill->reference);
        return redirect()->back()->with('success', __('Bill successfully sent.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));

    }

    public function shippingDisplay(Request $request, $id)
    {
        $bill = Bill::find($id);

        if ($request->is_display == 'true') {
            $bill->shipping_display = 1;
        } else {
            $bill->shipping_display = 0;
        }
        $bill->save();
        //log
        Utility::makeActivityLog(\Auth::user()->id, 'Shipping address updated', $bill->id, 'Shipping address updated', $bill->reference);
        return redirect()->back()->with('success', __('Shipping address status successfully changed.'));
    }

    public function duplicate($bill_id)
    {
        if (\Auth::user()->can('duplicate bill')) {
            $bill = Bill::where('id', $bill_id)->first();

            $duplicateBill = new Bill();
            $duplicateBill->bill_id = $this->billNumber();
            $duplicateBill->vender_id = $bill['vender_id'];
            $duplicateBill->bill_date = date('Y-m-d');
            $duplicateBill->due_date = $bill['due_date'];
            $duplicateBill->send_date = null;
            $duplicateBill->category_id = $bill['category_id'];
            $duplicateBill->order_number = $bill['order_number'];
            $duplicateBill->status = 0;
            $duplicateBill->type = 'Bill';
            $duplicateBill->shipping_display = $bill['shipping_display'];
            $duplicateBill->created_by = $bill['created_by'];
            $duplicateBill->save();

            if ($duplicateBill) {
                $billProduct = BillProduct::where('bill_id', $bill_id)->get();
                foreach ($billProduct as $product) {
                    $duplicateProduct = new BillProduct();
                    $duplicateProduct->bill_id = $duplicateBill->id;
                    $duplicateProduct->product_id = $product->product_id;
                    $duplicateProduct->quantity = $product->quantity;
                    $duplicateProduct->tax = $product->tax;
                    $duplicateProduct->discount = $product->discount;
                    $duplicateProduct->price = $product->price;
                    $duplicateProduct->save();
                }
            }
            //log
            Utility::makeActivityLog(\Auth::user()->id, 'Duplicate Bill', $duplicateBill->id, 'Duplicate Bill', $bill->reference);
            return redirect()->back()->with('success', __('Bill duplicate successfully.'));

        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function previewBill($template, $color)
    {
        $objUser = \Auth::user();
        $settings = Utility::settings();
        $bill = new Bill();

        $vendor = new \stdClass();
        $vendor->email = '<Email>';
        $vendor->shipping_name = '<Vendor Name>';
        $vendor->shipping_country = '<Country>';
        $vendor->shipping_state = '<State>';
        $vendor->shipping_city = '<City>';
        $vendor->shipping_phone = '<Vendor Phone Number>';
        $vendor->shipping_zip = '<Zip>';
        $vendor->shipping_address = '<Address>';
        $vendor->billing_name = '<Vendor Name>';
        $vendor->billing_country = '<Country>';
        $vendor->billing_state = '<State>';
        $vendor->billing_city = '<City>';
        $vendor->billing_phone = '<Vendor Phone Number>';
        $vendor->billing_zip = '<Zip>';
        $vendor->billing_address = '<Address>';

        $totalTaxPrice = 0;
        $taxesData = [];
        $items = [];
        for ($i = 1; $i <= 3; $i++) {
            $item = new \stdClass();
            $item->name = 'Item ' . $i;
            $item->quantity = 1;
            $item->tax = 5;
            $item->discount = 50;
            $item->price = 100;
            $item->unit = 1;

            $taxes = [
                'Tax 1',
                'Tax 2',
            ];

            $itemTaxes = [];
            foreach ($taxes as $k => $tax) {
                $taxPrice = 10;
                $totalTaxPrice += $taxPrice;
                $itemTax['name'] = 'Tax ' . $k;
                $itemTax['rate'] = '10 %';
                $itemTax['price'] = '$10';
                $itemTax['tax_price'] = 10;
                $itemTaxes[] = $itemTax;
                if (array_key_exists('Tax ' . $k, $taxesData)) {
                    $taxesData['Tax ' . $k] = $taxesData['Tax 1'] + $taxPrice;
                } else {
                    $taxesData['Tax ' . $k] = $taxPrice;
                }
            }
            $item->itemTax = $itemTaxes;
            $items[] = $item;
        }

        $bill->bill_id = 1;
        $bill->issue_date = date('Y-m-d H:i:s');
        $bill->due_date = date('Y-m-d H:i:s');
        $bill->itemData = $items;

        $bill->totalTaxPrice = 60;
        $bill->totalQuantity = 3;
        $bill->totalRate = 300;
        $bill->totalDiscount = 10;
        $bill->taxesData = $taxesData;
        $bill->created_by = $objUser->creatorId();


        $bill->customField = [];
        $customFields = [];

        $preview = 1;
        $color = '#' . $color;
        $font_color = Utility::getFontColor($color);

        //        $logo         = asset(Storage::url('uploads/logo/'));
//        $bill_logo = Utility::getValByName('bill_logo');
//        $company_logo = \App\Models\Utility::GetLogo();
//        if(isset($bill_logo) && !empty($bill_logo))
//        {
//            $img          = asset(\Storage::url('bill_logo').'/'. $bill_logo);
//        }
//        else
//        {
//            $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
//        }

        $logo = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $bill_logo = Utility::getValByName('bill_logo');
        if (isset($bill_logo) && !empty($bill_logo)) {
            $img = Utility::get_file('bill_logo/') . $bill_logo;
        } else {
            $img = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }



        return view('bill.templates.' . $template, compact('bill', 'preview', 'color', 'img', 'settings', 'vendor', 'font_color', 'customFields'));
    }

    public function bill($bill_id)
    {
        $settings = Utility::settings();
        try {
            $billId = Crypt::decrypt($bill_id);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Bill Not Found.'));
        }
        $billId = Crypt::decrypt($bill_id);

        $bill = Bill::where('id', $billId)->first();
        $data = DB::table('settings');
        $data = $data->where('created_by', '=', $bill->created_by);
        $data1 = $data->get();

        foreach ($data1 as $row) {
            $settings[$row->name] = $row->value;
        }

        $vendor = $bill->vender;

        $totalTaxPrice = 0;
        $totalQuantity = 0;
        $totalRate = 0;
        $totalDiscount = 0;
        $taxesData = [];
        $items = [];

        foreach ($bill->items as $product) {
            $item = new \stdClass();
            $item->name = !empty($product->product) ? $product->product->name : '';
            $item->quantity = $product->quantity;
            $item->unit = !empty($product->product) ? $product->product->unit_id : '';
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

        $bill->itemData = $items;
        $bill->totalTaxPrice = $totalTaxPrice;
        $bill->totalQuantity = $totalQuantity;
        $bill->totalRate = $totalRate;
        $bill->totalDiscount = $totalDiscount;
        $bill->taxesData = $taxesData;
        $bill->customField = CustomField::getData($bill, 'bill');
        $customFields = [];
        if (!empty(\Auth::user())) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'bill')->get();
        }

        //        $logo         = asset(Storage::url('uploads/logo/'));
//        $company_logo = Utility::getValByName('company_logo_dark');
//        $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));


        $logo = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $settings_data = \App\Models\Utility::settingsById($bill->created_by);
        $bill_logo = $settings_data['bill_logo'];
        if (isset($bill_logo) && !empty($bill_logo)) {
            $img = Utility::get_file('bill_logo/') . $bill_logo;
        } else {
            $img = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        if ($bill) {
            $color = '#' . $settings['bill_color'];
            $font_color = Utility::getFontColor($color);

            return view('bill.templates.' . $settings['bill_template'], compact('bill', 'color', 'settings', 'vendor', 'img', 'font_color', 'customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

    }

    public function saveBillTemplateSettings(Request $request)
    {
        $post = $request->all();
        unset($post['_token']);

        if (isset($post['bill_template']) && (!isset($post['bill_color']) || empty($post['bill_color']))) {
            $post['bill_color'] = "ffffff";
        }


        //        $validator = \Validator::make(
//            $request->all(),
//            [
//                'bill_logo' => 'image|mimes:png|max:20480',
//            ]
//        );
//        if($validator->fails())
//        {
//            $messages = $validator->getMessageBag();
//            return  redirect()->back()->with('error', $messages->first());
//        }
//        $bill_logo = \Auth::user()->id . '_bill_logo.png';
//        $path = $request->file('bill_logo')->storeAs('bill_logo', $bill_logo);
//        $post['bill_logo'] = $bill_logo;

        if ($request->bill_logo) {
            $dir = 'bill_logo/';
            $bill_logo = \Auth::user()->id . '_bill_logo.png';
            $validation = [
                'mimes:' . 'png',
                'max:' . '20480',
            ];
            $path = Utility::upload_file($request, 'bill_logo', $bill_logo, $dir, $validation);
            if ($path['flag'] == 0) {
                return redirect()->back()->with('error', __($path['msg']));
            }
            $post['bill_logo'] = $bill_logo;
        }


        foreach ($post as $key => $data) {
            \DB::insert(
                'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                [
                    $data,
                    $key,
                    \Auth::user()->creatorId(),
                ]
            );
        }

        return redirect()->back()->with('success', __('Bill Setting updated successfully'));
    }

    public function items(Request $request)
    {
        $items = BillProduct::where('bill_id', $request->bill_id)->where('product_id', $request->product_id)->first();
        return json_encode($items);
    }


    public function invoiceLink($billId)
    {
        try {
            $id = Crypt::decrypt($billId);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Bill Not Found.'));
        }

        $id = Crypt::decrypt($billId);
        $bill = Bill::find($id);
        if (!empty($bill)) {
            $user_id = $bill->created_by;
            $user = User::find($user_id);
            $billPayment = BillPayment::where('bill_id', $bill->id)->get();
            $vendor = $bill->vender;
            $iteams = $bill->items;
            $bill->customField = CustomField::getData($bill, 'bill');
            $customFields = CustomField::where('module', '=', 'bill')->get();
            return view('bill.customer_bill', compact('bill', 'vendor', 'iteams', 'customFields', 'billPayment', 'user'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

    }

    public function export()
    {
        $name = 'bill_' . date('Y-m-d i:h:s');
        $data = Excel::download(new BillExport(), $name . '.xlsx');
        ob_end_clean();

        return $data;
    }

    /**
     * Create journal entry for a bill using JournalService
     * 
     * @param Bill $bill
     * @return JournalEntry
     * @throws Exception
     */
    private function createBillJournalEntry($bill)
    {
        // Build journal items from bill categories and products
        $journalItems = [];

        $vendor = Vender::find($bill->vender_id);
        // Add category-based expenses (BillAccount)
        $billAccounts = BillAccount::where('ref_id', $bill->id)->where('type', 'Bill')->get();
        foreach ($billAccounts as $billAccount) {
            $journalItems[] = [
                'account_id' => $billAccount->chart_account_id,
                'debit' => $billAccount->price,
                'credit' => 0,
                'description' => $billAccount->description ?: 'Expense',
                'type' => 'Bill',
                'sub_type' => 'bill account',
                'name' => $vendor->name,
                'vendor_id' => $bill->vender_id,
                'customer_id' => null, // If billable
                'created_user' => \Auth::user()->id,
                'created_by' => \Auth::user()->creatorId(),
                'company_id' => \Auth::user()->ownedId(),
            ];
        }

        // Add product/service items (BillProduct)
        $billProducts = BillProduct::where('bill_id', $bill->id)->get();
        foreach ($billProducts as $billProduct) {
            $product = $billProduct->product;
            
            // Determine account ID based on product type
            $accountId = null;
            if ($product) {
                $accountId = $billProduct->account_id;
            }

            if($accountId == null){
                $accountId = $product->expense_chartaccount_id;
            }
            
            $journalItems[] = [
                'account_id' => $accountId,
                'debit' => $billProduct->line_total ?: ($billProduct->quantity * $billProduct->price),
                'credit' => 0,
                'description' => $billProduct->description ?: ($product ? $product->name : 'Product'),
                'product_id' => $billProduct->product_id,
                'type' => 'Bill',
                'sub_type' => 'bill item',
                'name' => $vendor ? $vendor->name : '',
                'vendor_id' => $bill->vender_id,
                'customer_id' => null, // If billable
                'created_user' => \Auth::user()->id,
                'created_by' => \Auth::user()->creatorId(),
                'company_id' => \Auth::user()->ownedId(),
            ];
        }

        // Get Accounts Payable account ID
        $accountPayable = Utility::getAccountPayableAccount(\Auth::user()->creatorId());
        
        if (!$accountPayable) {
            throw new \Exception('Accounts Payable account not found. Please configure your chart of accounts.');
        }
        //    dd all

        // Create journal entry using JournalService
        // This will throw an exception if creation fails, causing the entire bill transaction to rollback
        $journalEntry = JournalService::createJournalEntry([
            'date' => $bill->bill_date,
            'backdate' => true, // Set created_at/updated_at to bill_date
            'reference' => \Auth::user()->billNumberFormat($bill->bill_id),
            'description' => 'Bill from ' . ($bill->vender ? $bill->vender->name : 'Vendor'),
            'journal_id' => Utility::journalNumber(),
            'voucher_type' => 'JV',
            'reference_id' => $bill->id,
            'prod_id' => null,
            'category' => 'Bill',
            'module' => 'bill',
            'source' => 'bill_creation',
            'created_user' => \Auth::user()->id,
            'created_by' => \Auth::user()->creatorId(),
            'owned_by' => \Auth::user()->ownedId(),
            'vendor_id' => $bill->vender_id,
            'company_id' => \Auth::user()->ownedId(),
            'bill_id' => $bill->id, // For transaction_lines
            'items' => $journalItems,
            'ap_name' => $bill->vender ? $bill->vender->name : '',
            'ap_account_id' => $accountPayable->id,
            'ap_amount' => $bill->getTotal(),
            'ap_sub_type' => 'bill payable',
            'ap_description' => 'Accounts Payable - ' . \Auth::user()->billNumberFormat($bill->bill_id),
        ]);

        \Log::info('Journal entry created for bill', [
            'bill_id' => $bill->id,
            'journal_entry_id' => $journalEntry->id,
        ]);
        
        return $journalEntry;
    }

    /**
     * Update journal entry for a bill using JournalService
     * 
     * @param Bill $bill
     * @param JournalEntry $journalEntry
     * @return JournalEntry
     * @throws Exception
     */
    private function updateBillJournalEntry($bill, $journalEntry)
    {
        // Build journal items from bill categories and products
        $journalItems = [];

        $vendor = Vender::find($bill->vender_id);
        
        // Add category-based expenses (BillAccount)
        $billAccounts = BillAccount::where('ref_id', $bill->id)->where('type', 'Bill')->get();
        foreach ($billAccounts as $billAccount) {
            $journalItems[] = [
                'account_id' => $billAccount->chart_account_id,
                'debit' => $billAccount->price,
                'credit' => 0,
                'description' => $billAccount->description ?: 'Expense',
                'type' => 'Bill',
                'sub_type' => 'bill account',
                'name' => $vendor->name,
                'vendor_id' => $bill->vender_id,
                'customer_id' => null,
                'created_user' => \Auth::user()->id,
                'created_by' => \Auth::user()->creatorId(),
                'company_id' => \Auth::user()->ownedId(),
            ];
        }

        // Add product/service items (BillProduct)
        $billProducts = BillProduct::where('bill_id', $bill->id)->get();
        foreach ($billProducts as $billProduct) {
            $product = $billProduct->product;
            
            // Determine account ID based on product type
            $accountId = null;
            if ($product) {
                $accountId = $billProduct->account_id;
            }

            if($accountId == null && $product){
                $accountId = $product->expense_chartaccount_id;
            }
            
            $journalItems[] = [
                'account_id' => $accountId,
                'debit' => $billProduct->line_total ?: ($billProduct->quantity * $billProduct->price),
                'credit' => 0,
                'description' => $billProduct->description ?: ($product ? $product->name : 'Product'),
                'product_id' => $billProduct->product_id,
                'type' => 'Bill',
                'sub_type' => 'bill item',
                'name' => $vendor ? $vendor->name : '',
                'vendor_id' => $bill->vender_id,
                'customer_id' => null,
                'created_user' => \Auth::user()->id,
                'created_by' => \Auth::user()->creatorId(),
                'company_id' => \Auth::user()->ownedId(),
            ];
        }

        // Get Accounts Payable account ID
        $accountPayable = Utility::getAccountPayableAccount(\Auth::user()->creatorId());
        
        if (!$accountPayable) {
            throw new \Exception('Accounts Payable account not found. Please configure your chart of accounts.');
        }

        // Update journal entry using JournalService
        // This will delete old journal items and create new ones
        $updatedJournalEntry = JournalService::updateJournalEntry($journalEntry->id, [
            'date' => $bill->bill_date,
            'backdate' => true, // Set updated_at to bill_date
            'reference' => \Auth::user()->billNumberFormat($bill->bill_id),
            'description' => 'Bill from ' . ($bill->vender ? $bill->vender->name : 'Vendor'),
            'voucher_type' => 'JV',
            'category' => 'Bill',
            'module' => 'bill',
            'source' => 'bill_update',
            'created_user' => \Auth::user()->id,
            'created_by' => \Auth::user()->creatorId(),
            'vendor_id' => $bill->vender_id,
            'company_id' => \Auth::user()->ownedId(),
            'bill_id' => $bill->id, // For transaction_lines
            'items' => $journalItems,
            'ap_name' => $bill->vender ? $bill->vender->name : '',
            'ap_account_id' => $accountPayable->id,
            'ap_amount' => $bill->getTotal(),
            'ap_sub_type' => 'bill payable',
            'ap_description' => 'Accounts Payable - ' . \Auth::user()->billNumberFormat($bill->bill_id),
        ]);

        \Log::info('Journal entry updated for bill', [
            'bill_id' => $bill->id,
            'journal_entry_id' => $updatedJournalEntry->id,
        ]);
        
        return $updatedJournalEntry;
    }


    /**
     * Get or create Accounts Payable account
     * 
     * @return ChartOfAccount|null
     */
    

}

