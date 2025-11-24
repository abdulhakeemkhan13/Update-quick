<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Account Filter</title>
    {{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> --}}
    {{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css"> --}}
    
    <style>
        /* Embedded all CSS styles into the HTML file */
        .bank-filter-dropdown {
            position: relative;
            width: 100%;
        }

        .dropdown-toggle-custom {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .dropdown-toggle-custom:hover {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .selected-bank {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .bank-icon {
            width: 40px;
            height: 40px;
            background: #0d6efd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .bank-name {
            flex: 1;
            font-weight: 500;
            color: #333;
        }

        .dropdown-arrow {
            transition: transform 0.2s ease;
            color: #6c757d;
        }

        .dropdown-toggle-custom.active .dropdown-arrow {
            transform: rotate(180deg);
        }

        .dropdown-menu-custom {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            z-index: 1000;
            display: none;
            margin-top: 0.25rem;
        }

        .dropdown-menu-custom.show {
            display: block;
        }

        .dropdown-header {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            font-size: 0.875rem;
        }

        .account-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .account-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #f8f9fa;
            cursor: pointer;
            transition: background-color 0.2s ease;
            gap: 1rem;
        }

        .account-item:hover {
            background-color: #f8f9fa;
        }

        .account-item:last-child {
            border-bottom: none;
        }

        .account-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .account-icon.checking {
            background: #0d6efd;
        }

        .account-icon.savings {
            background: #198754;
        }

        .account-icon.mastercard {
            background: #0d6efd;
        }

        .account-details {
            flex: 1;
        }

        .account-name {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .account-balance {
            color: #6c757d;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .account-transactions {
            color: #6c757d;
            font-size: 0.875rem;
        }

        .account-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
        }

        .updated-time {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .edit-icon {
            color: #6c757d;
            cursor: pointer;
        }

        .edit-icon:hover {
            color: #0d6efd;
        }

        .dropdown-footer {
            padding: 1rem;
            border-top: 1px solid #dee2e6;
            text-align: center;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .account-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .account-actions {
                align-items: flex-start;
                flex-direction: row;
                justify-content: space-between;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <form id="bankTransaction" method="GET">
                    <div class="row">
                        <!-- Bank Account Filter -->
                        <div class="col-xl-6 col-lg-8 col-md-10 col-sm-12 col-12">
                            <div class="btn-box">
                                <label for="bank" class="form-label">Bank Accounts</label>
                                <div class="bank-filter-dropdown">
                                    <div class="dropdown-toggle-custom" id="bankDropdownToggle">
                                        <div class="selected-bank">
                                            <div class="bank-icon">
                                                <i class="bi bi-credit-card-2-front"></i>
                                            </div>
                                            <span class="bank-name">Checking</span>
                                            <i class="bi bi-chevron-down dropdown-arrow"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="dropdown-menu-custom" id="bankDropdownMenu">
                                        <div class="dropdown-header">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-primary">Hide account cards</span>
                                                <span class="text-primary">Reorder accounts</span>
                                            </div>
                                        </div>
                                        
                                        <div class="account-list">
                                            <!-- Dynamic bank accounts will be rendered here -->
                                        </div>
                                        
                                        <div class="dropdown-footer">
                                            <button type="button" class="btn btn-outline-success btn-sm">
                                                Try new banking page
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Hidden select for form submission -->
                                <select name="bank" id="bankSelect" class="d-none">
                                    <option value="">All Banks</option>
                                    <!-- Dynamic options will be rendered here -->
                                </select>
                            </div>
                        </div>
                        
                        <!-- Filter Actions -->
                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 mt-4">
                            <div class="mt-2">
                                <button type="submit" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Apply">
                                    <i class="bi bi-search"></i>
                                </button>
                                
                                <a href="#" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Reset">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        /* Embedded all JavaScript functionality into the HTML file */
        document.addEventListener("DOMContentLoaded", () => {
            const bankAccounts = [
                {
                    id: 'checking',
                    name: 'Example Bank U.S.A. - Checking',
                    balance: -3621.93,
                    transactions: 25,
                    type: 'checking',
                    icon: 'bi-credit-card-2-front',
                    updated: 'Updated moments ago'
                },
                {
                    id: 'savings',
                    name: 'Example Bank U.S.A. - Savings',
                    balance: 200.00,
                    transactions: 1,
                    type: 'savings',
                    icon: 'bi-piggy-bank',
                    updated: 'Updated moments ago'
                },
                {
                    id: 'mastercard',
                    name: 'Example Bank U.S.A. - Mastercard',
                    balance: 304.96,
                    transactions: 7,
                    type: 'mastercard',
                    icon: 'bi-credit-card',
                    updated: 'Updated moments ago'
                }
            ];

            function renderBankAccounts() {
                const accountList = document.querySelector('.account-list');
                const bankSelect = document.getElementById('bankSelect');
                
                // Clear existing accounts
                accountList.innerHTML = '';
                
                // Clear existing select options (except "All Banks")
                const allBanksOption = bankSelect.querySelector('option[value=""]');
                bankSelect.innerHTML = '';
                bankSelect.appendChild(allBanksOption);
                
                bankAccounts.forEach(account => {
                    // Create account item
                    const accountItem = document.createElement('div');
                    accountItem.className = 'account-item';
                    accountItem.setAttribute('data-value', account.id);
                    
                    accountItem.innerHTML = `
                        <div class="account-icon ${account.type}">
                            <i class="bi ${account.icon}"></i>
                        </div>
                        <div class="account-details">
                            <div class="account-name">${account.name}</div>
                            <div class="account-balance">Bank balance: ${account.balance >= 0 ? '$' : '-$'}${Math.abs(account.balance).toFixed(2)}</div>
                            <div class="account-transactions">${account.transactions} Transaction${account.transactions !== 1 ? 's' : ''}</div>
                        </div>
                        <div class="account-actions">
                            <span class="updated-time">${account.updated}</span>
                            ${account.type === 'checking' ? '<i class="bi bi-pencil edit-icon"></i>' : ''}
                        </div>
                    `;
                    
                    accountList.appendChild(accountItem);
                    
                    // Create select option
                    const option = document.createElement('option');
                    option.value = account.id;
                    option.textContent = account.name;
                    bankSelect.appendChild(option);
                });
                
                // Re-attach event listeners to new account items
                attachAccountItemListeners();
            }

            function attachAccountItemListeners() {
                const accountItems = document.querySelectorAll(".account-item");
                
                accountItems.forEach((item) => {
                    item.addEventListener("click", function (e) {
                        e.preventDefault();

                        const value = this.getAttribute("data-value");
                        const account = bankAccounts.find(acc => acc.id === value);
                        
                        if (account) {
                            // Update the selected bank display
                            bankNameSpan.textContent = account.name.split(" - ")[1] || account.name;

                            // Update the hidden select value
                            bankSelect.value = value;

                            // Close dropdown
                            dropdownToggle.classList.remove("active");
                            dropdownMenu.classList.remove("show");

                            // Update selected state
                            accountItems.forEach((otherItem) => {
                                otherItem.classList.remove("selected");
                            });
                            this.classList.add("selected");
                        }
                    });
                });
            }

            const dropdownToggle = document.getElementById("bankDropdownToggle")
            const dropdownMenu = document.getElementById("bankDropdownMenu")
            const bankSelect = document.getElementById("bankSelect")
            const bankNameSpan = document.querySelector(".bank-name")

            renderBankAccounts();

            // Toggle dropdown
            dropdownToggle.addEventListener("click", (e) => {
                e.preventDefault()
                e.stopPropagation()

                dropdownToggle.classList.toggle("active")
                dropdownMenu.classList.toggle("show")
            })

            // Close dropdown when clicking outside
            document.addEventListener("click", (e) => {
                if (!dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                    dropdownToggle.classList.remove("active")
                    dropdownMenu.classList.remove("show")
                }
            })

            // Handle form submission
            document.getElementById("bankTransaction").addEventListener("submit", function (e) {
                e.preventDefault()

                // Get form data
                const formData = new FormData(this)
                const selectedBank = formData.get("bank")

                console.log("Form submitted with bank:", selectedBank)

                // Here you would typically send the data to your server
                // For demo purposes, we'll just show an alert
                alert(`Filter applied for: ${selectedBank || "All Banks"}`)
            })

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            const tooltipList = tooltipTriggerList.map((tooltipTriggerEl) => new window.bootstrap.Tooltip(tooltipTriggerEl))

            window.addBankAccount = function(accountData) {
                bankAccounts.push(accountData);
                renderBankAccounts();
            }

            window.updateBankAccount = function(accountId, updatedData) {
                const accountIndex = bankAccounts.findIndex(acc => acc.id === accountId);
                if (accountIndex !== -1) {
                    bankAccounts[accountIndex] = { ...bankAccounts[accountIndex], ...updatedData };
                    renderBankAccounts();
                }
            }
        })
    </script>
</body>
</html>
