@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Chart of Accounts') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Chart of Account') }}</li>
@endsection
@push('script-page')
    <script>
        $(document).on('change', '#sub_type', function() {
            $('.acc_check').removeClass('d-none');
            var type = $(this).val();
            $.ajax({
                url: '{{ route('charofAccount.subType') }}',
                type: 'POST',
                data: {
                    "type": type,
                    "_token": "{{ csrf_token() }}",
                },
                success: function(data) {
                    $('#parent').empty();
                    $.each(data, function(key, value) {
                        $('#parent').append('<option value="' + key + '">' + value +
                            '</option>');
                    });
                }
            });
        });
        $(document).on('click', '#account', function() {
            const element = $('#account').is(':checked');
            $('.acc_type').addClass('d-none');
            if (element == true) {
                $('.acc_type').removeClass('d-none');
            } else {
                $('.acc_type').addClass('d-none');
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            callback();

            function callback() {
                var start_date = $(".startDate").val();
                var end_date = $(".endDate").val();


                $('.start_date').val(start_date);
                $('.end_date').val(end_date);
            }
        });
    </script>
@endpush

@section('action-btn')
    <div class="float-end">
        <a href="#" data-size="md"  data-bs-toggle="tooltip" title="{{__('Import')}}" data-url="{{ route('chart-of-account.file.import') }}" data-ajax-popup="true" data-title="{{__('Import Chart of Account CSV file')}}" class="btn btn-sm btn-primary">
            <i class="ti ti-file-import"></i>
        </a>
        <a href="{{route('chart-of-account.export')}}" data-bs-toggle="tooltip" title="{{__('Export')}}" class="btn btn-sm btn-primary">
            <i class="ti ti-file-export"></i>
        </a>
        @can('create chart of account')
            <a href="#" data-url="{{ route('chart-of-account.create') }}" data-bs-toggle="tooltip" title="{{ __('Create') }}"
                data-size="lg" data-ajax-popup="true" data-title="{{ __('Create New Account') }}"
                class="btn btn-sm btn-primary ">
                <span>New account</span><i style="margin-left: 5px;" class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection
@section('content')
    {{-- MY APPS Left Sidebar --}}
    @include('partials.admin.allApps-subMenu-Sidebar', [
        'activeSection' => 'accounting',
        'activeItem' => 'chart_of_accounts'
    ])

    {{-- tabs --}}
    @include('transaction.transactions-tabs')

    {{-- Chart of Account Date Filter Dropdown --}}
    <div class="dropdown card-header mt-4 mb-2">
        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="chartAccountDateFilterDropdown"
            data-bs-toggle="dropdown" aria-expanded="false">
            <i class="ti ti-calendar"></i> {{ __('Date Filter') }}
        </button>
        <div class="dropdown-menu p-3" style="min-width: 350px;">
            <div class="card shadow-none border-0">
                <div class="card-body p-0">
                    {{ Form::open(['route' => ['chart-of-account.index'], 'method' => 'GET', 'id' => 'report_bill_summary']) }}
                    <div class="row">

                        {{-- Start Date --}}
                        <div class="col-12 mb-3">
                            {{ Form::label('start_date', __('Start Date'), ['class' => 'form-label']) }}
                            {{ Form::date('start_date', $filter['startDateRange'], ['class' => 'form-control']) }}
                        </div>

                        {{-- End Date --}}
                        <div class="col-12 mb-3">
                            {{ Form::label('end_date', __('End Date'), ['class' => 'form-label']) }}
                            {{ Form::date('end_date', $filter['endDateRange'], ['class' => 'form-control']) }}
                        </div>

                        {{-- Buttons --}}
                        <div class="col-12 d-flex justify-content-between">
                            <a href="{{ route('chart-of-account.index') }}" class="btn btn-outline-secondary btn-sm"
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

    <div class="row justify-content-center">
        <div class="row card">
            @foreach ($chartAccounts as $type => $accounts)
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h6>{{ $type }}</h6>
                        </div>
                        <div class="card-body table-border-style">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th width="10%"> {{ __('Code') }}</th>
                                            <th width="30%"> {{ __('Name') }}</th>
                                            <th width="20%"> {{ __('Type') }}</th>
                                            <th width="20%"> {{ __('Parent Account Name') }}</th>
                                            <th width="20%"> {{ __('Balance') }}</th>
                                            <th width="10%"> {{ __('Status') }}</th>
                                            <th width="10%"> {{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($accounts as $account)
                                            @php
                                                $balance = 0;
                                                $totalDebit = 0;
                                                $totalCredit = 0;
                                                $totalBalance = App\Models\Utility::getAccountBalance(
                                                    $account->id,
                                                    $filter['startDateRange'],
                                                    $filter['endDateRange'],
                                                );
                                            @endphp

                                            <tr>
                                                <td>{{ $account->code }}</td>
                                                <td><a
                                                        href="{{ route('report.ledger', $account->id) }}?account={{ $account->id }}">{{ $account->name }}</a>
                                                </td>
                                                <td>{{ !empty($account->subType) ? $account->subType->name : '-' }}</td>
                                                <td>{{ !empty($account->parentAccount) ? $account->parentAccount->name : '-' }}
                                                </td>

                                                <td>
                                                    @if (!empty($totalBalance))
                                                        {{ \Auth::user()->priceFormat($totalBalance) }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($account->is_enabled == 1)
                                                        <span
                                                            class="badge bg-primary p-2 px-3 rounded">{{ __('Enabled') }}</span>
                                                    @else
                                                        <span
                                                            class="badge bg-danger p-2 px-3 rounded">{{ __('Disabled') }}</span>
                                                    @endif
                                                </td>
                                                <td class="Action">
                                                    <div class="action-btn bg-warning ms-2">
                                                        <a href="{{ route('report.ledger', $account->id) }}?account={{ $account->id }}"
                                                            class="mx-3 btn btn-sm align-items-center "
                                                            data-bs-toggle="tooltip"
                                                            title="{{ __('Transaction Summary') }}"
                                                            data-original-title="{{ __('Detail') }}">
                                                            <i class="ti ti-wave-sine text-white"></i>
                                                        </a>
                                                    </div>

                                                    @can('edit chart of account')
                                                        <div class="action-btn bg-primary ms-2">
                                                            <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                                data-url="{{ route('chart-of-account.edit', $account->id) }}"
                                                                data-ajax-popup="true" data-title="{{ __('Edit Account') }}"
                                                                data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                                data-original-title="{{ __('Edit') }}">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                    @can('delete chart of account')
                                                        <div class="action-btn bg-danger ms-2">
                                                            {!! Form::open([
                                                                'method' => 'DELETE',
                                                                'route' => ['chart-of-account.destroy', $account->id],
                                                                'id' => 'delete-form-' . $account->id,
                                                            ]) !!}
                                                            <a href="#"
                                                                class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                data-original-title="{{ __('Delete') }}"
                                                                data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                data-confirm-yes="document.getElementById('delete-form-{{ $account->id }}').submit();">
                                                                <i class="ti ti-trash text-white"></i>
                                                            </a>
                                                            {!! Form::close() !!}
                                                        </div>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endsection
