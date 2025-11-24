<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Termination;
use App\Models\TerminationType;
use App\Models\Utility;
use Illuminate\Http\Request;
use App\Models\WorkFlow;
use App\Models\Notification;
use App\Models\WorkFlowAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class TerminationController extends Controller
{
    public function index()
    {
        if(\Auth::user()->can('manage termination'))
        {
            if(Auth::user()->type == 'Employee')
            {
                $emp          = Employee::where('user_id', '=', \Auth::user()->id)->first();
                $terminations = Termination::where('created_by', '=', \Auth::user()->creatorId())->where('employee_id', '=', $emp->id)->with(['terminationType','employee'])->get();
            }
            else
            {
                $user = \Auth::user();
                $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
                $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
                $terminations = Termination::where($column, '=',$ownerId)->with(['terminationType','employee'])->get();
            }

            return view('termination.index', compact('terminations'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if(\Auth::user()->can('create termination'))
        {
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
            $employees        = Employee::where($column, $ownerId)->get()->pluck('name', 'id');
            $terminationtypes = TerminationType::where($column, '=', $ownerId)->get()->pluck('name', 'id');

            return view('termination.create', compact('employees', 'terminationtypes'));
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
        if(\Auth::user()->can('create termination'))
        {

            $validator = \Validator::make(
                $request->all(), [
                                   'employee_id' => 'required',
                                   'termination_type' => 'required',
                                   'notice_date' => 'required',
                                   'termination_date' => 'required',
                               ]
            );

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $termination                   = new Termination();
            $termination->employee_id      = $request->employee_id;
            $termination->termination_type = $request->termination_type;
            $termination->notice_date      = $request->notice_date;
            $termination->termination_date = $request->termination_date;
            $termination->description      = $request->description;
            $termination->created_by       = \Auth::user()->creatorId();
            $termination->owned_by       = \Auth::user()->ownedId();
            $termination->save();
            // dd($termination->employee->report_to);
            $userarr = [
                \Auth::user()->id,
                $termination->employee->report_to,
                $termination->employee->id,
            ];
            // dd($userarr);
            $dataarr = [
                "updated_by" => Auth::user()->id,
                "data_id" => $termination->id,
                "name" => @$termination->employee->name,
            ];
            foreach($userarr as $key => $notifyto){
                Utility::makeNotification($notifyto,'termination',$dataarr,$termination->id,'Terminated by',\Auth::user()->name);
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
                    if (strtolower('create-termination') == $action->node_id) {
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
                                'termination type' => 'termination_name',
                            ];
                            $relate = [
                                'termination_name' => 'terminationType',
                            ];
                            foreach ($applied_conditions['conditions'] as $conditionGroup) {
                                if (in_array($conditionGroup['action'], ['send_email', 'send_notification', 'send_approval'])) {
                                    $query = Termination::where('id', $termination->id);
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
                                $usr_Notification[] = $termination->employee_id;
                                $usr_Notification[] = Auth::user()->creatorId();
                                foreach ($usr_Notification as $usrLead) {
                                    $data = [
                                        "updated_by" => Auth::user()->id,
                                        "data_id" => $termination->id,
                                        "name" => @$termination->employee->name,
                                    ];
                                    if($us_notify == 'true'){
                                        Utility::makeNotification($usrLead,'create_termination',$data,$termination->id,'create Termination');
                                    }elseif($us_approve == 'true'){
                                        if($usrLead != $termination->employee_id){
                                            Utility::makeNotification($usrLead,'approve_termination',$data,$termination->id,'For Approval Termination');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $setings = Utility::settings();
            if($setings['termination_sent'] == 1)
            {
                $employee           = Employee::find($termination->employee_id);
//                $termination->name  = $employee->name;
//                $termination->email = $employee->email;
                $termination->type  = TerminationType::find($termination->termination_type);

                $terminationArr = [
                    'termination_name'=>$employee->name,
                    'termination_email'=>$employee->email,
                    'notice_date'=>$termination->notice_date,
                    'termination_date'=>$termination->termination_date,
                    'termination_type'=>$request->termination_type,
                ];

                $resp = Utility::sendEmailTemplate('termination_sent', [$employee->id => $employee->email], $terminationArr);
                \DB::commit();
                Utility::makeActivityLog(\Auth::user()->id,'Termination',$termination->id,'Create Termination',$employee->name);
                return redirect()->route('termination.index')->with('success', __('Termination  successfully created.') .(($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));


            }

            \DB::commit();
            Utility::makeActivityLog(\Auth::user()->id,'Termination',$termination->id,'Create Termination',$termination->description);

            return redirect()->route('termination.index')->with('success', __('Termination  successfully created.'));
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

    public function show(Termination $termination)
    {
        return redirect()->route('termination.index');
    }

    public function edit(Termination $termination)
    {
        if(\Auth::user()->can('edit termination'))
        {
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
            $employees        = Employee::where($column, $ownerId)->get()->pluck('name', 'id');
            $terminationtypes = TerminationType::where($column, '=', $ownerId)->get()->pluck('name', 'id');
            if($termination->created_by == \Auth::user()->creatorId())
            {
                return view('termination.edit', compact('termination', 'employees', 'terminationtypes'));
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

    public function update(Request $request, Termination $termination)
    {
        if(\Auth::user()->can('edit termination'))
        {
            if($termination->created_by == \Auth::user()->creatorId())
            {
                $validator = \Validator::make(
                    $request->all(), [
                                       'employee_id' => 'required',
                                       'termination_type' => 'required',
                                       'notice_date' => 'required',
                                       'termination_date' => 'required',
                                   ]
                );

                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }


                $termination->employee_id      = $request->employee_id;
                $termination->termination_type = $request->termination_type;
                $termination->notice_date      = $request->notice_date;
                $termination->termination_date = $request->termination_date;
                $termination->description      = $request->description;
                $termination->save();
                Utility::makeActivityLog(\Auth::user()->id,'Termination',$termination->id,'Update Termination',$termination->description);
                return redirect()->route('termination.index')->with('success', __('Termination successfully updated.'));
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

    public function destroy(Termination $termination)
    {
        if(\Auth::user()->can('delete termination'))
        {
            if($termination->created_by == \Auth::user()->creatorId())
            {
                // log
                Utility::makeActivityLog(\Auth::user()->id,'Termination',$termination->id,'Delete Termination',$termination->description);
                $termination->delete();

                return redirect()->route('termination.index')->with('success', __('Termination successfully deleted.'));
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

    public function description($id)
    {
        $termination = Termination::find($id);

        return view('termination.description', compact('termination'));
    }

}
