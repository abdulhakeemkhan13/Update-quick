@extends('layouts.admin')
@section('page-title')
    {{__('Refund Receipts')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Refund Receipts')}}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="text-end mb-3">
                <a href="{{ route('refund-receipt.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i> {{__('Create Refund Receipt')}}
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2" id="filters"></div>
        </div>
    </div>
    {{-- //table --}}
    <div class="card">
        <div class="card-body table-border-style">
            <div class="table-responsive">
                <table class="table datatable">
                    <thead>
                        <tr>
                            <th>{{ __('Refund Receipt') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Issue Date') }}</th>
                            <th>{{ __('Amount Refunded') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th width="10%">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody> 
                        @foreach ($refundReceipts as $refundReceipt)
                            <tr>
                                <td class="Id">
                                    <a href="{{ route('refund-receipt.show', $refundReceipt->id) }}" class="btn btn-outline-primary">{{ Auth::user()->refundReceiptNumberFormat($refundReceipt->refund_receipt_id) }}</a>
                                </td>
                                <td>{{ $refundReceipt->customer->name ?? 'N/A' }}</td>
                                <td>{{ \Auth::user()->dateFormat($refundReceipt->issue_date) }}</td>
                                <td>{{ \Auth::user()->priceFormat($refundReceipt->getTotal()) }}</td>
                                <td>
                                    @if($refundReceipt->status == 0)
                                        <span class="badge bg-secondary">{{ __('Draft') }}</span>
                                    @elseif($refundReceipt->status == 1)
                                        <span class="badge bg-info">{{ __('Sent') }}</span>
                                    @elseif($refundReceipt->status == 2)
                                        <span class="badge bg-success">{{ __('Approved') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-btn bg-primary ms-2">
                                        <a href="{{ route('refund-receipt.show', $refundReceipt->id) }}" class="mx-3 btn btn-sm  align-items-center" data-bs-toggle="tooltip" title="{{__('View')}}" data-original-title="{{__('View')}}">
                                            <i class="ti ti-eye text-white"></i>
                                        </a>
                                    </div>
                                    <div class="action-btn bg-info ms-2">
                                        <a href="{{ route('refund-receipt.edit', $refundReceipt->id) }}" class="mx-3 btn btn-sm  align-items-center" data-bs-toggle="tooltip" title="{{__('Edit')}}" data-original-title="{{__('Edit')}}">
                                            <i class="ti ti-pencil text-white"></i>
                                        </a>
                                    </div>
                                    <div class="action-btn bg-danger ms-2">
                                        {!! Form::open(['method' => 'DELETE', 'route' => ['refund-receipt.destroy', $refundReceipt->id],'id'=>'delete-form-'.$refundReceipt->id]) !!}
                                            <a href="#" class="mx-3 btn btn-sm  align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{__('Delete')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$refundReceipt->id}}').submit();">
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
@endsection
