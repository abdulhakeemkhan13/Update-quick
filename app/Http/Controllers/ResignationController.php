<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Resignation;
use App\Models\User;
use App\Models\Utility;
use App\Models\WorkFlow;
use App\Models\Notification;
use App\Models\WorkFlowAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class ResignationController extends Controller
{
    public function index()
    {
        if(\Auth::user()->can('manage resignation'))
        {
            if(Auth::user()->type == 'Employee')
            {
                $emp          = Employee::where('user_id', '=', \Auth::user()->id)->first();
                $resignations = Resignation::where('created_by', '=', \Auth::user()->creatorId())->where('employee_id', '=', $emp->id)->with('employee')->get();
            }
            else
            {
                $user = \Auth::user();
                $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
                $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
                $resignations = Resignation::where($column, '=', $ownerId)->with('employee')->get();
            }

            return view('resignation.index', compact('resignations'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if(\Auth::user()->can('create resignation'))
        {
            if(Auth::user()->type == 'company' )
            {
                $employees = Employee::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            }else if(Auth::user()->type == 'branch'){
                $employees = Employee::where('owned_by', \Auth::user()->ownedId())->get()->pluck('name', 'id');
            }else{
                $employees = Employee::where('user_id', \Auth::user()->id)->get()->pluck('name', 'id');
            }

            return view('resignation.create', compact('employees'));
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
        if(\Auth::user()->can('create resignation'))
        {

            $validator = \Validator::make(
                $request->all(), [

                                   'notice_date' => 'required',
                                   'resignation_date' => 'required',
                               ]
            );

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $resignation = new Resignation();
            $user        = \Auth::user();
            if($user->type == 'Employee')
            {
                $employee                 = Employee::where('user_id', $user->id)->first();
                $resignation->employee_id = $employee->id;
            }
            else
            {
                $resignation->employee_id = $request->employee_id;
            }
            $resignation->notice_date      = $request->notice_date;
            $resignation->resignation_date = $request->resignation_date;
            $resignation->description      = $request->description;
            $resignation->created_by       = \Auth::user()->creatorId();
            $resignation->owned_by       = \Auth::user()->ownedId();

            $resignation->save();
            $userarr = [
                \Auth::user()->id,
                $resignation->employee->report_to,
                $resignation->employee->id,
            ];
            // dd($userarr);
            $dataarr = [
                "updated_by" => Auth::user()->id,
                "data_id" => $resignation->id,
                "name" => @$resignation->employee->name,
            ];
            foreach($userarr as $key => $notifyto){
                Utility::makeNotification($notifyto,'resignation',$dataarr,$resignation->id,'Resigned');
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
                    if (strtolower('create-resignation') == $action->node_id) {
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

                            ];
                            $relate = [

                            ];
                            foreach ($applied_conditions['conditions'] as $conditionGroup) {
                                if (in_array($conditionGroup['action'], ['send_email', 'send_notification', 'send_approval'])) {
                                    $query = Resignation::where('id', $resignation->id);


                                    // foreach ($conditionGroup['conditions'] as $condition) {
                                    //     $field = $condition['field'];
                                    //     $operator = $condition['operator'];
                                    //     $value = $condition['value'];
                                    //     if (isset($arr[$field], $relate[$arr[$field]])) {
                                    //         $relatedField = strpos($arr[$field], '_') !== false ? explode('_', $arr[$field], 2)[1] : $arr[$field];
                                    //         $relation = $relate[$arr[$field]];

                                    //         // Apply condition to the related model
                                    //         $query->whereHas($relation, function ($relatedQuery) use ($relatedField, $operator, $value) {
                                    //             $relatedQuery->where($relatedField, $operator, $value);
                                    //         });
                                    //     } else {
                                    //         // Apply condition directly to the contract model
                                    //         $query->where($arr[$field], $operator, $value);
                                    //     }
                                    // }
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
                                $usr_Notification[] = $resignation->employee_id;
                                $usr_Notification[] = Auth::user()->creatorId();
                                foreach ($usr_Notification as $usrLead) {
                                    $data = [
                                        "updated_by" => Auth::user()->id,
                                        "data_id" => $resignation->id,
                                        "name" => @$resignation->employee->name,
                                    ];
                                    if($us_notify == 'true'){
                                        Utility::makeNotification($usrLead,'create_resignation',$data,$resignation->id,'create Resignation');
                                    }elseif($us_approve == 'true'){
                                        if($usrLead != $resignation->employee_id){
                                            Utility::makeNotification($usrLead,'approve_resignation',$data,$resignation->id,'For Approval Resignation');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $setings = Utility::settings();
            if($setings['resignation_sent'] == 1)
            {
                $employee           = Employee::find($resignation->employee_id);
                $resignation->name  = $employee->name;
                $resignation->email = $employee->email;

                $resignationArr = [
                    'resignation_email'=>$employee->email,
                    'assign_user'=>$employee->name,
                    'resignation_date'  =>$resignation->resignation_date,
                    'notice_date'  =>$resignation->notice_date,

                ];
                $resp = Utility::sendEmailTemplate('resignation_sent', [$employee->email], $resignationArr);

                \DB::commit();
                Utility::makeActivityLog(\Auth::user()->id,'Resgination',$resignation->id,'Create Resgination',$resignation->name);
                return redirect()->route('resignation.index')->with('success', __('Resignation  successfully created.'). ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));

            }
            \DB::commit();
            Utility::makeActivityLog(\Auth::user()->id,'Resgination',$resignation->id,'Create Resgination',$resignation->name);
            return redirect()->route('resignation.index')->with('success', __('Resignation  successfully created.'));
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

    public function show(Resignation $resignation)
    {
        return redirect()->route('resignation.index');
    }

    public function edit(Resignation $resignation)
    {
        if(\Auth::user()->can('edit resignation'))
        {
            if(Auth::user()->type == 'company')
            {
                $employees = Employee::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            }else if(Auth::user()->type == 'branch'){
                $employees = Employee::where('owned_by', \Auth::user()->ownedId())->get()->pluck('name', 'id');
            }else
            {
                $employees = Employee::where('user_id', \Auth::user()->id)->get()->pluck('name', 'id');
            }
            if($resignation->created_by == \Auth::user()->creatorId())
            {

                return view('resignation.edit', compact('resignation', 'employees'));
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

    public function update(Request $request, Resignation $resignation)
    {
        if(\Auth::user()->can('edit resignation'))
        {
            if($resignation->created_by == \Auth::user()->creatorId())
            {
                $validator = \Validator::make(
                    $request->all(), [

                                       'notice_date' => 'required',
                                       'resignation_date' => 'required',
                                   ]
                );

                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                if(\Auth::user()->type != 'employee')
                {
                    $resignation->employee_id = $request->employee_id;
                }


                $resignation->notice_date      = $request->notice_date;
                $resignation->resignation_date = $request->resignation_date;
                $resignation->description      = $request->description;

                $resignation->save();
                Utility::makeActivityLog(\Auth::user()->id,'Resignation',$resignation->id,'Update Resignation',$resignation->name);
                return redirect()->route('resignation.index')->with('success', __('Resignation successfully updated.'));
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

    public function destroy(Resignation $resignation)
    {
        if(\Auth::user()->can('delete resignation'))
        {
            if($resignation->created_by == \Auth::user()->creatorId())
            {
                Utility::makeActivityLog(\Auth::user()->id,'Resignation',$resignation->id,'Delete Resignation',$resignation->name);
                $resignation->delete();

                return redirect()->route('resignation.index')->with('success', __('Resignation successfully deleted.'));
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
