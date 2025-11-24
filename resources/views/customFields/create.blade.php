{{ Form::open(array('url' => 'custom-field')) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {{Form::label('name',__('Custom Field Name'),['class'=>'form-label'])}}
            {{Form::text('name',null,array('class'=>'form-control','required'=>'required' , 'placeholder'=>__('Enter Custom Field Name')))}}
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('type', __('Type'),['class'=>'form-label']) }}
            {{ Form::select('type',$types,null, array('class' => 'form-control select ','required'=>'required','id'=>'type')) }}
        </div>
        <div class="form-group col-md-12 d-none val">
            {{ Form::label('values', __('Values'),['class'=>'form-label']) }}<span>  (please use comma , to separate values) </span>
            {{Form::text('values',null,array('class'=>'form-control' , 'placeholder'=>__('Enter Select Field Values')))}}
        </div>
        <div class="form-group col-md-12">
            {{Form::label('is_required',__('Is Required'),['class'=>'form-label'])}}
            {{ Form::select('is_required', $is_required,null, array('class' => 'form-control select')) }}
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('module', __('Module'),['class'=>'form-label']) }}
            {{ Form::select('module',$modules,null, array('class' => 'form-control select ','required'=>'required')) }}
        </div>

    </div>
</div>

<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Create')}}" class="btn btn-primary">
</div>
{{ Form::close() }}
<script>
$('#type').on('change', function() {
    var type = $(this).val();
    if(type == 'list' || type == 'radio'){
        $('.val').removeClass('d-none');
    }else{
        $('.val').addClass('d-none');
     }
});
</script>
