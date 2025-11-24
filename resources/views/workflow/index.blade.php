@extends('layouts.admin')

@section('page-title')
    {{ __('Manage Workflow') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Workflow') }}</li>
@endsection


@section('action-btn')
    <div class="float-end">
        @can('create warning')
            @if ($module == 'crm')
                <a href="{{ route('workflow.create') }}" title="{{ __('Create') }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-plus"></i>
                </a>
            @elseif($module == 'hrm')
                <a href="{{ route('workflow_hrm') }}" title="{{ __('Create') }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-plus"></i>
                </a>
            @elseif($module == 'project')
                <a href="{{ route('workflow_project') }}" title="{{ __('Create') }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-plus"></i>
                </a>
            @elseif($module == 'accounts')
                <a href="{{ route('workflow_account') }}" title="{{ __('Create') }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-plus"></i>
                </a>
            @endif
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Branch') }}</th>
                                    <th>{{ __('Training Type') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th width="200px">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="font-style">
                                @foreach ($workflows as $workflow)
                                    <tr>
                                        <td>{{ !empty($workflow) ? $workflow->name : '' }}</td>
                                        <td>{{ !empty($workflow) ? $workflow->module : '' }}</td>
                                        </td>
                                        <td>
                                            @if ($workflow->status == 0)
                                                <span class="status_badge badge bg-warning p-2 px-3 rounded"
                                                    style="cursor: pointer;"
                                                    onclick="document.getElementById('status-form-{{ $workflow->id }}-activate').submit()">
                                                    {{ __('InActive') }}
                                                </span>

                                                <form id="status-form-{{ $workflow->id }}-activate" method="POST"
                                                    action="{{ route('workflows.updateStatus', $workflow->id) }}"
                                                    style="display: none;">
                                                    @csrf
                                                    <input type="hidden" name="status" value="1">
                                                </form>
                                            @elseif($workflow->status == 1)
                                                <span class="status_badge badge bg-primary p-2 px-3 rounded"
                                                    style="cursor: pointer;"
                                                    onclick="document.getElementById('status-form-{{ $workflow->id }}-deactivate').submit()">
                                                    {{ __('Active') }}
                                                </span>

                                                <form id="status-form-{{ $workflow->id }}-deactivate" method="POST"
                                                    action="{{ route('workflows.updateStatus', $workflow->id) }}"
                                                    style="display: none;">
                                                    @csrf
                                                    <input type="hidden" name="status" value="0">
                                                </form>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="action-btn bg-info ms-2">
                                                <a href="{{ route('workflow.show', \Illuminate\Support\Facades\Crypt::encrypt($workflow->id)) }}"
                                                    class="mx-3 btn btn-sm  align-items-center" data-bs-toggle="tooltip"
                                                    title="{{ __('View') }}"
                                                    data-original-title="{{ __('View Detail') }}">
                                                    <i class="ti ti-eye text-white"></i>
                                                </a>

                                            </div>
                                            <div class="action-btn bg-primary ms-2">
                                                <a href="{{ route('workflow.edit', $workflow->id) }}"
                                                    title="{{ __('Edit') }}"
                                                    class="mx-3 btn btn-sm  align-items-center">
                                                    <i class="ti ti-pencil text-white"></i></a>
                                            </div>
                                            <div class="action-btn bg-danger ms-2">
                                                {!! Form::open([
                                                    'method' => 'DELETE',
                                                    'route' => ['workflow.destroy', $workflow->id],
                                                    'id' => 'delete-form-' . $workflow->id,
                                                ]) !!}
                                                <a href="#" class="mx-3 btn btn-sm  align-items-center bs-pass-para"
                                                    data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                    data-confirm-yes="document.getElementById('delete-form-{{ $workflow->id }}').submit();"
                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                    data-original-title="{{ __('Delete') }}">
                                                    <i class="ti ti-trash text-white"></i></a>
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
        </div>
    </div>
@endsection
