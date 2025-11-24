<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Utility;
use App\Models\TaskFile;
use App\Models\Bug;
use App\Models\BugStatus;
use App\Models\TaskStage;
use App\Models\ActivityLog;
use App\Models\CustomField;
use App\Models\ProjectTask;
use App\Models\TaskComment;
use App\Models\TaskChecklist;
use App\Models\Notification;
use App\Models\WorkFlow;
use App\Models\WorkFlowAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProjectTaskController extends Controller
{

    public function index($project_id)
    {
        $usr = \Auth::user();
        if (\Auth::user()->can('manage project task')) {
            if (\Auth::user()->type == 'company') {
                $project = Project::where('id', $project_id)->where('created_by', \Auth::user()->creatorId())->first();
            } else {
                $project = Project::where('id', $project_id)->where('owned_by', \Auth::user()->ownedId())->first();
            }
            if ($project != null) {

                $stages = TaskStage::orderBy('order')->where('created_by', \Auth::user()->creatorId())->get();

                foreach ($stages as $status) {
                    $stageClass[] = 'task-list-' . $status->id;
                    if (\Auth::user()->type == 'company') {
                        $task = ProjectTask::where('project_id', '=', $project_id)->where('created_by', '=', \Auth::user()->creatorId());
                    } else {
                        $task = ProjectTask::where('project_id', '=', $project_id)->where('owned_by', '=', \Auth::user()->ownedId());
                    }
                    $task->orderBy('order');
                    $status['tasks'] = $task->where('stage_id', '=', $status->id)->get();
                }

                return view('project_task.index', compact('stages', 'stageClass', 'project'));
            } else {
                return redirect()->route('projects.index')->with('error', __('Projeat not found'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function create($project_id, $stage_id)
    {
        if (\Auth::user()->can('create project task')) {
            $project = Project::find($project_id);
            $hrs = Project::projectHrs($project_id);
            $settings = Utility::settings();
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'task')->get();
            return view('project_task.create', compact('project_id', 'stage_id', 'project', 'hrs', 'settings', 'customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    // app/Http/Controllers/ProjectTaskController.php
    public function taskCreationOnDashboardCreate()
    {
        if (\Auth::user()->can('create project task')) {
            $settings     = Utility::settings();
            $customFields = CustomField::where('created_by', \Auth::user()->creatorId())
                ->where('module', 'task')
                ->get();

            // No project/stage context for this simple modal
            $project = null;
            $hrs     = null;
            $project_id = null;
            $stage_id   = null;

            return view(
                'project_task.taskCreationOnDashboard',
                compact('project_id', 'stage_id', 'project', 'hrs', 'settings', 'customFields')
            );
        }

        return redirect()->back()->with('error', __('Permission Denied.'));
    }


    public function store(Request $request, $project_id, $stage_id)
    {
        \DB::beginTransaction();
        try {
            if (\Auth::user()->can('create project task')) {
                $validator = Validator::make(
                    $request->all(),
                    [
                        'name' => 'required',
                        'estimated_hrs' => 'required',
                        'priority' => 'required',
                    ]
                );

                if ($validator->fails()) {
                    return redirect()->back()->with('error', Utility::errorFormat($validator->getMessageBag()));
                }

                $usr = Auth::user();
                $project = Project::find($project_id);
                $last_stage = $project->first()->id;
                $post = $request->all();
                $post['project_id'] = $project->id;
                $post['stage_id'] = $stage_id;
                $post['assign_to'] = $request->assign_to;
                $post['created_by'] = \Auth::user()->creatorId();
                $post['owned_by'] = \Auth::user()->ownedId();
                $post['start_date'] = date("Y-m-d H:i:s", strtotime($request->start_date));
                $post['end_date'] = date("Y-m-d H:i:s", strtotime($request->end_date));
                if ($stage_id == $last_stage) {
                    $post['marked_at'] = date('Y-m-d');
                }
                $task = ProjectTask::create($post);
                $task->owned_by = \Auth::user()->ownedId();
                $task->save();
                $assignToIds = explode(',', $post['assign_to']);
                $userarr = array_unique(array_merge(
                    [\Auth::user()->id],
                    $assignToIds
                ));
                $dataarr = [
                    "updated_by" => Auth::user()->id,
                    "data_id" => $task->id,
                    'project_id' => $task->project_id,
                    "name" => @$task->name,
                ];
                foreach ($userarr as $key => $notifyto) {
                    Utility::makeNotification($notifyto, 'task', $dataarr, $task->id, 'created Task');
                }
                CustomField::saveData($task, $request->customField);
                //Make entry in activity log
                ActivityLog::create(
                    [
                        'user_id' => $usr->id,
                        'project_id' => $project_id,
                        'task_id' => $task->id,
                        'log_type' => 'Create Task',
                        'remark' => json_encode(['title' => $task->name]),
                    ]
                );

                //For Notification
                $setting = Utility::settings(\Auth::user()->creatorId());
                $project_name = Project::find($project_id);
                $project = Project::where('id', $project_name->id)->first();

                $users = explode(',', $task->assign_to);

                if (isset($setting['new_task']) && $setting['new_task'] == 1) {
                    foreach ($users as $key => $user) {
                        $user = User::find($user);
                        $taskArr = [
                            'task_user' => @$user->name,
                            'task_name' => @$task->name,
                            'project_name' => @$project->project_name,
                            'task_start_date' => @$task->start_date,
                            'task_end_date' => @$task->end_date,
                            'hours' => @$task->estimated_hrs,
                        ];
                        $resp = Utility::sendEmailTemplate('new_task', [@$user->id => @$user->email], $taskArr);
                    }
                }

                // WorkFlow get which is active
                $us_mail = 'false';
                $us_notify = 'false';
                $us_approve = 'false';
                $usr_Notification = [];
                $workflow = WorkFlow::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'project')->where('status', 1)->first();
                if ($workflow) {
                    $workflowaction = WorkFlowAction::where('workflow_id', $workflow->id)->where('status', 1)->get();
                    foreach ($workflowaction as $action) {
                        $useraction = json_decode($action->assigned_users);
                        if (strtolower('create-task') == $action->node_id) {
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
                                    'name' => 'name',
                                    'priority' => 'priority',
                                    'hours' => 'estimated_hrs',
                                    'stage' => 'stage_id',
                                ];
                                $relate = [
                                    'stage_id' => 'stage',
                                ];

                                foreach ($applied_conditions['conditions'] as $conditionGroup) {
                                    if (in_array($conditionGroup['action'], ['send_email', 'send_notification', 'send_approval'])) {
                                        $query = ProjectTask::where('id', $project->id);
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
                                            "data_id" => $task->id,
                                            "name" => $task->name,
                                        ];
                                        if ($us_notify == 'true') {
                                            Utility::makeNotification($usrLead, 'create_task', $data, $task->id, 'create Task');
                                        } elseif ($us_approve == 'true') {
                                            Utility::makeNotification($usrLead, 'approve_task', $data, $task->id, 'For Approval Task');
                                        }
                                    }
                                } else {
                                    foreach ($users as $key => $user) {
                                        $user = User::find($user);
                                        $data = [
                                            "updated_by" => Auth::user()->id,
                                            "data_id" => $task->id,
                                            "name" => $task->name,
                                        ];
                                        if ($us_notify == 'true') {
                                            Utility::makeNotification($user->id, 'assign_task', $data, $task->id, 'Assign Task');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }


                $taskNotificationArr = [
                    'task_name' => $task->name,
                    'project_name' => $project->project_name,
                    'user_name' => \Auth::user()->name,
                ];
                //Slack Notification
                if (isset($setting['task_notification']) && $setting['task_notification'] == 1) {
                    Utility::send_slack_msg('new_task', $taskNotificationArr);
                }
                //Telegram Notification
                if (isset($setting['telegram_task_notification']) && $setting['telegram_task_notification'] == 1) {
                    Utility::send_telegram_msg('new_task', $taskNotificationArr);
                }


                //For Google Calendar
                if ($request->get('synchronize_type') == 'google_calender') {
                    $type = 'task';
                    $request1 = new ProjectTask();
                    $request1->title = $request->name;
                    $request1->start_date = $request->start_date;
                    $request1->end_date = $request->end_date;
                    Utility::addCalendarData($request1, $type);
                }

                //webhook
                $module = 'New Task';
                $webhook = Utility::webhookSetting($module);
                if ($webhook) {
                    $parameter = json_encode($task);
                    $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                    if ($status == true) {
                        return redirect()->back()->with('success', __('Task added successfully.'));
                    } else {
                        return redirect()->back()->with('error', __('Webhook call failed.'));
                    }
                }
                \DB::commit();
                return redirect()->back()->with('success', __('Task added successfully.'));
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        } catch (\Exception $e) {
            \DB::rollback();
            dd($e);
            return redirect()->back()->with('error', $e);
        }
    }

    public function taskCreationOnDashboardStore(Request $request, $project_id = null, $stage_id = null)
    {
        \DB::beginTransaction();
        try {
            if (!\Auth::user()->can('create project task')) {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }

            $validator = Validator::make($request->all(), [
                'name'          => 'required',
                'estimated_hrs' => 'required',
                'priority'      => 'required',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->with('error', Utility::errorFormat($validator->getMessageBag()));
            }

            $usr = Auth::user();

            // NEW: only find project if an id is present
            $project = $project_id ? Project::find($project_id) : null;

            // NEW: last stage logic only if we have a project
            $last_stage = null;
            if ($project) {
                // (Your original code had $project->first()->id which is incorrect on a single Model)
                // If you intended "last stage id", fetch it from relation or fallback to current $stage_id
                $last_stage = $stage_id; // keep same behavior minimally; adjust if you actually need real "last stage"
            }

            $post = $request->all();
            // NEW: keep projectless task possible
            $post['assign_to']  = $request->assign_to;
            $post['created_by'] = \Auth::user()->creatorId();
            $post['owned_by']   = \Auth::user()->ownedId();
            $post['start_date'] = $request->start_date ? date("Y-m-d H:i:s", strtotime($request->start_date)) : null;
            $post['end_date']   = $request->end_date ? date("Y-m-d H:i:s", strtotime($request->end_date)) : null;

            if ($stage_id && $last_stage && $stage_id == $last_stage) {
                $post['marked_at'] = date('Y-m-d');
            }

            $task = ProjectTask::create($post);
            $task->owned_by = \Auth::user()->ownedId();
            $task->save();

            $assignToIds = explode(',', $post['assign_to'] ?? '');
            $assignToIds = array_filter($assignToIds, fn($v) => $v !== '' && $v !== null);
            $userarr = array_unique(array_merge([\Auth::user()->id], $assignToIds));

            $dataarr = [
                "updated_by" => Auth::user()->id,
                "data_id"    => $task->id,
                "name"       => @$task->name,
            ];
            foreach ($userarr as $notifyto) {
                Utility::makeNotification($notifyto, 'task', $dataarr, $task->id, 'created Task');
            }

            CustomField::saveData($task, $request->customField);

            // Activity log: allow null project id
            ActivityLog::create([
                'user_id'    => $usr->id,
                'task_id'    => $task->id,
                'log_type'   => 'Create Task',
                'remark'     => json_encode(['title' => $task->name]),
            ]);

            $setting = Utility::settings(\Auth::user()->creatorId());

            // NEW: project_name only if project exists
            $project_name = $project ? $project->project_name : null;
            $users = explode(',', $task->assign_to ?? '');

            if (isset($setting['new_task']) && $setting['new_task'] == 1) {
                foreach ($users as $userId) {
                    if (!$userId) continue;
                    $user = User::find($userId);
                    if (!$user) continue;

                    $taskArr = [
                        'task_user'      => @$user->name,
                        'task_name'      => @$task->name,
                        'project_name'   => $project_name,   // may be null
                        'task_start_date' => @$task->start_date,
                        'task_end_date'  => @$task->end_date,
                        'hours'          => @$task->estimated_hrs,
                    ];
                    Utility::sendEmailTemplate('new_task', [@$user->id => @$user->email], $taskArr);
                }
            }

            // Workflow (kept intact; tiny fix so it doesn't blow up projectless)
            $us_mail = 'false';
            $us_notify = 'false';
            $us_approve = 'false';
            $usr_Notification = [];
            $workflow = WorkFlow::where('created_by', \Auth::user()->creatorId())
                ->where('module', 'project')->where('status', 1)->first();

            if ($workflow) {
                $workflowaction = WorkFlowAction::where('workflow_id', $workflow->id)
                    ->where('status', 1)->get();
                foreach ($workflowaction as $action) {
                    $useraction = json_decode($action->assigned_users);
                    if (strtolower('create-task') == $action->node_id) {
                        if (!empty($useraction)) {
                            $useraction = json_decode($useraction);
                            foreach ($useraction as $anyaction) {
                                if (($anyaction->type ?? null) == 'user') {
                                    $usr_Notification[] = $anyaction->id;
                                }
                            }
                        }

                        $raw_json = trim($action->applied_conditions, '"');
                        $cleaned_json = stripslashes($raw_json);
                        $applied_conditions = json_decode($cleaned_json, true);

                        if (isset($applied_conditions['conditions']) && is_array($applied_conditions['conditions'])) {
                            $arr = [
                                'name'     => 'name',
                                'priority' => 'priority',
                                'hours'    => 'estimated_hrs',
                                'stage'    => 'stage_id',
                            ];
                            $relate = [
                                'stage_id' => 'stage',
                            ];

                            // NEW: run conditions against the created task (not $project)
                            $query = ProjectTask::where('id', $task->id);

                            foreach ($applied_conditions['conditions'] as $conditionGroup) {
                                if (in_array($conditionGroup['action'], ['send_email', 'send_notification', 'send_approval'])) {
                                    foreach ($conditionGroup['conditions'] as $condition) {
                                        $field = $condition['field'];
                                        $operator = $condition['operator'];
                                        $value = $condition['value'];

                                        if (isset($arr[$field], $relate[$arr[$field]])) {
                                            $relatedField = $arr[$field];
                                            $relation = $relate[$relatedField];
                                            $query->whereHas($relation, function ($relatedQuery) use ($relatedField, $operator, $value) {
                                                $relatedQuery->where($relatedField, $operator, $value);
                                            });
                                        } else {
                                            $query->where($arr[$field], $operator, $value);
                                        }
                                    }

                                    $result = $query->first();
                                    if (!empty($result)) {
                                        if ($conditionGroup['action'] === 'send_email')       $us_mail = 'true';
                                        elseif ($conditionGroup['action'] === 'send_notification') $us_notify = 'true';
                                        elseif ($conditionGroup['action'] === 'send_approval')     $us_approve = 'true';
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
                                        "data_id"    => $task->id,
                                        "name"       => $task->name,
                                    ];
                                    if ($us_notify == 'true') {
                                        Utility::makeNotification($usrLead, 'create_task', $data, $task->id, 'create Task');
                                    } elseif ($us_approve == 'true') {
                                        Utility::makeNotification($usrLead, 'approve_task', $data, $task->id, 'For Approval Task');
                                    }
                                }
                            } else {
                                foreach ($users as $userId) {
                                    if (!$userId) continue;
                                    $user = User::find($userId);
                                    if (!$user) continue;
                                    $data = [
                                        "updated_by" => Auth::user()->id,
                                        "data_id"    => $task->id,
                                        "name"       => $task->name,
                                    ];
                                    if ($us_notify == 'true') {
                                        Utility::makeNotification($user->id, 'assign_task', $data, $task->id, 'Assign Task');
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $taskNotificationArr = [
                'task_name'   => $task->name,
                'project_name' => $project_name, // may be null
                'user_name'   => \Auth::user()->name,
            ];
            if (isset($setting['task_notification']) && $setting['task_notification'] == 1) {
                Utility::send_slack_msg('new_task', $taskNotificationArr);
            }
            if (isset($setting['telegram_task_notification']) && $setting['telegram_task_notification'] == 1) {
                Utility::send_telegram_msg('new_task', $taskNotificationArr);
            }

            if ($request->get('synchronize_type') == 'google_calender') {
                $type = 'task';
                $request1 = new ProjectTask();
                $request1->title = $request->name;
                $request1->start_date = $request->start_date;
                $request1->end_date = $request->end_date;
                Utility::addCalendarData($request1, $type);
            }

            $module = 'New Task';
            $webhook = Utility::webhookSetting($module);
            if ($webhook) {
                $parameter = json_encode($task);
                $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                if ($status == true) {
                    \DB::commit();
                    return redirect()->back()->with('success', __('Task added successfully.'));
                } else {
                    \DB::rollBack();
                    return redirect()->back()->with('error', __('Webhook call failed.'));
                }
            }

            \DB::commit();
            return redirect()->back()->with('success', __('Task added successfully.'));
        } catch (\Exception $e) {
            \DB::rollback();
            // dd($e);  // avoid dumping in production
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // For Taskboard View
    public function taskBoard($view)
    {
        if ($view == 'list') {
            return view('project_task.taskboard', compact('view'));
        } else {
            $usr = Auth::user();
            if (\Auth::user()->type == 'client') {
                $user_projects = Project::where('client_id', \Auth::user()->id)->pluck('id', 'id')->toArray();
            } elseif (\Auth::user()->type != 'client') {
                if (\Auth::user()->type == 'company') {
                    $user_projects = $usr->projects()->where('created_by', '=', \Auth::user()->creatorId())->pluck('project_id', 'project_id')->toArray();
                } else {
                    $user_projects = $usr->projects()->where('owned_by', '=', \Auth::user()->ownedId())->pluck('project_id', 'project_id')->toArray();
                }
            }

            $tasks = ProjectTask::whereIn('project_id', $user_projects);
            if (\Auth::user()->type != 'company') {
                if (\Auth::user()->type == 'client') {
                    $tasks->where('created_by', \Auth::user()->creatorId());
                } else {
                    $tasks->whereRaw("find_in_set('" . $usr->id . "',assign_to)");
                }
            } else {
                if (\Auth::user()->type == 'company') {
                    $tasks->where('created_by', \Auth::user()->creatorId());
                } else {
                    $tasks->where('owned_by', \Auth::user()->ownedId());
                }
            }

            $tasks = $tasks->get();
            return view('project_task.grid', compact('tasks', 'view'));
        }

        return redirect()->back()->with('error', __('Permission Denied.'));
    }

    public function taskCreationOnDashboardList(Request $request)
    {
        // fetch all data from project_tasks table where project_id is null or is 0
        $tasks = ProjectTask::where('project_id', '=', 0)->get();
        // dd($tasks);
        return view('project_task.taskCreationOnDashboardList', compact('tasks'));
    }


    // For Load Task using ajax
    public function taskboardView(Request $request)
    {

        $usr = Auth::user();
        if (\Auth::user()->type == 'client') {
            $user_projects = Project::where('client_id', \Auth::user()->id)->pluck('id', 'id')->toArray();
        } elseif (\Auth::user()->type != 'client') {
            $user_projects = $usr->projects()->pluck('project_id', 'project_id')->toArray();
        }
        if ($request->ajax() && $request->has('view') && $request->has('sort')) {
            $sort = explode('-', $request->sort);
            //            $task = ProjectTask::whereIn('project_id', $user_projects)->get();
            $tasks = ProjectTask::whereIn('project_id', $user_projects)->orderBy($sort[0], $sort[1]);
            if (\Auth::user()->type != 'company') {
                if (\Auth::user()->type == 'client') {
                    $tasks->where('created_by', \Auth::user()->creatorId());
                } else {
                    $tasks->whereRaw("find_in_set('" . $usr->id . "',assign_to)");
                }
            } else {
                $tasks->where('created_by', \Auth::user()->creatorId());
            }
            if (!empty($request->keyword)) {
                $tasks->where('name', 'LIKE', $request->keyword . '%');
            }
            //            dd($tasks->get()->toArray());
            if (!empty($request->status)) {
                $todaydate = date('Y-m-d');

                // For Optimization
                $status = $request->status;
                foreach ($status as $k => $v) {
                    if ($v == 'due_today' || $v == 'over_due' || $v == 'starred' || $v == 'see_my_tasks') {
                        unset($status[$k]);
                    }
                }
                // end

                if (count($status) > 0) {
                    $tasks->whereIn('priority', $status);
                }


                //                if(in_array('see_my_tasks', $request->status) && \Auth::user()->type!='company')
                //                {
                //                    $tasks->whereRaw("find_in_set('" . $usr->id . "',assign_to)");
                //                }

                if (in_array('due_today', $request->status)) {
                    $tasks->where('end_date', $todaydate);
                }

                if (in_array('over_due', $request->status)) {
                    $tasks->where('end_date', '<', $todaydate);
                }

                if (in_array('starred', $request->status)) {
                    $tasks->where('is_favourite', '=', 1);
                }
            }

            $tasks = $tasks->with(['project'])->get();
            $view = $request->view;
            $returnHTML = view('project_task.' . $request->view, compact('tasks', 'view'))->render();

            return response()->json(
                [
                    'success' => true,
                    'html' => $returnHTML,
                ]
            );
        }
    }


    // For Taskboard View
    public function allBugList($view)
    {
        $bugStatus = BugStatus::where('created_by', \Auth::user()->creatorId())->get();
        if (Auth::user()->type == 'company') {
            $bugs = Bug::where('created_by', \Auth::user()->creatorId())->with(['project', 'createdBy', 'projectBUg'])->get();
        } elseif (Auth::user()->type != 'company') {
            if (\Auth::user()->type == 'client') {
                $user_projects = Project::where('client_id', \Auth::user()->id)->pluck('id', 'id')->toArray();
                $bugs = Bug::whereIn('project_id', $user_projects)->where('created_by', \Auth::user()->creatorId())->with(['project', 'createdBy'])->get();
            } else {
                $bugs = Bug::where('owned_by', \Auth::user()->ownedId())->whereRaw("find_in_set('" . \Auth::user()->id . "',assign_to)")->with(['project', 'createdBy'])->get();
            }
        }
        if ($view == 'list') {
            return view('projects.allBugListView', compact('bugs', 'bugStatus', 'view'));
        } else {
            return view('projects.allBugGridView', compact('bugs', 'bugStatus', 'view'));
        }
        return redirect()->back()->with('error', __('Permission Denied.'));
    }

    public function show($project_id, $task_id)
    {

        if (\Auth::user()->can('view project task')) {
            $allow_progress = Project::find($project_id)->task_progress;
            $task = ProjectTask::find($task_id);

            return view('project_task.view', compact('task', 'allow_progress'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function edit($project_id, $task_id)
    {
        if (\Auth::user()->can('edit project task')) {
            $project = Project::find($project_id);
            $task = ProjectTask::find($task_id);
            $hrs = Project::projectHrs($project_id);
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'task')->get();
            $task->customField = CustomField::getData($task, 'task')->toArray();
            return view('project_task.edit', compact('project', 'task', 'hrs', 'customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function taskCreationOnDashboardEdit(Request $request)
    {
        if (!\Auth::user()->can('edit project task')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $taskId = $request->query('task_id');
        if (!$taskId) {
            return redirect()->back()->with('error', __('Missing task id.'));
        }

        $task = ProjectTask::find($taskId);
        if (!$task) {
            return redirect()->back()->with('error', __('Task not found.'));
        }

        $project = (!empty($task->project_id) && (int) $task->project_id !== 0)
            ? Project::find($task->project_id)
            : null;

        $hrs = $project ? Project::projectHrs($project->id) : null;

        $customFields = CustomField::where('created_by', \Auth::user()->creatorId())
            ->where('module', 'task')->get();
        $task->customField = CustomField::getData($task, 'task')->toArray();

        $members = \App\Models\User::where('created_by', \Auth::user()->creatorId())
            ->orderBy('name')->get();

        return view('project_task.taskEditOnDashboard', compact('project', 'task', 'hrs', 'customFields', 'members'));
    }



    public function update(Request $request, $project_id, $task_id)
    {

        if (\Auth::user()->can('edit project task')) {
            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'estimated_hrs' => 'required',
                    'priority' => 'required',
                ]
            );

            if ($validator->fails()) {
                return redirect()->back()->with('error', Utility::errorFormat($validator->getMessageBag()));
            }

            $post = $request->all();
            $task = ProjectTask::find($task_id);
            $task->update($post);
            CustomField::saveData($task, $request->customField);
            Utility::makeActivityLog(\Auth::user()->id, 'Project Task', $task->id, 'Update Project Task', $task->name);
            return redirect()->back()->with('success', __('Task Updated successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    public function taskCreationOnDashboardUpdate(Request $request)
    {
        if (!\Auth::user()->can('edit project task')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        // validate
        $validator = \Validator::make($request->all(), [
            'task_id'       => 'required|integer|exists:project_tasks,id',
            'name'          => 'required|string|max:255',
            'estimated_hrs' => 'required|numeric|min:0',
            'priority'      => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', \App\Models\Utility::errorFormat($validator->getMessageBag()));
        }

        // fetch task
        $task = \App\Models\ProjectTask::findOrFail($request->input('task_id'));

        // remove project_id and stage_id from payload (and any form helpers)
        $post = $request->except([
            '_token',
            '_method',
            'task_id',
            'project_id',
            'stage_id',
        ]);

        // update only the intended fields (safer than blind mass-assign)
        $task->update($post);

        // custom fields (optional)
        if ($request->has('customField')) {
            \App\Models\CustomField::saveData($task, $request->input('customField'));
        }

        \App\Models\Utility::makeActivityLog(\Auth::id(), 'Project Task', $task->id, 'Update Project Task', $task->name);

        return redirect()->back()->with('success', __('Task Updated successfully.'));
    }


    public function destroy($project_id, $task_id)
    {

        if (\Auth::user()->can('delete project task')) {
            ProjectTask::deleteTask([$task_id]);

            return redirect()->back()->with('success', __('Task Deleted successfully.'));

            echo json_encode(['task_id' => $task_id]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function taskCreationOnDashboardDestroy(Request $request, $task)
    {
        if (!\Auth::user()->can('delete project task')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        // Validate existence
        \Validator::make(['task_id' => $task], [
            'task_id' => 'required|integer|exists:project_tasks,id',
        ])->validate();

        \App\Models\ProjectTask::deleteTask([(int) $task]);

        if ($request->wantsJson()) {
            return response()->json(['task_id' => (int) $task, 'status' => 'deleted']);
        }

        return redirect()->back()->with('success', __('Task deleted successfully.'));
    }



    public function getStageTasks(Request $request, $stage_id)
    {

        if (\Auth::user()->can('view project task')) {
            $count = ProjectTask::where('stage_id', $stage_id)->count();
            echo json_encode($count);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function changeCom($projectID, $taskId)
    {

        if (\Auth::user()->can('view project task')) {
            $project = Project::find($projectID);
            $task = ProjectTask::find($taskId);

            if ($task->is_complete == 0) {
                $last_stage = TaskStage::orderBy('order', 'DESC')->where('created_by', \Auth::user()->creatorId())->first();
                $task->is_complete = 1;
                $task->marked_at = date('Y-m-d');
                $task->stage_id = $last_stage->id;
            } else {
                $first_stage = TaskStage::orderBy('order', 'ASC')->where('created_by', \Auth::user()->creatorId())->first();
                $task->is_complete = 0;
                $task->marked_at = NULL;
                $task->stage_id = $first_stage->id;
            }

            $task->save();

            return [
                'com' => $task->is_complete,
                'task' => $task->id,
                'stage' => $task->stage_id,
            ];
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function changeFav($projectID, $taskId)
    {
        if (\Auth::user()->can('view project task')) {
            $task = ProjectTask::find($taskId);
            if ($task->is_favourite == 0) {
                $task->is_favourite = 1;
            } else {
                $task->is_favourite = 0;
            }

            $task->save();

            return [
                'fav' => $task->is_favourite,
            ];
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function changeProg(Request $request, $projectID, $taskId)
    {
        if (\Auth::user()->can('view project task')) {
            $task = ProjectTask::find($taskId);
            $task->progress = $request->progress;
            $task->save();

            return ['task_id' => $taskId];
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function checklistStore(Request $request, $projectID, $taskID)
    {

        if (\Auth::user()->can('view project task')) {
            $request->validate(
                ['name' => 'required']
            );

            $post = [];
            $post['name'] = $request->name;
            $post['task_id'] = $taskID;
            $post['user_type'] = 'User';
            $post['created_by'] = \Auth::user()->id;
            $post['status'] = 0;

            $checkList = TaskChecklist::create($post);
            $user = $checkList->user;
            $checkList->updateUrl = route(
                'checklist.update',
                [
                    $projectID,
                    $checkList->id,
                ]
            );
            $checkList->deleteUrl = route(
                'checklist.destroy',
                [
                    $projectID,
                    $checkList->id,
                ]
            );

            return $checkList->toJson();
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function checklistUpdate($projectID, $checklistID)
    {
        \DB::beginTransaction();
        try {
            if (\Auth::user()->can('view project task')) {
                $checkList = TaskChecklist::find($checklistID);
                if ($checkList->status == 0) {
                    $checkList->status = 1;
                    $notificationMessage = 'Marked Checklist as completed';
                } else {
                    $checkList->status = 0;
                    $notificationMessage = 'Unmarked Checklist';
                }
                $checkList->save();
                $userarr = array_filter(array_unique([
                    \Auth::user()->id,
                    @$checkList->user->getEmployee(\Auth::user()->id)->report_to,
                ]));
                $dataarr = [
                    "updated_by" => \Auth::user()->id,
                    "data_id" => $checklistID,
                    "project_id" => $projectID,
                    "name" => @$checkList->user->getEmployee(\Auth::user()->id)->name,
                ];
                foreach ($userarr as $notifyto) {
                    Utility::makeNotification($notifyto, 'checklist', $dataarr, $checklistID, $notificationMessage);
                }

                // dd($dataarr);
                $proj = ProjectTask::where('id', $checkList->task_id)->first();
                // WorkFlow get which is active
                $us_mail = 'false';
                $us_notify = 'false';
                $us_approve = 'false';
                $usr_Notification = [];
                $workflow = WorkFlow::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'project')->where('status', 1)->first();
                if ($workflow) {
                    $workflowaction = WorkFlowAction::where('workflow_id', $workflow->id)->where('status', 1)->get();
                    foreach ($workflowaction as $action) {
                        $useraction = json_decode($action->assigned_users);
                        if (strtolower('create-checkbox') == $action->node_id) {
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
                        }
                        $us_mail = 'true';
                        $us_notify = 'true';
                        $us_approve = 'true';

                        $raw_json = trim($action->applied_conditions, '"');
                        $cleaned_json = stripslashes($raw_json);
                        $applied_conditions = json_decode($cleaned_json, true);

                        // if (isset($applied_conditions['conditions']) && is_array($applied_conditions['conditions'])) {
                        //     // $arr = [
                        //     //     'products' => 'App\Models\ProductService',
                        //     //     'sources' => 'App\Models\Source',
                        //     // ];
                        //     foreach ($applied_conditions['conditions'] as $conditionGroup) {
                        //         if (in_array($conditionGroup['action'], ['send_email', 'send_notification','send_approval'])) {
                        //             $query = Project::where('id',$project->id);
                        //             foreach ($conditionGroup['conditions'] as $condition) {
                        //                 $field = $condition['field'];
                        //                 $operator = $condition['operator'];
                        //                 $value = $condition['value'];
                        //                 $query->where($field, $operator, $value);
                        //             }
                        //             $result = $query->first();

                        //             if (!empty($result)) {
                        //                 if ($conditionGroup['action'] === 'send_email') {
                        //                     $us_mail = 'true';
                        //                 } elseif ($conditionGroup['action'] === 'send_notification') {
                        //                     $us_notify = 'true';
                        //                 }
                        //                 elseif ($conditionGroup['action'] === 'send_approval') {
                        //                     $us_approve = 'true';
                        //                 }
                        //             }
                        //         }

                        //     }
                        // }
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
                                        "data_id" => $proj->id,
                                        "name" => $proj->name,
                                    ];
                                    if ($us_notify == 'true') {
                                        Utility::makeNotification($usrLead, 'checklist_mark', $data, $proj->id, 'Checklist Mark');
                                    } elseif ($us_approve == 'true') {
                                        Utility::makeNotification($usrLead, 'approve_checklist', $data, $proj->id, 'For Approval Checklist');
                                    }
                                }
                            }
                        }
                    }
                }

                \DB::commit();
                return $checkList->toJson();
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        } catch (\Exception $e) {
            \DB::rollback();
            return redirect()->back()->with('error', $e);
        }
    }

    public function checklistDestroy($projectID, $checklistID)
    {
        if (\Auth::user()->can('view project task')) {
            $checkList = TaskChecklist::find($checklistID);
            $checkList->delete();

            return "true";
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function commentStoreFile(Request $request, $projectID, $taskID)
    {

        if (\Auth::user()->can('view project task')) {
            $request->validate(
                ['file' => 'required']
            );
            if ($request->hasFile('file')) {
                $filenameWithExt = $request->file('file')->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension = $request->file('file')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                $settings = Utility::getStorageSetting();
                if ($settings['storage_setting'] == 'local') {
                    $dir = 'uploads/tasks/';
                } else {
                    $dir = 'uploads/tasks';
                }


                $url = '';
                $path = Utility::upload_file($request, 'file', $fileNameToStore, $dir, []);
                if ($path['flag'] == 1) {
                    $url = $path['url'];
                } else {
                    return redirect()->route('file', \Auth::user()->id)->with('error', __($path['msg']));
                }
            }

            $post['task_id'] = $taskID;
            $post['file'] = $request->hasFile('file') ? $fileNameToStore : '';
            $post['name'] = $request->file->getClientOriginalName();
            $post['extension'] = $request->file->getClientOriginalExtension();
            $post['file_size'] = round(($request->file->getSize() / 1024) / 1024, 2) . ' MB';
            $post['created_by'] = \Auth::user()->id;
            $post['user_type'] = 'User';
            $TaskFile = TaskFile::create($post);
            $user = $TaskFile->user;
            $TaskFile->deleteUrl = '';
            $TaskFile->deleteUrl = route(
                'comment.destroy.file',
                [
                    $projectID,
                    $taskID,
                    $TaskFile->id,
                ]
            );

            return $TaskFile->toJson();
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function commentDestroyFile(Request $request, $projectID, $taskID, $fileID)
    {
        if (\Auth::user()->can('view project task')) {
            $commentFile = TaskFile::find($fileID);
            $path = storage_path('tasks/' . $commentFile->file);
            if (file_exists($path)) {
                \File::delete($path);
            }
            $commentFile->delete();

            return "true";
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function commentDestroy(Request $request, $projectID, $taskID, $commentID)
    {

        if (\Auth::user()->can('view project task')) {
            $comment = TaskComment::find($commentID);
            $comment->delete();

            return "true";
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function commentStore(Request $request, $projectID, $taskID)
    {

        if (\Auth::user()->can('view project task')) {
            $post = [];
            $post['task_id'] = $taskID;
            $post['user_id'] = \Auth::user()->id;
            $post['comment'] = $request->comment;
            $post['created_by'] = \Auth::user()->creatorId();
            $post['user_type'] = \Auth::user()->type;

            $comment = TaskComment::create($post);
            $userarr = array_filter(array_unique([
                \Auth::user()->id,
                @$comment->user->getEmployee(\Auth::user()->id)->report_to,
            ]));
            $dataarr = [
                "updated_by" => \Auth::user()->id,
                "data_id" => $comment->id,
                "project_id" => $projectID,
                "name" => @$comment->user->getEmployee(\Auth::user()->id)->name,
            ];
            foreach ($userarr as $notifyto) {
                Utility::makeNotification($notifyto, 'comment', $dataarr, $comment->id, 'Commented on task');
            }
            $user = $comment->user;
            $user_detail = $comment->userdetail;

            $comment->deleteUrl = route(
                'comment.destroy',
                [
                    $projectID,
                    $taskID,
                    $comment->id,
                ]
            );

            //For Notification
            $setting = Utility::settings(\Auth::user()->creatorId());
            $commentOfTask = ProjectTask::find($taskID);
            $project = Project::find($projectID);
            $CommentNotificationArr = [
                'task_name' => $commentOfTask->name,
                'project_name' => $project->project_name,
                'user_name' => \Auth::user()->name,
            ];
            //Slack Notification
            if (isset($setting['taskcomment_notification']) && $setting['taskcomment_notification'] == 1) {
                Utility::send_slack_msg('new_task_comment', $CommentNotificationArr);
            }

            //Telegram Notification
            if (isset($setting['telegram_taskcomment_notification']) && $setting['telegram_taskcomment_notification'] == 1) {
                Utility::send_telegram_msg('new_task_comment', $CommentNotificationArr);
            }


            $comment->current_time = $comment->created_at->diffForHumans();
            $comment->default_img = asset(\Storage::url("uploads/avatar/avatar.png"));

            //webhook
            $module = 'New Task Comment';
            $webhook = Utility::webhookSetting($module);
            if ($webhook) {
                $parameter = json_encode($comment);
                $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);

                if ($status == true) {
                    return redirect()->back()->with('success', __('Comment added successfully.'));
                } else {
                    return redirect()->back()->with('error', __('Webhook call failed.'));
                }
            }
            return $comment->toJson();
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function updateTaskPriorityColor(Request $request)
    {
        if (\Auth::user()->can('view project task')) {
            $task_id = $request->input('task_id');
            $color = $request->input('color');

            $task = ProjectTask::find($task_id);

            if ($task && $color) {
                $task->priority_color = $color;
                $task->save();
            }
            echo json_encode(true);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function taskOrderUpdate(Request $request, $project_id)
    {
        \DB::beginTransaction();
        try {
            if (\Auth::user()->can('view project task')) {

                $user = \Auth::user();
                $project = Project::find($project_id);
                // Save data as per order

                if (isset($request->sort)) {
                    foreach ($request->sort as $index => $taskID) {
                        if (!empty($taskID)) {
                            echo $index . "-" . $taskID;
                            $task = ProjectTask::find($taskID);

                            $task->order = $index;
                            $task->save();
                        }
                    }
                }

                // Update Task Stage
                if ($request->new_stage != $request->old_stage) {

                    $new_stage = TaskStage::find($request->new_stage);
                    $old_stage = TaskStage::find($request->old_stage);
                    $last_stage = TaskStage::where('created_by', \Auth::user()->creatorId())->orderBy('order', 'DESC')->first();
                    $last_stage = $last_stage->id;

                    $task = ProjectTask::find($request->id);

                    $task->stage_id = $request->new_stage;

                    if ($request->new_stage == $last_stage) {
                        $task->is_complete = 1;
                        $task->marked_at = date('Y-m-d');
                    } else {
                        $task->is_complete = 0;
                        $task->marked_at = NULL;
                    }
                    $task->save();

                    //For Notification
                    $setting = Utility::settings(\Auth::user()->creatorId());
                    $old_stage = TaskStage::find($request->old_stage);
                    $new_stage = TaskStage::find($request->new_stage);
                    $task = ProjectTask::find($request->id);
                    $users = explode(',', $task->assign_to);

                    if (isset($setting['task_status_updated']) && $setting['task_status_updated'] == 1) {
                        foreach ($users as $key => $user) {
                            $user = User::find($user);
                            $projectArr = [
                                'task_user' => $user->name,
                                'task_name' => $task->name,
                                'old_stage_name' => $old_stage->name,
                                'new_stage_name' => $new_stage->name,
                            ];
                            $resp = Utility::sendEmailTemplate('task_status_updated', [$user->id => $user->email], $projectArr);
                        }
                    }

                    $projectTaskNotificationArr = [
                        'task_name' => $task->name,
                        'old_stage_name' => $old_stage->name,
                        'new_stage_name' => $new_stage->name,
                    ];
                    //Slack Notification
                    if (isset($setting['taskmove_notification']) && $setting['taskmove_notification'] == 1) {
                        Utility::send_slack_msg('task_stage_updated', $projectTaskNotificationArr);
                    }
                    //Telegram Notification
                    if (isset($setting['telegram_taskmove_notification']) && $setting['telegram_taskmove_notification'] == 1) {
                        Utility::send_telegram_msg('task_stage_updated', $projectTaskNotificationArr);
                    }

                    //webhook
                    $module = 'Task Stage Updated';
                    $webhook = Utility::webhookSetting($module);
                    if ($webhook) {
                        $parameter = json_encode($task);
                        $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                        if ($status == true) {
                            return redirect()->back()->with('success', __('Task successfully updated!'));
                        } else {
                            return redirect()->back()->with('error', __('Webhook call failed.'));
                        }
                    }

                    // WorkFlow get which is active
                    $us_mail = 'false';
                    $us_notify = 'false';
                    $us_approve = 'false';
                    $usrTasks = [];
                    $workflow = WorkFlow::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'project')->where('status', 1)->first();
                    if ($workflow) {
                        $workflowaction = WorkFlowAction::where('workflow_id', $workflow->id)->where('status', 1)->get();
                        foreach ($workflowaction as $action) {
                            $useraction = json_decode($action->assigned_users);
                            // dd(strtolower($newStage->name . '-1'),$action->node_id);
                            if (strtolower($new_stage->name) == $action->node_id) {
                                // Pick that stage user assign or change on lead
                                if (@$useraction != '') {
                                    $useraction = json_decode($useraction);
                                    foreach ($useraction as $anyaction) {
                                        // make new user array
                                        if ($anyaction->type == 'user') {
                                            $usrTasks[] = $anyaction->id;
                                        }
                                    }
                                }

                                //  if user assign on this stage then check for mail and notification conditions

                                $raw_json = trim($action->applied_conditions, '"');
                                $cleaned_json = stripslashes($raw_json);
                                $applied_conditions = json_decode($cleaned_json, true);

                                if (isset($applied_conditions['conditions']) && is_array($applied_conditions['conditions'])) {
                                    $arr = [
                                        'name' => 'name',
                                        'priority' => 'priority',
                                        'hours' => 'estimated_hrs',
                                        'stage' => 'stage',
                                    ];
                                    $relate = [
                                        'stage' => 'stage',
                                    ];
                                    foreach ($applied_conditions['conditions'] as $conditionGroup) {
                                        if (in_array($conditionGroup['action'], ['send_email', 'send_notification', 'send_approval'])) {
                                            $query = ProjectTask::where('id', $task->id);
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
                                                    $query->where($field, $operator, $value);
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
                            }
                            if ($us_mail == 'true') {
                                // email send
                            }
                            if ($us_notify == 'true' || $us_approve == 'true') {
                                // notification generate
                                if (count($usrTasks) > 0) {
                                    $usrTasks[] = Auth::user()->creatorId();
                                    foreach ($usrTasks as $usrDeal1) {
                                        $data = [
                                            "updated_by" => Auth::user()->id,
                                            "data_id" => $task->id,
                                            "name" => $task->name,
                                        ];
                                        if ($us_notify == 'true') {
                                            Utility::makeNotification($usrDeal1, 'task_stage_change', $data, $task->id, 'Task Stage Change');
                                        } elseif ($us_approve == 'true') {
                                            Utility::makeNotification($usrDeal1, 'approve_task_stage_change', $data, $task->id, 'For Approval Task Stage Change');
                                        }
                                    }
                                } else {
                                    foreach ($users as $key => $user) {
                                        $user = User::find($user);

                                        $data = [
                                            "updated_by" => Auth::user()->id,
                                            "data_id" => $task->id,
                                            "name" => $task->name,
                                        ];
                                        if ($us_notify == 'true') {
                                            Utility::makeNotification($user->id, 'task_stage_change', $data, $task->id, 'create Transfer');
                                        }
                                    }
                                }
                                $output = json_decode($action->outputs);

                                $triger_mail = 'false';
                                $triger_notify = 'false';
                                $send_approval = 'false';
                                // For pick its Child nodes or connected nodes
                                if (isset($output->output_1->connections)) {
                                    foreach ($output->output_1->connections as $out) {
                                        //  if any of its child have trigger
                                        $rows = WorkFlowAction::where('workflow_id', $workflow->id)->where('status', 1)->where('node_actual_id', 'node-' . $out->node)->where('type', 'trigger')->get();
                                        if ($rows) {
                                            foreach ($rows as $row) {
                                                $rowdata = json_decode($row->assigned_users);
                                                if (@$rowdata != '') {
                                                    $rowdata = json_decode($rowdata);
                                                    foreach ($rowdata as $us_action) {
                                                        if ($us_action->type == 'user') {
                                                            $rowusrLeads[] = $us_action->id;
                                                        }
                                                    }
                                                }

                                                $triger_mail = 'true';
                                                $triger_notify = 'true';
                                                $send_approval = 'true';
                                                //  if user assign on this stage then check for mail and notification conditions

                                                // $raw_json = trim($row->applied_conditions, '"');
                                                // $cleaned_json = stripslashes($raw_json);
                                                // $applied_conditions = json_decode($cleaned_json, true);

                                                // if (isset($applied_conditions['conditions']) && is_array($applied_conditions['conditions'])) {
                                                //     $arr = [
                                                //         'products' => 'App\Models\ProductService',
                                                //         'sources' => 'App\Models\Source',
                                                //     ];
                                                //     foreach ($applied_conditions['conditions'] as $conditionGroup) {
                                                //         if (in_array($conditionGroup['action'], ['send_email', 'send_notification','send_approval'])) {
                                                //             $query = Lead::where('id',$post['lead_id']);
                                                //             foreach ($conditionGroup['conditions'] as $condition) {
                                                //                 $field = $condition['field'];
                                                //                 $operator = $condition['operator'];
                                                //                 $value = $condition['value'];
                                                //                 if (array_key_exists($field, $arr)) {
                                                //                     $a =$arr[$field]::where('name',$value)->pluck('id')->toArray();
                                                //                     if(isset($a) && count($a) > 0){
                                                //                         $query->where($field,$operator,$a);
                                                //                     }
                                                //                 }else{
                                                //                     $query->where($field, $operator, $value);
                                                //                 }
                                                //             }
                                                //             $result = $query->first();
                                                //             if (!empty($result)) {
                                                //                 if ($conditionGroup['action'] === 'send_email') {
                                                //                     $us_mail = 'true';
                                                //                 } elseif ($conditionGroup['action'] === 'send_notification') {
                                                //                     $us_notify = 'true';
                                                //                 }
                                                //                 elseif ($conditionGroup['action'] === 'send_approval') {
                                                //                     $us_approve = 'true';
                                                //                 }
                                                //             }
                                                //         }

                                                //     }
                                                // }

                                                if ($triger_mail == 'true') {
                                                    // email send
                                                }

                                                if ($triger_notify == 'true' || $send_approval = 'true') {
                                                    if ($row->node_id == 'create-task') {
                                                        $type = 'create_task';
                                                    } else if ($row->node_id == 'create-checklist') {
                                                        $type = 'create_checklist';
                                                    } else {
                                                        $type = 'other';
                                                    }

                                                    // notification generate
                                                    if (count($rowusrLeads) > 0) {
                                                        $rowusrLeads[] = Auth::user()->creatorId();

                                                        foreach ($rowusrLeads as $usrLead) {
                                                            $data = [
                                                                "updated_by" => Auth::user()->id,
                                                                "data_id" => $task->id,
                                                                "name" => $task->name,
                                                            ];
                                                            if ($triger_notify == 'true') {
                                                                Utility::makeNotification($usrLead, $type, $data, $task->id, $type);
                                                            } elseif ($send_approval == 'true') {
                                                                Utility::makeNotification($usrLead, $type, $data, $task->id, $type);
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    break;
                                }
                            }
                        }
                    }

                    // Make Entry in activity log
                    ActivityLog::create(
                        [
                            'user_id' => $user->id,
                            'project_id' => $project_id,
                            'task_id' => $request->id,
                            'log_type' => 'Move Task',
                            'remark' => json_encode(
                                [
                                    'title' => $task->name,
                                    'old_stage' => $old_stage->name,
                                    'new_stage' => $new_stage->name,
                                ]
                            ),
                        ]

                    );
                    \DB::commit();
                    return $task->toJson();
                }
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        } catch (\Exception $e) {
            \DB::rollback();
            return redirect()->back()->with('error', $e);
        }
    }

    public function taskGet($task_id)
    {
        if (\Auth::user()->can('view project task')) {
            $task = ProjectTask::find($task_id);
            //            dd($task->taskProgress()['color']);

            $html = '';
            $html .= '<div class="card-body"><div class="row align-items-center mb-2">';
            $html .= '<div class="col-6">';
            $html .= '<span class="badge badge-pill badge-xs badge-' . ProjectTask::$priority_color[$task->priority] . '">' . ProjectTask::$priority[$task->priority] . '</span>';
            $html .= '</div>';
            $html .= '<div class="col-6 text-end">';
            //            if(str_replace('%', '', $task->taskProgress()['percentage']) > 0)
            //            {
            //                $html .= '<span class="text-sm">' . $task->taskProgress()['percentage'] . '</span> <div class="progress">
            //                                                    <div class="progress-bar bg-{{ $task->taskProgress()['color'] }}" role="progressbar"
            //                                                         style="width: {{ $task->taskProgress()['percentage'] }};"></div>
            //                                                </div>';
            //            }
            if (\Auth::user()->can('view project task') || \Auth::user()->can('edit project task') || \Auth::user()->can('delete project task')) {
                $html .= '<div class="dropdown action-item">
                                                            <a href="#" class="action-item" data-toggle="dropdown"><i class="ti ti-ellipsis-h"></i></a>
                                                            <div class="dropdown-menu dropdown-menu-right">';
                if (\Auth::user()->can('view project task')) {
                    $html .= '<a href="#" data-url="' . route(
                        'projects.tasks.show',
                        [
                            $task->project_id,
                            $task->id,
                        ]
                    ) . '" data-ajax-popup="true" class="dropdown-item">' . __('View') . '</a>';
                }
                if (\Auth::user()->can('edit project task')) {
                    $html .= '<a href="#" data-url="' . route(
                        "projects.tasks.edit",
                        [
                            $task->project_id,
                            $task->id,
                        ]
                    ) . '" data-ajax-popup="true" data-size="lg" data-title="' . __("Edit ") . $task->name . '" class="dropdown-item">' . __('Edit') . '</a>';
                }
                if (\Auth::user()->can('delete project task')) {
                    $html .= '<a href="#" class="dropdown-item del_task" data-url="' . route(
                        'projects.tasks.destroy',
                        [
                            $task->project_id,
                            $task->id,
                        ]
                    ) . '">' . __('Delete') . '</a>';
                }
                $html .= '                                 </div>
                                                        </div>
                                                    </div>';
                $html .= '</div>';
            }
            $html .= '<a class="h6" href="#" data-url="' . route(
                "projects.tasks.show",
                [
                    $task->project_id,
                    $task->id,
                ]
            ) . '" data-ajax-popup="true">' . $task->name . '</a>';
            $html .= '<div class="row align-items-center">';
            $html .= '<div class="col-12">';
            $html .= '<div class="actions d-inline-block">';
            if (count($task->taskFiles) > 0) {
                $html .= '<div class="action-item mr-2"><i class="ti ti-file text-primary mr-2"></i>' . count($task->taskFiles) . '</div>';
            }
            if (count($task->comments) > 0) {
                $html .= '<div class="action-item mr-2"><i class="ti ti-message text-primary mr-2"></i>' . count($task->comments) . '</div>';
            }
            if ($task->checklist->count() > 0) {
                $html .= '<div class="action-item mr-2"><i class="ti ti-list text-primary mr-2"></i>' . $task->countTaskChecklist() . '</div>';
            }
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="col-5">';
            if (!empty($task->end_date) && $task->end_date != '0000-00-00') {
                $clr = (strtotime($task->end_date) < time()) ? 'text-danger' : '';
                $html .= '<small class="' . $clr . '">' . date("d M Y", strtotime($task->end_date)) . '</small>';
            }
            $html .= '</div>';
            $html .= '<div class="col-7 text-end">';

            if ($users = $task->users()) {
                $html .= '<div class="avatar-group">';
                foreach ($users as $key => $user) {
                    if ($key < 3) {
                        $html .= ' <a href="#" class="avatar rounded-circle avatar-sm">';
                        $html .= '<img class="hweb" src="' . $user->getImgImageAttribute() . '" title="' . $user->name . '">';
                        $html .= '</a>';
                    }
                }

                if (count($users) > 3) {
                    $html .= '<a href="#" class="avatar rounded-circle avatar-sm"><img avatar="';
                    $html .= count($users) - 3;
                    $html .= '"></a>';
                }
                $html .= '</div>';
            }
            $html .= '</div></div></div>';

            print_r($html);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function getDefaultTaskInfo(Request $request, $task_id)
    {

        if (\Auth::check()) {
            if (\Auth::user()->can('view project task')) {
                $response = [];
                $task = ProjectTask::find($task_id);
                if ($task) {
                    $response['task_name'] = $task->name;
                    $response['task_due_date'] = $task->due_date;
                }

                return json_encode($response);
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        } else {
            $response = [];
            $task = ProjectTask::find($task_id);
            if ($task) {
                $response['task_name'] = $task->name;
                $response['task_due_date'] = $task->due_date;
            }

            return json_encode($response);
        }
    }

    // Calendar View
    public function calendarView($task_by, $project_id = NULL)
    {
        $usr = Auth::user();
        $transdate = date('Y-m-d', time());

        if ($usr->type != 'admin') {
            if (\Auth::user()->type == 'client') {
                $user_projects = Project::where('client_id', \Auth::user()->id)->pluck('id', 'id')->toArray();
            } else {
                $user_projects = $usr->projects()->pluck('project_id', 'project_id')->toArray();
            }
            $user_projects = (!empty($project_id) && $project_id > 0) ? [$project_id] : $user_projects;

            if (\Auth::user()->type == 'company') {
                $tasks = ProjectTask::whereIn('project_id', $user_projects);
            } elseif (\Auth::user()->type != 'company') {
                if (\Auth::user()->type == 'client') {

                    $tasks = ProjectTask::whereIn('project_id', $user_projects);
                } else {
                    $tasks = ProjectTask::whereIn('project_id', $user_projects)->whereRaw("find_in_set('" . \Auth::user()->id . "',assign_to)");
                }
            }
            if (\Auth::user()->type == 'client') {
                if ($task_by == 'all') {
                    $tasks->where('created_by', \Auth::user()->creatorId());
                }
            } else {
                if ($task_by == 'my') {
                    $tasks->whereRaw("find_in_set('" . $usr->id . "',assign_to)");
                }
            }
            $tasks = $tasks->get();
            $arrTasks = [];

            foreach ($tasks as $task) {
                $arTasks = [];
                if ((!empty($task->start_date) && $task->start_date != '0000-00-00') || !empty($task->end_date) && $task->end_date != '0000-00-00') {
                    $arTasks['id'] = $task->id;
                    $arTasks['title'] = $task->name;

                    if (!empty($task->start_date) && $task->start_date != '0000-00-00') {
                        $arTasks['start'] = $task->start_date;
                    } elseif (!empty($task->end_date) && $task->end_date != '0000-00-00') {
                        $arTasks['start'] = $task->end_date;
                    }
                    if (!empty($task->end_date) && $task->end_date != '0000-00-00') {
                        $arTasks['end'] = $task->end_date;
                    } elseif (!empty($task->start_date) && $task->start_date != '0000-00-00') {
                        $arTasks['end'] = $task->start_date;
                    }
                    $arTasks['allDay'] = !0;
                    $arTasks['className'] = 'event-' . ProjectTask::$priority_color[$task->priority];
                    $arTasks['description'] = $task->description;
                    $arTasks['url'] = route('task.calendar.show', $task->id);
                    $arTasks['resize_url'] = route('task.calendar.drag', $task->id);
                    $arrTasks[] = $arTasks;
                }
            }

            return view('tasks.calendar', compact('arrTasks', 'project_id', 'task_by', 'transdate'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    // Calendar Show
    public function calendarShow($id)
    {
        $task = ProjectTask::find($id);

        return view('tasks.calendar_show', compact('task'));
    }

    // Calendar Drag
    public function calendarDrag(Request $request, $id)
    {
        $task = ProjectTask::find($id);
        $task->start_date = $request->start;
        $task->end_date = $request->end;
        $task->save();
    }

    //for Google Calendar
    public function get_task_data(Request $request)
    {
        if ($request->get('calender_type') == 'goggle_calender') {
            $type = 'task';
            $arrayJson = Utility::getCalendarData($type);
        } else {
            if (Auth::user()->type == 'client') {
                $user_projects = Project::where('client_id', \Auth::user()->id)->pluck('id', 'id')->toArray();
                $data = ProjectTask::whereIn('project_id', $user_projects)->get();
            } else {
                if (Auth::user()->type == 'company') {
                    $data = ProjectTask::where('created_by', \Auth::user()->creatorId())->get();
                } else {
                    $usr = Auth::user();
                    $user_projects = $usr->projects()->pluck('project_id', 'project_id')->toArray();
                    $data = ProjectTask::whereIn('project_id', $user_projects)
                        ->where('created_by', \Auth::user()->creatorId())
                        ->whereRaw("find_in_set('" . \Auth::user()->id . "',assign_to)")->get();
                }
            }

            //            $data = ProjectTask::where('created_by', \Auth::user()->creatorId())->get();
            $arrayJson = [];
            foreach ($data as $val) {
                $end_date = date_create($val->end_date);
                date_add($end_date, date_interval_create_from_date_string("1 days"));
                $arrayJson[] = [
                    "id" => $val->id,
                    "title" => $val->name,
                    "start" => $val->start_date,
                    "end" => date_format($end_date, "Y-m-d H:i:s"),
                    "className" => 'event-primary',
                    "textColor" => '#51459d',
                    "allDay" => true,
                    'url' => route('task.calendar.show', $val->id),
                    'resize_url' => route('task.calendar.drag', $val->id),
                ];
            }
        }

        return $arrayJson;
    }

    public function task_add(Request $request)
    {
        $usr = Auth::user();
        $project = Project::where('id', $request->task['project_update'])->first();
        // dd($request->all());
        // $post = $request->all();
        $post['project_id'] = $project->id;
        $post['name'] = $request->task['task'];
        $post['stage_id'] = $request->task['stage'];
        $post['assign_to'] = '';
        $post['created_by'] = \Auth::user()->creatorId();
        $post['owned_by'] = \Auth::user()->ownedId();
        $task = ProjectTask::create($post);
        $task->owned_by = \Auth::user()->ownedId();
        $task->save();
        $task = ProjectTask::where('id', $task->id)->first();
        $show_url = route('projects.tasks.show', [$project->id, $task->id]);
        $edit_url = route('projects.tasks.edit', [$project->id, $task->id]);
        $del_url = route('projects.tasks.destroy', [$project->id, $task->id]);
        return response()->json([
            'success' => true,
            'message' => 'Task added successfully.',
            'task' => $task,
            'show_url' => $show_url,
            'edit_url' => $edit_url,
            'del_url' => $del_url,
        ]);
    }
}
