{{ Form::model($lead, array('route' => array('leads.poststagemove', $lead->id), 'method' => 'POST')) }}

<div class="modal-body">
    <div class="col-12 form-group">
        {{ Form::label('stage_id', __('Stage'), ['class' => 'form-label']) }}<span class="text-danger">*</span>
        {{ Form::select('stage_id', $lead_stages, null, ['class' => 'form-control select', 'required' => 'required']) }}
    </div>
    <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light " data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Submit') }}" class="btn  btn-primary float-end">
</div>


{{ Form::close() }}
