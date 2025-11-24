@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Leads') }} @if ($pipeline)
        - {{ $pipeline->name }}
    @endif
@endsection

@push('css-page')
    <link rel="stylesheet" href="{{ asset('css/summernote/summernote-bs4.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/dragula.min.css') }}" id="main-style-link">
@endpush

@push('script-page')
    <script src="{{ asset('assets/js/plugins/choices.min.js') }}"></script>
    <script src="{{ asset('css/summernote/summernote-bs4.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/dragula.min.js') }}"></script>
    <script>
        ! function(a) {
            "use strict";
            var t = function() {
                this.$body = a("body")
            };
            t.prototype.init = function() {
                a('[data-plugin="dragula"]').each(function() {
                    var t = a(this).data("containers"),
                        n = [];
                    if (t)
                        for (var i = 0; i < t.length; i++) n.push(a("#" + t[i])[0]);
                    else n = [a(this)[0]];
                    var r = a(this).data("handleclass");
                    r ? dragula(n, {
                        moves: function(a, t, n) {
                            return n.classList.contains(r)
                        }
                    }) : dragula(n).on('drop', function(el, target, source, sibling) {

                        var order = [];
                        $("#" + target.id + " > div").each(function() {
                            order[$(this).index()] = $(this).attr('data-id');
                        });

                        var id = $(el).attr('data-id');

                        var old_status = $("#" + source.id).data('status');
                        var new_status = $("#" + target.id).data('status');
                        var stage_id = $(target).attr('data-id');
                        var pipeline_id = '{{ $pipeline->id }}';

                        $("#" + source.id).parent().find('.count').text($("#" + source.id + " > div")
                            .length);
                        $("#" + target.id).parent().find('.count').text($("#" + target.id + " > div")
                            .length);
                        $.ajax({
                            url: '{{ route('leads.order') }}',
                            type: 'POST',
                            data: {
                                lead_id: id,
                                stage_id: stage_id,
                                order: order,
                                new_status: new_status,
                                old_status: old_status,
                                pipeline_id: pipeline_id,
                                "_token": $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(data) {},
                            error: function(data) {
                                data = data.responseJSON;
                                show_toastr('error', data.error, 'error')
                            }
                        });
                    });
                })
            }, a.Dragula = new t, a.Dragula.Constructor = t
        }(window.jQuery),
        function(a) {
            "use strict";

            a.Dragula.init()

        }(window.jQuery);
    </script>
    <script>
        $(document).on("change", "#default_pipeline_id", function() {
            $('#change-pipeline').submit();
        });
        $(document).ready(function() {
            $($("#label")).each(function(index, element) {
                var id = $(element).attr('id');
                var multipleCancelButton = new Choices(
                    '#' + id, {
                        removeItemButton: true,
                    }
                );
            });
        });
    </script>
    <script>
        function removeFilter() {
            window.location.href = "{{ route('leads.index') }}";
        }
    </script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Lead') }}</li>
@endsection
@section('action-btn')
    <div class="float-end">
        {{ Form::open(['route' => 'deals.change.pipeline', 'id' => 'change-pipeline', 'class' => 'btn btn-sm']) }}
        {{ Form::select('default_pipeline_id', $pipelines, $pipeline->id, ['class' => 'form-control select me-2', 'id' => 'default_pipeline_id']) }}
        {{ Form::close() }}

        <a href="{{ route('leads.list') }}" data-size="lg" data-bs-toggle="tooltip" title="{{ __('List View') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-list"></i>
        </a>
        <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import') }}"
            data-url="{{ route('leads.file.import', ['id' => $pipeline->id]) }}" data-ajax-popup="true"
            data-title="{{ __('Import Lead CSV file') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-file-import"></i>
        </a>
        <a href="{{ route('leads.export', ['id' => $pipeline->id]) }}" data-bs-toggle="tooltip"
            title="{{ __('Export') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-file-export"></i>
        </a>
        <a href="#" data-size="lg" data-url="{{ route('leads.filtermodal') }}" data-ajax-popup="true"
            data-bs-toggle="tooltip" title="{{ __('Apply Filter') }}" data-title="{{ __('Filter Leads') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-filter"></i>
        </a>
        @if($filter == true)
        <a href="#" id="remove-filter" class="btn btn-sm btn-warning" onclick="removeFilter()" data-bs-toggle="tooltip" title="{{ __('Remove Filter') }}">
            <i class="ti ti-filter"></i>
        </a>
        @endif
        <a href="#" data-size="lg" data-url="{{ route('leads.create') }}" data-ajax-popup="true"
            data-bs-toggle="tooltip" title="{{ __('Create New Lead') }}" data-title="{{ __('Create Lead') }}"
            class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>
    </div>
@endsection

@section('content')
    <?php
    $selectedLabels = isset($_GET['labels']) ? $_GET['labels'] : [];
    ?>
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2 " id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body" style="padding-top: 12px;">
                        {{ Form::open(['route' => ['leads.index'], 'method' => 'GET', 'id' => 'customer_submit']) }}
                        <div class="row d-flex align-items-center justify-content-end">
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 mr-2">
                                <div class="btn-box">
                                    {{ Form::label('date', __('Date'), ['class' => 'form-label']) }}
                                    {{ Form::date('date', isset($_GET['date']) ? $_GET['date'] : '', ['class' => 'form-control month-btn', 'id' => 'pc-daterangepicker-1']) }}
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 mr-2">
                                <div class="btn-box">
                                    {{ Form::label('label', __('Label'), ['class' => 'form-label']) }}
                                    <select class="select form-control form-select" multiple="multiple" name="labels[]"
                                        id="label">
                                        @foreach ($labels as $label)
                                            <option value="{{ @$label->id }}"
                                                {{ in_array($label->id, $selectedLabels) ? 'selected' : '' }}>
                                                {{ $label->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                <div class="btn-box">
                                    {{ Form::label('search', __('Search'), ['class' => 'form-label']) }}
                                    <input type="search" name="search" class="form-control" id="search"
                                        value="{{ isset($_GET['search']) ? $_GET['search'] : '' }}"
                                        placeholder="Search from name,subject,phone">
                                </div>
                            </div>
                            <div class="col-auto float-end ms-2 mt-4">
                                <a href="#" class="btn btn-sm btn-primary"
                                    onclick="document.getElementById('customer_submit').submit(); return false;"
                                    data-toggle="tooltip" data-original-title="{{ __('apply') }}">
                                    <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                </a>
                                <a href="{{ route('leads.index') }}" class="btn btn-sm btn-danger" data-toggle="tooltip"
                                    data-original-title="{{ __('Reset') }}">
                                    <span class="btn-inner--icon"><i class="ti ti-trash-off text-white-off"></i></span>
                                </a>
                            </div>
                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12 mb-6">

    </div>
    <div class="row">
        <div class="col-sm-12">
            @php
                $lead_stages = $pipeline->leadStages;
                $json = [];
                foreach ($lead_stages as $lead_stage) {
                    $json[] = 'task-list-' . $lead_stage->id;
                }
            @endphp
            <div class="row kanban-wrapper horizontal-scroll-cards" data-containers='{!! json_encode($json) !!}'
                data-plugin="dragula">
                @foreach ($lead_stages as $lead_stage)
                    {{-- @php($lead = $lead_stage->lead()) --}}
                    @php($leads = $lead_stage->search($query, $pipeline->id, $lead_stage->id))
                    <div class="col">
                        <div class="card">
                            <div class="card-header">
                                <div class="float-end">
                                    <span class="btn btn-sm btn-primary btn-icon count">
                                        {{ count($leads) }}
                                    </span>
                                </div>
                                <h4 class="mb-0">{{ $lead_stage->name }}</h4>
                            </div>
                            <div class="card-body kanban-box" id="task-list-{{ $lead_stage->id }}"
                                data-id="{{ $lead_stage->id }}">
                                @foreach ($leads as $lead)
                                    <div class="card" data-id="{{ $lead->id }}">
                                        <div class="pt-3 ps-3">
                                            @php($labels = $lead->labels())
                                            @if ($labels)
                                                @foreach ($labels as $label)
                                                    <div class="badge-xs badge bg-{{ $label->color }} p-2 px-3 rounded">
                                                        {{ $label->name }}</div>
                                                @endforeach
                                            @endif
                                            @if ($lead->updated_at < \Carbon\Carbon::now()->subHours(96))
                                                <div class="badge-xs badge bg-primary p-0 rounded float-end "
                                                    style="margin: 4px 12px 0px 0px;"><a href="#"
                                                        data-id="{{ $lead->id }}" data-type="Follow up"
                                                        data-bs-toggle="tooltip"
                                                        title="{{ __('No Activity for the Last 96 Hours ') }}"
                                                        class="btn btn-sm btn-primary rounded">
                                                        Follow up
                                                    </a></div>
                                            @endif
                                        </div>
                                        <div class="card-header border-0 pb-0 position-relative">
                                            <h5><a
                                                    href="@can('view lead')@if ($lead->is_active){{ route('leads.show', $lead->id) }}@else#@endif @else#@endcan">{{ $lead->name }}</a>
                                            </h5>
                                            <div class="card-header-right">
                                                @if (Auth::user()->type != 'client')
                                                    <div class="btn-group card-option">
                                                        <button type="button" class="btn dropdown-toggle"
                                                            data-bs-toggle="dropdown" aria-haspopup="true"
                                                            aria-expanded="false">
                                                            <i class="ti ti-dots-vertical"></i>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-end">
                                                            @can('edit lead')
                                                                <a href="#!" data-size="md"
                                                                    data-url="{{ URL::to('leads/' . $lead->id . '/labels') }}"
                                                                    data-ajax-popup="true" class="dropdown-item"
                                                                    data-bs-original-title="{{ __('Add Labels') }}">
                                                                    <i class="ti ti-bookmark"></i>
                                                                    <span>{{ __('Labels') }}</span>
                                                                </a>
                                                                <a href="#!" data-size="sm"
                                                                    data-url="{{ URL::to('leads/' . $lead->id . '/stage') }}"
                                                                    data-ajax-popup="true"
                                                                    data-bs-original-title="{{ __('Change Stage of this lead') }}"
                                                                    class="dropdown-item">
                                                                    <i class="ti ti-arrow-right"></i>
                                                                    <span>{{ __('Move Stage') }}</span>
                                                                </a>
                                                                <a href="#!" data-size="lg"
                                                                    data-url="{{ URL::to('leads/' . $lead->id . '/edit') }}"
                                                                    data-ajax-popup="true" class="dropdown-item"
                                                                    data-bs-original-title="{{ __('Edit Lead') }}">
                                                                    <i class="ti ti-pencil"></i>
                                                                    <span>{{ __('Edit') }}</span>
                                                                </a>
                                                            @endcan
                                                            @can('delete lead')
                                                                {!! Form::open([
                                                                    'method' => 'DELETE',
                                                                    'route' => ['leads.destroy', $lead->id],
                                                                    'id' => 'delete-form-' . $lead->id,
                                                                ]) !!}
                                                                <a href="#!" class="dropdown-item bs-pass-para">
                                                                    <i class="ti ti-archive"></i>
                                                                    <span> {{ __('Delete') }} </span>
                                                                </a>
                                                                {!! Form::close() !!}
                                                            @endcan


                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <?php
                                        $products = $lead->products();
                                        $sources = $lead->sources();
                                        ?>
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <ul class="list-inline mb-0">

                                                    <li class="list-inline-item d-inline-flex align-items-center"
                                                        data-bs-toggle="tooltip" title="{{ __('Product') }}">
                                                        <i class="f-16 text-primary ti ti-shopping-cart"></i>
                                                        {{ count($products) }}
                                                    </li>

                                                    <li class="list-inline-item d-inline-flex align-items-center"
                                                        data-bs-toggle="tooltip" title="{{ __('Source') }}">
                                                        <i
                                                            class="f-16 text-primary ti ti-social"></i>{{ count($sources) }}
                                                    </li>
                                                </ul>
                                                <div class="user-group">
                                                    @foreach ($lead->users as $user)
                                                        <img src="@if ($user->avatar) {{ asset('/storage/uploads/avatar/' . $user->avatar) }} @else {{ asset('storage/uploads/avatar/avatar.png') }} @endif"
                                                            alt="image" data-bs-toggle="tooltip"
                                                            title="{{ $user->name }}">
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
