@extends('layouts.admin')
@section('page-title')
    {{__('Delayed Credits')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Delayed Credits')}}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <button class="btn btn-sm btn-primary openDelayedCreditModal" data-url="{{ route('delayed-credit.create') }}" data-bs-toggle="tooltip" title="{{__('Create Delayed Credit')}}">
            {{__('Create Delayed Credit')}}
            <i class="ti ti-plus"></i>
        </button>
    </div>
@endsection

@section('content')
    {{-- Fullscreen Modal for Create/Edit --}}
    <div class="modal fade" id="delayedCreditModal" tabindex="-1" aria-hidden="true">
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
                            <th>{{ __('Delayed Credit #') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th width="10%">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody> 
                        @foreach ($delayedCredits as $delayedCredit)
                            <tr>
                                <td class="Id">
                                    <a href="#" class="btn btn-outline-primary openDelayedCreditModal" data-url="{{ route('delayed-credit.edit', $delayedCredit->id) }}">{{ $delayedCredit->credit_id }}</a>
                                </td>
                                <td>{{ $delayedCredit->customer->name ?? 'N/A' }}</td>
                                <td>{{ \Auth::user()->dateFormat($delayedCredit->date) }}</td>
                                <td>{{ \Auth::user()->priceFormat($delayedCredit->total_amount) }}</td>
                                <td>
                                    <div class="action-btn bg-info ms-2">
                                        <a href="#" data-url="{{ route('delayed-credit.edit', $delayedCredit->id) }}" class="mx-3 btn btn-sm align-items-center openDelayedCreditModal" data-bs-toggle="tooltip" title="{{__('Edit')}}" data-original-title="{{__('Edit')}}">
                                            <i class="ti ti-pencil text-white"></i>
                                        </a>
                                    </div>
                                    <div class="action-btn bg-danger ms-2">
                                        {!! Form::open(['method' => 'DELETE', 'route' => ['delayed-credit.destroy', $delayedCredit->id],'id'=>'delete-form-'.$delayedCredit->id]) !!}
                                            <a href="#" class="mx-3 btn btn-sm  align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{__('Delete')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$delayedCredit->id}}').submit();">
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
        $(document).on('click', '.openDelayedCreditModal', function (e) {
            e.preventDefault();
            var url = $(this).data('url');
            $('#delayedCreditModal').modal('show');
            $('#delayedCreditModal .modal-content').html('<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
            $.ajax({
                url: url,
                type: 'GET',
                success: function (res) {
                    $('#delayedCreditModal .modal-content').html(res);
                },
                error: function () {
                    $('#delayedCreditModal .modal-content').html('<div class="text-center text-danger p-5">Something went wrong!</div>');
                }
            });
        });
    </script>
@endpush
@endsection
