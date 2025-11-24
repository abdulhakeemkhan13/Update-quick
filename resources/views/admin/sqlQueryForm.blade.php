@extends('layouts.admin')

@section('page-title')
    {{ __('AI SQL Query Assistant') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('AI SQL Query') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="#" class="btn btn-sm btn-primary" onclick="clearResults()">
            <i class="ti ti-refresh"></i> {{ __('Clear Results') }}
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Ask AI About Your Database') }}</h5>
                    <small class="text-muted">{{ __('Ask questions about your database in natural language') }}</small>
                </div>
                <div class="card-body">
                    <form id="aiSqlForm" onsubmit="return false;">
                        @csrf
                        <div class="row">
                            <div class="col-md-10">
                                <div class="form-group">
                                    <input type="text" name="question" id="question" 
                                           class="form-control" 
                                           placeholder="{{ __('Ask about your database (e.g., How many customers do we have?)') }}"
                                           required>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-primary w-100" id="askBtn">
                                    <i class="ti ti-send"></i> {{ __('Ask') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Section -->
    <div class="row" id="loadingSection" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ __('Loading...') }}</span>
                    </div>
                    <p class="mt-2">{{ __('AI is processing your query...') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <div class="row" id="resultsSection" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('AI Response') }}</h5>
                </div>
                <div class="card-body">
                    <div id="aiResponse"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Query Results Table -->
    <div class="row" id="queryResultsSection" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Query Results') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive" id="multiTableResults">
                        <table class="table table-striped" id="resultsTable">
                            <thead id="tableHead"></thead>
                            <tbody id="tableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('#askBtn').on('click', function() {
            submitQuery();
        });
        
        $('#question').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                submitQuery();
            }
        });
    });

    function submitQuery() {
        const question = $('#question').val().trim();
        if (!question) {
            if (typeof show_toastr === 'function') {
                show_toastr('Error', '{{ __("Please enter a question") }}', 'error');
            } else {
                alert('Please enter a question');
            }
            return;
        }

        $('#loadingSection').show();
        $('#resultsSection').hide();
        $('#queryResultsSection').hide();
        $('#askBtn').prop('disabled', true).html('<i class="ti ti-loader"></i> {{ __("Processing...") }}');

        $.ajax({
            url: '{{ route("ai-sql.ask") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                question: question,
                page: 1,
                per_page: 50
            },
            success: function(response) {
                console.log('AJAX Success:', response);
                $('#loadingSection').hide();
                $('#askBtn').prop('disabled', false).html('<i class="ti ti-send"></i> {{ __("Ask") }}');
                
                displayResponse(response);
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr, status, error);
                $('#loadingSection').hide();
                $('#askBtn').prop('disabled', false).html('<i class="ti ti-send"></i> {{ __("Ask") }}');
                
                let errorMessage = '{{ __("An error occurred while processing your request") }}';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                $('#resultsSection').show();
                $('#aiResponse').html(`
                    <div class="alert alert-danger">
                        <i class="ti ti-alert-circle"></i> ${errorMessage}
                    </div>
                `);
                
                if (typeof show_toastr === 'function') {
                    show_toastr('Error', errorMessage, 'error');
                }
            }
        });
    }

    function displayResponse(response) {
        let html = '';
        
        if (response.explanation) {
            html += `
                <div class="alert alert-info">
                    <h6><i class="ti ti-info-circle"></i> {{ __('AI Explanation') }}</h6>
                    <p class="mb-0">${response.explanation}</p>
                </div>
            `;
        }
        
        if (response.sql_query) {
            html += `
                <div class="alert alert-secondary">
                    <h6><i class="ti ti-code"></i> {{ __('Generated SQL Query') }}</h6>
                    <pre class="mb-0"><code>${response.sql_query}</code></pre>
                </div>
            `;
        }
        
        $('#aiResponse').html(html);
        $('#resultsSection').show();
        
        // Handle entire database response ONLY if mode is "entire_database"
        if (response.mode && response.mode === "entire_database" && response.tables) {
            let tablesHtml = "";
            for (let table in response.tables) {
                let t = response.tables[table];
                tablesHtml += `<h5 class="mt-3">${table}</h5>`;
                if (t.data.length > 0) {
                    tablesHtml += "<div class='table-responsive'><table class='table table-bordered'><thead><tr>";
                    Object.keys(t.data[0]).forEach(col => {
                        tablesHtml += `<th>${col}</th>`;
                    });
                    tablesHtml += "</tr></thead><tbody>";
                    t.data.forEach(row => {
                        tablesHtml += "<tr>";
                        Object.values(row).forEach(val => {
                            tablesHtml += `<td>${val !== null ? val : '-'}</td>`;
                        });
                        tablesHtml += "</tr>";
                    });
                    tablesHtml += "</tbody></table></div>";
                } else {
                    tablesHtml += "<p><em>No data found</em></p>";
                }
            }
            $('#multiTableResults').html(tablesHtml);
            $('#queryResultsSection').show();
        }
        // Handle normal query results
        else if (response.results && Array.isArray(response.results) && response.results.length > 0) {
            displayQueryResults(response.results);
        } 
        // Handle success message only
        else if (response.message && !response.results) {
            $('#aiResponse').append(`
                <div class="alert alert-success">
                    <i class="ti ti-check"></i> ${response.message}
                </div>
            `);
        }
    }

    function displayQueryResults(results) {
        if (!results || results.length === 0) return;
        
        const headers = Object.keys(results[0]);
        
        let headerHtml = '<tr>';
        headers.forEach(header => {
            headerHtml += `<th>${header}</th>`;
        });
        headerHtml += '</tr>';
        $('#tableHead').html(headerHtml);
        
        let bodyHtml = '';
        results.forEach(row => {
            bodyHtml += '<tr>';
            headers.forEach(header => {
                bodyHtml += `<td>${row[header] || '-'}</td>`;
            });
            bodyHtml += '</tr>';
        });
        $('#tableBody').html(bodyHtml);
        
        $('#queryResultsSection').show();
    }

    function clearResults() {
        $('#question').val('');
        $('#loadingSection').hide();
        $('#resultsSection').hide();
        $('#queryResultsSection').hide();
        $('#aiResponse').empty();
        $('#tableHead').empty();
        $('#tableBody').empty();
        $('#multiTableResults').empty();
    }
    </script>
@endsection
