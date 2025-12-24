@extends('layouts.admin')
@section('page-title')
    {{__('Delayed Charges')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Delayed Charges')}}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <button class="btn btn-sm btn-primary openDelayedChargeModal" data-url="{{ route('delayed-charge.create') }}" data-bs-toggle="tooltip" title="{{__('Create Delayed Charge')}}">
            {{__('Create Delayed Charge')}}
            <i class="ti ti-plus"></i>
        </button>
    </div>
@endsection

@section('content')
    {{-- Fullscreen Modal for Create/Edit --}}
    <div class="modal fade" id="delayedChargeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-fullscreen">
            <div class="modal-content">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2" id="filters"></div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body table-border-style">
            <div class="table-responsive">
                <table class="table datatable">
                    <thead>
                        <tr>
                            <th>{{ __('Delayed Charge #') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th width="10%">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody> 
                        @foreach ($delayedCharges as $delayedCharge)
                            <tr>
                                <td class="Id">
                                    <a href="#" class="btn btn-outline-primary openDelayedChargeModal" data-url="{{ route('delayed-charge.edit', $delayedCharge->id) }}">{{ $delayedCharge->charge_id }}</a>
                                </td>
                                <td>{{ $delayedCharge->customer->name ?? 'N/A' }}</td>
                                <td>{{ \Auth::user()->dateFormat($delayedCharge->date) }}</td>
                                <td>{{ \Auth::user()->priceFormat($delayedCharge->total_amount) }}</td>
                                <td>
                                    <div class="action-btn bg-info ms-2">
                                        <a href="#" data-url="{{ route('delayed-charge.edit', $delayedCharge->id) }}" class="mx-3 btn btn-sm align-items-center openDelayedChargeModal" data-bs-toggle="tooltip" title="{{__('Edit')}}" data-original-title="{{__('Edit')}}">
                                            <i class="ti ti-pencil text-white"></i>
                                        </a>
                                    </div>
                                    <div class="action-btn bg-danger ms-2">
                                        {!! Form::open(['method' => 'DELETE', 'route' => ['delayed-charge.destroy', $delayedCharge->id],'id'=>'delete-form-'.$delayedCharge->id]) !!}
                                            <a href="#" class="mx-3 btn btn-sm  align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{__('Delete')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$delayedCharge->id}}').submit();">
                                                <i class="ti ti-trash text-white"></i>
                                            </a>
                                        {!! Form::close() !!}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@push('script-page')
    <script>
        $(document).on('click', '.openDelayedChargeModal', function (e) {
            e.preventDefault();
            var url = $(this).data('url');
            $('#delayedChargeModal').modal('show');
            $('#delayedChargeModal .modal-content').html('<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
            $.ajax({
                url: url,
                type: 'GET',
                success: function (res) {
                    $('#delayedChargeModal .modal-content').html(res);
                },
                error: function () {
                    $('#delayedChargeModal .modal-content').html('<div class="text-center text-danger p-5">Something went wrong!</div>');
                }
            });
        });
    </script>
@endpush
@endsection
