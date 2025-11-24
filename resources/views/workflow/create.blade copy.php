<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flowy - Workflow Engine</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap" rel="stylesheet">
    <link href="{{ asset('public/workflow/styles.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('public/workflow/flowy.min.css') }}" rel="stylesheet" type="text/css">
    <script src="{{ asset('public/workflow/flowy.min.js') }}"></script>
    <script src="{{ asset('public/workflow/main.js') }}"></script>
    <style>
        .tabs {
            display: flex;
            gap: 10px;
            cursor: pointer;
        }

        .tab {
            padding: 10px;
            background-color: #ddd;
            border-radius: 5px;
        }

        .tab.active {
            background-color: #007bff;
            color: white;
        }

        #subnav {
            margin-top: 20px;
            display: none;
        }

        .subnav-content,
        .blocklist-content {
            display: none;
        }

        .subnav-content.active,
        .blocklist-content.active {
            display: block;
        }
    </style>
</head>

<body>

    <div id="leftcard">
        <p id="header">Create Workflow</p>

        <!-- Tabs for Leads, Deals, Contracts, and Invoices -->
        <div class="tabs">
            <div class="tab active" data-target="leads">Leads</div>
            <div class="tab" data-target="deals">Deals</div>
            <div class="tab" data-target="contracts">Contracts</div>
            <div class="tab" data-target="invoices">Invoices</div>
        </div>

        <!-- Subnavigation based on selected tab -->
        <div id="subnav">
            <!-- Subnav for Leads -->
            <div id="leads" class="subnav-content active">
                <div class="side">Triggers</div>
                <div class="side">Actions</div>
                <div class="side">Loggers</div>
            </div>

            <!-- Subnav for Deals -->
            <div id="deals" class="subnav-content">
                <div class="side">Triggers</div>
                <div class="side">Actions</div>
                <div class="side">Loggers</div>
            </div>

            <!-- Subnav for Contracts -->
            <div id="contracts" class="subnav-content">
                <div class="side">Triggers</div>
                <div class="side">Actions</div>
                <div class="side">Loggers</div>
            </div>

            <!-- Subnav for Invoices -->
            <div id="invoices" class="subnav-content">
                <div class="side">Triggers</div>
                <div class="side">Actions</div>
                <div class="side">Loggers</div>
            </div>
        </div>

        <!-- Block Lists for Each Tab -->
        <div id="blocklists">
            <!-- Block List for Leads -->
            <div id="leads-blocks" class="blocklist-content active">
                <div class="blockelem create-flowy noselect">
                    <input type="hidden" name='blockelemtype' class="blockelemtype" value="1">
                    <div class="grabme"><img src="{{ asset('public/workflow/assets/grabme.svg') }}"></div>
                    <div class="blockin">
                        <div class="blockico"><img src="{{ asset('public/workflow/assets/eye.svg') }}"></div>
                        <div class="blocktext">
                            <p class="blocktitle">New Lead</p>
                            <p class="blockdesc">Triggers when somebody visits a specified page</p>
                        </div>
                    </div>
                </div>
                <!-- Additional block elements for Leads -->
            </div>

            <!-- Block List for Deals -->
            <div id="deals-blocks" class="blocklist-content">
                <div class="blockelem create-flowy noselect">
                    <input type="hidden" name='blockelemtype' class="blockelemtype" value="2">
                    <div class="grabme"><img src="{{ asset('public/workflow/assets/grabme.svg') }}"></div>
                    <div class="blockin">
                        <div class="blockico"><img src="{{ asset('public/workflow/assets/action.svg') }}"></div>
                        <div class="blocktext">
                            <p class="blocktitle">Deal Action</p>
                            <p class="blockdesc">Triggers when a deal action is performed</p>
                        </div>
                    </div>
                </div>
                <!-- Additional block elements for Deals -->
            </div>

            <!-- Block List for Contracts -->
            <div id="contracts-blocks" class="blocklist-content">
                <div class="blockelem create-flowy noselect">
                    <input type="hidden" name='blockelemtype' class="blockelemtype" value="3">
                    <div class="grabme"><img src="{{ asset('public/workflow/assets/grabme.svg') }}"></div>
                    <div class="blockin">
                        <div class="blockico"><img src="{{ asset('public/workflow/assets/time.svg') }}"></div>
                        <div class="blocktext">
                            <p class="blocktitle">Contract Time</p>
                            <p class="blockdesc">Triggers after a specified contract time</p>
                        </div>
                    </div>
                </div>
                <!-- Additional block elements for Contracts -->
            </div>

            <!-- Block List for Invoices -->
            <div id="invoices-blocks" class="blocklist-content">
                <div class="blockelem create-flowy noselect">
                    <input type="hidden" name='blockelemtype' class="blockelemtype" value="4">
                    <div class="grabme"><img src="{{ asset('public/workflow/assets/grabme.svg') }}"></div>
                    <div class="blockin">
                        <div class="blockico"><img src="{{ asset('public/workflow/assets/error.svg') }}"></div>
                        <div class="blocktext">
                            <p class="blocktitle">Invoice Error</p>
                            <p class="blockdesc">Triggers when a specified invoice error happens</p>
                        </div>
                    </div>
                </div>
                <!-- Additional block elements for Invoices -->
            </div>
        </div>

        <div id="footer">
            <a>To remove just Drag the Block aside.</a>
        </div>
    </div>

    <div id="propwrap">
        <div id="properties">
            <div id="close"><img src="{{ asset('public/workflow/assets/close.svg') }}"></div>
            <div class="details" style="position: absolute; top: 30%;">
                <div id="blockname">Block Name</div>
            </div>
        </div>
    </div>

    <div id="canvas"></div>

    <script>
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                // Set active class on selected tab
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                // Display the corresponding subnav content
                const target = tab.getAttribute('data-target');
                document.querySelectorAll('.subnav-content').forEach(content => content.classList.remove('active'));
                document.getElementById(target).classList.add('active');
                document.getElementById('subnav').style.display = 'block';

                // Display the corresponding block list
                document.querySelectorAll('.blocklist-content').forEach(content => content.classList.remove('active'));
                document.getElementById(target + '-blocks').classList.add('active');
            });
        });
    </script>

</body>

</html>
