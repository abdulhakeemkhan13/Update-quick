<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class YodleeService
{
    private $apiBase;
    private $fastlinkUrl;
    private $clientId;
    private $clientSecret;
    private $adminLoginName;
    private $apiVersion;
    private $sandboxUser;
    private $isSandbox;

    public function __construct()
    {
        $this->apiBase = config('yodlee.api_base');
        $this->fastlinkUrl = config('yodlee.fastlink_url');
        $this->clientId = config('yodlee.client_id');
        $this->clientSecret = config('yodlee.client_secret');
        $this->adminLoginName = config('yodlee.admin_login_name');
        $this->apiVersion = config('yodlee.api_version');
        $this->sandboxUser = config('yodlee.sandbox_user');
        
        // Check if we're in sandbox
        $this->isSandbox = str_contains($this->apiBase, 'sandbox');
        
        Log::info('Yodlee Service Initialized', [
            'isSandbox' => $this->isSandbox,
            'sandboxUser' => $this->sandboxUser
        ]);
    }

    /**
     * Get Admin Token
     */
    public function getAdminToken()
    {
        try {
            $cachedToken = Cache::get('yodlee_admin_token');
            if ($cachedToken) {
                return $cachedToken;
            }

            $response = Http::asForm()
                ->withHeaders([
                    'Api-Version' => $this->apiVersion,
                    'loginName' => $this->adminLoginName,
                ])
                ->post($this->apiBase . '/auth/token', [
                    'clientId' => $this->clientId,
                    'secret' => $this->clientSecret,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['token']['accessToken'];
                Cache::put('yodlee_admin_token', $token, now()->addMinutes(25));
                Log::info('Admin token obtained successfully');
                return $token;
            }

            throw new \Exception('Failed to get admin token: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Yodlee Admin Token Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get User Token
     * In sandbox, we always use the pre-registered sandbox test user
     */
    public function getUserToken($userLoginName = null, $email = null)
    {
        try {
            // In sandbox, ALWAYS use the sandbox test user
            if ($this->isSandbox) {
                $userLoginName = $this->sandboxUser;
                Log::info('Using sandbox test user: ' . $userLoginName);
            }

            $cacheKey = 'yodlee_user_token_' . $userLoginName;
            $cachedToken = Cache::get($cacheKey);
            if ($cachedToken) {
                Log::info('Using cached user token');
                return $cachedToken;
            }

            Log::info('Getting user token for: ' . $userLoginName);

            $response = Http::asForm()
                ->withHeaders([
                    'Api-Version' => $this->apiVersion,
                    'loginName' => $userLoginName,
                ])
                ->post($this->apiBase . '/auth/token', [
                    'clientId' => $this->clientId,
                    'secret' => $this->clientSecret,
                ]);

            Log::info('User token response status: ' . $response->status());

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['token']['accessToken'];
                Cache::put($cacheKey, $token, now()->addMinutes(25));
                Log::info('User token obtained successfully');
                return $token;
            }

            throw new \Exception('Failed to get user token: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Yodlee User Token Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get FastLink token
     */
    public function getFastLinkToken($userLoginName = null, $email = null)
    {
        try {
            $userToken = $this->getUserToken($userLoginName, $email);

            $response = Http::withHeaders([
                'Api-Version' => $this->apiVersion,
                'Authorization' => 'Bearer ' . $userToken,
            ])->post($this->apiBase . '/user/accessTokens?tokenType=AccessToken');

            Log::info('FastLink token response status: ' . $response->status());

            if ($response->successful()) {
                $data = $response->json();
                Log::info('FastLink token obtained successfully');
                return [
                    'accessToken' => $data['user']['accessTokens'][0]['value'],
                    'fastlinkUrl' => $this->fastlinkUrl,
                    'userLoginName' => $this->isSandbox ? $this->sandboxUser : $userLoginName,
                ];
            }

            throw new \Exception('Failed to get FastLink token: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Yodlee FastLink Token Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get connected accounts
     */
    public function getAccounts($userLoginName = null, $email = null)
    {
        try {
            $userToken = $this->getUserToken($userLoginName, $email);

            $response = Http::withHeaders([
                'Api-Version' => $this->apiVersion,
                'Authorization' => 'Bearer ' . $userToken,
            ])->get($this->apiBase . '/accounts');

            Log::info('Get accounts response status: ' . $response->status());

            if ($response->successful()) {
                return $response->json();
            }

            // If no accounts, return empty structure
            if ($response->status() === 404) {
                return ['account' => []];
            }

            throw new \Exception('Failed to get accounts: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Yodlee Get Accounts Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get transactions
     */
    public function getTransactions($userLoginName = null, $accountId = null, $fromDate = null, $toDate = null, $email = null)
    {
        try {
            $userToken = $this->getUserToken($userLoginName, $email);

            $queryParams = [];
            if ($accountId) {
                $queryParams['accountId'] = $accountId;
            }
            if ($fromDate) {
                $queryParams['fromDate'] = $fromDate;
            }
            if ($toDate) {
                $queryParams['toDate'] = $toDate;
            }

            $url = $this->apiBase . '/transactions';
            if (!empty($queryParams)) {
                $url .= '?' . http_build_query($queryParams);
            }

            $response = Http::withHeaders([
                'Api-Version' => $this->apiVersion,
                'Authorization' => 'Bearer ' . $userToken,
            ])->get($url);

            Log::info('Get transactions response status: ' . $response->status());

            if ($response->successful()) {
                return $response->json();
            }

            // If no transactions, return empty structure
            if ($response->status() === 404) {
                return ['transaction' => []];
            }

            throw new \Exception('Failed to get transactions: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Yodlee Get Transactions Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get account details
     */
    public function getAccountDetails($userLoginName = null, $accountId, $email = null)
    {
        try {
            $userToken = $this->getUserToken($userLoginName, $email);

            $response = Http::withHeaders([
                'Api-Version' => $this->apiVersion,
                'Authorization' => 'Bearer ' . $userToken,
            ])->get($this->apiBase . '/accounts/' . $accountId);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Failed to get account details: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Yodlee Get Account Details Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Unlink account
     */
    public function unlinkAccount($userLoginName = null, $accountId, $email = null)
    {
        try {
            $userToken = $this->getUserToken($userLoginName, $email);

            $response = Http::withHeaders([
                'Api-Version' => $this->apiVersion,
                'Authorization' => 'Bearer ' . $userToken,
            ])->delete($this->apiBase . '/accounts/' . $accountId);

            if ($response->successful() || $response->status() === 204) {
                Log::info('Account unlinked successfully: ' . $accountId);
                return true;
            }

            throw new \Exception('Failed to unlink account: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Yodlee Unlink Account Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get sandbox user login name
     */
    public function getSandboxUserLogin()
    {
        return $this->sandboxUser;
    }
}   