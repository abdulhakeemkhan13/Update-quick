<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Yodlee FastLink Integration - Sandbox</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #666;
            font-size: 14px;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            background: #fef3c7;
            color: #92400e;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .info-group {
            margin-bottom: 15px;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .info-value {
            background: #f7f7f7;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            word-break: break-all;
            color: #333;
        }

        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
            margin-top: 10px;
        }

        .btn:hover:not(:disabled) {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: #48bb78;
        }

        .btn-secondary:hover:not(:disabled) {
            background: #38a169;
        }

        .status {
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
        }

        .status.success {
            background: #c6f6d5;
            color: #22543d;
        }

        .status.error {
            background: #fed7d7;
            color: #742a2a;
        }

        .status.info {
            background: #bee3f8;
            color: #2c5282;
        }

        #fastlink-container {
            width: 100%;
            height: 600px;
            border: none;
            border-radius: 10px;
            background: white;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .transactions-table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        .transactions-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        .transactions-table tr:hover {
            background: #f7f7f7;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #667eea;
            font-weight: 600;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #48bb78;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border-left: 4px solid #f56565;
        }

        .alert-info {
            background: #bee3f8;
            color: #2c5282;
            border-left: 4px solid #4299e1;
        }

        .hidden {
            display: none;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .token-info {
            background: #f0fdf4;
            border: 1px solid #86efac;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 12px;
            word-break: break-all;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🏦 Yodlee FastLink Integration <span class="badge">SANDBOX MODE</span></h1>
            <p class="subtitle">Connect bank accounts securely using Yodlee Sandbox environment</p>
        </div>

        <div id="alert-container"></div>

        <div class="content-grid">
            <div class="card">
                <h2>📋 Configuration Details</h2>
                <div class="info-group">
                    <div class="info-label">Environment:</div>
                    <div class="info-value">Sandbox (Demo Mode)</div>
                </div>
                <div class="info-group">
                    <div class="info-label">Admin Login Name:</div>
                    <div class="info-value">{{ env('YODLEE_ADMIN_LOGIN_NAME') }}</div>
                </div>
                <div class="info-group">
                    <div class="info-label">Client ID:</div>
                    <div class="info-value">{{ env('YODLEE_CLIENT_ID') }}</div>
                </div>
                <div class="info-group">
                    <div class="info-label">API Endpoint:</div>
                    <div class="info-value">https://sandbox.api.yodlee.com/ysl</div>
                </div>
                <div class="info-group">
                    <div class="info-label">FastLink URL:</div>
                    <div class="info-value">https://fl4.sandbox.yodlee.com/authenticate/restserver/fastlink</div>
                </div>
                <div class="info-group">
                    <div class="info-label">Status:</div>
                    <span class="status info" id="connection-status">Not Connected</span>
                </div>
            </div>

            <div class="card">
                <h2>🎯 Quick Actions</h2>
                <div class="alert-info" style="margin-bottom: 15px; font-size: 13px;">
                    <strong>ℹ️ Sandbox Mode:</strong> Using admin credentials to generate access token for FastLink.
                </div>
                <button class="btn" id="get-access-token" onclick="getAccessToken()">
                    1️⃣ Get Access Token
                </button>
                <div id="token-display" class="hidden token-info">
                    <strong>Token:</strong> <span id="token-value"></span>
                </div>
                <button class="btn" id="launch-fastlink" onclick="launchFastLink()" disabled>
                    2️⃣ Launch FastLink
                </button>
                <button class="btn btn-secondary" id="get-transactions" onclick="getTransactions()" disabled>
                    3️⃣ Fetch Transactions
                </button>
            </div>
        </div>

        <div class="card full-width hidden" id="fastlink-section">
            <h2>🔗 FastLink Connection</h2>
            <div id="fastlink-container"></div>
        </div>

        <div class="card full-width hidden" id="transactions-section">
            <h2>💰 Recent Transactions</h2>
            <div id="transactions-content">
                <div class="loading">
                    <div class="spinner"></div>
                    Loading transactions...
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.yodlee.com/fastlink/v4/initialize.js"></script>
    <script>
    const CONFIG = {
        fastlinkUrl: 'https://fl4.sandbox.yodlee.com/authenticate/restserver/fastlink'
    };

    let baseUrl = "{{ url('') }}";
    let accessToken = null;
    let fastlinkToken = null;
    let providerAccountId = null;
    let selectedUser = 'sbMem68ef21052da091';

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function showAlert(message, type = 'success') {
        const alertHtml = `<div class="alert alert-${type}">${message}</div>`;
        $('#alert-container').html(alertHtml);
        setTimeout(() => $('#alert-container').empty(), 5000);
    }

    function updateStatus(status, type = 'info') {
        $('#connection-status').removeClass('info success error').addClass(type).text(status);
    }

    // Handle test-user selection
    $('#test-user-select').change(function() {
        selectedUser = $(this).val();
        if (selectedUser) {
            $('#user-info').html(`
                <p><strong>Selected User:</strong> ${$(this).find('option:selected').text()}</p>
                <p>This is a pre-configured Yodlee sandbox test user.</p>
            `);
            $('#get-access-token').prop('disabled', false);
        } else {
            $('#user-info').html(`
                <p><strong>Select a test user to begin</strong></p>
                <p>This will use the Yodlee sandbox environment</p>
            `);
            $('#get-access-token').prop('disabled', true);
        }
    });

    // Step 1: Get Access Token
    function getAccessToken() {
        if (!selectedUser) {
            showAlert('Please select a test user first', 'error');
            return;
        }

        $('#get-access-token').prop('disabled', true).text('Getting Token...');
        updateStatus('Authenticating...', 'info');

        $.ajax({
            url: baseUrl + '/api/yodlee/get-access-token',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                username: selectedUser
            }),
            success: function(response) {
                console.log('Token Response:', response);
                
                if (response && response.success) {
                    accessToken = response.accessToken;
                    fastlinkToken = response.fastlinkToken || response.accessToken;
                    
                    showAlert('✅ Access token obtained successfully!', 'success');
                    updateStatus('Authenticated', 'success');
                    $('#get-access-token').text('✓ Token Obtained');
                    $('#launch-fastlink').prop('disabled', false);
                    
                    const truncatedToken = fastlinkToken.substring(0, 20) + '...' + fastlinkToken.substring(fastlinkToken.length - 20);
                    $('#token-value').text(truncatedToken);
                    $('#token-display').removeClass('hidden');
                    
                    console.log('Access Token:', accessToken);
                    console.log('FastLink Token:', fastlinkToken);
                } else {
                    throw new Error(response?.message || 'Failed to get token');
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || xhr.responseText || 'Failed to get access token';
                console.error('Get token error:', xhr);
                showAlert('❌ ' + error, 'error');
                updateStatus('Authentication Failed', 'error');
                $('#get-access-token').prop('disabled', false).text('1️⃣ Get Access Token');
            },
            timeout: 15000
        });
    }

    // Step 2: Launch FastLink
    function launchFastLink() {
        if (!fastlinkToken) {
            showAlert('Please get access token first', 'error');
            return;
        }

        $('#launch-fastlink').prop('disabled', true).text('Launching...');
        $('#fastlink-section').removeClass('hidden');
        
        $('html, body').animate({
            scrollTop: $('#fastlink-section').offset().top - 20
        }, 400);

        const checkFastLink = setInterval(function() {
            if (window.fastlink && typeof window.fastlink.open === 'function') {
                clearInterval(checkFastLink);
                
                try {
                    console.log('Opening FastLink with token:', fastlinkToken);
                    
                    window.fastlink.open({
                        fastLinkURL: CONFIG.fastlinkUrl,
                        accessToken: 'Bearer ' + fastlinkToken,
                        params: {
                            configName: 'Aggregation'
                        },
                        onSuccess: function(data) {
                            console.log('FastLink Success:', data);
                            showAlert('✅ Account connected successfully!', 'success');
                            $('#launch-fastlink').text('✓ Connected').prop('disabled', false);
                            $('#get-transactions').prop('disabled', false);
                            
                            if (data.providerAccountId) {
                                providerAccountId = data.providerAccountId;
                            } else if (data.providerAccountIds && data.providerAccountIds.length > 0) {
                                providerAccountId = data.providerAccountIds[0];
                            }
                            
                            console.log('Provider Account ID:', providerAccountId);
                        },
                        onError: function(err) {
                            console.error('FastLink Error:', err);
                            showAlert('❌ Connection failed: ' + (err?.message || JSON.stringify(err)), 'error');
                            $('#launch-fastlink').prop('disabled', false).text('2️⃣ Launch FastLink');
                        },
                        onClose: function() {
                            console.log('FastLink Closed');
                            $('#fastlink-section').addClass('hidden');
                            if ($('#launch-fastlink').text() !== '✓ Connected') {
                                $('#launch-fastlink').prop('disabled', false).text('2️⃣ Launch FastLink');
                            }
                        },
                        onEvent: function(evt) {
                            console.log('FastLink Event:', evt);
                        }
                    }, 'fastlink-container');
                } catch (e) {
                    console.error('Error launching FastLink:', e);
                    showAlert('❌ Failed to launch FastLink: ' + e.message, 'error');
                    $('#launch-fastlink').prop('disabled', false).text('2️⃣ Launch FastLink');
                }
            }
        }, 100);

        setTimeout(function() {
            clearInterval(checkFastLink);
            if ($('#launch-fastlink').text() === 'Launching...') {
                showAlert('❌ FastLink SDK failed to load', 'error');
                $('#launch-fastlink').prop('disabled', false).text('2️⃣ Launch FastLink');
            }
        }, 10000);
    }

    // Step 3: Fetch Transactions
    function getTransactions() {
        if (!accessToken) {
            showAlert('No access token available', 'error');
            return;
        }

        $('#get-transactions').prop('disabled', true).text('Fetching...');
        $('#transactions-section').removeClass('hidden');
        
        $('html, body').animate({
            scrollTop: $('#transactions-section').offset().top - 20
        }, 400);

        $.ajax({
            url: baseUrl + '/api/yodlee/get-transactions',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                accessToken: accessToken
            }),
            success: function(response) {
                if (response && response.success) {
                    if (response.transactions && response.transactions.length > 0) {
                        displayTransactions(response.transactions);
                        showAlert(`✅ Found ${response.transactions.length} transactions`, 'success');
                    } else {
                        $('#transactions-content').html('<p style="padding: 20px;">No transactions found. Try connecting an account first.</p>');
                        showAlert('No transactions found', 'info');
                    }
                } else {
                    $('#transactions-content').html('<p style="padding: 20px;">No transactions available.</p>');
                    showAlert('No transactions found', 'info');
                }
                $('#get-transactions').prop('disabled', false).text('3️⃣ Fetch Transactions');
            },
            error: function(xhr) {
                console.error('Fetch transactions error', xhr);
                const error = xhr.responseJSON?.message || xhr.responseText || 'Failed to fetch transactions';
                showAlert('❌ ' + error, 'error');
                $('#transactions-content').html(`<div class="alert alert-error">${error}</div>`);
                $('#get-transactions').prop('disabled', false).text('3️⃣ Fetch Transactions');
            },
            timeout: 20000
        });
    }

    function displayTransactions(transactions) {
        if (!transactions || transactions.length === 0) {
            $('#transactions-content').html('<p style="padding: 20px;">No transactions found.</p>');
            return;
        }

        let html = `
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
        `;

        transactions.forEach(tx => {
            const amount = tx.amount?.amount ?? (tx.amount || 0);
            const currency = tx.amount?.currency || 'USD';
            const isDebit = tx.baseType === 'DEBIT';
            const amountColor = isDebit ? 'color: #e53e3e' : 'color: #38a169';
            
            html += `
                <tr>
                    <td>${tx.transactionDate || tx.date || 'N/A'}</td>
                    <td>${tx.description?.original || tx.description || 'N/A'}</td>
                    <td style="${amountColor}; font-weight:600;">
                        ${isDebit ? '-' : '+'}${currency} ${Math.abs(Number(amount)).toFixed(2)}
                    </td>
                    <td>${tx.baseType || 'N/A'}</td>
                    <td>${tx.category || 'Uncategorized'}</td>
                    <td>${tx.status || 'POSTED'}</td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        $('#transactions-content').html(html);
    }
</script>
</body>

</html>
