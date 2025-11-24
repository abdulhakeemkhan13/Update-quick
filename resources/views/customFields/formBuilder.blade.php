@if($customFields)
    @foreach($customFields as $customField)
        @if($customField->type == 'text')
            <div class="col-6 form-group">
                {{ Form::label('customField-'.$customField->id, __($customField->name),['class'=>'form-label']) }}
                <div class="input-group">
                    {{ Form::text('customField['.$customField->id.']', null, array('class' => 'form-control',$customField->is_required === 'yes' ? 'required' : '')) }}
                </div>
            </div>
        @elseif($customField->type == 'email')
            <div class="col-6 form-group">
                {{ Form::label('customField-'.$customField->id, __($customField->name),['class'=>'form-label']) }}
                <div class="input-group">
                    {{ Form::email('customField['.$customField->id.']', null, array('class' => 'form-control',$customField->is_required === 'yes' ? 'required' : '')) }}
                </div>
            </div>
        @elseif($customField->type == 'number')
            <div class="col-6 form-group">
                {{ Form::label('customField-'.$customField->id, __($customField->name),['class'=>'form-label']) }}
                <div class="input-group">
                    {{ Form::number('customField['.$customField->id.']', null, array('class' => 'form-control',$customField->is_required === 'yes' ? 'required' : '')) }}
                </div>
            </div>
        @elseif($customField->type == 'date')
            <div class="col-6 form-group">
                {{ Form::label('customField-'.$customField->id, __($customField->name),['class'=>'form-label']) }}
                <div class="input-group">
                    {{ Form::date('customField['.$customField->id.']', null, array('class' => 'form-control',$customField->is_required === 'yes' ? 'required' : '')) }}
                </div>
            </div>
        @elseif($customField->type == 'month')
            <div class="col-6 form-group">
                {{ Form::label('customField-'.$customField->id, __($customField->name),['class'=>'form-label']) }}
                <div class="input-group">
                    {{ Form::month('customField['.$customField->id.']', null, array('class' => 'form-control',$customField->is_required === 'yes' ? 'required' : '')) }}
                </div>
            </div>
        @elseif($customField->type == 'time')
            <div class="col-6 form-group">
                {{ Form::label('customField-'.$customField->id, __($customField->name),['class'=>'form-label']) }}
                <div class="input-group">
                    {{ Form::time('customField['.$customField->id.']', null, array('class' => 'form-control',$customField->is_required === 'yes' ? 'required' : '')) }}
                </div>
            </div>
        @elseif($customField->type == 'textarea')
            <div class="col-6 form-group">
                {{ Form::label('customField-'.$customField->id, __($customField->name),['class'=>'form-label']) }}
                <div class="input-group">
                    {{ Form::textarea('customField['.$customField->id.']', null, array('class' => 'form-control',$customField->is_required === 'yes' ? 'required' : '')) }}
                </div>
            </div>
        @elseif($customField->type == 'list')
            @php
                $valuesArray = explode(',', $customField->values);
                $values = array_combine($valuesArray, $valuesArray);
                $values = array_merge(['' => 'Select an option'], $values);
            @endphp
            <div class="col-6 form-group">
                {{ Form::label('customField-'.$customField->id, __($customField->name),['class'=>'form-label']) }}
                <div class="input-group">
                    {{ Form::select('customField['.$customField->id.']', $values, null, array('class' => 'form-control', $customField->is_required === 'yes' ? 'required' : '')) }}
                </div>
            </div>
        @elseif($customField->type == 'radio')
            @php
                $valuesArray = explode(',', $customField->values);
            @endphp
            <div class="col-6 form-group">
                {{ Form::label('customField-'.$customField->id, __($customField->name), ['class' => 'form-label']) }}
                <div class="input-group" style="gap: 15px;">
                    @foreach ($valuesArray as $value)
                        <div class="form-check">
                            {{ Form::radio(
                                'customField['.$customField->id.']',
                                $value,
                                null,
                                ['id' => 'customField-'.$customField->id.'-'.$value, 'class' => 'form-check-input']
                            ) }}
                            {{ Form::label('customField-'.$customField->id.'-'.$value, ucfirst($value), ['class' => 'form-check-label']) }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
@endif


