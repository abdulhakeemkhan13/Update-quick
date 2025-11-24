@extends('layouts.admin')
@section('page-title')
    {{ __('Dashboard') }}
@endsection
@push('script-page')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.4.0/echarts.min.js"></script>
    <script>
        /*const usedColor = getComputedStyle(document.body).getPropertyValue('--used-color').trim();
        const usedColorLight = getComputedStyle(document.body).getPropertyValue('--used-color-light').trim();
        const usedColorMedium = getComputedStyle(document.body).getPropertyValue('--used-color-medium').trim();
        const usedColorDark = getComputedStyle(document.body).getPropertyValue('--used-color-dark').trim();
        const usedColorDarker = getComputedStyle(document.body).getPropertyValue('--used-color-darker').trim();
        const usedColorContrast = getComputedStyle(document.body).getPropertyValue('--used-color-contrast').trim();*/
        // $(document).ready(function() {
        //     $(".widget-meter").addClass("meter-status");
        //     var Mrotate1 = $("#nxt-meter-status").attr("data-meter");
        //     var Mrotate = 1.33 * Mrotate1;
        //     $("#nxt-need-tocomplete").text(100 - Mrotate1);
        //     var statusStep = 0;

        //     function statusfunction() {
        //         $("#nxt-meter-status span").text(statusStep);
        //         if (statusStep < Mrotate1) {
        //             statusStep = (statusStep + 1);
        //             setTimeout(function() {
        //                 statusfunction();
        //             }, 2000 / Mrotate1);
        //         }
        //     }
        //     statusfunction();
        //     $(document).ready(function() {
        //         $('#MyDiv2').animate({
        //             deg: 133
        //         }, {
        //             duration: 1000,
        //             step: function(now) {
        //                 $(this).css({
        //                     transform: 'rotate(' + now + 'deg)'
        //                 });
        //             }
        //         });
        //         $('#MyDiv2').animate({
        //             deg: Mrotate
        //         }, {
        //             duration: 2000,
        //             step: function(now) {
        //                 $(this).css({
        //                     transform: 'rotate(' + now + 'deg)'
        //                 });
        //             }
        //         });
        //     });
        // });
        // $(document).ready(function() {
        //     $(".widget-meter-2").addClass("meter-status");
        //     var Mrotate2 = $("#nxt-meter-2-status").attr("data-meter");
        //     var Mrotate = 1.33 * Mrotate2;
        //     $("#nxt-need-tocomplete").text(100 - Mrotate2);
        //     var statusStep = 0;

        //     function statusfunction() {
        //         $("#nxt-meter-2-status span").text(statusStep);
        //         if (statusStep < Mrotate2) {
        //             statusStep = (statusStep + 1);
        //             setTimeout(function() {
        //                 statusfunction();
        //             }, 2000 / Mrotate2);
        //         }
        //     }
        //     statusfunction();
        //     $(document).ready(function() {
        //         $('#MyDiv3').animate({
        //             deg: 133
        //         }, {
        //             duration: 1000,
        //             step: function(now) {
        //                 $(this).css({
        //                     transform: 'rotate(' + now + 'deg)'
        //                 });
        //             }
        //         });
        //         $('#MyDiv3').animate({
        //             deg: Mrotate
        //         }, {
        //             duration: 2000,
        //             step: function(now) {
        //                 $(this).css({
        //                     transform: 'rotate(' + now + 'deg)'
        //                 });
        //             }
        //         });
        //     });
        // });
        // $(function() {
        //     const pieData = [{
        //             title: "Facebook",
        //             value: 180,
        //             color: "rgb(62,201,214)",
        //         },
        //         {
        //             title: "Instagram",
        //             value: 60,
        //             color: "#ffa21d",
        //         },
        //         {
        //             title: "Google",
        //             value: 50,
        //             color: "#6fd943",
        //         },
        //     ];
        //     $("#pieChart").drawPieChart(pieData);
        //     const listContainer = $(".content-list");
        //     pieData.forEach((item) => {
        //         const listItem = `
    //     <li style="font-size:0.7rem;">
    //         <span class="color-square" style="background-color: ${item.color};"></span>
    //         ${item.title} - ${item.value}
    //     </li>`;
        //         listContainer.append(listItem);
        //     });
        // });


        // (function($, undefined) {
        //     $.fn.drawPieChart = function(data, options) {
        //         var $this = this,
        //             W = $this.width(),
        //             H = $this.height(),
        //             centerX = W / 2,
        //             centerY = H / 2,
        //             cos = Math.cos,
        //             sin = Math.sin,
        //             PI = Math.PI,
        //             settings = $.extend({
        //                 segmentShowStroke: true,
        //                 segmentStrokeWidth: 1,
        //                 edgeOffset: 20,
        //                 pieSegmentGroupClass: "pieSegmentGroup",
        //                 lightPieClass: "lightPie",
        //                 animation: true,
        //                 animationSteps: 90,
        //                 animationEasing: "easeInOutExpo",
        //                 tipOffsetX: -15,
        //                 tipOffsetY: -45,
        //                 tipClass: "pieTip",
        //                 beforeDraw: function() {},
        //                 afterDrawed: function() {},
        //                 onPieMouseenter: function(e, data) {},
        //                 onPieMouseleave: function(e, data) {},
        //                 onPieClick: function(e, data) {}
        //             }, options),
        //             animationOptions = {
        //                 linear: function(t) {
        //                     return t;
        //                 },
        //                 easeInOutExpo: function(t) {
        //                     var v = t < .5 ? 8 * t * t * t * t : 1 - 8 * (--t) * t * t * t;
        //                     return (v > 1) ? 1 : v;
        //                 }
        //             },
        //             requestAnimFrame = function() {
        //                 return window.requestAnimationFrame ||
        //                     window.webkitRequestAnimationFrame ||
        //                     window.mozRequestAnimationFrame ||
        //                     window.oRequestAnimationFrame ||
        //                     window.msRequestAnimationFrame ||
        //                     function(callback) {
        //                         window.setTimeout(callback, 1000 / 60);
        //                     };
        //             }();

        //         var $wrapper = $('<svg width="' + W + '" height="' + H + '" viewBox="0 0 ' + W + ' ' + H +
        //                 '" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"></svg>')
        //             .appendTo($this);
        //         var $groups = [],
        //             $pies = [],
        //             $lightPies = [],
        //             easingFunction = animationOptions[settings.animationEasing],
        //             pieRadius = Min([H / 2, W / 2]) - settings.edgeOffset,
        //             segmentTotal = 0;

        //         //Draw base circle
        //         var drawBasePie = function() {
        //             var base = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        //             var $base = $(base).appendTo($wrapper);
        //             base.setAttribute("cx", centerX);
        //             base.setAttribute("cy", centerY);
        //             base.setAttribute("r", pieRadius + settings.baseOffset);
        //             base.setAttribute("fill", settings.baseColor);
        //         }();

        //         //Set up pie segments wrapper
        //         var pathGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        //         var $pathGroup = $(pathGroup).appendTo($wrapper);
        //         $pathGroup[0].setAttribute("opacity", 0);

        //         //Set up tooltip
        //         var $tip = $('<div class="' + settings.tipClass + '" />').appendTo('body').hide(),
        //             tipW = $tip.width(),
        //             tipH = $tip.height();

        //         for (var i = 0, len = data.length; i < len; i++) {
        //             segmentTotal += data[i].value;
        //             var g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        //             g.setAttribute("data-order", i);
        //             g.setAttribute("class", settings.pieSegmentGroupClass);
        //             $groups[i] = $(g).appendTo($pathGroup);
        //             $groups[i]
        //                 .on("mouseenter", pathMouseEnter)
        //                 .on("mouseleave", pathMouseLeave)
        //                 .on("mousemove", pathMouseMove)
        //                 .on("click", pathClick);

        //             var p = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        //             p.setAttribute("stroke-width", settings.segmentStrokeWidth);
        //             p.setAttribute("stroke", settings.segmentStrokeColor);
        //             p.setAttribute("stroke-miterlimit", 2);
        //             p.setAttribute("fill", data[i].color);
        //             p.setAttribute("class", settings.pieSegmentClass);
        //             $pies[i] = $(p).appendTo($groups[i]);

        //             var lp = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        //             lp.setAttribute("stroke-width", settings.segmentStrokeWidth);
        //             lp.setAttribute("stroke", settings.segmentStrokeColor);
        //             lp.setAttribute("stroke-miterlimit", 2);
        //             lp.setAttribute("fill", data[i].color);
        //             lp.setAttribute("opacity", settings.lightPiesOpacity);
        //             lp.setAttribute("class", settings.lightPieClass);
        //             $lightPies[i] = $(lp).appendTo($groups[i]);
        //         }

        //         settings.beforeDraw.call($this);
        //         //Animation start
        //         triggerAnimation();

        //         function pathMouseEnter(e) {
        //             var index = $(this).data().order;
        //             $tip.text(data[index].title + ": " + data[index].value).fadeIn(200);
        //             if ($groups[index][0].getAttribute("data-active") !== "active") {
        //                 $lightPies[index].animate({
        //                     opacity: .8
        //                 }, 180);
        //             }
        //             settings.onPieMouseenter.apply($(this), [e, data]);
        //         }

        //         function pathMouseLeave(e) {
        //             var index = $(this).data().order;
        //             $tip.hide();
        //             if ($groups[index][0].getAttribute("data-active") !== "active") {
        //                 $lightPies[index].animate({
        //                     opacity: settings.lightPiesOpacity
        //                 }, 100);
        //             }
        //             settings.onPieMouseleave.apply($(this), [e, data]);
        //         }

        //         function pathMouseMove(e) {
        //             $tip.css({
        //                 top: e.pageY + settings.tipOffsetY,
        //                 left: e.pageX - $tip.width() / 2 + settings.tipOffsetX
        //             });
        //         }

        //         function pathClick(e) {
        //             var index = $(this).data().order;
        //             var targetGroup = $groups[index][0];
        //             for (var i = 0, len = data.length; i < len; i++) {
        //                 if (i === index) continue;
        //                 $groups[i][0].setAttribute("data-active", "");
        //                 $lightPies[i].css({
        //                     opacity: settings.lightPiesOpacity
        //                 });
        //             }
        //             if (targetGroup.getAttribute("data-active") === "active") {
        //                 targetGroup.setAttribute("data-active", "");
        //                 $lightPies[index].css({
        //                     opacity: .8
        //                 });
        //             } else {
        //                 targetGroup.setAttribute("data-active", "active");
        //                 $lightPies[index].css({
        //                     opacity: 1
        //                 });
        //             }
        //             settings.onPieClick.apply($(this), [e, data]);
        //         }

        //         function drawPieSegments(animationDecimal) {
        //             var startRadius = -PI / 2, //-90 degree
        //                 rotateAnimation = 1;
        //             if (settings.animation) {
        //                 rotateAnimation = animationDecimal; //count up between0~1
        //             }

        //             $pathGroup[0].setAttribute("opacity", animationDecimal);

        //             //draw each path
        //             for (var i = 0, len = data.length; i < len; i++) {
        //                 var segmentAngle = rotateAnimation * ((data[i].value / segmentTotal) * (PI *
        //                         2)), //start radian
        //                     endRadius = startRadius + segmentAngle,
        //                     largeArc = ((endRadius - startRadius) % (PI * 2)) > PI ? 1 : 0,
        //                     startX = centerX + cos(startRadius) * pieRadius,
        //                     startY = centerY + sin(startRadius) * pieRadius,
        //                     endX = centerX + cos(endRadius) * pieRadius,
        //                     endY = centerY + sin(endRadius) * pieRadius,
        //                     startX2 = centerX + cos(startRadius) * (pieRadius + settings.lightPiesOffset),
        //                     startY2 = centerY + sin(startRadius) * (pieRadius + settings.lightPiesOffset),
        //                     endX2 = centerX + cos(endRadius) * (pieRadius + settings.lightPiesOffset),
        //                     endY2 = centerY + sin(endRadius) * (pieRadius + settings.lightPiesOffset);
        //                 var cmd = [
        //                     'M', startX, startY, //Move pointer
        //                     'A', pieRadius, pieRadius, 0, largeArc, 1, endX, endY, //Draw outer arc path
        //                     'L', centerX, centerY, //Draw line to the center.
        //                     'Z' //Cloth path
        //                 ];
        //                 var cmd2 = [
        //                     'M', startX2, startY2,
        //                     'A', pieRadius + settings.lightPiesOffset, pieRadius + settings.lightPiesOffset, 0,
        //                     largeArc, 1, endX2, endY2, //Draw outer arc path
        //                     'L', centerX, centerY,
        //                     'Z'
        //                 ];
        //                 $pies[i][0].setAttribute("d", cmd.join(' '));
        //                 $lightPies[i][0].setAttribute("d", cmd2.join(' '));
        //                 startRadius += segmentAngle;
        //             }
        //         }

        //         var animFrameAmount = (settings.animation) ? 1 / settings.animationSteps :
        //             1, //if settings.animationSteps is 10, animFrameAmount is 0.1
        //             animCount = (settings.animation) ? 0 : 1;

        //         function triggerAnimation() {
        //             if (settings.animation) {
        //                 requestAnimFrame(animationLoop);
        //             } else {
        //                 drawPieSegments(1);
        //             }
        //         }

        //         function animationLoop() {
        //             animCount +=
        //                 animFrameAmount; //animCount start from 0, after "settings.animationSteps"-times executed, animCount reaches 1.
        //             drawPieSegments(easingFunction(animCount));
        //             if (animCount < 1) {
        //                 requestAnimFrame(arguments.callee);
        //             } else {
        //                 settings.afterDrawed.call($this);
        //             }
        //         }

        //         function Max(arr) {
        //             return Math.max.apply(null, arr);
        //         }

        //         function Min(arr) {
        //             return Math.min.apply(null, arr);
        //         }
        //         return $this;
        //     };
        // })(jQuery);
        // input data
        document.addEventListener("DOMContentLoaded", function() {
            var option = {
                title: {
                    text: '',
                    left: 'center',
                    top: 'center'
                },
                color: [usedColorDarker,usedColorDark,usedColor],
                series: [{
                    type: 'pie',
                    data: [{
                            value: 335,
                            name: 'Facebook'
                        },
                        {
                            value: 234,
                            name: 'Instagram'
                        },
                        {
                            value: 1548,
                            name: 'Google'
                        }
                    ],
                    radius: ['40%', '70%']
                }]
            };

            var dountchart = document.querySelector("#dount-chart");
            var pieChartdonut = echarts.init(dountchart);
            pieChartdonut.setOption(option);
        });
    </script>
    <script src="{{ asset('public/js/crm/crmchart.js') }}"></script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('CRM') }}</li>
@endsection
@section('content')
    <style>
        .apexcharts-toolbar {
            display: none;
        }
    </style>
    <div class="row " style="row-gap: 20px;">
        <div class="col-lg-3 col-md-12 dashboard-card">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-auto mb-3 mb-sm-0">
                            <div class="d-flex align-items-center">
                                <div class="theme-avtar bg-primary">
                                    <i class="ti ti-layout-2"></i>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted">{{ __('This month') }}</small>
                                    <h6 class="m-0">{{ __('Lead') }}</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto text-end">
                            <h4 class="m-0">{{ $crm_data['total_leads'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-12 dashboard-card">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-auto mb-3 mb-sm-0">
                            <div class="d-flex align-items-center">
                                <div class="theme-avtar bg-primary">
                                    <i class="ti ti-notebook"></i>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted">{{ __('This month') }}</small>
                                    <h6 class="m-0">{{ __('Accounts') }}</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto text-end">
                            <h4 class="m-0">{{ $crm_data['total_contracts'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-12 dashboard-card">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-auto mb-3 mb-sm-0">
                            <div class="d-flex align-items-center">
                                <div class="theme-avtar bg-primary">
                                    <i class="ti ti-layout-2"></i>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted">{{ __('Pipeline') }}</small>
                                    <h6 class="m-0">{{ __('Deal') }}</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto text-end">
                            <h4 class="m-0">{{ $crm_data['total_deals'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-12 dashboard-card">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-auto mb-3 mb-sm-0">
                            <div class="d-flex align-items-center">
                                <div class="theme-avtar bg-primary">
                                    <i class="ti ti-notebook"></i>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted">{{ __('Last month') }}</small>
                                    <h6 class="m-0">{{ __('Revenue') }}</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto text-end">
                            <h4 class="m-0">{{ $crm_data['total_contracts'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {{-- </div>
    <div class="row" style="row-gap: 20px;"> --}}
        <div class="col-12 col-md-3 col-lg-3 col-xxl-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mt-1 mb-0">{{ __('Lead Target This Year') }}</h5>
                </div>
                <div class="card-body d-flex flex-column" style="align-items: center; justify-content:center;">
                    <div id="chart6"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card h-100" style="overflow-y:hidden;">
                <div class="card-header">
                    <h5 class="mt-1 mb-0">{{ __('Lead By Source') }}</h5>
                </div>
                <div id="dount-chart" style="width:100%; height:100%;"></div>
            </div>
        </div>
        <div class="col-12 col-md-3 col-lg-3 col-xxl-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mt-1 mb-0">{{ __('Deal Target This Year') }}</h5>
                </div>
                <div class="card-body d-flex flex-column" style="align-items: center; justify-content:center;">
                    <div id="chart5"></div>
                </div>
            </div>
        </div>
    {{-- </div>
    <div class="row mt-4" style="row-gap:20px;"> --}}
        <div class="col-12 col-md-6 col-xxl-6">
            <div class="card h-100 " style="max-height: 400px;">
                <div class="card-header">
                    <h5 class="mt-1 mb-0">{{ __('Targets') }}</h5>
                </div>
                <div class="card-body" style="max-height: 100%;">
                    <div class="table-responsive table-card" style="height:90%; overflow-y:scroll; overflow-x:hidden;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('User') }}</th>
                                    <th>{{ __('Month') }}</th>
                                    <th>{{ __('Lead Target') }}</th>
                                    <th>{{ __('Deal Target') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($targets as $target)
                                    <tr>
                                        <td>{{ $target->user->name }}</td>
                                        <td>{{ date('F', strtotime($target->month)) }}</td>
                                        <td>{{ $target->lead_target }}</td>
                                        <td>{{ $target->deal_target }}</td>
                                        @if (\Auth::user()->type == 'company' || \Auth::user()->type == 'branch')
                                            <td class="action text-end">
                                                <div class="action-btn bg-info ms-2">
                                                    <a href="#"
                                                        class="mx-3 btn btn-sm d-inline-flex align-items-center"
                                                        data-url="{{ route('assign-target.edit', $target->id) }}"
                                                        data-ajax-popup="true" data-size="md" data-bs-toggle="tooltip"
                                                        title="{{ __('Edit') }}" data-title="{{ __('Edit Target') }}">
                                                        <i class="ti ti-pencil text-white"></i>
                                                    </a>
                                                </div>
                                                <div class="action-btn bg-danger ms-2">
                                                    {!! Form::open(['method' => 'DELETE', 'route' => ['assign-target.destroy', $target->id]]) !!}
                                                    <a href="#"
                                                        class="mx-3 btn btn-sm  align-items-center bs-pass-para"
                                                        data-bs-toggle="tooltip" title="{{ __('Delete') }}"><i
                                                            class="ti ti-trash text-white"></i></a>
                                                    {!! Form::close() !!}
                                                </div>
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
        <div class="col-12 col-md-6 col-xxl-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mt-1 mb-0">{{ __('Latest Contract') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>{{ __('Subject') }}</th>
                                    @if (\Auth::user()->type != 'client')
                                        <th>{{ __('Client') }}</th>
                                    @endif
                                    <th>{{ __('Project') }}</th>
                                    <th>{{ __('Contract Type') }}</th>
                                    <th>{{ __('Contract Value') }}</th>
                                    <th>{{ __('Start Date') }}</th>
                                    <th>{{ __('End Date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($crm_data['latestContract'] as $contract)
                                    <tr>
                                        <td>
                                            <a href="{{ route('contract.show', $contract->id) }}"
                                                class="btn btn-outline-primary">{{ \Auth::user()->contractNumberFormat($contract->id) }}</a>
                                        </td>
                                        <td>{{ $contract->subject }}</td>
                                        @if (\Auth::user()->type != 'client')
                                            <td>{{ !empty($contract->clients) ? $contract->clients->name : '-' }}</td>
                                        @endif
                                        <td>{{ !empty($contract->projects) ? $contract->projects->project_name : '-' }}
                                        </td>
                                        <td>{{ !empty($contract->types) ? $contract->types->name : '' }}</td>
                                        <td>{{ \Auth::user()->priceFormat($contract->value) }}</td>
                                        <td>{{ \Auth::user()->dateFormat($contract->start_date) }}</td>
                                        <td>{{ \Auth::user()->dateFormat($contract->end_date) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="text-center">
                                                <h6>{{ __('There is no latest contract') }}</h6>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        {{-- <div class="col-lg-4 col-md-12 dashboard-card">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-auto mb-3 mb-sm-0">
                            <div class="d-flex align-items-center">
                                <div class="theme-avtar bg-primary">
                                    <i class="ti ti-layout-2"></i>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted">{{ __('Total') }}</small>
                                    <h6 class="m-0">{{ __('Lead') }}</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto text-end">
                            <h4 class="m-0">{{ $crm_data['total_leads'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12 dashboard-card">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-auto mb-3 mb-sm-0">
                            <div class="d-flex align-items-center">
                                <div class="theme-avtar bg-warning">
                                    <i class="ti ti-layout-2"></i>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted">{{ __('Total') }}</small>
                                    <h6 class="m-0">{{ __('Deal') }}</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto text-end">
                            <h4 class="m-0">{{ $crm_data['total_deals'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12 dashboard-card">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-auto mb-3 mb-sm-0">
                            <div class="d-flex align-items-center">
                                <div class="theme-avtar bg-info">
                                    <i class="ti ti-notebook"></i>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted">{{ __('Total') }}</small>
                                    <h6 class="m-0">{{ __('Contract') }}</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto text-end">
                            <h4 class="m-0">{{ $crm_data['total_contracts'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>   --}}

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Lead Status') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row ">
                        @foreach ($crm_data['lead_status'] as $status => $val)
                            <div class="col-md-6 col-sm-6 mb-5">
                                <div class="align-items-start">
                                    <div class="ms-2">
                                        <p class="text-muted text-sm mb-0">{{ $val['lead_stage'] }}</p>
                                        <h3 class="mb-0 text-primary">{{ $val['lead_percentage'] }}%</h3>
                                        <div class="progress mb-0">
                                            <div class="progress-bar bg-primary"
                                                style="width:{{ $val['lead_percentage'] }}%;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Deal Status') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row ">
                        @foreach ($crm_data['deal_status'] as $status => $val)
                            <div class="col-md-6 col-sm-6 mb-5">
                                <div class="align-items-start">
                                    <div class="ms-2">
                                        <p class="text-muted text-sm mb-0">{{ $val['deal_stage'] }}</p>
                                        <h3 class="mb-0 text-primary">{{ $val['deal_percentage'] }}%</h3>
                                        <div class="progress mb-0">
                                            <div class="progress-bar bg-primary"
                                                style="width:{{ $val['deal_percentage'] }}%;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    {{-- </div> --}}
@endsection
