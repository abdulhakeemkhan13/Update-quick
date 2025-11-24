
{{ Form::open(array('route' => array('leads.import'),'method'=>'post', 'enctype' => "multipart/form-data")) }}
<div class="modal-body">
    <div class="row">
        <div class="col-md-12 mb-6">
            {{Form::label('file',__('Download sample Lead CSV file'),['class'=>'form-label'])}}
            <a href="{{asset(Storage::url('uploads/sample')).'/sample-lead.csv'}}" class="btn btn-sm btn-primary">
                <i class="ti ti-download"></i> {{__('Download')}}
            </a>
        </div>
        <input type="hidden" name="pipeline" value="{{$id}}">
        <div class="col-md-12 mb-6">
            {{ Form::label('stage_id', __('Stage'),['class'=>'form-label']) }}
            <select class="select form-select " name="stage_id" >
                @foreach ($stages as $stage)
                    <option value="{{@$stage->id }}" >{{ @$stage->name}}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-12 mb-6  mt-2">
            {{ Form::label('user_id', __('User'),['class'=>'form-label']) }}
            <select class="select form-select"  multiple="multiple" name="all_users[]" id="all_users">
                <option value="" class="">{{ __('All Users') }}</option>
                @foreach ($users as $user)
                    <option value="{{@$user->id }}" >{{ @$user->name}}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-12  mt-2">
            {{Form::label('file',__('Select CSV File'),['class'=>'form-label',])}}
            <div class="choose-file form-group" >
                <label for="file" class="form-label" style ='width: 100% !important;'>
                    <input type="file" class="form-control" name="file" id="file" data-filename="upload_file" required>
                </label>
                <p class="upload_file"></p>
            </div>
        </div>

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Upload')}}" class="btn  btn-primary">
</div>
{{ Form::close() }}

<script>
    $(document).ready(function() {
        $($("#all_users")).each(function(index, element) {
            var id = $(element).attr('id');
            var multipleCancelButton = new Choices(
                '#' + id, {
                    removeItemButton: true,
                }
            );
        });
    });
</script>
