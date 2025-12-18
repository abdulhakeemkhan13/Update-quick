@extends('layouts.admin')
@section('page-title')
    {{__('Manage Bill Payments')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Bill Payments')}}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create payment')
            <a href="#" data-url="{{ route('payment.create') }}" data-ajax-popup="true" data-bs-toggle="tooltip" data-size="lg"
                data-title="{{__('Create New Payment')}}" title="{{__('Create')}}" class="btn btn-sm btn-primary">
                {{__('Create New Payment')}}
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection

@section('content')
    {{-- MY APPS Sidebar (Fixed Position) --}}
    @include('partials.admin.allApps-subMenu-Sidebar', [
        'activeSection' => 'expenses',
        'activeItem' => 'bill_payments',
    ])

    @include('expense.expense-tabs')
    <div class="dropdown mt-4 mb-2">
        <button class="btn btn-outline-primary dropdown-toggle d-flex align-items-center" type="button" id="filtersDropdown"
            data-bs-toggle="dropdown" aria-expanded="false">
            <i class="ti ti-filter me-1"></i> {{ __('Filters') }}
        </button>

        <div class="dropdown-menu p-3" style="min-width: 350px;">
            <div class="card shadow-none border-0">
                <div class="card-body p-0">
                    {{ Form::open(['route' => ['payment.index'], 'method' => 'GET', 'id' => 'payment_form']) }}
                    <div class="row">

                        {{-- Date --}}
                        <div class="col-12 mb-3">
                            {{ Form::label('date', __('Date'), ['class' => 'form-label']) }}
                            {{ Form::date('date', request('date'), ['class' => 'form-control', 'id' => 'pc-daterangepicker-1']) }}
                        </div>

                        {{-- Account --}}
                        <div class="col-12 mb-3">
                            {{ Form::label('account', __('Account'), ['class' => 'form-label']) }}
                            {{ Form::select('account', $account, request('account'), ['class' => 'form-control select', 'id' => 'choices-multiple']) }}
                        </div>

                        {{-- Vendor --}}
                        <div class="col-12 mb-3">
                            {{ Form::label('vender', __('Vendor'), ['class' => 'form-label']) }}
                            {{ Form::select('vender', $vender, request('vender'), ['class' => 'form-control select', 'id' => 'choices-multiple1']) }}
                        </div>

                        {{-- Category --}}
                        <div class="col-12 mb-3">
                            {{ Form::label('category', __('Category'), ['class' => 'form-label']) }}
                            {{ Form::select('category', $category, request('category'), ['class' => 'form-control select', 'id' => 'choices-multiple2']) }}
                        </div>

                        {{-- Buttons --}}
                        <div class="col-12 d-flex justify-content-between">
                            <a href="{{ route('payment.index') }}" class="btn btn-outline-secondary btn-sm"
                                data-bs-toggle="tooltip" title="{{ __('Reset') }}">
                                <i class="ti ti-trash-off"></i> {{ __('Reset') }}
                            </a>

                            <button type="submit" class="btn btn-success btn-sm" data-bs-toggle="tooltip"
                                title="{{ __('Apply') }}">
                                <i class="ti ti-search"></i> {{ __('Apply') }}
                            </button>
                        </div>

                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{__('Date')}}</th>
                                    <th>{{__('Amount')}}</th>
                                    <th>{{__('Account')}}</th>
                                    {{-- <th> {{__('Chart Of Account')}}</th>--}}
                                    <th>{{__('Vendor')}}</th>
                                    <th>{{__('Category')}}</th>
                                    <th>{{__('Reference')}}</th>
                                    <th>{{__('Description')}}</th>
                                    <th>{{__('Payment Receipt')}}</th>
                                    @if(Gate::check('edit payment') || Gate::check('delete payment'))
                                        <th>{{__('Action')}}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                {{-- @dd($billpayments) --}}
                                @php
                                    $paymentpath = \App\Models\Utility::get_file('uploads/payment');
                                @endphp

                                @foreach ($billpayments as $payment)
                                    <tr class="font-style">
                                        <td>{{  Auth::user()->dateFormat($payment->date)}}</td>
                                        <td>{{  Auth::user()->priceFormat($payment->amount)}}</td>
                                        <td>{{ !empty($payment->bankAccount) ? $payment->bankAccount->bank_name . ' ' . $payment->bankAccount->holder_name : ''}}
                                        </td>
                                        {{-- <td>{{ !empty($payment->chartAccount)?$payment->chartAccount->name :'-' }}</td>--}}
                                        <td>{{  !empty($payment->vender) ? $payment->vender->name : '-'}}</td>
                                        <td>{{  !empty($payment->category) ? $payment->category->name : '-'}}</td>
                                        <td>{{  !empty($payment->reference) ? $payment->reference : '-'}}</td>
                                        <td>{{  !empty($payment->description) ? $payment->description : '-'}}</td>
                                        <td>
                                            @if(!empty($payment->add_receipt))
                                                <a class="action-btn bg-primary ms-2 btn btn-sm align-items-center"
                                                    href="{{ $paymentpath . '/' . $payment->add_receipt }}" download="">
                                                    <i class="ti ti-download text-white"></i>
                                                </a>
                                                <a href="{{ $paymentpath . '/' . $payment->add_receipt }}"
                                                    class="action-btn bg-secondary ms-2 mx-3 btn btn-sm align-items-center"
                                                    data-bs-toggle="tooltip" title="{{__('Download')}}" target="_blank"><span
                                                        class="btn-inner--icon"><i
                                                            class="ti ti-crosshair text-white"></i></span></a>
                                            @else
                                                -
                                            @endif

                                        </td>
                                        @if(Gate::check('edit revenue') || Gate::check('delete revenue'))
                                            <td class="action">
                                                @can('edit payment')
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                            data-url="{{ route('payment.edit', $payment->id) }}" data-ajax-popup="true"
                                                            data-title="{{__('Edit Payment')}}" data-size="lg" data-bs-toggle="tooltip"
                                                            title="{{__('Edit')}}" data-original-title="{{__('Edit')}}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('delete payment')
                                                    <div class="action-btn bg-danger ms-2">
                                                        {!! Form::open(['method' => 'DELETE', 'route' => ['payment.destroy', $payment->id], 'id' => 'delete-form-' . $payment->id]) !!}
                                                        <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                            data-bs-toggle="tooltip" data-original-title="{{__('Delete')}}"
                                                            title="{{__('Delete')}}"
                                                            data-confirm="{{__('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?')}}"
                                                            data-confirm-yes="document.getElementById('delete-form-{{$payment->id}}').submit();">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </a>
                                                        {!! Form::close() !!}
                                                    </div>
                                                @endcan
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection