{{ Form::open(array('url' => 'assign-target')) }}
    <div class="modal-body">
        <div class="row">
            <div class="form-group">
                {{ Form::label('user', __('Name')) }}
                {{ Form::select('user',$assign_user,null, array('class' => 'form-control','required'=>'required')) }}
            </div>
            <div class="form-group">
                {{ Form::label('month', __('Month')) }}
                {{ Form::month('month', null, array('class' => 'form-control','required'=>'required')) }}
            </div>
            <div class="form-group">
                {{ Form::label('lead_tar', __('Lead Target')) }}
                {{ Form::text('lead_tar', '', array('class' => 'form-control','required'=>'required')) }}
            </div>
            <div class="form-group">
                {{ Form::label('deal_tar', __('Deal Target')) }}
                {{ Form::text('deal_tar', '', array('class' => 'form-control','required'=>'required')) }}
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{__('Create')}}" class="btn  btn-primary">
    </div>
{{Form::close()}}



