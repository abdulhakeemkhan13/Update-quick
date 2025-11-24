@extends('layouts.admin')

@section('page-title')
    {{ __('Manage Workflow') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create Workflow') }}</li>
@endsection


@section('action-btn')
    <div class="tabs">
        {{-- <div class="tab active-tab" onclick="openTab(1)">CRM</div>
        <div class="tab" onclick="openTab(2)">HRM</div>
        <div class="tab" onclick="openTab(3)">Project</div>
        <div class="tab" onclick="openTab(4)">Accounts</div> --}}
        {{-- <div class="btn-export"
            onclick="Swal.fire({ title: 'Export',
                        html: '<pre><code>'+JSON.stringify(editor.export(), null,4)+'</code></pre>'
                        })">
            Save</div> --}}
        <div class="btn-export" onclick="console.log(JSON.stringify(editor.export(), null, 4))">
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


            // function populateStages(tabId) {
            //     const tabContent = document.querySelector(`.tab-content[data-index="${tabId}"]`);
            //     if (!tabContent) return;

            //     tabContent.innerHTML = '';

            //     if (tabId == 1) {
            //         const createLeadDiv = document.createElement('div');
            //         createLeadDiv.classList.add('drag-drawflow');
            //         createLeadDiv.setAttribute('draggable', 'true');
            //         createLeadDiv.setAttribute('ondragstart', 'drag(event)');
            //         createLeadDiv.setAttribute('data-node', 'create-lead');
            //         createLeadDiv.innerHTML = `<i class="fa fa-plus"></i><span> Create Lead</span>`;
            //         tabContent.appendChild(createLeadDiv);
            //         leadStages.forEach(stage => {
            //             const icon = iconsMap.lead[stage.name.toLowerCase()] || "fa-plus";
            //             const div = document.createElement('div');
            //             div.classList.add('drag-drawflow');
            //             div.setAttribute('draggable', 'true');
            //             div.setAttribute('ondragstart', 'drag(event)');
            //             div.setAttribute('data-node', stage.name.toLowerCase());
            //             div.innerHTML = `<i class="fa ${icon}"></i><span> ${stage.name}</span>`;
            //             tabContent.appendChild(div);
            //         });
            //     } else if (tabId == 2) {
            //         const createDealDiv = document.createElement('div');
            //         createDealDiv.classList.add('drag-drawflow');
            //         createDealDiv.setAttribute('draggable', 'true');
            //         createDealDiv.setAttribute('ondragstart', 'drag(event)');
            //         createDealDiv.setAttribute('data-node', 'create-deal');
            //         createDealDiv.innerHTML = `<i class="fa fa-plus"></i><span> Create Deal</span>`;
            //         tabContent.appendChild(createDealDiv);
            //         dealStages.forEach(stage => {
            //             const icon = iconsMap.deal[stage.name.toLowerCase()] || "fa-plus";
            //             const div = document.createElement('div');
            //             div.classList.add('drag-drawflow');
            //             div.setAttribute('draggable', 'true');
            //             div.setAttribute('ondragstart', 'drag(event)');
            //             div.setAttribute('data-node', stage.name.toLowerCase());
            //             div.innerHTML = `<i class="fa ${icon}"></i><span> ${stage.name}</span>`;
            //             tabContent.appendChild(div);
            //         });
            //     } else if (tabId == 3) {
            //         const createContractDiv = document.createElement('div');
            //         createContractDiv.classList.add('drag-drawflow');
            //         createContractDiv.setAttribute('draggable', 'true');
            //         createContractDiv.setAttribute('ondragstart', 'drag(event)');
            //         createContractDiv.setAttribute('data-node', 'create-contract');
            //         createContractDiv.innerHTML = `<i class="fa fa-file-contract"></i><span> Create Contract</span>`;
            //         tabContent.appendChild(createContractDiv);
            //     } else if (tabId == 4) {
            //         const createInvoiceDiv = document.createElement('div');
            //         createInvoiceDiv.classList.add('drag-drawflow');
            //         createInvoiceDiv.setAttribute('draggable', 'true');
            //         createInvoiceDiv.setAttribute('ondragstart', 'drag(event)');
            //         createInvoiceDiv.setAttribute('data-node', 'create-invoice');
            //         createInvoiceDiv.innerHTML = `<i class="fa fa-file-invoice"></i><span> Create Invoice</span>`;
            //         tabContent.appendChild(createInvoiceDiv);
            //     }
            // }


            // function openTab(index) {
            //     index = index - 1;
            //     let contents = document.querySelectorAll('.tab-content');
            //     contents.forEach(content => content.classList.remove('active-content'));
            //     let tabs = document.querySelectorAll('.tab');
            //     tabs.forEach(tab => tab.classList.remove('active'));
            //     tabs[index].classList.add('active');
            //     contents[index].classList.add('active-content');
            //     contents[index].setAttribute('data-index', index + 1);
            //     // populateStages(index + 1);
            //     // let activeDragItems = contents[index].querySelectorAll('.drag-drawflow');
            //     // activeDragItems.forEach(item => {
            //     //     let nodeValue = item.getAttribute('data-node');
            //     //     let tabId = `-${index + 1}`;
            //     //     if (!nodeValue.endsWith(tabId)) {
            //     //         let modifiedNodeValue = `${nodeValue}${tabId}`;
            //     //         item.setAttribute('data-node', modifiedNodeValue);
            //     //     }
            //     //     item.setAttribute('data-tab-id', index + 1);
            //     // });
            // }
            // openTab(1);
        </script>
        <script>
            var users = {!! json_encode($allusers) !!};
            var departments = {!! json_encode($departments) !!};
            var designations = {!! json_encode($designations) !!};
            const workflowStoreUrl = '{{ route('workflow.store') }}';

            const blockData = {
                tab1: [{
                        value: 1,
                        title: "New visitor",
                        desc: "Triggers when somebody visits a specified page",
                        icon: "assets/eye.svg",
                    },
                    {
                        value: 2,
                        title: "Action is performed",
                        desc: "Triggers when somebody performs a specified action",
                        icon: "assets/action.svg",
                    },
                ],
                tab2: [{
                        value: 1,
                        title: "New visitor in Tab 2",
                        desc: "Triggers when somebody visits a specified page in Tab 2",
                        icon: "assets/eye.svg",
                    },
                    {
                        value: 2,
                        title: "Action is performed in Tab 2",
                        desc: "Triggers when somebody performs a specified action in Tab 2",
                        icon: "assets/action.svg",
                    },
                ],
                tab3: [{
                        value: 1,
                        title: "New visitor in Tab 3",
                        desc: "Triggers when somebody visits a specified page in Tab 3",
                        icon: "assets/eye.svg",
                    },
                    {
                        value: 2,
                        title: "Action is performed in Tab 3",
                        desc: "Triggers when somebody performs a specified action in Tab 3",
                        icon: "assets/action.svg",
                    },
                ],
                tab4: [{
                        value: 1,
                        title: "New visitor in Tab 4",
                        desc: "Triggers when somebody visits a specified page in Tab 4",
                        icon: "assets/eye.svg",
                    },
                    {
                        value: 2,
                        title: "Action is performed in Tab 4",
                        desc: "Triggers when somebody performs a specified action in Tab 4",
                        icon: "assets/action.svg",
                    },
                ],
            };
            var id = document.getElementById("drawflow");
            const editor = new Drawflow(id);
            editor.reroute = true;
            // const dataToImport = {"drawflow":{"Home":{"data":{"1":{"id":1,"name":"welcome","data":{},"class":"welcome","html":"\n    <div>\n      <div class=\"title-box\">👏 Welcome!!</div>\n      <div class=\"box\">\n        <p>Simple flow library <b>demo</b>\n        <a href=\"https://github.com/jerosoler/Drawflow\" target=\"_blank\">Drawflow</a> by <b>Jero Soler</b></p><br>\n\n        <p>Multiple input / outputs<br>\n           Data sync nodes<br>\n           Import / export<br>\n           Modules support<br>\n           Simple use<br>\n           Type: Fixed or Edit<br>\n           Events: view console<br>\n           Pure Javascript<br>\n        </p>\n        <br>\n        <p><b><u>Shortkeys:</u></b></p>\n        <p>🎹 <b>Delete</b> for remove selected<br>\n        💠 Mouse Left Click == Move<br>\n        ❌ Mouse Right == Delete Option<br>\n        🔍 Ctrl + Wheel == Zoom<br>\n        📱 Mobile support<br>\n        ...</p>\n      </div>\n    </div>\n    ","typenode": false, "inputs":{},"outputs":{},"pos_x":50,"pos_y":50},"2":{"id":2,"name":"slack","data":{},"class":"slack","html":"\n          <div>\n            <div class=\"title-box\"><i class=\"fab fa-slack\"></i> Slack chat message</div>\n          </div>\n          ","typenode": false, "inputs":{"input_1":{"connections":[{"node":"7","input":"output_1"}]}},"outputs":{},"pos_x":1028,"pos_y":87},"3":{"id":3,"name":"telegram","data":{"channel":"channel_2"},"class":"telegram","html":"\n          <div>\n            <div class=\"title-box\"><i class=\"fab fa-telegram-plane\"></i> Telegram bot</div>\n            <div class=\"box\">\n              <p>Send to telegram</p>\n              <p>select channel</p>\n              <select df-channel>\n                <option value=\"channel_1\">Channel 1</option>\n                <option value=\"channel_2\">Channel 2</option>\n                <option value=\"channel_3\">Channel 3</option>\n                <option value=\"channel_4\">Channel 4</option>\n              </select>\n            </div>\n          </div>\n          ","typenode": false, "inputs":{"input_1":{"connections":[{"node":"7","input":"output_1"}]}},"outputs":{},"pos_x":1032,"pos_y":184},"4":{"id":4,"name":"email","data":{},"class":"email","html":"\n            <div>\n              <div class=\"title-box\"><i class=\"fas fa-at\"></i> Send Email </div>\n            </div>\n            ","typenode": false, "inputs":{"input_1":{"connections":[{"node":"5","input":"output_1"}]}},"outputs":{},"pos_x":1033,"pos_y":439},"5":{"id":5,"name":"template","data":{"template":"Write your template"},"class":"template","html":"\n            <div>\n              <div class=\"title-box\"><i class=\"fas fa-code\"></i> Template</div>\n              <div class=\"box\">\n                Ger Vars\n                <textarea df-template></textarea>\n                Output template with vars\n              </div>\n            </div>\n            ","typenode": false, "inputs":{"input_1":{"connections":[{"node":"6","input":"output_1"}]}},"outputs":{"output_1":{"connections":[{"node":"4","output":"input_1"},{"node":"11","output":"input_1"}]}},"pos_x":607,"pos_y":304},"6":{"id":6,"name":"github","data":{"name":"https://github.com/jerosoler/Drawflow"},"class":"github","html":"\n          <div>\n            <div class=\"title-box\"><i class=\"fab fa-github \"></i> Github Stars</div>\n            <div class=\"box\">\n              <p>Enter repository url</p>\n            <input type=\"text\" df-name>\n            </div>\n          </div>\n          ","typenode": false, "inputs":{},"outputs":{"output_1":{"connections":[{"node":"5","output":"input_1"}]}},"pos_x":341,"pos_y":191},"7":{"id":7,"name":"facebook","data":{},"class":"facebook","html":"\n        <div>\n          <div class=\"title-box\"><i class=\"fab fa-facebook\"></i> Facebook Message</div>\n        </div>\n        ","typenode": false, "inputs":{},"outputs":{"output_1":{"connections":[{"node":"2","output":"input_1"},{"node":"3","output":"input_1"},{"node":"11","output":"input_1"}]}},"pos_x":347,"pos_y":87},"11":{"id":11,"name":"log","data":{},"class":"log","html":"\n            <div>\n              <div class=\"title-box\"><i class=\"fas fa-file-signature\"></i> Save log file </div>\n            </div>\n            ","typenode": false, "inputs":{"input_1":{"connections":[{"node":"5","input":"output_1"},{"node":"7","input":"output_1"}]}},"outputs":{},"pos_x":1031,"pos_y":363}}},"Other":{"data":{"8":{"id":8,"name":"personalized","data":{},"class":"personalized","html":"\n            <div>\n              Personalized\n            </div>\n            ","typenode": false, "inputs":{"input_1":{"connections":[{"node":"12","input":"output_1"},{"node":"12","input":"output_2"},{"node":"12","input":"output_3"},{"node":"12","input":"output_4"}]}},"outputs":{"output_1":{"connections":[{"node":"9","output":"input_1"}]}},"pos_x":764,"pos_y":227},"9":{"id":9,"name":"dbclick","data":{"name":"Hello World!!"},"class":"dbclick","html":"\n            <div>\n            <div class=\"title-box\"><i class=\"fas fa-mouse\"></i> Db Click</div>\n              <div class=\"box dbclickbox\" ondblclick=\"showpopup(event)\">\n                Db Click here\n                <div class=\"modal\" style=\"display:none\">\n                  <div class=\"modal-content\">\n                    <span class=\"close\" onclick=\"closemodal(event)\">&times;</span>\n                    Change your variable {name} !\n                    <input type=\"text\" df-name>\n                  </div>\n\n                </div>\n              </div>\n            </div>\n            ","typenode": false, "inputs":{"input_1":{"connections":[{"node":"8","input":"output_1"}]}},"outputs":{"output_1":{"connections":[{"node":"12","output":"input_2"}]}},"pos_x":209,"pos_y":38},"12":{"id":12,"name":"multiple","data":{},"class":"multiple","html":"\n            <div>\n              <div class=\"box\">\n                Multiple!\n              </div>\n            </div>\n            ","typenode": false, "inputs":{"input_1":{"connections":[]},"input_2":{"connections":[{"node":"9","input":"output_1"}]},"input_3":{"connections":[]}},"outputs":{"output_1":{"connections":[{"node":"8","output":"input_1"}]},"output_2":{"connections":[{"node":"8","output":"input_1"}]},"output_3":{"connections":[{"node":"8","output":"input_1"}]},"output_4":{"connections":[{"node":"8","output":"input_1"}]}},"pos_x":179,"pos_y":272}}}}}
            editor.start();
            // editor.import(dataToImport);

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

                    if (type === 'adduser') {
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
                    const assignedData = JSON.parse(assignedUserInput.value);
                    assignedData.forEach(item => {
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
                clickedNode.appendChild(hiddenInput);
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
                const savedConditions = hiddenInput ? JSON.parse(hiddenInput.value).conditions : [];

                console.log('Saved Conditions:', savedConditions);

                // Filter saved conditions for the specific action
                const conditionsForAction = savedConditions.filter(savedCondition => savedCondition.action === action);

                if (conditionsForAction.length > 0) {
                    if (!conditionsContainer.dataset.initialized) {
                        initializeSavedConditions(conditionsContainer, clickedNode, conditionsForAction[0].conditions);
                        conditionsContainer.dataset.initialized = true;
                    }
                } else {
                    console.log('No saved conditions for action:', action);
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

                // columns.forEach(option => {
                //     const opt = document.createElement('option');
                //     opt.value = option;
                //     opt.textContent = option;
                //     conditionSelect.appendChild(opt);
                // });
                columns.forEach(option => {
                    const opt = document.createElement('option');
                    opt.value = option;
                    const displayText = option
                        .replace('-db', '')
                        .split('_')
                        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                        .join(' ');
                    opt.textContent = displayText;
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
                    const rawValue = row.querySelector('select:nth-child(1)').value;
                    let origin = 'cf';
                    let value = rawValue;
                    if (rawValue.includes('-db')) {
                        origin = 'db';
                        value = rawValue.replace('-db', '');
                    }
                    const condition = {
                        // field: row.querySelector('select:nth-child(1)').value,
                        field: value,
                        operator: row.querySelector('.dropdown-toggle').dataset.value,
                        value: row.querySelector('input').value,
                        origin: origin 
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
                    const savedData = JSON.parse(hiddenInput.value);
                    const existingAction = savedData.conditions.find(cond => cond.action === action);

                    if (existingAction) {
                        // Update the existing action's conditions
                        existingAction.conditions = conditions;
                    } else {
                        savedData.conditions.push(result);
                    }

                    hiddenInput.value = JSON.stringify(savedData);
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
                if (!assignedUserInput) {
                    assignedUserInput = document.createElement('input');
                    assignedUserInput.type = 'hidden';
                    assignedUserInput.name = 'assigned-user';
                    targetTitleBox.appendChild(assignedUserInput);
                }
                assignedUserInput.value = JSON.stringify(assignedIdsArray);

                console.log("Assigned User IDs Array:", assignedIdsArray);
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
