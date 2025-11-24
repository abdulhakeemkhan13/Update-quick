<?php

namespace App\Http\Controllers;

use App\Models\Holdings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use TomorrowIdeas\Plaid\Entities\User;
use TomorrowIdeas\Plaid\Plaid;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\PlaidAccounts;
use App\Models\BankAccount;
use TomorrowIdeas\Plaid\PlaidRequestException;
// use App\Models\Holdings;
use Illuminate\Http\Response;

class PlaidController extends Controller
{

    protected Plaid $plaid;

    public function __construct()
    {
        $this->plaid = new Plaid(
            env('PLAID_CLIENT_ID'),
            env('PLAID_SECRET'),
            env('PLAID_ENV')
        );
    }
    public function createLinkToken()
    {
        $user_id = Auth::user()->id;
        $plaidUser = new User($user_id);
        $plaid = new Plaid(env('PLAID_CLIENT_ID'), env('PLAID_SECRET'), env('PLAID_ENV'));
        $response = $plaid->tokens->create('Plaid Test', 'en', ['US'], $plaidUser, ['investments'], env('PLAID_WEBHOOK'));
        return response()->json([
            'result' => 'success',
            'data' => json_encode($response)
        ], 200);
    }
    // public function placeTransaction(Request $request)
    // {
    //     $accountId = $request->input('account_id');
    //     $bankAccount = BankAccount::where('account_id', $accountId)
    //         ->where('user_id', Auth::id())
    //         ->firstOrFail();
    //     $accessToken = $bankAccount->plaid_access_token;

    //     try {
    //         // Step 1: Authorize transfer
    //         $client = new \GuzzleHttp\Client();
    //         $authResponse = $client->post($this->getPlaidUrl('/transfer/authorization/create'), [
    //             'json' => [
    //                 'client_id' => env('PLAID_CLIENT_ID'),
    //                 'secret' => env('PLAID_SECRET'),
    //                 'access_token' => $accessToken,
    //                 'account_id' => $accountId,
    //                 'type' => 'debit',   // debit = pull funds from user account
    //                 'network' => 'ach',
    //                 'amount' => '10.00',   // example amount
    //                 'ach_class' => 'ppd',
    //                 'user' => [
    //                     'legal_name' => Auth::user()->name,
    //                     'email_address' => Auth::user()->email,
    //                 ]
    //             ]
    //         ]);

    //         $authBody = json_decode($authResponse->getBody(), true);
    //         if (!isset($authBody['authorization']['id'])) {
    //             return back()->with('error', 'Authorization failed: ' . json_encode($authBody));
    //         }
    //         $authorizationId = $authBody['authorization']['id'];
    //         $transferResponse = $client->post($this->getPlaidUrl('/transfer/create'), [
    //             'json' => [
    //                 'client_id' => env('PLAID_CLIENT_ID'),
    //                 'secret' => env('PLAID_SECRET'),
    //                 'access_token' => $accessToken,
    //                 'account_id' => $accountId,
    //                 'authorization_id' => $authorizationId,
    //                 'amount' => '10.00',          // must match auth step
    //                 'description' => 'Test Payment'     // <= 15 chars
    //             ]
    //         ]);
    //         $transferBody = json_decode($transferResponse->getBody(), true);
    //         // dd($transferBody);
    //         $transferBody = json_decode($transferResponse->getBody(), true);

    //         if (!isset($transferBody['transfer']['id'])) {
    //             return back()->with('error', 'Transfer failed: ' . json_encode($transferBody));
    //         }

    //         $transferId = $transferBody['transfer']['id'];
    //         // dd($transferId);

    //         return back()->with('success', 'Transaction placed successfully. ID: ' . $transferId);

    //     } catch (\GuzzleHttp\Exception\RequestException $e) {
    //         $error = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
    //         dd($error);
    //         \Log::error("Plaid transaction error: " . $error);
    //         return back()->with('error', 'Transaction failed: ' . $error);
    //     } catch (\Throwable $e) {
    //         dd($e->getMessage());
    //         \Log::error("Unexpected transaction error: " . $e->getMessage());
    //         return back()->with('error', 'Transaction failed: ' . $e->getMessage());
    //     }
    // }

    /**
     * Helper to get Plaid API URL based on environment
     */
    public function placeTransaction(Request $request)
    {
        $accountId = $request->input('account_id');

        $bankAccount = BankAccount::where('account_id', $accountId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $accessToken = $bankAccount->plaid_access_token;

        try {
            $client = new \GuzzleHttp\Client();

            // 1) Authorization
            $authResponse = $client->post($this->getPlaidUrl('/transfer/authorization/create'), [
                'json' => [
                    'client_id' => env('PLAID_CLIENT_ID'),
                    'secret' => env('PLAID_SECRET'),
                    'access_token' => $accessToken,
                    'account_id' => $accountId,
                    'type' => 'debit',
                    'network' => 'ach',
                    'amount' => '10.00',
                    'ach_class' => 'ppd',
                    'user' => [
                        'legal_name' => Auth::user()->name,
                        'email_address' => Auth::user()->email,
                    ]
                ]
            ]);

            $authBody = json_decode($authResponse->getBody(), true);
            $authorizationId = $authBody['authorization']['id'] ?? null;

            if (!$authorizationId) {
                return back()->with('error', 'Authorization failed: ' . json_encode($authBody));
            }

            // 2) Create transfer
            $transferResponse = $client->post($this->getPlaidUrl('/transfer/create'), [
                'json' => [
                    'client_id' => env('PLAID_CLIENT_ID'),
                    'secret' => env('PLAID_SECRET'),
                    'access_token' => $accessToken,
                    'account_id' => $accountId,
                    'authorization_id' => $authorizationId,
                    'amount' => '10.00',
                    'description' => 'Test Payment'
                ]
            ]);

            $transferBody = json_decode($transferResponse->getBody()->getContents(), true);
            $transferId = $transferBody['transfer']['id'] ?? null;

            if (!$transferId) {
                return back()->with('error', 'Transfer failed: ' . json_encode($transferBody));
            }

            // --- Sandbox simulation ---
            if (env('PLAID_ENV') == 'sandbox') {
                // 1) Simulate transfer posted (decreases pending balance)
                $client->post($this->getPlaidUrl('/sandbox/transfer/simulate'), [
                    'json' => [
                        'client_id' => env('PLAID_CLIENT_ID'),
                        'secret' => env('PLAID_SECRET'),
                        'transfer_id' => $transferId,
                        'event_type' => 'posted',
                    ]
                ]);

                // 2) Simulate moving pending to available balance
                $client->post($this->getPlaidUrl('/sandbox/transfer/ledger/simulate_available'), [
                    'json' => [
                        'client_id' => env('PLAID_CLIENT_ID'),
                        'secret' => env('PLAID_SECRET'),
                        // 'account_id' => $accountId, // optional, simulate for specific account
                    ]
                ]);
            }

            // 3) Fetch updated balances from Plaid
            $balanceRes = $this->plaid->accounts->getBalance($accessToken);
            $balances = [];

            foreach ($balanceRes->accounts as $acct) {
                // Update local DB
                BankAccount::where('account_id', $acct->account_id)
                    ->where('user_id', Auth::id())
                    ->update(['opening_balance' => $acct->balances->current]);

                // Collect balances for display
                $balances[$acct->account_id] = [
                    'name' => $acct->name,
                    'current' => $acct->balances->current,
                    'available' => $acct->balances->available,
                    'iso_currency_code' => $acct->balances->iso_currency_code,
                ];
            }

            return back()->with('success', 'Transaction placed successfully. ID: ' . $transferId);

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            dd($e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage());
        } catch (\Throwable $e) {
            dd($e->getMessage());
        }
    }




    private function getPlaidUrl($path)
    {
        $env = env('PLAID_ENV', 'sandbox');
        $baseUrls = [
            'sandbox' => 'https://sandbox.plaid.com',
            'development' => 'https://development.plaid.com',
            'production' => 'https://production.plaid.com',
        ];

        return $baseUrls[$env] . $path;
    }

    public function storePlaidAccount(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'public_token' => ['required', 'string']
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'message' => $validator->errors()], 201);
        }

        $user_id = Auth::user()->id;
        $plaid = new Plaid(env('PLAID_CLIENT_ID'), env('PLAID_SECRET'), env('PLAID_ENV'));
        $obj = $plaid->items->exchangeToken($request->public_token);
        try {
            \DB::transaction(function () use ($request, $obj, $user_id) {
                foreach ($request->accounts as $account) {
                    $query = BankAccount::where('account_id', isset($account['id']) ? $account['id'] : $account['account_id']);
                    if ($query->count() > 0) {
                        $new_account = $query->first();
                        $new_account->plaid_item_id = $obj->item_id;
                        $new_account->plaid_access_token = $obj->access_token;
                        $new_account->plaid_public_token = $request->public_token;
                        $new_account->link_session_id = $request->link_session_id;
                        $new_account->link_token = $request->link_token;
                        $new_account->institution_id = $request->institution['institution_id'];
                        $new_account->institution_name = $request->institution['name'];
                        $new_account->account_id = isset($account['id']) ? $account['id'] : $account['account_id'];
                        $new_account->account_name = isset($account['name']) ? $account['name'] : $account['account_name'];
                        $new_account->account_mask = isset($account['account_number']) ? $account['account_number'] : $account['mask'];
                        $new_account->account_mask = null;
                        $new_account->account_type = isset($account['type']) ? $account['type'] : $account['account_type'];
                        $new_account->account_subtype = isset($account['subtype']) ? $account['subtype'] : $account['account_sub_type'];
                        $new_account->user_id = $user_id;
                        //created_by 
                        $new_account->created_by = \Auth::user()->creatorId();
                        $new_account->updated_by = \Auth::user()->ownedId();
                        $new_account->save();
                    } else {
                        $new_account = ([
                            'plaid_item_id' => $obj->item_id,
                            'plaid_access_token' => $obj->access_token,
                            'plaid_public_token' => $request->public_token,
                            'link_session_id' => $request->link_session_id,
                            'link_token' => $request->link_token,
                            'institution_id' => $request->institution['institution_id'],
                            'institution_name' => $request->institution['name'],
                            'account_id' => isset($account['id']) ? $account['id'] : $account['account_id'],
                            'account_name' => isset($account['name']) ? $account['name'] : $account['account_name'],
                            'account_mask' => isset($account['account_number']) ? $account['account_number'] : $account['mask'],
                            'account_mask' => null,
                            'account_type' => isset($account['type']) ? $account['type'] : $account['account_type'],
                            'account_subtype' => isset($account['subtype']) ? $account['subtype'] : $account['account_sub_type'],
                            'user_id' => $user_id,
                            'created_by' => \Auth::user()->creatorId(),
                            'updated_by' => \Auth::user()->ownedId(),
                        ]);
                        BankAccount::create($new_account);
                    }
                }
            });
        } catch (\Exception $e) {
            dd($e->getMessage());
            Log::error('An error occurred linking a Plaid account: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred attempting to link a Plaid account.'
            ], 200);
        }
        return response()->json([
            'message' => 'Successfully linked plaid account.',
            'item_id' => $obj->item_id
        ], 200);
    }
    public function getInvestmentHoldings(Request $request)
    {
        if ($request->itemId != NULL) {
            $account = BankAccount::where('plaid_item_id', $request->itemId)->first();
        }

        if (!isset($plaid)) {
            $plaid = new Plaid(env('PLAID_CLIENT_ID'), env('PLAID_SECRET'), env('PLAID_ENV'));
        }


        try {
            $results = $plaid->investments->listHoldings($account->plaid_access_token);
            $account->last_update = new \DateTime();
            $account->last_status = '';
            $account->save();
        } catch (PlaidRequestException $e) {
            // Plaid-specific errors
            $resp = $e->getResponse();
            $account->last_status = $resp['error_code'] ?? 'UNKNOWN_ERROR';
            $account->save();

            Log::error('Error pulling holdings from Plaid: ' . ($resp['error_code'] ?? 'no_code'), [
                'response' => $resp
            ]);

            // Stop the transaction and return a JSON error
            throw new \RuntimeException('Plaid error: ' . $resp['error_message'] ?? $e->getMessage());
        } catch (\Throwable $e) {
            // Any other exception
            Log::error('Unexpected error pulling holdings: ' . $e->getMessage());
            throw $e;
        }

        // Only proceed if we got results
        foreach ($results->holdings as $holding) {
            Holdings::create([
                'institution_id' => $account->institution_id,
                'holding_id' => $holding->security_id,
                'user_id' => $account->user_id,
                'cost_basis' => $holding->cost_basis,
                'price' => $holding->institution_price,
            ]);
        }


        return [
            'result' => 'success',
            'message' => 'Successfully added holdings from Plaid.'
        ];
    }

    public function showBalance($id)
    {
        $pa = BankAccount::where('institution_id', $id)->firstOrFail();

        try {
            //get account
            $acc = $this->plaid->accounts->list($pa->plaid_access_token);
            // dd($acc);
            // 1. Fetch balances
            $balanceRes = $this->plaid->accounts->getBalance($pa->plaid_access_token);

            // Default range (last 30 days)
            $startDate = new \DateTime('-30 days');
            $endDate = new \DateTime('now');

            $transactions = [];
            $rangeMessage = null;

            try {
                // Try requested range
                $transactionsRes = $this->plaid->transactions->list($pa->plaid_access_token,$startDate,$endDate
                );
                $transactions = $transactionsRes->transactions ?? [];
                if (empty($transactions)) {
                    // No transactions in range → fallback
                    $rangeMessage = "No transactions found in this range. Here are your most recent transactions.";

                    // Fallback: last 7 days
                    $fallbackStart = new \DateTime('-7 days');
                    $fallbackEnd = new \DateTime('now');

                    $fallbackRes = $this->plaid->transactions->list(
                        $pa->plaid_access_token,
                        $fallbackStart,
                        $fallbackEnd
                    );

                    $transactions = $fallbackRes->transactions ?? [];
                }
                // dd($transactions);
            } catch (\TomorrowIdeas\Plaid\PlaidRequestException $e) {
                \Log::warning("Plaid transaction fetch failed: " . $e->getMessage());

                $rangeMessage = "No transactions found in this range. Here are your most recent transactions.";

                // fallback to recent
                $fallbackStart = new \DateTime('-7 days');
                $fallbackEnd = new \DateTime('now');
                try {
                    $fallbackRes = $this->plaid->transactions->list(
                        $pa->plaid_access_token,
                        $fallbackStart,
                        $fallbackEnd
                    );
                    $transactions = $fallbackRes->transactions ?? [];
                } catch (\Throwable $ex) {
                    $transactions = [];
                }
            }

            return view('bankAccount.balance', [
                'accounts' => $balanceRes->accounts,
                'transactions' => $transactions,
                'rangeMessage' => $rangeMessage,
                'account' => $pa
            ]);
        } catch (\TomorrowIdeas\Plaid\PlaidRequestException $e) {
            // Plaid SDK returns a response object (stdClass) — handle both object/array forms safely
            $resp = $e->getResponse();
            $errorCode = null;
            $errorMessage = null;

            if (is_array($resp)) {
                $errorCode = $resp['error_code'] ?? null;
                $errorMessage = $resp['error_message'] ?? null;
            } elseif (is_object($resp)) {
                $errorCode = $resp->error_code ?? null;
                $errorMessage = $resp->error_message ?? null;
            }
            \Log::warning("Plaid error fetching balance for item {$pa->id}: " . json_encode($resp));
            // If bank requires user login -> Offer update-mode re-link
            if ($errorCode == 'ITEM_LOGIN_REQUIRED') {
                try {
                    // Use direct REST call to /link/token/create to create update-mode link token
                    $client = new \GuzzleHttp\Client();
                    $payload = [
                        'client_id' => env('PLAID_CLIENT_ID'),
                        'secret' => env('PLAID_SECRET'),
                        'client_name' => config('app.name', 'App'),
                        'language' => 'en',
                        'country_codes' => ['US'],
                        'user' => [
                            'client_user_id' => (string) $pa->user_id,
                        ],
                        // ask for products you need in link (auth, transactions, investments etc.)
                        'products' => ['auth', 'transactions'],
                        // This access_token puts Link into update mode
                        'access_token' => $pa->plaid_access_token,
                    ];

                    $linkRes = $client->post($this->getPlaidUrl('/link/token/create'), [
                        'json' => $payload
                    ]);

                    $linkBody = json_decode($linkRes->getBody()->getContents(), true);

                    $linkToken = $linkBody['link_token'] ?? null;

                    if (!$linkToken) {
                        // fallback: return error view
                        return back()->with('error', 'Unable to create re-auth link token. Please re-link the bank manually.');
                    }

                    return view('bankAccount.reauth', [
                        'link_token' => $linkToken,
                        'account' => $pa
                    ]);
                } catch (\Throwable $innerEx) {
                    \Log::error("Failed to create update link token: " . $innerEx->getMessage());
                    return back()->with('error', 'Unable to create re-auth link. Please re-link the bank manually.');
                }
            }

            // Other Plaid errors
            return back()->with('error', $errorMessage ?? 'Plaid error while fetching balance.');
        } catch (\Throwable $e) {
            \Log::error("Unexpected error showBalance: " . $e->getMessage());
            return back()->with('error', 'Unexpected error: ' . $e->getMessage());
        }
    }

    public function updateBalance(Request $request, $id)
    {
        try {
            $pa = BankAccount::where('institution_id', $id)->firstOrFail();

            // 1. Fetch balances from Plaid
            $balanceRes = $this->plaid->accounts->getBalance($pa->plaid_access_token);

            if (empty($balanceRes->accounts)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No accounts returned from Plaid.'
                ]);
            }

            // 2. Build an array of account_id => balance
            $updates = [];
            foreach ($balanceRes->accounts as $account) {
                $updates[$account->account_id] = $account->balances->current;
            }

            // 3. Update balances in DB
            foreach ($updates as $accountId => $balance) {
                BankAccount::where('institution_id', $id)
                    ->where('account_id', $accountId)
                    ->update(['opening_balance' => $balance]);
            }
            return redirect()->back()->with('success', 'Balances updated successfully' . json_encode($updates));
            // return response()->json([
            //     'status' => 'success',
            //     'message' => 'Balances updated successfully',
            //     'updated' => $updates
            // ]);

        } catch (\TomorrowIdeas\Plaid\PlaidRequestException $e) {
            \Log::error("Plaid API error in updateBalance: " . $e->getMessage());
            // return response()->json([
            //     'status' => 'error',
            //     'message' => 'Plaid API error: ' . $e->getMessage()
            // ]);
            return redirect()->back()->with('error', 'Plaid API error: ' . $e->getMessage());
        } catch (\Throwable $e) {
            \Log::error("Unexpected error updateBalance: " . $e->getMessage());
            // return response()->json([
            //     'status' => 'error',
            //     'message' => 'Unexpected error: ' . $e->getMessage()
            // ]);
            return redirect()->back()->with('error', 'Unexpected error: ' . $e->getMessage());
        }
    }



    /**
     * Endpoint to exchange public_token returned by Link (update mode may still return a public_token)
     * and persist the access_token (safe to call even if update mode retains access_token).
     */
    public function exchangePublicToken(Request $request)
    {
        $request->validate([
            'public_token' => 'required|string',
            'account_id' => 'required|integer' // BankAccount id (DB)
        ]);

        $pa = BankAccount::findOrFail($request->account_id);

        try {
            // Use your SDK's exchangeToken method (you used this in storePlaidAccount)
            $plaid = new Plaid(env('PLAID_CLIENT_ID'), env('PLAID_SECRET'), env('PLAID_ENV'));
            $obj = $plaid->items->exchangeToken($request->public_token);

            // Update stored fields (keep existing created_by/updated_by logic if needed)
            $pa->plaid_access_token = $obj->access_token;
            $pa->plaid_item_id = $obj->item_id ?? $pa->plaid_item_id;
            $pa->save();

            return response()->json(['status' => 'success']);
        } catch (\TomorrowIdeas\Plaid\PlaidRequestException $e) {
            \Log::error("Plaid exchange token failed: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Plaid exchange failed'], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            \Log::error("Unexpected exchange error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Unexpected error while exchanging token'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function getConnectedBank(Request $request)
    {
        $userId = Auth::user()->id;

        $plaidAccounts = BankAccount::where('user_id', $userId)->get();

        if ($plaidAccounts->isEmpty()) {
            return response()->json(['result' => 'error', 'message' => 'No Plaid access tokens found'], 400);
        }

        $banks = [];
        $seenInstitutionIds = [];
        $balance = 0;
        foreach ($plaidAccounts as $pa) {
            try {
                $accessToken = $pa->plaid_access_token;

                // 1. Get item info
                $itemResponse = $this->plaid->items->get($accessToken);
                $institutionId = $itemResponse->item->institution_id;

                // Skip if already added
                if (in_array($institutionId, $seenInstitutionIds)) {
                    continue;
                }

                // 2. Get institution info
                $institutionResponse = $this->plaid->institutions->get($institutionId, ['US']);
                //balance
                $balanceResponse = $this->plaid->accounts->getBalance($accessToken);
                foreach ($balanceResponse->accounts as $account) {
                    $balance += $account->balances->current;
                }
                $banks[] = [
                    'bank_name' => $institutionResponse->institution->name,
                    'institution_id' => ucfirst($institutionId),
                    'total_balance' => \Auth::user()->priceFormat($balance)
                ];

                $seenInstitutionIds[] = $institutionId;

                // if (count($banks) >= 2) {
                //     break;
                // }

            } catch (\TomorrowIdeas\Plaid\Exceptions\PlaidRequestException $e) {
                \Log::warning("Plaid request error: " . $e->getMessage());
                continue;
            } catch (\Throwable $e) {
                \Log::error("Unexpected error: " . $e->getMessage());
                continue;
            }
        }

        return response()->json([
            'result' => 'success',
            'banks' => $banks,
        ]);
    }


}
