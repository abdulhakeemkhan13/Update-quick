
{{ Form::model($task, [
    'route'  => 'task-Creation-On-Dashboard-Update',
    'id'     => 'edit_task',
    'method' => 'POST'
]) }}

{{-- include the task id so the update knows what to save --}}
{{ Form::hidden('task_id', $task->id) }}

<div class="modal-body">
  <div class="row">
    <div class="col-6">
      <div class="form-group">
        {{ Form::label('name', __('Task name'), ['class' => 'form-label']) }}<span class="text-danger">*</span>
        {{ Form::text('name', null, ['class' => 'form-control','required'=>'required']) }}
      </div>
    </div>

    <div class="col-6">
      <div class="form-group">
        {{ Form::label('priority', __('Priority'), ['class' => 'form-label']) }}
        <small class="form-text text-muted mb-2 mt-0">{{ __('Set Priority of your task') }}</small>
        <select class="form-control select" name="priority" id="priority" required>
          @foreach(\App\Models\ProjectTask::$priority as $key => $val)
            <option value="{{ $key }}" {{ ($key == $task->priority) ? 'selected' : '' }}>{{ __($val) }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="col-12">
      <div class="form-group">
        {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
        <small class="form-text text-muted mb-2 mt-0">{{ __('This textarea will autosize while you type') }}</small>
        {{ Form::textarea('description', null, ['class' => 'form-control','rows'=>'1','data-toggle' => 'autosize']) }}
      </div>
    </div>

    <div class="col-6">
      <div class="form-group">
        {{ Form::label('estimated_hrs', __('Estimated Hours'), ['class' => 'form-label']) }}<span class="text-danger">*</span>
        @php $allocated = data_get($hrs ?? [], 'allocated'); @endphp
        @if(!is_null($allocated))
          <small class="form-text text-muted mb-2 mt-0">
            {{ __('allocated total ') . $allocated . __(' hrs in other tasks') }}
          </small>
        @endif
        {{ Form::number('estimated_hrs', null, ['class' => 'form-control','required' => 'required','min'=>'0','maxlength' => '8']) }}
      </div>
    </div>

    <div class="col-6">
      <div class="form-group">
        {{ Form::label('start_date', __('Start Date'), ['class' => 'form-label']) }}
        {{ Form::date('start_date', $task->start_date ? \Carbon\Carbon::parse($task->start_date)->toDateString() : null, ['class' => 'form-control']) }}
      </div>
    </div>

    <div class="col-6">
      <div class="form-group">
        {{ Form::label('end_date', __('End Date'), ['class' => 'form-label']) }}
        {{ Form::date('end_date', $task->end_date ? \Carbon\Carbon::parse($task->end_date)->toDateString() : null, ['class' => 'form-control']) }}
      </div>
    </div>

    @if(!$customFields->isEmpty())
      @include('customFields.formBuilder')
    @endif
  </div>

  {{-- Members (show all if projectless) --}}
  <div class="form-group">
    <label class="form-label">{{ __('Task members') }}</label>
    <small class="form-text text-muted mb-2 mt-0">
      {{ $project ? __('Below users are assigned in your project.') : __('All members in your workspace.') }}
    </small>
  </div>

  @php
    // Use $members passed from controller (all users in tenant)
    // Preselect from CSV
    $preselected = collect(explode(',', $task->assign_to ?? ''))->filter()->values()->all();
  @endphp

  <div class="list-group list-group-flush mb-4">
    <div class="row">
      @foreach(($members ?? collect()) as $user)
        @php $isSelected = in_array((string)$user->id, $preselected, true); @endphp
        <div class="col-6">
          <div class="list-group-item px-0">
            <div class="row align-items-center">
              <div class="col-auto">
                <a href="#" class="avatar avatar-sm rounded-circle">
                  <img class="wid-40 rounded-circle ml-3"
                       data-original-title="{{ $user->name }}"
                       src="{{ $user->avatar
                              ? asset('/storage/uploads/avatar/'.$user->avatar)
                              : asset('/storage/uploads/avatar/avatar.png') }}" />
                </a>
              </div>
              <div class="col">
                <p class="d-block h6 text-sm mb-0">{{ $user->name }}</p>
                <p class="card-text text-sm text-muted mb-0">{{ $user->email }}</p>
              </div>

              <div class="col-auto text-end add_usr {{ $isSelected ? 'is-selected' : '' }}"
                   data-id="{{ $user->id }}">
                <button type="button" class="btn mr-3">
                  <span class="btn-inner--visible">
                    <i class="ti ti-{{ $isSelected ? 'check' : 'plus' }}" id="usr_icon_{{ $user->id }}"></i>
                  </span>
                  <span class="btn-inner--hidden text-white" id="usr_txt_{{ $user->id }}">
                    {{ $isSelected ? __('Added') : __('Add') }}
                  </span>
                </button>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    {{-- Hidden CSV of selected user IDs --}}
    {{ Form::hidden('assign_to', implode(',', $preselected)) }}
  </div>
</div>

<div class="modal-footer">
  <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
  <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
</div>

{{ Form::close() }}
