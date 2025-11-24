@extends('layouts.admin')

@section('page-title')
    {{ __('Tasks') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Tasks (No Project)') }}</li>
@endsection

{{-- action button --}}
@section('action-btn')
    {{-- create task --}}
    <div class="float-end">
        <a href="#" data-size="lg" data-url="{{ route('task-Creation-On-Dashboard-Create') }}" data-ajax-popup="true"
            data-bs-toggle="tooltip" title="{{ __('Create New Task') }}" class="btn btn-sm btn-primary">
            {{ __('Create New Task') }}
            <i class="ti ti-plus"></i>
        </a>
    </div>
@endsection

@section('content')
    <div class="col-md-12">
        <div class="card">
            <div class="col-12">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('Name') }}</th>
                                    <th scope="col">{{ __('Priority') }}</th>
                                    <th scope="col">{{ __('End Date') }}</th>
                                    <th scope="col">{{ __('Assigned To') }}</th>
                                    <th scope="col">{{ __('Completion') }}</th>
                                    <th scope="col">{{ __('Attachments') }}</th>
                                    <th scope="col">{{ __('Actions') }}</th>
                                </tr>
                            </thead>

                            <tbody class="list">
                                @forelse ($tasks as $task)
                                    @php
                                        // Projectless check
                                        $hasProject = !empty($task->project_id) && (int) $task->project_id !== 0;

                                        // Stage (if you have a relation; guard it)
                                        $stageName = $hasProject ? optional($task->stage)->name : null;

                                        // Priority label/color with safe fallbacks
                                        $priorityKey = $task->priority ?? 'medium';
                                        $priorityLabel =
                                            \App\Models\ProjectTask::$priority[$priorityKey] ?? ucfirst($priorityKey);
                                        $priorityColor =
                                            \App\Models\ProjectTask::$priority_color[$priorityKey] ?? 'secondary';

                                        // End date formatting
                                        $endDateRaw = $task->end_date ? \Carbon\Carbon::parse($task->end_date) : null;
                                        $isOverdue = $endDateRaw ? $endDateRaw->isPast() : false;
                                        $endDate = $endDateRaw
                                            ? \App\Models\Utility::getDateFormated($endDateRaw)
                                            : '—';

                                        // Assigned users from comma-separated ids (or '-')
                                        $taskuser = [];
                                        $getUsers = \App\Models\ProjectTask::getusers(); // existing helper that returns [id => ['name'=>..., 'avatar'=>...]]
                                        if (!empty($task->assign_to)) {
                                            foreach (explode(',', $task->assign_to) as $uid) {
                                                $uid = trim($uid);
                                                if ($uid !== '' && isset($getUsers[$uid])) {
                                                    $taskuser[] = [
                                                        'name' => $getUsers[$uid]['name'] ?? '',
                                                        'avatar' => $getUsers[$uid]['avatar'] ?? null,
                                                    ];
                                                }
                                            }
                                        }

                                        // Progress: use method if present; else column
                                        $progressPct = method_exists($task, 'taskProgress')
                                            ? $task->taskProgress($task)['percentage'] ?? ($task->progress ?? '0%')
                                            : (is_numeric($task->progress)
                                                ? $task->progress . '%'
                                                : $task->progress ?? '0%');

                                        $progressClr = method_exists($task, 'taskProgress')
                                            ? $task->taskProgress($task)['color'] ?? 'secondary'
                                            : 'primary';
                                    @endphp

                                    <tr>
                                        <td>
                                            <span class="h6 text-sm font-weight-bold mb-0">
                                                {{-- No project route when projectless --}}
                                                @if ($hasProject)
                                                    <a
                                                        href="{{ route('projects.tasks.index', $task->project->id) }}">{{ $task->name }}</a>
                                                @else
                                                    {{ $task->name }}
                                                @endif
                                            </span>

                                            <span class="d-flex text-sm text-muted justify-content-between">
                                                @if ($hasProject)
                                                    <p class="m-0">{{ $task->project->project_name }}</p>
                                                    @php
                                                        $checkProject = \Auth::user()->checkProject($task->project_id);
                                                    @endphp
                                                    <span
                                                        class="me-5 badge p-2 px-3 rounded bg-{{ $checkProject == 'Owner' ? 'success' : 'warning' }}">
                                                        {{ __($checkProject) }}
                                                    </span>
                                                @else
                                                    <p class="m-0"></p>
                                                @endif
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status_badge badge p-2 px-3 rounded bg-{{ $priorityColor }}">
                                                {{ __($priorityLabel) }}
                                            </span>
                                        </td>

                                        <td class="{{ $isOverdue ? 'text-danger' : '' }}">
                                            {{ $endDate }}
                                        </td>

                                        <td>
                                            <div class="avatar-group">
                                                @if (count($taskuser) > 0)
                                                    @foreach ($taskuser as $user)
                                                        <a href="#" class="avatar rounded-circle avatar-sm"
                                                            title="{{ $user['name'] }}">
                                                            <img @if (!empty($user['avatar'])) src="{{ asset('/storage/uploads/avatar/' . $user['avatar']) }}"
                                                        @else
                                                            src="{{ asset('/storage/uploads/avatar/avatar.png') }}" @endif
                                                                alt="{{ $user['name'] }}">
                                                        </a>
                                                    @endforeach
                                                @else
                                                    {{ __('-') }}
                                                @endif
                                            </div>
                                        </td>

                                        <td>
                                            <div class="align-items-center">
                                                <span class="completion">{{ $progressPct }}</span>
                                                <div class="progress">
                                                    <div class="progress-bar bg-{{ $progressClr }}" role="progressbar"
                                                        style="width: {{ is_string($progressPct) ? $progressPct : $progressPct . '%' }};">
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="text-end w-15">
                                            <div class="actions">
                                                <a class="action-item px-1" data-bs-toggle="tooltip"
                                                    title="{{ __('Attachment') }}">
                                                    <i class="ti ti-paperclip mr-2"></i>{{ count($task->taskFiles ?? []) }}
                                                </a>
                                                <a class="action-item px-1" data-bs-toggle="tooltip"
                                                    title="{{ __('Comment') }}">
                                                    <i
                                                        class="ti ti-brand-hipchat mr-2"></i>{{ count($task->comments ?? []) }}
                                                </a>
                                                <a class="action-item px-1" data-bs-toggle="tooltip"
                                                    title="{{ __('Checklist') }}">
                                                    <i
                                                        class="ti ti-list-check mr-2"></i>{{ method_exists($task, 'countTaskChecklist') ? $task->countTaskChecklist() : 0 }}
                                                </a>
                                            </div>
                                        </td>

                                        <td class="text-end">
                                            <div class="actions">
                                                {{-- edit button to open modal which is on this route named task-Creation-On-Dashboard-Edit --}}
                                                <a class="action-item px-1" href="#"
                                                    data-url="{{ route('task-Creation-On-Dashboard-Edit') . '?task_id=' . $task->id }}"
                                                    data-size="lg" data-ajax-popup="true" data-bs-toggle="tooltip"
                                                    data-original-title="{{ __('Edit') }}">
                                                    <i class="ti ti-pencil"></i>
                                                </a>

                                                <div class="action-btn bg-danger ms-2">
                                                    {!! Form::open([
                                                        'method' => 'DELETE',
                                                        'route' => ['task-Creation-On-Dashboard-Delete', $task->id],
                                                        'id' => 'delete-task-form-' . $task->id,
                                                    ]) !!}
                                                    <a href="#"
                                                        class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                        data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                        data-original-title="{{ __('Delete') }}"
                                                        data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                        data-confirm-yes="document.getElementById('delete-task-form-{{ $task->id }}').submit();">
                                                        <i class="ti ti-trash text-white"></i>
                                                    </a>
                                                    {!! Form::close() !!}
                                                </div>


                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <th scope="col" colspan="7">
                                            <h6 class="text-center">{{ __('No tasks found') }}</h6>
                                        </th>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div> <!-- table-responsive -->
                </div>
            </div>
        </div>
    </div>
@endsection
