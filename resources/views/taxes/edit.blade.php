{{ Form::model($tax, ['route' => ['taxes.update', $tax->id], 'method' => 'PUT']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-6">
            {{ Form::label('name', __('Tax Rate Name'), ['class' => 'form-label']) }}
            {{ Form::text('name', null, ['class' => 'form-control font-style', 'required' => 'required']) }}
            @error('name')
                <small class="invalid-name" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </small>
            @enderror
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('rate', __('Tax Rate %'), ['class' => 'form-label']) }}
            {{ Form::number('rate', null, ['class' => 'form-control', 'required' => 'required', 'step' => '0.01']) }}
            @error('rate')
                <small class="invalid-rate" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </small>
            @enderror
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('chart_account_id', __('Sales Tax Liability Account'), ['class' => 'form-label']) }}
            {{ Form::select('chart_account_id', $chartAccounts ?? [], $tax->chart_account_id ?? null, ['class' => 'form-control select2', 'placeholder' => __('Select Account')]) }}
            <small
                class="text-muted">{{ __('The liability account where collected sales tax will be recorded.') }}</small>
            @error('chart_account_id')
                <small class="invalid-chart_account_id" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </small>
            @enderror
        </div>

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
</div>
{{ Form::close() }}
