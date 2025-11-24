<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Utility;
use App\Models\User;
use App\Models\WorkFlow;
use App\Models\Notification;
use App\Models\WorkFlowAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use DB;

class LeaveController extends Controller
{
    public function index()
    {
        if (\Auth::user()->can('manage leave')) {
            if (\Auth::user()->type == 'company' || \Auth::user()->type == 'HR' || \Auth::user()->type == 'branch') {
                if (\Auth::user()->type == 'company') {
                    $leaves = Leave::where('created_by', '=', \Auth::user()->creatorId())->with(['leaveType', 'employees'])->get();
                } else {
                    $leaves = Leave::where('owned_by', '=', \Auth::user()->ownedId())->with(['leaveType', 'employees'])->get();
                }
            } else {
                $user = \Auth::user();
                $employee = Employee::where('user_id', '=', $user->id)->first();
                $leaves = Leave::where('employee_id', '=', $employee->id)->with(['leaveType', 'employees'])->get();
            }
            return view('leave.index', compact('leaves'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (!\Auth::user()->can('create leave')) {
            return response()->json(['error' => __('Permission denied.')], 401);
        }

        $user = \Auth::user();
        $creatorId = $user->creatorId();
        $ownedId = $user->ownedId();
        if (in_array($user->type, ['company', 'HR','branch'])) {
            $column = $user->type == 'company' ? 'created_by' : 'owned_by';
            $value = $user->type == 'company' ? $creatorId : $ownedId;
            $employees = Employee::where($column, '=', $value)->pluck('name', 'id');
        } else {
            $employees = Employee::where('user_id', '=', $user->id)->pluck('name', 'id');
        }
        $leavetypes = LeaveType::where($user->type == 'company' ? 'created_by' : 'owned_by', '=', $user->type == 'company' ? $creatorId : $ownedId)->get();

        $customFields = CustomField::where('created_by', '=', $creatorId)
            ->where('module', '=', 'leave')
            ->get();

        return view('leave.create', compact('employees', 'leavetypes', 'customFields'));
    }




    public function store(Request $request)
    {
        \DB::beginTransaction();
        try {
        if (\Auth::user()->can('create leave')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'leave_type_id' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required',
                    'leave_reason' => 'required',
                    'remark' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $employee = Employee::where('user_id', '=', Auth::user()->id)->first();
            $leave_type = LeaveType::find($request->leave_type_id);
            $startDate = new \DateTime($request->start_date);
            $endDate = new \DateTime($request->end_date);
            $endDate->add(new \DateInterval('P1D'));
            $total_leave_days = !empty($startDate->diff($endDate)) ? $startDate->diff($endDate)->days : 0;
            if ($leave_type->days >= $total_leave_days) {
                $leave = new Leave();
                $leaveuser = '';
                if (\Auth::user()->type == 'company' || \Auth::user()->type == 'HR' || $request->employee_id) {
                    $leaveuser = $request->employee_id;
                } else {
                    $leaveuser = $employee->id;
                }
                if (!$leaveuser) {
                    return redirect()->back()->with('error', 'Employee must be selected');
                }

                $leave->employee_id = @$leaveuser;
                $leave->leave_type_id = $request->leave_type_id;
                $leave->applied_on = date('Y-m-d');
                $leave->start_date = $request->start_date;
                $leave->end_date = $request->end_date;
                $leave->total_leave_days = $total_leave_days + 1;
                $leave->leave_reason = $request->leave_reason;
                $leave->remark = $request->remark;
                $leave->status = 'Pending';
                $leave->created_by = \Auth::user()->creatorId();
                $leave->owned_by = \Auth::user()->ownedId();
                $leave->save();
                // For Notification
                //send to users 
                // $leave = Leave::with('employees')->find($leave->id);
                // dd($leave);
                $userarr = [
                    \Auth::user()->id,
                    $leave->employees->report_to,
                    User::where('type','hr')->pluck('id')->first(),
                ];
                $dataarr = [
                    "updated_by" => Auth::user()->id,
                    "data_id" => $leave->id,
                    "name" => @$leave->employees->name,
                ];
                foreach($userarr as $key => $notifyto){
                    Utility::makeNotification($notifyto,'leave',$dataarr,$leave->id,'create Leave');
                }
                CustomField::saveData($leave, $request->customField);

                if (\Auth::user()->type != 'company' || \Auth::user()->type != 'HR') {
                    $setting = Utility::settings(\Auth::user()->creatorId());
                    $employee = Employee::find($leave->employee_id);
                    $user = User::find($leave->created_by);
                    if (isset($setting['new_leave']) && $setting['new_leave'] == 1) {
                        $leaveArr = [
                            'user_name' => $user->name,
                            'start_date' => $leave->start_date,
                            'end_date' => $leave->end_date,
                            'leave_reason' => $leave->leave_reason,
                            'employee_name' => $employee->name,
                        ];
                        $resp = Utility::sendEmailTemplate('new_leave', [$user->id => $user->email], $leaveArr);
                    }
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
                        if (strtolower('create-leave') == $action->node_id) {
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
                                        'leave_type' => 'leave_type_id',
                                        'days' => 'total_leave_days',
                                        'status' => 'status',
                                    ];
                                    $relate = [
                                        'leave_type_id' => 'leaveType',
                                    ];
                                    foreach ($applied_conditions['conditions'] as $conditionGroup) {
                                        if (in_array($conditionGroup['action'], ['send_email', 'send_notification', 'send_approval'])) {
                                            $query = Leave::where('id', $leave->id);
                                            foreach ($conditionGroup['conditions'] as $condition) {
                                                $field = $condition['field'];
                                                $operator = $condition['operator'];
                                                $value = $condition['value'];
                                            if (isset($arr[$field], $relate[$arr[$field]])) {
                                                $relatedField = $arr[$field];
                                                $relation = $relate[$relatedField];

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
                                            "data_id" => $leave->id,
                                            "name" => @$leave->employees->name,
                                        ];

                                        if($us_notify == 'true'){
                                            Utility::makeNotification($usrLead,'create_leave',$data,$leave->id,'create Leave');
                                        }elseif($us_approve == 'true'){
                                            Utility::makeNotification($usrLead,'approve_leave',$data,$leave->id,'For Approval Leave');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                Utility::makeActivityLog(\Auth::user()->id, 'Leave', $leave->id, 'Create Leave', $employee->name);
                \DB::commit();
                return redirect()->route('leave.index')->with('success', __('Leave successfully created.'));
            } else {
                return redirect()->back()->with('error', __('Leave type ' . $leave_type->name . ' is provide maximum ' . $leave_type->days . "  days please make sure your selected days is under " . $leave_type->days . ' days.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        } catch (\Exception $e) {
            \DB::rollback();
            return redirect()->back()->with('error', $e);
        }
    }

    public function show(Leave $leave)
    {
        return redirect()->route('leave.index');
    }

    public function edit(Leave $leave)
    {
        if (\Auth::user()->can('edit leave')) {
            if ($leave->created_by == \Auth::user()->creatorId()) {
                $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $leavetypes = LeaveType::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('title', 'id');
                $customFields = CustomField::where('module', '=', 'leave')->get();
                $leave->customField = CustomField::getData($leave, 'leave')->toArray();

                return view('leave.edit', compact('leave', 'employees', 'leavetypes', 'customFields'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, $leave)
    {

        $leave = Leave::find($leave);
        if (\Auth::user()->can('edit leave')) {
            if ($leave->created_by == Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'leave_type_id' => 'required',
                        'start_date' => 'required',
                        'end_date' => 'required',
                        'leave_reason' => 'required',
                        'remark' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $leave_type = LeaveType::find($request->leave_type_id);

                $startDate = new \DateTime($request->start_date);
                $endDate = new \DateTime($request->end_date);
                $endDate->add(new \DateInterval('P1D'));
                $total_leave_days = !empty($startDate->diff($endDate)) ? $startDate->diff($endDate)->days : 0;
                if ($leave_type->days >= $total_leave_days) {

                    $leave->employee_id = $request->employee_id;
                    $leave->leave_type_id = $request->leave_type_id;
                    $leave->start_date = $request->start_date;
                    $leave->end_date = $request->end_date;
                    $leave->total_leave_days = $total_leave_days + 1;
                    $leave->leave_reason = $request->leave_reason;
                    $leave->remark = $request->remark;
                    $leave->save();
                    $userarr = [
                        \Auth::user()->id,
                        $leave->employees->report_to,
                        User::where('type','hr')->pluck('id')->first(),
                    ];
                    $dataarr = [
                        "updated_by" => Auth::user()->id,
                        "data_id" => $leave->id,
                        "name" => @$leave->employees->name,
                    ];
                    foreach($userarr as $key => $notifyto){
                        Utility::makeNotification($notifyto,'leave',$dataarr,$leave->id,'Update Leave');
                    }
                    CustomField::saveData($leave, $request->customField);
                    $employee = Employee::find($leave->employee_id);
                    Utility::makeActivityLog(\Auth::user()->id, 'Leave', $leave->id, 'Update Leave', $employee->name);
                    return redirect()->route('leave.index')->with('success', __('Leave successfully updated.'));
                } else {
                    return redirect()->back()->with('error', __('Leave type ' . $leave_type->name . ' is provide maximum ' . $leave_type->days . "  days please make sure your selected days is under " . $leave_type->days . ' days.'));
                }
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(Leave $leave)
    {
        if (\Auth::user()->can('delete leave')) {
            if ($leave->created_by == \Auth::user()->creatorId()) {
                $employee = Employee::find($leave->employee_id);
                Utility::makeActivityLog(\Auth::user()->id, 'Leave', $leave->id, 'Delete Leave', $employee->name);
                $leave->delete();
                return redirect()->route('leave.index')->with('success', __('Leave successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function action($id)
    {
        $leave = Leave::find($id);
        $employee = Employee::find($leave->employee_id);
        $leavetype = LeaveType::find($leave->leave_type_id);

        return view('leave.action', compact('employee', 'leavetype', 'leave'));
    }

    public function changeaction(Request $request)
    {
        \DB::beginTransaction();
        try {
        $leave = Leave::find($request->leave_id);

        $leave->status = $request->status;
        if ($leave->status == 'Approval') {
            $startDate = new \DateTime($leave->start_date);
            $endDate = new \DateTime($leave->end_date);
            $total_leave_days = $startDate->diff($endDate)->days;
            $leave->total_leave_days = $total_leave_days;
            $leave->status = 'Approved';
        }
        $leave->save();
        $userarr = [
            \Auth::user()->id,
            $leave->employees->report_to,
            User::where('type','hr')->pluck('id')->first(),
        ];
        $dataarr = [
            "updated_by" => Auth::user()->id,
            "data_id" => $leave->id,
            "name" => @$leave->employees->name,
        ];
        foreach($userarr as $key => $notifyto){
            Utility::makeNotification($notifyto,'leave',$dataarr,$leave->id,'Applied action on Leave');
        }
        $employee = Employee::find($leave->employee_id);

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
                if (strtolower('leave-status-update') == $action->node_id) {
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
                                'leave_type' => 'leave_type_id',
                                'days' => 'total_leave_days',
                                'status' => 'status',
                            ];
                            $relate = [
                                'leave_type_id' => 'leaveType',
                            ];
                            foreach ($applied_conditions['conditions'] as $conditionGroup) {
                                if (in_array($conditionGroup['action'], ['send_email', 'send_notification', 'send_approval'])) {
                                    $query = Leave::where('id', $leave->id);
                                    foreach ($conditionGroup['conditions'] as $condition) {
                                        $field = $condition['field'];
                                        $operator = $condition['operator'];
                                        $value = $condition['value'];
                                    if (isset($arr[$field], $relate[$arr[$field]])) {
                                        $relatedField = $arr[$field];
                                        $relation = $relate[$relatedField];

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
                            $usr_Notification[] = $leave->employee_id;
                            $usr_Notification[] = Auth::user()->creatorId();
                            foreach ($usr_Notification as $usrLead) {
                                $data = [
                                    "updated_by" => Auth::user()->id,
                                    "data_id" => $leave->id,
                                    "name" => @$leave->employees->name,
                                ];
                                if($us_notify == 'true'){
                                    Utility::makeNotification($usrLead,'update_leave',$data,$leave->id,'update Leave');
                                }elseif($us_approve == 'true'){
                                    if($usrLead != $leave->employee_id){
                                        Utility::makeNotification($usrLead,'approve_leave_update',$data,$leave->id,'For Approval Leave_update');
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
        if (!empty($employee->id)) {
            if ($setings['leave_status'] == 1) {

                $employee = Employee::where('id', $leave->employee_id)->where('created_by', '=', \Auth::user()->creatorId())->first();
                $leave->name = !empty($employee->name) ? $employee->name : '';
                $leave->email = !empty($employee->email) ? $employee->email : '';

                $actionArr = [
                    'leave_name' => !empty($employee->name) ? $employee->name : '',
                    'leave_status' => $leave->status,
                    'leave_reason' => $leave->leave_reason,
                    'leave_start_date' => $leave->start_date,
                    'leave_end_date' => $leave->end_date,
                    'total_leave_days' => $leave->total_leave_days,
                ];
                $resp = Utility::sendEmailTemplate('leave_action_sent', [$employee->id => $employee->email], $actionArr);
                // log for leave ststus approve or rejected
                Utility::makeActivityLog(\Auth::user()->id, 'Leave', $leave->id, 'Leave Status Changed', $employee->name);
                \DB::commit();
                return redirect()->route('leave.index')->with('success', __('Leave status successfully updated.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));

            }

        }
        Utility::makeActivityLog(\Auth::user()->id, 'Leave', $leave->id, 'Leave Status Changed', $employee->name);
        \DB::commit();
        return redirect()->route('leave.index')->with('success', __('Leave status successfully updated.'));
        } catch (\Exception $e) {
            \DB::rollback();
            return redirect()->back()->with('error', $e);
        }
    }


    public function jsoncount(Request $request)
    {

        // $leave_counts = LeaveType::select(\DB::raw('COALESCE(SUM(leaves.total_leave_days),0) AS total_leave, leave_types.title, leave_types.days,leave_types.id'))
        //                          ->leftjoin('leaves', function ($join) use ($request){
        //     $join->on('leaves.leave_type_id', '=', 'leave_types.id');
        //     $join->where('leaves.employee_id', '=', $request->employee_id);
        // }
        // )->groupBy('leaves.leave_type_id')->get();

        $leave_counts = [];
        $user = \Auth::user();
        $createdBy = $user->creatorId();
        $ownedBy = $user->ownedId();
        $column = $user->type == 'company' ? 'created_by' : 'owned_by';
        $value = $user->type == 'company' ? $createdBy : $ownedBy;
        $leave_types = LeaveType::where($column, $value)->get();

        foreach ($leave_types as $type) {
            $counts = Leave::select(\DB::raw('COALESCE(SUM(leaves.total_leave_days),0) AS total_leave'))->where('leave_type_id', $type->id)->groupBy('leaves.leave_type_id')->where('employee_id', $request->employee_id)->first();

            $leave_count['total_leave'] = !empty($counts) ? $counts['total_leave'] : 0;
            $leave_count['title'] = $type->title;
            $leave_count['days'] = $type->days;
            $leave_count['id'] = $type->id;
            $leave_counts[] = $leave_count;
        }

        return $leave_counts;

    }

    public function leaveto(Request $request)
    {
        $emp = Employee::where('report_to', @Auth::user()->employee->id)->get()->pluck('id');
        if (\Auth::user()->type == 'company') {
            $leaves = Leave::where('created_by', '=', \Auth::user()->creatorId())->whereIn('employee_id', $emp)->with(['leaveType', 'employees'])->get();
        } else {
            $leaves = Leave::where('owned_by', '=', \Auth::user()->ownedId())->whereIn('employee_id', $emp)->with(['leaveType', 'employees'])->get();
        }
        return view('leave.report_index', compact('leaves'));

    }


    public function report_to_create()
    {
        if (\Auth::user()->type == 'company') {
            $employees = Employee::where('created_by', '=', \Auth::user()->creatorId());
            $leavetypes = LeaveType::where('created_by', '=', \Auth::user()->creatorId())->get();
        } else {
            $employees = Employee::where('owned_by', '=', \Auth::user()->ownedId());
            $leavetypes = LeaveType::where('owned_by', '=', \Auth::user()->ownedId())->get();
        }
        $emp = Employee::where('report_to', @Auth::user()->employee->id)->get()->pluck('id');

        if (!empty($emp) && is_array($emp)) {
            $employees = $employees->whereIn('employee_id', $emp)->get()->pluck('name', 'id');
        } else {

            $employees = $employees->get()->pluck('name', 'id');
        }

        $type = 'report';
        $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'leave')->get();
        return view('leave.create', compact('employees', 'leavetypes', 'type', 'customFields'));
    }
}
