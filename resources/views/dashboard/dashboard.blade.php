@extends('layouts.admin')
@section('page-title')
    {{ __('Dashboard') }}
@endsection
@push('script-page')
    <script>
        $(document).ready(function() {
            get_data();
        });

        function get_data() {
            var calender_type = $('#calender_type :selected').val();
            $('#calendar').removeClass('local_calender');
            $('#calendar').removeClass('goggle_calender');
            if (calender_type == undefined) {
                $('#calendar').addClass('local_calender');
            }
            $('#calendar').addClass(calender_type);
            $.ajax({
                url: $("#event_dashboard").val() + "/event/get_event_data",
                method: "POST",
                data: {
                    "_token": "{{ csrf_token() }}",
                    'calender_type': calender_type
                },
                success: function(data) {
                    (function() {
                        var etitle;
                        var etype;
                        var etypeclass;
                        var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                            headerToolbar: {
                                left: 'prev,next today',
                                center: 'title',
                                right: 'timeGridDay,timeGridWeek,dayGridMonth'
                            },
                            buttonText: {
                                timeGridDay: "{{ __('Day') }}",
                                timeGridWeek: "{{ __('Week') }}",
                                dayGridMonth: "{{ __('Month') }}"
                            },
                            slotLabelFormat: {
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: false,
                            },
                            themeSystem: 'bootstrap',
                            navLinks: true,
                            droppable: true,
                            selectable: true,
                            selectMirror: true,
                            editable: true,
                            dayMaxEvents: true,
                            handleWindowResize: true,
                            height: 'auto',
                            timeFormat: 'H(:mm)',
                            {{-- events: {!! json_encode($arrEvents) !!}, --}}
                            events: data,
                            locale: '{{ basename(App::getLocale()) }}',
                            dayClick: function(e) {
                                var t = moment(e).toISOString();
                                $("#new-event").modal("show"), $(".new-event--title").val(""),
                                    $(".new-event--start").val(t), $(".new-event--end").val(t)
                            },
                            eventResize: function(event) {
                                var eventObj = {
                                    start: event.start.format(),
                                    end: event.end.format(),
                                };
                            },
                            viewRender: function(t) {
                                e.fullCalendar("getDate").month(), $(".fullcalendar-title")
                                    .html(t.title)
                            },
                            eventClick: function(e, t) {
                                var title = e.title;
                                var url = e.url;

                                if (typeof url != 'undefined') {
                                    $("#commonModal .modal-title").html(title);
                                    $("#commonModal .modal-dialog").addClass('modal-md');
                                    $("#commonModal").modal('show');
                                    $.get(url, {}, function(data) {
                                        $('#commonModal .modal-body').html(data);
                                    });
                                    return false;
                                }
                            }
                        });
                        calendar.render();
                    })();
                }
            });
        }
    </script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('HRM') }}</li>
@endsection
@php
    $setting = \App\Models\Utility::settings();
@endphp
@section('content')
    @if (\Auth::user()->type != 'client' && \Auth::user()->type != 'company' && \Auth::user()->type != 'branch')
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-xxl-6">
                        <div class="card">
                            <div class="card-header">
                                <h4>{{ __('Mark Attandance') }}</h4>
                            </div>
                            <div class="card-body dash-card-body">
                                <p class="text-muted pb-0-5">
                                    {{ __('My Office Time: ' . $officeTime['startTime'] . ' to ' . $officeTime['endTime']) }}</p>
                                <center>
                                    <div class="row">
                                        <div class="col-md-6">
                                            {{ Form::open(['url' => 'attendanceemployee/attendance', 'method' => 'post']) }}
                                            @if (empty($employeeAttendance) || $employeeAttendance->clock_out != '00:00:00')
                                                <button type="submit" value="0" name="in" id="clock_in"
                                                    class="btn btn-success ">{{ __('CLOCK IN') }}</button>
                                            @else
                                                <button type="submit" value="0" name="in" id="clock_in"
                                                    class="btn btn-success disabled" disabled>{{ __('CLOCK IN') }}</button>
                                            @endif
                                            {{ Form::close() }}
                                        </div>
                                        <div class="col-md-6 ">
                                            @if (!empty($employeeAttendance) && $employeeAttendance->clock_out == '00:00:00')
                                                {{ Form::model($employeeAttendance, ['route' => ['attendanceemployee.update', $employeeAttendance->id], 'method' => 'PUT']) }}
                                                <button type="submit" value="1" name="out" id="clock_out"
                                                    class="btn btn-danger">{{ __('CLOCK OUT') }}</button>
                                            @else
                                                <button type="submit" value="1" name="out" id="clock_out"
                                                    class="btn btn-danger disabled" disabled>{{ __('CLOCK OUT') }}</button>
                                            @endif
                                            {{ Form::close() }}
                                        </div>
                                    </div>
                                </center>

                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <h5>{{ __('Event') }}</h5>
                                    </div>
                                    <div class="col-lg-6">
                                        @if (isset($setting['google_calendar_enable']) && $setting['google_calendar_enable'] == 'on')
                                            <select class="form-control" name="calender_type" id="calender_type"
                                                onchange="get_data()">
                                                <option value="goggle_calender">{{ __('Google Calender') }}</option>
                                                <option value="local_calender" selected="true">{{ __('Local Calender') }}
                                                </option>
                                            </select>
                                        @endif
                                        <input type="hidden" id="event_dashboard" value="{{ url('/') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id='calendar' class='calendar e-height'></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-6">
                        <div class="card list_card">
                            <div class="card-header">
                                <h4>{{ __('Announcement List') }}</h4>
                            </div>
                            <div class="card-body dash-card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Title') }}</th>
                                                <th>{{ __('Start Date') }}</th>
                                                <th>{{ __('End Date') }}</th>
                                                <th>{{ __('description') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($announcements as $announcement)
                                                <tr>
                                                    <td>{{ $announcement->title }}</td>
                                                    <td>{{ \Auth::user()->dateFormat($announcement->start_date) }}</td>
                                                    <td>{{ \Auth::user()->dateFormat($announcement->end_date) }}</td>
                                                    <td>{{ $announcement->description }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4">
                                                        <div class="text-center">
                                                            <h6>{{ __('There is no Announcement List') }}</h6>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="card list_card">
                            <div class="card-header">
                                <h4>{{ __('Meeting List') }}</h4>
                            </div>
                            <div class="card-body dash-card-body">
                                @if (count($meetings) > 0)
                                    <div class="table-responsive">
                                        <table class="table align-items-center">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Meeting title') }}</th>
                                                    <th>{{ __('Meeting Date') }}</th>
                                                    <th>{{ __('Meeting Time') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($meetings as $meeting)
                                                    <tr>
                                                        <td>{{ $meeting->title }}</td>
                                                        <td>{{ \Auth::user()->dateFormat($meeting->date) }}</td>
                                                        <td>{{ \Auth::user()->timeFormat($meeting->time) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="p-2">
                                        {{ __('No meeting scheduled yet.') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-xxl-12">
                <div class="card">
                    <div class="card-header">
                        <h5>{{ __("Today's Not Clock In") }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row g-3 flex-nowrap team-lists horizontal-scroll-cards">
                                    @foreach ($notClockIns as $notClockIn)
                                        @php
                                            $user = $notClockIn->user;
                                            $logo = asset(Storage::url('uploads/avatar/'));
                                            $avatar = !empty($notClockIn->user->avatar)
                                                ? $notClockIn->user->avatar
                                                : 'avatar.png';
                                        @endphp
                                        <div class="col-auto">
                                            <img src="{{ $logo . $avatar }}" alt="">
                                            <p class="mt-2">{{ $notClockIn->name }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-md-9">
                        <style>
                            /* General Card Styling */
                            .calendar-card {
                                border: 1px solid #ddd;
                                border-radius: 8px;
                                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                                overflow: hidden;
                            }

                            /* Header Styling */
                            .calendar-header {
                                background-color: #003366;
                                color: white !important;
                                padding: 15px;
                            }

                            .calendar-title {
                                font-size: 1.25rem;
                                font-weight: bold;
                                margin: 0;
                                color: white !important;
                            }

                            /* Dropdown Styling */
                            .calendar-select {
                                border: 1px solid #ccc;
                                border-radius: 4px;
                                padding: 5px 10px;
                                font-size: 0.9rem;
                                background-color: #f9f9f9;
                                color: #333;
                                transition: all 0.3s ease;
                            }

                            .calendar-select:hover {
                                background-color: #e0e0e0;
                                border-color: #999;
                            }

                            /* Calendar Container Styling */
                            .calendar-body {
                                background-color: #ffffff;
                                padding: 20px;
                                font-family: Arial, sans-serif;
                            }

                            /* Calendar Table Styling */
                            #calendar {
                                border-collapse: collapse;
                                width: 100%;
                                margin: 0 auto;
                                font-size: 0.9rem;
                            }

                            #calendar .fc-header-toolbar {
                                /* background-color: #4285f4; */
                                color: white;
                                padding: 10px;
                                text-align: center;
                                font-size: 1rem;
                                border-radius: 4px 4px 0 0;
                            }

                            #calendar .fc-daygrid-day {
                                border: 1px solid #ddd;
                                padding: 10px;
                                cursor: pointer;
                                transition: all 0.2s ease;
                            }

                            #calendar .fc-daygrid-day:hover {
                                background-color: #f0f8ff;
                                transform: scale(1.05);
                            }

                            #calendar .fc-day-today {
                                background-color: #003366 !important;
                                border-radius: 4px;
                                font-weight: bold;
                                color: #ffffff;
                            }

                            #calendar .fc-button {
                                background-color: #003366;
                                color: white;
                                border: none;
                                padding: 5px 10px;
                                margin: 2px;
                                border-radius: 4px;
                                cursor: pointer;
                                transition: all 0.3s ease;
                            }

                            #calendar .fc-button:hover {
                                background-color: #005bb5;
                            }
                            #calendar table thead{
                                background: #003366;
                                color: #fff !important; 
                            }
                            /* Responsive Design */
                            @media (max-width: 768px) {
                                .calendar-header {
                                    text-align: center;
                                }

                                .calendar-select {
                                    width: 100%;
                                }
                            }
                        </style>
                        {{-- <div class="card">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <h5>{{ __('Event') }}</h5>
                                    </div>
                                    <div class="col-lg-6">

                                        @if (isset($setting['google_calendar_enable']) && $setting['google_calendar_enable'] == 'on')
                                            <select class="form-control" name="calender_type" id="calender_type" onchange="get_data()">
                                                <option value="goggle_calender">{{__('Google Calender')}}</option>
                                                <option value="local_calender" selected="true">{{__('Local Calender')}}</option>
                                            </select>
                                        @endif
                                        <input type="hidden" id="event_dashboard" value="{{url('/')}}">
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id='calendar' class='calendar'></div>
                            </div>
                        </div> --}}
                        <div class="card calendar-card">
                            <div class="card-header calendar-header">
                                <div class="row align-items-center">
                                    <div class="col-lg-6">
                                        <h5 class="calendar-title">{{ __('Event') }}</h5>
                                    </div>
                                    <div class="col-lg-6 text-end">
                                        @if (isset($setting['google_calendar_enable']) && $setting['google_calendar_enable'] == 'on')
                                            <select class="form-control calendar-select" name="calender_type"
                                                id="calender_type" onchange="get_data()">
                                                <option value="goggle_calender">{{ __('Google Calendar') }}</option>
                                                <option value="local_calender" selected="true">{{ __('Local Calendar') }}
                                                </option>
                                            </select>
                                        @endif
                                        <input type="hidden" id="event_dashboard" value="{{ url('/') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="card-body calendar-body">
                                <div id="calendar" class="calendar e-height"></div>
                            </div>
                        </div>

                    </div>


                    <div class="col-md-3">
                        <div class="col-xxl-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5>{{ __('Staff') }}</h5>
                                    <div class="row  mt-4">
                                        <div class="col-md-6 col-sm-6">
                                            <div class="d-flex align-items-start mb-3">
                                                <div class="theme-avtar bg-primary">
                                                    <i class="ti ti-users"></i>
                                                </div>
                                                <div class="ms-2">
                                                    <p class="text-muted text-sm mb-0">{{ __('Total Staff') }}</p>
                                                    <h4 class="mb-0 text-success">{{ $countUser + $countClient }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-6 my-3 my-sm-0">
                                            <div class="d-flex align-items-start mb-3">
                                                <div class="theme-avtar bg-info">
                                                    <i class="ti ti-user"></i>
                                                </div>
                                                <div class="ms-2">
                                                    <p class="text-muted text-sm mb-0">{{ __('Total Employee') }}</p>
                                                    <h4 class="mb-0 text-info">{{ $countUser }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="d-flex align-items-start mb-3">
                                                <div class="theme-avtar bg-danger">
                                                    <i class="ti ti-user"></i>
                                                </div>
                                                <div class="ms-2">
                                                    <p class="text-muted text-sm mb-0">{{ __('Total Client') }}</p>
                                                    <h4 class="mb-0 text-danger">{{ $countClient }}</h4>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5>{{ __('Job') }}</h5>
                                    <div class="row  mt-4">
                                        <div class="col-md-6 col-sm-6">
                                            <div class="d-flex align-items-start mb-3">
                                                <div class="theme-avtar bg-primary">
                                                    <i class="ti ti-award"></i>
                                                </div>
                                                <div class="ms-2">
                                                    <p class="text-muted text-sm mb-0">{{ __('Total Jobs') }}</p>
                                                    <h4 class="mb-0 text-success">{{ $activeJob + $inActiveJOb }}</h4>

                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-6 my-3 my-sm-0">
                                            <div class="d-flex align-items-start mb-3">
                                                <div class="theme-avtar bg-info">
                                                    <i class="ti ti-check"></i>
                                                </div>
                                                <div class="ms-2">
                                                    <p class="text-muted text-sm mb-0">{{ __('Active Jobs') }}</p>
                                                    <h4 class="mb-0 text-info">{{ $activeJob }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="d-flex align-items-start mb-3">
                                                <div class="theme-avtar bg-danger">
                                                    <i class="ti ti-x"></i>
                                                </div>
                                                <div class="ms-2">
                                                    <p class="text-muted text-sm mb-0">{{ __('Inactive Jobs') }}</p>
                                                    <h4 class="mb-0 text-danger">{{ $inActiveJOb }}</h4>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5>{{ __('Training') }}</h5>
                                    <div class="row  mt-4">
                                        <div class="col-md-6 col-sm-6">
                                            <div class="d-flex align-items-start mb-3">
                                                <div class="theme-avtar bg-primary">
                                                    <i class="ti ti-users"></i>
                                                </div>
                                                <div class="ms-2">
                                                    <p class="text-muted text-sm mb-0">{{ __('Total Training') }}</p>
                                                    <h4 class="mb-0 text-success">{{ $onGoingTraining + $doneTraining }}
                                                    </h4>

                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-6 my-3 my-sm-0">
                                            <div class="d-flex align-items-start mb-3">
                                                <div class="theme-avtar bg-info">
                                                    <i class="ti ti-user"></i>
                                                </div>
                                                <div class="ms-2">
                                                    <p class="text-muted text-sm mb-0">{{ __('Trainer') }}</p>
                                                    <h4 class="mb-0 text-info">{{ $countTrainer }}</h4>

                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="d-flex align-items-start mb-3">
                                                <div class="theme-avtar bg-danger">
                                                    <i class="ti ti-user-check"></i>
                                                </div>
                                                <div class="ms-2">
                                                    <p class="text-muted text-sm mb-0">{{ __('Active Training') }}</p>
                                                    <h4 class="mb-0 text-danger">{{ $onGoingTraining }}</h4>

                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="d-flex align-items-start mb-3">
                                                <div class="theme-avtar bg-secondary">
                                                    <i class="ti ti-user-minus"></i>
                                                </div>
                                                <div class="ms-2">
                                                    <p class="text-muted text-sm mb-0">{{ __('Done Training') }}</p>
                                                    <h4 class="mb-0 text-secondary">{{ $doneTraining }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">

                                <h5>{{ __('Announcement List') }}</h5>
                            </div>
                            <div class="card-body" style="min-height: 295px;">
                                <div class="table-responsive">
                                    @if (count($announcements) > 0)
                                        <table class="table align-items-center">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Title') }}</th>
                                                    <th>{{ __('Start Date') }}</th>
                                                    <th>{{ __('End Date') }}</th>

                                                </tr>
                                            </thead>
                                            <tbody class="list">
                                                @foreach ($announcements as $announcement)
                                                    <tr>
                                                        <td>{{ $announcement->title }}</td>
                                                        <td>{{ \Auth::user()->dateFormat($announcement->start_date) }}</td>
                                                        <td>{{ \Auth::user()->dateFormat($announcement->end_date) }}</td>

                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <div class="p-2">
                                            {{ __('No accouncement present yet.') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>{{ __('Meeting schedule') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    @if (count($meetings) > 0)
                                        <table class="table align-items-center">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Title') }}</th>
                                                    <th>{{ __('Date') }}</th>
                                                    <th>{{ __('Time') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="list">
                                                @foreach ($meetings as $meeting)
                                                    <tr>
                                                        <td>{{ $meeting->title }}</td>
                                                        <td>{{ \Auth::user()->dateFormat($meeting->date) }}</td>
                                                        <td>{{ \Auth::user()->timeFormat($meeting->time) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <div class="p-2">
                                            {{ __('No meeting scheduled yet.') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    @endif
@endsection
