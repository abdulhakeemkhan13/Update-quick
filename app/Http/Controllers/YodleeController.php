<?php

namespace App\Http\Controllers;

use Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class YodleeController extends Controller
{
    protected $config;
    protected $fastlinkConfig;
    protected $clientId;
    protected $clientSecret;     // your FastLink config name
    public function __construct()
    {
        $this->fastlinkConfig = 'Aggregation';
        $this->clientId = env('YODLEE_CLIENT_ID');      // from .env
        $this->clientSecret = env('YODLEE_CLIENT_SECRET');      // your FastLink config
        $this->config = [
            'apiEndpoint' => 'https://sandbox.api.yodlee.com/ysl',
            'fastlinkUrl' => 'https://fl4.sandbox.yodlee.com/authenticate/restserver/fastlink',
            'clientId' => env('YODLEE_CLIENT_ID'),
            'clientSecret' => env('YODLEE_CLIENT_SECRET'),
            // support both env keys just in case (prefers YODLEE_ADMIN_LOGINNAME)
            'adminLoginName' => env('YODLEE_ADMIN_LOGINNAME', env('YODLEE_ADMIN_LOGIN_NAME')),
        ];
    }

    public function index()
    {
        return view('yodlee');
    }
    public function index2()
    {
        return view('yodlee2');
    }

    /**
     * Get Cobrand token and (optionally) FastLink user access token for sandbox.
     *
     * Returns JSON:
     *  - success: bool
     *  - cobrandToken: string
     *  - fastlinkToken: string|null
     *  - username: string
     */
    public function getAccessToken(Request $request)
    {
        try {
            // use provided username or fallback to a sensible sandbox default
            $selectedUser = $request->input('username', $request->input('loginName', 'sbMem68ef21052da091'));

            // STEP 1: Get Cobrand token
            $cobrandCurl = curl_init();
            curl_setopt_array($cobrandCurl, [
                CURLOPT_URL => $this->config['apiEndpoint'] . '/auth/token',
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'clientId' => $this->config['clientId'],
                    'secret' => $this->config['clientSecret'],
                ]),
                CURLOPT_HTTPHEADER => [
                    'Api-Version: 1.1',
                    'loginName: ' . $this->config['adminLoginName'],
                    'Content-Type: application/x-www-form-urlencoded',
                ],
            ]);

            $cobrandResponse = curl_exec($cobrandCurl);
            $cobrandErr = curl_error($cobrandCurl);
            $cobrandHttpCode = curl_getinfo($cobrandCurl, CURLINFO_HTTP_CODE);
            curl_close($cobrandCurl);

            if ($cobrandResponse === false) {
                Log::error('Cobrand curl error', ['error' => $cobrandErr]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to call cobrand token endpoint',
                    'details' => $cobrandErr,
                ], 500);
            }

            $cobrandData = json_decode($cobrandResponse, true);

            Log::info('Yodlee Cobrand Token Response', [
                'http_code' => $cobrandHttpCode,
                'response' => $cobrandData ?? $cobrandResponse
            ]);

            // Accept 200 or 201 as success and require token.accessToken
            if (!in_array($cobrandHttpCode, [200, 201]) || !isset($cobrandData['token']['accessToken'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get cobrand token',
                    'details' => $cobrandData ?? $cobrandResponse,
                ], $cobrandHttpCode ?: 500);
            }

            $cobrandToken = $cobrandData['token']['accessToken'];

            // STEP 2: Attempt to generate a FastLink token for the selected user (sandbox)
            // Some sandboxes return 201 with token; others require using cobrand token directly in FastLink.
            $fastlinkToken = null;
            try {
                $tokenCurl = curl_init();
                curl_setopt_array($tokenCurl, [
                    // appIds value may vary per Yodlee docs / account; using appIds=10003600 as in your example.
                    CURLOPT_URL => $this->config['apiEndpoint'] . '/user/accessTokens?appIds=10003600',
                    CURLOPT_POST => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POSTFIELDS => json_encode(new \stdClass()), // send empty JSON object
                    CURLOPT_HTTPHEADER => [
                        'Api-Version: 1.1',
                        'Authorization: Bearer ' . $cobrandToken,
                        'loginName: ' . $selectedUser,
                        'Content-Type: application/json',
                    ],
                ]);

                $tokenResponse = curl_exec($tokenCurl);
                $tokenErr = curl_error($tokenCurl);
                $tokenHttpCode = curl_getinfo($tokenCurl, CURLINFO_HTTP_CODE);
                curl_close($tokenCurl);

                if ($tokenResponse === false) {
                    Log::warning('FastLink token curl error', ['error' => $tokenErr]);
                } else {
                    $tokenData = json_decode($tokenResponse, true);
                    Log::info('Yodlee FastLink Token Response', [
                        'http_code' => $tokenHttpCode,
                        'response' => $tokenData ?? $tokenResponse,
                        'username' => $selectedUser
                    ]);

                    // Yodlee may return 201 on success (created). Token location varies, handle common shapes:
                    if (in_array($tokenHttpCode, [200, 201])) {
                        // common path: $tokenData['user']['accessTokens'][0]['value']
                        if (isset($tokenData['user']['accessTokens'][0]['value'])) {
                            $fastlinkToken = $tokenData['user']['accessTokens'][0]['value'];
                        } elseif (isset($tokenData['user']['accessToken'])) {
                            $fastlinkToken = $tokenData['user']['accessToken'];
                        } elseif (isset($tokenData['token']['accessToken'])) {
                            $fastlinkToken = $tokenData['token']['accessToken'];
                        }
                    }
                }
            } catch (Exception $e) {
                Log::warning('FastLink token generation exception', ['message' => $e->getMessage()]);
            }

            // If fastlink token was not generated, we still return cobrand token for sandbox flows.
            $responsePayload = [
                'success' => true,
                'message' => $fastlinkToken ? 'FastLink token generated successfully' : 'Using cobrand token for sandbox',
                'cobrandToken' => $cobrandToken,
                'fastlinkToken' => $fastlinkToken ?? $cobrandToken, // use cobrand token as fallback for sandbox
                'username' => $selectedUser,
                'raw' => [
                    'cobrand' => $cobrandData ?? $cobrandResponse,
                    'fastlink' => $tokenData ?? ($tokenResponse ?? null)
                ]
            ];

            return response()->json($responsePayload);

        } catch (Exception $e) {
            Log::error('Yodlee getAccessToken Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Transactions
     */
    public function getTransactions(Request $request)
    {
        try {
            $accessToken = $request->input('accessToken');

            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access token is required',
                ], 400);
            }

            $curl = curl_init();

            $fromDate = date('Y-m-d', strtotime('-30 days'));
            $toDate = date('Y-m-d');

            curl_setopt_array($curl, [
                CURLOPT_URL => $this->config['apiEndpoint'] . '/transactions?fromDate=' . $fromDate . '&toDate=' . $toDate,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Api-Version: 1.1',
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json',
                ],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($curl);
            curl_close($curl);

            if ($response === false) {
                Log::error('Transactions curl error', ['error' => $curlErr]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to call transactions endpoint',
                    'details' => $curlErr,
                ], 500);
            }

            $data = json_decode($response, true);

            Log::info('Yodlee Transactions Response', [
                'http_code' => $httpCode,
                'response' => $data
            ]);

            if ($httpCode === 200 && isset($data['transaction'])) {
                return response()->json([
                    'success' => true,
                    'transactions' => $data['transaction'],
                    'count' => count($data['transaction']),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transactions',
                'details' => $data ?? $response,
            ], $httpCode ?: 500);

        } catch (Exception $e) {
            Log::error('Yodlee getTransactions Error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Accounts
     */
    public function getAccounts(Request $request)
    {
        try {
            $accessToken = $request->input('accessToken');

            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access token is required',
                ], 400);
            }

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $this->config['apiEndpoint'] . '/accounts',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Api-Version: 1.1',
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json',
                ],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($curl);
            curl_close($curl);

            if ($response === false) {
                Log::error('Accounts curl error', ['error' => $curlErr]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to call accounts endpoint',
                    'details' => $curlErr,
                ], 500);
            }

            $data = json_decode($response, true);

            if ($httpCode === 200 && isset($data['account'])) {
                return response()->json([
                    'success' => true,
                    'accounts' => $data['account'],
                    'count' => count($data['account']),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch accounts',
                'details' => $data ?? $response,
            ], $httpCode ?: 500);

        } catch (Exception $e) {
            Log::error('Yodlee getAccounts Error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
    //    public function getFastlinkToken(Request $request)
// {
//     try {
//         // ------------------------------
//         // 1️⃣ Get OAuth2 Admin Token
//         // ------------------------------
//         $curl = curl_init();
//         curl_setopt_array($curl, [
//             CURLOPT_URL => "https://sandbox.api.yodlee.com/ysl/auth/token",
//             CURLOPT_POST => true,
//             CURLOPT_RETURNTRANSFER => true,
//             CURLOPT_POSTFIELDS => http_build_query([
//                 'clientId' => $this->config['clientId'],
//                 'secret' => $this->config['clientSecret'],
//             ]),
//             CURLOPT_HTTPHEADER => [
//                 'Api-Version: 1.1',
//                 'loginName: ' . $this->config['adminLoginName'],
//                 'Content-Type: application/x-www-form-urlencoded',
//             ],
//         ]);

    //         $response = curl_exec($curl);
//         $err = curl_error($curl);
//         curl_close($curl);

    //         if ($err) {
//             Log::error('Admin token curl error', ['error' => $err]);
//             return response()->json(['error' => 'Failed to call admin token endpoint', 'details' => $err], 500);
//         }

    //         $tokenData = json_decode($response, true);
//         $adminToken = $tokenData['token']['accessToken'] ?? null;

    //         if (!$adminToken) {
//             return response()->json(['error' => 'No admin token returned', 'details' => $response], 500);
//         }

    //         // ------------------------------
//         // 2️⃣ Return Admin Token to Frontend
//         // ------------------------------
//         return response()->json([
//             'adminToken' => $adminToken,
//             'fastLinkURL' => $this->config['fastlinkUrl'] ?? 'https://fl4.sandbox.yodlee.com/authenticate/restserver/fastlink',
//             'userId' => $request->query('userId') ?? env('YODLEE_TEST_USER_ID', 'sbMem68ef21052da091')
//         ]);

    //     } catch (\Exception $e) {
//         Log::error('Exception in getFastlinkToken', ['error' => $e->getMessage()]);
//         return response()->json(['error' => $e->getMessage()], 500);
//     }
// }


    public function getFastlinkToken(Request $request)
    {
        try {
            // Use sandbox test user ID
            $userLogin = $request->query('userLogin') ?? env('YODLEE_TEST_USER_ID', 'sbMem68ef21052da091');

            // 🔹 1. Get Cobrand (Admin) Token
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://sandbox.api.yodlee.com/ysl/auth/token",
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'clientId' => $this->config['clientId'],
                    'secret' => $this->config['clientSecret'],
                ]),
                CURLOPT_HTTPHEADER => [
                    'Api-Version: 1.1',
                    'loginName: ' . $this->config['adminLoginName'],
                    'Content-Type: application/x-www-form-urlencoded',
                ],
            ]);

            $response = curl_exec($curl);
            curl_close($curl);

            $tokenData = json_decode($response, true);
            $adminToken = $tokenData['token']['accessToken'] ?? null;

            if (!$adminToken) {
                return response()->json(['error' => 'Failed to get cobrand token', 'details' => $response], 500);
            }

            // ✅ Return cobrand token for FastLink sandbox use
            return response()->json([
                'accessToken' => $adminToken,
                'userLogin' => $userLogin,
                'fastLinkURL' => 'https://fl4.sandbox.yodlee.com/authenticate/restserver/fastlink'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


}
