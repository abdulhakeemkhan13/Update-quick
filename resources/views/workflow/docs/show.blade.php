@extends('layouts.admin')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Hrm Workflow') }}</li>
@endsection

@section('content')
    <script src="https://cdn.jsdelivr.net/gh/jerosoler/Drawflow/dist/drawflow.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/js/all.min.js"
        integrity="sha256-KzZiKy0DWYsnwMF+X1DvQngQ2/FxF7MF3Ff72XcpuPs=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/jerosoler/Drawflow@0.0.48/dist/drawflow.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css"
        integrity="sha256-h20CPZ0QyXlBuAw7A+KluUYx/3pK+c7lYEpqLTlxjYQ=" crossorigin="anonymous" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@9"></script>
    <script src="https://unpkg.com/micromodal/dist/micromodal.min.js"></script>
    <link rel="stylesheet" type="text/css" href="{{ asset('public/workflow/beautiful.css') }}" />
    <div class="wrapper">
        <div class="col">
            <div id="drawflow" ondrop="drop(event)" ondragover="allowDrop(event)">
                {{-- <div class="btn-clear" onclick="editor.clearModuleSelected()">Clear</div> --}}
                <div class="btn-lock">
                    <i id="lock" class="fas fa-lock" onclick="editor.editor_mode='fixed'; changeMode('lock');"></i>
                    <i id="unlock" class="fas fa-lock-open" onclick="editor.editor_mode='edit'; changeMode('unlock');"
                        style="display:none;"></i>
                </div>
                <div class="bar-zoom">
                    <i class="fas fa-search-minus" onclick="editor.zoom_out()"></i>
                    <i class="fas fa-search" onclick="editor.zoom_reset()"></i>
                    <i class="fas fa-search-plus" onclick="editor.zoom_in()"></i>
                </div>
            </div>
        </div>
    </div>
    <script>
        var workflow = {!! json_encode($workflow) !!};
        var workflow_actions = {!! json_encode($workflow_actions) !!};
        var id = document.getElementById("drawflow");
        const editor = new Drawflow(id);
        editor.reroute = true;
        editor.start();
        const dataToImport = {
            "drawflow": {
                "Home": {
                    "data": {}
                }
            }
        };

        const minSpacing = 30;
        const maxSpacing = 50;
        const maxX = 800;
        const maxY = 600;
        const placedNodes = [];
        let nodeIndex = 1;
        let position_y = 40;
        workflow_actions.forEach((action) => {
            const nodeName = action.node_id.split('-')[0] + (/[a-zA-Z]/.test(action.node_id.split('-')[1]) ? ' ' +
                action.node_id.split('-')[1] : '');
            const nodeId = action.node_actual_id.split('node-')[1];
            const nodeHtml = `<div class="py-3">
                        <div class="title-box" style="flex; flex-direction:column; background-color:#fff !important; ">
                            <section class="content" style="display:grid; grid-template-columns:100%; align-items:center;">
                                <div class="buttonss flex" style="display: flex;justify-content: end;gap: 5px;" bis_skin_checked="1"> 
                                    <svg class="svg-inline--fa fa-user-plus fa-w-20" style="cursor: pointer;" onclick="opensidebar(event,type='adduser')" aria-hidden="true" focusable="false" data-prefix="fa" data-icon="user-plus" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" data-fa-i2svg=""><path fill="currentColor" d="M624 208h-64v-64c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v64h-64c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h64v64c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16v-64h64c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16zm-400 48c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-41.6c0-74.2-60.2-134.4-134.4-134.4z"></path></svg><!-- <i class="fa fa-user-plus" style="cursor:pointer;" onclick="opensidebar(event,type='adduser')"></i> -->
                                    <svg class="svg-inline--fa fa-ellipsis-vertical fa-w-16" style="cursor: pointer;" onclick="opensidebar(event,type='addaction')" aria-hidden="true" focusable="false" data-prefix="fa" data-icon="ellipsis-vertical" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg=""><g><path fill="currentColor" d="M156.5,447.7l-12.6,29.5c-18.7-9.5-35.9-21.2-51.5-34.9l22.7-22.7C127.6,430.5,141.5,440,156.5,447.7z M40.6,272H8.5 c1.4,21.2,5.4,41.7,11.7,61.1L50,321.2C45.1,305.5,41.8,289,40.6,272z M40.6,240c1.4-18.8,5.2-37,11.1-54.1l-29.5-12.6 C14.7,194.3,10,216.7,8.5,240H40.6z M64.3,156.5c7.8-14.9,17.2-28.8,28.1-41.5L69.7,92.3c-13.7,15.6-25.5,32.8-34.9,51.5 L64.3,156.5z M397,419.6c-13.9,12-29.4,22.3-46.1,30.4l11.9,29.8c20.7-9.9,39.8-22.6,56.9-37.6L397,419.6z M115,92.4 c13.9-12,29.4-22.3,46.1-30.4l-11.9-29.8c-20.7,9.9-39.8,22.6-56.8,37.6L115,92.4z M447.7,355.5c-7.8,14.9-17.2,28.8-28.1,41.5 l22.7,22.7c13.7-15.6,25.5-32.9,34.9-51.5L447.7,355.5z M471.4,272c-1.4,18.8-5.2,37-11.1,54.1l29.5,12.6 c7.5-21.1,12.2-43.5,13.6-66.8H471.4z M321.2,462c-15.7,5-32.2,8.2-49.2,9.4v32.1c21.2-1.4,41.7-5.4,61.1-11.7L321.2,462z M240,471.4c-18.8-1.4-37-5.2-54.1-11.1l-12.6,29.5c21.1,7.5,43.5,12.2,66.8,13.6V471.4z M462,190.8c5,15.7,8.2,32.2,9.4,49.2h32.1 c-1.4-21.2-5.4-41.7-11.7-61.1L462,190.8z M92.4,397c-12-13.9-22.3-29.4-30.4-46.1l-29.8,11.9c9.9,20.7,22.6,39.8,37.6,56.9 L92.4,397z M272,40.6c18.8,1.4,36.9,5.2,54.1,11.1l12.6-29.5C317.7,14.7,295.3,10,272,8.5V40.6z M190.8,50 c15.7-5,32.2-8.2,49.2-9.4V8.5c-21.2,1.4-41.7,5.4-61.1,11.7L190.8,50z M442.3,92.3L419.6,115c12,13.9,22.3,29.4,30.5,46.1 l29.8-11.9C470,128.5,457.3,109.4,442.3,92.3z M397,92.4l22.7-22.7c-15.6-13.7-32.8-25.5-51.5-34.9l-12.6,29.5 C370.4,72.1,384.4,81.5,397,92.4z"></path><circle fill="currentColor" cx="256" cy="364" r="28"><animate attributeType="XML" repeatCount="indefinite" dur="2s" attributeName="r" values="28;14;28;28;14;28;"></animate><animate attributeType="XML" repeatCount="indefinite" dur="2s" attributeName="opacity" values="1;0;1;1;0;1;"></animate></circle><path fill="currentColor" opacity="1" d="M263.7,312h-16c-6.6,0-12-5.4-12-12c0-71,77.4-63.9,77.4-107.8c0-20-17.8-40.2-57.4-40.2c-29.1,0-44.3,9.6-59.2,28.7 c-3.9,5-11.1,6-16.2,2.4l-13.1-9.2c-5.6-3.9-6.9-11.8-2.6-17.2c21.2-27.2,46.4-44.7,91.2-44.7c52.3,0,97.4,29.8,97.4,80.2 c0,67.6-77.4,63.5-77.4,107.8C275.7,306.6,270.3,312,263.7,312z"><animate attributeType="XML" repeatCount="indefinite" dur="2s" attributeName="opacity" values="1;0;0;0;0;1;"></animate></path><path fill="currentColor" opacity="0" d="M232.5,134.5l7,168c0.3,6.4,5.6,11.5,12,11.5h9c6.4,0,11.7-5.1,12-11.5l7-168c0.3-6.8-5.2-12.5-12-12.5h-23 C237.7,122,232.2,127.7,232.5,134.5z"><animate attributeType="XML" repeatCount="indefinite" dur="2s" attributeName="opacity" values="0;0;1;1;0;0;"></animate></path></g></svg><!-- <i class="fa fa-ellipsis-vertical" style="cursor:pointer;" onclick="opensidebar(event,type='addaction')"></i> -->
                                </div>
                                <div>${nodeName.charAt(0).toUpperCase() + nodeName.slice(1)}</div>
                                <input type="text" hidden="" name="nodeid" value="${action.node_id}">
                            </section>
                            <div class="labels" style="display: flex; gap: 2px; flex-wrap: wrap;" bis_skin_checked="1"></div>
                            <input type="hidden" name="conditions" value='${action.applied_conditions}' />
                            <input type="hidden" name="assigned-user" value='${action.assigned_users}' />
                        </div>
                    </div>`;
            dataToImport.drawflow.Home.data[nodeIndex] = {
                id: nodeId,
                name: nodeName,
                data: {},
                class: nodeName,
                html: nodeHtml,
                typenode: false,
                inputs: JSON.parse(action.inputs),
                outputs: JSON.parse(action.outputs),
                pos_x: position_y,
                pos_y: position_y,
            };
            nodeIndex++;
            position_y += 100;
        });

        editor.import(dataToImport);
        editor.on('addReroute', function(id) {})
    </script>
@endsection
