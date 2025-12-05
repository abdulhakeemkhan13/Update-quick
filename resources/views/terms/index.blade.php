@extends('layouts.admin')
@section('page-title')
    {{ __('Payment Terms') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a >{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Payment Terms') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create customer')
            <a href="#" data-url="{{ route('payment-terms.create') }}" data-ajax-popup="true"
                data-title="{{ __('New Term') }}" data-size="md" class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i> {{ __('New Term') }}
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Details') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th width="200px">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($paymentTerms as $term)
                                    <tr>
                                        <td>{{ $term->name }}</td>
                                        <td>
                                            @if ($term->type === 'fixed_days')
                                                {{ __('Fixed Days') }}
                                            @elseif ($term->type === 'day_of_month')
                                                {{ __('Day of Month') }}
                                            @else
                                                {{ __('Next Month If Within') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($term->type === 'fixed_days')
                                                {{ $term->due_in_days }} {{ __('days') }}
                                            @elseif ($term->type === 'day_of_month')
                                                {{ __('Day') }} {{ $term->day_of_month }}
                                            @else
                                                {{ __('Day') }} {{ $term->day_of_month }}, {{ __('within') }} {{ $term->cutoff_days }} {{ __('days') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($term->is_active)
                                                <span class="badge bg-success">{{ __('Active') }}</span>
                                            @else
                                                <span class="badge bg-danger">{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td class="Action">
                                            <span>
                                                @can('edit customer')
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="#" data-url="{{ route('payment-terms.edit', $term->id) }}"
                                                            data-ajax-popup="true" data-title="{{ __('Edit Term') }}"
                                                            data-size="md" class="mx-3 btn btn-sm d-inline-flex align-items-center"
                                                            data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('delete customer')
                                                    <div class="action-btn bg-danger ms-2">
                                                        {!! Form::open(['method' => 'DELETE', 'route' => ['payment-terms.destroy', $term->id], 'id' => 'delete-form-' . $term->id]) !!}
                                                        <a href="#" class="mx-3 btn btn-sm d-inline-flex align-items-center show_confirm"
                                                            data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </a>
                                                        {!! Form::close() !!}
                                                    </div>
                                                @endcan
                                            </span>
                                        </td>
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
