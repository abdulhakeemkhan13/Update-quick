@extends('layouts.admin')
@section('page-title')
    {{__('Sales Receipts')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Sales Receipts')}}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="text-end mb-3">
                <a href="{{ route('sales.reciepts.create', 0) }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i> {{__('Create Sales Receipt')}}
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
                            <th>{{ __('Sales Receipt') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Issue Date') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th width="10%">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody> 
                        @foreach ($salesReceipts as $salesReceipt)
                            <tr>
                                <td class="Id">
                                    <a href="{{ route('sales-receipt.show', $salesReceipt->id) }}" class="btn btn-outline-primary">{{ AUth::user()->salesReceiptNumberFormat($salesReceipt->sales_receipt_id) }}</a>
                                </td>
                                <td>{{ $salesReceipt->customer->name }}</td>
                                <td>{{ \Auth::user()->dateFormat($salesReceipt->issue_date) }}</td>
                                <td>{{ \Auth::user()->priceFormat($salesReceipt->getTotal()) }}</td>
                                <td>{{ $salesReceipt->status }}</td>
                                <td>
                                    <div class="action-btn bg-primary ms-2">
                                        <a href="{{ route('sales-receipt.show', $salesReceipt->id) }}" class="mx-3 btn btn-sm  align-items-center" data-bs-toggle="tooltip" title="{{__('View')}}" data-original-title="{{__('View')}}">
                                            <i class="ti ti-eye text-white"></i>
                                        </a>
                                    </div>
                                    <div class="action-btn bg-info ms-2">
                                        <a href="{{ route('sales-receipt.edit', $salesReceipt->id) }}" class="mx-3 btn btn-sm  align-items-center" data-bs-toggle="tooltip" title="{{__('Edit')}}" data-original-title="{{__('Edit')}}">
                                            <i class="ti ti-pencil text-white"></i>
                                        </a>
                                    </div>
                                    <div class="action-btn bg-danger ms-2">
                                        {!! Form::open(['method' => 'DELETE', 'route' => ['sales-receipt.destroy', $salesReceipt->id],'id'=>'delete-form-'.$salesReceipt->id]) !!}
                                            <a href="#" class="mx-3 btn btn-sm  align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{__('Delete')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$salesReceipt->id}}').submit();">
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
