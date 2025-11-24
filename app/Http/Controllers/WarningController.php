<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Utility;
use App\Models\Warning;
use App\Models\WorkFlow;
use App\Models\Notification;
use App\Models\WorkFlowAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class WarningController extends Controller
{
    public function index()
    {
        if(\Auth::user()->can('manage warning'))
        {
            if(Auth::user()->type == 'Employee')
            {
                $emp      = Employee::where('user_id', '=', \Auth::user()->id)->first();
                $warnings = Warning::where('warning_by', '=', $emp->id)->with(['warningTo'])->get();
            }
            else
            {
                $user = \Auth::user();
                $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
                $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
                $warnings = Warning::where($column, '=', $ownerId)->with(['warningTo'])->get();
            }

            return view('warning.index', compact('warnings'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if(\Auth::user()->can('create warning'))
        {
            if(Auth::user()->type == 'Employee')
            {
                $user             = \Auth::user();
                $current_employee = Employee::where('user_id', $user->id)->get()->pluck('name', 'id');
                $employees        = Employee::where('user_id', '!=', $user->id)->get()->pluck('name', 'id');
            }
            else
            {
                $user = \Auth::user();
                $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
                $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
                $user             = \Auth::user();
                $current_employee = Employee::where('user_id', $user->id)->get()->pluck('name', 'id');
                $employees        = Employee::where($column, $ownerId)->get()->pluck('name', 'id');
            }

            return view('warning.create', compact('employees', 'current_employee'));
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
        if(\Auth::user()->can('create warning'))
        {
            if(\Auth::user()->type != 'employee')
            {
                $validator = \Validator::make(
                    $request->all(), [
                                       'warning_by' => 'required',
                                   ]
                );
            }

            $validator = \Validator::make(
                $request->all(), [
                                   'warning_to' => 'required',
                                   'subject' => 'required',
                                   'warning_date' => 'required',
                               ]
            );

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $warning = new Warning();
            if(\Auth::user()->type == 'Employee')
            {
                $emp                 = Employee::where('user_id', '=', \Auth::user()->id)->first();
                $warning->warning_by = $emp->id;
            }
            else
            {
                $warning->warning_by = $request->warning_by;
            }
            $warning->warning_to   = $request->warning_to;
            $warning->subject      = $request->subject;
            $warning->warning_date = $request->warning_date;
            $warning->description  = $request->description;
            $warning->created_by   = \Auth::user()->creatorId();
            $warning->owned_by   = \Auth::user()->ownedId();
            $warning->save();
            $warning = Warning::with('warningTo','warningBy')->where('id',$warning->id)->first();
            $userarr = [
                \Auth::user()->id,
                @$warning->warningTo->report_to,
                @$warning->warningTo->id,
            ];
            // dd($userarr);
            $dataarr = [
                "updated_by" => Auth::user()->id,
                "data_id" => $warning->id,
                "name" => @$warning->warningTo->name,
            ];
            foreach($userarr as $key => $notifyto){
                Utility::makeNotification($notifyto,'warning',$dataarr,$warning->id,'Warned by',\Auth::user()->name);
            }
            // // WorkFlow get which is active
            $us_mail = 'false';
            $us_notify = 'false';
            $us_approve = 'false';
            $usr_Notification = [];
            $workflow = WorkFlow::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'hrm')->where('status', 1)->first();
            if ($workflow) {
                $workflowaction = WorkFlowAction::where('workflow_id', $workflow->id)->where('status', 1)->get();
                foreach ($workflowaction as $action) {
                    $useraction = json_decode($action->assigned_users);
                    if (strtolower('create-warning') == $action->node_id) {
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
                                'subject' => 'subject',
                            ];
                            $relate = [

                            ];
                            foreach ($applied_conditions['conditions'] as $conditionGroup) {
                                if (in_array($conditionGroup['action'], ['send_email', 'send_notification', 'send_approval'])) {
                                    $query = Warning::where('id', $warning->id);
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
                                $usr_Notification[] = $warning->warning_to;
                                $usr_Notification[] = Auth::user()->creatorId();

                                foreach ($usr_Notification as $usrLead) {
                                    $data = [
                                        "updated_by" => Auth::user()->id,
                                        "data_id" => $warning->id,
                                        "name" => @$warning->warningTo->name,
                                    ];
                                    if($us_notify == 'true'){
                                        Utility::makeNotification($usrLead,'create_warning',$data,$warning->id,'create Warning');
                                    }elseif($us_approve == 'true'){
                                        if($usrLead != $warning->warning_to){
                                            Utility::makeNotification($usrLead,'approve_warning',$data,$warning->id,'For Approval Warning');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            //Send Email

            $setings = Utility::settings();
            if($setings['warning_sent'] == 1)
            {
                $employee       = Employee::find($warning->warning_to);
                $warningArr = [
                    'employee_warning_name'=>$employee->name,
                    'warning_subject' =>$warning->subject,
                    'warning_description'  =>$warning->description,
                ];

                $resp = Utility::sendEmailTemplate('warning_sent', [$employee->id => $employee->email], $warningArr);
                \DB::commit();
                Utility::makeActivityLog(\Auth::user()->id,'Warning',$warning->id,'Create Warning',$employee->name);
                return redirect()->route('warning.index')->with('success', __('Warning  successfully created.'). ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));;

            }
            \DB::commit();
            Utility::makeActivityLog(\Auth::user()->id,'Warning',$warning->id,'Create Warning',$warning->subject);
            return redirect()->route('warning.index')->with('success', __('Warning  successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        } catch (\Exception $e) {
            \DB::rollback();
            return redirect()->back()->with('error', $e);
        }
    }

    public function show(Warning $warning)
    {
        return redirect()->route('warning.index');
    }

    public function edit(Warning $warning)
    {

        if(\Auth::user()->can('edit warning'))
        {
            if(Auth::user()->type == 'Employee')
            {
                $user             = \Auth::user();
                $current_employee = Employee::where('user_id', $user->id)->get()->pluck('name', 'id');
                $employees        = Employee::where('user_id', '!=', $user->id)->get()->pluck('name', 'id');
            }
            else
            {
                $user             = \Auth::user();
                $current_employee = Employee::where('user_id', $user->id)->get()->pluck('name', 'id');
                $user = \Auth::user();
                $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
                $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
                $employees        = Employee::where($column, $ownerId)->get()->pluck('name', 'id');
            }
            if($warning->created_by == \Auth::user()->creatorId())
            {
                return view('warning.edit', compact('warning', 'employees', 'current_employee'));
            }
            else
            {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, Warning $warning)
    {
        if(\Auth::user()->can('edit warning'))
        {
            if($warning->created_by == \Auth::user()->creatorId())
            {
                if(\Auth::user()->type != 'employee')
                {
                    $validator = \Validator::make(
                        $request->all(), [
                                           'warning_by' => 'required',
                                       ]
                    );
                }

                $validator = \Validator::make(
                    $request->all(), [
                                       'warning_to' => 'required',
                                       'subject' => 'required',
                                       'warning_date' => 'required',
                                   ]
                );

                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                if(\Auth::user()->type == 'Employee')
                {
                    $emp                 = Employee::where('user_id', '=', \Auth::user()->id)->first();
                    $warning->warning_by = $emp->id;
                }
                else
                {
                    $warning->warning_by = $request->warning_by;
                }

                $warning->warning_to   = $request->warning_to;
                $warning->subject      = $request->subject;
                $warning->warning_date = $request->warning_date;
                $warning->description  = $request->description;
                $warning->save();
                // log will be here
                Utility::makeActivityLog(\Auth::user()->id,'Warning',$warning->id,'Update Warning',$warning->subject);
                return redirect()->route('warning.index')->with('success', __('Warning successfully updated.'));
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

    public function destroy(Warning $warning)
    {
        if(\Auth::user()->can('delete warning'))
        {
            if($warning->created_by == \Auth::user()->creatorId())
            {
                // log will be here
                Utility::makeActivityLog(\Auth::user()->id,'Warning',$warning->id,'Delete Warning',$warning->subject);
                $warning->delete();

                return redirect()->route('warning.index')->with('success', __('Warning successfully deleted.'));
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
