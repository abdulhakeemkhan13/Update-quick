<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransfer;
use App\Models\Utility;
use App\Models\WorkFlow;
use App\Models\Notification;
use App\Models\WorkFlowAction;
use Illuminate\Http\Request;
use Auth;

class BankTransferController extends Controller
{

    public function index(Request $request)
    {

        if(\Auth::user()->can('manage bank transfer'))
        {
            $account = BankAccount::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('holder_name', 'id');
            $account->prepend('Select Account', '');

            $query = BankTransfer::where('created_by', '=', \Auth::user()->creatorId());

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


            if(!empty($request->f_account))
            {
                $query->where('from_account', '=', $request->f_account);
            }
            if(!empty($request->t_account))
            {
                $query->where('to_account', '=', $request->t_account);
            }
            $transfers = $query->with(['fromBankAccount','toBankAccount'])->get();

            return view('bank-transfer.index', compact('transfers', 'account'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if(\Auth::user()->can('create bank transfer'))
        {
            $bankAccount = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            return view('bank-transfer.create', compact('bankAccount'));
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
            if (\Auth::user()->can('create bank transfer')) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'from_account' => 'required|numeric',
                        'to_account' => 'required|numeric',
                        'amount' => 'required|numeric',
                        'date' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $transfer = new BankTransfer();
                $transfer->from_account = $request->from_account;
                $transfer->to_account = $request->to_account;
                $transfer->amount = $request->amount;
                $transfer->date = $request->date;
                $transfer->payment_method = 0;
                $transfer->reference = $request->reference;
                $transfer->description = $request->description;
                $transfer->created_by = \Auth::user()->creatorId();
                $transfer->save();

                Utility::bankAccountBalance($request->from_account, $request->amount, 'debit');

                Utility::bankAccountBalance($request->to_account, $request->amount, 'credit');

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
                        if (strtolower('bank-transfer') == $action->node_id) {
                            // Pick that stage user assign or change on lead
                            if (@$useraction != '') {
                                $useraction = json_decode($useraction);
                                
                                if (@$useraction != '') {
                                    foreach ($useraction as $anyaction) {
                                        // make new user array
                                        if ($anyaction->type == 'user') {
                                            $usr_Notification[] = $anyaction->id;
                                        }
                                    }
                                }
                            }
                            $raw_json = trim($action->applied_conditions, '"');
                            $cleaned_json = stripslashes($raw_json);
                            $applied_conditions = json_decode($cleaned_json, true);
                            // dd($usr_Notification);

                            if (isset($applied_conditions['conditions']) && is_array($applied_conditions['conditions'])) {
                                $arr = [
                                    'from_account' => 'from_account',
                                    'to_account' => 'to_account',
                                    'amount' => 'amount',
                                    'date' => 'date',
                                    'payment_method' => 'payment_method',
                                ];
                                $relate = [

                                ];
                                foreach ($applied_conditions['conditions'] as $conditionGroup) {
                                    if (in_array($conditionGroup['action'], ['send_email', 'send_notification', 'send_approval'])) {
                                        $query = BankTransfer::where('id', $transfer->id);
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
                                // dd($usr_Notification,'out');
                                if (count($usr_Notification) > 0) {
                                    $usr_Notification[] = Auth::user()->creatorId();
                                    foreach ($usr_Notification as $usrLead) {
                                        // dd($usr_Notification,$usrLead);
                                        $data = [
                                            "updated_by" => Auth::user()->id,
                                            "data_id" => $transfer->id,
                                            "name" => @$transfer->toBankAccount->bank_name,
                                        ];
                                        if ($us_notify == 'true') {
                                            Utility::makeNotification($usrLead, 'create_transfer', $data, $transfer->id, 'create Transfer');
                                        } elseif ($us_approve == 'true') {
                                            Utility::makeNotification($usrLead, 'approve_transfer', $data, $transfer->id, 'For Approval Transfer');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                \DB::commit();
                return redirect()->route('bank-transfer.index')->with('success', __('Amount successfully transfer.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } catch (\Exception $e) {
            \DB::rollback();
            return redirect()->back()->with('error', $e);
        }
    }

    public function show()
    {
        return redirect()->route('bank-transfer.index');
    }

    public function edit(BankTransfer $transfer,$id)
    {
        if(\Auth::user()->can('edit bank transfer'))
        {
            $transfer = BankTransfer::where('id',$id)->first();
            $bankAccount = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            return view('bank-transfer.edit', compact('bankAccount', 'transfer'));
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, BankTransfer $transfer,$id)
    {
        if(\Auth::user()->can('edit bank transfer'))
        {
            $transfer = BankTransfer::find($id);
            $validator = \Validator::make(
                $request->all(), [
                                   'from_account' => 'required|numeric',
                                   'to_account' => 'required|numeric',
                                   'amount' => 'required|numeric',
                                   'date' => 'required',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            Utility::bankAccountBalance($transfer->from_account, $transfer->amount, 'credit');
            Utility::bankAccountBalance($transfer->to_account, $transfer->amount, 'debit');

            $transfer->from_account   = $request->from_account;
            $transfer->to_account     = $request->to_account;
            $transfer->amount         = $request->amount;
            $transfer->date           = $request->date;
            $transfer->payment_method = 0;
            $transfer->reference      = $request->reference;
            $transfer->description    = $request->description;
            $transfer->save();


            Utility::bankAccountBalance($request->from_account, $request->amount, 'debit');
            Utility::bankAccountBalance($request->to_account, $request->amount, 'credit');

            return redirect()->route('bank-transfer.index')->with('success', __('Amount successfully transfer updated.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy(BankTransfer $BankTransfer)
    {

        if(\Auth::user()->can('delete bank transfer'))
        {
            if($BankTransfer->created_by == \Auth::user()->creatorId())
            {
                $BankTransfer->delete();

                Utility::bankAccountBalance($BankTransfer->from_account, $BankTransfer->amount, 'credit');
                Utility::bankAccountBalance($BankTransfer->to_account, $BankTransfer->amount, 'debit');

                return redirect()->route('bank-transfer.index')->with('success', __('Amount transfer successfully deleted.'));
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
