@extends('layouts.admin')

@section('page-title')
    {{ __('Manage Workflow') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit Workflow') }}</li>
@endsection


@section('action-btn')
    <div class="tabs">
        <div class="btn-export" onclick="console.log(JSON.stringify(editor.export('edit',{{ $workflow->id }}), null, 4))">
            Save
        </div>

    </div>
    {{-- <div class="float-end">
    @can('create warning')
            <a  href="{{ route('workflow.create') }}" title="{{__('Create')}}" class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
    @endcan
    </div> --}}
@endsection

@section('content')

    <head>
        {{-- <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Coworkit - Create WorkFlow</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description"
            content="Simple library for flow programming. Drawflow allows you to create data flows easily and quickly."> --}}
        <script src="https://cdn.jsdelivr.net/gh/jerosoler/Drawflow/dist/drawflow.min.js"></script>
        <script src="{{ asset('public/workflow/docs/script.js') }}"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/js/all.min.js"
            integrity="sha256-KzZiKy0DWYsnwMF+X1DvQngQ2/FxF7MF3Ff72XcpuPs=" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/jerosoler/Drawflow@0.0.48/dist/drawflow.min.css">

        <link rel="stylesheet" type="text/css" href="{{ asset('public/workflow/docs/beautiful.css') }}" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css"
            integrity="sha256-h20CPZ0QyXlBuAw7A+KluUYx/3pK+c7lYEpqLTlxjYQ=" crossorigin="anonymous" />
        {{-- <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet"> --}}
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@9"></script>
        <script src="https://unpkg.com/micromodal/dist/micromodal.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
            integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <style>
            .tabs {
                display: flex;
                cursor: pointer;
                /* background-color: #f1f1f1; */
                justify-content: right;
            }

            .tab {
                padding: 10px 15px;
                margin: 0 5px;
                background-color: #ddd;
                border: 1px solid #ccc;
                border-radius: 5px;
                width: 100px;
                text-align: center
            }

            .tab:hover {
                background-color: #ccc;
            }

            .tab-content {
                display: none;
                padding-right: 0px !important;
                border-top: none;
                background-color: #fff;
            }

            .active-content {
                display: block;
            }

            #openSidebarBtn,
            #closeSidebarBtn {
                cursor: pointer;
                font-size: 20px;
                padding: 0px 5px 0px 5px;
                border: none;
                background-color: #4285f4;
                color: #fff;
                border-radius: 5px;
                transition: background-color 0.3s ease;
            }

            #closeSidebarBtn:hover {
                background-color: red;
                color: #fff;
            }

            .active-tab {
                background-color: #4CAF50;
                color: white;
            }

            /* Sidebar styling */
            .sidebar {
                position: fixed;
                top: 0;
                right: -400px;
                height: 100%;
                width: 400px;
                background-color: #ffffff;
                color: #000;
                overflow-x: hidden;
                z-index: 10000;
                transition: right 0.4s ease;
                /* Smooth animation */
                padding-top: 60px;
                box-shadow: -5px 0 15px rgba(0, 0, 0, 0.3);
            }

            #closeSidebarBtn {
                position: absolute;
                top: 7px;
                right: 10px;
                font-size: 24px;
                background: none;
                color: #000;
                border: none;
                cursor: pointer;
            }

            .sidebar .action-content {
                margin: -50px 20px 0px;
            }

            .action-content {
                margin-top: -50px;
            }

            .sidebar.active {
                right: 0;
            }

            .drawflow,
            #drawflow {
                /* background-color: #fff !important; */
            }

            .tab-content ul,
            .tab-content li,
            .tab-content li a {
                list-style: none !important;
                text-decoration: none;
                cursor: pointer;
            }

            .tab-content ul li {
                position: relative;
                left: -10%;
            }

            .tab-content ul :not(collapsed) .arrow,
            .tab-content li :not(collapsed) .arrow {
                display: inline-block;
                padding-left: 10px;
                padding-right: 10px;
                vertical-align: middle;
                float: right;
            }

            .tab-content .nav-item>li {
                padding: 10px;
                background: #fff;
                border-radius: 10px;
                box-shadow: 0px 1px 10px 1px #eee;
                position: relative;
                left: -10px;
                margin-bottom: 10px;
            }
        </style>
    </head>

    <body>
        <div class="wrapper">
            <div class="col tab-content active-content">
                <div class="nav-item dropdown">
                    <li data-toggle="collapse" data-target="#lead" class="collapsed mt-2">
                        <a href="#" style="display: flex; justify-content:space-between;">Lead <span><i
                                    class="fa fa-chevron-down"></i></span></a>
                    </li>
                    <ul class="sub-menu collapse" id="lead">
                    </ul>
                    <li data-toggle="collapse" data-target="#deal" class="collapsed mt-2">
                        <a href="#" style="display: flex; justify-content:space-between;">Deal <span><i
                                    class="fa fa-chevron-down"></i></span></a>
                    </li>
                    <ul class="sub-menu collapse" id="deal">
                    </ul>
                    <li data-toggle="collapse" data-target="#contract" class="collapsed mt-2">
                        <a href="#" style="display: flex; justify-content:space-between;">Contract <span><i
                                    class="fa fa-chevron-down"></i></span></a>
                    </li>
                    <ul class="sub-menu collapse" id="contract">
                    </ul>
                    <li data-toggle="collapse" data-target="#invoice" class="collapsed mt-2">
                        <a href="#" style="display: flex; justify-content:space-between;">Invoice <span><i
                                    class="fa fa-chevron-down"></i></span></a>
                    </li>
                    <ul class="sub-menu collapse" id="invoice">
                    </ul>
                </div>
            </div>
            <div class="col-right">
                <div class="menu">
                </div>
                <div id="drawflow" ondrop="drop(event)" ondragover="allowDrop(event)">
                    <div class="btn-lock">
                        <i id="lock" class="fas fa-lock" onclick="editor.editor_mode='fixed'; changeMode('lock');"></i>
                        <i id="unlock" class="fas fa-lock-open"
                            onclick="editor.editor_mode='edit'; changeMode('unlock');" style="display:none;"></i>
                    </div>
                    <div class="bar-zoom">
                        <i class="fas fa-search-minus" onclick="editor.zoom_out()"></i>
                        <i class="fas fa-search" onclick="editor.zoom_reset()"></i>
                        <i class="fas fa-search-plus" onclick="editor.zoom_in()"></i>
                    </div>
                </div>
                <div id="rightsidebar" class="sidebar">
                    <button id="closeSidebarBtn">&times;</button>
                    <div class="action-content">
                        <h2 class="action-heading">Sidebar Content</h2>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
            integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous">
        </script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
            integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous">
        </script>
        <script>
            var users = {!! json_encode($allusers) !!};
            var departments = {!! json_encode($departments) !!};
            var designations = {!! json_encode($designations) !!};
            var leadStages = {!! json_encode($lead_stages) !!};
            var dealStages = {!! json_encode($deal_stages) !!};
            var leadsColumn = {!! json_encode($availableLeadColumns) !!};
            var dealColumn = {!! json_encode($availableDealColumns) !!};
            var contractColumn = {!! json_encode($contractcolumns) !!};
            var invoiceColumn = {!! json_encode($invoicecolumns) !!};
            let activeTabIndex = 1;
            const iconsMap = {
                lead: {
                    draft: "fa-pencil-alt",
                    sent: "fa-paper-plane",
                    open: "fa-folder-open",
                    revised: "fa-edit",
                    declined: "fa-times-circle",
                    "custom stage": "fa-star"
                },
                deal: {
                    draft: "fa-file-alt",
                    sent: "fa-envelope",
                    open: "fa-briefcase",
                    revised: "fa-sync-alt",
                    declined: "fa-ban",
                    "custom stage": "fa-cogs"
                }
            };

            function populateDropdowns(dropdownId) {
                const dropdownContent = document.querySelector(`#${dropdownId}`);
                if (dropdownContent) {
                    dropdownContent.innerHTML = '';
                    let stages = [];
                    let createItem = null;
                    let levelid = 0;

                    if (dropdownId == 'lead') {
                        stages = leadStages;
                        createItem = {
                            icon: 'fa-plus',
                            text: 'Create Lead',
                            node: 'create-lead'
                        };
                        levelid = 1; // Level ID for lead
                    } else if (dropdownId == 'deal') {
                        stages = dealStages;
                        createItem = {
                            icon: 'fa-plus',
                            text: 'Create Deal',
                            node: 'create-deal'
                        };
                        levelid = 2; // Level ID for deal
                    } else if (dropdownId == 'contract') {
                        createItem = {
                            icon: 'fa-file-contract',
                            text: 'Create Contract',
                            node: 'create-contract'
                        };
                        levelid = 3; // Level ID for contract
                    } else if (dropdownId == 'invoice') {
                        createItem = {
                            icon: 'fa-file-invoice',
                            text: 'Create Invoice',
                            node: 'create-invoice'
                        };
                        levelid = 4; // Level ID for invoice
                    }

                    if (createItem) {
                        const createDiv = document.createElement('li');
                        createDiv.classList.add('drag-drawflow');
                        createDiv.setAttribute('draggable', 'true');
                        createDiv.setAttribute('ondragstart', 'drag(event)');
                        createDiv.setAttribute('data-node', createItem.node);
                        createDiv.setAttribute('data-tab-id', levelid);
                        createDiv.innerHTML =
                            `<i class="fa ${createItem.icon}-${levelid}"></i><span> ${createItem.text}</span>`;
                        dropdownContent.appendChild(createDiv);
                    }

                    stages.forEach(stage => {
                        const icon = iconsMap[dropdownId]?.[stage.name.toLowerCase()] || 'fa-plus';
                        const stageDiv = document.createElement('li');
                        stageDiv.classList.add('drag-drawflow');
                        stageDiv.setAttribute('draggable', 'true');
                        stageDiv.setAttribute('ondragstart', 'drag(event)');
                        stageDiv.setAttribute('data-node', `${stage.name.toLowerCase()}-${levelid}`);
                        stageDiv.setAttribute('data-tab-id', levelid);
                        stageDiv.innerHTML = `<i class="fa ${icon}"></i><span> ${stage.name}</span>`;
                        dropdownContent.appendChild(stageDiv);
                    });
                }
            }



            // Initialize dropdowns on DOMContentLoaded
            document.addEventListener('DOMContentLoaded', () => {
                populateDropdowns('lead');
                populateDropdowns('deal');
                populateDropdowns('contract');
                populateDropdowns('invoice');
            });
        </script>
        <script>
            var users = {!! json_encode($allusers) !!};
            var departments = {!! json_encode($departments) !!};
            var designations = {!! json_encode($designations) !!};
            const workflowStoreUrl = '{{ route('workflow.store') }}';
            const workflowUpdateUrl = '{{ route('workflow.update', $workflow->id) }}';
            var workflow = {!! json_encode($workflow) !!};
            var workflow_actions = {!! json_encode($workflow_actions) !!};
            // Initialize the Drawflow editor
            var id = document.getElementById("drawflow");
            const editor = new Drawflow(id);
            editor.reroute = true; // Enable reroute connections
            editor.start(); // Start the editor

            // Process workflow actions into Drawflow format
            const dataToImport = {
                "drawflow": {
                    "Home": {
                        "data": {}
                    }
                }
            };

            // Minimum spacing between nodes
            const minSpacing = 30;
            const maxSpacing = 50;

            // Maximum area for placing nodes
            const maxX = 800; // Adjust as needed for canvas size
            const maxY = 600; // Adjust as needed for canvas size

            // List to track positions of placed nodes
            const placedNodes = [];
            let nodeIndex = 1;
            let position_y = 40;
            workflow_actions.forEach((action) => {
                const nodeName = action.node_id.split('-')[0] + (/[a-zA-Z]/.test(action.node_id.split('-')[1]) ? ' ' +
                    action.node_id.split('-')[1] : '');
                const nodeId = action.node_actual_id.split('node-')[1];
                const tabId = (() => {
                    const parts = action.node_id.split("-");
                    if (parts.length > 1) {
                        const suffix = parts[1];
                        const numericPart = parseInt(suffix, 10);

                        if (!isNaN(numericPart)) {
                            return numericPart;
                        } else {
                            const mapping = {
                                lead: 1,
                                deal: 2,
                                contract: 3,
                                invoice: 4,
                            };
                            return mapping[suffix.toLowerCase()] || null;
                        }
                    }
                    return null;
                })();
                const nodeHtml = `
                    <div>
                        <div class="title-box" style="flex; flex-direction:column;">
                            <section class="content" style="display:grid; grid-template-columns:100%; align-items:center;">
                                <div class="buttonss flex" style="display: flex;justify-content: end;gap: 5px;" bis_skin_checked="1"> 
                                    <svg class="svg-inline--fa fa-user-plus fa-w-20" style="cursor: pointer;" onclick="opensidebar(event,type='adduser')" aria-hidden="true" focusable="false" data-prefix="fa" data-icon="user-plus" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" data-fa-i2svg=""><path fill="currentColor" d="M624 208h-64v-64c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v64h-64c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h64v64c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16v-64h64c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16zm-400 48c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-41.6c0-74.2-60.2-134.4-134.4-134.4z"></path></svg><!-- <i class="fa fa-user-plus" style="cursor:pointer;" onclick="opensidebar(event,type='adduser')"></i> -->
                                    <svg class="svg-inline--fa fa-ellipsis-vertical fa-w-16" style="cursor: pointer;" onclick="opensidebar(event,type='addaction')" aria-hidden="true" focusable="false" data-prefix="fa" data-icon="ellipsis-vertical" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg=""><g><path fill="currentColor" d="M156.5,447.7l-12.6,29.5c-18.7-9.5-35.9-21.2-51.5-34.9l22.7-22.7C127.6,430.5,141.5,440,156.5,447.7z M40.6,272H8.5 c1.4,21.2,5.4,41.7,11.7,61.1L50,321.2C45.1,305.5,41.8,289,40.6,272z M40.6,240c1.4-18.8,5.2-37,11.1-54.1l-29.5-12.6 C14.7,194.3,10,216.7,8.5,240H40.6z M64.3,156.5c7.8-14.9,17.2-28.8,28.1-41.5L69.7,92.3c-13.7,15.6-25.5,32.8-34.9,51.5 L64.3,156.5z M397,419.6c-13.9,12-29.4,22.3-46.1,30.4l11.9,29.8c20.7-9.9,39.8-22.6,56.9-37.6L397,419.6z M115,92.4 c13.9-12,29.4-22.3,46.1-30.4l-11.9-29.8c-20.7,9.9-39.8,22.6-56.8,37.6L115,92.4z M447.7,355.5c-7.8,14.9-17.2,28.8-28.1,41.5 l22.7,22.7c13.7-15.6,25.5-32.9,34.9-51.5L447.7,355.5z M471.4,272c-1.4,18.8-5.2,37-11.1,54.1l29.5,12.6 c7.5-21.1,12.2-43.5,13.6-66.8H471.4z M321.2,462c-15.7,5-32.2,8.2-49.2,9.4v32.1c21.2-1.4,41.7-5.4,61.1-11.7L321.2,462z M240,471.4c-18.8-1.4-37-5.2-54.1-11.1l-12.6,29.5c21.1,7.5,43.5,12.2,66.8,13.6V471.4z M462,190.8c5,15.7,8.2,32.2,9.4,49.2h32.1 c-1.4-21.2-5.4-41.7-11.7-61.1L462,190.8z M92.4,397c-12-13.9-22.3-29.4-30.4-46.1l-29.8,11.9c9.9,20.7,22.6,39.8,37.6,56.9 L92.4,397z M272,40.6c18.8,1.4,36.9,5.2,54.1,11.1l12.6-29.5C317.7,14.7,295.3,10,272,8.5V40.6z M190.8,50 c15.7-5,32.2-8.2,49.2-9.4V8.5c-21.2,1.4-41.7,5.4-61.1,11.7L190.8,50z M442.3,92.3L419.6,115c12,13.9,22.3,29.4,30.5,46.1 l29.8-11.9C470,128.5,457.3,109.4,442.3,92.3z M397,92.4l22.7-22.7c-15.6-13.7-32.8-25.5-51.5-34.9l-12.6,29.5 C370.4,72.1,384.4,81.5,397,92.4z"></path><circle fill="currentColor" cx="256" cy="364" r="28"><animate attributeType="XML" repeatCount="indefinite" dur="2s" attributeName="r" values="28;14;28;28;14;28;"></animate><animate attributeType="XML" repeatCount="indefinite" dur="2s" attributeName="opacity" values="1;0;1;1;0;1;"></animate></circle><path fill="currentColor" opacity="1" d="M263.7,312h-16c-6.6,0-12-5.4-12-12c0-71,77.4-63.9,77.4-107.8c0-20-17.8-40.2-57.4-40.2c-29.1,0-44.3,9.6-59.2,28.7 c-3.9,5-11.1,6-16.2,2.4l-13.1-9.2c-5.6-3.9-6.9-11.8-2.6-17.2c21.2-27.2,46.4-44.7,91.2-44.7c52.3,0,97.4,29.8,97.4,80.2 c0,67.6-77.4,63.5-77.4,107.8C275.7,306.6,270.3,312,263.7,312z"><animate attributeType="XML" repeatCount="indefinite" dur="2s" attributeName="opacity" values="1;0;0;0;0;1;"></animate></path><path fill="currentColor" opacity="0" d="M232.5,134.5l7,168c0.3,6.4,5.6,11.5,12,11.5h9c6.4,0,11.7-5.1,12-11.5l7-168c0.3-6.8-5.2-12.5-12-12.5h-23 C237.7,122,232.2,127.7,232.5,134.5z"><animate attributeType="XML" repeatCount="indefinite" dur="2s" attributeName="opacity" values="0;0;1;1;0;0;"></animate></path></g></svg><!-- <i class="fa fa-ellipsis-vertical" style="cursor:pointer;" onclick="opensidebar(event,type='addaction')"></i> -->
                                </div>
                                <div>${nodeName.charAt(0).toUpperCase() + nodeName.slice(1)}</div>
                                <input type="text" hidden="" name="tabid" value="${tabId}">
                                <input type="text" hidden="" name="actionId" value="${action.id}">
                                <input type="text" hidden="" name="nodeid" value="${action.node_id}">
                                <input type="hidden" name="assigned-user" value='${action.assigned_users}' />
                                <input type="hidden" name="conditions" value='${action.applied_conditions}' />
                            </section>
                            <div class="labels" style="display: flex; gap: 2px; flex-wrap: wrap;" bis_skin_checked="1"></div>
                        </div>
                    </div>`;
                // const position = generateRandomPosition();

                dataToImport.drawflow.Home.data[nodeIndex] = {
                    id: nodeId, // Use node_id as id
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
                // placedNodes.push(position);
                nodeIndex++;
                position_y += 100;
            });
            editor.import(dataToImport);

            // Events!
            editor.on('nodeCreated', function(id) {
                // console.log("Node created " + id);
            })

            editor.on('nodeRemoved', function(id) {
                // console.log("Node removed " + id);
            })

            editor.on('nodeSelected', function(id) {
                // console.log("Node selecttted " + id);
            })

            editor.on('moduleCreated', function(name) {
                // console.log("Module Created " + name);
            })

            editor.on('moduleChanged', function(name) {
                // console.log("Module Changed " + name);
            })

            editor.on('connectionCreated', function(connection) {
                // console.log('Connection created');
                // console.log(connection);
            })

            editor.on('connectionRemoved', function(connection) {
                // console.log('Connection removed');
                // console.log(connection);
            })

            editor.on('mouseMove', function(position) {
                // console.log('Position mouse x:' + position.x + ' y:'+ position.y);
            })

            editor.on('nodeMoved', function(id) {
                // console.log("Node moved " + id);
            })

            editor.on('zoom', function(zoom) {
                // console.log('Zoom level ' + zoom);
            })

            editor.on('translate', function(position) {
                // console.log('Translate x:' + position.x + ' y:'+ position.y);
            })

            editor.on('addReroute', function(id) {
                // console.log("Reroute added " + id);
            })

            editor.on('removeReroute', function(id) {
                // console.log("Reroute removed " + id);
            })

            /* DRAG EVENT */

            /* Mouse and Touch Actions */

            var elements = document.getElementsByClassName('drag-drawflow');
            for (var i = 0; i < elements.length; i++) {
                elements[i].addEventListener('touchend', drop, false);
                elements[i].addEventListener('touchmove', positionMobile, false);
                elements[i].addEventListener('touchstart', drag, false);
            }

            var mobile_item_selec = '';
            var mobile_last_move = null;

            function positionMobile(ev) {
                mobile_last_move = ev;
            }

            function allowDrop(ev) {
                ev.preventDefault();
            }

            function drag(ev) {
                // console.log('drag', ev);

                if (ev.type === "touchstart") {
                    mobile_item_selec = ev.target.closest(".drag-drawflow").getAttribute('data-node');
                } else {
                    ev.dataTransfer.setData("node", ev.target.getAttribute('data-node'));
                }
            }

            function drop(ev) {
                ev.preventDefault();
                var appen_data = '';

                if (ev.type === "touchend") {
                    var parentdrawflow = document.elementFromPoint(
                        mobile_last_move.touches[0].clientX,
                        mobile_last_move.touches[0].clientY
                    ).closest("#drawflow");

                    if (parentdrawflow != null) {
                        appen_data = `
            <div>
              <div class="title-box">
                                <div>${mobile_item_selec}</div>
              </div>
            </div>`;
                        addNodeToDrawFlow(mobile_item_selec, mobile_last_move.touches[0].clientX, mobile_last_move.touches[0]
                            .clientY, appen_data);
                    }
                    mobile_item_selec = '';
                } else {
                    var data_node = ev.dataTransfer.getData("node");
                    var draggedElement = document.querySelector(`[data-node="${data_node}"]`);
                    if (draggedElement) {
                        console.log(draggedElement);

                        var tabId = draggedElement.getAttribute("data-tab-id");
                        // var data_tabparentid = draggedElement.getAttribute("data-parent-tab-id");
                        var completeData = {
                            id: draggedElement.id,
                            nodeData: draggedElement.getAttribute("data-node"),
                            text: draggedElement.textContent.trim(),
                        };

                        var svgElement = draggedElement.querySelector('svg');
                        var svgContent = svgElement ? svgElement.outerHTML : '';
                        var data = completeData.text;

                        appen_data = `
                    <div>
                    <div class="title-box" style="flex; flex-direction:column;">
                        <section class="content" style="display:grid; grid-template-columns:100%; align-items:center;">
                            <div class='buttonss flex' style="display: flex;justify-content: end;gap: 5px;">
                                <i class="fa fa-user-plus" style="cursor:pointer;" onclick="opensidebar(event,type='adduser')"></i>
                                <i class="fa fa-ellipsis-vertical" style="cursor:pointer;" onclick="opensidebar(event,type='addaction')"></i>
                            </div>
                            <div> ${svgContent} ${data}</div>

                            <input type="text" hidden name="tabid" value="${tabId}">
                            <input type="text" hidden name="nodeid" value="${data_node}">
                        </section>
                        <div class="labels" style="display: flex; gap: 2px; flex-wrap: wrap;"></div>
                    </div>
                    </div>`;
                    } else {
                        appen_data = `
                    <div>
                    <div class="title-box">
                        <div>${data}</div>
                    </div>
                    </div>`;
                    }

                    addNodeToDrawFlow(data, ev.clientX, ev.clientY, appen_data); // Pass tabId to addNodeToDrawFlow
                }
            }



            function addNodeToDrawFlow(name, pos_x, pos_y, appen_data) {

                if (editor.editor_mode === 'fixed') {
                    return false;
                }
                pos_x = pos_x * (editor.precanvas.clientWidth / (editor.precanvas.clientWidth * editor.zoom)) - (editor
                    .precanvas.getBoundingClientRect().x * (editor.precanvas.clientWidth / (editor.precanvas.clientWidth *
                        editor.zoom)));
                pos_y = pos_y * (editor.precanvas.clientHeight / (editor.precanvas.clientHeight * editor.zoom)) - (editor
                    .precanvas.getBoundingClientRect().y * (editor.precanvas.clientHeight / (editor.precanvas.clientHeight *
                        editor.zoom)));
                // editor.addNode(name,1,1,pos_x,pos_y, name, {},appen_data, {inputs: {input_1: {position: "top"}},outputs: {output_1: {position: "bottom"}}});

                editor.addNode(name, 1, 1, pos_x, pos_y, name, {}, appen_data);
            }


            var transform = '';

            function showpopup(e) {
                e.target.closest(".drawflow-node").style.zIndex = "9999";
                e.target.children[0].style.display = "block";
                transform = editor.precanvas.style.transform;
                editor.precanvas.style.transform = '';
                editor.precanvas.style.left = editor.canvas_x + 'px';
                editor.precanvas.style.top = editor.canvas_y + 'px';
                editor.editor_mode = "fixed";

            }

            function closemodal(e) {
                e.target.closest(".drawflow-node").style.zIndex = "2";
                e.target.parentElement.parentElement.style.display = "none";
                editor.precanvas.style.transform = transform;
                editor.precanvas.style.left = '0px';
                editor.precanvas.style.top = '0px';
                editor.editor_mode = "edit";
            }


            function changeModule(event) {
                var all = document.querySelectorAll(".menu ul li");
                for (var i = 0; i < all.length; i++) {
                    all[i].classList.remove('selected');
                }
                event.target.classList.add('selected');
            }

            function changeMode(option) {

                //console.log(lock.id);
                if (option == 'lock') {
                    lock.style.display = 'none';
                    unlock.style.display = 'block';
                } else {
                    lock.style.display = 'block';
                    unlock.style.display = 'none';
                }
                actionHeading.innerHTML += clickedNode.querySelector('.drawflow_content_node .title-box div:nth-of-type(2)')
                    .textContent;

            }
            // const drawflowNodes = document.querySelectorAll('.drawflow-node');
            // drawflowNodes.forEach(node => {
            //     node.addEventListener('click', function(event) {
            //         console.log('Clicked element ID:', event.target.id);
            //     });
            // });
            // function opensidebar(e, type) {
            //     // console.log(JSON.parse(JSON.stringify(this.drawflow)));
            //     const sidebar = document.getElementById("rightsidebar");
            //     const closeSidebarBtn = document.getElementById("closeSidebarBtn");
            //     const actionContent = sidebar.querySelector('.action-content');

            //     sidebar.classList.add("active");
            //     closeSidebarBtn.addEventListener("click", () => {
            //         sidebar.classList.remove("active");
            //     });

            //     let clickedNode = e.target.closest('.drawflow-node');
            //     if (clickedNode) {
            //         console.log(clickedNode);

            //         actionContent.innerHTML = '';
            //         const actionHeading = document.createElement('h2');
            //         const actionid = document.createElement('input');
            //         let nodeId = clickedNode.id;
            //         actionid.type = 'hidden';
            //         actionid.value = nodeId;
            //         actionid.name = 'actionid';

            //         const svgElement = clickedNode.querySelector('.drawflow_content_node .title-box div:nth-of-type(2) svg');
            //         if (svgElement) {
            //             // svgElement.style.marginRight = '10px';
            //             actionHeading.appendChild(svgElement.cloneNode(true));
            //         }

            //         actionHeading.innerHTML += clickedNode.querySelector('.drawflow_content_node .title-box div:nth-of-type(2)')
            //             .textContent;

            //         actionContent.innerHTML = '';
            //         actionContent.appendChild(actionid);
            //         actionContent.appendChild(actionHeading);
            //         if (type == 'adduser') {

            //             const formelement = document.createElement('form');
            //             formelement.id = 'actionform';
            //             actionContent.appendChild(formelement);

            //             // User select
            //             const userSelect = document.createElement('select');
            //             userSelect.id = 'userSelect';
            //             userSelect.multiple = true;
            //             userSelect.classList.add('form-control', 'mt-2', 'js-example-basic-multiple');

            //             // Department select with placeholder
            //             const departmentSelect = document.createElement('select');
            //             departmentSelect.id = 'departmentSelect';
            //             departmentSelect.classList.add('form-control', 'mt-2');
            //             const departmentPlaceholder = document.createElement('option');
            //             departmentPlaceholder.value = '';
            //             departmentPlaceholder.text = 'Select Department';
            //             departmentPlaceholder.disabled = true;
            //             departmentPlaceholder.selected = true;
            //             departmentSelect.appendChild(departmentPlaceholder);

            //             // Designation select with placeholder
            //             const designationSelect = document.createElement('select');
            //             designationSelect.id = 'designationSelect';
            //             designationSelect.classList.add('form-control', 'mt-2', 'mb-2');
            //             const designationPlaceholder = document.createElement('option');
            //             designationPlaceholder.value = '';
            //             designationPlaceholder.text = 'Select Designation';
            //             designationPlaceholder.disabled = true;
            //             designationPlaceholder.selected = true;
            //             designationSelect.appendChild(designationPlaceholder);

            //             // Populate selects
            //             users.forEach(user => {
            //                 const option = document.createElement('option');
            //                 option.value = user.id;
            //                 option.text = user.name;
            //                 userSelect.appendChild(option);
            //             });

            //             departments.forEach(department => {
            //                 const option = document.createElement('option');
            //                 option.value = department.id;
            //                 option.text = department.name;
            //                 departmentSelect.appendChild(option);
            //             });

            //             designations.forEach(designation => {
            //                 const option = document.createElement('option');
            //                 option.value = designation.id;
            //                 option.text = designation.name;
            //                 designationSelect.appendChild(option);
            //             });

            //             // Notify via Email and Notification checkboxes
            //             const notifyDiv = document.createElement('div');
            //             notifyDiv.classList.add('form-group', 'mt-3');

            //             const emailNotifyLabel = document.createElement('label');
            //             emailNotifyLabel.innerText = 'Notify via Email';
            //             emailNotifyLabel.style.display = 'block';

            //             const emailNotifyCheckbox = document.createElement('input');
            //             emailNotifyCheckbox.type = 'checkbox';
            //             emailNotifyCheckbox.name = 'notify_email';
            //             emailNotifyCheckbox.classList.add('mr-2');

            //             const notificationLabel = document.createElement('label');
            //             notificationLabel.innerText = 'Notify via Notification';
            //             notificationLabel.style.display = 'block';

            //             const notificationCheckbox = document.createElement('input');
            //             notificationCheckbox.type = 'checkbox';
            //             notificationCheckbox.name = 'notify_notification';
            //             notificationCheckbox.classList.add('mr-2');

            //             const assignedUserInput = clickedNode.querySelector('input[name="assigned-user"]');
            //             if (assignedUserInput) {
            //                 const assignedData = JSON.parse(assignedUserInput.value);
            //                 assignedData.forEach(item => {
            //                     if (item.type === 'user') {
            //                         const userOption = userSelect.querySelector(`option[value="${item.id}"]`);
            //                         if (userOption) userOption.selected = true;
            //                     } else if (item.type === 'department') {
            //                         departmentSelect.value = item.id;
            //                     } else if (item.type === 'designation') {
            //                         designationSelect.value = item.id;
            //                     } else if (item.type == 'notify_email') {
            //                         emailNotifyCheckbox.checked = item.id;
            //                     } else if (item.type == 'notify_notification') {
            //                         notificationCheckbox.checked = item.id;
            //                     }
            //                 });
            //             }
            //             emailNotifyLabel.prepend(emailNotifyCheckbox);
            //             notificationLabel.prepend(notificationCheckbox);
            //             // Append selects and button to the form
            //             formelement.appendChild(departmentSelect);
            //             formelement.appendChild(designationSelect);
            //             formelement.appendChild(userSelect);
            //             notifyDiv.appendChild(emailNotifyLabel);
            //             notifyDiv.appendChild(notificationLabel);
            //             formelement.appendChild(notifyDiv);
            //             // Create and append the button
            //             const buttondiv = document.createElement('div');
            //             buttondiv.id = 'removeblock';
            //             formelement.appendChild(buttondiv);

            //             const getValuesButton = document.createElement('button');
            //             getValuesButton.type = 'button';
            //             getValuesButton.innerText = 'Get Selected Values';
            //             getValuesButton.classList.add('btn', 'btn-primary', 'mt-4');
            //             getValuesButton.onclick = logSelectedValues;
            //             buttondiv.appendChild(getValuesButton);

            function opensidebar(e, type) {
                const sidebar = document.getElementById("rightsidebar");
                const closeSidebarBtn = document.getElementById("closeSidebarBtn");
                const actionContent = sidebar.querySelector('.action-content');

                sidebar.classList.add("active");
                closeSidebarBtn.addEventListener("click", () => {
                    sidebar.classList.remove("active");
                });

                let clickedNode = e.target.closest('.drawflow-node');
                if (clickedNode) {
                    actionContent.innerHTML = '';
                    const actionHeading = document.createElement('h2');
                    const actionid = document.createElement('input');
                    let nodeId = clickedNode.id;
                    actionid.type = 'hidden';
                    actionid.value = nodeId;
                    actionid.name = 'actionid';

                    const svgElement = clickedNode.querySelector('.drawflow_content_node .title-box div:nth-of-type(2) svg');
                    if (svgElement) {
                        actionHeading.appendChild(svgElement.cloneNode(true));
                    }
                    actionHeading.innerHTML += clickedNode.querySelector('.drawflow_content_node .title-box div:nth-of-type(2)')
                        .textContent;
                    actionContent.appendChild(actionid);
                    actionContent.appendChild(actionHeading);
                    const formelement = document.createElement('form');
                    formelement.id = 'actionform';
                    actionContent.appendChild(formelement);

                    const actionSelectContainer = document.createElement('div');
                    const actionSelect = document.createElement('select');
                    actionSelect.classList.add('form-control', 'mt-2');

                    if (type == 'adduser') {
                        renderAddUserForm(formelement, clickedNode);
                    } else if (type === 'addaction') {
                        const placeholderOption = document.createElement('option');
                        placeholderOption.text = 'Choose Action';
                        placeholderOption.value = '';
                        placeholderOption.disabled = true;
                        placeholderOption.selected = true;
                        actionSelect.appendChild(placeholderOption);

                        const actions = [{
                                value: 'adduser',
                                label: 'Add User'
                            },
                            {
                                value: 'send_email',
                                label: 'Send Email'
                            },
                            {
                                value: 'send_notification',
                                label: 'Send Notification'
                            },
                            {
                                value: 'send_approval',
                                label: 'Send Approval'
                            },
                        ];
                        actions.forEach(action => {
                            const option = document.createElement('option');
                            option.value = action.value;
                            option.text = action.label;
                            actionSelect.appendChild(option);
                        });
                        actionSelect.addEventListener('change', (event) => handleActionChange(event.target.value, formelement,
                            clickedNode));

                        actionSelectContainer.appendChild(actionSelect);
                    }
                    formelement.appendChild(actionSelectContainer);
                }
            }


            function handleActionChange(actionType, formelement, clickedNode) {
                const actionSelect = formelement.querySelector('select.form-control');
                const actionSelectContainer = actionSelect.parentElement;
                formelement.innerHTML = '';
                formelement.appendChild(actionSelectContainer);
                switch (actionType) {
                    case 'adduser':
                        renderAddUserForm(formelement, clickedNode);
                        break;
                    case 'send_email':
                        renderAction2Form(formelement, clickedNode, 'send_email');
                        break;
                    case 'send_notification':
                        renderAction2Form(formelement, clickedNode, 'send_notification');
                        break;
                    case 'send_approval':
                        renderAction2Form(formelement, clickedNode, 'send_approval');
                        break;
                    default:
                        break;
                }
            }

            function renderAddUserForm(formelement, clickedNode) {
                const userSelect = document.createElement('select');
                userSelect.id = 'userSelect';
                userSelect.multiple = true;
                userSelect.classList.add('form-control', 'mt-2', 'js-example-basic-multiple');

                const departmentSelect = document.createElement('select');
                departmentSelect.id = 'departmentSelect';
                departmentSelect.classList.add('form-control', 'mt-2');
                const departmentPlaceholder = document.createElement('option');
                departmentPlaceholder.value = '';
                departmentPlaceholder.text = 'Select Department';
                departmentPlaceholder.disabled = true;
                departmentPlaceholder.selected = true;
                departmentSelect.appendChild(departmentPlaceholder);

                const designationSelect = document.createElement('select');
                designationSelect.id = 'designationSelect';
                designationSelect.classList.add('form-control', 'mt-2', 'mb-2');
                const designationPlaceholder = document.createElement('option');
                designationPlaceholder.value = '';
                designationPlaceholder.text = 'Select Designation';
                designationPlaceholder.disabled = true;
                designationPlaceholder.selected = true;
                designationSelect.appendChild(designationPlaceholder);

                // Populate selects
                users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.text = user.name;
                    userSelect.appendChild(option);
                });
                departments.forEach(department => {
                    const option = document.createElement('option');
                    option.value = department.id;
                    option.text = department.name;
                    departmentSelect.appendChild(option);
                });
                designations.forEach(designation => {
                    const option = document.createElement('option');
                    option.value = designation.id;
                    option.text = designation.name;
                    designationSelect.appendChild(option);
                });
                const assignedUserInput = clickedNode.querySelector('input[name="assigned-user"]');
                if (assignedUserInput) {
                    const decodedUserValue = assignedUserInput.value.replace(/&quot;/g, '"');
                    let assignedData = JSON.parse(decodedUserValue);
                    // let savedData2 = typeof assignedData == "object" ? JSON.stringify(assignedData) : `${assignedData}`;
                    // let parsedData = JSON.parse(savedData2);
                    while (typeof assignedData == "string") {
                        assignedData = JSON.parse(assignedData);
                    }
                    let parsedData = assignedData;
                    // console.log(assignedData,savedData2,parsedData);
                    parsedData.forEach(item => {
                        if (item.type === 'user') {
                            const userOption = userSelect.querySelector(`option[value="${item.id}"]`);
                            if (userOption) userOption.selected = true;
                        } else if (item.type === 'department') {
                            departmentSelect.value = item.id;
                        } else if (item.type === 'designation') {
                            designationSelect.value = item.id;
                        } else if (item.type == 'notify_email') {
                            emailNotifyCheckbox.checked = item.id;
                        } else if (item.type == 'notify_notification') {
                            notificationCheckbox.checked = item.id;
                        }
                    });
                }
                formelement.appendChild(departmentSelect);
                formelement.appendChild(designationSelect);
                formelement.appendChild(userSelect);
                const buttondiv = document.createElement('div');
                buttondiv.id = 'removeblock';
                formelement.appendChild(buttondiv);
                const getValuesButton = document.createElement('button');
                getValuesButton.type = 'button';
                getValuesButton.innerText = 'Assign Users';
                getValuesButton.classList.add('btn', 'btn-primary', 'mt-4');
                getValuesButton.onclick = logSelectedValues;
                buttondiv.appendChild(getValuesButton);
                $('.js-example-basic-multiple').select2({
                    placeholder: "Select Users"
                });
                // clickedNode.appendChild(hiddenInput);
            }

            function renderAction2Form(formelement, clickedNode, action) {
                const conditionsContainer = document.createElement('div');
                conditionsContainer.id = `conditions-container-${action}`; // Unique ID per action
                conditionsContainer.style.marginLeft = '5px';
                conditionsContainer.style.width = '100%';
                conditionsContainer.dataset.action = action;
                formelement.appendChild(conditionsContainer);

                const addConditionButton = document.createElement('button');
                addConditionButton.type = 'button';
                addConditionButton.innerText = 'Add Condition';
                addConditionButton.classList.add('btn', 'btn-secondary', 'mt-3');
                addConditionButton.addEventListener('click', () => addConditionRow(conditionsContainer, clickedNode));
                formelement.appendChild(addConditionButton);

                const brele = document.createElement('br');
                formelement.appendChild(brele);

                // Add "Submit" button
                const submitButton = document.createElement('button');
                submitButton.type = 'button';
                submitButton.innerText = 'Submit';
                submitButton.classList.add('btn', 'btn-primary', 'mt-4');
                submitButton.addEventListener('click', () => submitConditions(conditionsContainer, clickedNode));
                formelement.appendChild(submitButton);

                // Retrieve saved conditions
                const hiddenInput = clickedNode.querySelector('input[name="conditions"]');
                if (hiddenInput) {
                    const decodedValue = hiddenInput.value.replace(/&quot;/g, '"');
                    let savedData = JSON.parse(decodedValue);
                    // let savedData2 = typeof savedData === "object" ? JSON.stringify(savedData) : `${savedData}`;
                    // let savedData2 = `${savedData}`;
                    while (typeof savedData == "string") {
                        savedData = JSON.parse(savedData);
                    }
                    let parsedData = savedData;
                    // console.log(savedData,'saveddata');

                    const savedConditions = Array.isArray(parsedData?.conditions) ? parsedData.conditions : [];
                    const conditionsForAction = savedConditions.find(savedCondition => savedCondition.action === action);

                    if (conditionsForAction) {
                        // console.log('Conditions for action:', conditionsForAction.conditions);

                        if (!conditionsContainer.dataset.initialized) {
                            initializeSavedConditions(conditionsContainer, clickedNode, conditionsForAction.conditions);
                            conditionsContainer.dataset.initialized = true;
                        }
                    } else {
                        console.log('No saved conditions for action:', action);
                    }
                } else {
                    console.log('No hidden input found for conditions.');
                }
            }



            function addConditionRow(conditionsContainer, clickedNode) {
                let tabidValue = clickedNode.querySelector('.title-box input[name="tabid"]').value;
                const conditionRow = document.createElement('div');
                conditionRow.classList.add('condition-row', 'mt-2', 'd-flex', 'flex-column', 'align-items-center');
                conditionRow.style.gap = '10px';

                // Add select for fields
                const conditionSelect = document.createElement('select');
                conditionSelect.classList.add('form-control', 'mr-2');
                // const columns = tabidValue == 1 ? leadsColumn : dealColumn;
                let columns;

                switch (tabidValue) {
                    case '1':
                        columns = leadsColumn;
                        break;
                    case '2':
                        columns = dealColumn;
                        break;
                    case '3':
                        columns = contractColumn;
                        break;
                    case '4':
                        columns = invoiceColumn;
                        break;
                    default:
                        columns = invoiceColumn;
                        break;
                }

                columns.forEach(option => {
                    const opt = document.createElement('option');
                    opt.value = option;
                    opt.textContent = option;
                    conditionSelect.appendChild(opt);
                });
                conditionRow.appendChild(conditionSelect);

                // Add operator dropdown
                const operatorDropdown = createOperatorDropdown();
                conditionRow.appendChild(operatorDropdown);

                // Add value input
                const valueInput = document.createElement('input');
                valueInput.type = 'text';
                valueInput.classList.add('form-control', 'mr-2');
                valueInput.placeholder = 'Enter value';
                conditionRow.appendChild(valueInput);

                // Add remove button
                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.innerText = 'Remove';
                removeButton.style.position = 'relative';
                removeButton.style.left = '35%';
                removeButton.classList.add('btn', 'btn-danger', 'ml-2');
                removeButton.addEventListener('click', () => conditionsContainer.removeChild(conditionRow));
                conditionRow.appendChild(removeButton);

                // Append the new condition row to the container
                conditionsContainer.appendChild(conditionRow);
            }

            function initializeSavedConditions(conditionsContainer, clickedNode, conditionsForAction) {

                let tabidValue = clickedNode.querySelector('.title-box input[name="tabid"]').value;
                conditionsForAction.forEach(savedCondition => {
                    const conditionRow = document.createElement('div');
                    conditionRow.classList.add('condition-row', 'mt-2', 'd-flex', 'flex-column', 'align-items-center');
                    conditionRow.style.gap = '10px';

                    // Add select for fields
                    const conditionSelect = document.createElement('select');
                    conditionSelect.classList.add('form-control', 'mr-2');
                    // const columns = conditionsContainer.dataset.action === "1" ? leadsColumn : dealColumn;
                    let columns;

                    switch (tabidValue) {
                        case '1':
                            columns = leadsColumn;
                            break;
                        case '2':
                            columns = dealColumn;
                            break;
                        case '3':
                            columns = contractColumn;
                            break;
                        case '4':
                            columns = invoiceColumn;
                            break;
                        default:
                            columns = invoiceColumn;
                            break;
                    }

                    columns.forEach(option => {
                        const opt = document.createElement('option');
                        opt.value = option;
                        opt.textContent = option;
                        if (savedCondition.field === option) {
                            opt.selected = true;
                        }
                        conditionSelect.appendChild(opt);
                    });
                    conditionRow.appendChild(conditionSelect);

                    // Add operator dropdown
                    const operatorDropdown = createOperatorDropdown(savedCondition.operator);
                    conditionRow.appendChild(operatorDropdown);

                    // Add value input
                    const valueInput = document.createElement('input');
                    valueInput.type = 'text';
                    valueInput.classList.add('form-control', 'mr-2');
                    valueInput.placeholder = 'Enter value';
                    if (savedCondition.value) {
                        valueInput.value = savedCondition.value;
                    }
                    conditionRow.appendChild(valueInput);

                    // Add remove button
                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.innerText = 'Remove';
                    removeButton.style.position = 'relative';
                    removeButton.style.left = '35%';
                    removeButton.classList.add('btn', 'btn-danger', 'ml-2');
                    removeButton.addEventListener('click', () => conditionsContainer.removeChild(conditionRow));
                    conditionRow.appendChild(removeButton);

                    // Append the new condition row to the container
                    conditionsContainer.appendChild(conditionRow);
                });
            }

            function createOperatorDropdown(selectedOperator = '') {
                const operatorDropdown = document.createElement('div');
                operatorDropdown.classList.add('dropdown', 'mr-2');

                const operatorButton = document.createElement('button');
                operatorButton.classList.add('btn', 'btn-light', 'dropdown-toggle');
                operatorButton.type = 'button';
                operatorButton.dataset.toggle = 'dropdown';
                operatorButton.setAttribute('aria-haspopup', 'true');
                operatorButton.setAttribute('aria-expanded', 'false');
                operatorButton.innerHTML = selectedOperator ? getOperatorIcon(selectedOperator) : 'Select Operator';
                operatorButton.dataset.value = selectedOperator;

                const operatorMenu = document.createElement('div');
                operatorMenu.classList.add('dropdown-menu');
                const operators = [{
                        value: '<',
                        icon: '<i class="fa fa-less-than"></i>'
                    },
                    {
                        value: '>',
                        icon: '<i class="fa fa-greater-than"></i>'
                    },
                    {
                        value: '=',
                        icon: '<i class="fa fa-equals"></i>'
                    },
                    {
                        value: '!=',
                        icon: '<i class="fa fa-not-equal"></i>'
                    }
                ];

                operators.forEach(op => {
                    const operatorItem = document.createElement('a');
                    operatorItem.href = '#';
                    operatorItem.classList.add('dropdown-item');
                    operatorItem.dataset.value = op.value;
                    operatorItem.innerHTML = op.icon;
                    operatorItem.addEventListener('click', (event) => {
                        event.preventDefault();
                        operatorButton.innerHTML = op.icon;
                        operatorButton.dataset.value = op.value;
                    });
                    operatorMenu.appendChild(operatorItem);
                });

                operatorDropdown.appendChild(operatorButton);
                operatorDropdown.appendChild(operatorMenu);

                return operatorDropdown;
            }

            function getOperatorIcon(operator) {
                const operators = {
                    '<': '<i class="fa fa-less-than"></i>',
                    '>': '<i class="fa fa-greater-than"></i>',
                    '=': '<i class="fa fa-equals"></i>',
                    '!=': '<i class="fa fa-not-equal"></i>',
                };
                return operators[operator] || 'Select Operator';
            }

            function submitConditions(conditionsContainer, clickedNode) {
                const sidebar = document.getElementById("rightsidebar");
                sidebar.classList.remove("active");

                const action = conditionsContainer.dataset.action;
                const conditions = [];

                // Collect conditions for this specific action
                conditionsContainer.querySelectorAll('.condition-row').forEach(row => {
                    const condition = {
                        field: row.querySelector('select:nth-child(1)').value,
                        operator: row.querySelector('.dropdown-toggle').dataset.value,
                        value: row.querySelector('input').value
                    };
                    conditions.push(condition);
                });

                // Prepare the result object for the specific action
                const result = {
                    action: action, // Store the action this condition is associated with
                    conditions: conditions
                };
                // Check if the hidden input exists for this action
                let hiddenInput = clickedNode.querySelector(`input[name="conditions"]`);
                if (hiddenInput) {

                    // If the hidden input already exists, update its value
                    const decodedValue = hiddenInput.value.replace(/&quot;/g, '"');
                    // const savedData = JSON.parse(hiddenInput.value);
                    let savedData = JSON.parse(decodedValue);
                    // let savedData2 = typeof savedData == "object" ? JSON.stringify(savedData) : `${savedData}`;
                    // let parsedData = JSON.parse(savedData2);
                    while (typeof savedData == "string") {
                        savedData = JSON.parse(savedData);
                    }
                    let parsedData = savedData;
                    // // Ensure `savedData.conditions` exists and is an array
                    const savedConditions = Array.isArray(parsedData?.conditions) ? parsedData.conditions : [];
                    const existingAction = savedConditions.find(savedCondition => savedCondition.action === action);

                    // const existingAction = savedData.conditions.find(cond => cond.action === action);

                    if (existingAction) {
                        // Update the existing action's conditions
                        existingAction.conditions = conditions;
                    } else {
                        savedData.conditions.push(result);
                    }
                    hiddenInput.value = JSON.stringify(parsedData);
                } else {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'conditions';
                    hiddenInput.value = JSON.stringify({
                        conditions: [result]
                    });
                    clickedNode.appendChild(hiddenInput);
                }
            }

            function renderAction3Form(formelement, clickedNode) {
                const action3Textarea = document.createElement('textarea');
                action3Textarea.classList.add('form-control', 'mt-2');
                action3Textarea.placeholder = 'Action 3 Textarea';
                formelement.appendChild(action3Textarea);
                const buttondiv = document.createElement('div');
                buttondiv.id = 'removeblock';
                formelement.appendChild(buttondiv);
                const getValuesButton = document.createElement('button');
                getValuesButton.type = 'button';
                getValuesButton.innerText = 'Submit';
                getValuesButton.classList.add('btn', 'btn-primary', 'mt-4');
                getValuesButton.onclick = logSelectedValues;
                buttondiv.appendChild(getValuesButton);
            }

            function logSelectedValues(e) {
                const sidebar = document.getElementById("rightsidebar");
                sidebar.classList.remove("active");
                const onselectCont = document.querySelector('.action-content');
                const userSelect = document.getElementById('userSelect');
                const departmentSelect = document.getElementById('departmentSelect');
                const designationSelect = document.getElementById('designationSelect');
                const actionId = onselectCont.querySelector('input[name="actionid"]').value;
                const targetElement = document.querySelector(`.drawflow #${actionId}`);
                const targetTitleBox = document.querySelector(`.drawflow #${actionId} .title-box section`);
                if (!targetElement) {
                    console.error("Target element with specified ID not found.");
                    return;
                }
                const selectedUsers = Array.from(userSelect.selectedOptions).map(option => ({
                    type: 'user',
                    id: option.value
                }));

                const selectedDepartment = {
                    type: 'department',
                    id: departmentSelect.options[departmentSelect.selectedIndex].value
                };

                const selectedDesignation = {
                    type: 'designation',
                    id: designationSelect.options[designationSelect.selectedIndex].value
                };
                const notifyActions = getNotifyActions();
                const assignedIdsArray = [
                    ...selectedUsers,
                    selectedDepartment,
                    selectedDesignation,
                    ...notifyActions
                ].filter(item => item.id); // Remove empty IDs
                
                let assignedUserInput = targetTitleBox.querySelector('input[name="assigned-user"]');
                console.log(targetTitleBox,'titlebox',assignedUserInput);
                if (!assignedUserInput) {
                    assignedUserInput = document.createElement('input');
                    assignedUserInput.type = 'hidden';
                    assignedUserInput.name = 'assigned-user';
                    targetTitleBox.appendChild(assignedUserInput);
                }
                assignedUserInput.value = JSON.stringify(assignedIdsArray);
                let labelsContainer = targetElement.querySelector('.labels');
                if (!labelsContainer) {
                    labelsContainer = document.createElement('div');
                    labelsContainer.className = 'labels';
                    labelsContainer.style.margin = '0px 0px -25px';
                    labelsContainer.style.display = 'flex';
                    labelsContainer.style.flexWrap = 'wrap';
                    labelsContainer.style.position = 'relative';
                    labelsContainer.style.top = '-10px';
                    targetElement.appendChild(labelsContainer);
                } else {
                    labelsContainer.innerHTML = ''; // Clear existing labels
                }

                // Append department label
                if (selectedDepartment.id) {
                    const departmentLabel = document.createElement('div');
                    departmentLabel.className = 'label';
                    departmentLabel.style.backgroundColor = 'green';
                    departmentLabel.style.color = '#fff';
                    departmentLabel.style.width = 'fit-content';
                    departmentLabel.style.padding = '0px 5px';
                    departmentLabel.style.marginRight = '5px';
                    departmentLabel.innerText = departmentSelect.options[departmentSelect.selectedIndex].text;
                    labelsContainer.appendChild(departmentLabel);
                }

                // Append designation label
                if (selectedDesignation.id) {
                    const designationLabel = document.createElement('div');
                    designationLabel.className = 'label';
                    designationLabel.style.backgroundColor = 'purple';
                    designationLabel.style.color = '#fff';
                    designationLabel.style.width = 'fit-content';
                    designationLabel.style.padding = '0px 5px';
                    designationLabel.style.marginRight = '5px';
                    designationLabel.innerText = designationSelect.options[designationSelect.selectedIndex].text;
                    labelsContainer.appendChild(designationLabel);
                }

                // Append user labels
                selectedUsers.forEach(user => {
                    const userLabel = document.createElement('div');
                    userLabel.className = 'label';
                    userLabel.style.backgroundColor = 'blue';
                    userLabel.style.color = '#fff';
                    userLabel.style.width = 'fit-content';
                    userLabel.style.padding = '0px 5px';
                    userLabel.style.marginRight = '5px';
                    userLabel.innerText = userSelect.querySelector(`option[value="${user.id}"]`).text;
                    labelsContainer.appendChild(userLabel);
                });
            }

            function getNotifyActions() {
                const checkboxes = document.querySelectorAll('input[type="checkbox"]');
                return Array.from(checkboxes)
                    .filter(checkbox => checkbox.checked)
                    .map(checkbox => ({
                        type: checkbox.name,
                        id: true
                    }));
            }
        </script>
    @endsection
