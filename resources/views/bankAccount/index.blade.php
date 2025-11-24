@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Bank Account') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Bank Account') }}</li>
@endsection
@push('script-page')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"
        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="https://cdn.plaid.com/link/v2/stable/link-initialize.js"></script>
    <script>
        $(".link-account").click(function() {
            createLinkToken();
        });

        function createLinkToken() {
            $.ajax({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
                },
                url: "{{ route('createLinkToken') }}",
                type: "GET",
                dataType: "json",
                success: function(response) {
                    const data = JSON.parse(response.data);
                    linkPlaidAccount(data.link_token);
                },
                error: function(err) {
                    console.error("Error creating link token: ", err);
                    Swal.fire('Error', 'Could not create link token', 'error');
                }
            });
        }

        function linkPlaidAccount(linkToken) {
            const handler = Plaid.create({
                token: linkToken,
                onSuccess: function(public_token, metadata) {
                    // 1. Show "Getting Holdings..." loader
                    Swal.fire({
                        title: 'Fetching Holdings…',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    // 2. Exchange token & link account
                    $.ajax({
                        headers: {
                            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
                        },
                        url: "{{ route('linkPlaidAccount') }}",
                        type: "POST",
                        data: {
                            public_token: public_token,
                            accounts: metadata.accounts,
                            institution: metadata.institution,
                            link_session_id: metadata.link_session_id,
                            link_token: linkToken
                        },
                        dataType: "json",
                        success: function(data) {
                            // 3. Import holdings
                            getInvestmentHoldings(data.item_id);
                        },
                        error: function(err) {
                            Swal.close();
                            console.error("Error linking Plaid account: ", err);
                            Swal.fire('Error', 'Failed to link Plaid account', 'error');
                        }
                    });
                },
                onExit: function(err, metadata) {
                    console.error("Plaid Link exited:", err, metadata);
                    handler.destroy();
                    if (!metadata.link_session_id && metadata.status === "requires_credentials") {
                        createLinkToken();
                    }
                }
            });
            handler.open();
        }

        function getInvestmentHoldings(itemId) {
            $.ajax({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
                },
                url: "{{ route('getInvestmentHoldings') }}",
                type: "POST",
                data: {
                    itemId: itemId
                },
                dataType: "json",
                success: function(data) {
                    // 4. On success, show "Getting Banks..." and fetch banks
                    Swal.fire({
                        title: 'Fetching Accounts...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    getBanks();
                },
                error: function(err) {
                    Swal.close();
                    console.error("Error importing holdings:", err);
                    Swal.fire('Error', 'Failed to import holdings', 'error');
                }
            });
        }

        function getBanks() {
            $.ajax({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
                },
                url: "{{ route('getBanks') }}",
                type: "GET",
                dataType: "json",
                success: function(response) {
                    // 5. Finally reload the page
                    Swal.close();
                    window.location.reload();
                },
                error: function(err) {
                    Swal.close();
                    console.error('Error getting banks.', err);
                    Swal.fire('Error', 'Failed to fetch banks', 'error');
                }
            });
        }
    </script>
@endpush

@section('action-btn')
    <div class="float-end">
        @can('create bank account')
            <a href="#" data-url="{{ route('bank-account.create') }}" data-ajax-popup="true" data-size="lg"
                data-bs-toggle="tooltip" title="{{ __('Create') }}" data-title="{{ __('Create New Bank Account') }}"
                class="btn btn-sm btn-primary">
                {{ __('Create New Bank Account') }}
                <i class="ti ti-plus"></i>
            </a>
            {{-- //link plaid account --}}
            <button type="button" class="link-account btn btn-sm btn-primary">Link Plaid</button>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style table-border-style">
                    <h5></h5>
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Chart Of Account') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Bank') }}</th>
                                    <th>{{ __('Account Number') }}</th>
                                    <th>{{ __('Current Balance') }}</th>
                                    <th>{{ __('Contact Number') }}</th>
                                    <th>{{ __('Bank Branch') }}</th>
                                    <th width="10%"> {{ __('Action') }}</th>
                                </tr>
                            </thead>

                            <tbody id="bank_accounts_rows">
                                @php
                                    $groupedAccounts = $accounts->groupBy(function ($acc) {
                                        return $acc->institution_id ?? 'manual_' . $acc->id;
                                    });
                                @endphp

                                @foreach ($groupedAccounts as $key => $group)
                                    @php
                                        $isManual = str_starts_with($key, 'manual_');
                                        $account = $group->first();
                                        $totalBalance = $isManual
                                            ? $account->opening_balance
                                            : $group->sum('opening_balance');
                                    @endphp

                                    <tr class="font-style">
                                        {{-- Chart Account / Accounts button --}}
                                        <td>
                                            @if (!empty($account->chartAccount))
                                                {{ $account->chartAccount->name }}
                                            @else
                                                <a href="{{ route('getBalance', ['id' => $account->institution_id]) }}"
                                                    class="btn btn-sm btn-primary">
                                                    {{ __('Accounts') }}
                                                </a>
                                            @endif
                                        </td>

                                        <td>{{ $account->holder_name }}</td>
                                        <td>{{ !empty($account->bank_name) ? $account->bank_name : ucfirst($account->institution_name) }}
                                        <td>
                                            <?php
                                            echo !empty($account->account_number) ? $account->account_number : str_replace('ins_', '', $account->institution_id);
                                            ?>
                                        </td>


                                        {{-- Show summed balance for institution OR manual balance --}}
                                        <td>{{ \Auth::user()->priceFormat($totalBalance) }}</td>

                                        <td>{{ $account->contact_number }}</td>
                                        <td>{{ $account->bank_address }}</td>

                                        @if ($account->chart_account_id != 0)
                                            @if (Gate::check('edit bank account') || Gate::check('delete bank account'))
                                                <td class="Action">
                                                    <span>
                                                        @if ($account->holder_name != 'Cash')
                                                            @can('edit bank account')
                                                                <div class="action-btn bg-primary ms-2">
                                                                    <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                                        data-url="{{ route('bank-account.edit', $account->id) }}"
                                                                        data-ajax-popup="true" title="{{ __('Edit') }}"
                                                                        data-title="{{ __('Edit Bank Account') }}"
                                                                        data-bs-toggle="tooltip" data-size="lg">
                                                                        <i class="ti ti-pencil text-white"></i>
                                                                    </a>
                                                                </div>
                                                            @endcan
                                                            @can('delete bank account')
                                                                <div class="action-btn bg-danger ms-2">
                                                                    {!! Form::open([
                                                                        'method' => 'DELETE',
                                                                        'route' => ['bank-account.destroy', $account->id],
                                                                        'id' => 'delete-form-' . $account->id,
                                                                    ]) !!}
                                                                    <a href="#"
                                                                        class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                                        data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                        data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                        data-confirm-yes="document.getElementById('delete-form-{{ $account->id }}').submit();">
                                                                        <i class="ti ti-trash text-white"></i>
                                                                    </a>
                                                                    {!! Form::close() !!}
                                                                </div>
                                                            @endcan
                                                        @else
                                                            -
                                                        @endif
                                                    </span>
                                                </td>
                                            @endif
                                        @else
                                            <td>
                                                <a href="{{ route('updateBalance', ['id' => $account->institution_id]) }}"
                                                    class="btn btn-sm btn-primary">
                                                    {{ __('Update Balance') }}
                                                </a>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
