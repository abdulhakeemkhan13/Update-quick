
{{ Form::open(['route' => ['employee_store'], 'method' => 'post', 'enctype' => 'multipart/form-data']) }}
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    {!! Form::label('name', __('Name'), ['class' => 'form-label']) !!}<span class="text-danger pl-1">*</span>
                    {!! Form::text('name', old('name'), ['class' => 'form-control', 'required' => 'required' ,'placeholder'=>'Enter employee name']) !!}
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    {!! Form::label('phone', __('Phone'), ['class' => 'form-label']) !!}<span class="text-danger pl-1">*</span>
                    {!! Form::text('phone', old('phone'), ['class' => 'form-control' , 'required' => 'required' ,'placeholder'=>'Enter employee phone'  ]) !!}
               
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    {!! Form::label('dob', __('Date of Birth'), ['class' => 'form-label']) !!}<span class="text-danger pl-1">*</span>
                    {{ Form::date('dob', null, ['class' => 'form-control ', 'required' => 'required', 'autocomplete' => 'off','placeholder'=>'Select Date of Birth']) }}
             
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    {!! Form::label('gender', __('Gender'), ['class' => 'form-label' , 'required' => 'required' ]) !!}<span class="text-danger pl-1">*</span>
                    <div class="d-flex radio-check">
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="g_male" value="Male" name="gender"
                                class="form-check-input" checked>
                            <label class="form-check-label " for="g_male">{{ __('Male') }}</label>
                        </div>
                        <div class="custom-control custom-radio ms-1 custom-control-inline">
                            <input type="radio" id="g_female" value="Female" name="gender"
                                class="form-check-input">
                            <label class="form-check-label "
                                for="g_female">{{ __('Female') }}</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="modal-footer">
        <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{__('Create')}}" class="btn  btn-primary">
    </div>
{{Form::close()}}

           