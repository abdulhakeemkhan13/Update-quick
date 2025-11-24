{{ Form::model($customField, array('route' => array('custom-field.update', $customField->id), 'method' => 'PUT')) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {{Form::label('name',__('Custom Field Name'),['class'=>'form-label'])}}
            {{Form::text('name',null,array('class'=>'form-control','required'=>'required'))}}
        </div>
        @if ($customField->type == 'list' || $customField->type == 'radio')
            <div class="form-group col-md-12 ">
                {{ Form::label('values', __('Values'),['class'=>'form-label']) }}<span>  (please use comma , to separate values) </span>
                {{Form::text('values',null,array('class'=>'form-control' , 'placeholder'=>__('Enter Select Field Values')))}}
            </div>
        @endif
        <div class="form-group col-md-12">
            {{Form::label('is_required',__('Is Required'),['class'=>'form-label'])}}
            {{ Form::select('is_required', $is_required,null, array('class' => 'form-control select')) }}
        </div>

    </div>
</div>

    <div class="modal-footer">
        <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{__('Update')}}" class="btn  btn-primary">
    </div>
{{ Form::close() }}

