<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositLine;
use App\Models\Vender;
use App\Models\ProductService;
use App\Models\ChartOfAccount;
use App\Models\ProductServiceCategory;
use App\Models\ProductServiceUnit;
use App\Models\ChartOfAccountType;
use App\Models\ChartOfAccountSubType;
use App\Models\ChartOfAccountParent;
use App\Models\Bill;
use App\Models\BillProduct;
use App\Models\BillPayment;
use App\Models\BillAccount;
use App\Models\Employee;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\TransactionLines;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
class QuickBooksApiController extends Controller
{
    protected $clientId;
    protected $clientSecret;
    protected $authUrl;
    protected $tokenUrl;
    protected $scope;
    protected $redirectUri;
    public $baseUrl;
    protected $userId;

    public function __construct() // production
    {
        $this->clientId = 'AByYeIrpQQktbXur2EwxXINJWZzJTJrkuH8BRb7P5I2p9L4qrL';
        $this->clientSecret = 'uBFqiKdEr9UvCps9SvmZh6ggRiu0CJxjPjMwhW4y';
        $this->userId = auth()->id();
        $this->authUrl = 'https://appcenter.intuit.com/connect/oauth2';
        $this->tokenUrl = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';
        $this->scope = 'com.intuit.quickbooks.accounting openid profile email';
        $this->redirectUri = 'https://update.creativesuite.co/quickbooks/callback';
        $this->baseUrl = 'https://quickbooks.api.intuit.com';
    }
    // public function __construct()
    // {
    //     // Directly read from env to avoid config caching issues
    //     // $this->clientId     = env('QB_CLIENT_ID');
    //     $this->clientId = 'AB91apFaxICw2LLUpMSqbaTj639nwk7xsDO3zLL9dFOee9lUYI';
    //     $this->clientSecret = 'VynpkqKTBrOaQE10eFqqwSgNGBFf9Wsc6ANcS3Vl';
    //     $this->authUrl = env('QB_AUTH_URL', 'https://appcenter.intuit.com/connect/oauth2');
    //     $this->tokenUrl = env('QB_TOKEN_URL', 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer');
    //     $this->scope = env('QB_SCOPE', 'com.intuit.quickbooks.accounting com.intuit.quickbooks.payment openid profile email');
    //     $this->redirectUri = env('QB_REDIRECT_URI', 'http://localhost:8012/csuitequickbook/quickbooks/callback');
    //     $this->baseUrl = env('QB_BASE_URL', 'https://sandbox-quickbooks.api.intuit.com');
    // }
    // public function __construct() //my
    // {
    //     // Directly read from env to avoid config caching issues
    //     // $this->clientId     = env('QB_CLIENT_ID');
    //     $this->clientId = 'ABpCTnsvhjnEcBTWVIofKoQ482JGuH6yXpb4ARb4uFvefO145m';
    //     $this->clientSecret = 'gUVkoksUL0busJJRj8WNEj7BEjnCveF4EoWGU2xp';
    //     $this->authUrl = env('QB_AUTH_URL', 'https://appcenter.intuit.com/connect/oauth2');
    //     $this->tokenUrl = env('QB_TOKEN_URL', 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer');
    //     $this->scope = env('QB_SCOPE', 'com.intuit.quickbooks.accounting com.intuit.quickbooks.payment openid profile email');
    //     $this->redirectUri = env('QB_REDIRECT_URI', 'http://localhost:8012/csuite/new/quickbooks/callback');
    //     $this->baseUrl = env('QB_BASE_URL', 'https://sandbox-quickbooks.api.intuit.com');
    // }
    
    public function license()
    {
        return view('license');
    }

    public function privacyPolicy()
    {
        return view('privacy-policy');
    }
    public function accessToken()
    {
        // Try session first (for web requests)
        $sessionToken = Session::get('qb_access_token');
        if ($sessionToken) {
            return $sessionToken;
        }

        // Fall back to database (for queue jobs)
        $userId = \Auth::id();
        if ($userId) {
            $tokenRecord = \App\Models\QuickBooksToken::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->first();
            return $tokenRecord ? $tokenRecord->access_token : null;
        }

        return null;
    }

    public function realmId()
    {
        // Try session first (for web requests)
        $sessionRealmId = Session::get('qb_realm_id');
        if ($sessionRealmId) {
            return $sessionRealmId;
        }

        // Fall back to database (for queue jobs)
        $userId = \Auth::id();
        if ($userId) {
            $tokenRecord = \App\Models\QuickBooksToken::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->first();
            return $tokenRecord ? $tokenRecord->realm_id : null;
        }

        return null;
    }

    public function refreshToken($refreshToken)
    {
        try {
            $response = Http::asForm()
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->post($this->tokenUrl, [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ]);

            if ($response->failed()) {
                return false;
            }

            $data = $response->json();

            // Store new tokens in session (for immediate use)
            Session::put('qb_access_token', $data['access_token']);
            Session::put('qb_refresh_token', $data['refresh_token'] ?? $refreshToken); // Keep old refresh token if not provided

            // Store expiry time (access tokens typically last 1 hour)
            $expiresAt = now()->addSeconds($data['expires_in'] ?? 3600);
            \Illuminate\Support\Facades\Cache::put('qb_token_data', [
                'expires_at' => $expiresAt->timestamp,
            ], 3600);

            // IMPORTANT: Also update database for queue jobs
            $userId = \Auth::id();
            if ($userId) {
                $tokenRecord = \App\Models\QuickBooksToken::where('user_id', $userId)->first();

                if ($tokenRecord) {
                    $tokenRecord->update([
                        'access_token' => $data['access_token'],
                        'refresh_token' => $data['refresh_token'] ?? $refreshToken,
                        'expires_at' => $expiresAt,
                    ]);

                    \Log::info("QuickBooks tokens refreshed in database for user {$userId}");
                }
            }

            return $data;
        } catch (\Exception $e) {
            \Log::error('QuickBooks token refresh failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Redirect user to QuickBooks login/consent screen.
     */
    public function connect()
    {
        // dd($this->redirectUri,$this->clientId,$this->scope,$this->authUrl,$this->tokenUrl);
        $params = http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'scope' => $this->scope,
            'redirect_uri' => $this->redirectUri,
            'state' => csrf_token(),
        ]);

        return redirect("{$this->authUrl}?{$params}");
    }
    public function disconnect()
    {
        $access = Session::pull('qb_access_token');
        $refresh = Session::pull('qb_refresh_token');
        Session::forget(['qb_realm_id','qb_token_expires_at','qb_oauth_state']);

        if ($access) {
            Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->post('https://developer.api.intuit.com/v2/oauth2/tokens/revoke', [
                    'token' => $access
                ]);
        }

        // IMPORTANT: Also remove tokens from database
        $userId = \Auth::id();
        if ($userId) {
            \App\Models\QuickBooksToken::where('user_id', $userId)->delete();
            \Log::info("QuickBooks tokens removed from database for user {$userId}");
        }

        return redirect()->route('quickbooks.sync')->with('success', 'Disconnected from QuickBooks.');
    }

    /**
     * Handle the callback from QuickBooks OAuth.
     */
    public function callback(Request $request)
    {
        $code = $request->query('code');
        $realmId = $request->query('realmId');

        if (!$code) {
            return response()->json(['error' => 'No authorization code returned'], 400);
        }

        // Exchange authorization code for tokens
        $response = Http::asForm()
            ->withBasicAuth($this->clientId, $this->clientSecret)
            ->post($this->tokenUrl, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->redirectUri,
            ]);

        if ($response->failed()) {
            return response()->json([
                'error' => 'Token exchange failed',
                'details' => $response->json(),
            ], 400);
        }

        $data = $response->json();

        // Store access token and realmId in session (for immediate use)
        Session::put('qb_access_token', $data['access_token']);
        Session::put('qb_refresh_token', $data['refresh_token']);
        Session::put('qb_realm_id', $realmId);

        // IMPORTANT: Also save to database for queue jobs
        $userId = \Auth::id();
        if ($userId) {
            // Calculate token expiry time (QuickBooks tokens expire in 1 hour)
            $expiresAt = now()->addSeconds($data['expires_in'] ?? 3600);

            // Save or update token in database
            \App\Models\QuickBooksToken::updateOrCreate(
                ['user_id' => $userId],
                [
                    'realm_id' => $realmId,
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'],
                    'expires_at' => $expiresAt,
                ]
            );

            \Log::info("QuickBooks tokens saved to database for user {$userId}");
        }

        return redirect()->route('quickbooks.sync')->with('success', 'QuickBooks connected successfully!');
    }

    /**
     * Helper to run a QuickBooks query.
     */
    public function runQuery(string $query)
    
    {
        $token = $this->accessToken();
        $realm = $this->realmId();

        if (!$token || !$realm) {
            return response()->json([
                'error' => true,
                'message' => 'Missing QuickBooks connection. Please connect first.',
            ], 401);
        }

        $url = "{$this->baseUrl}/v3/company/{$realm}/query?query=" . urlencode($query);

        $response = Http::withToken($token)
            ->accept('application/json')
            ->get($url);

        if ($response->status() === 401) {
            return response()->json([
                'error' => true,
                'message' => 'Unauthorized (401). Access token may be expired. Please reconnect.',
            ], 401);
        }

        return $response->json();
    }

    /**
     * View with buttons for actions
     */
    public function index()
    {
        $connected = $this->accessToken() && $this->realmId();
        return view('quickbooks_sync', compact('connected'));
    }

    public function invoices()
{
    $startPosition = 1;
    $maxResults = 50;
    $allInvoices = [];

    do {
        $query = "SELECT * FROM Invoice STARTPOSITION $startPosition MAXRESULTS $maxResults";
        $data = $this->runQuery($query);

        // Protect against unexpected responses
        $invoices = $data['QueryResponse']['Invoice'] ?? [];
        $count = count($invoices);

        // Merge the results
        $allInvoices = array_merge($allInvoices, $invoices);

        // Increment start position for next batch (1, 51, 101 ...)
        $startPosition += $maxResults;

    } while ($count === $maxResults); // Keep looping while we get a full batch

    // Get first invoice safely
    $firstInvoice = $allInvoices[0] ?? null;

    // Use an associative array with dd() to get labeled output
    dd([
        'total_invoices' => count($allInvoices),
        'first_invoice'  => $firstInvoice,
        'all_invoices'   => $allInvoices,
    ]);
}



    public function bills()
    {
        $startPosition = 1;
        $maxResults = 50;
        $allBills = [];

        do {
            $query = "SELECT * FROM Bill STARTPOSITION $startPosition MAXRESULTS $maxResults";
            $data = $this->runQuery($query);

            $bills = $data['QueryResponse']['Bill'] ?? [];
            $count = count($bills);

            // Merge into main array
            $allBills = array_merge($allBills, $bills);

            // Next batch
            $startPosition += $maxResults;

        } while ($count === $maxResults);

        dd(count($allBills), $allBills);
    }


    public function customers()
    {
        $startPosition = 1;
        $maxResults = 50;
        $allCustomer = [];

        do {
            // Run paginated query
            $query = "SELECT * FROM Customer STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
            $data = $this->runQuery($query);

            // Extract vendors from response (adjust key path based on your runQuery response)
            $Customer = $data['QueryResponse']['Customer'] ?? [];

            // Merge into full vendor list
            $allCustomer = array_merge($allCustomer, $Customer);

            // Increment for next batch
            $count = count($Customer);
            $startPosition += $maxResults;

        } while ($count === $maxResults);

        dd($allCustomer);
    }

    public function chartOfAccounts()
    {
        $startPosition = 1;
        $maxResults = 1000;
        $allAccounts = [];

        do {
            $query = "SELECT * FROM Account WHERE Active IN (true, false) STARTPOSITION $startPosition MAXRESULTS $maxResults";
            $data = $this->runQuery($query);

            $accounts = $data['QueryResponse']['Account'] ?? [];
            $count = count($accounts);

            $allAccounts = array_merge($allAccounts, $accounts);
            $startPosition += $maxResults;

        } while ($count === $maxResults);

        return response()->json(['co'=> count($allAccounts),'coa'=> $allAccounts]);
    }


    public function vendors()
    {
        $startPosition = 1;
        $maxResults = 50;
        $allVendors = [];

        do {
            // Run paginated query
            $query = "SELECT * FROM Vendor STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
            $data = $this->runQuery($query);

            // Extract vendors from response (adjust key path based on your runQuery response)
            $vendors = $data['QueryResponse']['Vendor'] ?? [];

            // Merge into full vendor list
            $allVendors = array_merge($allVendors, $vendors);

            // Increment for next batch
            $count = count($vendors);
            $startPosition += $maxResults;

        } while ($count === $maxResults);

        dd($allVendors);
    }

    public function journalEntries()
    {
        $data = $this->runQuery("SELECT * FROM JournalEntry STARTPOSITION 1 MAXRESULTS 100");
        dd($data);
    }
    // 📊 Fetch Journal Entries (with all available fields)
    // public function journalReport()
    // {
    //     $query = "SELECT Id, SyncToken, MetaData, DocNumber, TxnDate, PrivateNote,Line, ExchangeRate, Adjustment, TxnSource, Domain, sparse
    //             FROM JournalEntry STARTPOSITION 1 MAXRESULTS 500";

    //     $data = $this->runQuery($query);
    //     dd($data); // dump journal entries for now
    // }


    // 📘 Fetch QuickBooks “Journal Report” (Financial Report API)
    // public function journalFRReport(Request $request)
    // {
    //     $token = $this->accessToken();
    //     $realm = $this->realmId();

    //     // Define base URL
    //     $baseUrl = "{$this->baseUrl}/v3/company/{$realm}/reports/JournalReport";

    //     // Get requested or default date range
    //     $startDate = Carbon::parse($request->input('start_date', '2023-10-26'));
    //     $endDate   = Carbon::parse($request->input('end_date', '2024-10-28'));
    //     $accountingMethod = $request->input('accounting_method', 'Accrual');
    //     $url = "{$baseUrl}?start_date=2023-10-26&end_date=2023-10-28";
    //     $response = Http::timeout(0)
    //             ->withToken($token)
    //             ->accept('application/json')
    //             ->get($url);
    //                     $data = $response->json();

    //     // To store all data from multiple years
    //     $allData = [];

    //     // Loop year by year
    //     $currentStart = $startDate->copy();
    //     while ($currentStart->lte($endDate)) {

    //         $currentEnd = $currentStart->copy()->endOfYear();

    //         // Ensure we don’t go beyond the requested end date
    //         if ($currentEnd->gt($endDate)) {
    //             $currentEnd = $endDate->copy();
    //         }

    //         // Build URL for the current year's range
    //         $url = "{$baseUrl}?start_date={$currentStart->format('Y-m-d')}&end_date={$currentEnd->format('Y-m-d')}&accounting_method={$accountingMethod}";

    //         // Call the API with timeout disabled (0 = unlimited)
    //         $response = Http::timeout(0)
    //             ->withToken($token)
    //             ->accept('application/json')
    //             ->get($url);

    //         // Handle response errors
    //         if ($response->failed()) {
    //             \Log::error("QuickBooks JournalReport failed for {$currentStart->year}", [
    //                 'status' => $response->status(),
    //                 'body' => $response->body(),
    //             ]);
    //             // Move to next year even if failed
    //             $currentStart->addYear();
    //             continue;
    //         }

    //         // Parse response
    //         $data = $response->json();
    //         $rows = $data['Rows']['Row'] ?? [];

    //         // Merge results
    //         $allData = array_merge($allData, $rows);

    //         // Log or print progress (optional)
    //         \Log::info("Fetched JournalReport for year {$currentStart->year}", [
    //             'rows_count' => count($rows)
    //         ]);

    //         // Move to next year
    //         $currentStart->addYear();
    //     }

    //     // Return combined data (or process further)
    //     return dd([
    //         'success' => true,
    //         'message' => 'Journal Report fetched successfully (year by year)',
    //         'total_rows' => count($allData),
    //         'data' => $allData,
    //     ]);
    // }
    public function journalFRReport(Request $request)
    {
        $companyId = $this->realmId();
        $accessToken = $this->accessToken();
        $baseUrl = "{$this->baseUrl}/v3/company/{$companyId}";

        // Input or default range
        $startDate = Carbon::parse($request->input('start_date', '2023-10-26'));
        $endDate = Carbon::parse($request->input('end_date', now()->format('Y-m-d')));
        $accountingMethod = $request->input('accounting_method', 'Accrual');

        // Determine batch size (1 year chunks)
        $batchSizeMonths = 12;
        $batches = [];
        $skippedEntries = []; // Track skipped entries
        $current = $startDate->copy();

        while ($current->lt($endDate)) {
            $batchStart = $current->copy();
            $batchEnd = $current->copy()->addMonths($batchSizeMonths)->endOfMonth();
            if ($batchEnd->gt($endDate)) $batchEnd = $endDate->copy();
            $batches[] = [$batchStart->toDateString(), $batchEnd->toDateString()];
            $current = $batchEnd->copy()->addDay();
        }

        $groupedEntries = [];
        $totalImported = 0;

        foreach ($batches as $index => [$batchStart, $batchEnd]) {

            // 🧩 Refresh token before each batch
            try {
                $this->refreshTokenIfNeeded();
                $accessToken = $this->accessToken(); // get fresh token
            } catch (\Throwable $e) {
                \Log::error("QuickBooks token refresh failed before batch {$index}", [
                    'error' => $e->getMessage(),
                ]);
                continue; // Skip this batch if refresh fails
            }

            $url = "{$baseUrl}/reports/JournalReport?start_date=2025-05-23&end_date=2025-05-24&accounting_method={$accountingMethod}";

            try {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$accessToken}",
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/text',
                ])
                ->timeout(180)   // 3-minute timeout per batch
                ->retry(3, 5000) // Retry 3 times, 5s interval
                ->get($url);

                if ($response->failed()) {
                    \Log::warning("QuickBooks JournalReport batch failed", [
                        'url' => $url,
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);
                    continue;
                }

                $data = $response->json();
                $rows = $data['Rows']['Row'] ?? [];
                $batchEntries = $this->processJournalRows($rows);
                $groupedEntries = array_merge($groupedEntries, $batchEntries);

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                dd($e);
                \Log::error('QuickBooks JournalReport timeout', [
                    'url' => $url,
                    'message' => $e->getMessage(),
                ]);
                continue;
            }

            // Optional: Sleep a bit between years (avoid rate limit)
            sleep(2);
        }

        // Create entries
        $createdEntries = [];
        foreach ($groupedEntries as $entryData) {
            $result = $this->createJournalEntry($groupedEntries['11']);
            
            if ($result['status'] == 'created') {
                $createdEntries[] = $result['data'];
                $totalImported++;
            } elseif ($result['status'] == 'skipped') {
                $skippedEntries[] = $result['data'];
            }
        }
        $excelPath = null;
            if (!empty($skippedEntries)) {
                $excelPath = $this->exportSkippedEntriesToExcel($skippedEntries);
            }

        $response = [
            'success' => true,
            'message' => 'Batched journal report import completed successfully.',
            'imported_batches' => count($batches),
            'imported_entries' => $totalImported,
            'skipped_entries_count' => count($skippedEntries),
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ]
        ];

        // Add download link if skipped entries exist
        if ($excelPath) {
            $response['skipped_entries_file'] = $excelPath;
            $response['download_url'] = route('download.skipped.entries', ['file' => basename($excelPath)]);
        }

        return response()->json($response);
    }

    
    private function processJournalRows(array $rows): array
    {
        $groupedEntries = [];
        $entryBuffer = [];
        $currentDateValue = null;
        $entityType = null;
        $entityId = null;
        $name = null;
        $transtype = null;

        foreach ($rows as $row) {
            $type = $row['type'] ?? null;

            if ($type === 'Data') {
                $colData = $row['ColData'] ?? [];
                $firstValue = $colData[0]['value'] ?? null;

                if (empty($firstValue) && empty($currentDateValue)) continue;

                if ($currentDateValue === null && !empty($firstValue)) {
                    $currentDateValue = $firstValue;
                }

                $tratype = $colData[1]['value'] ?? null;
                if (!empty($tratype)) {
                    $transtype = $tratype;
                } else {
                    $colData[1]['value'] = $transtype;
                }

                $entityName = $colData[3]['value'] ?? null;
                if (!empty($entityName)) {
                    [$entityType, $entityId] = $this->mapQuickBooksEntity($entityName);
                    $name = $entityName;
                    $colData[3]['emp_id'] = $entityId;
                    $colData[3]['type'] = $entityType;
                } else {
                    $colData[3]['value'] = $name;
                    $colData[3]['emp_id'] = $entityId;
                    $colData[3]['type'] = $entityType;
                }

                $colData[0]['value'] = $currentDateValue;
                $entryBuffer[] = $colData;

            } elseif ($type === 'Section') {
                if (!empty($entryBuffer)) {
                    $groupedEntries[] = $entryBuffer;
                    $entryBuffer = [];
                    $currentDateValue = null;
                    $entityType = null;
                    $entityId = null;
                    $name = null;
                    $transtype = null;
                }
            }
        }

        if (!empty($entryBuffer)) {
            $groupedEntries[] = $entryBuffer;
        }

        return $groupedEntries;
    }

    private function mapQuickBooksEntity($data)
    {
        $name = $data; // example: "Tania's Nursery"

        if (!empty($name)) {
            // Try to find in Vendors
            $vendor = Vender::where('name', $name)->first();
            if ($vendor) {
                 return ['vendor', $vendor->id];
            } else {
                // Try to find in Customers
                $customer = Customer::where('name', $name)->first();
                if ($customer) {
                     return ['customer', $customer->id];
                } else {
                    // Try to find in Employees
                    $employee = Employee::where('name', $name)->first();
                    if ($employee) {
                        return ['employee', $employee->id];
                    }
                }
            }
        }
        return [null, null];
    }
    private function createJournalEntry($entryData)
    {   
        try {
            // Extract data from the first row (assuming it's the header row for the entry)
            $firstRow = $entryData[0] ?? [];
            $date = $firstRow[0]['value'] ?? now()->toDateString();
            $transactionType = $firstRow[1]['value'] ?? 'Journal';
            $num = $firstRow[2]['value'] ?? '';
            $name = $firstRow[3]['value'] ?? '';
            $memo = $firstRow[4]['value'] ?? '';
            $accountName = $firstRow[5]['value'] ?? '';
            $debit = $firstRow[6]['value'] ?? 0;
            $credit = $firstRow[7]['value'] ?? 0;
            $entityType = $firstRow[1]['value'] ?? '';
            $entityId = $firstRow[3]['emp_id'] ?? null;
            $entityName = $firstRow[3]['value'] ?? '';


            $totalDebit = 0;
            $totalCredit = 0;

            // Calculate totals from all rows
            foreach ($entryData as $row) {
                $debitVal = floatval($row[6]['value'] ?? 0);
                $creditVal = floatval($row[7]['value'] ?? 0);
                $totalDebit += $debitVal;
                $totalCredit += $creditVal;
            }
            if (abs($totalCredit - $totalDebit) > 0.0001) {
                return [
                    'status' => 'skipped',
                    'data' => [
                        'date' => $date,
                        'reference' => $num,
                        'description' => $memo,
                        'entity_name' => $name,
                        'total_debit' => $totalDebit,
                        'total_credit' => $totalCredit,
                        'difference' => abs($totalDebit - $totalCredit),
                        'reason' => 'Unbalanced Entry',
                        'rows' => $entryData,
                    ]
                ];
            }
            $journal = new JournalEntry();
            $journal->journal_id = $this->journalNumber();
            $journal->date = date('Y-m-d', strtotime($date));
            $journal->reference = $num;
            $journal->description = $memo;
            $journal->created_by = Auth::user()->creatorId();
            $journal->owned_by = Auth::user()->ownedId();
            $journal->save();
            $journal->created_at = date('Y-m-d H:i:s', strtotime($date));
            $journal->updated_at = date('Y-m-d H:i:s', strtotime($date));
            $journal->save();

            foreach ($entryData as $row) {
                $accountName = $row[5]['value'] ?? '';
                $debit = floatval($row[6]['value'] ?? 0);
                $credit = floatval($row[7]['value'] ?? 0);
                $memo = $row[4]['value'] ?? '';

                $account = $this->ensureCOA($accountName);
                if (!$account) {
                    dd($accountName);
                    continue;
                }

                $journalItem = new JournalItem();
                $journalItem->journal = $journal->id;
                $journalItem->account = $account->id;
                $journalItem->description = $memo;
                $journalItem->debit = $debit;
                $journalItem->credit = $credit;
                $journalItem->type = $entityType;
                $journalItem->name = $entityName;
                if ($entityType === 'customer') {
                    $journalItem->customer_id = $entityId;
                } elseif ($entityType === 'vendor') {
                    $journalItem->vendor_id = $entityId;
                } elseif ($entityType === 'employee') {
                    $journalItem->employee_id = $entityId;
                }
                $journalItem->save();
                $journalItem->created_at = date('Y-m-d H:i:s', strtotime($date));
                $journalItem->updated_at = date('Y-m-d H:i:s', strtotime($date));
                $journalItem->save();

                $bankAccounts = BankAccount::where('chart_account_id', '=', $account->id)->get();
                if (!empty($bankAccounts)) {
                    foreach ($bankAccounts as $bankAccount) {
                        $old_balance = $bankAccount->opening_balance;
                        if ($journalItem->debit > 0) {
                            $new_balance = $old_balance - $journalItem->debit;
                        }
                        if ($journalItem->credit > 0) {
                            $new_balance = $old_balance + $journalItem->credit;
                        }
                        if (isset($new_balance)) {
                            $bankAccount->opening_balance = $new_balance;
                            $bankAccount->save();
                        }
                    }
                }

                if ($debit > 0) {
                    $data = [
                        'account_id' => $account->id,
                        'transaction_type' => 'Debit',
                        'transaction_amount' => $debit,
                        'reference' => 'Journal',
                        'reference_id' => $journal->id,
                        'reference_sub_id' => $journalItem->id,
                        'date' => $journal->date,
                    ];
                } elseif ($credit > 0) {
                    $data = [
                        'account_id' => $account->id,
                        'transaction_type' => 'Credit',
                        'transaction_amount' => $credit,
                        'reference' => 'Journal',
                        'reference_id' => $journal->id,
                        'reference_sub_id' => $journalItem->id,
                        'date' => $journal->date,
                    ];
                } else {
                    continue; // skipping entries of 0 in the trnasaction line table.
                }
                $this->addTransactionLines($data, 'create');
            }

            return [
                'status' => 'created',
                'data' => $journal
            ];

        } catch (\Exception $e) {
            // Log error and skip
            dd($e->getMessage());
            \Log::error('Error creating journal entry: ' . $e->getMessage());
            return null;
        }
    }
    public static function addTransactionLines($data, $action)
    {
        $existingTransaction = TransactionLines::where('reference_id', $data['reference_id'])
            ->where('reference_sub_id', $data['reference_sub_id'])->where('reference', $data['reference'])
            ->first();
        if ($existingTransaction && $action == 'edit') {
            $transactionLines = $existingTransaction;
        } else {
            $transactionLines = new TransactionLines();
        }
        $transactionLines->account_id = $data['account_id'];
        $transactionLines->reference = $data['reference'];
        $transactionLines->reference_id = $data['reference_id'];
        $transactionLines->reference_sub_id = $data['reference_sub_id'];
        $transactionLines->date = $data['date'];
        $transactionLines->product_id = @$data['product_id'] ?? @$transactionLines->product_id;
        $transactionLines->product_type = @$data['product_type'] ?? @$transactionLines->product_type;
        $transactionLines->product_item_id = @$data['product_item_id'] ?? @$transactionLines->product_item_id;
        if ($data['transaction_type'] == "Credit") {
            $transactionLines->credit = $data['transaction_amount'];
            $transactionLines->debit = 0;
        } else {
            $transactionLines->credit = 0;
            $transactionLines->debit = $data['transaction_amount'];
        }
        $transactionLines->created_by = Auth::user()->creatorId();
        $transactionLines->created_at = date('Y-m-d H:i:s', strtotime($data['date']));
        $transactionLines->updated_at = date('Y-m-d H:i:s', strtotime($data['date']));
        $transactionLines->save();
        $transactionLines->created_at = date('Y-m-d H:i:s', strtotime($data['date']));
        $transactionLines->updated_at = date('Y-m-d H:i:s', strtotime($data['date']));
        $transactionLines->save();
    }
    private function journalNumber()
    {
        $latest = JournalEntry::where('created_by', '=', Auth::user()->creatorId())->latest()->first();
        if (!$latest) {
            return 1;
        }
        return $latest->journal_id + 1;
    }

    private function ensureCOA($fullName)
    {
 
        $account = ChartOfAccount::where('name', $fullName)->where('created_by', Auth::user()->creatorId())->first();
        if (!$account) {
            $accountId = $this->getAccountIdByFullName($fullName);
           
            if($accountId){
                $account = ChartOfAccount::where('id', $accountId)->where('created_by', Auth::user()->creatorId())->first();
                return $account;
            }
            // dd($account);
          $typeMapping = [
                'accounts payable (a/p)' => 'Liabilities',
                'accounts payable' => 'Liabilities',
                'credit card' => 'Liabilities',
                'long term liabilities' => 'Liabilities',
                'other current liabilities' => 'Liabilities',
                'loan payable' => 'Liabilities',
                'notes payable' => 'Liabilities',
                'board of equalization payable' => 'Liabilities',
                'arizona dept. of revenue payable' => 'Liabilities',
                'accounts receivable (a/r)' => 'Assets',
                'accounts receivable' => 'Assets',
                'bank' => 'Assets',
                'checking' => 'Assets',
                'savings' => 'Assets',
                'undeposited funds' => 'Assets',
                'inventory asset' => 'Assets',
                'other current assets' => 'Assets',
                'fixed assets' => 'Assets',
                'truck' => 'Assets',
                'equity' => 'Equity',
                'opening balance equity' => 'Equity',
                'retained earnings' => 'Equity',
                'income' => 'Income',
                'other income' => 'Income',
                'sales of product income' => 'Income',
                'service/fee income' => 'Income',
                'sales' => 'Income',
                'cost of goods sold' => 'Costs of Goods Sold',
                'cogs' => 'Costs of Goods Sold',
                'expenses' => 'Expenses',
                'expense' => 'Expenses',
                'other expense' => 'Expenses',
                'marketing' => 'Expenses',
                'insurance' => 'Expenses',
                'utilities' => 'Expenses',
                'rent or lease' => 'Expenses',
                'meals and entertainment' => 'Expenses',
                'bank charges' => 'Expenses',
                'depreciation' => 'Expenses',
            ];

            // Convert to lowercase for comparison
            $typeName = strtolower(trim($fullName));
            $systemTypeName = 'Other'; // Default
            $detailType = 'Other';

            // Check for exact match first
            if (isset($typeMapping[$typeName])) {
                $systemTypeName = $typeMapping[$typeName];
                $detailType = $typeName;
            } else {
                // Fuzzy match: if it contains the word "expense"
                foreach ($typeMapping as $key => $value) {
                    $typeName = str_replace([':', ',', '&', '|'], ' ', $typeName);
                    $typeName = preg_replace('/\s+/', ' ', trim($typeName));
                    if (str_contains($typeName, strtolower($key))) {
                        $systemTypeName = $value;
                        $detailType = $key;
                        break;
                    }
                }
                
                // Additional fallback: if the name itself contains "expense"
                // if (str_contains($typeName, 'expense')) {
                //     $systemTypeName = 'Expenses';
                // }
                    // $type = ChartOfAccountType::firstOrCreate(
                    //     ['name' => 'Other', 'created_by' => Auth::user()->creatorId()]
                    // );
                    // $subType = ChartOfAccountSubType::firstOrCreate([
                    //     'type' => $type->id,
                    //     'name' => 'Other',
                    //     'created_by' => Auth::user()->creatorId(),
                    // ]);
                    // $account = ChartOfAccount::create([
                    //     'name' => $fullName,
                    //     'type' => $type->id,
                    //     'sub_type' => $subType->id,
                    //     'created_by' => Auth::user()->creatorId(),
                    // ]);
                }       
                 $debugNames = [
                    'Legal & Professional Fees:Lawyer',
                    'Landscaping Services:Job Materials:Plants and Soil',
                    'Landscaping Services:Job Materials:Fountains and Garden Lighting',
                    'Legal & Professional Fees:Accounting',
                    'Landscaping Services:Job Materials:Sprinklers and Drip Systems',
                ];

                // if (!in_array($fullName, $debugNames)) {
                //     dd($fullName, $systemTypeName, $detailType, $typeName);
                // }
                // else{
                //     dd($fullName,$systemTypeName,$detailType,$typeName,$typeMapping,'sds');
                // }
                 $type = ChartOfAccountType::firstOrCreate(
                        ['name' => $systemTypeName, 'created_by' => Auth::user()->creatorId()]
                    );

                    $subType = ChartOfAccountSubType::firstOrCreate([
                        'type' => $type->id,
                        'name' => $detailType ?: 'Other',
                        'created_by' => Auth::user()->creatorId(),
                    ]);
                    $acct = ChartOfAccount::where('name', $fullName)
                            ->where('type', $type->id)
                            ->where('sub_type', $subType->id)
                            ->where('created_by', Auth::user()->creatorId())
                            ->first();
                if (!$acct) {
                    $account = ChartOfAccount::create([
                        'name' => $fullName,
                        'type' => $type->id,
                        'sub_type' => $subType->id,
                        'created_by' => Auth::user()->creatorId(),
                    ]);
                }


           
        }
        return $account;
    }

    public function getAccountIdByFullName($fullName)
    {
        $parts = explode(':', $fullName);
        $parentId = null;
        $account = null;
        foreach ($parts as $part) {
            $part = trim($part);
            ;

            if (is_null($parentId)) {
                // Find top-level account (no parent)
                $account = ChartOfAccount::where('name', $part)
                    ->where(function ($q) {
                        $q->whereNull('parent')->orWhere('parent', 0);
                    })
                    ->first();
                    // dd($part,$parts,$account);
            } else {
                // Find parent name first in chart_of_account_parents
                $parentRow = \DB::table('chart_of_account_parents')
                    ->where('id', $parentId)
                    ->first();

                // Now find next child account using parent_id
                $account = ChartOfAccount::where('name', $part)
                    ->where('parent', $parentId)
                    ->first();
            }

            // If not found, stop
            if (!$account) {
                return null;
            }

            // Now find this account’s parent row id for next iteration
            $parentRow = \DB::table('chart_of_account_parents')
                ->where('account', $account->id)
                ->first();

            $parentId = $parentRow ? $parentRow->id : null;
        }

        return $account ? $account->id : null;
    }
    protected function startQueueWorkerForJob()
    {
        try {
            // Get the base path of the Laravel application
            $basePath = base_path();
            $artisanPath = $basePath . DIRECTORY_SEPARATOR . 'artisan';

            // Build the command to run queue worker
            // --once: Process only one job and then exit
            // --timeout=3600: Allow job to run for 1 hour
            // --tries=3: Retry failed jobs 3 times
            $command = sprintf(
                'php "%s" queue:work database --once --timeout=3600 --tries=3 > /dev/null 2>&1 &',
                $artisanPath
            );

            // For Windows, use different command
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $command = sprintf(
                    'start /B php "%s" queue:work database --once --timeout=3600 --tries=3',
                    $artisanPath
                );
            }

            // Execute the command in the background
            if (function_exists('exec')) {
                exec($command);
                \Log::info('Queue worker started automatically for import job');
            } else {
                \Log::warning('exec() function not available, queue worker not started automatically');
            }

        } catch (\Exception $e) {
            \Log::error('Failed to start queue worker automatically: ' . $e->getMessage());
            // Don't throw exception - job is already dispatched and will be processed when worker runs manually
        }
    }
    protected function refreshTokenIfNeeded()
    {
        try {
            $token = \App\Models\QuickBooksToken::where('user_id', auth()->id())
                ->latest()->first();
            if (!$token) throw new \Exception("No QuickBooks tokens for user {auth()->id()}");

            if ($token->expires_at && now()->addMinutes(5)->greaterThan($token->expires_at)) {
                $this->logInfo('Refreshing QuickBooks token...');
                $api = new QuickBooksApiController();
                $new = $api->refreshToken($token->refresh_token);
                if ($new) $this->logSuccess('QuickBooks token refreshed successfully');
                else throw new \Exception('Token refresh failed');
            }
        } catch (\Throwable $e) {
            dd($e);
            $this->logError('Token refresh failed: ' . $e->getMessage());
            throw $e;
        }
    }
    protected function logSuccess($msg) { $this->addLog('[SUCCESS]', $msg); }
    protected function logError($msg) { $this->addLog('[ERROR]', $msg); }
    protected function logInfo($msg) { $this->addLog('[INFO]', $msg); }

    protected function addLog($type, $msg)
    {
        $key = "qb_import_progress_{auth()->id()}";
        $progress = Cache::get($key, []);
        $progress['logs'][] = "{$type} {$msg} at " . now();
        Cache::put($key, $progress, 3600);
    }
    private function exportSkippedEntriesToExcel($skippedEntries)
    {
        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set headers
            $headers = ['Date', 'Reference', 'Description', 'Entity Name', 'Total Debit', 'Total Credit', 'Difference', 'Reason'];
            $sheet->fromArray($headers, null, 'A1');

            // Style header row
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '366092']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ];
            $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

            // Add data rows
            $row = 2;
            foreach ($skippedEntries as $entry) {
                $sheet->setCellValue("A{$row}", $entry['date'] ?? '');
                $sheet->setCellValue("B{$row}", $entry['reference'] ?? '');
                $sheet->setCellValue("C{$row}", $entry['description'] ?? '');
                $sheet->setCellValue("D{$row}", $entry['entity_name'] ?? '');
                $sheet->setCellValue("E{$row}", $entry['total_debit'] ?? 0);
                $sheet->setCellValue("F{$row}", $entry['total_credit'] ?? 0);
                $sheet->setCellValue("G{$row}", $entry['difference'] ?? 0);
                $sheet->setCellValue("H{$row}", $entry['reason'] ?? 'Unknown');

                // Highlight rows with difference
                if (($entry['difference'] ?? 0) > 0) {
                    $sheet->getStyle("A{$row}:H{$row}")->getFill()
                        ->setFillType('solid')
                        ->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFF00'));
                }

                $row++;
            }

            // Auto-fit column widths
            foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Create file path
            $fileName = 'skipped_entries_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
            $filePath = storage_path('app/exports/' . $fileName);
            
            // Ensure directory exists
            if (!is_dir(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }

            // Save file
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filePath);

            \Log::info("Skipped entries exported to: {$filePath}");

            return $filePath;

        } catch (\Exception $e) {
            \Log::error('Failed to export skipped entries: ' . $e->getMessage());
            return null;
        }
    }
    // 💰 Fetch Bill Payments (Vendor Payments)
    public function billPayments()
    {
        try {
            $query = "SELECT * FROM BillPayment STARTPOSITION 1 MAXRESULTS 200";
            $response = $this->runQuery($query);

            // ✅ If it's a JsonResponse (expired token, etc.), just return it
            if ($response instanceof \Illuminate\Http\JsonResponse) {
                return $response;
            }

            // ✅ If QuickBooks returned a fault (error)
            if (isset($response['Fault'])) {
                throw new \Exception($response['Fault']['Error'][0]['Message'] ?? 'Error fetching BillPayments');
            }

            // ✅ Extract the data safely
            $billPayments = collect($response['QueryResponse']['BillPayment'] ?? []);

            // ✅ Get one sample record
            $first = $billPayments->first();

            // ✅ Dump a clean, structured view
            return dd([
                'status' => 'success',
                'count' => $billPayments->count(),
                'sample' => $first,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // public function billsWithPayments()
    // {
    //     try {
    //         // Step 1: Fetch Bills and Bill Payments
    //         $billsResponse = $this->runQuery("SELECT * FROM Bill");
    //         $paymentsResponse = $this->runQuery("SELECT * FROM BillPayment");

    //         // Step 2: Map Bills with Expense Accounts
    //         $bills = collect($billsResponse['QueryResponse']['Bill'] ?? [])->map(function ($bill) {
    //             // Expense (debit) accounts
    //             $expenseAccounts = collect($bill['Line'] ?? [])
    //                 ->map(function ($line) {
    //                     if (isset($line['AccountBasedExpenseLineDetail']['AccountRef'])) {
    //                         return [
    //                             'Id' => $line['AccountBasedExpenseLineDetail']['AccountRef']['value'] ?? null,
    //                             'Name' => $line['AccountBasedExpenseLineDetail']['AccountRef']['name'] ?? null,
    //                             'Amount' => $line['Amount'] ?? 0,
    //                             'Description' => $line['Description'] ?? null,
    //                         ];
    //                     }
    //                     return null;
    //                 })
    //                 ->filter()
    //                 ->values()
    //                 ->toArray();

    //             // A/P (credit) account
    //             $apAccount = [
    //                 'Id' => $bill['APAccountRef']['value'] ?? null,
    //                 'Name' => $bill['APAccountRef']['name'] ?? null,
    //             ];

    //             return [
    //                 'BillId' => $bill['Id'] ?? null,
    //                 'VendorName' => $bill['VendorRef']['name'] ?? null,
    //                 'VendorId' => $bill['VendorRef']['value'] ?? null,
    //                 'TxnDate' => $bill['TxnDate'] ?? null,
    //                 'DueDate' => $bill['DueDate'] ?? null,
    //                 'TotalAmount' => $bill['TotalAmt'] ?? 0,
    //                 'Balance' => $bill['Balance'] ?? 0,
    //                 'Currency' => $bill['CurrencyRef']['name'] ?? null,
    //                 'Address' => $bill['VendorAddr']['Line1'] ?? null,
    //                 'ExpenseAccounts' => $expenseAccounts,
    //                 'APAccount' => $apAccount,
    //                 'Payments' => [],
    //             ];
    //         });

    //         // Step 3: Map Bill Payments (with payment account)
    //         $payments = collect($paymentsResponse['QueryResponse']['BillPayment'] ?? [])->map(function ($payment) {
    //             // Detect payment source account
    //             $paymentAccount = null;
    //             if (isset($payment['CreditCardPayment']['CCAccountRef'])) {
    //                 $paymentAccount = $payment['CreditCardPayment']['CCAccountRef'];
    //             } elseif (isset($payment['CheckPayment']['BankAccountRef'])) {
    //                 $paymentAccount = $payment['CheckPayment']['BankAccountRef'];
    //             } elseif (isset($payment['PayFromAccountRef'])) {
    //                 $paymentAccount = $payment['PayFromAccountRef'];
    //             }

    //             return [
    //                 'PaymentId' => $payment['Id'] ?? null,
    //                 'VendorId' => $payment['VendorRef']['value'] ?? null,
    //                 'VendorName' => $payment['VendorRef']['name'] ?? null,
    //                 'TxnDate' => $payment['TxnDate'] ?? null,
    //                 'TotalAmount' => $payment['TotalAmt'] ?? 0,
    //                 'PayType' => $payment['PayType'] ?? null,
    //                 'PaymentAccount' => $paymentAccount ? [
    //                     'Id' => $paymentAccount['value'] ?? null,
    //                     'Name' => $paymentAccount['name'] ?? null,
    //                 ] : null,
    //                 'LinkedTxn' => collect($payment['Line'] ?? [])
    //                     ->pluck('LinkedTxn')
    //                     ->flatten(1)
    //                     ->toArray(),
    //             ];
    //         });

    //         // Step 4: Attach Payments to Their Corresponding Bills
    //         $billsWithPayments = $bills->map(function ($bill) use ($payments) {
    //             $linkedPayments = $payments->filter(function ($payment) use ($bill) {
    //                 return collect($payment['LinkedTxn'])->contains(function ($txn) use ($bill) {
    //                     return isset($txn['TxnType'], $txn['TxnId'])
    //                         && $txn['TxnType'] === 'Bill'
    //                         && $txn['TxnId'] == $bill['BillId'];
    //                 });
    //             })->values();

    //             $bill['Payments'] = $linkedPayments;
    //             return $bill;
    //         });

    //         // Step 5: Return response
    //         return dd([
    //             'status' => 'success',
    //             'count' => $billsWithPayments->count(),
    //             'data' => $billsWithPayments->values(),
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage(),
    //         ]);
    //     }
    // }
    public function billsWithPayments()
    {
        try {
            // Step 1: Fetch Bills and Bill Payments
            $billsResponse = $this->runQuery("SELECT * FROM Bill");
            $paymentsResponse = $this->runQuery("SELECT * FROM BillPayment");

            // Step 2: Map Bills (with expense + A/P accounts)
            $bills = collect($billsResponse['QueryResponse']['Bill'] ?? [])->map(function ($bill) {
                $expenseAccounts = collect($bill['Line'] ?? [])
                    ->map(function ($line) {
                        if (isset($line['AccountBasedExpenseLineDetail']['AccountRef'])) {
                            return [
                                'Id' => $line['AccountBasedExpenseLineDetail']['AccountRef']['value'] ?? null,
                                'Name' => $line['AccountBasedExpenseLineDetail']['AccountRef']['name'] ?? null,
                                'Amount' => $line['Amount'] ?? 0,
                                'Description' => $line['Description'] ?? null,
                            ];
                        }
                        return null;
                    })
                    ->filter()
                    ->values()
                    ->toArray();

                $apAccount = [
                    'Id' => $bill['APAccountRef']['value'] ?? null,
                    'Name' => $bill['APAccountRef']['name'] ?? null,
                ];

                return [
                    'BillId' => $bill['Id'] ?? null,
                    'VendorName' => $bill['VendorRef']['name'] ?? null,
                    'VendorId' => $bill['VendorRef']['value'] ?? null,
                    'TxnDate' => $bill['TxnDate'] ?? null,
                    'DueDate' => $bill['DueDate'] ?? null,
                    'TotalAmount' => $bill['TotalAmt'] ?? 0,
                    'Balance' => $bill['Balance'] ?? 0,
                    'Currency' => $bill['CurrencyRef']['name'] ?? null,
                    'Address' => $bill['VendorAddr']['Line1'] ?? null,
                    'ExpenseAccounts' => $expenseAccounts,
                    'APAccount' => $apAccount,
                    'Payments' => [],
                ];
            });

            // Step 3: Map Payments (with payment account)
            $payments = collect($paymentsResponse['QueryResponse']['BillPayment'] ?? [])->map(function ($payment) {
                $paymentAccount = null;
                if (isset($payment['CreditCardPayment']['CCAccountRef'])) {
                    $paymentAccount = $payment['CreditCardPayment']['CCAccountRef'];
                } elseif (isset($payment['CheckPayment']['BankAccountRef'])) {
                    $paymentAccount = $payment['CheckPayment']['BankAccountRef'];
                } elseif (isset($payment['PayFromAccountRef'])) {
                    $paymentAccount = $payment['PayFromAccountRef'];
                }

                return [
                    'PaymentId' => $payment['Id'] ?? null,
                    'VendorId' => $payment['VendorRef']['value'] ?? null,
                    'VendorName' => $payment['VendorRef']['name'] ?? null,
                    'TxnDate' => $payment['TxnDate'] ?? null,
                    'TotalAmount' => $payment['TotalAmt'] ?? 0,
                    'PayType' => $payment['PayType'] ?? null,
                    'PaymentAccount' => $paymentAccount ? [
                        'Id' => $paymentAccount['value'] ?? null,
                        'Name' => $paymentAccount['name'] ?? null,
                    ] : null,
                    'LinkedTxn' => collect($payment['Line'] ?? [])
                        ->pluck('LinkedTxn')
                        ->flatten(1)
                        ->toArray(),
                ];
            });

            // Step 4: Attach payments to bills
            $billsWithPayments = $bills->map(function ($bill) use ($payments) {
                $linkedPayments = $payments->filter(function ($payment) use ($bill) {
                    return collect($payment['LinkedTxn'])->contains(function ($txn) use ($bill) {
                        return isset($txn['TxnType'], $txn['TxnId'])
                            && $txn['TxnType'] === 'Bill'
                            && $txn['TxnId'] == $bill['BillId'];
                    });
                })->values();

                $bill['Payments'] = $linkedPayments;
                return $bill;
            })
            // ✅ Only keep bills that actually have payments
            ->filter(function ($bill) {
                return count($bill['Payments']) > 0;
            })
            ->values();

            // ✅ Return only one bill (the first one with payments)
            $singleBill = $billsWithPayments->first();

            // Step 5: Return response
            return dd([
                'status' => 'success',
                'total_with_payments' => $billsWithPayments->count(),
                'single_bill' => $singleBill,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
    public function checkUnbalancedBills()
    {
        try {
            $token = $this->accessToken();
            $realm = $this->realmId();

            // 1️⃣ Fetch Bills
            $billsResponse = $this->runQuery("select * from Bill", $token, $realm);
            $allBills = collect($billsResponse['QueryResponse']['Bill'] ?? []);

            // 2️⃣ Fetch BillPayments
            $paymentsResponse = $this->runQuery("select * from BillPayment", $token, $realm);
            $allPayments = collect($paymentsResponse['QueryResponse']['BillPayment'] ?? []);

            $billPaymentsTotal = [];
            $billPaymentDetails = [];

            // 3️⃣ Map BillPayments → Bills
            foreach ($allPayments as $payment) {
                $paymentId = $payment['Id'] ?? null;
                $paymentDate = $payment['TxnDate'] ?? null;
                $lines = $payment['Line'] ?? [];

                foreach ($lines as $line) {
                    $amount = isset($line['Amount']) ? (float)$line['Amount'] : 0;

                    if (isset($line['LinkedTxn'])) {
                        $linkedTxns = is_array($line['LinkedTxn']) ? $line['LinkedTxn'] : [$line['LinkedTxn']];
                        foreach ($linkedTxns as $lt) {
                            if (isset($lt['TxnType']) && strtolower($lt['TxnType']) === 'bill') {
                                $billId = $lt['TxnId'];

                                // Sum payments per bill
                                $billPaymentsTotal[$billId] = ($billPaymentsTotal[$billId] ?? 0) + $amount;

                                // Store details
                                $billPaymentDetails[$billId][] = [
                                    'PaymentId' => $paymentId,
                                    'Date' => $paymentDate,
                                    'Amount' => $amount,
                                ];
                            }
                        }
                    }
                }
            }

            // 4️⃣ Detect mismatches
            $overpaidBills = [];
            $underpaidBills = [];
            $multiplePaymentBills = [];

            foreach ($allBills as $bill) {
                $billId = $bill['Id'];
                $vendorName = $bill['VendorRef']['name'] ?? 'Unknown Vendor';
                $total = (float)($bill['TotalAmt'] ?? 0);
                $balance = (float)($bill['Balance'] ?? 0);
                $paid = (float)($billPaymentsTotal[$billId] ?? 0);

                // Case A: Overpaid
                if ($paid > 0 && $paid > $total) {
                    $overpaidBills[] = [
                        'BillId' => $billId,
                        'VendorName' => $vendorName,
                        'BillTotal' => $total,
                        'TotalPayments' => $paid,
                        'OverPaidBy' => round($paid - $total, 2),
                        'Balance' => $balance,
                        'Payments' => $billPaymentDetails[$billId] ?? [],
                    ];
                }

                // Case B: Underpaid (one payment only)
                if ($paid > 0 && $paid < $total && isset($billPaymentDetails[$billId]) && count($billPaymentDetails[$billId]) === 1) {
                    $underpaidBills[] = [
                        'BillId' => $billId,
                        'VendorName' => $vendorName,
                        'BillTotal' => $total,
                        'TotalPayments' => $paid,
                        'UnderPaidBy' => round($total - $paid, 2),
                        'Balance' => $balance,
                        'Payments' => $billPaymentDetails[$billId],
                    ];
                }

                // Case C: Multiple payments on single bill
                if (isset($billPaymentDetails[$billId]) && count($billPaymentDetails[$billId]) > 1) {
                    $multiplePaymentBills[] = [
                        'BillId' => $billId,
                        'VendorName' => $vendorName,
                        'BillTotal' => $total,
                        'Balance' => $balance,
                        'TotalPayments' => $paid,
                        'Payments' => $billPaymentDetails[$billId],
                    ];
                }
            }

            return response()->json([
                'overpaid_count' => count($overpaidBills),
                'underpaid_count' => count($underpaidBills),
                'multiple_payment_count' => count($multiplePaymentBills),
                'overpaid_bills' => $overpaidBills,
                'underpaid_bills' => $underpaidBills,
                'multiple_payment_bills' => $multiplePaymentBills,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }
    }


    // public function expensesWithPayments()
    // {
    //     try {
    //         // --- CONFIG ---
    //         // Payment-like types to fetch
    //         $typesToQuery = [
    //             'Payment',
    //             'Check',
    //             'BillPayment',
    //             'CreditCardCredit',
    //             'VendorCredit',
    //             'Deposit',
    //             'Purchase', // include as candidate payment
    //         ];

    //         // Fuzzy fallback config (only used when no explicit LinkedTxn matches found)
    //         $useFuzzyFallback = true;
    //         $fuzzyDateWindowDays = 7;      // +/- days
    //         $fuzzyAmountTolerance = 0.5;   // tolerance in currency units (adjust as needed)

    //         // --- 1) Fetch purchases (expenses) ---
    //         $purchasesResp = $this->runQuery("SELECT * FROM Purchase");
    //         $rawPurchases = $purchasesResp['QueryResponse']['Purchase'] ?? [];

    //         // --- 2) Fetch all payment-like types and merge ---
    //         $allRawPayments = collect();
    //         foreach ($typesToQuery as $type) {
    //             try {
    //                 $resp = $this->runQuery("SELECT * FROM {$type}");
    //                 $items = $resp['QueryResponse'][$type] ?? [];
    //                 $allRawPayments = $allRawPayments->merge(collect($items));
    //             } catch (\Exception $e) {
    //                 \Log::warning("Failed to fetch {$type}: " . $e->getMessage());
    //             }
    //         }

    //         // --- Helper: Extract & normalize LinkedTxn entries robustly ---
    //         $extractLinkedTxn = function ($raw) {
    //             $linked = [];

    //             // 1) Top-level LinkedTxn
    //             if (!empty($raw['LinkedTxn']) && is_array($raw['LinkedTxn'])) {
    //                 $linked = array_merge($linked, $raw['LinkedTxn']);
    //             }

    //             // 2) Inside Line[].LinkedTxn
    //             if (!empty($raw['Line']) && is_array($raw['Line'])) {
    //                 $fromLines = collect($raw['Line'])
    //                     ->pluck('LinkedTxn')
    //                     ->flatten(1)
    //                     ->filter()
    //                     ->values()
    //                     ->toArray();
    //                 $linked = array_merge($linked, $fromLines);
    //             }

    //             // 3) Apply / ApplyTo / AppliedToTxn / ApplyToTxn - common alternative names
    //             if (!empty($raw['Apply']) && is_array($raw['Apply'])) {
    //                 $linked = array_merge($linked, $raw['Apply']);
    //             }
    //             if (!empty($raw['AppliedToTxn']) && is_array($raw['AppliedToTxn'])) {
    //                 $linked = array_merge($linked, $raw['AppliedToTxn']);
    //             }

    //             // 4) Also check for shapes like ['TxnId'] / ['Id'] pairs directly on the raw (rare)
    //             if (isset($raw['TxnId']) && isset($raw['TxnType'])) {
    //                 $linked[] = ['TxnId' => $raw['TxnId'], 'TxnType' => $raw['TxnType']];
    //             }

    //             // Normalize each entry to have TxnId and TxnType keys (when possible)
    //             $normalized = [];
    //             foreach ($linked as $l) {
    //                 if (!is_array($l))
    //                     continue;

    //                 // possible keys in different shapes
    //                 $txnId = $l['TxnId'] ?? $l['Id'] ?? $l['AppliedToTxnId'] ?? $l['AppliedToTxnId'] ?? null;
    //                 $txnType = $l['TxnType'] ?? $l['TxnTypeName'] ?? $l['Type'] ?? $l['TxnType'] ?? null;

    //                 // some shapes use 'TxnId' numeric etc. cast to string for consistent comparison
    //                 if ($txnId !== null) {
    //                     $normalized[] = [
    //                         'TxnId' => (string) $txnId,
    //                         'TxnType' => $txnType ? (string) $txnType : null,
    //                     ];
    //                 }
    //             }

    //             // dedupe
    //             $unique = [];
    //             foreach ($normalized as $n) {
    //                 $key = ($n['TxnId'] ?? '') . '|' . ($n['TxnType'] ?? '');
    //                 if (!isset($unique[$key]))
    //                     $unique[$key] = $n;
    //             }

    //             return array_values($unique);
    //         };

    //         // --- Helper: detect payment account and vendor info ---
    //         $detectPaymentAccount = function ($raw) {
    //             if (!empty($raw['CreditCardPayment']['CCAccountRef']))
    //                 return $raw['CreditCardPayment']['CCAccountRef'];
    //             if (!empty($raw['CheckPayment']['BankAccountRef']))
    //                 return $raw['CheckPayment']['BankAccountRef'];
    //             if (!empty($raw['BankAccountRef']))
    //                 return $raw['BankAccountRef'];
    //             if (!empty($raw['PayFromAccountRef']))
    //                 return $raw['PayFromAccountRef'];
    //             if (!empty($raw['DepositToAccountRef']))
    //                 return $raw['DepositToAccountRef'];
    //             if (!empty($raw['CCAccountRef']))
    //                 return $raw['CCAccountRef'];
    //             if (!empty($raw['AccountRef']))
    //                 return $raw['AccountRef'];
    //             return null;
    //         };

    //         // --- 3) Normalize all payments ---
    //         $normalizedPayments = $allRawPayments->map(function ($raw) use ($extractLinkedTxn, $detectPaymentAccount) {
    //             // vendor detection
    //             $vendorId = $raw['VendorRef']['value'] ?? $raw['EntityRef']['value'] ?? $raw['PayeeRef']['value'] ?? $raw['CustomerRef']['value'] ?? null;
    //             $vendorName = $raw['VendorRef']['name'] ?? $raw['EntityRef']['name'] ?? $raw['PayeeRef']['name'] ?? $raw['CustomerRef']['name'] ?? null;

    //             $paymentAccount = $detectPaymentAccount($raw);

    //             $total = $raw['TotalAmt'] ?? $raw['Amount'] ?? $raw['TotalAmount'] ?? null;

    //             return [
    //                 'Raw' => $raw,
    //                 'PaymentId' => $raw['Id'] ?? ($raw['PaymentId'] ?? null),
    //                 'TxnTypeRaw' => $raw['TxnType'] ?? null,
    //                 'TxnDate' => $raw['TxnDate'] ?? null,
    //                 'DocNumber' => $raw['DocNumber'] ?? null,
    //                 'TotalAmount' => $total !== null ? (float) $total : null,
    //                 'PaymentAccount' => $paymentAccount ? [
    //                     'Id' => $paymentAccount['value'] ?? null,
    //                     'Name' => $paymentAccount['name'] ?? null,
    //                 ] : null,
    //                 'VendorId' => $vendorId ? (string) $vendorId : null,
    //                 'VendorName' => $vendorName ?? null,
    //                 'LinkedTxn' => $extractLinkedTxn($raw),
    //             ];
    //         })->values();

    //         // diagnostics pre-checks
    //         $diag = [
    //             'purchases_count' => count($rawPurchases),
    //             'raw_payments_count' => $allRawPayments->count(),
    //             'normalized_payments_count' => $normalizedPayments->count(),
    //             'normalized_payments_with_linkedtxn' => $normalizedPayments->filter(fn($p) => !empty($p['LinkedTxn']))->count(),
    //             'sample_normalized_payment' => $normalizedPayments->first() ? [
    //                 'PaymentId' => $normalizedPayments->first()['PaymentId'] ?? null,
    //                 'VendorId' => $normalizedPayments->first()['VendorId'] ?? null,
    //                 'TotalAmount' => $normalizedPayments->first()['TotalAmount'] ?? null,
    //                 'LinkedTxn' => $normalizedPayments->first()['LinkedTxn'] ?? [],
    //             ] : null,
    //         ];

    //         // --- 4) Normalize purchases (expenses) ---
    //         $expenses = collect($rawPurchases)->map(function ($purchase) {
    //             $expenseAccounts = collect($purchase['Line'] ?? [])->map(function ($line) {
    //                 if (!empty($line['AccountBasedExpenseLineDetail']['AccountRef'])) {
    //                     $acct = $line['AccountBasedExpenseLineDetail']['AccountRef'];
    //                 } elseif (!empty($line['AccountRef'])) {
    //                     $acct = $line['AccountRef'];
    //                 } else {
    //                     return null;
    //                 }
    //                 return [
    //                     'Id' => $acct['value'] ?? null,
    //                     'Name' => $acct['name'] ?? null,
    //                     'Amount' => $line['Amount'] ?? 0,
    //                     'Description' => $line['Description'] ?? null,
    //                 ];
    //             })->filter()->values()->toArray();

    //             $mainAccount = null;
    //             if (!empty($purchase['AccountRef'])) {
    //                 $mainAccount = [
    //                     'Id' => $purchase['AccountRef']['value'] ?? null,
    //                     'Name' => $purchase['AccountRef']['name'] ?? null,
    //                 ];
    //             }

    //             return [
    //                 'ExpenseId' => $purchase['Id'] ?? null,
    //                 'VendorName' => $purchase['VendorRef']['name'] ?? ($purchase['EntityRef']['name'] ?? null),
    //                 'VendorId' => $purchase['VendorRef']['value'] ?? ($purchase['EntityRef']['value'] ?? null),
    //                 'TxnDate' => $purchase['TxnDate'] ?? null,
    //                 'TotalAmount' => (float) ($purchase['TotalAmt'] ?? ($purchase['Amount'] ?? 0)),
    //                 'Currency' => $purchase['CurrencyRef']['name'] ?? null,
    //                 'Memo' => $purchase['Memo'] ?? null,
    //                 'MainAccount' => $mainAccount,
    //                 'ExpenseAccounts' => $expenseAccounts,
    //                 'Payments' => [],
    //             ];
    //         });

    //         // --- 5) Link payments to expenses (explicit LinkedTxn) + fuzzy fallback ---
    //         $expensesWithPayments = $expenses->map(function ($exp) use ($normalizedPayments, $useFuzzyFallback, $fuzzyDateWindowDays, $fuzzyAmountTolerance) {
    //             // exact matches by LinkedTxn
    //             $linkedExact = $normalizedPayments->filter(function ($p) use ($exp) {
    //                 if (empty($p['LinkedTxn']))
    //                     return false;
    //                 return collect($p['LinkedTxn'])->contains(function ($txn) use ($exp) {
    //                     if (empty($txn['TxnId']))
    //                         return false;
    //                     // match by TxnId (type may vary or be null) — string compare
    //                     return (string) $txn['TxnId'] === (string) $exp['ExpenseId'];
    //                 });
    //             })->values();

    //             // If no explicit linked payments and fuzzy fallback enabled, perform vendor+amount+date heuristic
    //             if ($linkedExact->isEmpty() && $useFuzzyFallback) {
    //                 $expDate = $exp['TxnDate'] ? strtotime($exp['TxnDate']) : null;
    //                 $linkedFuzzy = $normalizedPayments->filter(function ($p) use ($exp, $expDate, $fuzzyDateWindowDays, $fuzzyAmountTolerance) {
    //                     // vendor must match if present
    //                     if (!empty($exp['VendorId']) && !empty($p['VendorId']) && (string) $exp['VendorId'] !== (string) $p['VendorId']) {
    //                         return false;
    //                     }
    //                     // amount must be similar within tolerance
    //                     if ($p['TotalAmount'] === null)
    //                         return false;
    //                     if (abs($p['TotalAmount'] - $exp['TotalAmount']) > $fuzzyAmountTolerance) {
    //                         return false;
    //                     }
    //                     // if both have dates, require within date window
    //                     if ($expDate && !empty($p['TxnDate'])) {
    //                         $pDate = strtotime($p['TxnDate']);
    //                         $deltaDays = abs(($pDate - $expDate) / 86400);
    //                         if ($deltaDays > $fuzzyDateWindowDays)
    //                             return false;
    //                     }
    //                     return true;
    //                 })->values();

    //                 // prefer exact (none here), otherwise use fuzzy set
    //                 $finalLinked = $linkedFuzzy;
    //             } else {
    //                 $finalLinked = $linkedExact;
    //             }

    //             $exp['Payments'] = $finalLinked;
    //             return $exp;
    //         });

    //         // --- 6) Add diagnostics about matches ---
    //         $diag['expenses_count'] = $expenses->count();
    //         $diag['expenses_with_any_payment'] = $expensesWithPayments->filter(fn($e) => !empty($e['Payments']))->count();
    //         $diag['example_expense_with_payment'] = $expensesWithPayments->first(function ($e) {
    //             return !empty($e['Payments']);
    //         });

    //         // --- 7) Return response (includes diagnostics) ---
    //         return response()->json([
    //             'status' => 'success',
    //             'count' => $expensesWithPayments->count(),
    //             'data' => $expensesWithPayments->values(),
    //             'single' => collect($expensesWithPayments->values())->first(),
    //             'diagnostics' => $diag,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage(),
    //         ]);
    //     }
    // }
    public function expensesWithPayments()
    {
        try {
            // --- CONFIG ---
            $typesToQuery = [
                'Payment',
                'Check',
                'BillPayment',
                'CreditCardCredit',
                'VendorCredit',
                'Deposit',
                'Purchase',
            ];

            $useFuzzyFallback = true;
            $fuzzyDateWindowDays = 7;
            $fuzzyAmountTolerance = 0.5;

            // --- 1) Fetch purchases (expenses) ---
            $purchasesResp = $this->runQuery("SELECT * FROM Purchase");
            $rawPurchases = $purchasesResp['QueryResponse']['Purchase'] ?? [];

            // --- 2) Fetch items and accounts for product mapping ---
            $itemsRaw = $this->runQuery("SELECT * FROM Item STARTPOSITION 1 MAXRESULTS 500");
            $accountsRaw = $this->runQuery("SELECT * FROM Account STARTPOSITION 1 MAXRESULTS 500");

            $itemsList = collect($itemsRaw['QueryResponse']['Item'] ?? []);
            $accountsList = collect($accountsRaw['QueryResponse']['Account'] ?? []);

            $itemsMap = $itemsList->keyBy(fn($it) => $it['Id'] ?? null)->toArray();
            $accountsMap = $accountsList->keyBy(fn($a) => $a['Id'] ?? null)->toArray();

            // --- 3) Fetch all payment-like types and merge ---
            $allRawPayments = collect();
            foreach ($typesToQuery as $type) {
                try {
                    $resp = $this->runQuery("SELECT * FROM {$type}");
                    $items = $resp['QueryResponse'][$type] ?? [];
                    $allRawPayments = $allRawPayments->merge(collect($items));
                } catch (\Exception $e) {
                    \Log::warning("Failed to fetch {$type}: " . $e->getMessage());
                }
            }

            // --- Helper: Extract & normalize LinkedTxn entries ---
            $extractLinkedTxn = function ($raw) {
                $linked = [];

                if (!empty($raw['LinkedTxn']) && is_array($raw['LinkedTxn'])) {
                    $linked = array_merge($linked, $raw['LinkedTxn']);
                }

                if (!empty($raw['Line']) && is_array($raw['Line'])) {
                    $fromLines = collect($raw['Line'])
                        ->pluck('LinkedTxn')
                        ->flatten(1)
                        ->filter()
                        ->values()
                        ->toArray();
                    $linked = array_merge($linked, $fromLines);
                }

                if (!empty($raw['Apply']) && is_array($raw['Apply'])) {
                    $linked = array_merge($linked, $raw['Apply']);
                }
                if (!empty($raw['AppliedToTxn']) && is_array($raw['AppliedToTxn'])) {
                    $linked = array_merge($linked, $raw['AppliedToTxn']);
                }

                if (isset($raw['TxnId']) && isset($raw['TxnType'])) {
                    $linked[] = ['TxnId' => $raw['TxnId'], 'TxnType' => $raw['TxnType']];
                }

                $normalized = [];
                foreach ($linked as $l) {
                    if (!is_array($l))
                        continue;

                    $txnId = $l['TxnId'] ?? $l['Id'] ?? $l['AppliedToTxnId'] ?? null;
                    $txnType = $l['TxnType'] ?? $l['TxnTypeName'] ?? $l['Type'] ?? null;

                    if ($txnId !== null) {
                        $normalized[] = [
                            'TxnId' => (string) $txnId,
                            'TxnType' => $txnType ? (string) $txnType : null,
                        ];
                    }
                }

                $unique = [];
                foreach ($normalized as $n) {
                    $key = ($n['TxnId'] ?? '') . '|' . ($n['TxnType'] ?? '');
                    if (!isset($unique[$key]))
                        $unique[$key] = $n;
                }

                return array_values($unique);
            };

            // --- Helper: Detect payment account ---
            $detectPaymentAccount = function ($raw) {
                if (!empty($raw['CreditCardPayment']['CCAccountRef']))
                    return $raw['CreditCardPayment']['CCAccountRef'];
                if (!empty($raw['CheckPayment']['BankAccountRef']))
                    return $raw['CheckPayment']['BankAccountRef'];
                if (!empty($raw['BankAccountRef']))
                    return $raw['BankAccountRef'];
                if (!empty($raw['PayFromAccountRef']))
                    return $raw['PayFromAccountRef'];
                if (!empty($raw['DepositToAccountRef']))
                    return $raw['DepositToAccountRef'];
                if (!empty($raw['CCAccountRef']))
                    return $raw['CCAccountRef'];
                if (!empty($raw['AccountRef']))
                    return $raw['AccountRef'];
                return null;
            };

            // --- Helper: Parse expense lines (products & accounts) ---
            $parseExpenseLine = function ($line) use ($itemsMap, $accountsMap) {
                $out = [];

                if (!empty($line['ItemBasedExpenseLineDetail'])) {
                    $sid = $line['ItemBasedExpenseLineDetail'];
                    $out[] = [
                        'DetailType' => $line['DetailType'] ?? 'ItemBasedExpenseLineDetail',
                        'Description' => $line['Description'] ?? ($sid['ItemRef']['name'] ?? null),
                        'Amount' => $line['Amount'] ?? 0,
                        'Quantity' => $sid['Qty'] ?? 1,
                        'ItemId' => $sid['ItemRef']['value'] ?? null,
                        'ItemName' => $sid['ItemRef']['name'] ?? null,
                        'AccountId' => null,
                        'AccountName' => null,
                        'RawLine' => $line,
                        'HasProduct' => true,
                    ];
                    return $out;
                }

                if (!empty($line['AccountBasedExpenseLineDetail'])) {
                    $accDetail = $line['AccountBasedExpenseLineDetail'];
                    $out[] = [
                        'DetailType' => $line['DetailType'] ?? 'AccountBasedExpenseLineDetail',
                        'Description' => $line['Description'] ?? null,
                        'Amount' => $line['Amount'] ?? 0,
                        'Quantity' => 1,
                        'ItemId' => null,
                        'ItemName' => null,
                        'AccountId' => $accDetail['AccountRef']['value'] ?? null,
                        'AccountName' => $accDetail['AccountRef']['name'] ?? null,
                        'RawLine' => $line,
                        'HasProduct' => false,
                    ];
                    return $out;
                }

                $out[] = [
                    'DetailType' => $line['DetailType'] ?? null,
                    'Description' => $line['Description'] ?? null,
                    'Amount' => $line['Amount'] ?? 0,
                    'Quantity' => 1,
                    'ItemId' => null,
                    'ItemName' => null,
                    'AccountId' => null,
                    'AccountName' => null,
                    'RawLine' => $line,
                    'HasProduct' => false,
                ];
                return $out;
            };

            // --- 4) Normalize all payments ---
            $normalizedPayments = $allRawPayments->map(function ($raw) use ($extractLinkedTxn, $detectPaymentAccount) {
                $vendorId = $raw['VendorRef']['value'] ?? $raw['EntityRef']['value'] ?? $raw['PayeeRef']['value'] ?? $raw['CustomerRef']['value'] ?? null;
                $vendorName = $raw['VendorRef']['name'] ?? $raw['EntityRef']['name'] ?? $raw['PayeeRef']['name'] ?? $raw['CustomerRef']['name'] ?? null;

                $paymentAccount = $detectPaymentAccount($raw);

                $total = $raw['TotalAmt'] ?? $raw['Amount'] ?? $raw['TotalAmount'] ?? null;

                return [
                    'Raw' => $raw,
                    'PaymentId' => $raw['Id'] ?? ($raw['PaymentId'] ?? null),
                    'TxnTypeRaw' => $raw['TxnType'] ?? null,
                    'TxnDate' => $raw['TxnDate'] ?? null,
                    'DocNumber' => $raw['DocNumber'] ?? null,
                    'TotalAmount' => $total !== null ? (float) $total : null,
                    'PaymentAccount' => $paymentAccount ? [
                        'Id' => $paymentAccount['value'] ?? null,
                        'Name' => $paymentAccount['name'] ?? null,
                    ] : null,
                    'VendorId' => $vendorId ? (string) $vendorId : null,
                    'VendorName' => $vendorName ?? null,
                    'LinkedTxn' => $extractLinkedTxn($raw),
                ];
            })->values();

            // --- 5) Normalize purchases (expenses) ---
            $expenses = collect($rawPurchases)->map(function ($purchase) use ($parseExpenseLine) {
                $parsedLines = [];
                foreach ($purchase['Line'] ?? [] as $line) {
                    $parsedLines = array_merge($parsedLines, $parseExpenseLine($line));
                }

                return [
                    'ExpenseId' => $purchase['Id'] ?? null,
                    'VendorName' => $purchase['VendorRef']['name'] ?? ($purchase['EntityRef']['name'] ?? null),
                    'VendorId' => $purchase['VendorRef']['value'] ?? ($purchase['EntityRef']['value'] ?? null),
                    'TxnDate' => $purchase['TxnDate'] ?? null,
                    'TotalAmount' => (float) ($purchase['TotalAmt'] ?? ($purchase['Amount'] ?? 0)),
                    'Currency' => $purchase['CurrencyRef']['name'] ?? null,
                    'Memo' => $purchase['Memo'] ?? null,
                    'ParsedLines' => $parsedLines,
                    'Payments' => [],
                    'RawExpense' => $purchase,
                ];
            });

            // --- 6) Link payments to expenses ---
            $expensesWithPayments = $expenses->map(function ($exp) use ($normalizedPayments, $useFuzzyFallback, $fuzzyDateWindowDays, $fuzzyAmountTolerance) {
                $linkedExact = $normalizedPayments->filter(function ($p) use ($exp) {
                    if (empty($p['LinkedTxn']))
                        return false;
                    return collect($p['LinkedTxn'])->contains(function ($txn) use ($exp) {
                        if (empty($txn['TxnId']))
                            return false;
                        return (string) $txn['TxnId'] === (string) $exp['ExpenseId'];
                    });
                })->values();

                if ($linkedExact->isEmpty() && $useFuzzyFallback) {
                    $expDate = $exp['TxnDate'] ? strtotime($exp['TxnDate']) : null;
                    $linkedFuzzy = $normalizedPayments->filter(function ($p) use ($exp, $expDate, $fuzzyDateWindowDays, $fuzzyAmountTolerance) {
                        if (!empty($exp['VendorId']) && !empty($p['VendorId']) && (string) $exp['VendorId'] !== (string) $p['VendorId']) {
                            return false;
                        }
                        if ($p['TotalAmount'] === null)
                            return false;
                        if (abs($p['TotalAmount'] - $exp['TotalAmount']) > $fuzzyAmountTolerance) {
                            return false;
                        }
                        if ($expDate && !empty($p['TxnDate'])) {
                            $pDate = strtotime($p['TxnDate']);
                            $deltaDays = abs(($pDate - $expDate) / 86400);
                            if ($deltaDays > $fuzzyDateWindowDays)
                                return false;
                        }
                        return true;
                    })->values();

                    $finalLinked = $linkedFuzzy;
                } else {
                    $finalLinked = $linkedExact;
                }

                $exp['Payments'] = $finalLinked;
                return $exp;
            });

            return response()->json([
                'status' => 'success',
                'count' => $expensesWithPayments->count(),
                'data' => $expensesWithPayments->values(),
                'single' => collect($expensesWithPayments->values())->first(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
    // public function invoicesWithPayments()
    // {
    //     try {
    //         // -----------------------
    //         // 1) Fetch core data
    //         // -----------------------
    //         $invoicesRaw = $this->runQuery("SELECT * FROM Invoice");
    //         $paymentsRaw = $this->runQuery("SELECT * FROM Payment");
    //         $itemsRaw = $this->runQuery("SELECT * FROM Item");
    //         $accountsRaw = $this->runQuery("SELECT * FROM Account");

    //         $invoicesList = collect($invoicesRaw['QueryResponse']['Invoice'] ?? []);
    //         $paymentsList = collect($paymentsRaw['QueryResponse']['Payment'] ?? []);
    //         $itemsList = collect($itemsRaw['QueryResponse']['Item'] ?? []);
    //         $accountsList = collect($accountsRaw['QueryResponse']['Account'] ?? []);

    //         $itemsMap = $itemsList->keyBy(fn($it) => $it['Id'] ?? null)->toArray();
    //         $accountsMap = $accountsList->keyBy(fn($a) => $a['Id'] ?? null)->toArray();

    //         // -----------------------
    //         // helpers
    //         // -----------------------
    //         $findARAccount = function () use ($accountsList) {
    //             $ar = $accountsList->first(fn($a) => isset($a['AccountType']) && strcasecmp($a['AccountType'], 'AccountsReceivable') === 0);
    //             if ($ar)
    //                 return ['Id' => $ar['Id'], 'Name' => $ar['Name'] ?? null];
    //             $ar = $accountsList->first(fn($a) => stripos($a['Name'] ?? '', 'receivable') !== false);
    //             return $ar ? ['Id' => $ar['Id'], 'Name' => $ar['Name'] ?? null] : null;
    //         };
    //         $findTaxPayableAccount = function () use ($accountsList) {
    //             $found = $accountsList->first(function ($a) {
    //                 if (isset($a['AccountType']) && strcasecmp($a['AccountType'], 'OtherCurrentLiability') === 0) {
    //                     return (stripos($a['Name'] ?? '', 'tax') !== false) || (stripos($a['Name'] ?? '', 'payable') !== false);
    //                 }
    //                 return false;
    //             });
    //             if ($found)
    //                 return ['Id' => $found['Id'], 'Name' => $found['Name'] ?? null];
    //             $found = $accountsList->first(fn($a) => stripos($a['Name'] ?? '', 'tax') !== false);
    //             return $found ? ['Id' => $found['Id'], 'Name' => $found['Name'] ?? null] : null;
    //         };

    //         $arAccount = $findARAccount();
    //         $taxAccount = $findTaxPayableAccount();

    //         // A small helper to detect the account for a sales-item line
    //         $detectAccountForSalesItem = function ($sid) use ($itemsMap, $accountsMap) {
    //             // sid = SalesItemLineDetail
    //             if (!empty($sid['ItemAccountRef']['value'])) {
    //                 return [
    //                     'AccountId' => $sid['ItemAccountRef']['value'],
    //                     'AccountName' => $sid['ItemAccountRef']['name'] ?? ($accountsMap[$sid['ItemAccountRef']['value']]['Name'] ?? null)
    //                 ];
    //             }
    //             if (!empty($sid['ItemRef']['value'])) {
    //                 $itemId = $sid['ItemRef']['value'];
    //                 $item = $itemsMap[$itemId] ?? null;
    //                 if ($item) {
    //                     if (!empty($item['IncomeAccountRef']['value'])) {
    //                         return ['AccountId' => $item['IncomeAccountRef']['value'], 'AccountName' => $item['IncomeAccountRef']['name'] ?? ($accountsMap[$item['IncomeAccountRef']['value']]['Name'] ?? null)];
    //                     }
    //                     if (!empty($item['ExpenseAccountRef']['value'])) {
    //                         return ['AccountId' => $item['ExpenseAccountRef']['value'], 'AccountName' => $item['ExpenseAccountRef']['name'] ?? ($accountsMap[$item['ExpenseAccountRef']['value']]['Name'] ?? null)];
    //                     }
    //                     if (!empty($item['AssetAccountRef']['value'])) {
    //                         return ['AccountId' => $item['AssetAccountRef']['value'], 'AccountName' => $item['AssetAccountRef']['name'] ?? ($accountsMap[$item['AssetAccountRef']['value']]['Name'] ?? null)];
    //                     }
    //                 }
    //             }
    //             return ['AccountId' => null, 'AccountName' => null];
    //         };

    //         // Parse one invoice line (handles GroupLine by expanding children).
    //         $parseInvoiceLine = function ($line) use ($detectAccountForSalesItem, $itemsMap, $accountsMap) {
    //             $out = [];
    //             $detailType = $line['DetailType'] ?? null;

    //             // Expand GroupLine children (if present). This prevents having a summary line and also child lines counted twice.
    //             if (!empty($line['GroupLineDetail']) && !empty($line['GroupLineDetail']['Line'])) {
    //                 foreach ($line['GroupLineDetail']['Line'] as $child) {
    //                     // recursively parse each child (but avoid infinite recursion by not re-expanding groups in child)
    //                     if (!empty($child['SalesItemLineDetail'])) {
    //                         $sid = $child['SalesItemLineDetail'];
    //                         $acc = $detectAccountForSalesItem($sid);
    //                         $out[] = [
    //                             'DetailType' => $child['DetailType'] ?? 'SalesItemLineDetail',
    //                             'Description' => $child['Description'] ?? $sid['ItemRef']['name'] ?? null,
    //                             'Amount' => $child['Amount'] ?? 0,
    //                             'AccountId' => $acc['AccountId'],
    //                             'AccountName' => $acc['AccountName'],
    //                             'RawLine' => $child,
    //                         ];
    //                     } else {
    //                         // for non-sales child lines, attempt to capture amount but leave account null (we'll surface these as unmapped)
    //                         $out[] = [
    //                             'DetailType' => $child['DetailType'] ?? null,
    //                             'Description' => $child['Description'] ?? null,
    //                             'Amount' => $child['Amount'] ?? 0,
    //                             'AccountId' => null,
    //                             'AccountName' => null,
    //                             'RawLine' => $child,
    //                         ];
    //                     }
    //                 }
    //                 return $out;
    //             }

    //             // Normal single line
    //             if (!empty($line['SalesItemLineDetail'])) {
    //                 $sid = $line['SalesItemLineDetail'];
    //                 $acc = $detectAccountForSalesItem($sid);
    //                 $out[] = [
    //                     'DetailType' => $line['DetailType'] ?? 'SalesItemLineDetail',
    //                     'Description' => $line['Description'] ?? ($sid['ItemRef']['name'] ?? null),
    //                     'Amount' => $line['Amount'] ?? 0,
    //                     'AccountId' => $acc['AccountId'],
    //                     'AccountName' => $acc['AccountName'],
    //                     'RawLine' => $line,
    //                 ];
    //                 return $out;
    //             }

    //             // TaxLine -> handled separately by TaxTotal; still return it so we can notice unmapped tax lines if present
    //             if (!empty($line['TaxLineDetail']) || stripos($detailType ?? '', 'Tax') !== false) {
    //                 $out[] = [
    //                     'DetailType' => $detailType,
    //                     'Description' => $line['Description'] ?? null,
    //                     'Amount' => $line['Amount'] ?? 0,
    //                     'AccountId' => null,
    //                     'AccountName' => null,
    //                     'RawLine' => $line,
    //                 ];
    //                 return $out;
    //             }

    //             // Subtotal/Description/Other lines -> return as unmapped to avoid double counting
    //             $out[] = [
    //                 'DetailType' => $detailType,
    //                 'Description' => $line['Description'] ?? null,
    //                 'Amount' => $line['Amount'] ?? 0,
    //                 'AccountId' => null,
    //                 'AccountName' => null,
    //                 'RawLine' => $line,
    //             ];
    //             return $out;
    //         };

    //         // -----------------------
    //         // 2) Build invoice objects + invoice-line-level parsed lines
    //         // -----------------------
    //         $invoices = $invoicesList->map(function ($invoice) use ($parseInvoiceLine, $accountsMap, $arAccount, $taxAccount) {
    //             $parsedLines = [];
    //             foreach ($invoice['Line'] ?? [] as $line) {
    //                 $parsedLines = array_merge($parsedLines, $parseInvoiceLine($line));
    //             }

    //             // collect unmapped (AccountId === null) separately
    //             $unmapped = array_values(array_filter($parsedLines, fn($l) => empty($l['AccountId']) && (float) $l['Amount'] != 0.0));

    //             // Invoice tax detection (TxnTaxDetail or TotalTax)
    //             $taxTotal = 0;
    //             if (!empty($invoice['TxnTaxDetail']['TotalTax']))
    //                 $taxTotal = $invoice['TxnTaxDetail']['TotalTax'];
    //             elseif (!empty($invoice['TotalTax']))
    //                 $taxTotal = $invoice['TotalTax'];

    //             $totalAmount = (float) ($invoice['TotalAmt'] ?? 0);

    //             // Build reconstructed journal from invoice lines BUT only include lines with detected accountId (avoid double-counting)
    //             $journalLines = [];

    //             // Debit AR (invoice total)
    //             if ($arAccount) {
    //                 $journalLines[] = [
    //                     'AccountId' => $arAccount['Id'],
    //                     'AccountName' => $arAccount['Name'],
    //                     'Debit' => $totalAmount,
    //                     'Credit' => 0.0,
    //                     'Note' => 'Accounts Receivable (invoice total)'
    //                 ];
    //             } else {
    //                 $journalLines[] = [
    //                     'AccountId' => null,
    //                     'AccountName' => 'Accounts Receivable (not found)',
    //                     'Debit' => $totalAmount,
    //                     'Credit' => 0.0,
    //                     'Note' => 'Accounts Receivable (invoice total, account not auto-detected)'
    //                 ];
    //             }

    //             // Credit per parsed line only if AccountId is present (this prevents adding subtotal/group duplicates)
    //             foreach ($parsedLines as $pl) {
    //                 if ((float) $pl['Amount'] == 0.0)
    //                     continue;
    //                 if (empty($pl['AccountId']))
    //                     continue; // skip unmapped lines here
    //                 $journalLines[] = [
    //                     'AccountId' => $pl['AccountId'],
    //                     'AccountName' => $pl['AccountName'] ?? null,
    //                     'Debit' => 0.0,
    //                     'Credit' => (float) $pl['Amount'],
    //                     'Note' => $pl['Description'] ?? 'Sales / line item'
    //                 ];
    //             }

    //             // Tax payable (heuristic) — keep as separate credit so total credits + tax = AR debit
    //             if ($taxTotal > 0) {
    //                 $journalLines[] = [
    //                     'AccountId' => $taxAccount['Id'] ?? null,
    //                     'AccountName' => $taxAccount['Name'] ?? 'Sales Tax Payable (heuristic)',
    //                     'Debit' => 0.0,
    //                     'Credit' => (float) $taxTotal,
    //                     'Note' => 'Sales/Tax payable'
    //                 ];
    //             }

    //             $sumDebits = array_sum(array_map(fn($l) => $l['Debit'] ?? 0, $journalLines));
    //             $sumCredits = array_sum(array_map(fn($l) => $l['Credit'] ?? 0, $journalLines));
    //             $balanced = abs($sumDebits - $sumCredits) < 0.01;

    //             return [
    //                 'InvoiceId' => (string) ($invoice['Id'] ?? null),
    //                 'Id' => $invoice['Id'] ?? null,
    //                 'DocNumber' => $invoice['DocNumber'] ?? null,
    //                 'CustomerName' => $invoice['CustomerRef']['name'] ?? null,
    //                 'CustomerId' => $invoice['CustomerRef']['value'] ?? null,
    //                 'TxnDate' => $invoice['TxnDate'] ?? null,
    //                 'DueDate' => $invoice['DueDate'] ?? null,
    //                 'TotalAmount' => $totalAmount,
    //                 'Balance' => $invoice['Balance'] ?? 0,
    //                 'Currency' => $invoice['CurrencyRef']['name'] ?? null,
    //                 'Payments' => [],
    //                 'ParsedLines' => $parsedLines,
    //                 'UnmappedInvoiceLines' => $unmapped,
    //                 'TaxTotal' => (float) $taxTotal,
    //                 'ReconstructedJournal' => [
    //                     'Source' => 'InvoiceLines',
    //                     'Lines' => $journalLines,
    //                     'SumDebits' => (float) $sumDebits,
    //                     'SumCredits' => (float) $sumCredits,
    //                     'Balanced' => $balanced,
    //                 ],
    //                 'RawInvoice' => $invoice,
    //             ];
    //         });

    //         // -----------------------
    //         // 3) Normalize payments and attach them to invoices
    //         // -----------------------
    //         $payments = $paymentsList->map(function ($payment) {
    //             $linked = [];
    //             foreach ($payment['Line'] ?? [] as $l) {
    //                 if (!empty($l['LinkedTxn'])) {
    //                     if (isset($l['LinkedTxn'][0]))
    //                         $linked = array_merge($linked, $l['LinkedTxn']);
    //                     else
    //                         $linked[] = $l['LinkedTxn'];
    //                 }
    //             }
    //             return [
    //                 'PaymentId' => $payment['Id'] ?? null,
    //                 'CustomerId' => $payment['CustomerRef']['value'] ?? null,
    //                 'CustomerName' => $payment['CustomerRef']['name'] ?? null,
    //                 'TxnDate' => $payment['TxnDate'] ?? null,
    //                 'TotalAmount' => $payment['TotalAmt'] ?? 0,
    //                 'PaymentMethod' => $payment['PaymentMethodRef']['name'] ?? null,
    //                 'LinkedTxn' => $linked,
    //                 'RawPayment' => $payment,
    //             ];
    //         });

    //         $invoicesById = $invoices->keyBy('InvoiceId')->toArray();
    //         foreach ($invoicesById as $invId => &$inv) {
    //             $inv['Payments'] = collect($payments)->filter(function ($p) use ($invId) {
    //                 return collect($p['LinkedTxn'])->contains(fn($txn) => isset($txn['TxnType'], $txn['TxnId']) && strcasecmp($txn['TxnType'], 'Invoice') === 0 && (string) $txn['TxnId'] === (string) $invId);
    //             })->values()->toArray();
    //         }
    //         $invoicesWithPayments = collect($invoicesById);

    //         // -----------------------
    //         // 4) Fetch JournalEntries (paginated) for invoice date range
    //         // -----------------------
    //         $txnDates = $invoicesList->pluck('TxnDate')->filter()->values();
    //         if ($txnDates->isEmpty()) {
    //             $minDate = date('Y-m-d', strtotime('-90 days'));
    //             $maxDate = date('Y-m-d');
    //         } else {
    //             $minDate = $txnDates->min();
    //             $maxDate = $txnDates->max();
    //         }

    //         $startPosition = 1;
    //         $maxResults = 1000;
    //         $allJournalEntries = [];
    //         while (true) {
    //             $q = "SELECT * FROM JournalEntry WHERE TxnDate >= '{$minDate}' AND TxnDate <= '{$maxDate}' STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
    //             $jesRaw = $this->runQuery($q);
    //             $jesPage = $jesRaw['QueryResponse']['JournalEntry'] ?? [];
    //             if (empty($jesPage))
    //                 break;
    //             foreach ($jesPage as $je)
    //                 $allJournalEntries[] = $je;
    //             if (count($jesPage) < $maxResults)
    //                 break;
    //             $startPosition += $maxResults;
    //         }

    //         // Index JEs by any LinkedTxn.TxnId found (may be DocNumber or Id)
    //         $jeByLinkedTxn = [];
    //         foreach ($allJournalEntries as $je) {
    //             foreach ($je['Line'] ?? [] as $line) {
    //                 $linked = $line['LinkedTxn'] ?? $line['JournalEntryLineDetail']['LinkedTxn'] ?? null;
    //                 if (empty($linked))
    //                     continue;
    //                 if (isset($linked['TxnId']) && isset($linked['TxnType']))
    //                     $linked = [$linked];
    //                 foreach ($linked as $lt) {
    //                     if (isset($lt['TxnType'], $lt['TxnId']) && strcasecmp($lt['TxnType'], 'Invoice') === 0) {
    //                         $key = (string) $lt['TxnId'];
    //                         if (!isset($jeByLinkedTxn[$key]))
    //                             $jeByLinkedTxn[$key] = [];
    //                         $jeByLinkedTxn[$key][] = $je;
    //                     }
    //                 }
    //             }
    //         }

    //         // -----------------------
    //         // 5) For invoices with no explicit LinkedTxn, attempt heuristic: match JE with AR line = invoice total ± tolerance, same customer (if present), date ±1 day
    //         // -----------------------
    //         $tolerance = 0.01;
    //         foreach ($invoicesWithPayments as $invKey => &$invoice) {
    //             $invoiceId = (string) $invoice['InvoiceId'];
    //             $docNum = (string) ($invoice['DocNumber'] ?? '');
    //             $total = (float) $invoice['TotalAmount'];
    //             $invDate = $invoice['TxnDate'] ?? null;
    //             $custId = (string) ($invoice['CustomerId'] ?? '');

    //             // Try explicit linked txn (by Id or DocNumber)
    //             $matchedJEs = $jeByLinkedTxn[$invoiceId] ?? [];
    //             if (empty($matchedJEs) && $docNum !== '')
    //                 $matchedJEs = $jeByLinkedTxn[$docNum] ?? [];

    //             // Heuristic scan if none found
    //             if (empty($matchedJEs)) {
    //                 foreach ($allJournalEntries as $je) {
    //                     // date within +/-1 day
    //                     if ($invDate && !empty($je['TxnDate'])) {
    //                         if (abs(strtotime($je['TxnDate']) - strtotime($invDate)) > 86400)
    //                             continue;
    //                     }
    //                     // customer match if JE has CustomerRef
    //                     if (!empty($je['CustomerRef']['value']) && $custId !== '') {
    //                         if ((string) $je['CustomerRef']['value'] !== $custId)
    //                             continue;
    //                     }

    //                     // find AR line in JE with amount ~= total
    //                     $hasMatchingAR = false;
    //                     foreach ($je['Line'] ?? [] as $jl) {
    //                         $acctId = $jl['AccountRef']['value'] ?? ($jl['JournalEntryLineDetail']['AccountRef']['value'] ?? null);
    //                         $amount = isset($jl['Amount']) ? (float) $jl['Amount'] : 0.0;
    //                         $postingType = $jl['JournalEntryLineDetail']['PostingType'] ?? null;

    //                         if ($arAccount && $acctId && (string) $acctId === (string) $arAccount['Id']) {
    //                             if ($postingType && strcasecmp($postingType, 'Debit') === 0 && abs($amount - $total) <= $tolerance) {
    //                                 $hasMatchingAR = true;
    //                                 break;
    //                             }
    //                             if ($postingType === null && abs($amount - $total) <= $tolerance) {
    //                                 $hasMatchingAR = true;
    //                                 break;
    //                             }
    //                         } else {
    //                             // check accountsMap for AR-typed acct
    //                             if ($acctId && isset($accountsMap[$acctId]) && isset($accountsMap[$acctId]['AccountType']) && strcasecmp($accountsMap[$acctId]['AccountType'], 'AccountsReceivable') === 0) {
    //                                 if ($postingType && strcasecmp($postingType, 'Debit') === 0 && abs($amount - $total) <= $tolerance) {
    //                                     $hasMatchingAR = true;
    //                                     break;
    //                                 }
    //                                 if ($postingType === null && abs($amount - $total) <= $tolerance) {
    //                                     $hasMatchingAR = true;
    //                                     break;
    //                                 }
    //                             }
    //                         }
    //                     }

    //                     if ($hasMatchingAR) {
    //                         $matchedJEs[] = $je;
    //                         break; // use first match (can be changed to collect multiple)
    //                     }
    //                 }
    //             }

    //             // If matched JEs found, parse the JE lines into authoritative ReconstructedJournal
    //             $invoice['LinkedJournalEntries'] = [];
    //             $invoice['HasLinkedJournalEntry'] = !empty($matchedJEs);

    //             if (!empty($matchedJEs)) {
    //                 // compact summaries
    //                 $invoice['LinkedJournalEntries'] = array_map(fn($je) => [
    //                     'JournalEntryId' => $je['Id'] ?? null,
    //                     'TxnDate' => $je['TxnDate'] ?? null,
    //                     'TotalAmt' => $je['TotalAmt'] ?? null,
    //                     'RawJournalEntry' => $je,
    //                 ], $matchedJEs);

    //                 // parse lines
    //                 $mergedJournalLines = [];
    //                 foreach ($matchedJEs as $je) {
    //                     foreach ($je['Line'] ?? [] as $jl) {
    //                         $amount = isset($jl['Amount']) ? (float) $jl['Amount'] : 0.0;
    //                         $acctId = $jl['AccountRef']['value'] ?? ($jl['JournalEntryLineDetail']['AccountRef']['value'] ?? null);
    //                         $acctName = $jl['AccountRef']['name'] ?? ($jl['JournalEntryLineDetail']['AccountRef']['name'] ?? ($accountsMap[$acctId]['Name'] ?? null));
    //                         $postingType = $jl['JournalEntryLineDetail']['PostingType'] ?? null;

    //                         $debit = 0.0;
    //                         $credit = 0.0;
    //                         if ($postingType) {
    //                             if (strcasecmp($postingType, 'Debit') === 0)
    //                                 $debit = $amount;
    //                             elseif (strcasecmp($postingType, 'Credit') === 0)
    //                                 $credit = $amount;
    //                         } else {
    //                             $acctInfo = $accountsMap[$acctId] ?? null;
    //                             if ($acctInfo && isset($acctInfo['AccountType']) && strcasecmp($acctInfo['AccountType'], 'AccountsReceivable') === 0)
    //                                 $debit = $amount;
    //                             else
    //                                 $credit = $amount;
    //                         }

    //                         $mergedJournalLines[] = [
    //                             'AccountId' => $acctId,
    //                             'AccountName' => $acctName,
    //                             'Debit' => $debit,
    //                             'Credit' => $credit,
    //                             'Note' => $jl['Description'] ?? ($jl['JournalEntryLineDetail']['Memo'] ?? null),
    //                         ];
    //                     }
    //                 }

    //                 $sumDebits = array_sum(array_map(fn($l) => $l['Debit'] ?? 0, $mergedJournalLines));
    //                 $sumCredits = array_sum(array_map(fn($l) => $l['Credit'] ?? 0, $mergedJournalLines));
    //                 $balanced = abs($sumDebits - $sumCredits) < 0.01;

    //                 // Replace invoice reconstructed journal with authoritative JE lines
    //                 $invoice['ReconstructedJournal'] = [
    //                     'Source' => 'JournalEntry',
    //                     'Lines' => $mergedJournalLines,
    //                     'SumDebits' => (float) $sumDebits,
    //                     'SumCredits' => (float) $sumCredits,
    //                     'Balanced' => $balanced,
    //                 ];
    //             }
    //             // else: keep InvoiceLines-based ReconstructedJournal (and UnmappedInvoiceLines will show lines we could not map)
    //         }

    //         // -----------------------
    //         // 6) Return
    //         // -----------------------
    //         // return response()->json([
    //         //     'status' => 'success',
    //         //     'count' => $invoicesWithPayments->count(),
    //         //     'data' => array_values($invoicesWithPayments),
    //         // ]);
    //         return $invoicesWithPayments;
    //         // dd($invoicesWithPayments->last(),$invoicesWithPayments->first(), $invoicesWithPayments);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage(),
    //         ]);
    //     }
    // }
    
public function invoicesWithPayments()
{
    try {
        $targetInvoiceId = '43038';

        // 1) Fetch the target invoice
        $invoiceResponse = $this->runQuery("SELECT * FROM Invoice WHERE Id = '{$targetInvoiceId}'");
        $invoiceRaw = $invoiceResponse['QueryResponse']['Invoice'][0] ?? null;
        if (!$invoiceRaw) {
            return response()->json([
                'status' => 'error',
                'message' => "Invoice {$targetInvoiceId} not found."
            ], 404);
        }

        // 2) Fetch all payments
        $allPayments = collect();
        $start = 1;
        $maxResults = 50;
        do {
            $response = $this->runQuery("SELECT * FROM Payment STARTPOSITION {$start} MAXRESULTS {$maxResults}");
            $paymentsData = $response['QueryResponse']['Payment'] ?? [];
            $allPayments = $allPayments->merge($paymentsData);
            $fetched = count($paymentsData);
            $start += $fetched;
        } while ($fetched === $maxResults);

        // 3) Gather all invoice IDs referenced by payments
        $allInvoiceIds = [$targetInvoiceId];
        foreach ($allPayments as $payment) {
            foreach ($payment['Line'] ?? [] as $line) {
                if (!empty($line['LinkedTxn'])) {
                    $linked = is_array($line['LinkedTxn'][0] ?? null) ? $line['LinkedTxn'] : [$line['LinkedTxn']];
                    foreach ($linked as $txn) {
                        if (($txn['TxnType'] ?? null) === 'Invoice') {
                            $allInvoiceIds[] = $txn['TxnId'];
                        }
                    }
                }
            }
        }
        $allInvoiceIds = array_unique($allInvoiceIds);

        // 4) Fetch all these invoices in a single query (batch)
        $invoicesMap = [];
        $batchSize = 30; // QuickBooks has a limit on IN (...) queries, so batch
        $chunks = array_chunk($allInvoiceIds, $batchSize);

        foreach ($chunks as $chunk) {
            $idsStr = implode(',', array_map(fn($id) => "'{$id}'", $chunk));
            $resp = $this->runQuery("SELECT * FROM Invoice WHERE Id IN ({$idsStr})");
            foreach ($resp['QueryResponse']['Invoice'] ?? [] as $inv) {
                $invoicesMap[$inv['Id']] = $inv;
            }
        }

        // 5) Normalize payments with full invoice details
        $normalizedPayments = $allPayments->map(function ($payment) use ($invoicesMap) {
            $linked = [];
            foreach ($payment['Line'] ?? [] as $line) {
                if (!empty($line['LinkedTxn'])) {
                    $linked = array_merge($linked, is_array($line['LinkedTxn'][0] ?? null) ? $line['LinkedTxn'] : [$line['LinkedTxn']]);
                }
            }

            $relatedInvoices = [];
            foreach ($linked as $txn) {
                $txnId = $txn['TxnId'] ?? null;
                if ($txnId && isset($invoicesMap[$txnId])) {
                    $relatedInvoices[] = $invoicesMap[$txnId];
                }
            }

            return [
                'PaymentId' => $payment['Id'] ?? null,
                'TxnDate' => $payment['TxnDate'] ?? null,
                'TotalAmount' => $payment['TotalAmt'] ?? 0,
                'PaymentMethod' => $payment['PaymentMethodRef']['name'] ?? null,
                'LinkedTxn' => $linked,
                'RelatedInvoices' => $relatedInvoices,
                'RawPayment' => $payment,
            ];
        });

        // 6) Attach payments relevant to target invoice
        $paymentsForInvoice = $normalizedPayments->filter(function ($p) use ($targetInvoiceId) {
            foreach ($p['RelatedInvoices'] as $inv) {
                if (($inv['Id'] ?? null) === $targetInvoiceId) return true;
            }
            return false;
        })->values()->toArray();

        // 7) Parse invoice lines
        $itemsRaw = $this->runQuery("SELECT * FROM Item STARTPOSITION 1 MAXRESULTS 500");
        $accountsRaw = $this->runQuery("SELECT * FROM Account STARTPOSITION 1 MAXRESULTS 500");
        $itemsMap = collect($itemsRaw['QueryResponse']['Item'] ?? [])->keyBy('Id')->toArray();
        $accountsMap = collect($accountsRaw['QueryResponse']['Account'] ?? [])->keyBy('Id')->toArray();

        $detectAccountForSalesItem = function ($sid) use ($itemsMap, $accountsMap) {
            if (!empty($sid['ItemAccountRef']['value'])) {
                return [
                    'AccountId' => $sid['ItemAccountRef']['value'],
                    'AccountName' => $sid['ItemAccountRef']['name'] ?? ($accountsMap[$sid['ItemAccountRef']['value']]['Name'] ?? null)
                ];
            }
            if (!empty($sid['ItemRef']['value'])) {
                $item = $itemsMap[$sid['ItemRef']['value']] ?? null;
                if ($item && !empty($item['IncomeAccountRef']['value'])) {
                    return [
                        'AccountId' => $item['IncomeAccountRef']['value'],
                        'AccountName' => $item['IncomeAccountRef']['name'] ?? ($accountsMap[$item['IncomeAccountRef']['value']]['Name'] ?? null)
                    ];
                }
            }
            return ['AccountId' => null, 'AccountName' => null];
        };

        $parseInvoiceLine = function ($line) use ($detectAccountForSalesItem) {
            $out = [];
            if (!empty($line['GroupLineDetail']['Line'])) {
                foreach ($line['GroupLineDetail']['Line'] as $child) {
                    if (!empty($child['SalesItemLineDetail'])) {
                        $sid = $child['SalesItemLineDetail'];
                        $acc = $detectAccountForSalesItem($sid);
                        $out[] = [
                            'Description' => $child['Description'] ?? $sid['ItemRef']['name'] ?? null,
                            'Amount' => $child['Amount'] ?? 0,
                            'Quantity' => $sid['Qty'] ?? 1,
                            'AccountId' => $acc['AccountId'],
                            'AccountName' => $acc['AccountName'],
                            'HasProduct' => true,
                            'RawLine' => $child,
                        ];
                    }
                }
                return $out;
            }

            if (!empty($line['SalesItemLineDetail'])) {
                $sid = $line['SalesItemLineDetail'];
                $acc = $detectAccountForSalesItem($sid);
                $out[] = [
                    'Description' => $line['Description'] ?? $sid['ItemRef']['name'] ?? null,
                    'Amount' => $line['Amount'] ?? 0,
                    'Quantity' => $sid['Qty'] ?? 1,
                    'AccountId' => $acc['AccountId'],
                    'AccountName' => $acc['AccountName'],
                    'HasProduct' => true,
                    'RawLine' => $line,
                ];
            }
            return $out;
        };

        $parsedLines = [];
        foreach ($invoiceRaw['Line'] ?? [] as $line) {
            $parsedLines = array_merge($parsedLines, $parseInvoiceLine($line));
        }

        return dd([
            'status' => 'success',
            'data' => [
                'Invoice' => $invoiceRaw,
                'ParsedLines' => $parsedLines,
                'Payments' => $paymentsForInvoice,
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}


    // public function items()
    // {
    //     try {
    //         $itemsResponse = $this->runQuery("SELECT * FROM Item STARTPOSITION 1 MAXRESULTS 200");

    //         // Check if the response is already a JsonResponse (error from runQuery)
    //         if ($itemsResponse instanceof \Illuminate\Http\JsonResponse) {
    //             return $itemsResponse; // just return the error response
    //         }

    //         // QuickBooks wraps results inside QueryResponse
    //         $itemsData = $itemsResponse['QueryResponse']['Item'] ?? [];

    //         $items = collect($itemsData)->map(function ($item) {
    //             return [
    //                 'ItemId' => $item['Id'] ?? null,
    //                 'Name' => $item['Name'] ?? null,
    //                 'Description' => $item['Description'] ?? null,
    //                 'Type' => $item['Type'] ?? null,
    //                 'IncomeAccount' => $item['IncomeAccountRef']['name'] ?? null,
    //                 'ExpenseAccount' => $item['ExpenseAccountRef']['name'] ?? null,
    //                 'AssetAccount' => $item['AssetAccountRef']['name'] ?? null,
    //                 'UnitPrice' => $item['UnitPrice'] ?? 0,
    //                 'QtyOnHand' => $item['QtyOnHand'] ?? 0,
    //                 'TrackQtyOnHand' => $item['TrackQtyOnHand'] ?? false,
    //             ];
    //         });

    //         return response()->json([
    //             'status' => 'success',
    //             'count' => $items->count(),
    //             'data' => $items->values(),
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage(),
    //         ]);
    //     }
    // }

public function items()
{
    try {
        $allItems = collect();
        $start = 1;
        $maxResults = 1000;   // API maximum
        $queryBase = "SELECT * FROM Item WHERE Active IN (true, false)";

        do {
            $query = "{$queryBase} STARTPOSITION {$start} MAXRESULTS {$maxResults}";
            $response = $this->runQuery($query);

            if ($response instanceof \Illuminate\Http\JsonResponse) {
                return $response;
            }

            $batch = $response['QueryResponse']['Item'] ?? [];
            $count = count($batch);

            $mapped = collect($batch)->map(function ($item) {
                return [
                    'ItemId'        => $item['Id'] ?? null,
                    'Name'          => $item['Name'] ?? null,
                    'Description'   => $item['Description'] ?? null,
                    'Type'          => $item['Type'] ?? null,
                    'Active'        => $item['Active'] ?? null,
                    'UnitPrice'     => isset($item['UnitPrice']) ? $item['UnitPrice'] : 0,
                    'QtyOnHand'     => isset($item['QtyOnHand']) ? $item['QtyOnHand'] : 0,
                    'TrackQtyOnHand'=> $item['TrackQtyOnHand'] ?? false,
                ];
            });

            $allItems = $allItems->merge($mapped);
            $start += $maxResults;
        } while ($count === $maxResults);  // keep fetching while full page returned

        \Log::info("QuickBooks import: total items fetched = {$allItems->count()}");

        return response()->json([
            'status' => 'success',
            'count'  => $allItems->count(),
            'data'   => $allItems->values(),
        ]);
    } catch (\Exception $e) {
        \Log::error("QuickBooks import error: " . $e->getMessage());

        return response()->json([
            'status'  => 'error',
            'message' => $e->getMessage(),
        ]);
    }
}





    // public function deposits()
    // {
    //     try {
    //         // 1️⃣ Fetch all deposits (up to 200 records)
    //         $depositResponse = $this->runQuery("SELECT * FROM Deposit STARTPOSITION 1 MAXRESULTS 200");

    //         // 2️⃣ Handle token/connection errors
    //         if ($depositResponse instanceof \Illuminate\Http\JsonResponse) {
    //             return $depositResponse; // early return if token expired or API error
    //         }

    //         // 3️⃣ Extract data safely from QuickBooks QueryResponse
    //         $depositsData = $depositResponse['QueryResponse']['Deposit'] ?? [];

    //         // 4️⃣ Map data into a clean, readable format
    //         $deposits = collect($depositsData)->map(function ($deposit) {
    //             return [
    //                 'DepositId' => $deposit['Id'] ?? null,
    //                 'TxnDate' => $deposit['TxnDate'] ?? null,
    //                 'TotalAmt' => $deposit['TotalAmt'] ?? 0,
    //                 'PrivateNote' => $deposit['PrivateNote'] ?? null,
    //                 'Currency' => $deposit['CurrencyRef']['name'] ?? null,
    //                 'DepositTo' => $deposit['DepositToAccountRef']['name'] ?? null,
    //                 'LineCount' => isset($deposit['Line']) ? count($deposit['Line']) : 0,
    //                 'Lines' => collect($deposit['Line'] ?? [])->map(function ($line) {
    //                     return [
    //                         'Amount' => $line['Amount'] ?? null,
    //                         'DetailType' => $line['DetailType'] ?? null,
    //                         'Entity' => $line['DepositLineDetail']['Entity']['name'] ?? null,
    //                         'Account' => $line['DepositLineDetail']['AccountRef']['name'] ?? null,
    //                         'PaymentMethod' => $line['DepositLineDetail']['PaymentMethodRef']['name'] ?? null,
    //                     ];
    //                 }),
    //             ];
    //         });

    //         // 5️⃣ Return formatted response
    //         return response()->json([
    //             'status' => 'success',
    //             'count' => $deposits->count(),
    //             'data' => $deposits->values(),
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function deposits()
    {
        try {
            // 1) Pull deposits (tweak MAXRESULTS / add WHERE if you like)
            $depositResponse = $this->runQuery("SELECT * FROM Deposit");

            if ($depositResponse instanceof \Illuminate\Http\JsonResponse) {
                return $depositResponse;
            }

            $depositsData = $depositResponse['QueryResponse']['Deposit'] ?? [];
            // dd($depositsData);
            // 2) Map into a rich, readable shape
            $deposits = collect($depositsData)->map(function ($deposit) {
                $lines = collect($deposit['Line'] ?? [])->map(function ($line) {
                    $detail   = $line['DepositLineDetail'] ?? [];
                    $entity   = $detail['Entity'] ?? [];
                    $entityRef = $entity['EntityRef'] ?? [];

                    // Linked transactions that this line is sourced from (your "voucher" links)
                    $linkedTxns = collect($detail['LinkedTxn'] ?? [])->map(function ($lt) {
                        return [
                            'TxnId'     => $lt['TxnId']     ?? null,
                            'TxnType'   => $lt['TxnType']   ?? null,  // e.g., Payment, SalesReceipt, RefundReceipt
                            'TxnLineId' => $lt['TxnLineId'] ?? null,
                        ];
                    })->values();

                    return [
                        'Amount'        => $line['Amount'] ?? null,
                        'DetailType'    => $line['DetailType'] ?? null,
                        'CustomerRef'   => [
                            'Id'   => $entityRef['value'] ?? null,
                            'Type' => $entity['type']     ?? null,   // expecting "Customer" for customer-linked lines
                            'Name' => $entity['name']     ?? null,
                        ],
                        'Account'       => $detail['AccountRef']['name'] ?? null,
                        'PaymentMethod' => $detail['PaymentMethodRef']['name'] ?? null,
                        'CheckNum'      => $detail['CheckNum'] ?? null,
                        'LinkedTxn'     => $linkedTxns,            // voucher links
                    ];
                });

                // unique customers involved in this deposit (top-level quick look)
                $customersInDeposit = $lines->pluck('CustomerRef')
                    ->filter(fn ($c) => ($c['Id'] ?? null) && strtolower($c['Type'] ?? '') == 'customer')
                    ->unique('Id')
                    ->values();

                return [
                    'DepositId'   => $deposit['Id'] ?? null,
                    'DocNumber'   => $deposit['DocNumber'] ?? null, // "voucher"/deposit number
                    'TxnDate'     => $deposit['TxnDate'] ?? null,
                    'TotalAmt'    => $deposit['TotalAmt'] ?? 0,
                    'PrivateNote' => $deposit['PrivateNote'] ?? null,
                    'Currency'    => $deposit['CurrencyRef']['name'] ?? null,
                    'DepositTo'   => $deposit['DepositToAccountRef']['name'] ?? null,
                    'LineCount'   => isset($deposit['Line']) ? count($deposit['Line']) : 0,

                    // all lines (full detail)
                    'Lines'       => $lines,

                    // quick summary: customers touched by this deposit
                    'Customers'   => $customersInDeposit,
                ];
            });

            // 3) If you ONLY want deposits that actually have customer-linked lines, filter here:
            $customerOnly = request()->boolean('customers_only', false);
            $filtered = $customerOnly
                ? $deposits->filter(fn ($d) => ($d['Customers']->count() ?? 0) > 0)->values()
                : $deposits->values();

            return response()->json([
                'status' => 'success',
                'count'  => $filtered->count(),
                'data'   => $filtered,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function depositsWithVoucher()
    {
        try {
            // 1️⃣ Fetch deposits (limit to 200 for now)
            $depositResponse = $this->runQuery("SELECT * FROM Deposit STARTPOSITION 1 MAXRESULTS 200");
            $deposits = $depositResponse['QueryResponse']['Deposit'] ?? [];

            // 2️⃣ Fetch all accounts for mapping
            $accountResponse = $this->runQuery("SELECT * FROM Account STARTPOSITION 1 MAXRESULTS 500");
            $accounts = collect($accountResponse['QueryResponse']['Account'] ?? [])->keyBy('Id');

            // 3️⃣ Determine earliest & latest deposit dates
            $dates = collect($deposits)->pluck('TxnDate')->filter()->sort();
            $startDate = $dates->first() ?? now()->subMonths(1)->format('Y-m-d');
            $endDate = $dates->last() ?? now()->format('Y-m-d');

            // 4️⃣ Fetch Journal Entries within that date range
            $journalResponse = $this->runQuery("SELECT * FROM JournalEntry WHERE TxnDate >= '$startDate' AND TxnDate <= '$endDate'");
            $journalEntries = collect($journalResponse['QueryResponse']['JournalEntry'] ?? []);

            // 5️⃣ Build combined vouchers
            $combined = collect($deposits)->map(function ($deposit) use ($accounts, $journalEntries) {
                $depositId = $deposit['Id'] ?? null;
                $txnDate = $deposit['TxnDate'] ?? null;
                $total = $deposit['TotalAmt'] ?? 0;

                // Find possible related journal entry (by date and amount)
                $relatedJE = $journalEntries->first(function ($je) use ($txnDate, $total) {
                    return ($je['TxnDate'] ?? null) === $txnDate && (float) ($je['TotalAmt'] ?? 0) === (float) $total;
                });

                // Build voucher lines (both sides)
                $voucherLines = collect($relatedJE['Line'] ?? [])->map(function ($line) use ($accounts) {
                    $accId = $line['JournalEntryLineDetail']['AccountRef']['value'] ?? null;
                    $account = $accounts[$accId] ?? null;
                    return [
                        'PostingType' => $line['JournalEntryLineDetail']['PostingType'] ?? null,
                        'Account' => [
                            'id' => $accId,
                            'name' => $account['Name'] ?? null,
                            'type' => $account['AccountType'] ?? null,
                        ],
                        'Amount' => $line['Amount'] ?? null,
                    ];
                });

                return [
                    'VoucherNo' => $depositId,
                    'TxnDate' => $txnDate,
                    'TotalAmt' => $total,
                    'PrivateNote' => $deposit['PrivateNote'] ?? null,
                    'DepositTo' => [
                        'id' => $deposit['DepositToAccountRef']['value'] ?? null,
                        'name' => $deposit['DepositToAccountRef']['name'] ?? null,
                    ],
                    'VoucherLines' => $voucherLines->isNotEmpty()
                        ? $voucherLines
                        : collect($deposit['Line'] ?? [])->map(function ($line) use ($accounts) {
                            $accId = $line['DepositLineDetail']['AccountRef']['value'] ?? null;
                            $account = $accounts[$accId] ?? null;
                            return [
                                'PostingType' => 'Credit', // Default for deposit line
                                'Account' => [
                                    'id' => $accId,
                                    'name' => $account['Name'] ?? null,
                                    'type' => $account['AccountType'] ?? null,
                                ],
                                'Amount' => $line['Amount'] ?? null,
                            ];
                        }),
                ];
            });

            // 6️⃣ Return response
            return response()->json([
                'status' => 'success',
                'count' => $combined->count(),
                'data' => $combined->values(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    // public function getSalesReceipts()
    // {
    //     try {
    //         $realmId = $this->realmId(); // Company ID (Realm ID)
    //         $baseUrl = $this->baseUrl;   // Base URL for environment (sandbox or production)

    //         // Construct the API URL for SalesReceipts
    //         $url = "{$baseUrl}/v3/company/{$realmId}/query?minorversion=75";
    //         $query = "SELECT * FROM SalesReceipt"; // Get all sales receipts

    //         $data = $this->runQuery($query);

    //         // Check if data exists
    //         if (isset($data['QueryResponse']['SalesReceipt'])) {
    //             $salesReceipts = collect($data['QueryResponse']['SalesReceipt'])->map(function ($receipt) {
    //                 return [
    //                     'Id' => $receipt['Id'] ?? null,
    //                     'DocNumber' => $receipt['DocNumber'] ?? null,
    //                     'TxnDate' => $receipt['TxnDate'] ?? null,
    //                     'CustomerRef' => $receipt['CustomerRef']['name'] ?? null,
    //                     'TotalAmt' => $receipt['TotalAmt'] ?? null,
    //                     'PrivateNote' => $receipt['PrivateNote'] ?? null,
    //                     'PaymentMethodRef' => $receipt['PaymentMethodRef']['name'] ?? null,
    //                     'DepositToAccountRef' => $receipt['DepositToAccountRef']['name'] ?? null,
    //                     'LineItems' => collect($receipt['Line'] ?? [])->map(function ($line) {
    //                         return [
    //                             'LineNum' => $line['LineNum'] ?? null,
    //                             'Description' => $line['Description'] ?? null,
    //                             'Amount' => $line['Amount'] ?? null,
    //                             'DetailType' => $line['DetailType'] ?? null,
    //                             'ItemRef' => $line['SalesItemLineDetail']['ItemRef']['name'] ?? null,
    //                             'Qty' => $line['SalesItemLineDetail']['Qty'] ?? null,
    //                             'UnitPrice' => $line['SalesItemLineDetail']['UnitPrice'] ?? null,
    //                         ];
    //                     }),
    //                 ];
    //             });

    //             return response()->json([
    //                 'status' => 'success',
    //                 'count' => $salesReceipts->count(),
    //                 'data' => $salesReceipts
    //             ]);
    //         }

    //         // Return empty data if no records
    //         return response()->json([
    //             'status' => 'success',
    //             'count' => 0,
    //             'data' => []
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }


public function getSalesReceipts(array $opts = [])
{
    try {
        // ----------- 0) Guard: connection
        $token = $this->accessToken();
        $realm = $this->realmId();
        if (!$token || !$realm) {
            return response()->json([
                'error'   => true,
                'message' => 'Missing QuickBooks connection. Please connect first.',
            ], 401);
        }

        // ----------- 1) Build WHERE & paging
        $startDate  = $opts['start_date']  ?? '2011-01-01'; // 'YYYY-MM-DD'
        $endDate    = $opts['end_date']    ?? '2025-11-10'; // 'YYYY-MM-DD'
        $customerId = $opts['customer_id'] ?? null;
        $pageSize   = max(1, min(1000, (int)($opts['page_size'] ?? 500)));
        $startPos   = 1;

        $where = [];
        if ($startDate)  $where[] = "TxnDate >= '{$startDate}'";
        if ($endDate)    $where[] = "TxnDate <= '{$endDate}'";
        if ($customerId) $where[] = "CustomerRef = '{$customerId}'";
        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        // ----------- 2) Page through SELECT * FROM SalesReceipt
        $allReceipts = collect();
        while (true) {
            $query = "SELECT * FROM SalesReceipt";

            $resp = $this->runQuery($query);
            if (!is_array($resp)) {
                // If your runQuery returns a JsonResponse on error, forward it.
                return $resp;
            }
            dd($resp);
            $chunk = collect($resp['QueryResponse']['SalesReceipt'] ?? []);
            $allReceipts = $allReceipts->concat($chunk);

            $returned = $chunk->count();
            if ($returned < $pageSize) break; // last page
            $startPos += $pageSize;
        }

        if ($allReceipts->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'count'  => 0,
                'data'   => [],
                'meta'   => [
                    'mode'       => 'query+expand',
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                    'page_size'  => $pageSize,
                ],
            ]);
        }

        // ----------- 3) Collect LinkedTxn by type → ids
        $byType = [];
        $linkTriples = []; // [receiptId => [ [TxnType, TxnId], ... ]]
        foreach ($allReceipts as $sr) {
            $rid = $sr['Id'] ?? null;
            $linkTriples[$rid] = [];
            foreach (($sr['LinkedTxn'] ?? []) as $lt) {
                $t = $lt['TxnType'] ?? null;
                $i = $lt['TxnId']   ?? null;
                if ($t && $i) {
                    $byType[$t] = $byType[$t] ?? [];
                    $byType[$t][] = (string)$i;
                    $linkTriples[$rid][] = [$t, (string)$i];
                }
            }
        }
        // Deduplicate per type
        foreach ($byType as $t => $ids) {
            $byType[$t] = array_values(array_unique($ids));
        }

        // ----------- 4) Batch-read referenced entities, by type (chunks of 25)
        $baseUrl = rtrim($this->baseUrl, '/');
        $batchUrl = "{$baseUrl}/v3/company/{$realm}/batch?minorversion=75";

        // Build a global lookup: [Type][Id] => Entity
        $entityLookup = [];
        foreach ($byType as $type => $ids) {
            $entityLookup[$type] = [];
            foreach (array_chunk($ids, 25) as $chunk) {
                $ops = [];
                $bId = 1;
                foreach ($chunk as $id) {
                    $ops[] = [
                        'bId'       => (string)$bId++,
                        'operation' => 'read',
                        'entity'    => $type,   // e.g. 'Estimate', 'Payment', 'RefundReceipt', 'TimeActivity', etc.
                        'id'        => (string)$id,
                    ];
                }

                $res = Http::withToken($token)
                    ->accept('application/json')
                    ->post($batchUrl, ['BatchItemRequest' => $ops]);

                if ($res->status() === 401) {
                    return response()->json([
                        'error'   => true,
                        'message' => 'Unauthorized (401). Access token may be expired. Please reconnect.',
                    ], 401);
                }

                $json = $res->json();
                foreach (($json['BatchItemResponse'] ?? []) as $bir) {
                    if (!empty($bir['fault'])) continue;

                    // Each response returns a top-level key equal to the entity type, e.g. 'Estimate'
                    if (isset($bir[$type]) && isset($bir[$type]['Id'])) {
                        $entityLookup[$type][$bir[$type]['Id']] = $bir[$type];
                    }
                }
            }
        }

        // ----------- 5) Attach expanded entities back onto each receipt
        $enriched = $allReceipts->map(function ($sr) use ($linkTriples, $entityLookup) {
            $rid = $sr['Id'] ?? null;
            $linked = [];
            foreach ($linkTriples[$rid] ?? [] as [$t, $i]) {
                $linked[] = [
                    'TxnType' => $t,
                    'TxnId'   => $i,
                    'Entity'  => $entityLookup[$t][$i] ?? null, // null if not found/accessible
                ];
            }
            $sr['LinkedEntities'] = $linked;
            return $sr;
        })->values();

        // ----------- 6) Respond
        return response()->json([
            'status' => 'success',
            'count'  => $enriched->count(),
            'data'   => $enriched,
            'meta'   => [
                'mode'       => 'query+batch-expand',
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'page_size'  => $pageSize,
                'types_expanded' => array_keys($byType),
            ],
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}


    /**
     * 💰 Sales Tax Payments - Payments made to tax authorities
     */
    public function salesTaxPayments()
    {
        try {
            // Fetch "Purchase" entries where PaymentType is Check
            $query = "SELECT * FROM Purchase WHERE PaymentType = 'Check' STARTPOSITION 1 MAXRESULTS 200";
            $response = $this->runQuery($query);

            if (isset($response['Fault'])) {
                \Log::error('QuickBooks Tax Payment fetch error', [
                    'fault' => $response['Fault'],
                    'query' => $query,
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => $response['Fault']['Error'][0]['Detail'] ?? 'Query failed',
                ], 400);
            }

            $payments = collect($response['QueryResponse']['Purchase'] ?? [])->map(function ($purchase) {
                return [
                    'Id' => $purchase['Id'] ?? null,
                    'DocNumber' => $purchase['DocNumber'] ?? null,
                    'TxnDate' => $purchase['TxnDate'] ?? null,
                    'TotalAmount' => $purchase['TotalAmt'] ?? 0,
                    'PaymentType' => $purchase['PaymentType'] ?? null,
                    'Payee' => $purchase['EntityRef']['name'] ?? null,
                    'PayeeId' => $purchase['EntityRef']['value'] ?? null,
                    'Account' => [
                        'Id' => $purchase['AccountRef']['value'] ?? null,
                        'Name' => $purchase['AccountRef']['name'] ?? null,
                    ],
                    'PrivateNote' => $purchase['PrivateNote'] ?? null,
                    'Currency' => $purchase['CurrencyRef']['name'] ?? null,
                    'Lines' => collect($purchase['Line'] ?? [])->map(function ($line) {
                        return [
                            'Amount' => $line['Amount'] ?? 0,
                            'Description' => $line['Description'] ?? null,
                            'DetailType' => $line['DetailType'] ?? null,
                            'AccountRef' => $line['AccountBasedExpenseLineDetail']['AccountRef']['name'] ?? null,
                        ];
                    }),
                    'RawData' => $purchase,
                ];
            });

            return response()->json([
                'status' => 'success',
                'count' => $payments->count(),
                'data' => $payments->values(),
            ]);

        } catch (\Exception $e) {
            \Log::error('QuickBooks salesTaxPayments exception', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * 🔄 Refunds - Customer refunds/returns with payment reversals
     */
    public function refunds()
    {
        try {
            // 1️⃣ Fetch all refunds
            $refundResponse = $this->runQuery("SELECT * FROM RefundReceipt STARTPOSITION 1 MAXRESULTS 200");
            dd($refundResponse);
            if ($refundResponse instanceof \Illuminate\Http\JsonResponse) {
                return $refundResponse;
            }

            $refunds = collect($refundResponse['QueryResponse']['Refund'] ?? [])
                ->map(function ($refund) {
                    // Extract refund lines with accounts
                    $refundLines = collect($refund['Line'] ?? [])->map(function ($line) {
                        $accountRef = null;

                        if (!empty($line['SalesItemLineDetail']['ItemAccountRef'])) {
                            $accountRef = $line['SalesItemLineDetail']['ItemAccountRef'];
                        } elseif (!empty($line['SalesItemLineDetail']['ItemRef'])) {
                            // May need to look up item to get account
                            $accountRef = $line['SalesItemLineDetail']['ItemRef'];
                        }

                        return [
                            'DetailType' => $line['DetailType'] ?? null,
                            'Description' => $line['Description'] ?? null,
                            'Amount' => $line['Amount'] ?? 0,
                            'Account' => $accountRef ? [
                                'Id' => $accountRef['value'] ?? null,
                                'Name' => $accountRef['name'] ?? null,
                            ] : null,
                            'ItemRef' => $line['SalesItemLineDetail']['ItemRef']['name'] ?? null,
                            'Quantity' => $line['SalesItemLineDetail']['Qty'] ?? null,
                            'UnitPrice' => $line['SalesItemLineDetail']['UnitPrice'] ?? null,
                        ];
                    });

                    // Tax info
                    $taxTotal = 0;
                    if (!empty($refund['TxnTaxDetail']['TotalTax'])) {
                        $taxTotal = $refund['TxnTaxDetail']['TotalTax'];
                    } elseif (!empty($refund['TotalTax'])) {
                        $taxTotal = $refund['TotalTax'];
                    }

                    return [
                        'RefundId' => $refund['Id'] ?? null,
                        'DocNumber' => $refund['DocNumber'] ?? null,
                        'CustomerName' => $refund['CustomerRef']['name'] ?? null,
                        'CustomerId' => $refund['CustomerRef']['value'] ?? null,
                        'TxnDate' => $refund['TxnDate'] ?? null,
                        'DueDate' => $refund['DueDate'] ?? null,
                        'TotalAmount' => (float) ($refund['TotalAmt'] ?? 0),
                        'TaxTotal' => (float) $taxTotal,
                        'Currency' => $refund['CurrencyRef']['name'] ?? null,
                        'PaymentMethod' => $refund['PaymentMethodRef']['name'] ?? null,
                        'DepositToAccount' => [
                            'Id' => $refund['DepositToAccountRef']['value'] ?? null,
                            'Name' => $refund['DepositToAccountRef']['name'] ?? null,
                        ],
                        'PrivateNote' => $refund['PrivateNote'] ?? null,
                        'RefundLines' => $refundLines,
                        'RawRefund' => $refund,
                    ];
                });

            return response()->json([
                'status' => 'success',
                'count' => $refunds->count(),
                'data' => $refunds->values(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 📝 Credit Memos - Customer credits for returns, discounts, or adjustments
     */
    public function creditMemos()
    {
        try {
            // 1️⃣ Fetch all credit memos
            $creditMemoResponse = $this->runQuery("SELECT * FROM CreditMemo");

            if ($creditMemoResponse instanceof \Illuminate\Http\JsonResponse) {
                return $creditMemoResponse;
            }
            dd(collect($creditMemoResponse['QueryResponse']['CreditMemo']));
            // 2️⃣ Fetch accounts to find A/R if missing
            $accountResponse = $this->runQuery("SELECT * FROM Account WHERE AccountType = 'Accounts Receivable'");

            $defaultARAccount = null;
            if (!($accountResponse instanceof \Illuminate\Http\JsonResponse) && isset($accountResponse['QueryResponse']['Account'])) {
                $defaultARAccount = collect($accountResponse['QueryResponse']['Account'])->first();
            }
            // dd(collect($creditMemoResponse['QueryResponse']['CreditMemo']));
            $creditMemos = collect($creditMemoResponse['QueryResponse']['CreditMemo'] ?? [])->map(function ($memo) use ($defaultARAccount) {
                // 🧾 Parse line-level accounts (Debit side)
                $memoLines = collect($memo['Line'] ?? [])->map(function ($line) {
                    $accountRef = null;

                    if (!empty($line['SalesItemLineDetail']['ItemAccountRef'])) {
                        $accountRef = $line['SalesItemLineDetail']['ItemAccountRef'];
                    } elseif (!empty($line['SalesItemLineDetail']['ItemRef'])) {
                        $accountRef = $line['SalesItemLineDetail']['ItemRef'];
                    } elseif (!empty($line['AccountBasedExpenseLineDetail']['AccountRef'])) {
                        $accountRef = $line['AccountBasedExpenseLineDetail']['AccountRef'];
                    }

                    return [
                        'DetailType' => $line['DetailType'] ?? null,
                        'Description' => $line['Description'] ?? null,
                        'Amount' => $line['Amount'] ?? 0,
                        'PostingType' => 'Debit', // ✅ Debit side
                        'Account' => $accountRef ? [
                            'Id' => $accountRef['value'] ?? null,
                            'Name' => $accountRef['name'] ?? null,
                        ] : null,
                        'ItemRef' => $line['SalesItemLineDetail']['ItemRef']['name'] ?? null,
                        'Quantity' => $line['SalesItemLineDetail']['Qty'] ?? null,
                        'UnitPrice' => $line['SalesItemLineDetail']['UnitPrice'] ?? null,
                    ];
                });

                // 💰 Ensure A/R account (Credit side)
                $arAccountRef = $memo['ARAccountRef'] ?? null;
                $arAccount = [
                    'PostingType' => 'Credit',
                    'Account' => [
                        'Id' => $arAccountRef['value']
                            ?? $defaultARAccount['Id']
                            ?? null,
                        'Name' => $arAccountRef['name']
                            ?? $defaultARAccount['Name']
                            ?? 'Accounts Receivable',
                    ],
                    'Amount' => $memo['TotalAmt'] ?? 0,
                ];

                // ➕ Combine both sides
                $journalEntries = $memoLines->push($arAccount)->values();

                // 📑 Tax details
                $taxTotal = 0;
                if (!empty($memo['TxnTaxDetail']['TotalTax'])) {
                    $taxTotal = $memo['TxnTaxDetail']['TotalTax'];
                } elseif (!empty($memo['TotalTax'])) {
                    $taxTotal = $memo['TotalTax'];
                }

                // 🔗 Linked Transactions
                $linkedTxns = [];
                foreach ($memo['Line'] ?? [] as $line) {
                    if (!empty($line['LinkedTxn'])) {
                        $linkedTxns = array_merge($linkedTxns, (array) $line['LinkedTxn']);
                    }
                }

                // 📦 Final structured object
                return [
                    'CreditMemoId' => $memo['Id'] ?? null,
                    'DocNumber' => $memo['DocNumber'] ?? null,
                    'CustomerName' => $memo['CustomerRef']['name'] ?? null,
                    'CustomerId' => $memo['CustomerRef']['value'] ?? null,
                    'TxnDate' => $memo['TxnDate'] ?? null,
                    'TotalAmount' => (float) ($memo['TotalAmt'] ?? 0),
                    'TaxTotal' => (float) $taxTotal,
                    'Balance' => (float) ($memo['Balance'] ?? 0),
                    'Currency' => $memo['CurrencyRef']['name'] ?? null,
                    'PrivateNote' => $memo['PrivateNote'] ?? null,
                    'Reason' => $memo['Reason'] ?? null,
                    'JournalEntries' => $journalEntries, // ✅ Debit + Credit sides
                    'LinkedTransactions' => $linkedTxns,
                ];
            });

            return response()->json([
                'status' => 'success',
                'count' => $creditMemos->count(),
                'data' => $creditMemos->values(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * 💳 Credit Card Credits - Vendor credits via credit card (accounts payable reduction)
     */
    private function runEntity($entity)
    {
        $accessToken = $this->accessToken();
            $realmId = $this->realmId();

        $url = "https://quickbooks.api.intuit.com/v3/company/{$realmId}/{$entity}";
        $response = Http::withToken($accessToken)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->get($url);

        if ($response->failed()) {
            return response()->json([
                'status' => 'error',
                'message' => 'QuickBooks API request failed',
                'details' => $response->json(),
            ], $response->status());
        }

        return $response->json();
    }

   public function creditCardCredits(Request $request)
{
    try {
        $startDate = $request->get('start_date', '2010-01-01');
        $endDate   = $request->get('end_date', date('Y-m-d'));

        // 1️⃣ Fetch report from QuickBooks (TransactionList for CreditCardCredit)
        $token = $this->accessToken();
        $realm = $this->realmId();
        $url = "https://quickbooks.api.intuit.com/v3/company/{$realm}/reports/TransactionList" .
               "?start_date={$startDate}&end_date={$endDate}&transaction_type=CreditCardCredit&minorversion=65";

        $response = Http::withToken($token)
            ->withHeaders(['Accept' => 'application/json'])
            ->get($url)
            ->throw()
            ->json();

        $columns = collect($response['Columns']['Column'] ?? [])->pluck('ColTitle')->toArray();
        $rows = collect($response['Rows']['Row'] ?? []);

        // 2️⃣ Map each row into a key-value structure
        $entries = $rows->map(function ($row) use ($columns) {
            $colData = $row['ColData'] ?? [];
            $mapped = [];
            foreach ($colData as $index => $col) {
                $key = $columns[$index] ?? "Column_{$index}";
                $mapped[$key] = $col['value'] ?? null;
            }
            return $mapped;
        })->filter(function ($entry) {
            return ($entry['Transaction Type'] ?? '') === 'Credit Card Credit';
        })->values();

        if ($entries->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'count' => 0,
                'data' => [],
                'message' => 'No Credit Card Credit entries found for this date range.',
            ]);
        }

        // 3️⃣ Collect unique account and split names for lookup
        $accountNames = collect($entries)->pluck('Account')
            ->merge($entries->pluck('Split'))
            ->unique()
            ->filter()
            ->values();

        // 4️⃣ Fetch all account details in batches (QuickBooks limits query size)
        $accountDetails = collect();
        foreach ($accountNames->chunk(20) as $chunk) {
            $query = "SELECT Id, Name, AccountType, AccountSubType, Classification 
                      FROM Account WHERE Name IN ('" .
                      implode("','", $chunk->map(fn($n) => addslashes($n))->toArray()) . "')";
            
            $resp = $this->runQuery($query);

            // Some QuickBooks responses may come back as JsonResponse, so handle that
            if ($resp instanceof \Illuminate\Http\JsonResponse) {
                $resp = $resp->getData(true);
            }

            $accountDetails = $accountDetails->merge($resp['QueryResponse']['Account'] ?? []);
        }

        // 5️⃣ Create lookup table by Name
        $accountsByName = $accountDetails->keyBy('Name');

        // 6️⃣ Merge account info back into each entry
        $detailedEntries = $entries->map(function ($e) use ($accountsByName) {
            $account = $accountsByName[$e['Account']] ?? null;
            $split   = $accountsByName[$e['Split']] ?? null;

            return [
                'Date' => $e['Date'] ?? null,
                'TransactionType' => $e['Transaction Type'] ?? null,
                'Name' => $e['Name'] ?? null,
                'Memo' => $e['Memo/Description'] ?? null,
                'Amount' => $e['Amount'] ?? null,
                'Posting' => $e['Posting'] ?? null,
                'Account' => $account ? [
                    'Id' => $account['Id'] ?? null,
                    'Name' => $account['Name'] ?? null,
                    'AccountType' => $account['AccountType'] ?? null,
                    'AccountSubType' => $account['AccountSubType'] ?? null,
                    'Classification' => $account['Classification'] ?? null,
                ] : [
                    'Id' => null,
                    'Name' => $e['Account'] ?? null
                ],
                'Split' => $split ? [
                    'Id' => $split['Id'] ?? null,
                    'Name' => $split['Name'] ?? null,
                    'AccountType' => $split['AccountType'] ?? null,
                    'AccountSubType' => $split['AccountSubType'] ?? null,
                    'Classification' => $split['Classification'] ?? null,
                ] : [
                    'Id' => null,
                    'Name' => $e['Split'] ?? null
                ],
            ];
        });

        // ✅ 7️⃣ Return complete formatted response
        return response()->json([
            'status' => 'success',
            'count' => $detailedEntries->count(),
            'data' => $detailedEntries->values(),
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}



    /**
     * 📊 Credit Card Credits with Bill Links - Enhanced version showing bill applications
     */
    public function creditCardCreditsWithBills()
    {
        try {
            // Fetch credit card credits and bills
            $creditsResponse = $this->runQuery("SELECT * FROM CreditCardCredit STARTPOSITION 1 MAXRESULTS 200");
            $billsResponse = $this->runQuery("SELECT * FROM Bill STARTPOSITION 1 MAXRESULTS 200");

            if ($creditsResponse instanceof \Illuminate\Http\JsonResponse) {
                return $creditsResponse;
            }

            $credits = collect($creditsResponse['QueryResponse']['CreditCardCredit'] ?? []);
            $bills = collect($billsResponse['QueryResponse']['Bill'] ?? []);

            // Normalize credits with linked bills
            $creditsWithBills = $credits->map(function ($credit) use ($bills) {
                $linkedBills = [];

                foreach ($credit['Line'] ?? [] as $line) {
                    if (!empty($line['LinkedTxn'])) {
                        $linkedArray = is_array($line['LinkedTxn']) ? $line['LinkedTxn'] : [$line['LinkedTxn']];
                        foreach ($linkedArray as $linked) {
                            if (isset($linked['TxnType'], $linked['TxnId']) && strcasecmp($linked['TxnType'], 'Bill') === 0) {
                                $bill = $bills->first(fn($b) => (string) $b['Id'] === (string) $linked['TxnId']);
                                if ($bill) {
                                    $linkedBills[] = [
                                        'BillId' => $bill['Id'] ?? null,
                                        'DocNumber' => $bill['DocNumber'] ?? null,
                                        'VendorName' => $bill['VendorRef']['name'] ?? null,
                                        'BillAmount' => (float) ($bill['TotalAmt'] ?? 0),
                                        'Balance' => (float) ($bill['Balance'] ?? 0),
                                        'TxnDate' => $bill['TxnDate'] ?? null,
                                    ];
                                }
                            }
                        }
                    }
                }

                return [
                    'CreditId' => $credit['Id'] ?? null,
                    'DocNumber' => $credit['DocNumber'] ?? null,
                    'VendorName' => $credit['VendorRef']['name'] ?? null,
                    'VendorId' => $credit['VendorRef']['value'] ?? null,
                    'TxnDate' => $credit['TxnDate'] ?? null,
                    'TotalAmount' => (float) ($credit['TotalAmt'] ?? 0),
                    'CreditCardAccount' => [
                        'Id' => $credit['CCAccountRef']['value'] ?? null,
                        'Name' => $credit['CCAccountRef']['name'] ?? null,
                    ],
                    'LinkedBills' => $linkedBills,
                    'RawCredit' => $credit,
                ];
            });

            return response()->json([
                'status' => 'success',
                'count' => $creditsWithBills->count(),
                'data' => $creditsWithBills->values(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getEstimates($start = 1, $max = 200)
    {
        try {
            // Run QuickBooks query for Estimate objects
            $query = "SELECT * FROM Estimate STARTPOSITION {$start} MAXRESULTS {$max}";
            $response = $this->runQuery($query);

            // Handle validation or empty responses
            if (isset($response['Fault'])) {
                \Log::error('QuickBooks Estimate fetch error', [
                    'fault' => $response['Fault'],
                    'query' => $query,
                ]);
                return [
                    'success' => false,
                    'message' => $response['Fault']['Error'][0]['Detail'] ?? 'Unknown error',
                ];
            }

            // Extract Estimate data
            $estimates = $response['QueryResponse']['Estimate'] ?? [];

            return [
                'success' => true,
                'count' => count($estimates),
                'data' => $estimates,
            ];

        } catch (\Exception $e) {
            \Log::error('QuickBooks getEstimates exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    private function runPayrollGraphQL(string $query, array $variables = [])
    {
        try {
            // Get tokens and realm ID like you already do for QBO REST
            $accessToken = $this->accessToken(); // Your existing function
            $realmId = $this->realmId();         // Your existing function

            if (!$accessToken || !$realmId) {
                throw new \Exception('Missing QuickBooks authorization.');
            }

            $url = "https://sandbox-graphql.qbo.intuit.com/v1/graphql";

            $payload = [
                'query' => $query,
                'variables' => (object) $variables,
            ];

            $client = new \GuzzleHttp\Client();

            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'body' => json_encode($payload),
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (\Exception $e) {
            \Log::error('QuickBooks Payroll GraphQL failed', [
                'message' => $e->getMessage(),
            ]);

            return [
                'errors' => [
                    ['message' => $e->getMessage()],
                ],
            ];
        }
    }

    public function getPayrollRuns($limit = 50, $cursor = null)
    {
        try {
            // GraphQL query for payroll runs (payslips) – adjust fields as needed
            $query = <<<'GRAPHQL'
        query GetPayrollRuns($limit: Int!, $cursor: String) {
          payrollRuns(limit: $limit, cursor: $cursor) {
            edges {
              node {
                id
                startDate
                endDate
                payDate
                totalGross
                totalNet
                employeeCount
              }
            }
            pageInfo {
              endCursor
              hasNextPage
            }
          }
        }
GRAPHQL;

            $variables = ['limit' => $limit, 'cursor' => $cursor];

            $response = $this->runPayrollGraphQL($query, $variables);

            if (isset($response['errors'])) {
                \Log::error('QuickBooks Payroll Runs fetch error', [
                    'errors' => $response['errors'],
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => $response['errors'][0]['message'] ?? 'Unknown error',
                ], 400);
            }

            $runs = collect($response['data']['payrollRuns']['edges'])
                ->map(fn($edge) => $edge['node']);

            return response()->json([
                'status' => 'success',
                'data' => $runs,
                'pageInfo' => $response['data']['payrollRuns']['pageInfo'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function getPayrollAdjustments($limit = 50, $cursor = null)
    {
        try {
            // GraphQL query for payroll adjustments – fields may vary
            $query = <<<'GRAPHQL'
        query GetPayrollAdjustments($limit: Int!, $cursor: String) {
          payrollAdjustments(limit: $limit, cursor: $cursor) {
            edges {
              node {
                id
                payrollRunId
                employeeId
                adjustmentType
                amount
                reason
                effectiveDate
              }
            }
            pageInfo {
              endCursor
              hasNextPage
            }
          }
        }
GRAPHQL;

            $variables = ['limit' => $limit, 'cursor' => $cursor];

            $response = $this->runPayrollGraphQL($query, $variables);

            if (isset($response['errors'])) {
                \Log::error('QuickBooks Payroll Adjustments fetch error', [
                    'errors' => $response['errors'],
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => $response['errors'][0]['message'] ?? 'Unknown error',
                ], 400);
            }

            $adjustments = collect($response['data']['payrollAdjustments']['edges'])
                ->map(fn($edge) => $edge['node']);

            return response()->json([
                'status' => 'success',
                'data' => $adjustments,
                'pageInfo' => $response['data']['payrollAdjustments']['pageInfo'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function getTransfers()
    {
        try {
            // 🔹 Run a QuickBooks Query to fetch up to 200 transfers
            $transferResponse = $this->runQuery("SELECT * FROM Transfer STARTPOSITION 1 MAXRESULTS 500");
            dd($transferResponse);
            // 🔹 Handle QuickBooks or connection errors
            if ($transferResponse instanceof \Illuminate\Http\JsonResponse) {
                return $transferResponse;
            }

            // 🔹 Handle Faults from QuickBooks
            if (isset($transferResponse['Fault'])) {
                return response()->json([
                    'status' => 'error',
                    'error' => $transferResponse['Fault']['Error'][0]['Detail'] ?? 'Unknown QuickBooks error',
                    'raw' => $transferResponse,
                ], 400);
            }

            // 🔹 Parse Transfer Data
            $transfers = collect($transferResponse['QueryResponse']['Transfer'] ?? [])->map(function ($transfer) {
                return [
                    'TransferId' => $transfer['Id'] ?? null,
                    'TxnDate' => $transfer['TxnDate'] ?? null,
                    'Amount' => $transfer['Amount'] ?? 0,
                    'FromAccount' => [
                        'Id' => $transfer['FromAccountRef']['value'] ?? null,
                        'Name' => $transfer['FromAccountRef']['name'] ?? null,
                    ],
                    'ToAccount' => [
                        'Id' => $transfer['ToAccountRef']['value'] ?? null,
                        'Name' => $transfer['ToAccountRef']['name'] ?? null,
                    ],
                    'PrivateNote' => $transfer['PrivateNote'] ?? null,
                    'Currency' => $transfer['CurrencyRef']['name'] ?? null,
                    'ExchangeRate' => $transfer['ExchangeRate'] ?? null,
                    'RawTransfer' => $transfer,
                ];
            });

            // 🔹 Return a clean JSON response
            return response()->json([
                'status' => 'success',
                'count' => $transfers->count(),
                'data' => $transfers->values(),
            ]);

        } catch (\Exception $e) {
            dd($e);
            // 🔹 Fallback on any unexpected errors
            \Log::error('QuickBooks Transfer Fetch Failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
// public function getAllTransactionsGrouped(Request $request)
// {
//     try {
//         $start = (int) $request->get('start', 1);
//         $max = (int) $request->get('max', 50);

//         $types = [
//             'Invoice', 'Bill', 'Payment', 'Expense', 'JournalEntry',
//             'Deposit', 'Transfer', 'CreditMemo', 'Purchase', 'Estimate',
//             'VendorCredit', 'SalesReceipt', 'RefundReceipt', 'PurchaseOrder',
//             'TimeActivity'
//         ];

//         $grouped = [];

//         foreach ($types as $type) {
//             try {
//                 $query = "SELECT * FROM {$type} STARTPOSITION {$start} MAXRESULTS {$max}";
//                 $response = $this->runQuery($query);

//                 // Handle token or connection issues
//                 if ($response instanceof \Illuminate\Http\JsonResponse) {
//                     continue;
//                 }

//                 // Skip if Fault
//                 if (isset($response['Fault'])) {
//                     $grouped[$type] = [
//                         'status' => 'error',
//                         'message' => $response['Fault']['Error'][0]['Message'] ?? 'Unknown error',
//                     ];
//                     continue;
//                 }

//                 // Extract transactions of this type
//                 $data = collect($response['QueryResponse'][$type] ?? []);

//                 $grouped[$type] = [
//                     'count' => $data->count(),
//                     'data' => $data->values(),
//                 ];
//             } catch (\Exception $innerEx) {
//                 $grouped[$type] = [
//                     'status' => 'error',
//                     'message' => $innerEx->getMessage(),
//                 ];
//             }
//         }

//         return dd([
//             'status' => 'success',
//             'types_count' => count($types),
//             'data' => $grouped,
//         ]);

//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => 'error',
//             'message' => $e->getMessage(),
//         ], 500);
//     }
// }
  public function getAllTransactionsGrouped(Request $request)
{
    try {
        $start = (int) $request->get('start', 1);
        $max = (int) $request->get('max', 100);

        // 1️⃣ Fetch Invoices
        $invoiceQuery = "SELECT * FROM Invoice STARTPOSITION {$start} MAXRESULTS {$max}";
        $invoiceResponse = $this->runQuery($invoiceQuery);

        if ($invoiceResponse instanceof \Illuminate\Http\JsonResponse) return $invoiceResponse;
        if (isset($invoiceResponse['Fault'])) {
            throw new \Exception($invoiceResponse['Fault']['Error'][0]['Message'] ?? 'Error fetching invoices');
        }

        $invoices = collect($invoiceResponse['QueryResponse']['Invoice'] ?? []);

        // 2️⃣ Fetch Payments
        $paymentQuery = "SELECT * FROM Payment STARTPOSITION {$start} MAXRESULTS {$max}";
        $paymentResponse = $this->runQuery($paymentQuery);

        if ($paymentResponse instanceof \Illuminate\Http\JsonResponse) return $paymentResponse;
        if (isset($paymentResponse['Fault'])) {
            throw new \Exception($paymentResponse['Fault']['Error'][0]['Message'] ?? 'Error fetching payments');
        }

        $payments = collect($paymentResponse['QueryResponse']['Payment'] ?? []);

        // 3️⃣ Fetch Accounts
        $accountQuery = "SELECT * FROM Account STARTPOSITION 1 MAXRESULTS 500";
        $accountResponse = $this->runQuery($accountQuery);

        if ($accountResponse instanceof \Illuminate\Http\JsonResponse) return $accountResponse;
        if (isset($accountResponse['Fault'])) {
            throw new \Exception($accountResponse['Fault']['Error'][0]['Message'] ?? 'Error fetching accounts');
        }

        $accounts = collect($accountResponse['QueryResponse']['Account'] ?? []);

        // 4️⃣ Combine — only invoices that have payments
        $invoicePayments = collect();

        foreach ($payments as $payment) {
            if (!isset($payment['Line'])) continue;

            foreach ($payment['Line'] as $line) {
                if (!isset($line['LinkedTxn'])) continue;

                foreach ($line['LinkedTxn'] as $txn) {
                    if ($txn['TxnType'] === 'Invoice') {
                        $invoiceId = $txn['TxnId'];
                        $invoice = $invoices->firstWhere('Id', $invoiceId);
                        if (!$invoice) continue;

                        // Invoice account
                        $invoiceAccountId = $invoice['ARAccountRef']['value'] ?? null;
                        $invoiceAccount = $accounts->firstWhere('Id', $invoiceAccountId);

                        // Payment account
                        $paymentAccountId = $payment['DepositToAccountRef']['value'] ?? null;
                        $paymentAccount = $accounts->firstWhere('Id', $paymentAccountId);

                        $invoicePayments->push([
                            'invoice' => $invoice,
                            'invoice_account' => $invoiceAccount ?? null,
                            'payment' => $payment,
                            'payment_account' => $paymentAccount ?? null,
                        ]);
                    }
                }
            }
        }

        // 5️⃣ Show only a single clean linked record
        $first = $invoicePayments->first();

        return dd([
            'status' => 'success',
            'count' => $invoicePayments->count(),
            'linked_record' => $first
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}
}
