<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\InvoicePayment;
use App\Models\ProductServiceCategory;
use App\Models\Revenue;
use App\Models\Transaction;
use App\Models\Utility;
use App\Models\TransactionLines;
use Illuminate\Http\Request;
use App\Models\WorkFlow;
use App\Models\Notification;
use App\Models\WorkFlowAction;
use Illuminate\Support\Facades\Mail;
use Auth;

class RevenueController extends Controller
{

    public function index(Request $request)
    {

        if(\Auth::user()->can('manage revenue'))
        {
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

            $customer = Customer::where($column, '=',$ownerId)->get()->pluck('name', 'id');
            $customer->prepend('Select Customer', '');

            $account = BankAccount::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('holder_name', 'id');
            $account->prepend('Select Account', '');

            $category = ProductServiceCategory::where($column, '=',$ownerId)->where('type', '=', 'income')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');


            $query = Revenue::where($column, '=',$ownerId);


            if(count(explode('to', $request->date)) > 1)
            {
                $date_range = explode(' to ', $request->date);
                $query->whereBetween('date', $date_range);
            }
            elseif(!empty($request->date))
            {
                $date_range = [$request->date , $request->date];
                $query->whereBetween('date', $date_range);
            }

            if(!empty($request->customer))
            {
                $query->where('customer_id', '=', $request->customer);
            }
            if(!empty($request->account))
            {
                $query->where('account_id', '=', $request->account);
            }
            if(!empty($request->category))
            {
                $query->where('category_id', '=', $request->category);
            }

            if(!empty($request->payment))
            {
                $query->where('payment_method', '=', $request->payment);
            }

            $revenues = $query->with(['bankAccount','customer','category'])->get();

            return view('revenue.index', compact('revenues', 'customer', 'account', 'category'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function create()
    {

        if(\Auth::user()->can('create revenue'))
        {
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
            $customers = Customer::where($column, '=',$ownerId)->get()->pluck('name', 'id');
            $customers->prepend('--', 0);
            $categories = ProductServiceCategory::where($column, '=',$ownerId)->where('type', '=', 'income')->get()->pluck('name', 'id');
            $accounts   = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            return view('revenue.create', compact('customers', 'categories', 'accounts'));
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function store(Request $request)
    {
        \DB::beginTransaction();
        try {
        if(\Auth::user()->can('create revenue'))
        {

            $validator = \Validator::make(
                $request->all(), [
                                   'date' => 'required',
                                   'amount' => 'required',
                                   'account_id' => 'required',
                                   'category_id' => 'required',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $revenue                 = new Revenue();
            $revenue->date           = $request->date;
            $revenue->amount         = $request->amount;
            $revenue->account_id     = $request->account_id;
            $revenue->customer_id    = $request->customer_id;
            $revenue->category_id    = $request->category_id;
            $revenue->payment_method = 0;
            $revenue->reference      = $request->reference;
            $revenue->description    = $request->description;
            if(!empty($request->add_receipt))
            {
                //storage limit
                $image_size = $request->file('add_receipt')->getSize();
                $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);

                if($result==1)
                {
                    $fileName = time() . "_" . $request->add_receipt->getClientOriginalName();
                    $revenue->add_receipt = $fileName;
                    $dir = 'uploads/revenue';
                    $url = '';
                    $path = Utility::upload_file($request, 'add_receipt', $fileName, $dir, []);
                    if ($path['flag'] == 0) {
                        return redirect()->back()->with('error', __($path['msg']));
                    }
                }
            }


            $revenue->created_by     = \Auth::user()->creatorId();
            $revenue->owned_by     = \Auth::user()->ownedId();
            $revenue->save();

            $category            = ProductServiceCategory::where('id', $request->category_id)->first();
            $revenue->payment_id = $revenue->id;
            $revenue->type       = 'Revenue';
            $revenue->category   = $category->name;
            $revenue->user_id    = $revenue->customer_id;
            $revenue->user_type  = 'Customer';
            $revenue->account    = $request->account_id;
            Transaction::addTransaction($revenue);

            $customer         = Customer::where('id', $request->customer_id)->first();
            $payment          = new InvoicePayment();
            $payment->name    = !empty($customer) ? $customer['name'] : '';
            $payment->date    = \Auth::user()->dateFormat($request->date);
            $payment->amount  = \Auth::user()->priceFormat($request->amount);
            $payment->invoice = '';

            if(!empty($customer))
            {
                Utility::userBalance('customer', $customer->id, $revenue->amount, 'credit');
            }

            Utility::bankAccountBalance($request->account_id, $revenue->amount, 'credit');

            $accountId = BankAccount::find($revenue->account_id);
            $data = [
                'account_id' => $accountId->chart_account_id,
                    'transaction_type' => 'Credit',
                    'transaction_amount' => $revenue->amount,
                    'reference' => 'Revenue',
                    'reference_id' => $revenue->id,
                    'reference_sub_id' => 0,
                    'date' => $revenue->date,
                ];
                Utility::addTransactionLines($data , 'create');

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
                    if (strtolower('create-revenue') == $action->node_id) {
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
                                'amount' => 'amount',
                            ];
                            $relate = [
                            ];

                            foreach ($applied_conditions['conditions'] as $conditionGroup) {
                                if (in_array($conditionGroup['action'], ['send_email', 'send_notification', 'send_approval'])) {
                                    $query = Revenue::where('id', $revenue->id);
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
                                    $result1 = $query->first();

                                    if (!empty($result1)) {
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
                                        "data_id" => $revenue->id,
                                        "name" => '.',
                                    ];
                                    if($us_notify == 'true'){
                                        Utility::makeNotification($usrLead,'create_revenue',$data,$revenue->id,'create Revenue');
                                    }elseif($us_approve == 'true'){
                                        Utility::makeNotification($usrLead,'approve_revenue',$data,$revenue->id,'For Approval Revenue');
                                    }
                                }
                            }
                        }
                    }
                }
            }


            //For Notification
            $setting  = Utility::settings(\Auth::user()->creatorId());
            $revenueNotificationArr = [
                'revenue_amount' => \Auth::user()->priceFormat($request->amount),
                'customer_name' => !empty($customer)?$customer->name:'-',
                'user_name' => \Auth::user()->name,
                'revenue_date' => $request->date,
            ];
            //Slack Notification
            if(isset($setting['revenue_notification']) && $setting['revenue_notification'] ==1)
            {
                Utility::send_slack_msg('new_revenue', $revenueNotificationArr);
            }
            //Telegram Notification
            if(isset($setting['telegram_revenue_notification']) && $setting['telegram_revenue_notification'] ==1)
            {
                Utility::send_telegram_msg('new_revenue', $revenueNotificationArr);
            }
            //Twilio Notification
            if(isset($setting['twilio_revenue_notification']) && $setting['twilio_revenue_notification'] ==1)
            {
                Utility::send_twilio_msg(!empty($customer)?$customer->contact:'-','new_revenue', $revenueNotificationArr);
            }


            //webhook
            $module ='New Revenue';
            $webhook =  Utility::webhookSetting($module);
            if($webhook)
            {
                $parameter = json_encode($revenue);
                $status = Utility::WebhookCall($webhook['url'],$parameter,$webhook['method']);
                if($status == true)
                {
                    \DB::commit();
                    return redirect()->route('revenue.index')->with('success', __('Revenue successfully created.'));
                }
                else
                {
                    \DB::commit();
                    return redirect()->back()->with('error', __('Webhook call failed.'));
                }
            }
            \DB::commit();

            Utility::makeActivityLog(\Auth::user()->id,'Reveneue',$revenue->id,'Create Reveneue',$revenue->category);
            return redirect()->route('revenue.index')->with('success', __('Revenue successfully created'). ((isset($result) && $result!=1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));

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
    public function show()
    {
        return redirect()->route('revenue.index');
    }


    public function edit(Revenue $revenue)
    {
        if(\Auth::user()->can('edit revenue'))
        {
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
            $customers = Customer::where($column, '=', $ownerId)->get()->pluck('name', 'id');
            $customers->prepend('--', 0);
            $categories = ProductServiceCategory::where($column, '=', $ownerId)->where('type', '=', 'income')->get()->pluck('name', 'id');
            $accounts   = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            return view('revenue.edit', compact('customers', 'categories', 'accounts', 'revenue'));
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function update(Request $request, Revenue $revenue)
    {

        if(\Auth::user()->can('edit revenue'))
        {

            $validator = \Validator::make(
                $request->all(), [
                                   'date' => 'required',
                                   'amount' => 'required',
                                   'account_id' => 'required',
                                   'category_id' => 'required',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $customer = Customer::where('id', $request->customer_id)->first();
            if(!empty($customer))
            {
                Utility::userBalance('customer', $revenue->customer_id, $revenue->amount, 'debit');
            }

            Utility::bankAccountBalance($revenue->account_id, $revenue->amount, 'debit');


            if(!empty($customer))
            {
                Utility::userBalance('customer', $customer->id, $request->amount, 'credit');
            }

            Utility::bankAccountBalance($request->account_id, $request->amount, 'credit');

            $revenue->date           = $request->date;
            $revenue->amount         = $request->amount;
            $revenue->account_id     = $request->account_id;
            $revenue->customer_id    = $request->customer_id;
            $revenue->category_id    = $request->category_id;
            $revenue->payment_method = 0;
            $revenue->reference      = $request->reference;
            $revenue->description    = $request->description;
            if(!empty($request->add_receipt))
            {
                //storage limit
                $file_path = '/uploads/revenue/'.$revenue->add_receipt;
                $image_size = $request->file('add_receipt')->getSize();
                $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);

                if($result==1)
                {
                    Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
                    $path = storage_path('uploads/revenue/' . $revenue->add_receipt);

                    if(file_exists($path))
                    {
                        \File::delete($path);
                    }
                    $fileName = time() . "_" . $request->add_receipt->getClientOriginalName();
                    $revenue->add_receipt = $fileName;
                    $dir        = 'uploads/revenue';
                    $url = '';
                    $path = Utility::upload_file($request,'add_receipt',$fileName,$dir,[]);
                    if($path['flag']==0){
                        return redirect()->back()->with('error', __($path['msg']));
                    }
                }
            }

            $revenue->save();

            $category            = ProductServiceCategory::where('id', $request->category_id)->first();
            $revenue->category   = $category->name;
            $revenue->payment_id = $revenue->id;
            $revenue->type       = 'Revenue';
            $revenue->account    = $request->account_id;
            Transaction::editTransaction($revenue);

            $accountId = BankAccount::find($revenue->account_id);
            $data = [
                'account_id' => $accountId->chart_account_id,
                'transaction_type' => 'Credit',
                'transaction_amount' => $revenue->amount,
                'reference' => 'Revenue',
                'reference_id' => $revenue->id,
                'reference_sub_id' => 0,
                'date' => $revenue->date,
            ];
            Utility::addTransactionLines($data , 'edit');
            //log
            Utility::makeActivityLog(\Auth::user()->id,'Revenue',$revenue->id,'Update Revenue',$revenue->category);
            return redirect()->route('revenue.index')->with('success', __('Revenue Updated Successfully'). ((isset($result) && $result!=1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));

        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy(Revenue $revenue)
    {

        if(\Auth::user()->can('delete revenue'))
        {
            if($revenue->created_by == \Auth::user()->creatorId())
            {
                if(!empty($revenue->add_receipt))
                {
                    //storage limit
                    $file_path = '/uploads/revenue/'.$revenue->add_receipt;
                    $result = Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);

                }
                TransactionLines::where('reference_id',$revenue->id)->where('reference','Revenue')->delete();
                //log
                Utility::makeActivityLog(\Auth::user()->id,'Revenue',$revenue->id,'Delete Revenue',$revenue->category);
                $revenue->delete();
                $type = 'Revenue';
                $user = 'Customer';
                Transaction::destroyTransaction($revenue->id, $type, $user);

                if($revenue->customer_id != 0)
                {
                    Utility::userBalance('customer', $revenue->customer_id, $revenue->amount, 'debit');
                }


                Utility::bankAccountBalance($revenue->account_id, $revenue->amount, 'debit');

                return redirect()->route('revenue.index')->with('success', __('Revenue successfully deleted.'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
