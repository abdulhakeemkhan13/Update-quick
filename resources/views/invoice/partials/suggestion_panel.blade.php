<aside class="customer-suggestions-col" style="box-shadow: -12px 0 10px -10px #d4d7dc;">
    <div class="suggestions-header">
        <div>
            <div class="d-flex align-self-end">
                <button class="arrow" style="border: none; background: 0 0; padding: 0;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        color="currentColor" width="24px" height="24px" focusable="false" aria-hidden="true">
                        <path fill="currentColor"
                            d="M15.009 19.022a1 1 0 0 1-.708-.294L8.31 12.72a1 1 0 0 1 0-1.415l6.009-5.991a1 1 0 0 1 1.414 1.416l-5.3 5.285 5.285 5.3a1 1 0 0 1-.708 1.706z">
                        </path>
                    </svg>
                </button>
                <h3>Suggested transactions</h3>
            </div>
            <p class="suggestions-subtitle">
                We've found one or more transactions linked to this customer.
                Select the ones you'd like to add to the invoice.
            </p>
        </div>
        <button type="button" class="suggestions-close">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                color="currentColor" width="24px" height="24px" focusable="false" aria-hidden="true" class="">
                <path fill="currentColor"
                    d="m13.432 11.984 5.3-5.285a1 1 0 1 0-1.412-1.416l-5.3 5.285-5.285-5.3A1 1 0 1 0 5.319 6.68l5.285 5.3L5.3 17.265a1 1 0 1 0 1.412 1.416l5.3-5.285L17.3 18.7a1 1 0 1 0 1.416-1.412z">
                </path>
            </svg>
        </button>
    </div>

    <div class="suggestions-toolbar">
        {{-- QBO-like filter button --}}
        <button type="button" class="suggestions-filter-btn">
            <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="24px"
                height="24px" fill="currentColor" color="#6B6C72">
                <path
                    d="M11 21.72a2 2 0 01-2-2v-4.429L3.134 6.074A2 2 0 014.822 3h14.356a2 2 0 011.687 3.074L15 15.291v3.928a2 2 0 01-1.515 1.94l-2 .5c-.159.04-.321.06-.485.061zM4.822 5l6.022 9.463A1 1 0 0111 15v4.719l2-.5V15a1 1 0 01.156-.537L19.178 5H4.822z">
                </path>
            </svg>
            <span>Filter</span>
        </button>

        <button type="button" class="link-button suggestions-addall">
            Add all
        </button>

        {{-- Filter popup --}}
        <div class="suggestions-filter-panel" id="suggestions-filter-panel">
            <div class="suggestions-filter-header">
                <div class="suggestions-filter-actions">
                    <button type="button" class="suggestions-filter-reset" id="suggestions-filter-reset">
                        Reset
                    </button>
                    <button type="button" class="suggestions-filter-apply" id="suggestions-filter-apply">
                        Apply filter
                    </button>
                </div>
                <button type="button" class="suggestions-filter-close">&times;</button>
            </div>

            <div class="suggestions-filter-title">Filter by</div>

            <div class="suggestions-filter-row">
                <label for="suggestions-filter-type">Transaction type</label>
                <select id="suggestions-filter-type" class="form-select">
                    <option value="all">All transactions</option>
                    <option value="estimate">Estimates only</option>
                </select>
            </div>

            <div class="suggestions-filter-row">
                <label for="suggestions-filter-date">Date</label>
                <select id="suggestions-filter-date" class="form-select">
                    <option value="all">All dates</option>
                    <option value="last30">Last 30 days</option>
                    <option value="last365">Last 12 months</option>
                </select>
            </div>
        </div>
    </div>

    <div id="suggestions-list" style="padding: 0; overflow-y: auto;">
        <p style="font-size:12px;color:#6b6f73;">
            Select a customer to see suggested transactions.
        </p>
    </div>
</aside>

