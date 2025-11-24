<!DOCTYPE html>
<html>

<head>
    <!-- Primary Meta Tags -->
    <title>Flowy - The simple flowchart engine</title>
    <meta name="title" content="Flowy - The simple flowchart engine">
    <meta name="description"
        content="Flowy is a minimal javascript library to create flowcharts. Use it for automation software, mind mapping tools, programming platforms, and more. Made by Alyssa X.">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://alyssax.com/x/flowy">
    <meta property="og:title" content="Flowy - The simple flowchart engine">
    <meta property="og:description"
        content="Flowy is a minimal javascript library to create flowcharts. Use it for automation software, mind mapping tools, programming platforms, and more. Made by Alyssa X.">
    <meta property="og:image" content="https://alyssax.com/x/assets/meta.png">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://alyssax.com/x/flowy">
    <meta property="twitter:site" content="alyssaxuu">
    <meta property="twitter:title" content="Flowy - The simple flowchart engine">
    <meta property="twitter:description"
        content="Flowy is a minimal javascript library to create flowcharts. Use it for automation software, mind mapping tools, programming platforms, and more. Made by Alyssa X.">
    <meta property="twitter:image" content="https://alyssax.com/x/assets/meta.png">
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
        integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <link href='{{ asset('public/workflow/styles.css') }}' rel='stylesheet' type='text/css'>
    <link href='{{ asset('public/workflow/flowy.min.css') }}' rel='stylesheet' type='text/css'>
    <script src="{{ asset('public/workflow/main.js') }}" defer></script>
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <script src="{{ asset('public/workflow/flowy.min.js') }}"></script>
    <style>
        /* Basic styling for the tabs */
        .tabs {
            display: flex;
            cursor: pointer;
            background-color: #f1f1f1;
            padding: 10px;
            justify-content:center;
        }

        .tab {
            padding: 10px 15px;
            margin: 0 5px;
            background-color: #ddd;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .tab:hover {
            background-color: #ccc;
        }

        /* Style for active tab */
        .active {
            background-color: #4CAF50;
            color: white;
        }

        /* Content area */
        .tab-content {
            display: none;
            padding: 20px;
            border: 1px solid #ccc;
            border-top: none;
            background-color: #f9f9f9;
        }

        .active-content {
            display: block;
        }

        /* Customize the select field and options */
        .choices__inner {
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 6px;
            font-family: Arial, sans-serif;
            width: 100%;
            /* Ensure full width */
            position: relative;
        }

        .choices__input {
            color: #333;
        }

        .choices__list--multiple .choices__item {
            background-color: #007bff;
            color: white;
            border-radius: 3px;
            padding: 5px 10px;
            margin: 3px 2px;
            font-size: 0.9em;
        }

        .choices__list--multiple .choices__item[data-item] {
            background-color: #007bff;
            color: white;
            padding: 4px 8px;
            border-radius: 5px;
        }

        .choices__list--multiple .choices__item[data-item] .choices__button {
            background: transparent;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }

        .choices__list--multiple .choices__item[data-item] .choices__button:hover {
            color: #ccc;
        }

        .choices__placeholder {
            color: #888;
        }

        /* Improve the dropdown styling */
        .choices__list--dropdown .choices__item--selectable {
            padding: 10px;
            font-size: 0.9em;
            color: #333;
            background-color: #f9f9f9;
            border-bottom: 1px solid #ddd;
        }

        .choices__list--dropdown .choices__item--selectable:hover {
            background-color: #007bff;
            color: white;
        }

        .choices__list--dropdown {
            position: absolute;
            max-height: 100px;
            width: 100%;
            overflow-y: auto;
            z-index: 1000;
        }

        
    </style>
</head>

<body>

    <div id="leftcard">
        <p id="header">Create WorkFlow</p>
        <div class="tabs">
            <div class="tab active" onclick="openTab(1)">Lead</div>
            <div class="tab" onclick="openTab(2)">Deal</div>
            <div class="tab" onclick="openTab(3)">Contract</div>
            <div class="tab" onclick="openTab(4)">Invoice</div>
        </div>
        <div class="tab-content active-content" data-index="1">
            <div class="tabledata">
                <div id="subnav">
                    <div id="triggers" class="navactive side">Triggers</div>
                    <div id="actions" class="navdisabled side">Actions</div>
                    <div id="loggers" class="navdisabled side">Loggers</div>
                </div>
                <div id="blocklist-tab1">
                    <div class="blockelem create-flowy noselect">
                        <input type="hidden" name='blockelemtype' class="blockelemtype" value="1">
                        <div class="grabme">
                            <img src="{{ asset('public\workflow\assets\grabme.svg') }}">
                        </div>
                        <div class="blockin">
                            <div class="blockico">
                                <span></span>
                                <img src="{{ asset('public\workflow\assets\eye.svg') }}">
                            </div>
                            <div class="blocktext">
                                <p class="blocktitle">New Lea tab 1</p>
                                <p class="blockdesc">Triggers when somebody visits a specified page</p>
                            </div>
                        </div>
                    </div>
                    <div class="blockelem create-flowy noselect">
                        <input type="hidden" name='blockelemtype' class="blockelemtype" value="2">
                        <div class="grabme">
                            <img src="{{ asset('public\workflow\assets\grabme.svg') }}">
                        </div>
                        <div class="blockin">
                            <div class="blockico">
                                <span></span>
                                <img src="{{ asset('public/workflow/assets/action.svg') }}">
                            </div>
                            <div class="blocktext">
                                <p class="blocktitle">Action is performed</p>
                                <p class="blockdesc">Triggers when somebody performs a specified action</p>
                            </div>
                        </div>
                    </div>
                    <div class="blockelem create-flowy noselect">
                        <input type="hidden" name='blockelemtype' class="blockelemtype" value="3">
                        <div class="grabme">
                            <img src="{{ asset('public/workflow/assets/grabme.svg') }}">
                        </div>
                        <div class="blockin">
                            <div class="blockico">
                                <span></span>
                                <img src="{{ asset('public/workflow/assets/time.svg') }}">
                            </div>
                            <div class="blocktext">
                                <p class="blocktitle">Time has passed</p>
                                <p class="blockdesc">Triggers after a specified amount of time</p>
                            </div>
                        </div>
                    </div>
                    <div class="blockelem create-flowy noselect">
                        <input type="hidden" name='blockelemtype' class="blockelemtype" value="4">
                        <div class="grabme">
                            <img src="{{ asset('public/workflow/assets/grabme.svg') }}">
                        </div>
                        <div class="blockin">
                            <div class="blockico">
                                <span></span>
                                <img src="{{ asset('public/workflow/assets/error.svg') }}">
                            </div>
                            <div class="blocktext">
                                <p class="blocktitle">Error prompt</p>
                                <p class="blockdesc">Triggers when a specified error happens</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="footer">
                    <a>To remove just Drag the Block aside.</a>
                    <a id="calculatelevels" style="padding: 10px; background-color:greenyellow; cursor: pointer;">Save</a>
                </div>
            </div>
        </div>

        <div class="tab-content">
            <div class="tabledata">
                <div id="subnav">
                    <div id="triggers" class="navactive side">Triggers</div>
                    <div id="actions" class="navdisabled side">Actions</div>
                    <div id="loggers" class="navdisabled side">Loggers</div>
                </div>
                <div id="blocklist-tab2">
                    <div class="blockelem create-flowy noselect">
                        <input type="hidden" name='blockelemtype' class="blockelemtype" value="1">
                        <div class="grabme">
                            <img src="{{ asset('public\workflow\assets\grabme.svg') }}">
                        </div>
                        <div class="blockin">
                            <div class="blockico">
                                <span></span>
                                <img src="{{ asset('public\workflow\assets\eye.svg') }}">
                            </div>
                            <div class="blocktext">
                                <p class="blocktitle">New Leas tab 2</p>
                                <p class="blockdesc">Triggers when somebody visits a specified page</p>
                            </div>
                        </div>
                    </div>
                    <div class="blockelem create-flowy noselect">
                        <input type="hidden" name='blockelemtype' class="blockelemtype" value="2">
                        <div class="grabme">
                            <img src="{{ asset('public\workflow\assets\grabme.svg') }}">
                        </div>
                        <div class="blockin">
                            <div class="blockico">
                                <span></span>
                                <img src="{{ asset('public/workflow/assets/action.svg') }}">
                            </div>
                            <div class="blocktext">
                                <p class="blocktitle">Action is performed</p>
                                <p class="blockdesc">Triggers when somebody performs a specified action</p>
                            </div>
                        </div>
                    </div>
                    <div class="blockelem create-flowy noselect">
                        <input type="hidden" name='blockelemtype' class="blockelemtype" value="3">
                        <div class="grabme">
                            <img src="{{ asset('public/workflow/assets/grabme.svg') }}">
                        </div>
                        <div class="blockin">
                            <div class="blockico">
                                <span></span>
                                <img src="{{ asset('public/workflow/assets/time.svg') }}">
                            </div>
                            <div class="blocktext">
                                <p class="blocktitle">Time has passed</p>
                                <p class="blockdesc">Triggers after a specified amount of time</p>
                            </div>
                        </div>
                    </div>
                    <div class="blockelem create-flowy noselect">
                        <input type="hidden" name='blockelemtype' class="blockelemtype" value="4">
                        <div class="grabme">
                            <img src="{{ asset('public/workflow/assets/grabme.svg') }}">
                        </div>
                        <div class="blockin">
                            <div class="blockico">
                                <span></span>
                                <img src="{{ asset('public/workflow/assets/error.svg') }}">
                            </div>
                            <div class="blocktext">
                                <p class="blocktitle">Error prompt</p>
                                <p class="blockdesc">Triggers when a specified error happens</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="footer">
                    <a>To remove just Drag the Block aside.</a>
                </div>
            </div>
        </div>

        <div class="tab-content">
            <div class="tabledata">
                <div id="subnav">
                    <div id="triggers" class="navactive side">Triggers</div>
                    <div id="actions" class="navdisabled side">Actions</div>
                    <div id="loggers" class="navdisabled side">Loggers</div>
                </div>
                <div id="blocklist-tab3">
                    <div class="blockelem create-flowy noselect">
                        <input type="hidden" name='blockelemtype' class="blockelemtype" value="1">
                        <div class="grabme">
                            <img src="{{ asset('public\workflow\assets\grabme.svg') }}">
                        </div>
                        <div class="blockin">
                            <div class="blockico">
                                <span></span>
                                <img src="{{ asset('public\workflow\assets\eye.svg') }}">
                            </div>
                            <div class="blocktext">
                                <p class="blocktitle">New Lea tab 3</p>
                                <p class="blockdesc">Triggers when somebody visits a specified page</p>
                            </div>
                        </div>
                    </div>
                    <div class="blockelem create-flowy noselect">
                        <input type="hidden" name='blockelemtype' class="blockelemtype" value="2">
                        <div class="grabme">
                            <img src="{{ asset('public\workflow\assets\grabme.svg') }}">
                        </div>
                        <div class="blockin">
                            <div class="blockico">
                                <span></span>
                                <img src="{{ asset('public/workflow/assets/action.svg') }}">
                            </div>
                            <div class="blocktext">
                                <p class="blocktitle">Action is performed</p>
                                <p class="blockdesc">Triggers when somebody performs a specified action</p>
                            </div>
                        </div>
                    </div>
                    <div class="blockelem create-flowy noselect">
                        <input type="hidden" name='blockelemtype' class="blockelemtype" value="3">
                        <div class="grabme">
                            <img src="{{ asset('public/workflow/assets/grabme.svg') }}">
                        </div>
                        <div class="blockin">
                            <div class="blockico">
                                <span></span>
                                <img src="{{ asset('public/workflow/assets/time.svg') }}">
                            </div>
                            <div class="blocktext">
                                <p class="blocktitle">Time has passed</p>
                                <p class="blockdesc">Triggers after a specified amount of time</p>
                            </div>
                        </div>
                    </div>
                    <div class="blockelem create-flowy noselect">
                        <input type="hidden" name='blockelemtype' class="blockelemtype" value="4">
                        <div class="grabme">
                            <img src="{{ asset('public/workflow/assets/grabme.svg') }}">
                        </div>
                        <div class="blockin">
                            <div class="blockico">
                                <span></span>
                                <img src="{{ asset('public/workflow/assets/error.svg') }}">
                            </div>
                            <div class="blocktext">
                                <p class="blocktitle">Error prompt</p>
                                <p class="blockdesc">Triggers when a specified error happens</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="footer">
                    <a>To remove just Drag the Block aside.</a>
                </div>
            </div>
        </div>

        <div class="tab-content">
            <div class="tabledata">
                <div id="subnav">
                    <div id="triggers" class="navactive side">Triggers</div>
                    <div id="actions" class="navdisabled side">Actions</div>
                    <div id="loggers" class="navdisabled side">Loggers</div>
                </div>
                <div id="blocklist-tab4">
                    <div class="blockelem create-flowy noselect">
                        <input type="hidden" name='blockelemtype' class="blockelemtype" value="1">
                        <div class="grabme">
                            <img src="{{ asset('public\workflow\assets\grabme.svg') }}">
                        </div>
                        <div class="blockin">
                            <div class="blockico">
                                <span></span>
                                <img src="{{ asset('public\workflow\assets\eye.svg') }}">
                            </div>
                            <div class="blocktext">
                                <p class="blocktitle">New Lea tab </p>
                                <p class="blockdesc">Triggers when somebody visits a specified page</p>
                            </div>
                        </div>
                    </div>
                    <div class="blockelem create-flowy noselect">
                        <input type="hidden" name='blockelemtype' class="blockelemtype" value="2">
                        <div class="grabme">
                            <img src="{{ asset('public\workflow\assets\grabme.svg') }}">
                        </div>
                        <div class="blockin">
                            <div class="blockico">
                                <span></span>
                                <img src="{{ asset('public/workflow/assets/action.svg') }}">
                            </div>
                            <div class="blocktext">
                                <p class="blocktitle">Action is performed</p>
                                <p class="blockdesc">Triggers when somebody performs a specified action</p>
                            </div>
                        </div>
                    </div>
                    <div class="blockelem create-flowy noselect">
                        <input type="hidden" name='blockelemtype' class="blockelemtype" value="3">
                        <div class="grabme">
                            <img src="{{ asset('public/workflow/assets/grabme.svg') }}">
                        </div>
                        <div class="blockin">
                            <div class="blockico">
                                <span></span>
                                <img src="{{ asset('public/workflow/assets/time.svg') }}">
                            </div>
                            <div class="blocktext">
                                <p class="blocktitle">Time has passed</p>
                                <p class="blockdesc">Triggers after a specified amount of time</p>
                            </div>
                        </div>
                    </div>
                    <div class="blockelem create-flowy noselect">
                        <input type="hidden" name='blockelemtype' class="blockelemtype" value="4">
                        <div class="grabme">
                            <img src="{{ asset('public/workflow/assets/grabme.svg') }}">
                        </div>
                        <div class="blockin">
                            <div class="blockico">
                                <span></span>
                                <img src="{{ asset('public/workflow/assets/error.svg') }}">
                            </div>
                            <div class="blocktext">
                                <p class="blocktitle">Error prompt</p>
                                <p class="blockdesc">Triggers when a specified error happens</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="footer">
                    <a>To remove just Drag the Block aside.</a>
                </div>
            </div>
        </div>
    </div>
    <div id="propwrap">
        <div id="properties">
            <div id="close" style="position: relative;top: -15%;left: 89%;">
                <img src="{{ asset('public/workflow/assets/close.svg') }}">
            </div>
            <div class="details" style="position: absolute; top: 2%; padding:0px 10px;">
                <h3 id="blockname">Block Name</h3>
                <div id="blockdesc">Block Type</div>
                <div class="form">

                </div>
            </div>
        </div>
    </div>
    <div id="leadcanvas">
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous">
    </script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous">
    </script>
    <script src="{{ asset('assets/js/plugins/choices.min.js') }}"></script>

    <script>
        var users = {!! json_encode($allusers) !!};
        var departments = {!! json_encode($departments) !!};
        var designations = {!! json_encode($designations) !!};

        function openTab(index) {
            index = index - 1;
            let contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active-content'));
            let tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            tabs[index].classList.add('active');
            contents[index].classList.add('active-content');
            contents[index].setAttribute('data-index', index + 1);
        }
    </script>
</body>

</html>
