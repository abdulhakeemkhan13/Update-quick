<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Searchable Dropdown</title>
    <style>
        * {
            box-sizing: border-box !important;
        }

        .searchbarbodySB {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            background-color: #f5f5f5 !important;
            padding: 20px !important;
        }

        .btn-box-searchSB {
            position: relative !important;
            max-width: 500px !important;
            margin: 20px 0 !important;
        }

        .searchable-dropdownSB {
            position: relative !important;
            width: 100% !important;
        }

        .dropdown-inputSB {
            width: 100% !important;
            padding: 12px 40px 12px 16px !important;
            border: 1px solid #d0d0d0 !important;
            border-radius: 4px !important;
            font-size: 14px !important;
            color: #666 !important;
            background-color: white !important;
            cursor: pointer !important;
            outline: none !important;
            transition: border-color 0.2s ease !important;
        }

        .dropdown-inputSB:focus {
            border-color: #007bff !important;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1) !important;
        }

        .dropdown-inputSB.activeSB {
            border-bottom-left-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
            border-bottom-color: transparent !important;
        }

        .dropdown-arrowSB {
            position: absolute !important;
            right: 12px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            width: 0 !important;
            height: 0 !important;
            border-left: 5px solid transparent !important;
            border-right: 5px solid transparent !important;
            border-top: 5px solid #666 !important;
            transition: transform 0.2s ease !important;
            pointer-events: none !important;
        }

        .dropdown-arrowSB.openSB {
            transform: translateY(-50%) rotate(180deg) !important;
        }

        .dropdown-listSB {
            position: absolute !important;
            top: 100% !important;
            left: 0 !important;
            right: 0 !important;
            background: white !important;
            border: 1px solid #d0d0d0 !important;
            border-top: none !important;
            border-radius: 0 0 4px 4px !important;
            max-height: 300px !important;
            overflow-y: auto !important;
            z-index: 1000 !important;
            display: none !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
        }

        .dropdown-listSB.showSB {
            display: block !important;
        }

        .dropdown-itemSB {
            padding: 12px 16px !important;
            font-size: 14px !important;
            color: #333 !important;
            cursor: pointer !important;
            border-bottom: 1px solid #f0f0f0 !important;
            transition: background-color 0.2s ease !important;
            display: flex !important;
            align-items: center !important;
        }

        .category-headerSB {
            padding: 8px 16px !important;
            background-color: #f8f9fa !important;
            font-weight: 600 !important;
            font-size: 12px !important;
            color: #666 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            border-bottom: 1px solid #e9ecef !important;
        }

        .report-iconSB {
            margin-right: 8px !important;
            color: #666 !important;
            width: 16px !important;
        }

        .dropdown-itemSB:hover {
            background-color: #f8f9fa !important;
        }

        .dropdown-itemSB:last-child {
            border-bottom: none !important;
        }

        .dropdown-itemSB.highlightedSB {
            background-color: #e3f2fd !important;
        }

        .no-resultsSB {
            padding: 12px 16px !important;
            color: #999 !important;
            font-style: italic !important;
        }

        /* Scrollbar styling */
        .dropdown-listSB::-webkit-scrollbar {
            width: 6px !important;
        }

        .dropdown-listSB::-webkit-scrollbar-track {
            background: #f1f1f1 !important;
        }

        .dropdown-listSB::-webkit-scrollbar-thumb {
            background: #c1c1c1 !important;
            border-radius: 3px !important;
        }

        .dropdown-listSB::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8 !important;
        }

        /* Demo styling */
        .demo-containerSB {
            background: white !important;
            padding: 18px !important;
            border-radius: 8px !important;
            max-width: 500px !important;
        }

        h2 {
            color: #333 !important;
            margin-bottom: 20px !important;
            font-size: 24px !important;
        }

        .selected-valueSB {
            margin-top: 20px !important;
            padding: 15px !important;
            background-color: #f8f9fa !important;
            border-radius: 4px !important;
            border-left: 4px solid #007bff !important;
        }
    </style>
</head>

<body class="searchbarbodySB">
    <div class="demo-containerSB">

        <div class="btn-box-searchSB">
            <div class="searchable-dropdownSB" id="reportDropdownSB">
                <input type="text" class="dropdown-inputSB" id="reportInputSB"
                    placeholder="Search reports by name or category..." autocomplete="off">
                <div class="dropdown-arrowSB"></div>
                <div class="dropdown-listSB" id="reportListSB"></div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            class SearchableDropdownSB {
                constructor(element, options = {}) {
                    this.element = element;
                    this.input = element.querySelector('.dropdown-inputSB');
                    this.arrow = element.querySelector('.dropdown-arrowSB');
                    this.list = element.querySelector('.dropdown-listSB');
                    this.options = options.data || [];
                    this.filteredOptions = [...this.options];
                    this.selectedValue = null;
                    this.isOpen = false;
                    this.highlightedIndex = -1;
                    this.init();
                }

                init() {
                    this.renderOptions();
                    this.bindEvents();
                }

                bindEvents() {
                    // Make input clickable to open dropdown
                    this.input.addEventListener('click', (e) => {
                        e.stopPropagation();
                        if (!this.isOpen) {
                            this.open();
                        }
                    });

                    // Handle typing for search
                    this.input.addEventListener('input', (e) => {
                        if (!this.isOpen) this.open();
                        this.filter(e.target.value);
                    });

                    this.input.addEventListener('keydown', (e) => {
                        switch (e.key) {
                            case 'ArrowDown':
                                e.preventDefault();
                                if (!this.isOpen) this.open();
                                this.highlightNext();
                                break;
                            case 'ArrowUp':
                                e.preventDefault();
                                this.highlightPrev();
                                break;
                            case 'Enter':
                                e.preventDefault();
                                if (this.isOpen && this.highlightedIndex >= 0) {
                                    this.selectOption(this.filteredOptions[this.highlightedIndex]);
                                }
                                break;
                            case 'Escape':
                                e.preventDefault();
                                this.close();
                                break;
                        }
                    });

                    document.addEventListener('click', (e) => {
                        if (!this.element.contains(e.target)) {
                            this.close();
                        }
                    });
                }

                renderOptions() {
                    if (this.filteredOptions.length === 0) {
                        this.list.innerHTML =
                            '<div class="no-resultsSB">No reports found matching your search</div>';
                        return;
                    }

                    let html = '';
                    let currentCategory = '';
                    let itemIndex = 0;

                    this.filteredOptions.forEach((option) => {
                        // Add category header if it's a new category
                        if (option.category !== currentCategory) {
                            currentCategory = option.category;
                            html += `<div class="category-headerSB">${option.category}</div>`;
                        }

                        html += `<div class="dropdown-itemSB" data-value="${option.value}" data-index="${itemIndex}">
                            <i class="${option.icon} report-iconSB"></i>
                            ${option.label}
                        </div>`;
                        itemIndex++;
                    });

                    this.list.innerHTML = html;

                    // Add click and hover events
                    this.list.querySelectorAll('.dropdown-itemSB').forEach(item => {
                        item.addEventListener('click', (e) => {
                            const value = e.currentTarget.dataset.value;
                            const option = this.filteredOptions.find(opt => opt.value ===
                            value);
                            this.selectOption(option);
                        });

                        item.addEventListener('mouseenter', (e) => {
                            this.highlightedIndex = parseInt(e.currentTarget.dataset.index);
                            this.updateHighlight();
                        });
                    });
                }

                filter(query) {
                    if (!query.trim()) {
                        this.filteredOptions = [...this.options];
                    } else {
                        this.filteredOptions = this.options.filter(option =>
                            option.label.toLowerCase().includes(query.toLowerCase()) ||
                            option.category.toLowerCase().includes(query.toLowerCase())
                        );
                    }
                    this.highlightedIndex = -1;
                    this.renderOptions();
                }

                selectOption(option) {
                    this.selectedValue = option;
                    this.input.value = option.label;
                    this.close();

                    // 🔑 Redirect to the selected route
                    if (option && option.value) {
                        window.location.href = option.value;
                    }
                }

                highlightNext() {
                    this.highlightedIndex = Math.min(this.highlightedIndex + 1, this.filteredOptions.length -
                        1);
                    this.updateHighlight();
                    this.scrollToHighlighted();
                }

                highlightPrev() {
                    this.highlightedIndex = Math.max(this.highlightedIndex - 1, 0);
                    this.updateHighlight();
                    this.scrollToHighlighted();
                }

                updateHighlight() {
                    this.list.querySelectorAll('.dropdown-itemSB').forEach((item, index) => {
                        item.classList.toggle('highlightedSB', index === this.highlightedIndex);
                    });
                }

                scrollToHighlighted() {
                    const highlighted = this.list.querySelector('.highlightedSB');
                    if (highlighted) {
                        highlighted.scrollIntoView({
                            block: 'nearest'
                        });
                    }
                }

                open() {
                    this.isOpen = true;
                    this.input.classList.add('activeSB');
                    this.arrow.classList.add('openSB');
                    this.list.classList.add('showSB');
                    this.input.focus();
                }

                close() {
                    this.isOpen = false;
                    this.input.classList.remove('activeSB');
                    this.arrow.classList.remove('openSB');
                    this.list.classList.remove('showSB');
                    this.highlightedIndex = -1;

                    // Reset to show all options when closed
                    if (!this.selectedValue) {
                        this.input.value = '';
                    }
                    this.filteredOptions = [...this.options];
                    this.renderOptions();
                }

                toggle() {
                    if (this.isOpen) {
                        this.close();
                    } else {
                        this.open();
                    }
                }
            }

            const reportOptions = [
                // ================== Business Overview ==================
                {
                    category: "Business Overview",
                    value: "{{ route('balance-sheet.index') }}",
                    label: "Balance Sheet",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Business Overview",
                    value: "{{ route('reports.profit_loss') }}",
                    label: "Profit and Loss",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Business Overview",
                    value: "{{ route('balance-sheet-detail.index') }}",
                    label: "Balance Sheet Detail",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Business Overview",
                    value: "{{ route('profit-loss-detail.index') }}",
                    label: "Profit and Loss Detail",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Business Overview",
                    value: "{{ route('balance-sheet-standard.index') }}",
                    label: "Balance Sheet Summary",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Business Overview",
                    value: "{{ route('profit-loss-by-month') }}",
                    label: "Profit and Loss By Month",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Business Overview",
                    value: "{{ route('balance-sheet-comparison.index') }}",
                    label: "Balance Sheet Comparison",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Business Overview",
                    value: "{{ route('profit-loss-comparison') }}",
                    label: "Profit and Loss Comparison",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Business Overview",
                    value: "{{ route('ledger.index') }}",
                    label: "General Ledger",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Business Overview",
                    value: "{{ route('profit-loss-quaterly') }}",
                    label: "Profit and Loss Quarterly",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Business Overview",
                    value: "{{ route('cash-flow.index') }}",
                    label: "Statement of Cash Flows",
                    icon: "bi bi-file-earmark-text"
                },

                // ================== Who Owes You ==================
                {
                    category: "Who Owes You",
                    value: "{{ route('receivables.aging_summary') }}",
                    label: "Accounts receivable aging summary",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Who Owes You",
                    value: "{{ route('receivables.invoices_received_payments') }}",
                    label: "Invoices and received payments",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Who Owes You",
                    value: "{{ route('receivables.aging_details') }}",
                    label: "Accounts receivable aging details",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Who Owes You",
                    value: "{{ route('receivables.open_invoice_list') }}",
                    label: "Open Invoice",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Who Owes You",
                    value: "{{ route('receivables.collection_details') }}",
                    label: "Collection Report",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Who Owes You",
                    value: "{{ route('receivables.invoice_list') }}",
                    label: "Invoice list",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Who Owes You",
                    value: "{{ route('receivables.customer_balance') }}",
                    label: "Customer balance summary",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Who Owes You",
                    value: "{{ route('receivables.customer_balance_detail') }}",
                    label: "Customer balance detail report",
                    icon: "bi bi-file-earmark-text"
                },

                // ================== Sales and Customers ==================
                {
                    category: "Sales and Customers",
                    value: "{{ route('report.salesbyCustomerTypeDetail') }}",
                    label: "Sales by Customer Type Detail",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Sales and Customers",
                    value: "{{ route('productservice.inventoryValuationSummary') }}",
                    label: "Inventory Valuation Summary",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Sales and Customers",
                    value: "{{ route('productservice.incomeByCustomerSummary') }}",
                    label: "Product/Service List",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Sales and Customers",
                    value: "{{ route('customer.contact.list') }}",
                    label: "Customer Contact List",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Sales and Customers",
                    value: "{{ route('productservice.SalesByProductServiceSummary') }}",
                    label: "Sales by Product/Service Summary",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Sales and Customers",
                    value: "{{ route('productservice.incomeByCustomerSummaryTwo') }}",
                    label: "Income by Customer Summary",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Sales and Customers",
                    value: "{{ route('productservice.SalesByProductServiceDetail') }}",
                    label: "Sales by Product/Service Detail",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Sales and Customers",
                    value: "{{ route('customer.contact.list.phone.numbers') }}",
                    label: "Customer Phone List",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Sales and Customers",
                    value: "{{ route('report.sales.salesByCustomerSummary') }}",
                    label: "Sales by Customer Summary",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Sales and Customers",
                    value: "{{ route('report.sales.salesByCustomerDetail') }}",
                    label: "Sales by Customer Detail",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Sales and Customers",
                    value: "{{ route('report.depositDetail') }}",
                    label: "Deposit Detail",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Sales and Customers",
                    value: "{{ route('productservice.transactionListByCustomer') }}",
                    label: "Transaction List by Customer",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Sales and Customers",
                    value: "{{ route('productservice.estimatesByCustomer') }}",
                    label: "Estimates by Customer",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Sales and Customers",
                    value: "{{ route('productservice.inventoryValuationDetail') }}",
                    label: "Inventory Valuation Detail",
                    icon: "bi bi-file-earmark-text"
                },

                // ================== Who You Owe ==================
                {
                    category: "Who You Owe",
                    value: "{{ route('payables.aging_summary') }}",
                    label: "Accounts payable aging summary",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Who You Owe",
                    value: "{{ route('payables.unpaid_bills_report') }}",
                    label: "Unpaid Bills",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Who You Owe",
                    value: "{{ route('payables.aging_details') }}",
                    label: "Accounts payable aging details",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Who You Owe",
                    value: "{{ route('payables.vendor_balance_summary') }}",
                    label: "Vendor balance summary",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Who You Owe",
                    value: "{{ route('payables.bills_payments') }}",
                    label: "Bills and Applied payments",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Who You Owe",
                    value: "{{ route('payables.vendor_balance_detail') }}",
                    label: "Vendor balance detail",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Who You Owe",
                    value: "{{ route('payables.bill_payment_list') }}",
                    label: "Bill Payment List",
                    icon: "bi bi-file-earmark-text"
                },

                // ================== Expenses And Vendors ==================
                {
                    category: "Expenses And Vendors",
                    value: "{{ route('expenses.open_purchase_order_detail') }}",
                    label: "Open Purchase Order Detail",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Expenses And Vendors",
                    value: "{{ route('expenses.vendors_phone_list') }}",
                    label: "Vendors Phone List",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Expenses And Vendors",
                    value: "{{ route('expenses.open_purchase_order_list') }}",
                    label: "Open Purchase Order List",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Expenses And Vendors",
                    value: "{{ route('expenses.transaction_list_by_vendor') }}",
                    label: "Transaction List by Vendor",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Expenses And Vendors",
                    value: "{{ route('expenses.purchase_list') }}",
                    label: "Purchase List",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Expenses And Vendors",
                    value: "{{ route('expenses.expenses_by_vendor_summary') }}",
                    label: "Expenses by Vendor Summary",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Expenses And Vendors",
                    value: "{{ route('expenses.purchase_by_vendor') }}",
                    label: "Purchase by Vendor Detail",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Expenses And Vendors",
                    value: "{{ route('expenses.vendors_contact_list') }}",
                    label: "Vendors Contact List",
                    icon: "bi bi-file-earmark-text"
                },

                // ================== Sales Tax ==================
                {
                    category: "Sales Tax",
                    value: "{{ route('SalesTaxLiabilityReport') }}",
                    label: "Sales Tax Liability Report",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Sales Tax",
                    value: "{{ route('report.taxableSalesSummary') }}",
                    label: "Taxable Sales Summary",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Sales Tax",
                    value: "{{ route('report.taxableSalesDetail') }}",
                    label: "Taxable Sales Detail",
                    icon: "bi bi-file-earmark-text"
                },

                // ================== Employees ==================
                {
                    category: "Employees",
                    value: "{{ route('employees.employeecontactlist') }}",
                    label: "Employee Contact List",
                    icon: "bi bi-people"
                },

                // ================== Accounting Reports ==================
                {
                    category: "Accounting Reports",
                    value: "{{ route('report.account.statement') }}",
                    label: "Account Statement",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Accounting Reports",
                    value: "{{ route('report.invoice.summary') }}",
                    label: "Invoice Summary",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Accounting Reports",
                    value: "{{ route('report.sales') }}",
                    label: "Sales Report",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Accounting Reports",
                    value: "{{ route('report.receivables') }}",
                    label: "Receivables",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Accounting Reports",
                    value: "{{ route('report.payables') }}",
                    label: "Payables",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Accounting Reports",
                    value: "{{ route('report.bill.summary') }}",
                    label: "Bill Summary",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Accounting Reports",
                    value: "{{ route('report.product.stock.report') }}",
                    label: "Product Stock",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Accounting Reports",
                    value: "{{ route('report.monthly.cashflow') }}",
                    label: "Cash Flow",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Accounting Reports",
                    value: "{{ route('report.income.summary') }}",
                    label: "Income Summary",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Accounting Reports",
                    value: "{{ route('report.expense.summary') }}",
                    label: "Expense Summary",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Accounting Reports",
                    value: "{{ route('report.income.vs.expense.summary') }}",
                    label: "Income VS Expense",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Accounting Reports",
                    value: "{{ route('report.tax.summary') }}",
                    label: "Tax Summary",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Accounting Reports",
                    value: "{{ route('transaction.index') }}",
                    label: "All Transactions",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Accounting Reports",
                    value: "{{ route('transaction.bankTransactions') }}",
                    label: "Bank Transactions",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Accounting Reports",
                    value: "{{ route('reciept.index') }}",
                    label: "Receipts",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Accounting Reports",
                    value: "{{ route('report.ledger', 0) }}",
                    label: "Ledger Summary",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Accounting Reports",
                    value: "{{ route('report.profit.loss') }}",
                    label: "Profit & Loss (Alt)",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Accounting Reports",
                    value: "{{ route('trial-balance.index') }}",
                    label: "Trial Balance",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Accounting Reports",
                    value: "{{ route('budget.index') }}",
                    label: "Budget Planner",
                    icon: "bi bi-file-earmark-text"
                },
                {
                    category: "Accounting Reports",
                    value: "{{ route('goal.index') }}",
                    label: "Financial Goal",
                    icon: "bi bi-file-earmark-text"
                },

                // ================== HRM Reports ==================
                {
                    category: "HRM Reports",
                    value: "{{ route('report.payroll') }}",
                    label: "Payroll",
                    icon: "bi bi-people"
                },
                {
                    category: "HRM Reports",
                    value: "{{ route('report.leave') }}",
                    label: "Leave",
                    icon: "bi bi-people"
                },
                {
                    category: "HRM Reports",
                    value: "{{ route('report.monthly.attendance') }}",
                    label: "Monthly Attendance",
                    icon: "bi bi-people"
                },

                // ================== CRM Reports (if enabled) ==================
                {
                    category: "CRM Reports",
                    value: "{{ route('report.lead') }}",
                    label: "Lead",
                    icon: "bi bi-person-check"
                },
                {
                    category: "CRM Reports",
                    value: "{{ route('report.deal') }}",
                    label: "Deal",
                    icon: "bi bi-person-check"
                },

                // ================== Project Reports (if enabled) ==================
                {
                    category: "Project Reports",
                    value: "{{ route('project.dashboard') }}",
                    label: "Project Dashboard",
                    icon: "bi bi-kanban"
                },

                // ================== POS Reports (existing) ==================
                {
                    category: "POS Reports",
                    value: "{{ route('report.warehouse') }}",
                    label: "Warehouse Report",
                    icon: "bi bi-shop"
                },
                {
                    category: "POS Reports",
                    value: "{{ route('report.daily.purchase') }}",
                    label: "Purchase Daily/Monthly Report",
                    icon: "bi bi-shop"
                },
                {
                    category: "POS Reports",
                    value: "{{ route('report.daily.pos') }}",
                    label: "POS Daily/Monthly Report",
                    icon: "bi bi-shop"
                },
                {
                    category: "POS Reports",
                    value: "{{ route('report.pos.vs.purchase') }}",
                    label: "POS VS Purchase Report",
                    icon: "bi bi-shop"
                }
            ];


            // Initialize dropdown
            new SearchableDropdownSB(
                document.getElementById('reportDropdownSB'), {
                    data: reportOptions
                }
            );
        });
    </script>



</body>

</html>
