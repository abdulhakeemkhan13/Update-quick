@extends('layouts.admin')
@section('page-title')
    {{ __('Re-authenticate Bank') }}
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card p-3">
                <h5>Bank Re-authentication Required</h5>
                <p>The connection to <strong>{{ $account->institution_name }}</strong> requires you to re-enter credentials.
                </p>

                <button id="reauth-btn" class="btn btn-primary">Re-authenticate Bank</button>
                <a href="{{ route('getBalance', $account->institution_id) }}" class="btn btn-link">Cancel</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.plaid.com/link/v2/stable/link-initialize.js"></script>
    <script>
        document.getElementById('reauth-btn').addEventListener('click', async function() {
            try {
                const linkToken = "{{ $link_token }}";

                const handler = Plaid.create({
                    token: linkToken,
                    onSuccess: async function(public_token, metadata) {
                        // send public_token & account id to backend to exchange and persist
                        const res = await fetch("{{ route('plaid.exchangePublicToken') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': "{{ csrf_token() }}"
                            },
                            body: JSON.stringify({
                                public_token: public_token,
                                account_id: {{ $account->id }}
                            })
                        });

                        const json = await res.json();
                        if (json.status === 'success') {
                            // Reload to show updated balances/transactions
                            window.location.href =
                                "{{ route('getBalance', $account->institution_id) }}";
                        } else {
                            alert('Re-auth failed. Please try again.');
                        }
                    },
                    onExit: function(err, metadata) {
                        if (err) {
                            console.error('Link Exit Error', err);
                            alert('Plaid Link exited with an error. Please try again.');
                        }
                    }
                });

                handler.open();
            } catch (err) {
                console.error(err);
                alert('Unable to open Plaid Link.');
            }
        });
    </script>
@endsection
