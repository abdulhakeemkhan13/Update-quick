{{-- create.blade.php --}}
{{ Form::open(['route' => 'task-Creation-On-Dashboard-Store', 'id' => 'create_task']) }}

<div class="modal-body">

    <div class="row">
        <div class="col-6">
            <div class="form-group">
                {{ Form::label('name', __('Task name'), ['class' => 'form-label']) }}<span class="text-danger">*</span>
                {{ Form::text('name', null, ['class' => 'form-control', 'required' => 'required']) }}
            </div>
        </div>

        {{-- Milestone: show only if a project exists --}}
        @php $milestones = isset($project) && $project ? ($project->milestones ?? collect()) : collect(); @endphp
        {{-- <div class="col-6">
            <div class="form-group">
                {{ Form::label('milestone_id', __('Milestone'), ['class' => 'form-label']) }}
                <select class="form-control select" name="milestone_id" id="milestone_id"
                    {{ $milestones->isEmpty() ? 'disabled' : '' }}>
                    <option value="0" class="text-muted">{{ __('Select Milestone') }}</option>
                    @foreach ($milestones as $m_val)
                        <option value="{{ $m_val->id }}">{{ $m_val->title }}</option>
                    @endforeach
                </select>
                @if ($milestones->isEmpty())
                    <small
                        class="form-text text-muted">{{ __('No milestones (task is not linked to a project).') }}</small>
                @endif
            </div>
        </div> --}}

        <div class="col-6">
            <div class="form-group">
                {{ Form::label('priority', __('Priority'), ['class' => 'form-label']) }}
                <small class="form-text text-muted mb-2 mt-0">{{ __('Set Priority of your task') }}</small>
                <select class="form-control select" name="priority" id="priority" required>
                    @foreach (\App\Models\ProjectTask::$priority as $key => $val)
                        <option value="{{ $key }}">{{ __($val) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-12">
            <div class="form-group">
                {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
                <small
                    class="form-text text-muted mb-2 mt-0">{{ __('This textarea will autosize while you type') }}</small>
                {{ Form::textarea('description', null, ['class' => 'form-control', 'rows' => '1', 'data-toggle' => 'autosize']) }}
            </div>
        </div>

        <div class="col-6">
            <div class="form-group">
                {{ Form::label('estimated_hrs', __('Estimated Hours'), ['class' => 'form-label']) }}<span
                    class="text-danger">*</span>
                @php $allocated = data_get($hrs ?? [], 'allocated', null); @endphp
                @if (!is_null($allocated))
                    <small
                        class="form-text text-muted mb-2 mt-0">{{ __('allocated total ') . $allocated . __(' hrs in other tasks') }}</small>
                @endif
                {{ Form::number('estimated_hrs', null, ['class' => 'form-control', 'required' => 'required', 'min' => '0', 'maxlength' => '8']) }}
            </div>
        </div>

        <div class="col-6">
            <div class="form-group">
                {{ Form::label('start_date', __('Start Date'), ['class' => 'form-label']) }}
                {{ Form::date('start_date', null, ['class' => 'form-control']) }}
            </div>
        </div>
        <div class="col-6">
            <div class="form-group">
                {{ Form::label('end_date', __('End Date'), ['class' => 'form-label']) }}
                {{ Form::date('end_date', null, ['class' => 'form-control']) }}
            </div>
        </div>

        @if (!empty($customFields) && !$customFields->isEmpty())
            @include('customFields.formBuilder')
        @endif
    </div>

    {{-- All Members --}}
    @php
        use App\Models\User;
        $projectUsers = isset($project) && $project ? $project->users ?? collect() : collect();

        // Fallback to all members if no project users
        if ($projectUsers->isEmpty()) {
            $projectUsers = User::where('created_by', Auth::user()->creatorId())
                ->orderBy('name')
                ->get();
        }
    @endphp
    @if (!$projectUsers->isEmpty())
        <div class="form-group">
            <label class="form-label">{{ __('Task members') }}</label>
            <small class="form-text text-muted mb-2 mt-0">{{ __('Below users are assigned in your project.') }}</small>
        </div>
        <div class="list-group list-group-flush mb-4">
            <div class="row">
                @foreach ($projectUsers as $user)
                    <div class="col-6">
                        <div class="list-group-item px-0">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <a href="#" class="avatar avatar-sm rounded-circle">
                                        <img class="wid-40 rounded-circle ml-3"
                                            data-original-title="{{ $user->name ?? '' }}"
                                            src="{{ $user->avatar
                                                ? asset('/storage/uploads/avatar/' . $user->avatar)
                                                : asset('/storage/uploads/avatar/avatar.png') }}" />
                                    </a>
                                </div>
                                <div class="col">
                                    <p class="d-block h6 text-sm mb-0">{{ $user->name }}</p>
                                    <p class="card-text text-sm text-muted mb-0">{{ $user->email }}</p>
                                </div>
                                <div class="col-auto text-end add_usr" data-id="{{ $user->id }}">
                                    <button type="button"
                                        class="btn btn-xs btn-animated btn-blue rounded-pill btn-animated-y mr-3">
                                        <span class="btn-inner--visible">
                                            <i class="ti ti-plus" id="usr_icon_{{ $user->id }}"></i>
                                        </span>
                                        <span class="btn-inner--hidden text-white"
                                            id="usr_txt_{{ $user->id }}">{{ __('Add') }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            {{ Form::hidden('assign_to', null) }}
        </div>
    @endif

    @if (isset($settings['google_calendar_enable']) && $settings['google_calendar_enable'] == 'on')
        <div class="form-group col-md-6">
            {{ Form::label('synchronize_type', __('Synchronize in Google Calendar ?'), ['class' => 'form-label']) }}
            <div class="form-switch">
                <input type="checkbox" class="form-check-input mt-2" name="synchronize_type" id="switch-shadow"
                    value="google_calender">
                <label class="form-check-label" for="switch-shadow"></label>
            </div>
        </div>
    @endif
</div>

<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
</div>

{{ Form::close() }}
<script>
(function(){
  // keep a set in memory so we can toggle easily
  let selectedIds = new Set();

  // helper: read current hidden input and rehydrate selectedIds (useful if modal re-opens)
  function hydrateFromHidden() {
    const input = document.querySelector('#taskCreationOnDashboardModal input[name="assign_to"]') ||
                  document.querySelector('input[name="assign_to"]'); // fallback
    selectedIds = new Set((input?.value || '').split(',').filter(Boolean));
  }

  // helper: write back to hidden input
  function syncHidden() {
    const input = document.querySelector('#taskCreationOnDashboardModal input[name="assign_to"]') ||
                  document.querySelector('input[name="assign_to"]');
    if (input) input.value = Array.from(selectedIds).join(',');
  }

  // helper: update UI for a given user id
  function flipUI(id, added) {
    const txt = document.getElementById('usr_txt_' + id);
    const ico = document.getElementById('usr_icon_' + id);
    const cell = document.querySelector('.add_usr[data-id="' + id + '"]');
    if (txt) txt.textContent = added ? 'Added' : 'Add';
    if (ico) {
      ico.classList.toggle('ti-plus', !added);
      ico.classList.toggle('ti-check', added);
    }
    if (cell) cell.classList.toggle('is-selected', added);
  }

  // when the modal content gets injected, (re)hydrate state
  document.addEventListener('DOMContentLoaded', hydrateFromHidden);

  // good place to hydrate after your fetch injects HTML:
  // In your fetch .then(html=>{ ... modalBodyEl.innerHTML = html; hydrateFromHidden(); ... })

  // global delegated click handler for any .add_usr (works after AJAX injection)
  document.addEventListener('click', function(e){
    const wrapper = e.target.closest('.add_usr');
    if (!wrapper) return;

    e.preventDefault();
    const id = String(wrapper.dataset.id || '').trim();
    if (!id) return;

    if (selectedIds.has(id)) {
      selectedIds.delete(id);
      flipUI(id, false);
    } else {
      selectedIds.add(id);
      flipUI(id, true);
    }
    syncHidden();
  });

  // optional: re-hydrate whenever the modal is shown (Bootstrap event)
  document.addEventListener('shown.bs.modal', function(ev){
    if (ev.target && ev.target.id === 'taskCreationOnDashboardModal') {
      hydrateFromHidden();
      // paint UI to match hidden field (in case of server-side validation errors, etc.)
      document.querySelectorAll('#taskCreationOnDashboardModal .add_usr[data-id]').forEach(el=>{
        const id = String(el.dataset.id || '');
        flipUI(id, selectedIds.has(id));
      });
    }
  });
})();
</script>
