@extends('layouts.admin')

@section('content')
    <style>
        .container {
            margin: 0 auto;
            display: flex;
            flex-direction: column;
        }

        .buttons {
            display: flex;
            flex-wrap: wrap;
            flex-direction: column;
            gap: 10px;
        }
    </style>
    <div class="container card p-4">
        <h1>QuickBooks Import</h1>

        <!-- QuickBooks Connection Status and Actions -->
            @php
                $qbController = new \App\Http\Controllers\QuickBooksApiController();
                $connected = $qbController->accessToken() && $qbController->realmId();
            @endphp

            @if($connected)
                <div class="alert alert-success">
                    <strong>Connected to QuickBooks</strong>
                    <a href="{{ route('quickbooks.disconnect') }}" class="btn btn-sm btn-outline-danger ml-2">Disconnect</a>
                </div>
                <div class="buttons">
                    <form action="{{ route('quickbooks.import.full') }}" method="POST" id="fullImportForm">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-lg" id="startImportBtn">
                            <i class="fa fa-play"></i> Start Full Import
                        </button>
                    </form>

                    <!-- Progress Bar -->
                    <div id="importProgress" class="mt-4" style="display: none;">
                        <h4>Import Progress</h4>
                        <div class="progress mb-3">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" id="progressBar" role="progressbar"
                                style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                0%
                            </div>
                        </div>
                        <div id="currentStep" class="text-muted mb-3">Preparing import...</div>
                    </div>

                    <!-- Logs Section -->
                    <div id="importLogs" class="mt-4" style="display: none;">
                        <h4>Import Logs</h4>
                        <div id="logsContainer" class="border rounded p-3 bg-light" style="max-height: 300px; overflow-y: auto;">
                            <div class="text-muted">Logs will appear here...</div>
                        </div>
                    </div>

                    <!-- Legacy Import Buttons (Hidden by default, can be shown for debugging) -->
                    <div id="legacyButtons" style="display: none;">
                        <hr>
                        <h5>Individual Imports (Legacy)</h5>
                        <form action="{{ route('quickbooks.import.customers') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary">Import Customers</button>
                        </form>
                        <form action="{{ route('quickbooks.import.chartOfAccounts') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary">Import Chart of Accounts</button>
                        </form>
                        <form action="{{ route('quickbooks.import.vendors') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary">Import Vendors</button>
                        </form>
                        <form action="{{ route('quickbooks.import.items') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary">Import Items</button>
                        </form>
                        <form action="{{ route('quickbooks.import.invoices') }}" method="POST" id="importInvoicesForm">
                            @csrf
                            <button type="submit" class="btn btn-primary" id="importInvoicesBtn">Import Invoices</button>
                        </form>
                        <form action="{{ route('quickbooks.import.bills') }}" method="POST" id="importBillsForm">
                            @csrf
                            <button type="submit" class="btn btn-success" id="importBillsBtn">Import Bills</button>
                        </form>
                        <form action="{{ route('quickbooks.import.expenses') }}" method="POST" id="importExpensesForm">
                            @csrf
                            <button type="submit" class="btn btn-warning" id="importExpensesBtn">Import Expenses</button>
                        </form>
                        <form action="{{ route('quickbooks.import.journalReport') }}" method="POST" id="journalReportForm">
                            @csrf
                            <button type="submit" class="btn btn-info" id="journalReportBtn">Import Journal Report</button>
                        </form>
                    </div>
                </div>
            @else
                <div class="alert alert-warning">
                    <strong>Not connected to QuickBooks</strong>
                    <a href="{{ route('quickbooks.connect') }}" class="btn btn-sm btn-primary ml-2">Connect to QuickBooks</a>
                </div>
            @endif
        <script>
            let progressInterval;
            let displayedLogs = new Set();  // Track displayed log messages to avoid duplicates
            let pollAttempts = 0;
            let maxIdlePollAttempts = 60; // Stop after 30 seconds of idle status (60 * 500ms)

            document.getElementById('fullImportForm').addEventListener('submit', function (e) {
                e.preventDefault();
                startFullImport(this);
            });

            function startFullImport(form) {
                const btn = document.getElementById('startImportBtn');
                const progressDiv = document.getElementById('importProgress');
                const logsDiv = document.getElementById('importLogs');

                btn.disabled = true;
                btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Starting Import...';

                progressDiv.style.display = 'block';
                logsDiv.style.display = 'block';

                // Clear previous logs and reset counters
                displayedLogs.clear();
                pollAttempts = 0;
                document.getElementById('logsContainer').innerHTML = '';

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({})
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Don't add log here - controller already added it to cache
                            // Start polling immediately to fetch the initial log
                            startProgressPolling();
                        } else if (data.status === 'already_running') {
                            // Import is already running, show current progress
                            addLog(data.message, 'info');

                            // Display existing logs from the running import
                            if (data.progress && data.progress.logs) {
                                data.progress.logs.forEach(log => {
                                    addLog(log, 'info');
                                });
                            }

                            // Start polling to continue monitoring
                            startProgressPolling();
                        } else {
                            addLog('Failed to start import: ' + data.message, 'error');
                            resetButton();
                        }
                    })
                    .catch(error => {
                        addLog('Error starting import: ' + error.message, 'error');
                        resetButton();
                    });
            }

            function startProgressPolling() {
                // Immediately fetch progress once before starting interval
                fetchProgress();

                // Then poll every 500ms for real-time updates
                progressInterval = setInterval(() => {
                    fetchProgress();
                }, 500);
            }

            function fetchProgress() {
                fetch('{{ route("quickbooks.import.progress") }}', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Progress data:', data); // Debug log

                        // Track idle status
                        if (data.status === 'idle') {
                            pollAttempts++;
                            if (pollAttempts >= maxIdlePollAttempts) {
                                console.warn('Import appears to have not started or completed too quickly');
                                addLogToDOM('Import may have completed. Please check Laravel logs for details.', 'info');
                                clearInterval(progressInterval);
                                resetButton();
                                return;
                            }
                        } else {
                            // Reset counter if we get a non-idle status
                            pollAttempts = 0;
                        }

                        updateProgress(data);

                        if (data.status === 'completed' || data.status === 'failed') {
                            clearInterval(progressInterval);
                            resetButton();
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching progress:', error);
                    });
            }

            function updateProgress(data) {
                const progressBar = document.getElementById('progressBar');
                const currentStep = document.getElementById('currentStep');

                // Update progress bar
                progressBar.style.width = data.percentage + '%';
                progressBar.setAttribute('aria-valuenow', data.percentage);
                progressBar.textContent = data.percentage + '%';

                // Update current step text
                currentStep.textContent = data.current_import || 'Processing...';

                // Update logs - add any new logs we haven't displayed yet
                if (data.logs && Array.isArray(data.logs)) {
                    data.logs.forEach(log => {
                        // Only add if we haven't displayed this exact log before
                        if (!displayedLogs.has(log)) {
                            displayedLogs.add(log);

                            // Determine log type from prefix
                            let logType = 'info';
                            let cleanLog = log;

                            if (log.includes('[SUCCESS]')) {
                                logType = 'success';
                                cleanLog = log.replace('[SUCCESS] ', '');
                            } else if (log.includes('[ERROR]')) {
                                logType = 'error';
                                cleanLog = log.replace('[ERROR] ', '');
                            } else if (log.includes('[INFO]')) {
                                logType = 'info';
                                cleanLog = log.replace('[INFO] ', '');
                            }

                            addLogToDOM(cleanLog, logType);
                        }
                    });
                }

                // Change progress bar color based on status
                if (data.status === 'completed') {
                    progressBar.className = 'progress-bar bg-success';
                    if (!displayedLogs.has('__completed__')) {
                        displayedLogs.add('__completed__');
                        addLogToDOM('Import completed successfully!', 'success');
                    }
                } else if (data.status === 'failed') {
                    progressBar.className = 'progress-bar bg-danger';
                    if (!displayedLogs.has('__failed__')) {
                        displayedLogs.add('__failed__');
                        addLogToDOM('Import failed!', 'error');
                    }
                }
            }

            function addLog(message, type = 'info') {
                const logKey = `[${type.toUpperCase()}] ${message}`;
                if (!displayedLogs.has(logKey)) {
                    displayedLogs.add(logKey);
                    addLogToDOM(message, type);
                }
            }

            function addLogToDOM(message, type = 'info') {
                const logsContainer = document.getElementById('logsContainer');

                // Clear the placeholder text if this is the first log
                if (logsContainer.children.length === 1 &&
                    logsContainer.children[0].classList.contains('text-muted')) {
                    logsContainer.innerHTML = '';
                }

                const logEntry = document.createElement('div');
                logEntry.className = `log-entry log-${type}`;
                logEntry.innerHTML = `<small class="text-muted">[${new Date().toLocaleTimeString()}]</small> ${message}`;
                logsContainer.appendChild(logEntry);

                // Auto-scroll to bottom
                logsContainer.scrollTop = logsContainer.scrollHeight;
            }

            function resetButton() {
                const btn = document.getElementById('startImportBtn');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-play"></i> Start Full Import';
            }
            // Legacy handlers (for debugging)
            document.getElementById('importInvoicesForm').addEventListener('submit', function (e) {
                e.preventDefault();
                handleImport('importInvoicesBtn', this.action, 'Invoices');
            });

            document.getElementById('importBillsForm').addEventListener('submit', function (e) {
                e.preventDefault();
                handleImport('importBillsBtn', this.action, 'Bills');
            });

            document.getElementById('importExpensesForm').addEventListener('submit', function (e) {
                e.preventDefault();
                handleImport('importExpensesBtn', this.action, 'Expenses');
            });

            document.getElementById('journalReportForm').addEventListener('submit', function (e) {
                e.preventDefault();
                handleImport('journalReportBtn', this.action, 'Journal Report');
            });

            function handleImport(btnId, actionUrl, type) {
                const btn = document.getElementById(btnId);
                btn.disabled = true;
                btn.textContent = `Importing ${type}...`;

                fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({})
                })
                    .then(response => response.json())
                    .then(data => {
                        alert(`${type} Import: ${data.message}`);
                    })
                    .catch(error => {
                        alert(`Error importing ${type}: ${error.message}`);
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.textContent = `Import ${type}`;
                    });
            }
        </script>

        <style>
            .progress{
                height:25px !important;
            }
            .log-entry {
                margin-bottom: 5px;
                font-family: monospace;
                font-size: 12px;
            }

            .log-success {
                color: #28a745;
            }

            .log-error {
                color: #dc3545;
            }

            .log-info {
                color: #007bff;
            }
        </style>
    </div>
@endsection