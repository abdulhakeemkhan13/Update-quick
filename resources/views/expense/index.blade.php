@extends('layouts.admin')
@section('page-title')
    {{__('Manage Expenses')}}
@endsection
@push('script-page')
    <script>
        $('.copy_link').click(function (e) {
            e.preventDefault();
            var copyText = $(this).attr('href');

            document.addEventListener('copy', function (e) {
                e.clipboardData.setData('text/plain', copyText);
                e.preventDefault();
            }, true);

            document.execCommand('copy');
            show_toastr('success', 'Url copied to clipboard', 'success');
        });
    </script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Expense')}}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create bill')

            <button class="btn btn-sm btn-primary openChecksModal" data-url="{{ route('expense.create', 0) }}" data-bs-toggle="tooltip" title="{{__('Create Expense')}}">
                {{ __('Create Expense') }}

           {{-- <a href="#" data-url="{{ route('expense.create',0) }}"  class="btn btn-sm btn-primary"
                data-ajax-popup="true" data-size="fullscreen"
                data-bs-toggle="tooltip" title="Create Expense">
                {{__('Create Expense')}} --}}

                <i class="ti ti-plus"></i>
            </button>
        @endcan
        <a href="{{ route('timeActivity.create') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{__('Create Time Activity')}}">
            {{__('Create Time Activity')}}
            <i class="ti ti-plus"></i>
        </a>
        <button class="btn btn-sm btn-primary openChecksModal" data-url="{{ route('checks.create') }}" data-bs-toggle="tooltip" title="{{__('Create Checks')}}">
            {{__('Create Checks')}}
            <i class="ti ti-plus"></i>
        </button>
    </div>
@endsection


@section('content')
    {{-- tabs --}}
    @include('expense.expense-tabs')
    <div class="modal fade" id="ajaxModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-fullscreen">
            <div class="modal-content">
            </div>
        </div>


    </div>

@push('script-page')
    <script>
        $(document).on('click', '.openChecksModal', function (e) {
            e.preventDefault();

            var url = $(this).data('url');

            $('#ajaxModal').modal('show');

            $.ajax({
                url: url,
                type: 'GET',
                success: function (res) {
                    $('#ajaxModal .modal-content').html(res);
                },
                error: function () {
                    alert('Something went wrong!');
                }
            });
        });
    </script>
@endpush
{{-- Filters Dropdown --}}
<div class="dropdown mt-4 mb-2">
    <button class="btn btn-outline-primary dropdown-toggle d-flex align-items-center" type="button"
        id="filtersDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="ti ti-filter me-1"></i> {{ __('Filters') }}
    </button>

    <div class="dropdown-menu p-3" style="min-width: 350px;">
        <div class="card shadow-none border-0">
            <div class="card-body p-0">
                {{ Form::open(['route' => ['expense.index'], 'method' => 'GET', 'id' => 'frm_submit']) }}
                <div class="row">

                    {{-- Payment Date --}}
                    <div class="col-12 mb-3">
                        {{ Form::label('bill_date', __('Payment Date'), ['class' => 'form-label']) }}
                        {{ Form::text('bill_date', request('bill_date'), [
                            'class' => 'form-control month-btn',
                            'id' => 'pc-daterangepicker-1',
                            'readonly',
                        ]) }}
                    </div>

                    {{-- Category --}}
                    <div class="col-12 mb-3">
                        {{ Form::label('category', __('Category'), ['class' => 'form-label']) }}
                        {{ Form::select('category', $category, request('category'), ['class' => 'form-control select']) }}
                    </div>

                    {{-- Buttons --}}
                    <div class="col-12 d-flex justify-content-between">
                        <a href="{{ route('expense.index') }}" 
                           class="btn btn-outline-secondary btn-sm"
                           data-bs-toggle="tooltip" 
                           title="{{ __('Reset') }}">
                            <i class="ti ti-trash-off"></i> {{ __('Reset') }}
                        </a>

                        <button type="submit" 
                                class="btn btn-success btn-sm"
                                data-bs-toggle="tooltip" 
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
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                            <tr>
                                <th class="text-center"> {{__('Expense')}}</th>
                                <th class="text-center"> {{__('Vendor')}}</th>
                                <th class="text-center">{{ __('Paid Amount') }}</th>
                                <th class="text-center">{{ __('Due Amount') }}</th>
                                <th class="text-center"> {{__('Date')}}</th>
                                <th class="text-center">{{__('Status')}}</th>
                                @if(Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                    <th width="10%"> {{__('Action')}}</th>
                                @endif
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($expenses as $expense)
                                @php
                                        // compute amounts using model methods
                                        $expenseTotal = (float) $expense->getTotal();
                                        $expenseDue = (float) $expense->getDue();
                                        $expensePaid = $expenseTotal - $expenseDue - $expense->billTotalDebitNote();
                                @endphp

                                <tr>
                                    <td class="Id">
                                        <a href="{{ route('expense.show',\Crypt::encrypt($expense->id)) }}" class="btn btn-outline-primary">{{ AUth::user()->expenseNumberFormat($expense->bill_id) }}</a>
                                    </td>
                                    <td class="text-center align-middle">
                                            {{ optional($expense->vender)->name ?? '-' }}
                                        </td>
                                    <td class="text-center align-middle">{{ \Auth::user()->priceFormat($expensePaid) }}</td>
                                    <td class="text-center align-middle">{{ \Auth::user()->priceFormat($expenseDue) }}</td>
                                    <td class="text-center align-middle">{{ Auth::user()->dateFormat($expense->bill_date) }}</td>
                                    <td class="text-center align-middle">
                                        {{-- //colorful --}}
                                        @if($expense->status == 0)
                                            <span class="status_badge badge bg-secondary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$expense->status]) }}</span>
                                        @elseif($expense->status == 1)
                                            <span class="status_badge badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$expense->status]) }}</span>
                                        @elseif($expense->status == 2)
                                            <span class="status_badge badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$expense->status]) }}</span>
                                        @elseif($expense->status == 3)
                                            <span class="status_badge badge bg-info p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$expense->status]) }}</span>
                                        @elseif($expense->status == 4)
                                            <span class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$expense->status]) }}</span>
                                        @elseif($expense->status == 5)
                                            <span class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$expense->status]) }}</span>
                                        @elseif($expense->status == 6)
                                            <span class="status_badge badge bg-success p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$expense->status]) }}</span>
                                        @elseif($expense->status == 7)
                                            <span class="status_badge badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\Invoice::$statues[$expense->status]) }}</span>
                                        @endif
                                    </td>
                                    @if(Gate::check('edit bill') || Gate::check('delete bill') || Gate::check('show bill'))
                                        <td class="Action text-center align-middle" >
                                            <span>
                                                {{-- // request for approaval --}}
                                                @if($expense->status == 0 || $expense->status == 7)
                                                    <div class="action-btn bg-info ms-2">
                                                        <a href="{{ route('expense.request.approval', $expense->id) }}" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('Send for Approval')}}" data-original-title="{{__('Send for Approval')}}">
                                                            <i class="ti ti-send text-white"></i>
                                                        </a>
                                                    </div>
                                                @endif
                                                @can('show bill')
                                                    <div class="action-btn bg-info ms-2">
                                                        <a href="{{ route('expense.show',\Crypt::encrypt($expense->id)) }}" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('Show')}}" data-original-title="{{__('Detail')}}">
                                                            <i class="ti ti-eye text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('edit bill')
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="#" data-url="{{ route('expense.edit',\Crypt::encrypt($expense->id)) }}"  class="mx-3 btn btn-sm align-items-center"
                                                            data-ajax-popup="true" data-size="fullscreen"
                                                            data-bs-toggle="tooltip" title="Edit Expense">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                    {{-- <div class="action-btn bg-primary ms-2">
                                                        <a href="{{ route('expense.edit',\Crypt::encrypt($expense->id)) }}" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="Edit" data-original-title="{{__('Edit')}}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div> --}}
                                                @endcan
                                                @can('delete bill')
                                                    <div class="action-btn bg-danger ms-2">
                                                        {!! Form::open(['method' => 'DELETE', 'route' => ['expense.destroy', $expense->id],'class'=>'delete-form-btn','id'=>'delete-form-'.$expense->id]) !!}
                                                        <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{__('Delete')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$expense->id}}').submit();">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </a>
                                                        {!! Form::close() !!}
                                                    </div>
                                                @endcan
                                            </span>
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

