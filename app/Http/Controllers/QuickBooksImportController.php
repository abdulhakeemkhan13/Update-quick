<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Utility;
use Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\ProposalProduct;
use App\Models\InvoiceProduct;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositLines;
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

class QuickBooksImportController extends Controller
{
    protected $qbController;
    protected $userId;

    public function __construct()
    {
        $this->qbController = new QuickBooksApiController();
        $this->userId = auth()->id();
    }

    public function showImportView()
    {
        return view('quickbooks_invoices');
    }

    public function startFullImport(Request $request)
    {
        try {
            $userId = \Auth::id();
            $cacheKey = 'qb_import_progress_' . $userId;
            Cache::forget($cacheKey);
            // Check if QuickBooks is connected
            $qbController = new QuickBooksApiController();
            if (!$qbController->accessToken() || !$qbController->realmId()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'QuickBooks is not connected. Please connect first.'
                ], 400);
            }

            // Check if import is already running for this user
            $cacheKey = 'qb_import_progress_' . $userId;
            $progress = Cache::get($cacheKey);
            if ($progress && $progress['status'] == 'running') {
                // Import is already running, return current progress instead of error
                return response()->json([
                    'status' => 'already_running',
                    'message' => 'Import is already running. Showing current progress...',
                    'progress' => [
                        'status' => $progress['status'] ?? 'running',
                        'current_step' => $progress['current_step'] ?? 0,
                        'total_steps' => $progress['total_steps'] ?? 8,
                        'current_import' => $progress['current_import'] ?? 'Processing...',
                        'percentage' => $progress['percentage'] ?? 0,
                        'logs' => $progress['logs'] ?? [],
                    ]
                ]);
            }

            // Clear any old completed/failed import data before starting new one
            Cache::forget($cacheKey);

            // Initialize fresh progress state BEFORE dispatching job
            $initialProgress = [
                'status' => 'running',
                'current_step' => 0,
                'total_steps' => 8,
                'current_import' => 'Dispatching import job...',
                'logs' => ['[' . now()->format('g:i:s A') . '] Import job dispatched successfully. Monitoring progress...'],
                'percentage' => 0,
            ];
            Cache::put($cacheKey, $initialProgress, 3600);
            $this->startQueueWorkerForJob();
            // Dispatch the job with user ID
            \App\Jobs\QuickBooksFullImportJob::dispatch($userId);

            return response()->json([
                'status' => 'success',
                'message' => 'Full import job has been dispatched and will run in the background.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to start QuickBooks import: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to start import: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getImportProgress(Request $request)
    {
        $userId = \Auth::id();
        $cacheKey = 'qb_import_progress_' . $userId;

        $progress = Cache::get($cacheKey, [
            'status' => 'idle',
            'current_step' => 0,
            'total_steps' => 8,
            'current_import' => 'Not started',
            'logs' => [],
            'percentage' => 0,
        ]);

        // Send all logs (we handle deduplication on frontend)
        $displayLogs = [];
        if (isset($progress['logs']) && is_array($progress['logs'])) {
            $displayLogs = $progress['logs'];
        }

        // If status is running but no logs in cache, try to read from laravel.log
        if (($progress['status'] ?? 'idle') === 'running' && empty($displayLogs)) {
            $laravelLogs = $this->getRecentLaravelLogs($userId);
            if (!empty($laravelLogs)) {
                $displayLogs = $laravelLogs;
            }
        }

        return response()->json([
            'status' => $progress['status'] ?? 'idle',
            'current_step' => $progress['current_step'] ?? 0,
            'total_steps' => $progress['total_steps'] ?? 8,
            'current_import' => $progress['current_import'] ?? 'Not started',
            'percentage' => $progress['percentage'] ?? 0,
            'logs' => $displayLogs,
        ]);
    }

    /**
     * Read recent QuickBooks import logs from laravel.log file
     */
    protected function getRecentLaravelLogs($userId, $lines = 50)
    {
        try {
            $logFile = storage_path('logs/laravel.log');

            if (!file_exists($logFile)) {
                return [];
            }

            // Read last N lines from log file
            $file = new \SplFileObject($logFile, 'r');
            $file->seek(PHP_INT_MAX);
            $lastLine = $file->key();
            $startLine = max(0, $lastLine - $lines);

            $logs = [];
            $file->seek($startLine);

            while (!$file->eof()) {
                $line = $file->current();
                $file->next();

                // Filter logs related to QuickBooks import for this user
                if (strpos($line, "user {$userId}") !== false ||
                    strpos($line, "QuickBooks") !== false ||
                    strpos($line, "Importing") !== false) {

                    // Extract timestamp and message
                    if (preg_match('/\[(.*?)\].*?(local\.(INFO|ERROR|WARNING)):\s*(.+)/', $line, $matches)) {
                        $timestamp = $matches[1];
                        $level = $matches[2];
                        $message = trim($matches[4]);

                        // Format log message
                        $formattedLog = "[{$timestamp}] {$message}";
                        $logs[] = $formattedLog;
                    }
                }
            }

            return array_slice($logs, -30); // Return last 30 relevant logs

        } catch (\Exception $e) {
            \Log::error('Failed to read laravel.log: ' . $e->getMessage());
            return [];
        }
    }
    // prev invoice import
    // public function importInvoices(Request $request)
    // {
    //     try {
    //         // Fetch all invoices with pagination
    //         $allInvoices = collect();
    //         $startPosition = 1;
    //         $maxResults = 50; // Adjust batch size as needed

    //         do {
    //             // Fetch paginated batch
    //             $query = "SELECT * FROM Invoice STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
    //             $invoicesResponse = $this->qbController->runQuery($query);

    //             // Handle API errors
    //             if ($invoicesResponse instanceof \Illuminate\Http\JsonResponse) {
    //                 return $invoicesResponse;
    //             }

    //             // Get invoices from response
    //             $invoicesData = $invoicesResponse['QueryResponse']['Invoice'] ?? [];

    //             // Merge entire objects (keep all keys)
    //             $allInvoices = $allInvoices->merge($invoicesData);

    //             // Move to next page
    //             $fetchedCount = count($invoicesData);
    //             $startPosition += $fetchedCount;
    //         } while ($fetchedCount === $maxResults); // continue if page is full

    //         // Fetch all payments with pagination
    //         $allPayments = collect();
    //         $startPosition = 1;

    //         do {
    //             // Fetch paginated batch
    //             $query = "SELECT * FROM Payment STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
    //             $paymentsResponse = $this->qbController->runQuery($query);

    //             // Handle API errors
    //             if ($paymentsResponse instanceof \Illuminate\Http\JsonResponse) {
    //                 return $paymentsResponse;
    //             }

    //             // Get payments from response
    //             $paymentsData = $paymentsResponse['QueryResponse']['Payment'] ?? [];

    //             // Merge entire objects (keep all keys)
    //             $allPayments = $allPayments->merge($paymentsData);

    //             // Move to next page
    //             $fetchedCount = count($paymentsData);
    //             $startPosition += $fetchedCount;
    //         } while ($fetchedCount === $maxResults); // continue if page is full

    //         // Fetch items and accounts (these are usually smaller datasets)
    //         $itemsRaw = $this->qbController->runQuery("SELECT * FROM Item STARTPOSITION 1 MAXRESULTS 500");
    //         $accountsRaw = $this->qbController->runQuery("SELECT * FROM Account STARTPOSITION 1 MAXRESULTS 500");

    //         $itemsList = collect($itemsRaw['QueryResponse']['Item'] ?? []);
    //         $accountsList = collect($accountsRaw['QueryResponse']['Account'] ?? []);

    //         $itemsMap = $itemsList->keyBy(fn($it) => $it['Id'] ?? null)->toArray();
    //         $accountsMap = $accountsList->keyBy(fn($a) => $a['Id'] ?? null)->toArray();

    //         // Helper functions as in the original
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

    //         $detectAccountForSalesItem = function ($sid) use ($itemsMap, $accountsMap) {
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

    //         $parseInvoiceLine = function ($line) use ($detectAccountForSalesItem, $itemsMap, $accountsMap) {
    //             $out = [];
    //             $detailType = $line['DetailType'] ?? null;

    //             if (!empty($line['GroupLineDetail']) && !empty($line['GroupLineDetail']['Line'])) {
    //                 foreach ($line['GroupLineDetail']['Line'] as $child) {
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

    //         $invoices = $allInvoices->map(function ($invoice) use ($parseInvoiceLine, $accountsMap, $arAccount, $taxAccount, &$invoicesList) {
    //             $parsedLines = [];
    //             foreach ($invoice['Line'] ?? [] as $line) {
    //                 $parsedLines = array_merge($parsedLines, $parseInvoiceLine($line));
    //             }

    //             $unmapped = array_values(array_filter($parsedLines, fn($l) => empty($l['AccountId']) && (float) $l['Amount'] != 0.0));

    //             $taxTotal = 0;
    //             if (!empty($invoice['TxnTaxDetail']['TotalTax']))
    //                 $taxTotal = $invoice['TxnTaxDetail']['TotalTax'];
    //             elseif (!empty($invoice['TotalTax']))
    //                 $taxTotal = $invoice['TotalTax'];

    //             $totalAmount = (float) ($invoice['TotalAmt'] ?? 0);

    //             $journalLines = [];

    //             if ($arAccount) {
    //                 $journalLines[] = [
    //                     'AccountId' => $arAccount['Id'],
    //                     'AccountName' => $arAccount['Name'],
    //                     'Debit' => $totalAmount,
    //                     'Credit' => 0.0,
    //                     'Note' => 'Accounts Receivable (invoice total)'
    //                 ];
    //             } else {
    //                 dd($arAccount);
    //                 // $journalLines[] = [
    //                 //     'AccountId' => null,
    //                 //     'AccountName' => 'Accounts Receivable (not found)',
    //                 //     'Debit' => $totalAmount,
    //                 //     'Credit' => 0.0,
    //                 //     'Note' => 'Accounts Receivable (invoice total, account not auto-detected)'
    //                 // ];
    //             }

    //             foreach ($parsedLines as $pl) {
    //                 if ((float) $pl['Amount'] == 0.0)
    //                     continue;
    //                 if (empty($pl['AccountId']))
    //                     continue;
    //                 $journalLines[] = [
    //                     'AccountId' => $pl['AccountId'],
    //                     'AccountName' => $pl['AccountName'] ?? null,
    //                     'Debit' => 0.0,
    //                     'Credit' => (float) $pl['Amount'],
    //                     'Note' => $pl['Description'] ?? 'Sales / line item'
    //                 ];
    //             }

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

    //         $payments = $allPayments->map(function ($payment) use (&$paymentsList) {
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
    //         // dd($invoicesWithPayments->first());
    //         // Now, import logic
    //         $imported = 0;
    //         $skipped = 0;
    //         $failed = 0;

    //         DB::beginTransaction();
    //         try {
    //             foreach ($invoicesWithPayments as $qbInvoice) {
    //                 $qbId = $qbInvoice['InvoiceId'];

    //                 // Check for duplicate
    //                 $existing = Invoice::where('invoice_id', $qbId)->first();
    //                 if ($existing) {
    //                     $skipped++;
    //                     continue;
    //                 }

    //                 // Map customer_id - assuming CustomerRef value maps to local customer id, but need to handle
    //                 // For simplicity, assume customer_id is the QB CustomerRef value, but in reality, you might need to map QB customers to local customers
    //                 $customerId = $qbInvoice['CustomerId']; // This might need adjustment

    //                 // Insert invoice
    //                 $invoice = Invoice::create([
    //                     'invoice_id' => $qbId,
    //                     'customer_id' => $customerId,
    //                     'issue_date' => $qbInvoice['TxnDate'],
    //                     'due_date' => $qbInvoice['DueDate'],
    //                     'ref_number' => $qbInvoice['DocNumber'],
    //                     'status' => 2, // default
    //                     // other fields as needed
    //                     'created_by' => \Auth::user()->creatorId(),
    //                     'owned_by' => \Auth::user()->ownedId(),
    //                 ]);

    //                 // Insert products
    //                 foreach ($qbInvoice['ParsedLines'] as $line) {
    //                     if (empty($line['AccountId']))
    //                         continue; // Skip unmapped

    //                     // Map to product by name - create if doesn't exist
    //                     $itemName = $line['RawLine']['SalesItemLineDetail']['ItemRef']['name'] ?? null;
    //                     if (!$itemName)
    //                         continue;

    //                     $product = ProductService::where('name', $itemName)
    //                         ->where('created_by', \Auth::user()->creatorId())
    //                         ->first();

    //                     if (!$product) {
    //                         // Create product if it doesn't exist
    //                         $unit = ProductServiceUnit::firstOrCreate(
    //                             ['name' => 'pcs'],
    //                             ['created_by' => \Auth::user()->creatorId()]
    //                         );

    //                         $productCategory = ProductServiceCategory::firstOrCreate(
    //                             [
    //                                 'name' => 'Product',
    //                                 'created_by' => \Auth::user()->creatorId(),
    //                             ],
    //                             [
    //                                 'color' => '#4CAF50',
    //                                 'type' => 'Product',
    //                                 'chart_account_id' => 0,
    //                                 'created_by' => \Auth::user()->creatorId(),
    //                                 'owned_by' => \Auth::user()->ownedId(),
    //                             ]
    //                         );

    //                         $productData = [
    //                             'name' => $itemName,
    //                             'sku' => $itemName,
    //                             'sale_price' => $line['Amount'] ?? 0,
    //                             'purchase_price' => 0,
    //                             'quantity' => 0,
    //                             'unit_id' => $unit->id,
    //                             'type' => 'product',
    //                             'category_id' => $productCategory->id,
    //                             'created_by' => \Auth::user()->creatorId(),
    //                         ];

    //                         // Map chart accounts if available
    //                         if (!empty($line['AccountId'])) {
    //                             $account = ChartOfAccount::where('code', $line['AccountId'])
    //                                 ->where('created_by', \Auth::user()->creatorId())
    //                                 ->first();
    //                             if ($account) {
    //                                 $productData['sale_chartaccount_id'] = $account->id;
    //                             }
    //                         }

    //                         $product = ProductService::create($productData);
    //                     }
    //                     // dd($line,$product,$qbInvoice);
    //                     InvoiceProduct::create([
    //                         'invoice_id' => $invoice->id,
    //                         'product_id' => $product->id,
    //                         'quantity' => $line['RawLine']['SalesItemLineDetail']['Qty'] ?? 1,
    //                         'price' => $line['Amount'],
    //                         'description' => $line['Description'],
    //                     ]);
    //                 }

    //                 // Insert payments
    //                 foreach ($qbInvoice['Payments'] as $payment) {
    //                     // Determine payment method based on payment data
    //                     $paymentMethod = $payment['PaymentMethod'];

    //                     // If payment method is null, try to determine from payment type or account
    //                     if (!$paymentMethod) {
    //                         // Check if it's a credit card payment
    //                         if (isset($payment['RawPayment']['CreditCardPayment'])) {
    //                             $paymentMethod = 'Credit Card';
    //                         }
    //                         // Check if it's a check payment
    //                         elseif (isset($payment['RawPayment']['CheckPayment'])) {
    //                             $paymentMethod = 'Check';
    //                         }
    //                         // Check deposit account type
    //                         elseif (isset($payment['RawPayment']['DepositToAccountRef'])) {
    //                             $accountId = $payment['RawPayment']['DepositToAccountRef']['value'];
    //                             $account = collect($accountsList)->firstWhere('Id', $accountId);
    //                             if ($account) {
    //                                 $accountType = strtolower($account['AccountType'] ?? '');
    //                                 if (strpos($accountType, 'bank') !== false || strpos($accountType, 'checking') !== false) {
    //                                     $paymentMethod = 'Bank Transfer';
    //                                 } elseif (strpos($accountType, 'credit') !== false) {
    //                                     $paymentMethod = 'Credit Card';
    //                                 } else {
    //                                     $paymentMethod = 'Cash';
    //                                 }
    //                             } else {
    //                                 $paymentMethod = 'Cash';
    //                             }
    //                         } else {
    //                             $paymentMethod = 'Cash';
    //                         }
    //                     }

    //                     InvoicePayment::create([
    //                         'invoice_id' => $invoice->id,
    //                         'date' => $payment['TxnDate'],
    //                         'amount' => $payment['TotalAmount'],
    //                         'payment_method' => $paymentMethod,
    //                         'txn_id' => $payment['PaymentId'],
    //                         'currency' => 'USD', // default
    //                         'reference' => $payment['PaymentId'],
    //                         'description' => 'Payment for Invoice ' . $qbInvoice['DocNumber'],
    //                     ]);
    //                 }
    //                 if (!empty($qbInvoice['Payments'])) {
    //                     $invoice->status = 4;
    //                     $invoice->send_date = $qbInvoice['TxnDate'];

    //                 }
    //                 $invoice->save();
    //                 $imported++;
    //             }

    //             DB::commit();
    //         } catch (\Exception $e) {
    //             DB::rollBack();
    //             dd($e);
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Import failed: ' . $e->getMessage(),
    //             ], 500);
    //         }

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => "Invoices import completed. Imported: {$imported}, Skipped: {$skipped}, Failed: {$failed}",
    //             'imported' => $imported,
    //             'skipped' => $skipped,
    //             'failed' => $failed,
    //         ]);

    //     } catch (\Exception $e) {
    //         dd($e);
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Error: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function importInvoices(Request $request)
    // {
    //     try {
    //         // Fetch all invoices with pagination
    //         $allInvoices = collect();
    //         $startPosition = 1;
    //         $maxResults = 50;

    //         do {
    //             $query = "SELECT * FROM Invoice STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
    //             $invoicesResponse = $this->qbController->runQuery($query);

    //             if ($invoicesResponse instanceof \Illuminate\Http\JsonResponse) {
    //                 return $invoicesResponse;
    //             }

    //             $invoicesData = $invoicesResponse['QueryResponse']['Invoice'] ?? [];
    //             $allInvoices = $allInvoices->merge($invoicesData);

    //             $fetchedCount = count($invoicesData);
    //             $startPosition += $fetchedCount;
    //         } while ($fetchedCount === $maxResults);

    //         // Fetch all payments with pagination
    //         $allPayments = collect();
    //         $startPosition = 1;

    //         do {
    //             $query = "SELECT * FROM Payment STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
    //             $paymentsResponse = $this->qbController->runQuery($query);

    //             if ($paymentsResponse instanceof \Illuminate\Http\JsonResponse) {
    //                 return $paymentsResponse;
    //             }

    //             $paymentsData = $paymentsResponse['QueryResponse']['Payment'] ?? [];
    //             $allPayments = $allPayments->merge($paymentsData);

    //             $fetchedCount = count($paymentsData);
    //             $startPosition += $fetchedCount;
    //         } while ($fetchedCount === $maxResults);

    //         // Fetch items and accounts
    //         $itemsRaw = $this->qbController->runQuery("SELECT * FROM Item STARTPOSITION 1 MAXRESULTS 500");
    //         $accountsRaw = $this->qbController->runQuery("SELECT * FROM Account STARTPOSITION 1 MAXRESULTS 500");

    //         $itemsList = collect($itemsRaw['QueryResponse']['Item'] ?? []);
    //         $accountsList = collect($accountsRaw['QueryResponse']['Account'] ?? []);

    //         $itemsMap = $itemsList->keyBy(fn($it) => $it['Id'] ?? null)->toArray();
    //         $accountsMap = $accountsList->keyBy(fn($a) => $a['Id'] ?? null)->toArray();

    //         // Helper functions
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

    //         $detectAccountForSalesItem = function ($sid) use ($itemsMap, $accountsMap) {
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

    //         $parseInvoiceLine = function ($line) use ($detectAccountForSalesItem, $itemsMap, $accountsMap) {
    //             $out = [];
    //             $detailType = $line['DetailType'] ?? null;

    //             if (!empty($line['GroupLineDetail']) && !empty($line['GroupLineDetail']['Line'])) {
    //                 foreach ($line['GroupLineDetail']['Line'] as $child) {
    //                     if (!empty($child['SalesItemLineDetail'])) {
    //                         $sid = $child['SalesItemLineDetail'];
    //                         $acc = $detectAccountForSalesItem($sid);
    //                         $out[] = [
    //                             'DetailType' => $child['DetailType'] ?? 'SalesItemLineDetail',
    //                             'Description' => $child['Description'] ?? $sid['ItemRef']['name'] ?? null,
    //                             'Amount' => $child['Amount'] ?? 0,
    //                             'Quantity' => $sid['Qty'] ?? 1,
    //                             'ItemName' => $sid['ItemRef']['name'] ?? null,
    //                             'AccountId' => $acc['AccountId'],
    //                             'AccountName' => $acc['AccountName'],
    //                             'RawLine' => $child,
    //                             'HasProduct' => true,
    //                         ];
    //                     } else {
    //                         $out[] = [
    //                             'DetailType' => $child['DetailType'] ?? null,
    //                             'Description' => $child['Description'] ?? null,
    //                             'Amount' => $child['Amount'] ?? 0,
    //                             'Quantity' => 1,
    //                             'ItemName' => null,
    //                             'AccountId' => null,
    //                             'AccountName' => null,
    //                             'RawLine' => $child,
    //                             'HasProduct' => false,
    //                         ];
    //                     }
    //                 }
    //                 return $out;
    //             }

    //             if (!empty($line['SalesItemLineDetail'])) {
    //                 $sid = $line['SalesItemLineDetail'];
    //                 $acc = $detectAccountForSalesItem($sid);
    //                 $out[] = [
    //                     'DetailType' => $line['DetailType'] ?? 'SalesItemLineDetail',
    //                     'Description' => $line['Description'] ?? ($sid['ItemRef']['name'] ?? null),
    //                     'Amount' => $line['Amount'] ?? 0,
    //                     'Quantity' => $sid['Qty'] ?? 1,
    //                     'ItemName' => $sid['ItemRef']['name'] ?? null,
    //                     'AccountId' => $acc['AccountId'],
    //                     'AccountName' => $acc['AccountName'],
    //                     'RawLine' => $line,
    //                     'HasProduct' => true,
    //                 ];
    //                 return $out;
    //             }

    //             if (!empty($line['TaxLineDetail']) || stripos($detailType ?? '', 'Tax') !== false) {
    //                 $out[] = [
    //                     'DetailType' => $detailType,
    //                     'Description' => $line['Description'] ?? null,
    //                     'Amount' => $line['Amount'] ?? 0,
    //                     'Quantity' => 1,
    //                     'ItemName' => null,
    //                     'AccountId' => null,
    //                     'AccountName' => null,
    //                     'RawLine' => $line,
    //                     'HasProduct' => false,
    //                 ];
    //                 return $out;
    //             }

    //             $out[] = [
    //                 'DetailType' => $detailType,
    //                 'Description' => $line['Description'] ?? null,
    //                 'Amount' => $line['Amount'] ?? 0,
    //                 'Quantity' => 1,
    //                 'ItemName' => null,
    //                 'AccountId' => null,
    //                 'AccountName' => null,
    //                 'RawLine' => $line,
    //                 'HasProduct' => false,
    //             ];
    //             return $out;
    //         };

    //         $invoices = $allInvoices->map(function ($invoice) use ($parseInvoiceLine, $accountsMap, $arAccount, $taxAccount) {
    //             $parsedLines = [];
    //             foreach ($invoice['Line'] ?? [] as $line) {
    //                 $parsedLines = array_merge($parsedLines, $parseInvoiceLine($line));
    //             }

    //             $unmapped = array_values(array_filter($parsedLines, fn($l) => empty($l['AccountId']) && (float) $l['Amount'] != 0.0));

    //             $taxTotal = 0;
    //             if (!empty($invoice['TxnTaxDetail']['TotalTax']))
    //                 $taxTotal = $invoice['TxnTaxDetail']['TotalTax'];
    //             elseif (!empty($invoice['TotalTax']))
    //                 $taxTotal = $invoice['TotalTax'];

    //             $totalAmount = (float) ($invoice['TotalAmt'] ?? 0);

    //             $journalLines = [];

    //             if ($arAccount) {
    //                 $journalLines[] = [
    //                     'AccountId' => $arAccount['Id'],
    //                     'AccountName' => $arAccount['Name'],
    //                     'Debit' => $totalAmount,
    //                     'Credit' => 0.0,
    //                     'Note' => 'Accounts Receivable (invoice total)'
    //                 ];
    //             }

    //             foreach ($parsedLines as $pl) {
    //                 if ((float) $pl['Amount'] == 0.0)
    //                     continue;
    //                 if (empty($pl['AccountId']))
    //                     continue;
    //                 $journalLines[] = [
    //                     'AccountId' => $pl['AccountId'],
    //                     'AccountName' => $pl['AccountName'] ?? null,
    //                     'Debit' => 0.0,
    //                     'Credit' => (float) $pl['Amount'],
    //                     'Note' => $pl['Description'] ?? 'Sales / line item'
    //                 ];
    //             }

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

    //         $payments = $allPayments->map(function ($payment) {
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

    //         // Import logic
    //         $imported = 0;
    //         $skipped = 0;
    //         $failed = 0;

    //         DB::beginTransaction();
    //         try {
    //             foreach ($invoicesWithPayments as $qbInvoice) {
    //                 try {
    //                     $qbId = $qbInvoice['InvoiceId'];

    //                     // Check for duplicate
    //                     $existing = Invoice::where('invoice_id', $qbId)->first();
    //                     if ($existing) {
    //                         \Log::error("Invoice already exists: " .$qbId);
    //                         $skipped++;
    //                         continue;
    //                     }

    //                     // Map customer - find local customer by QB customer ID or name
    //                     $qbCustomerId = $qbInvoice['CustomerId'];
    //                     $qbCustomerName = $qbInvoice['CustomerName'];

    //                     $customer = null;
    //                     if ($qbCustomerId) {
    //                         $customer = Customer::where('customer_id', $qbCustomerId)
    //                             ->where('created_by', \Auth::user()->creatorId())
    //                             ->first();
    //                     }

    //                     if (!$customer && $qbCustomerName) {
    //                         $customer = Customer::where('name', $qbCustomerName)
    //                             ->where('created_by', \Auth::user()->creatorId())
    //                             ->first();
    //                     }

    //                     if (!$customer) {
    //                     \Log::error('Customer Not Found', [
    //                         'qb_customer_id'   => $qbCustomerId,
    //                         'qb_customer_name' => $qbCustomerName,
    //                         'creator_id'       => \Auth::user()->creatorId(),
    //                     ]);
    //                     $skipped++;
    //                     continue;
    //                 }


    //                     $customerId = $customer->id;

    //                     // Insert invoice
    //                     $invoice = Invoice::create([
    //                         'invoice_id' => $qbId,
    //                         'customer_id' => $customerId,
    //                         'issue_date' => $qbInvoice['TxnDate'],
    //                         'due_date' => $qbInvoice['DueDate'],
    //                         'ref_number' => $qbInvoice['DocNumber'],
    //                         'issue_date' => $qbInvoice['TxnDate'],
    //                         'send_date' => $qbInvoice['TxnDate'],
    //                         'due_date' => $qbInvoice['DueDate'],
    //                         'status' => 2,
    //                         'created_by' => \Auth::user()->creatorId(),
    //                         'owned_by' => \Auth::user()->ownedId(),
    //                     ]);

    //                     // Track total payments for customer balance update
    //                     $totalPayments = 0;

    //                     // Insert products
    //                     foreach ($qbInvoice['ParsedLines'] as $line) {
    //                         if ($line['HasProduct']) {
    //                             $itemName = $line['ItemName'];
    //                             if (!$itemName)
    //                                 continue;

    //                             $product = ProductService::where('name', $itemName)
    //                                 ->where('created_by', \Auth::user()->creatorId())
    //                                 ->first();

    //                             if (!$product) {
    //                                 // Create product if it doesn't exist
    //                                 $unit = ProductServiceUnit::firstOrCreate(
    //                                     ['name' => 'pcs'],
    //                                     ['created_by' => \Auth::user()->creatorId()]
    //                                 );

    //                                 $productCategory = ProductServiceCategory::firstOrCreate(
    //                                     [
    //                                         'name' => 'Product',
    //                                         'created_by' => \Auth::user()->creatorId(),
    //                                     ],
    //                                     [
    //                                         'color' => '#4CAF50',
    //                                         'type' => 'Product',
    //                                         'chart_account_id' => 0,
    //                                         'created_by' => \Auth::user()->creatorId(),
    //                                         'owned_by' => \Auth::user()->ownedId(),
    //                                     ]
    //                                 );

    //                                 $productData = [
    //                                     'name' => $itemName,
    //                                     'sku' => $itemName,
    //                                     'sale_price' => $line['Amount'] ?? 0,
    //                                     'purchase_price' => 0,
    //                                     'quantity' => 0,
    //                                     'unit_id' => $unit->id,
    //                                     'type' => 'product',
    //                                     'category_id' => $productCategory->id,
    //                                     'created_by' => \Auth::user()->creatorId(),
    //                                 ];

    //                                 // Map chart accounts if available
    //                                 if (!empty($line['AccountId'])) {
    //                                     $account = ChartOfAccount::where('code', $line['AccountId'])
    //                                         ->where('created_by', \Auth::user()->creatorId())
    //                                         ->first();
    //                                     if ($account) {
    //                                         $productData['sale_chartaccount_id'] = $account->id;
    //                                     }
    //                                 }

    //                                 $product = ProductService::create($productData);
    //                             }

    //                             InvoiceProduct::create([
    //                                 'invoice_id' => $invoice->id,
    //                                 'product_id' => $product->id,
    //                                 'quantity' => $line['Quantity'] ?? 1,
    //                                 'price' => $line['Amount'],
    //                                 'description' => $line['Description'],
    //                             ]);
    //                         }
    //                     }

    //                     // Insert payments
    //                     foreach ($qbInvoice['Payments'] as $payment) {
    //                         // Determine payment method
    //                         $paymentMethod = $payment['PaymentMethod'];

    //                         if (!$paymentMethod) {
    //                             if (isset($payment['RawPayment']['CreditCardPayment'])) {
    //                                 $paymentMethod = 'Credit Card';
    //                             } elseif (isset($payment['RawPayment']['CheckPayment'])) {
    //                                 $paymentMethod = 'Check';
    //                             } elseif (isset($payment['RawPayment']['DepositToAccountRef'])) {
    //                                 $accountId = $payment['RawPayment']['DepositToAccountRef']['value'];
    //                                 $account = collect($accountsList)->firstWhere('Id', $accountId);
    //                                 if ($account) {
    //                                     $accountType = strtolower($account['AccountType'] ?? '');
    //                                     if (strpos($accountType, 'bank') !== false || strpos($accountType, 'checking') !== false) {
    //                                         $paymentMethod = 'Bank Transfer';
    //                                     } elseif (strpos($accountType, 'credit') !== false) {
    //                                         $paymentMethod = 'Credit Card';
    //                                     } else {
    //                                         $paymentMethod = 'Cash';
    //                                     }
    //                                 } else {
    //                                     $paymentMethod = 'Cash';
    //                                 }
    //                             } else {
    //                                 $paymentMethod = 'Cash';
    //                             }
    //                         }

    //                         $paymentAmount = $payment['TotalAmount'] ?? 0;

    //                         InvoicePayment::create([
    //                             'invoice_id' => $invoice->id,
    //                             'date' => $payment['TxnDate'],
    //                             'amount' => $paymentAmount,
    //                             'account_id' => $accountId,
    //                             'payment_method' => $paymentMethod,
    //                             'txn_id' => $payment['PaymentId'],
    //                             'currency' => 'USD',
    //                             'reference' => $payment['PaymentId'],
    //                             'description' => 'Payment for Invoice ' . $qbInvoice['DocNumber'],
    //                         ]);

    //                         $totalPayments += $paymentAmount;
    //                     }

    //                     if (!empty($qbInvoice['Payments'])) {
    //                         $invoice->status = 4;
    //                         $invoice->send_date = $qbInvoice['TxnDate'];
    //                     }

    //                     $invoice->save();

    //                     // Update customer balance
    //                     if ($customer) {
    //                         // Credit: invoices increase customer's receivable balance
    //                         if ($qbInvoice['TotalAmount'] > 0) {
    //                             Utility::updateUserBalance('customer', $customer->id, $qbInvoice['TotalAmount'], 'credit');
    //                         }

    //                         // Debit: payments decrease customer's receivable balance
    //                         if ($totalPayments > 0) {
    //                             Utility::updateUserBalance('customer', $customer->id, $totalPayments, 'debit');
    //                         }
    //                     }

    //                     $imported++;

    //                 } catch (\Exception $e) {
    //                     \Log::error("Failed to import invoice {$qbId}: " . $e->getMessage());
    //                     $failed++;
    //                     continue;
    //                 }
    //             }

    //             DB::commit();
    //         } catch (\Exception $e) {
    //             DB::rollBack();
    //             \Log::error("Invoices import error: " . $e->getMessage());
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Import failed: ' . $e->getMessage(),
    //             ], 500);
    //         }

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => "Invoices import completed. Imported: {$imported}, Skipped: {$skipped}, Failed: {$failed}",
    //             'imported' => $imported,
    //             'skipped' => $skipped,
    //             'failed' => $failed,
    //         ]);

    //     } catch (\Exception $e) {
    //         \Log::error("Invoices import error: " . $e->getMessage());
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Error: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function scratchAnalyzeInvoicesPayments(Request $request)
    {
        try {
            // Fetch all invoices with pagination
            $allInvoices = collect();
            $startPosition = 1;
            $maxResults = 50;

            do {
                $query = "SELECT * FROM Invoice STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $invoicesResponse = $this->qbController->runQuery($query);

                if ($invoicesResponse instanceof \Illuminate\Http\JsonResponse) {
                    return $invoicesResponse;
                }

                $invoicesData = $invoicesResponse['QueryResponse']['Invoice'] ?? [];
                $allInvoices = $allInvoices->merge($invoicesData);

                $fetchedCount = count($invoicesData);
                $startPosition += $fetchedCount;
            } while ($fetchedCount === $maxResults);

            // Fetch all payments with pagination
            $allPayments = collect();
            $startPosition = 1;

            do {
                $query = "SELECT * FROM Payment STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $paymentsResponse = $this->qbController->runQuery($query);

                if ($paymentsResponse instanceof \Illuminate\Http\JsonResponse) {
                    return $paymentsResponse;
                }

                $paymentsData = $paymentsResponse['QueryResponse']['Payment'] ?? [];
                $allPayments = $allPayments->merge($paymentsData);

                $fetchedCount = count($paymentsData);
                $startPosition += $fetchedCount;
            } while ($fetchedCount === $maxResults);

            // Build comprehensive mapping
            $mappedData = $this->mapInvoicesWithPayments($allInvoices, $allPayments);

            return response()->json([
                'status' => 'success',
                'data' => $mappedData,
                'summary' => [
                    'total_invoices' => count($mappedData['invoices']),
                    'total_payments' => count($mappedData['payments']),
                    'total_allocations' => count($mappedData['allocations']),
                    'unpaid_invoices' => count(array_filter($mappedData['invoices'], fn($i) => $i['status'] === 'unpaid')),
                    'partially_paid_invoices' => count(array_filter($mappedData['invoices'], fn($i) => $i['status'] === 'partially_paid')),
                    'fully_paid_invoices' => count(array_filter($mappedData['invoices'], fn($i) => $i['status'] === 'fully_paid')),
                    'total_invoice_amount' => array_sum(array_map(fn($i) => $i['total_amount'], $mappedData['invoices'])),
                    'total_payments_amount' => array_sum(array_map(fn($p) => $p['total_amount'], $mappedData['payments'])),
                    'total_allocated_amount' => array_sum(array_map(fn($a) => $a['allocated_amount'], $mappedData['allocations'])),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error("Scratch analysis error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
    * Map invoices with their payments comprehensively
    * Creates a complete allocation map showing which payment paid which invoice
    */
    private function mapInvoicesWithPayments($allInvoices, $allPayments)
    {
        $invoicesMap = [];
        $paymentsMap = [];
        $allocations = []; // Mapping of payment -> invoice allocations

        // Step 1: Build invoices map
        foreach ($allInvoices as $invoice) {
            $invoiceId = (string) ($invoice['Id'] ?? null);
            $totalAmount = (float) ($invoice['TotalAmt'] ?? 0);

            $invoicesMap[$invoiceId] = [
                'invoice_id' => $invoiceId,
                'doc_number' => $invoice['DocNumber'] ?? null,
                'customer_id' => $invoice['CustomerRef']['value'] ?? null,
                'customer_name' => $invoice['CustomerRef']['name'] ?? null,
                'txn_date' => $invoice['TxnDate'] ?? null,
                'due_date' => $invoice['DueDate'] ?? null,
                'total_amount' => $totalAmount,
                'currency' => $invoice['CurrencyRef']['name'] ?? 'USD',
                'raw_data' => $invoice,
                'allocated_amount' => 0,
                'status' => 'unpaid', // Will be updated
                'allocations' => [], // Payment allocations for this invoice
            ];
        }

        // Step 2: Build payments map
        foreach ($allPayments as $payment) {
            $paymentId = (string) ($payment['Id'] ?? null);
            $totalAmount = (float) ($payment['TotalAmt'] ?? 0);

            // Extract linked invoices
            $linkedInvoices = [];
            foreach ($payment['Line'] ?? [] as $line) {
                if (!empty($line['LinkedTxn'])) {
                    $linked = is_array($line['LinkedTxn'][0] ?? null) ? $line['LinkedTxn'] : [$line['LinkedTxn']];
                    foreach ($linked as $txn) {
                        if (($txn['TxnType'] ?? null) === 'Invoice') {
                            $linkedInvoices[] = (string) $txn['TxnId'];
                        }
                    }
                }
            }

            $paymentsMap[$paymentId] = [
                'payment_id' => $paymentId,
                'customer_id' => $payment['CustomerRef']['value'] ?? null,
                'customer_name' => $payment['CustomerRef']['name'] ?? null,
                'txn_date' => $payment['TxnDate'] ?? null,
                'total_amount' => $totalAmount,
                'payment_method' => $payment['PaymentMethodRef']['name'] ?? null,
                'linked_invoices' => array_unique($linkedInvoices),
                'raw_data' => $payment,
                'allocated_amount' => 0, // Will be updated
            ];
        }

        // Step 3: Allocate payments to invoices intelligently
        $sortedPayments = collect($paymentsMap)->sortBy('txn_date')->toArray();

        foreach ($sortedPayments as $payment) {
            $paymentId = $payment['payment_id'];
            $remainingAmount = (float) $payment['total_amount'];
            $linkedInvoices = $payment['linked_invoices'];

            // Case 1: No linked invoices - create orphan allocation
            if (empty($linkedInvoices)) {
                $allocations[] = [
                    'payment_id' => $paymentId,
                    'invoice_id' => null,
                    'allocated_amount' => $remainingAmount,
                    'allocation_type' => 'orphan', // Payment with no invoice link
                    'reason' => 'No invoices linked to payment',
                    'payment_date' => $payment['txn_date'],
                ];
                $paymentsMap[$paymentId]['allocated_amount'] += $remainingAmount;
                continue;
            }

            // Case 2: Single linked invoice
            if (count($linkedInvoices) === 1) {
                $invId = $linkedInvoices[0];

                if (isset($invoicesMap[$invId])) {
                    $invoiceAmount = $invoicesMap[$invId]['total_amount'];
                    $alreadyAllocated = $invoicesMap[$invId]['allocated_amount'];
                    $remainingInvoiceAmount = max(0, $invoiceAmount - $alreadyAllocated);

                    $allocatedToThisInvoice = min($remainingAmount, $remainingInvoiceAmount);

                    $allocations[] = [
                        'payment_id' => $paymentId,
                        'invoice_id' => $invId,
                        'allocated_amount' => $allocatedToThisInvoice,
                        'allocation_type' => 'single_link',
                        'reason' => 'Direct payment to single invoice',
                        'payment_date' => $payment['txn_date'],
                    ];

                    $invoicesMap[$invId]['allocated_amount'] += $allocatedToThisInvoice;
                    $invoicesMap[$invId]['allocations'][] = [
                        'payment_id' => $paymentId,
                        'amount' => $allocatedToThisInvoice,
                        'date' => $payment['txn_date'],
                    ];

                    $paymentsMap[$paymentId]['allocated_amount'] += $allocatedToThisInvoice;
                    $remainingAmount -= $allocatedToThisInvoice;

                    // Overpayment
                    if ($remainingAmount > 0.01) {
                        $allocations[] = [
                            'payment_id' => $paymentId,
                            'invoice_id' => null,
                            'allocated_amount' => $remainingAmount,
                            'allocation_type' => 'overpayment',
                            'reason' => 'Overpayment on single invoice',
                            'payment_date' => $payment['txn_date'],
                        ];
                        $paymentsMap[$paymentId]['allocated_amount'] += $remainingAmount;
                    }
                }
            }
            // Case 3: Multiple linked invoices - allocate sequentially by invoice date
            else {
                $sortedLinkedInvoices = [];
                foreach ($linkedInvoices as $invId) {
                    if (isset($invoicesMap[$invId])) {
                        $sortedLinkedInvoices[] = [
                            'invoice_id' => $invId,
                            'txn_date' => $invoicesMap[$invId]['txn_date'],
                            'total_amount' => $invoicesMap[$invId]['total_amount'],
                            'allocated_amount' => $invoicesMap[$invId]['allocated_amount'],
                        ];
                    }
                }

                usort($sortedLinkedInvoices, fn($a, $b) => strcmp($a['txn_date'], $b['txn_date']));

                foreach ($sortedLinkedInvoices as $inv) {
                    if ($remainingAmount <= 0.01) {
                        break;
                    }

                    $invId = $inv['invoice_id'];
                    $invoiceAmount = $inv['total_amount'];
                    $alreadyAllocated = $inv['allocated_amount'];
                    $remainingInvoiceAmount = max(0, $invoiceAmount - $alreadyAllocated);

                    $allocatedToThisInvoice = min($remainingAmount, $remainingInvoiceAmount);

                    if ($allocatedToThisInvoice > 0.01) {
                        $allocations[] = [
                            'payment_id' => $paymentId,
                            'invoice_id' => $invId,
                            'allocated_amount' => $allocatedToThisInvoice,
                            'allocation_type' => 'multi_link_sequential',
                            'reason' => 'Sequential allocation from multi-linked payment',
                            'payment_date' => $payment['txn_date'],
                        ];

                        $invoicesMap[$invId]['allocated_amount'] += $allocatedToThisInvoice;
                        $invoicesMap[$invId]['allocations'][] = [
                            'payment_id' => $paymentId,
                            'amount' => $allocatedToThisInvoice,
                            'date' => $payment['txn_date'],
                        ];

                        $paymentsMap[$paymentId]['allocated_amount'] += $allocatedToThisInvoice;
                        $remainingAmount -= $allocatedToThisInvoice;
                    }
                }

                // Overpayment after all invoices
                if ($remainingAmount > 0.01) {
                    $allocations[] = [
                        'payment_id' => $paymentId,
                        'invoice_id' => null,
                        'allocated_amount' => $remainingAmount,
                        'allocation_type' => 'overpayment',
                        'reason' => 'Overpayment after sequential allocation',
                        'payment_date' => $payment['txn_date'],
                    ];
                    $paymentsMap[$paymentId]['allocated_amount'] += $remainingAmount;
                }
            }
        }

        // Step 4: Update invoice status
        foreach ($invoicesMap as $invId => &$invoice) {
            $totalAmount = $invoice['total_amount'];
            $allocatedAmount = $invoice['allocated_amount'];

            if ($allocatedAmount <= 0.01) {
                $invoice['status'] = 'unpaid';
            } elseif ($allocatedAmount >= $totalAmount - 0.01) {
                $invoice['status'] = 'fully_paid';
            } else {
                $invoice['status'] = 'partially_paid';
            }

            $invoice['remaining_balance'] = max(0, $totalAmount - $allocatedAmount);
        }

        return [
            'invoices' => array_values($invoicesMap),
            'payments' => array_values($paymentsMap),
            'allocations' => $allocations,
        ];
    }

    public function importInvoices(Request $request)
    {
        try {
            // Fetch all invoices with pagination
            $allInvoices = collect();
            $startPosition = 1;
            $maxResults = 50;

            do {
                $query = "SELECT * FROM Invoice STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $invoicesResponse = $this->qbController->runQuery($query);

                if ($invoicesResponse instanceof \Illuminate\Http\JsonResponse) {
                    return $invoicesResponse;
                }

                $invoicesData = $invoicesResponse['QueryResponse']['Invoice'] ?? [];
                $allInvoices = $allInvoices->merge($invoicesData);

                $fetchedCount = count($invoicesData);
                $startPosition += $fetchedCount;
            } while ($fetchedCount === $maxResults);

            // Fetch all payments with pagination
            $allPayments = collect();
            $startPosition = 1;

            do {
                $query = "SELECT * FROM Payment STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $paymentsResponse = $this->qbController->runQuery($query);

                if ($paymentsResponse instanceof \Illuminate\Http\JsonResponse) {
                    return $paymentsResponse;
                }

                $paymentsData = $paymentsResponse['QueryResponse']['Payment'] ?? [];
                $allPayments = $allPayments->merge($paymentsData);

                $fetchedCount = count($paymentsData);
                $startPosition += $fetchedCount;
            } while ($fetchedCount === $maxResults);

            // Fetch items and accounts
            $itemsRaw = $this->qbController->runQuery("SELECT * FROM Item STARTPOSITION 1 MAXRESULTS 500");
            $accountsRaw = $this->qbController->runQuery("SELECT * FROM Account STARTPOSITION 1 MAXRESULTS 500");

            $itemsList = collect($itemsRaw['QueryResponse']['Item'] ?? []);
            $accountsList = collect($accountsRaw['QueryResponse']['Account'] ?? []);

            $itemsMap = $itemsList->keyBy(fn($it) => $it['Id'] ?? null)->toArray();
            $accountsMap = $accountsList->keyBy(fn($a) => $a['Id'] ?? null)->toArray();

            // Get comprehensive mapping
            $mappedData = $this->mapInvoicesWithPayments($allInvoices, $allPayments);
            $invoicesData = collect($mappedData['invoices'])->keyBy('invoice_id')->toArray();
            $allocationsData = $mappedData['allocations'];

            // Helper functions for parsing
            $findARAccount = function () use ($accountsList) {
                $ar = $accountsList->first(fn($a) => isset($a['AccountType']) && strcasecmp($a['AccountType'], 'AccountsReceivable') === 0);
                if ($ar)
                    return ['Id' => $ar['Id'], 'Name' => $ar['Name'] ?? null];
                $ar = $accountsList->first(fn($a) => stripos($a['Name'] ?? '', 'receivable') !== false);
                return $ar ? ['Id' => $ar['Id'], 'Name' => $ar['Name'] ?? null] : null;
            };

            $findTaxPayableAccount = function () use ($accountsList) {
                $found = $accountsList->first(function ($a) {
                    if (isset($a['AccountType']) && strcasecmp($a['AccountType'], 'OtherCurrentLiability') === 0) {
                        return (stripos($a['Name'] ?? '', 'tax') !== false) || (stripos($a['Name'] ?? '', 'payable') !== false);
                    }
                    return false;
                });
                if ($found)
                    return ['Id' => $found['Id'], 'Name' => $found['Name'] ?? null];
                $found = $accountsList->first(fn($a) => stripos($a['Name'] ?? '', 'tax') !== false);
                return $found ? ['Id' => $found['Id'], 'Name' => $found['Name'] ?? null] : null;
            };

            $detectAccountForSalesItem = function ($sid) use ($itemsMap, $accountsMap) {
                if (!empty($sid['ItemAccountRef']['value'])) {
                    return [
                        'AccountId' => $sid['ItemAccountRef']['value'],
                        'AccountName' => $sid['ItemAccountRef']['name'] ?? ($accountsMap[$sid['ItemAccountRef']['value']]['Name'] ?? null)
                    ];
                }
                if (!empty($sid['ItemRef']['value'])) {
                    $itemId = $sid['ItemRef']['value'];
                    $item = $itemsMap[$itemId] ?? null;
                    if ($item) {
                        if (!empty($item['IncomeAccountRef']['value'])) {
                            return ['AccountId' => $item['IncomeAccountRef']['value'], 'AccountName' => $item['IncomeAccountRef']['name'] ?? ($accountsMap[$item['IncomeAccountRef']['value']]['Name'] ?? null)];
                        }
                        if (!empty($item['ExpenseAccountRef']['value'])) {
                            return ['AccountId' => $item['ExpenseAccountRef']['value'], 'AccountName' => $item['ExpenseAccountRef']['name'] ?? ($accountsMap[$item['ExpenseAccountRef']['value']]['Name'] ?? null)];
                        }
                        if (!empty($item['AssetAccountRef']['value'])) {
                            return ['AccountId' => $item['AssetAccountRef']['value'], 'AccountName' => $item['AssetAccountRef']['name'] ?? ($accountsMap[$item['AssetAccountRef']['value']]['Name'] ?? null)];
                        }
                    }
                }
                return ['AccountId' => null, 'AccountName' => null];
            };

            $parseInvoiceLine = function ($line) use ($detectAccountForSalesItem) {
                $out = [];
                $detailType = $line['DetailType'] ?? null;

                if (!empty($line['GroupLineDetail']) && !empty($line['GroupLineDetail']['Line'])) {
                    foreach ($line['GroupLineDetail']['Line'] as $child) {
                        if (!empty($child['SalesItemLineDetail'])) {
                            $sid = $child['SalesItemLineDetail'];
                            $acc = $detectAccountForSalesItem($sid);
                            $qty = (float) ($sid['Qty'] ?? 0);
                            if ($qty < 1) {
                                $qty = 1;
                            }
                            $out[] = [
                                'DetailType' => $child['DetailType'] ?? 'SalesItemLineDetail',
                                'Description' => $child['Description'] ?? $sid['ItemRef']['name'] ?? null,
                                'Amount' => $child['Amount'] ?? 0,
                                'Quantity' => $qty,
                                'ItemName' => $sid['ItemRef']['name'] ?? null,
                                'AccountId' => $acc['AccountId'],
                                'AccountName' => $acc['AccountName'],
                                'RawLine' => $child,
                                'HasProduct' => true,
                            ];
                        } else {
                            $out[] = [
                                'DetailType' => $child['DetailType'] ?? null,
                                'Description' => $child['Description'] ?? null,
                                'Amount' => $child['Amount'] ?? 0,
                                'Quantity' => 1,
                                'ItemName' => null,
                                'AccountId' => null,
                                'AccountName' => null,
                                'RawLine' => $child,
                                'HasProduct' => false,
                            ];
                        }
                    }
                    return $out;
                }

                if (!empty($line['SalesItemLineDetail'])) {
                    $sid = $line['SalesItemLineDetail'];
                    $acc = $detectAccountForSalesItem($sid);
                    $qty = (float) ($sid['Qty'] ?? 0);
                    if ($qty < 1) {
                        $qty = 1;
                    }
                    $out[] = [
                        'DetailType' => $line['DetailType'] ?? 'SalesItemLineDetail',
                        'Description' => $line['Description'] ?? ($sid['ItemRef']['name'] ?? null),
                        'Amount' => $line['Amount'] ?? 0,
                        'Quantity' => $qty,
                        'ItemName' => $sid['ItemRef']['name'] ?? null,
                        'AccountId' => $acc['AccountId'],
                        'AccountName' => $acc['AccountName'],
                        'RawLine' => $line,
                        'HasProduct' => true,
                    ];
                    return $out;
                }

                if (!empty($line['TaxLineDetail']) || stripos($detailType ?? '', 'Tax') !== false) {
                    $out[] = [
                        'DetailType' => $detailType,
                        'Description' => $line['Description'] ?? null,
                        'Amount' => $line['Amount'] ?? 0,
                        'Quantity' => 1,
                        'ItemName' => null,
                        'AccountId' => null,
                        'AccountName' => null,
                        'RawLine' => $line,
                        'HasProduct' => false,
                    ];
                    return $out;
                }

                $out[] = [
                    'DetailType' => $detailType,
                    'Description' => $line['Description'] ?? null,
                    'Amount' => $line['Amount'] ?? 0,
                    'Quantity' => 1,
                    'ItemName' => null,
                    'AccountId' => null,
                    'AccountName' => null,
                    'RawLine' => $line,
                    'HasProduct' => false,
                ];
                return $out;
            };

            $arAccount = $findARAccount();
            $taxAccount = $findTaxPayableAccount();

            // Import statistics
            $imported = 0;
            $skipped = 0;
            $failed = 0;
            $errors = [];

            DB::beginTransaction();
            try {
                // Process each invoice
                foreach ($invoicesData as $qbInvoiceData) {
                    try {
                        $qbId = $qbInvoiceData['invoice_id'];
                        $qbRawInvoice = $qbInvoiceData['raw_data'];

                        // Check for duplicate
                        $existing = Invoice::where('invoice_id', $qbId)
                            ->where('created_by', \Auth::user()->creatorId())
                            ->first();
                        if ($existing) {
                            \Log::warning("Invoice already exists: " . $qbId);
                            $skipped++;
                            continue;
                        }

                        // Find customer
                        $qbCustomerId = $qbInvoiceData['customer_id'] ?? null;
                        $qbCustomerName = $qbInvoiceData['customer_name'];

                        $customer = null;
                        if ($qbCustomerId) {
                            $customer = Customer::where('customer_id', $qbCustomerId)
                                ->where('created_by', \Auth::user()->creatorId())
                                ->first();
                        }

                        if (!$customer && $qbCustomerName) {
                            $customer = Customer::where('name', $qbCustomerName)
                                ->where('created_by', \Auth::user()->creatorId())
                                ->first();
                        }

                        if (!$customer) {
                            $errors[] = "Invoice {$qbId}: Customer not found ({$qbCustomerName})";
                            $skipped++;
                            continue;
                        }

                        $customerId = $customer->id;
                        $invoiceStatus = $qbInvoiceData['status'] === 'fully_paid' ? 4 : ($qbInvoiceData['status'] === 'partially_paid' ? 3 : 2);

                        // Create invoice
                        $invoice = Invoice::create([
                            'invoice_id'  => $qbId,
                            'customer_id' => $customerId,
                            'issue_date'  => $qbInvoiceData['txn_date'],
                            'due_date'    => $qbInvoiceData['due_date'],
                            'ref_number'  => $qbInvoiceData['doc_number'],
                            'send_date'   => $qbInvoiceData['txn_date'],
                            'status'      => $invoiceStatus,
                            'created_by'  => \Auth::user()->creatorId(),
                            'owned_by'    => \Auth::user()->ownedId(),
                            'created_at'  => Carbon::parse($qbInvoiceData['txn_date'])->format('Y-m-d H:i:s'),
                            'updated_at'  => Carbon::parse($qbInvoiceData['txn_date'])->format('Y-m-d H:i:s'),
                        ]);

                        // Parse and create invoice lines
                        $parsedLines = [];
                        foreach ($qbRawInvoice['Line'] ?? [] as $line) {
                            $parsedLines = array_merge($parsedLines, $parseInvoiceLine($line));
                        }

                        foreach ($parsedLines as $line) {
                            if ($line['HasProduct']) {
                                $itemName = $line['ItemName'];
                                if (!$itemName)
                                    continue;

                                $product = ProductService::where('name', $itemName)
                                    ->where('created_by', \Auth::user()->creatorId())
                                    ->first();

                                if (!$product) {
                                    $unit = ProductServiceUnit::firstOrCreate(
                                        ['name' => 'pcs'],
                                        ['created_by' => \Auth::user()->creatorId()]
                                    );

                                    $productCategory = ProductServiceCategory::firstOrCreate(
                                        [
                                            'name' => 'Product',
                                            'created_by' => \Auth::user()->creatorId(),
                                        ],
                                        [
                                            'color' => '#4CAF50',
                                            'type' => 'Product',
                                            'chart_account_id' => 0,
                                            'created_by' => \Auth::user()->creatorId(),
                                            'owned_by' => \Auth::user()->ownedId(),
                                        ]
                                    );

                                    $productData = [
                                        'name' => $itemName,
                                        'sku' => $itemName,
                                        'sale_price' => $line['Amount'] ?? 0,
                                        'purchase_price' => 0,
                                        'quantity' => 0,
                                        'unit_id' => $unit->id,
                                        'type' => 'product',
                                        'category_id' => $productCategory->id,
                                        'created_by' => \Auth::user()->creatorId(),
                                    ];

                                    if (!empty($line['AccountId'])) {
                                        $account = ChartOfAccount::where('code', $line['AccountId'])
                                            ->where('created_by', \Auth::user()->creatorId())
                                            ->first();
                                        if ($account) {
                                            $productData['sale_chartaccount_id'] = $account->id;
                                        }
                                    }

                                    $product = ProductService::create($productData);
                                }

                                $quantity = (float) ($line['Quantity'] ?? 1);
                                // if ($quantity < 1) {
                                //     $quantity = 1;
                                // }

                                InvoiceProduct::create([
                                    'invoice_id' => $invoice->id,
                                    'product_id' => $product->id,
                                    'quantity' => $quantity,
                                    'rate' => $line['Amount'],
                                    'price' => ($line['Amount'] / $quantity),
                                    'description' => $line['Description'],
                                ]);
                            }
                        }

                        // Create invoice payments based on allocations
                        $invoiceAllocations = array_filter($allocationsData, fn($a) => $a['invoice_id'] == $qbId);

                        foreach ($invoiceAllocations as $allocation) {
                            $paymentId = $allocation['payment_id'];
                            $allocatedAmount = $allocation['allocated_amount'];

                            // Find payment details
                            $paymentData = collect($allPayments)->firstWhere('Id', $paymentId);
                            if (!$paymentData) {
                                continue;
                            }

                            $bankAccountId = null;
                            $paymentMethod = $paymentData['PaymentMethodRef']['name'] ?? null;

                            if (!$paymentMethod) {
                                if (isset($paymentData['CreditCardPayment'])) {
                                    $paymentMethod = 'Credit Card';
                                } elseif (isset($paymentData['CheckPayment'])) {
                                    $paymentMethod = 'Check';
                                } elseif (isset($paymentData['DepositToAccountRef'])) {
                                    $depositAccountRef = json_decode(json_encode($paymentData['DepositToAccountRef'] ?? []), true);
                                    $accountCode = $depositAccountRef['value'] ?? null;
                                    $accountName = $depositAccountRef['name'] ?? 'Bank Account';

                                    $bankAccountId = $this->getOrCreateBankAccountFromChartAccount($accountCode, $accountName);

                                    if ($bankAccountId) {
                                        $accountId = $depositAccountRef['value'] ?? null;
                                        $account = collect($accountsMap)->firstWhere('Id', $accountId);
                                        
                                        if ($account) {
                                            $accountType = strtolower($account['AccountType'] ?? '');
                                            if (strpos($accountType, 'bank') !== false || strpos($accountType, 'checking') !== false) {
                                                $paymentMethod = 'Bank Transfer';
                                            } elseif (strpos($accountType, 'credit') !== false) {
                                                $paymentMethod = 'Credit Card';
                                            } else {
                                                $paymentMethod = 'Cash';
                                            }
                                        } else {
                                            $paymentMethod = 'Bank Transfer';
                                        }
                                    } else {
                                        $errors[] = "Invoice {$qbId}: Could not find bank account for payment {$paymentId}";
                                        continue;
                                    }
                                } else {
                                    $paymentMethod = 'Cash';
                                }
                            }

                            if (!$bankAccountId && !$paymentMethod) {
                                $errors[] = "Invoice {$qbId}: No bank account or payment method found for payment {$paymentId}";
                                continue;
                            }

                            // Create payment record
                            InvoicePayment::create([
                                'invoice_id' => $invoice->id,
                                'date' => $allocation['payment_date'],
                                'amount' => $allocatedAmount,
                                'account_id' => $bankAccountId,
                                'payment_method' => $paymentMethod,
                                'txn_id' => $paymentId,
                                'currency' => $qbInvoiceData['currency'],
                                'reference' => $paymentId,
                                'description' => 'Payment for Invoice ' . $qbInvoiceData['doc_number'],
                                'created_at'  => Carbon::parse($allocation['payment_date'])->format('Y-m-d H:i:s'),
                                'updated_at'  => Carbon::parse($allocation['payment_date'])->format('Y-m-d H:i:s'),
                            ]);

                            // Update bank account balance
                            if ($bankAccountId) {
                                Utility::bankAccountBalance($bankAccountId, $allocatedAmount, 'credit');
                            }
                        }

                        $invoice->save();

                        // Update customer balance
                        if ($customer) {
                            // Debit for invoice amount
                            Utility::updateUserBalance('customer', $customer->id, $qbInvoiceData['total_amount'], 'debit');

                            // Credit for paid amount
                            if ($qbInvoiceData['allocated_amount'] > 0) {
                                Utility::updateUserBalance('customer', $customer->id, $qbInvoiceData['allocated_amount'], 'credit');
                            }
                        }

                        $imported++;

                    } catch (\Exception $e) {
                        \Log::error("Failed to import invoice {$qbId}: " . $e->getMessage());
                        $errors[] = "Invoice {$qbId}: " . $e->getMessage();
                        $failed++;
                        continue;
                    }
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error("Invoices import transaction error: " . $e->getMessage());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Import transaction failed: ' . $e->getMessage(),
                    'errors' => $errors,
                ], 500);
            }

            return response()->json([
                'status' => 'success',
                'message' => "Invoices import completed successfully",
                'imported' => $imported,
                'skipped' => $skipped,
                'failed' => $failed,
                'errors' => $errors,
                'summary' => [
                    'total_invoices_processed' => $imported + $skipped + $failed,
                    'successfully_imported' => $imported,
                    'skipped_invoices' => $skipped,
                    'failed_invoices' => $failed,
                    'invoice_count' => count($invoicesData),
                    'allocation_count' => count($allocationsData),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error("Invoices import error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getOrCreateBankAccountFromChartAccount($accountCode, $accountName)
    {
        try {
            if (!$accountCode) {
                \Log::warning('getOrCreateBankAccountFromChartAccount called with empty accountCode');
                return null;
            }

            $creatorId = \Auth::user()->creatorId();
            $accountCode = trim($accountCode);

            $chartAccount = ChartOfAccount::withoutGlobalScopes()
                ->whereRaw("TRIM(code) = ?", [$accountCode])
                ->where('created_by', $creatorId)
                ->first();

            if (!$chartAccount) {
                $chartAccount = ChartOfAccount::withoutGlobalScopes()
                    ->whereRaw("CAST(TRIM(code) AS CHAR) = ?", [$accountCode])
                    ->where('created_by', $creatorId)
                    ->first();
            }

            if (!$chartAccount) {
                \Log::error('Chart of account not found in getOrCreateBankAccountFromChartAccount', [
                    'accountCode' => $accountCode,
                    'creator_id' => $creatorId,
                ]);
                return null;
            }

            $existingBankAccount = BankAccount::where('chart_account_id', $chartAccount->id)
                ->where('created_by', $creatorId)
                ->first();

            if ($existingBankAccount) {
                return $existingBankAccount->id;
            }

            $newBankAccount = BankAccount::create([
                'bank_name' => $accountName ?? $chartAccount->name,
                'account_number' => $accountCode,
                'opening_balance' => 0,
                'chart_account_id' => $chartAccount->id,
                'created_by' => $creatorId,
                'owned_by' => \Auth::user()->ownedId(),
            ]);

            \Log::info('Created bank account from chart account', [
                'bank_account_id' => $newBankAccount->id,
                'chart_account_id' => $chartAccount->id,
                'account_code' => $accountCode,
            ]);

            return $newBankAccount->id;
        } catch (\Throwable $e) {
            \Log::error('getOrCreateBankAccountFromChartAccount failed', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'accountCode' => $accountCode,
            ]);
            return null;
        }
    }

    public function processBankAccount($depositAccountRef)
    {
        try {
            if (empty($depositAccountRef)) {
                \Log::warning('processBankAccount called with empty $depositAccountRef');
                return null;
            }

            $rawValue = $depositAccountRef['value'] ?? null;
            if (is_array($rawValue)) {
                $qbAccountCode = reset($rawValue);
            } elseif (is_object($rawValue)) {
                $qbAccountCode = property_exists($rawValue, 'value') ? $rawValue->value : (string) $rawValue;
            } else {
                $qbAccountCode = (string) $rawValue;
            }

            $qbAccountCode = trim($qbAccountCode);
            if ($qbAccountCode === '') {
                \Log::warning('Empty qbAccountCode after normalization', ['depositAccountRef' => $depositAccountRef]);
                return null;
            }

            $qbAccountName = $depositAccountRef['name'] ?? 'Bank Account';
            $creatorId = \Auth::user()->creatorId();

            $chartAccount = ChartOfAccount::withoutGlobalScopes()
                ->whereRaw("TRIM(code) = ?", [$qbAccountCode])
                ->where('created_by', $creatorId)
                ->first();

            if (!$chartAccount) {
                $chartAccount = ChartOfAccount::withoutGlobalScopes()
                    ->whereRaw("CAST(TRIM(code) AS CHAR) = ?", [$qbAccountCode])
                    ->where('created_by', $creatorId)
                    ->first();
            }

            if (!$chartAccount) {
                \Log::error('Chart of account not found in processBankAccount', [
                    'qbAccountCode' => $qbAccountCode,
                    'creator_id' => $creatorId,
                    'depositAccountRef' => $depositAccountRef,
                    'db_connection' => \DB::getDefaultConnection(),
                ]);
                return null;
            }

            $bankAccount = BankAccount::where('chart_account_id', $chartAccount->id)
                ->where('created_by', $creatorId)
                ->first();

            if ($bankAccount) {
                $bankAccount->update([
                    'bank_name' => $chartAccount->name,
                    'account_number' => $qbAccountCode,
                ]);
                return $bankAccount->id;
            }

            $newBankAccount = BankAccount::create([
                'bank_name' => $qbAccountName,
                'account_number' => $qbAccountCode,
                'opening_balance' => 0,
                'chart_account_id' => $chartAccount->id,
                'created_by' => $creatorId,
                'owned_by' => \Auth::user()->ownedId(),
            ]);

            return $newBankAccount->id;
        } catch (\Throwable $e) {
            \Log::error('processBankAccount failed', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'depositAccountRef' => $depositAccountRef,
            ]);
            return null;
        }
    }
    public function customers()
    {
        try {
            $allCustomers = collect();
            $startPosition = 1;
            $maxResults = 50; // Adjust batch size as needed

            do {
                // Fetch paginated batch
                $query = "SELECT * FROM Customer WHERE Active IN (true, false) STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $customersResponse = $this->qbController->runQuery($query);

                // Handle API errors
                if ($customersResponse instanceof \Illuminate\Http\JsonResponse) {
                    return $customersResponse;
                }
                // Get customers from response
                $customersData = $customersResponse['QueryResponse']['Customer'] ?? [];

                // Merge entire objects (keep all keys)
                $allCustomers = $allCustomers->merge($customersData);

                // Move to next page
                $fetchedCount = count($customersData);
                $startPosition += $fetchedCount;
            } while ($fetchedCount === $maxResults); // continue if page is full

            // Import customers to local database
            $importedCount = 0;
            $updatedCount = 0;
            $errors = [];

            foreach ($allCustomers as $qbCustomer) {
                try {
                    $isActive = $qbCustomer['Active'];
                    if (is_string($isActive)) {
                        $isActive = strtolower($isActive) === 'true';
                    }
                    $existingCustomer = Customer::Where('customer_id', $qbCustomer['Id'] ?? null)
                        ->where('created_by', \Auth::user()->creatorId())->first();
                    if ($existingCustomer) {
                        \Log::warning("Exisitng Customers: '{$existingCustomer}'");
                        // Update existing customer
                        $existingCustomer->update([
                            'name' => $qbCustomer['Name'] ?? $qbCustomer['FullyQualifiedName'] ?? '',
                            'email' => $qbCustomer['PrimaryEmailAddr']['Address'] ?? null,
                            'contact' => $qbCustomer['PrimaryPhone']['FreeFormNumber'] ?? null,
                            'billing_name' => $qbCustomer['BillAddr']['Line1'] ?? null,
                            'billing_city' => $qbCustomer['BillAddr']['City'] ?? null,
                            'billing_state' => $qbCustomer['BillAddr']['CountrySubDivisionCode'] ?? null,
                            'billing_country' => $qbCustomer['BillAddr']['Country'] ?? null,
                            'billing_zip' => $qbCustomer['BillAddr']['PostalCode'] ?? null,
                            'billing_address' => implode(', ', array_filter([
                                $qbCustomer['BillAddr']['Line1'] ?? null,
                                $qbCustomer['BillAddr']['Line2'] ?? null,
                                $qbCustomer['BillAddr']['City'] ?? null,
                                $qbCustomer['BillAddr']['CountrySubDivisionCode'] ?? null,
                                $qbCustomer['BillAddr']['PostalCode'] ?? null,
                                $qbCustomer['BillAddr']['Country'] ?? null,
                            ])),
                            'shipping_name' => $qbCustomer['ShipAddr']['Line1'] ?? null,
                            'shipping_city' => $qbCustomer['ShipAddr']['City'] ?? null,
                            'shipping_state' => $qbCustomer['ShipAddr']['CountrySubDivisionCode'] ?? null,
                            'shipping_country' => $qbCustomer['ShipAddr']['Country'] ?? null,
                            'shipping_zip' => $qbCustomer['ShipAddr']['PostalCode'] ?? null,
                            'shipping_address' => implode(', ', array_filter([
                                $qbCustomer['ShipAddr']['Line1'] ?? null,
                                $qbCustomer['ShipAddr']['Line2'] ?? null,
                                $qbCustomer['ShipAddr']['City'] ?? null,
                                $qbCustomer['ShipAddr']['CountrySubDivisionCode'] ?? null,
                                $qbCustomer['ShipAddr']['PostalCode'] ?? null,
                                $qbCustomer['ShipAddr']['Country'] ?? null,
                            ])),
                            'is_active' => $isActive ? 1 : 0,
                            'qb_balance' => $qbCustomer['Balance'] ?? null,
                        ]);
                        $updatedCount++;
                    } else {
                        // Create new customer
                        $customer = Customer::create([
                            'customer_id' => $qbCustomer['Id'],
                            'name' => $qbCustomer['Name'] ?? $qbCustomer['FullyQualifiedName'] ?? '',
                            'email' => $qbCustomer['PrimaryEmailAddr']['Address'] ?? null,
                            'contact' => $qbCustomer['PrimaryPhone']['FreeFormNumber'] ?? null,
                            'is_active' => 1,
                            'created_by' => \Auth::user()->creatorId(),
                            'owned_by' => \Auth::user()->ownedId(),
                            'billing_name' => $qbCustomer['BillAddr']['Line1'] ?? null,
                            'billing_city' => $qbCustomer['BillAddr']['City'] ?? null,
                            'billing_state' => $qbCustomer['BillAddr']['CountrySubDivisionCode'] ?? null,
                            'billing_country' => $qbCustomer['BillAddr']['Country'] ?? null,
                            'billing_zip' => $qbCustomer['BillAddr']['PostalCode'] ?? null,
                            'billing_address' => implode(', ', array_filter([
                                $qbCustomer['BillAddr']['Line1'] ?? null,
                                $qbCustomer['BillAddr']['Line2'] ?? null,
                                $qbCustomer['BillAddr']['City'] ?? null,
                                $qbCustomer['BillAddr']['CountrySubDivisionCode'] ?? null,
                                $qbCustomer['BillAddr']['PostalCode'] ?? null,
                                $qbCustomer['BillAddr']['Country'] ?? null,
                            ])),
                            'shipping_name' => $qbCustomer['ShipAddr']['Line1'] ?? null,
                            'shipping_city' => $qbCustomer['ShipAddr']['City'] ?? null,
                            'shipping_state' => $qbCustomer['ShipAddr']['CountrySubDivisionCode'] ?? null,
                            'shipping_country' => $qbCustomer['ShipAddr']['Country'] ?? null,
                            'shipping_zip' => $qbCustomer['ShipAddr']['PostalCode'] ?? null,
                            'shipping_address' => implode(', ', array_filter([
                                $qbCustomer['ShipAddr']['Line1'] ?? null,
                                $qbCustomer['ShipAddr']['Line2'] ?? null,
                                $qbCustomer['ShipAddr']['City'] ?? null,
                                $qbCustomer['ShipAddr']['CountrySubDivisionCode'] ?? null,
                                $qbCustomer['ShipAddr']['PostalCode'] ?? null,
                                $qbCustomer['ShipAddr']['Country'] ?? null,
                            ])),
                            'is_active' => $isActive ? 1 : 0,
                            'qb_balance' => $qbCustomer['Balance'] ?? null,
                        ]);
                        $customer->save();
                        $importedCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error importing customer {$qbCustomer['Id']}: " . $e->getMessage();
                }
            }
            return response()->json([
                'status' => 'success',
                'message' => "Customers import completed. Imported: {$importedCount}, Updated: {$updatedCount}",
                'imported' => $importedCount,
                'updated' => $updatedCount,
                'errors' => $errors,
                'total_fetched' => $allCustomers->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function vendors()
    {
        try {
            $allVendors = collect();
            $startPosition = 1;
            $maxResults = 50; // Adjust batch size as needed

            do {
                // Fetch paginated batch
                $query = "SELECT * FROM Vendor STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $vendorsResponse = $this->qbController->runQuery($query);

                // Handle API errors
                if ($vendorsResponse instanceof \Illuminate\Http\JsonResponse) {
                    return $vendorsResponse;
                }
                // Get vendors from response
                $vendorsData = $vendorsResponse['QueryResponse']['Vendor'] ?? [];

                // Merge entire objects (keep all keys)
                $allVendors = $allVendors->merge($vendorsData);

                // Move to next page
                $fetchedCount = count($vendorsData);
                $startPosition += $fetchedCount;
            } while ($fetchedCount === $maxResults); // continue if page is full

            // Import vendors to local database
            $importedCount = 0;
            $updatedCount = 0;
            $errors = [];
            // dd($allVendors);
            foreach ($allVendors as $qbVendor) {
                try {
                    // Check if vendor already exists (by email)
                    $existingVendor = Vender::where('vender_id', $qbVendor['Id'] ?? null)
                        ->where('created_by', \Auth::user()->creatorId())->first();
                    \Log::warning("Exisitng Vendor: '{$existingVendor}'");
                    if ($existingVendor) {
                        // Update existing vendor
                        $existingVendor->update([
                            'name' => $qbVendor['Name'] ?? $qbVendor['DisplayName'] ?? '',
                            'email' => $qbVendor['PrimaryEmailAddr']['Address'] ?? null,
                            'contact' => $qbVendor['PrimaryPhone']['FreeFormNumber'] ?? null,
                            'billing_name' => $qbVendor['BillAddr']['Line1'] ?? null,
                            'billing_city' => $qbVendor['BillAddr']['City'] ?? null,
                            'billing_state' => $qbVendor['BillAddr']['CountrySubDivisionCode'] ?? null,
                            'billing_country' => $qbVendor['BillAddr']['Country'] ?? null,
                            'billing_zip' => $qbVendor['BillAddr']['PostalCode'] ?? null,
                            'billing_address' => implode(', ', array_filter([
                                $qbVendor['BillAddr']['Line1'] ?? null,
                                $qbVendor['BillAddr']['Line2'] ?? null,
                                $qbVendor['BillAddr']['City'] ?? null,
                                $qbVendor['BillAddr']['CountrySubDivisionCode'] ?? null,
                                $qbVendor['BillAddr']['PostalCode'] ?? null,
                                $qbVendor['BillAddr']['Country'] ?? null,
                            ])),
                            'shipping_name' => $qbVendor['ShipAddr']['Line1'] ?? null,
                            'shipping_city' => $qbVendor['ShipAddr']['City'] ?? null,
                            'shipping_state' => $qbVendor['ShipAddr']['CountrySubDivisionCode'] ?? null,
                            'shipping_country' => $qbVendor['ShipAddr']['Country'] ?? null,
                            'shipping_zip' => $qbVendor['ShipAddr']['PostalCode'] ?? null,
                            'shipping_address' => implode(', ', array_filter([
                                $qbVendor['ShipAddr']['Line1'] ?? null,
                                $qbVendor['ShipAddr']['Line2'] ?? null,
                                $qbVendor['ShipAddr']['City'] ?? null,
                                $qbVendor['ShipAddr']['CountrySubDivisionCode'] ?? null,
                                $qbVendor['ShipAddr']['PostalCode'] ?? null,
                                $qbVendor['ShipAddr']['Country'] ?? null,
                            ])),
                            'qb_balance' => $qbVendor['Balance'] ?? 0,
                        ]);
                        $updatedCount++;
                    } else {
                        // Create new vendor
                        $vender = Vender::create([
                            'vender_id' => $qbVendor['Id'],
                            'name' => $qbVendor['Name'] ?? $qbVendor['DisplayName'] ?? '',
                            'email' => $qbVendor['PrimaryEmailAddr']['Address'] ?? null,
                            'contact' => $qbVendor['PrimaryPhone']['FreeFormNumber'] ?? null,
                            'is_active' => 1,
                            'created_by' => \Auth::user()->creatorId(),
                            'owned_by' => \Auth::user()->ownedId(),
                            'billing_name' => $qbVendor['BillAddr']['Line1'] ?? null,
                            'billing_city' => $qbVendor['BillAddr']['City'] ?? null,
                            'billing_state' => $qbVendor['BillAddr']['CountrySubDivisionCode'] ?? null,
                            'billing_country' => $qbVendor['BillAddr']['Country'] ?? null,
                            'billing_zip' => $qbVendor['BillAddr']['PostalCode'] ?? null,
                            'billing_address' => implode(', ', array_filter([
                                $qbVendor['BillAddr']['Line1'] ?? null,
                                $qbVendor['BillAddr']['Line2'] ?? null,
                                $qbVendor['BillAddr']['City'] ?? null,
                                $qbVendor['BillAddr']['CountrySubDivisionCode'] ?? null,
                                $qbVendor['BillAddr']['PostalCode'] ?? null,
                                $qbVendor['BillAddr']['Country'] ?? null,
                            ])),
                            'shipping_name' => $qbVendor['ShipAddr']['Line1'] ?? null,
                            'shipping_city' => $qbVendor['ShipAddr']['City'] ?? null,
                            'shipping_state' => $qbVendor['ShipAddr']['CountrySubDivisionCode'] ?? null,
                            'shipping_country' => $qbVendor['ShipAddr']['Country'] ?? null,
                            'shipping_zip' => $qbVendor['ShipAddr']['PostalCode'] ?? null,
                            'shipping_address' => implode(', ', array_filter([
                                $qbVendor['ShipAddr']['Line1'] ?? null,
                                $qbVendor['ShipAddr']['Line2'] ?? null,
                                $qbVendor['ShipAddr']['City'] ?? null,
                                $qbVendor['ShipAddr']['CountrySubDivisionCode'] ?? null,
                                $qbVendor['ShipAddr']['PostalCode'] ?? null,
                                $qbVendor['ShipAddr']['Country'] ?? null,
                            ])),
                            'qb_balance' => $qbVendor['Balance'] ?? 0,
                        ]);
                        $vender->save();
                        $importedCount++;
                    }
                } catch (\Exception $e) {
                    \Log::warning("Error importing vendor {$qbVendor['Id']}:'". $e->getMessage());
                    $errors[] = "Error importing vendor {$qbVendor['Id']}: " . $e->getMessage();
                    
                }
            }
            return response()->json([
                'status' => 'success',
                'message' => "Vendors import completed. Imported: {$importedCount}, Updated: {$updatedCount}",
                'imported' => $importedCount,
                'updated' => $updatedCount,
                'errors' => $errors,
                'total_fetched' => $allVendors->count(),
            ]);

        } catch (\Exception $e) {
            \Log::warning("Error importing vendor:".$e);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function chartOfAccounts()
    {
        try {
            $allAccounts = collect();
            $startPosition = 1;
            $maxResults = 200;
            $importedCount = 0;

            // 🌀 Fetch all accounts from QuickBooks in batches
            do {
                $query = "SELECT * FROM Account WHERE Active IN (true,false)  STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $accountsResponse = $this->qbController->runQuery($query);

                if ($accountsResponse instanceof \Illuminate\Http\JsonResponse) {
                    return $accountsResponse;
                }

                $accountsData = $accountsResponse['QueryResponse']['Account'] ?? [];
                $allAccounts = $allAccounts->merge($accountsData);

                $fetchedCount = count($accountsData);
                $startPosition += $fetchedCount;
            } while ($fetchedCount === $maxResults);

            // Sort accounts numerically by ID
            $allAccounts = $allAccounts->sortBy(fn($a) => (int) $a['Id'])->values();

            // 🧭 Import each account
            foreach ($allAccounts as $account) {
                $localAccount = $this->ensureChartOfAccount(
                    $account['Name'] ?? '',
                    $account['Classification'] ?? '',
                    $account['AccountSubType'] ?? 'Other',
                    $account
                );

                if (!$localAccount) {
                    continue; // Skip unmapped or invalid accounts
                }

                // Handle parent relationship
                $parentId = 0;
                if (isset($account['ParentRef']['value'])) {
                    $parentQBCode = $account['ParentRef']['value'];
                    $parentAccount = ChartOfAccount::where('code', $parentQBCode)
                        ->where('created_by', auth()->user()->creatorId())
                        ->first();
                    // dd($parentAccount->id);
                    if ($parentAccount) {
                        $parentRecord = ChartOfAccountParent::firstOrCreate(
                            [
                                'name' => $parentAccount->name,
                                'created_by' => auth()->user()->creatorId(),
                                'sub_type' => $parentAccount->sub_type ?? null,
                                'type' => $parentAccount->type ?? null,
                                'account' => $parentAccount->id,
                            ]
                        );

                        $parentId = $parentRecord->id;
                    }
                }

                // Update QuickBooks-specific info
                $localAccount->code = $account['Id'] ?? '';
                $localAccount->parent = $parentId;
                $localAccount->description = $account['AccountType'] ?? null;
                $localAccount->is_enabled = 1;
                $localAccount->save();

                $importedCount++;
            }

            return response()->json([
                'status' => 'success',
                'count' => $allAccounts->count(),
                'imported' => $importedCount,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
    private function ensureChartOfAccount($fullName, $distributionAccountType, $detailType = 'Other', $qbAccountData = null)
    {
        // 🔹 Map QuickBooks account types to your system's main account categories
        $typeMapping = [
            // Liabilities
            'accounts payable (a/p)' => 'Liabilities',
            'accounts payable' => 'Liabilities',
            'credit card' => 'Liabilities',
            'long term liabilities' => 'Liabilities',
            'other current liabilities' => 'Liabilities',
            'loan payable' => 'Liabilities',
            'notes payable' => 'Liabilities',
            'board of equalization payable' => 'Liabilities',
            'Other Current Liability' => 'Liabilities',
            'Liability' => 'Liabilities',
            'liability' => 'Liabilities',

            // Assets
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
            'Asset' => 'Assets',
            'asset' => 'Assets',
            'Other Current Asset' => 'Assets',

            // Equity
            'equity' => 'Equity',
            'opening balance equity' => 'Equity',
            'retained earnings' => 'Equity',
            'equity' => 'Equity',
            'Equity' => 'Equity',

            // Income
            'income' => 'Income',
            'other income' => 'Income',
            'sales of product income' => 'Income',
            'service/fee income' => 'Income',
            'sales' => 'Income',
            'revenue' => 'Income',
            'Revenue' => 'Income',

            // COGS
            'cost of goods sold' => 'Costs of Goods Sold',
            'cogs' => 'Costs of Goods Sold',

            // Expenses
            'expenses' => 'Expenses',
            'expense' => 'Expenses',
            'Expense' => 'Expenses',
            'other expense' => 'Expenses',
            'marketing' => 'Expenses',
            'insurance' => 'Expenses',
            'utilities' => 'Expenses',
            'rent or lease' => 'Expenses',
            'meals and entertainment' => 'Expenses',
            'bank charges' => 'Expenses',
            'depreciation' => 'Expenses',
        ];

        $typeName = strtolower(trim($distributionAccountType));
        $creatorId = \Auth::user()->creatorId();

        if (!isset($typeMapping[$typeName])) {
            \Log::warning("Unmapped QuickBooks type: '{$distributionAccountType}' for account '{$fullName}'");
            dd($qbAccountData);
            return null; // Skip unmapped
        }

        // 🏷️ Create/find ChartOfAccountType
        $systemTypeName = $typeMapping[$typeName];
        $type = ChartOfAccountType::firstOrCreate(
            ['name' => $systemTypeName, 'created_by' => $creatorId]
        );

        // 🧩 Create/find SubType
        $subType = ChartOfAccountSubType::firstOrCreate(
            [
                'type' => $type->id,
                'name' => $detailType ?: 'Other',
                'created_by' => $creatorId,
            ]
        );

        // 🧾 Create/find ChartOfAccount
        $account = ChartOfAccount::firstOrCreate(
            [
                'name' => $fullName,
                'code' => $qbAccountData['Id'] ?? '',
                'description' => $qbAccountData['AccountType'] ?? null,
                'type' => $type->id,
                'sub_type' => $subType->id,
                'created_by' => $creatorId,
            ]
        );

        return $account;
    }
    private function mapProductAccounts($accountName, $qbAccountData = null)
    {
        $account = ChartOfAccount::where('name', $accountName)->first();
        if (!$account) {
            //create new custom account
            dd($accountName);
        }
        return $account;
    }
    public function items()
    {
        try {
            DB::beginTransaction();
            $allItems = collect();
            $startPosition = 1;
            $maxResults = 10;
            $importedCount = 0;

            do {
                // Fetch paginated batch
                $query = "SELECT * FROM Item STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $itemsResponse = $this->qbController->runQuery($query);

                // Handle API errors
                if ($itemsResponse instanceof \Illuminate\Http\JsonResponse) {
                    return $itemsResponse;
                }

                // Get items from response
                $itemsData = $itemsResponse['QueryResponse']['Item'] ?? [];

                // Merge entire objects (keep all keys)
                $allItems = $allItems->merge($itemsData);

                // Move to next page
                $fetchedCount = count($itemsData);
                $startPosition += $fetchedCount;
            } while ($fetchedCount === $maxResults); // continue if page is full
            // dd($allItems);
            // Import items into ProductService
            $unit = ProductServiceUnit::firstOrCreate(
                ['name' => 'pcs'],
                ['created_by' => auth()->user()->creatorId() ?? 2] // optional
            );
            $productCategory = ProductServiceCategory::firstOrCreate(
                [
                    'name' => 'Product',
                    'created_by' => \Auth::user()->creatorId(),
                ],
                [
                    'color' => '#4CAF50',
                    'type' => 'Product',
                    'chart_account_id' => 0,
                    'created_by' => \Auth::user()->creatorId(),
                    'owned_by' => \Auth::user()->ownedId(),
                ]
            );

            $serviceCategory = ProductServiceCategory::firstOrCreate(
                [
                    'name' => 'Service',
                    'created_by' => \Auth::user()->creatorId(),
                ],
                [
                    'color' => '#2196F3',
                    'type' => 'Service',
                    'chart_account_id' => 0,
                    'created_by' => \Auth::user()->creatorId(),
                    'owned_by' => \Auth::user()->ownedId(),
                ]
            );

            // 🟡 Step 2: Store IDs for reuse
            $productCategoryId = $productCategory->id;
            $serviceCategoryId = $serviceCategory->id;

            foreach ($allItems as $item) {
                $isInventory = strtolower($item['Type'] ?? '') === 'inventory';

                // Determine values based on type
                $type = $isInventory ? 'product' : 'service';
                $categoryId = $isInventory ? $productCategoryId : $serviceCategoryId;
                $productData = [
                    'name' => $item['Name'] ?? '',
                    'sku' => $item['Name'] ?? '',
                    'sale_price' => $item['UnitPrice'] ?? 0,
                    'purchase_price' => $item['PurchaseCost'] ?? 0,
                    'quantity' => $item['QtyOnHand'] ?? 0,
                    'unit_id' => $unit->id ?? 1,
                    'type' => $type,
                    'category_id' => $categoryId,
                    'created_by' => auth()->user()->creatorId(),
                ];

                // Map chart accounts if available
                // Map chart accounts if available (with database mapping)
                if (isset($item['IncomeAccountRef']['name'])) {
                    $incomeName = $item['IncomeAccountRef']['name'];
                    $incomeAccount = $this->mapProductAccounts($incomeName);
                    $productData['sale_chartaccount_id'] = $incomeAccount ? $incomeAccount->id : null;
                }

                if (isset($item['ExpenseAccountRef']['name'])) {
                    $expenseName = $item['ExpenseAccountRef']['name'];
                    $expenseAccount = $this->mapProductAccounts($expenseName);
                    $productData['expense_chartaccount_id'] = $expenseAccount ? $expenseAccount->id : null;
                }
                if (isset($item['AssetAccountRef']['name'])) {
                    $assetName = $item['AssetAccountRef']['name'];
                    $assetAccount = $this->mapProductAccounts($assetName);
                    $productData['asset_chartaccount_id'] = $assetAccount ? $assetAccount->id : null;
                }
                if (isset($item['COGSAccountRef']['name'])) {
                    $cogsName = $item['COGSAccountRef']['name'];
                    $cogsAccount = $this->mapProductAccounts($cogsName);
                    $productData['cogs_chartaccount_id'] = $cogsAccount ? $cogsAccount->id : null;
                }
                // dd($productData,$item);
                // Use updateOrCreate to avoid duplicates (based on name and created_by)
                ProductService::updateOrCreate(
                    ['name' => $productData['name'], 'created_by' => $productData['created_by']],
                    $productData
                );
                DB::commit();
                $importedCount++;
            }

            return response()->json([
                'status' => 'success',
                'count' => $allItems->count(),
                'imported' => $importedCount,
                'data' => $allItems->values(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    // public function importExpenses(Request $request)
    // {
    //     try {
    //         // Fetch all expenses with pagination
    //         $allExpenses = collect();
    //         $startPosition = 1;
    //         $maxResults = 50; // Adjust batch size as needed

    //         do {
    //             // Fetch paginated batch
    //             $query = "SELECT * FROM Purchase STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
    //             $expensesResponse = $this->qbController->runQuery($query);

    //             // Handle API errors
    //             if ($expensesResponse instanceof \Illuminate\Http\JsonResponse) {
    //                 return $expensesResponse;
    //             }

    //             // Get expenses from response
    //             $expensesData = $expensesResponse['QueryResponse']['Purchase'] ?? [];

    //             // Merge entire objects (keep all keys)
    //             $allExpenses = $allExpenses->merge($expensesData);

    //             // Move to next page
    //             $fetchedCount = count($expensesData);
    //             $startPosition += $fetchedCount;
    //         } while ($fetchedCount === $maxResults); // continue if page is full

    //         // Fetch all expense payments with pagination (using the same logic as expensesWithPayments)
    //         $allExpensePayments = collect();
    //         $typesToQuery = [
    //             'Payment',
    //             'Check',
    //             'BillPayment',
    //             'CreditCardCredit',
    //             'VendorCredit',
    //             'Deposit',
    //             'Purchase', // include as candidate payment
    //         ];

    //         foreach ($typesToQuery as $type) {
    //             try {
    //                 $startPosition = 1;
    //                 do {
    //                     $query = "SELECT * FROM {$type} STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
    //                     $paymentsResponse = $this->qbController->runQuery($query);

    //                     if ($paymentsResponse instanceof \Illuminate\Http\JsonResponse) {
    //                         continue;
    //                     }

    //                     $paymentsData = $paymentsResponse['QueryResponse'][$type] ?? [];
    //                     $allExpensePayments = $allExpensePayments->merge(collect($paymentsData));

    //                     $fetchedCount = count($paymentsData);
    //                     $startPosition += $fetchedCount;
    //                 } while ($fetchedCount === $maxResults);
    //             } catch (\Exception $e) {
    //                 \Log::warning("Failed to fetch {$type}: " . $e->getMessage());
    //             }
    //         }

    //         // Fetch items and accounts (these are usually smaller datasets)
    //         $itemsRaw = $this->qbController->runQuery("SELECT * FROM Item STARTPOSITION 1 MAXRESULTS 500");
    //         $accountsRaw = $this->qbController->runQuery("SELECT * FROM Account STARTPOSITION 1 MAXRESULTS 500");

    //         $itemsList = collect($itemsRaw['QueryResponse']['Item'] ?? []);
    //         $accountsList = collect($accountsRaw['QueryResponse']['Account'] ?? []);

    //         $itemsMap = $itemsList->keyBy(fn($it) => $it['Id'] ?? null)->toArray();
    //         $accountsMap = $accountsList->keyBy(fn($a) => $a['Id'] ?? null)->toArray();

    //         // Helper functions as in the original
    //         $findAPAccount = function () use ($accountsList) {
    //             $ap = $accountsList->first(fn($a) => isset($a['AccountType']) && strcasecmp($a['AccountType'], 'AccountsPayable') === 0);
    //             if ($ap)
    //                 return ['Id' => $ap['Id'], 'Name' => $ap['Name'] ?? null];
    //             $ap = $accountsList->first(fn($a) => stripos($a['Name'] ?? '', 'payable') !== false);
    //             return $ap ? ['Id' => $ap['Id'], 'Name' => $ap['Name'] ?? null] : null;
    //         };

    //         $apAccount = $findAPAccount();

    //         $detectAccountForExpenseItem = function ($sid) use ($itemsMap, $accountsMap) {
    //             if (!empty($sid['AccountRef']['value'])) {
    //                 return [
    //                     'AccountId' => $sid['AccountRef']['value'],
    //                     'AccountName' => $sid['AccountRef']['name'] ?? ($accountsMap[$sid['AccountRef']['value']]['Name'] ?? null)
    //                 ];
    //             }
    //             if (!empty($sid['ItemRef']['value'])) {
    //                 $itemId = $sid['ItemRef']['value'];
    //                 $item = $itemsMap[$itemId] ?? null;
    //                 if ($item) {
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

    //         $parseExpenseLine = function ($line) use ($detectAccountForExpenseItem, $itemsMap, $accountsMap) {
    //             $out = [];
    //             $detailType = $line['DetailType'] ?? null;

    //             if (!empty($line['GroupLineDetail']) && !empty($line['GroupLineDetail']['Line'])) {
    //                 foreach ($line['GroupLineDetail']['Line'] as $child) {
    //                     if (!empty($child['ItemBasedExpenseLineDetail'])) {
    //                         $sid = $child['ItemBasedExpenseLineDetail'];
    //                         $acc = $detectAccountForExpenseItem($sid);
    //                         $out[] = [
    //                             'DetailType' => $child['DetailType'] ?? 'ItemBasedExpenseLineDetail',
    //                             'Description' => $child['Description'] ?? $sid['ItemRef']['name'] ?? null,
    //                             'Amount' => $child['Amount'] ?? 0,
    //                             'AccountId' => $acc['AccountId'],
    //                             'AccountName' => $acc['AccountName'],
    //                             'RawLine' => $child,
    //                             'HasProduct' => true,
    //                         ];
    //                     } elseif (!empty($child['AccountBasedExpenseLineDetail'])) {
    //                         $accDetail = $child['AccountBasedExpenseLineDetail'];
    //                         $out[] = [
    //                             'DetailType' => $child['DetailType'] ?? 'AccountBasedExpenseLineDetail',
    //                             'Description' => $child['Description'] ?? null,
    //                             'Amount' => $child['Amount'] ?? 0,
    //                             'AccountId' => $accDetail['AccountRef']['value'] ?? null,
    //                             'AccountName' => $accDetail['AccountRef']['name'] ?? null,
    //                             'RawLine' => $child,
    //                             'HasProduct' => false,
    //                         ];
    //                     } else {
    //                         $out[] = [
    //                             'DetailType' => $child['DetailType'] ?? null,
    //                             'Description' => $child['Description'] ?? null,
    //                             'Amount' => $child['Amount'] ?? 0,
    //                             'AccountId' => null,
    //                             'AccountName' => null,
    //                             'RawLine' => $child,
    //                             'HasProduct' => false,
    //                         ];
    //                     }
    //                 }
    //                 return $out;
    //             }

    //             if (!empty($line['ItemBasedExpenseLineDetail'])) {
    //                 $sid = $line['ItemBasedExpenseLineDetail'];
    //                 $acc = $detectAccountForExpenseItem($sid);
    //                 $out[] = [
    //                     'DetailType' => $line['DetailType'] ?? 'ItemBasedExpenseLineDetail',
    //                     'Description' => $line['Description'] ?? ($sid['ItemRef']['name'] ?? null),
    //                     'Amount' => $line['Amount'] ?? 0,
    //                     'AccountId' => $acc['AccountId'],
    //                     'AccountName' => $acc['AccountName'],
    //                     'RawLine' => $line,
    //                     'HasProduct' => true,
    //                 ];
    //                 return $out;
    //             }

    //             if (!empty($line['AccountBasedExpenseLineDetail'])) {
    //                 $accDetail = $line['AccountBasedExpenseLineDetail'];
    //                 $out[] = [
    //                     'DetailType' => $line['DetailType'] ?? 'AccountBasedExpenseLineDetail',
    //                     'Description' => $line['Description'] ?? null,
    //                     'Amount' => $line['Amount'] ?? 0,
    //                     'AccountId' => $accDetail['AccountRef']['value'] ?? null,
    //                     'AccountName' => $accDetail['AccountRef']['name'] ?? null,
    //                     'RawLine' => $line,
    //                     'HasProduct' => false,
    //                 ];
    //                 return $out;
    //             }

    //             $out[] = [
    //                 'DetailType' => $detailType,
    //                 'Description' => $line['Description'] ?? null,
    //                 'Amount' => $line['Amount'] ?? 0,
    //                 'AccountId' => null,
    //                 'AccountName' => null,
    //                 'RawLine' => $line,
    //                 'HasProduct' => false,
    //             ];
    //             return $out;
    //         };

    //         // Helper: Extract & normalize LinkedTxn entries robustly
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

    //         // Helper: detect payment account and vendor info
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

    //         // Normalize all payments
    //         $normalizedPayments = $allExpensePayments->map(function ($raw) use ($extractLinkedTxn, $detectPaymentAccount) {
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

    //         // Normalize expenses
    //         $expenses = $allExpenses->map(function ($expense) use ($parseExpenseLine) {
    //             $parsedLines = [];
    //             foreach ($expense['Line'] ?? [] as $line) {
    //                 $parsedLines = array_merge($parsedLines, $parseExpenseLine($line));
    //             }

    //             $mainAccount = null;
    //             if (!empty($expense['AccountRef'])) {
    //                 $mainAccount = [
    //                     'Id' => $expense['AccountRef']['value'] ?? null,
    //                     'Name' => $expense['AccountRef']['name'] ?? null,
    //                 ];
    //             }

    //             return [
    //                 'ExpenseId' => $expense['Id'] ?? null,
    //                 'VendorName' => $expense['VendorRef']['name'] ?? ($expense['EntityRef']['name'] ?? null),
    //                 'VendorId' => $expense['VendorRef']['value'] ?? ($expense['EntityRef']['value'] ?? null),
    //                 'TxnDate' => $expense['TxnDate'] ?? null,
    //                 'TotalAmount' => (float) ($expense['TotalAmt'] ?? ($expense['Amount'] ?? 0)),
    //                 'Currency' => $expense['CurrencyRef']['name'] ?? null,
    //                 'Memo' => $expense['Memo'] ?? null,
    //                 'MainAccount' => $mainAccount,
    //                 'ParsedLines' => $parsedLines,
    //                 'Payments' => [],
    //                 'RawExpense' => $expense,
    //             ];
    //         });

    //         // Link payments to expenses (explicit LinkedTxn) + fuzzy fallback
    //         $expensesWithPayments = $expenses->map(function ($exp) use ($normalizedPayments) {
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

    //             $exp['Payments'] = $linkedExact;
    //             return $exp;
    //         });

    //         // Now, import logic - use Bill table for expenses
    //         $imported = 0;
    //         $skipped = 0;
    //         $failed = 0;
    //         dd($expensesWithPayments,$expensesWithPayments->last());
    //         DB::beginTransaction();
    //         try {
    //             foreach ($expensesWithPayments as $qbExpense) {
    //                 $qbId = $qbExpense['ExpenseId'];

    //                 // Check for duplicate
    //                 $existing = Bill::where('bill_id', $qbId)->first();
    //                 if ($existing) {
    //                     $skipped++;
    //                     continue;
    //                 }

    //                 // Map vendor_id - find local vendor by name from QuickBooks
    //                 $vendorName = $qbExpense['VendorName'];
    //                 $vendor = Vender::where('name', $vendorName)
    //                     ->where('created_by', \Auth::user()->creatorId())
    //                     ->first();

    //                 if (!$vendor) {
    //                     // Skip this expense if vendor doesn't exist in local DB
    //                     $skipped++;
    //                     continue;
    //                 }

    //                 $vendorId = $vendor->id;

    //                 // Insert expense as bill (type = 'Expense')
    //                 $bill = Bill::create([
    //                     'bill_id' => $qbId ?: 0, // Generate unique ID if QB ID is missing
    //                     'vender_id' => $vendorId,
    //                     'bill_date' => $qbExpense['TxnDate'],
    //                     'due_date' => $qbExpense['TxnDate'], // Same as bill date for expenses
    //                     'order_number' => $qbId, // Use expense ID as order number
    //                     'status' => 3, // default
    //                     'created_by' => \Auth::user()->creatorId(),
    //                     'owned_by' => \Auth::user()->ownedId(),
    //                     'type' => 'Expense', // Mark as expense type
    //                     'user_type' => 'Vendor'
    //                 ]);

    //                 // Process lines: products vs accounts
    //                 foreach ($qbExpense['ParsedLines'] as $line) {
    //                     if (empty($line['AccountId']))
    //                         continue; // Skip unmapped

    //                     if ($line['HasProduct']) {
    //                         // This is a product line - insert into bill_products
    //                         $itemName = $line['RawLine']['ItemBasedExpenseLineDetail']['ItemRef']['name'] ?? null;
    //                         if (!$itemName) continue;

    //                         $product = ProductService::where('name', $itemName)
    //                             ->where('created_by', \Auth::user()->creatorId())
    //                             ->first();

    //                         if (!$product) {
    //                             // Create product if it doesn't exist
    //                             $unit = ProductServiceUnit::firstOrCreate(
    //                                 ['name' => 'pcs'],
    //                                 ['created_by' => \Auth::user()->creatorId()]
    //                             );

    //                             $productCategory = ProductServiceCategory::firstOrCreate(
    //                                 [
    //                                     'name' => 'Product',
    //                                     'created_by' => \Auth::user()->creatorId(),
    //                                 ],
    //                                 [
    //                                     'color' => '#4CAF50',
    //                                     'type' => 'Product',
    //                                     'chart_account_id' => 0,
    //                                     'created_by' => \Auth::user()->creatorId(),
    //                                     'owned_by' => \Auth::user()->ownedId(),
    //                                 ]
    //                             );

    //                             $productData = [
    //                                 'name' => $itemName,
    //                                 'sku' => $itemName,
    //                                 'sale_price' => 0,
    //                                 'purchase_price' => $line['Amount'] ?? 0,
    //                                 'quantity' => 0,
    //                                 'unit_id' => $unit->id,
    //                                 'type' => 'product',
    //                                 'category_id' => $productCategory->id,
    //                                 'created_by' => \Auth::user()->creatorId(),
    //                             ];

    //                             // Map chart accounts if available
    //                             if (!empty($line['AccountId'])) {
    //                                 $account = ChartOfAccount::where('code', $line['AccountId'])
    //                                     ->where('created_by', \Auth::user()->creatorId())
    //                                     ->first();
    //                                 if ($account) {
    //                                     $productData['expense_chartaccount_id'] = $account->id;
    //                                 }
    //                             }

    //                             $product = ProductService::create($productData);
    //                         }

    //                         BillProduct::create([
    //                             'bill_id' => $bill->id,
    //                             'product_id' => $product->id,
    //                             'quantity' => $line['RawLine']['ItemBasedExpenseLineDetail']['Qty'] ?? 1,
    //                             'price' => $line['Amount'],
    //                             'description' => $line['Description'],
    //                             // tax, discount as needed
    //                         ]);
    //                     } else {
    //                         // This is an account line - insert into bill_accounts
    //                         $account = ChartOfAccount::where('code', $line['AccountId'])
    //                             ->where('created_by', \Auth::user()->creatorId())
    //                             ->first();

    //                         if ($account) {
    //                             BillAccount::create([
    //                                 'chart_account_id' => $account->id,
    //                                 'price' => $line['Amount'],
    //                                 'description' => $line['Description'],
    //                                 'type' => 'Expense',
    //                                 'ref_id' => $bill->id,
    //                             ]);
    //                         }
    //                     }
    //                 }

    //                 // Insert payments
    //                 foreach ($qbExpense['Payments'] as $payment) {
    //                     // Determine payment method based on payment data
    //                     $paymentMethod = $payment['TxnTypeRaw'] ?? 'Cash';

    //                     // Map account_id from QuickBooks payment account
    //                     $accountId = 0; // Default to 0
    //                     if ($payment['PaymentAccount'] && isset($payment['PaymentAccount']['Id'])) {
    //                         $qbAccountId = $payment['PaymentAccount']['Id'];
    //                         $localAccount = ChartOfAccount::where('code', $qbAccountId)
    //                             ->where('created_by', \Auth::user()->creatorId())
    //                             ->first();
    //                         if ($localAccount) {
    //                             $accountId = $localAccount->id;
    //                         }
    //                     }

    //                     BillPayment::create([
    //                         'bill_id' => $bill->id,
    //                         'date' => $payment['TxnDate'],
    //                         'amount' => $payment['TotalAmount'],
    //                         'account_id' => $accountId,
    //                         'payment_method' => $paymentMethod,
    //                         'reference' => $payment['PaymentId'],
    //                         'description' => 'QuickBooks Expense Payment',
    //                     ]);
    //                 }

    //                 if($qbExpense['Payments']->isNotEmpty()){
    //                     $bill->status = 4;
    //                     $bill->send_date = $qbExpense['TxnDate'];
    //                     $bill->save();
    //                 }

    //                 $imported++;
    //             }

    //             DB::commit();
    //         } catch (\Exception $e) {
    //             DB::rollBack();
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Import failed: ' . $e->getMessage(),
    //             ], 500);
    //         }

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => "Expenses import completed. Imported: {$imported}, Skipped: {$skipped}, Failed: {$failed}",
    //             'imported' => $imported,
    //             'skipped' => $skipped,
    //             'failed' => $failed,
    //         ]);

    //     } catch (\Exception $e) {
    //         dd($e);
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Error: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }
    ///
    // import expense previous
    // public function importExpenses(Request $request)
    // {
    //     try {
    //         // Fetch expenses with payments using existing function
    //         $response = $this->qbController->expensesWithPayments();

    //         // Decode JsonResponse safely
    //         if ($response instanceof \Illuminate\Http\JsonResponse) {
    //             $responseData = json_decode($response->getContent(), true);
    //         } else {
    //             $responseData = $response;
    //         }

    //         // Validate structure
    //         if (!is_array($responseData) || !isset($responseData['status']) || $responseData['status'] !== 'success') {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => $responseData['message'] ?? 'Failed to fetch expenses',
    //             ], 400);
    //         }

    //         // Now it's safe to access data
    //         $expensesData = collect($responseData['data'] ?? []);
    //         // dd($expensesData->first());
    //         // Fetch chart accounts for mapping
    //         $accountsRaw = $this->qbController->runQuery("SELECT * FROM Account STARTPOSITION 1 MAXRESULTS 500");
    //         $accountsList = collect($accountsRaw['QueryResponse']['Account'] ?? []);
    //         $accountsMap = $accountsList->keyBy(fn($a) => $a['Id'] ?? null)->toArray();

    //         // Counters
    //         $imported = 0;
    //         $skipped = 0;
    //         $failed = 0;

    //         DB::beginTransaction();
    //         try {
    //             foreach ($expensesData as $qbExpense) {
    //                 try {
    //                     $qbId = $qbExpense['ExpenseId'];

    //                     // Check for duplicate
    //                     $existing = Bill::where('bill_id', $qbId)->first();
    //                     if ($existing) {
    //                         $skipped++;
    //                         continue;
    //                     }

    //                     // Skip if no vendor (cannot link to system vendor)
    //                     $qbvendorId = $qbExpense['VendorId'] ?? null;
    //                     $vendor = null;

    //                     if ($qbvendorId) {
    //                         $vendor = Vender::where('vender_id', $qbvendorId)
    //                             ->where('created_by', \Auth::user()->creatorId())
    //                             ->first();
    //                     } 
                        

    //                     $vendorId = $vendor->id;

    //                     // Create bill record
    //                     $bill = Bill::create([
    //                         'bill_id' => $qbId ?: 0,
    //                         'vender_id' => $vendorId,
    //                         'bill_date' => $qbExpense['TxnDate'],
    //                         'due_date' => $qbExpense['TxnDate'],
    //                         'order_number' => $qbId,
    //                         'status' => 3,
    //                         'created_by' => \Auth::user()->creatorId(),
    //                         'owned_by' => \Auth::user()->ownedId(),
    //                         'type' => 'Expense',
    //                         'user_type' => 'Vendor'
    //                     ]);

    //                     // Process expense accounts from ExpenseAccounts array
    //                     if (!empty($qbExpense['ExpenseAccounts']) && is_array($qbExpense['ExpenseAccounts'])) {
    //                         foreach ($qbExpense['ExpenseAccounts'] as $expenseAccount) {
    //                             $accountQbId = $expenseAccount['Id'] ?? null;

    //                             if (!$accountQbId)
    //                                 continue;

    //                             // Find local chart account by QB ID
    //                             $account = ChartOfAccount::where('code', $accountQbId)
    //                                 ->where('created_by', \Auth::user()->creatorId())
    //                                 ->first();

    //                             if (!$account) {
    //                                 // Try to find by name
    //                                 $account = ChartOfAccount::where('name', $expenseAccount['Name'] ?? '')
    //                                     ->where('created_by', \Auth::user()->creatorId())
    //                                     ->first();
    //                             }

    //                             if ($account) {
    //                                 BillAccount::create([
    //                                     'bill_id' => $bill->id,
    //                                     'chart_account_id' => $account->id,
    //                                     'price' => $expenseAccount['Amount'] ?? 0,
    //                                     'description' => $expenseAccount['Description'] ?? '',
    //                                     'type' => 'Expense',
    //                                     'ref_id' => $bill->id,
    //                                 ]);
    //                             }
    //                         }
    //                     }

    //                     // Process payments if exist
    //                     $payments = $qbExpense['Payments'] ?? null;
    //                     if ($payments) {
    //                         // Handle if it's a Collection or array
    //                         $paymentsArray = $payments instanceof \Illuminate\Support\Collection
    //                             ? $payments->toArray()
    //                             : (is_array($payments) ? $payments : []);

    //                         if (!empty($paymentsArray)) {
    //                             foreach ($paymentsArray as $payment) {
    //                                 // Map payment account
    //                                 $accountId = 0;
    //                                 if (!empty($payment['PaymentAccount']['Id'])) {
    //                                     $qbAccountId = $payment['PaymentAccount']['Id'];
    //                                     $localAccount = ChartOfAccount::where('code', $qbAccountId)
    //                                         ->where('created_by', \Auth::user()->creatorId())
    //                                         ->first();

    //                                     if (!$localAccount) {
    //                                         $localAccount = ChartOfAccount::where('name', $payment['PaymentAccount']['Name'] ?? '')
    //                                             ->where('created_by', \Auth::user()->creatorId())
    //                                             ->first();
    //                                     }

    //                                     if ($localAccount) {
    //                                         $accountId = $localAccount->id;
    //                                     }
    //                                 }

    //                                 // Determine payment method
    //                                 $paymentMethod = $payment['TxnTypeRaw'] ?? 'Other';
    //                                 if (isset($payment['Raw']['PaymentType'])) {
    //                                     $paymentMethod = $payment['Raw']['PaymentType'];
    //                                 }

    //                                 BillPayment::create([
    //                                     'bill_id' => $bill->id,
    //                                     'date' => $payment['TxnDate'] ?? $qbExpense['TxnDate'],
    //                                     'amount' => $payment['TotalAmount'] ?? 0,
    //                                     'account_id' => $accountId,
    //                                     'payment_method' => $paymentMethod,
    //                                     'reference' => $payment['PaymentId'],
    //                                     'description' => 'QB Expense Payment',
    //                                 ]);
    //                             }

    //                             // Mark as paid if payments exist
    //                             $bill->status = 4;
    //                             $bill->send_date = $qbExpense['TxnDate'];
    //                             $bill->save();
    //                         }
    //                     }

    //                     $imported++;

    //                 } catch (\Exception $e) {
    //                     \Log::error("Failed to import expense {$qbId}: " . $e->getMessage());
    //                     $failed++;
    //                     continue;
    //                 }
    //             }

    //             DB::commit();

    //         } catch (\Exception $e) {
    //             DB::rollBack();
    //             \Log::error("Import transaction failed: " . $e->getMessage());
    //             throw $e;
    //         }

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => "Import completed. Imported: {$imported}, Skipped: {$skipped}, Failed: {$failed}",
    //             'imported' => $imported,
    //             'skipped' => $skipped,
    //             'failed' => $failed,
    //         ]);

    //     } catch (\Exception $e) {
    //         \Log::error("Import expenses error: " . $e->getMessage());
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Error: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function importExpenses(Request $request)
{
    try {
        // Fetch expenses with payments using existing function
        $response = $this->qbController->expensesWithPayments();

        // Decode JsonResponse safely
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $responseData = json_decode($response->getContent(), true);
        } else {
            $responseData = $response;
        }

        // Validate structure
        if (!is_array($responseData) || !isset($responseData['status']) || $responseData['status'] !== 'success') {
            return response()->json([
                'status' => 'error',
                'message' => $responseData['message'] ?? 'Failed to fetch expenses',
            ], 400);
        }

        // Fetch chart accounts for mapping
        $accountsRaw = $this->qbController->runQuery("SELECT * FROM Account STARTPOSITION 1 MAXRESULTS 500");
        $accountsList = collect($accountsRaw['QueryResponse']['Account'] ?? []);

        // Now it's safe to access data
        $expensesData = collect($responseData['data'] ?? []);

        // === Helper function to process and create bank accounts ===
        $processBankAccount = function ($paymentAccount) {
            if (empty($paymentAccount)) {
                return null;
            }

            $creatorId = \Auth::user()->creatorId();
            
            // Extract account ID and name from payment account
            $qbAccountCode = $paymentAccount['Id'] ?? null;
            $qbAccountName = $paymentAccount['Name'] ?? 'Bank Account';

            if (!$qbAccountCode) {
                return null;
            }

            // Check if chart of account exists with this code
            $chartAccount = ChartOfAccount::where('code', $qbAccountCode)
                ->where('created_by', $creatorId)
                ->first();

            if (!$chartAccount) {
                return null;
            }

            // Check if bank account already exists for this chart account
            $bankAccount = BankAccount::where('chart_account_id', $chartAccount->id)
                ->where('created_by', $creatorId)
                ->first();

            if ($bankAccount) {
                return $bankAccount->id;
            }

            // Create new bank account
            try {
                $newBankAccount = BankAccount::create([
                    'bank_name' => $qbAccountName,
                    'chart_account_id' => $chartAccount->id,
                    'created_by' => $creatorId,
                    'owned_by' => \Auth::user()->ownedId(),
                ]);

                return $newBankAccount->id;
            } catch (\Exception $e) {
                \Log::error("Failed to create bank account: " . $e->getMessage());
                return null;
            }
        };

        // === Helper: Get or create default cash account for non-bank payments ===
        $getDefaultCashAccount = function () {
            $creatorId = \Auth::user()->creatorId();
            
            // Try to find existing default cash account
            $existingBankAccount = BankAccount::where('created_by', $creatorId)
                ->where('bank_name', 'like', '%Default%Cash%')
                ->first();
            
            if ($existingBankAccount) {
                return $existingBankAccount->id;
            }

            // Try to find a cash chart account
            $cashChartAccount = ChartOfAccount::where('created_by', $creatorId)
                ->where('account_type', 'Cash')
                ->orWhere('name', 'like', '%Cash%')
                ->first();

            if ($cashChartAccount) {
                // Create default cash bank account for this chart account if not exists
                $bankAccount = BankAccount::firstOrCreate(
                    [
                        'chart_account_id' => $cashChartAccount->id,
                        'created_by' => $creatorId,
                    ],
                    [
                        'bank_name' => 'Default Cash Account',
                        'owned_by' => \Auth::user()->ownedId(),
                    ]
                );
                return $bankAccount->id;
            }

            // Create bank account without chart account
            try {
                $bankAccount = BankAccount::create([
                    'bank_name' => 'Default Cash Account',
                    'chart_account_id' => null,
                    'created_by' => $creatorId,
                    'owned_by' => \Auth::user()->ownedId(),
                ]);

                return $bankAccount->id;
            } catch (\Exception $e) {
                \Log::error("Failed to create default cash account: " . $e->getMessage());
                return null;
            }
        };

        // Counters
        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $defaultCashAccountId = null;

        DB::beginTransaction();
        try {
            foreach ($expensesData as $qbExpense) {
                try {
                    $qbId = $qbExpense['ExpenseId'];

                    // Check for duplicate
                    $existing = Bill::where('bill_id', $qbId)->first();
                    if ($existing) {
                        $skipped++;
                        continue;
                    }

                    // Get vendor
                    $qbvendorId = $qbExpense['VendorId'] ?? null;
                    $vendor = null;

                    if ($qbvendorId) {
                        $vendor = Vender::where('vender_id', $qbvendorId)
                            ->where('created_by', \Auth::user()->creatorId())
                            ->first();
                    }

                    if (!$vendor) {
                        $skipped++;
                        continue;
                    }

                    $vendorId = $vendor->id;

                    // Create bill record
                    $bill = Bill::create([
                        'bill_id' => $qbId ?: 0,
                        'vender_id' => $vendorId,
                        'bill_date' => $qbExpense['TxnDate'],
                        'due_date' => $qbExpense['TxnDate'],
                        'order_number' => $qbId,
                        'status' => 3,
                        'created_by' => \Auth::user()->creatorId(),
                        'owned_by' => \Auth::user()->ownedId(),
                        'type' => 'Expense',
                        'user_type' => 'Vendor',
                        'created_at'  => Carbon::parse($qbExpense['TxnDate'])->format('Y-m-d H:i:s'),
                        'updated_at'  => Carbon::parse($qbExpense['TxnDate'])->format('Y-m-d H:i:s'),
                    ]);

                    // Track total amount for vendor balance update
                    $totalAmount = 0;

                    // Process parsed lines (both products and accounts)
                    if (!empty($qbExpense['ParsedLines']) && is_array($qbExpense['ParsedLines'])) {
                        foreach ($qbExpense['ParsedLines'] as $line) {
                            if ($line['HasProduct']) {
                                // This is a product line
                                $itemName = $line['ItemName'];
                                if (!$itemName)
                                    continue;

                                $product = ProductService::where('name', $itemName)
                                    ->where('created_by', \Auth::user()->creatorId())
                                    ->first();

                                if (!$product) {
                                    // Create product if it doesn't exist
                                    $unit = ProductServiceUnit::firstOrCreate(
                                        ['name' => 'pcs'],
                                        ['created_by' => \Auth::user()->creatorId()]
                                    );

                                    $productCategory = ProductServiceCategory::firstOrCreate(
                                        [
                                            'name' => 'Product',
                                            'created_by' => \Auth::user()->creatorId(),
                                        ],
                                        [
                                            'color' => '#4CAF50',
                                            'type' => 'Product',
                                            'chart_account_id' => 0,
                                            'created_by' => \Auth::user()->creatorId(),
                                            'owned_by' => \Auth::user()->ownedId(),
                                        ]
                                    );

                                    $productData = [
                                        'name' => $itemName,
                                        'sku' => $itemName,
                                        'sale_price' => 0,
                                        'purchase_price' => $line['Amount'] ?? 0,
                                        'quantity' => 0,
                                        'unit_id' => $unit->id,
                                        'type' => 'product',
                                        'category_id' => $productCategory->id,
                                        'created_by' => \Auth::user()->creatorId(),
                                    ];

                                    // Map chart accounts if available
                                    if (!empty($line['AccountId'])) {
                                        $account = ChartOfAccount::where('code', $line['AccountId'])
                                            ->where('created_by', \Auth::user()->creatorId())
                                            ->first();
                                        if ($account) {
                                            $productData['expense_chartaccount_id'] = $account->id;
                                        }
                                    }

                                    $product = ProductService::create($productData);
                                }

                                BillProduct::create([
                                    'bill_id' => $bill->id,
                                    'product_id' => $product->id,
                                    'quantity' => $line['Quantity'] ?? 1,
                                    'price' => $line['Amount'],
                                    'description' => $line['Description'],
                                ]);

                                $totalAmount += $line['Amount'];

                            } else {
                                // This is an account line
                                if (empty($line['AccountId']))
                                    continue;

                                $account = ChartOfAccount::where('code', $line['AccountId'])
                                    ->where('created_by', \Auth::user()->creatorId())
                                    ->first();

                                if (!$account) {
                                    $account = ChartOfAccount::where('name', $line['AccountName'] ?? '')
                                        ->where('created_by', \Auth::user()->creatorId())
                                        ->first();
                                }

                                if ($account) {
                                    BillAccount::create([
                                        'bill_id' => $bill->id,
                                        'chart_account_id' => $account->id,
                                        'price' => $line['Amount'] ?? 0,
                                        'description' => $line['Description'] ?? '',
                                        'type' => 'Expense',
                                        'ref_id' => $bill->id,
                                    ]);

                                    $totalAmount += $line['Amount'];
                                }
                            }
                        }
                    }

                    // Track total payments for vendor balance update
                    $totalPayments = 0;

                    // Process payments if exist
                    $payments = $qbExpense['Payments'] ?? null;
                    if ($payments) {
                        $paymentsArray = $payments instanceof \Illuminate\Support\Collection
                            ? $payments->toArray()
                            : (is_array($payments) ? $payments : []);

                        if (!empty($paymentsArray)) {
                            foreach ($paymentsArray as $payment) {
                                // Process bank account from payment
                                $bankAccountId = null;
                                
                                // First try to use BankAccountId if already set by expensesWithPayments
                                if (!empty($payment['BankAccountId'])) {
                                    $bankAccountId = $payment['BankAccountId'];
                                } else {
                                    // Fallback: Process payment account
                                    $paymentAccount = $payment['PaymentAccount'] ?? null;
                                    
                                    if ($paymentAccount) {
                                        $bankAccountId = $processBankAccount($paymentAccount);
                                    }

                                    // If still no bank account, use default cash account
                                    if (!$bankAccountId) {
                                        if (!$defaultCashAccountId) {
                                            $defaultCashAccountId = $getDefaultCashAccount();
                                        }
                                        $bankAccountId = $defaultCashAccountId;
                                    }
                                }

                                // Determine payment method
                                $paymentMethod = $payment['TxnTypeRaw'] ?? 'Other';
                                if (isset($payment['Raw']['PaymentType'])) {
                                    $paymentMethod = $payment['Raw']['PaymentType'];
                                }

                                $paymentAmount = $payment['TotalAmount'] ?? 0;

                                BillPayment::create([
                                    'bill_id' => $bill->id,
                                    'date' => $payment['TxnDate'] ?? $qbExpense['TxnDate'],
                                    'amount' => $paymentAmount,
                                    'account_id' => $bankAccountId,
                                    'payment_method' => $paymentMethod,
                                    'reference' => $payment['PaymentId'],
                                    'description' => 'QB Expense Payment',
                                    'created_at'  => Carbon::parse($payment['TxnDate'] ?? $qbExpense['TxnDate'])->format('Y-m-d H:i:s'),
                                    'updated_at'  => Carbon::parse($payment['TxnDate'] ?? $qbExpense['TxnDate'])->format('Y-m-d H:i:s'),
                                ]);

                                $totalPayments += $paymentAmount;
                                
                                if ($bankAccountId) {
                                    Utility::bankAccountBalance($bankAccountId, $paymentAmount, 'debit');
                                }
                            }

                            // Mark as paid if payments exist
                            $bill->status = 4;
                            $bill->send_date = $qbExpense['TxnDate'];
                            $bill->save();
                        }
                    }

                    // Update vendor balance
                    if ($vendor) {
                        // Debit: expenses increase vendor's liability
                        if ($totalAmount > 0) {
                            Utility::updateUserBalance('vendor', $vendor->id, $totalAmount, 'debit');
                        }

                        // Credit: payments decrease vendor's liability
                        if ($totalPayments > 0) {
                            Utility::updateUserBalance('vendor', $vendor->id, $totalPayments, 'credit');
                        }
                    }

                    $imported++;

                } catch (\Exception $e) {
                    \Log::error("Failed to import expense {$qbId}: " . $e->getMessage());
                    $failed++;
                    continue;
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Import transaction failed: " . $e->getMessage());
            throw $e;
        }

        return response()->json([
            'status' => 'success',
            'message' => "Import completed. Imported: {$imported}, Skipped: {$skipped}, Failed: {$failed}",
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
        ]);

    } catch (\Exception $e) {
        \Log::error("Import expenses error: " . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Error: ' . $e->getMessage(),
        ], 500);
    }
}
    // import bills previous
    // public function importBills(Request $request)
    // {
    //     try {
    //         // Fetch all bills with pagination
    //         $allBills = collect();
    //         $startPosition = 1;
    //         $maxResults = 50; // Adjust batch size as needed

    //         do {
    //             // Fetch paginated batch
    //             $query = "SELECT * FROM Bill STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
    //             $billsResponse = $this->qbController->runQuery($query);

    //             // Handle API errors
    //             if ($billsResponse instanceof \Illuminate\Http\JsonResponse) {
    //                 return $billsResponse;
    //             }

    //             // Get bills from response
    //             $billsData = $billsResponse['QueryResponse']['Bill'] ?? [];

    //             // Merge entire objects (keep all keys)
    //             $allBills = $allBills->merge($billsData);

    //             // Move to next page
    //             $fetchedCount = count($billsData);
    //             $startPosition += $fetchedCount;
    //         } while ($fetchedCount === $maxResults); // continue if page is full

    //         // Fetch all bill payments with pagination
    //         $allBillPayments = collect();
    //         $startPosition = 1;

    //         do {
    //             // Fetch paginated batch
    //             $query = "SELECT * FROM BillPayment STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
    //             $billPaymentsResponse = $this->qbController->runQuery($query);

    //             // Handle API errors
    //             if ($billPaymentsResponse instanceof \Illuminate\Http\JsonResponse) {
    //                 return $billPaymentsResponse;
    //             }

    //             // Get bill payments from response
    //             $billPaymentsData = $billPaymentsResponse['QueryResponse']['BillPayment'] ?? [];

    //             // Merge entire objects (keep all keys)
    //             $allBillPayments = $allBillPayments->merge($billPaymentsData);

    //             // Move to next page
    //             $fetchedCount = count($billPaymentsData);
    //             $startPosition += $fetchedCount;
    //         } while ($fetchedCount === $maxResults); // continue if page is full

    //         // Fetch items and accounts (these are usually smaller datasets)
    //         $itemsRaw = $this->qbController->runQuery("SELECT * FROM Item STARTPOSITION 1 MAXRESULTS 500");
    //         $accountsRaw = $this->qbController->runQuery("SELECT * FROM Account STARTPOSITION 1 MAXRESULTS 500");

    //         $itemsList = collect($itemsRaw['QueryResponse']['Item'] ?? []);
    //         $accountsList = collect($accountsRaw['QueryResponse']['Account'] ?? []);

    //         $itemsMap = $itemsList->keyBy(fn($it) => $it['Id'] ?? null)->toArray();
    //         $accountsMap = $accountsList->keyBy(fn($a) => $a['Id'] ?? null)->toArray();

    //         // Helper functions as in the original
    //         $findAPAccount = function () use ($accountsList) {
    //             $ap = $accountsList->first(fn($a) => isset($a['AccountType']) && strcasecmp($a['AccountType'], 'AccountsPayable') === 0);
    //             if ($ap)
    //                 return ['Id' => $ap['Id'], 'Name' => $ap['Name'] ?? null];
    //             $ap = $accountsList->first(fn($a) => stripos($a['Name'] ?? '', 'payable') !== false);
    //             return $ap ? ['Id' => $ap['Id'], 'Name' => $ap['Name'] ?? null] : null;
    //         };

    //         $apAccount = $findAPAccount();

    //         $detectAccountForExpenseItem = function ($sid) use ($itemsMap, $accountsMap) {
    //             if (!empty($sid['AccountRef']['value'])) {
    //                 return [
    //                     'AccountId' => $sid['AccountRef']['value'],
    //                     'AccountName' => $sid['AccountRef']['name'] ?? ($accountsMap[$sid['AccountRef']['value']]['Name'] ?? null)
    //                 ];
    //             }
    //             if (!empty($sid['ItemRef']['value'])) {
    //                 $itemId = $sid['ItemRef']['value'];
    //                 $item = $itemsMap[$itemId] ?? null;
    //                 if ($item) {
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

    //         $parseBillLine = function ($line) use ($detectAccountForExpenseItem, $itemsMap, $accountsMap) {
    //             $out = [];
    //             $detailType = $line['DetailType'] ?? null;

    //             if (!empty($line['GroupLineDetail']) && !empty($line['GroupLineDetail']['Line'])) {
    //                 foreach ($line['GroupLineDetail']['Line'] as $child) {
    //                     if (!empty($child['ItemBasedExpenseLineDetail'])) {
    //                         $sid = $child['ItemBasedExpenseLineDetail'];
    //                         $acc = $detectAccountForExpenseItem($sid);
    //                         $out[] = [
    //                             'DetailType' => $child['DetailType'] ?? 'ItemBasedExpenseLineDetail',
    //                             'Description' => $child['Description'] ?? $sid['ItemRef']['name'] ?? null,
    //                             'Amount' => $child['Amount'] ?? 0,
    //                             'AccountId' => $acc['AccountId'],
    //                             'AccountName' => $acc['AccountName'],
    //                             'RawLine' => $child,
    //                             'HasProduct' => true,
    //                         ];
    //                     } elseif (!empty($child['AccountBasedExpenseLineDetail'])) {
    //                         $accDetail = $child['AccountBasedExpenseLineDetail'];
    //                         $out[] = [
    //                             'DetailType' => $child['DetailType'] ?? 'AccountBasedExpenseLineDetail',
    //                             'Description' => $child['Description'] ?? null,
    //                             'Amount' => $child['Amount'] ?? 0,
    //                             'AccountId' => $accDetail['AccountRef']['value'] ?? null,
    //                             'AccountName' => $accDetail['AccountRef']['name'] ?? null,
    //                             'RawLine' => $child,
    //                             'HasProduct' => false,
    //                         ];
    //                     } else {
    //                         $out[] = [
    //                             'DetailType' => $child['DetailType'] ?? null,
    //                             'Description' => $child['Description'] ?? null,
    //                             'Amount' => $child['Amount'] ?? 0,
    //                             'AccountId' => null,
    //                             'AccountName' => null,
    //                             'RawLine' => $child,
    //                             'HasProduct' => false,
    //                         ];
    //                     }
    //                 }
    //                 return $out;
    //             }

    //             if (!empty($line['ItemBasedExpenseLineDetail'])) {
    //                 $sid = $line['ItemBasedExpenseLineDetail'];
    //                 $acc = $detectAccountForExpenseItem($sid);
    //                 $out[] = [
    //                     'DetailType' => $line['DetailType'] ?? 'ItemBasedExpenseLineDetail',
    //                     'Description' => $line['Description'] ?? ($sid['ItemRef']['name'] ?? null),
    //                     'Amount' => $line['Amount'] ?? 0,
    //                     'AccountId' => $acc['AccountId'],
    //                     'AccountName' => $acc['AccountName'],
    //                     'RawLine' => $line,
    //                     'HasProduct' => true,
    //                 ];
    //                 return $out;
    //             }

    //             if (!empty($line['AccountBasedExpenseLineDetail'])) {
    //                 $accDetail = $line['AccountBasedExpenseLineDetail'];
    //                 $out[] = [
    //                     'DetailType' => $line['DetailType'] ?? 'AccountBasedExpenseLineDetail',
    //                     'Description' => $line['Description'] ?? null,
    //                     'Amount' => $line['Amount'] ?? 0,
    //                     'AccountId' => $accDetail['AccountRef']['value'] ?? null,
    //                     'AccountName' => $accDetail['AccountRef']['name'] ?? null,
    //                     'RawLine' => $line,
    //                     'HasProduct' => false,
    //                 ];
    //                 return $out;
    //             }

    //             $out[] = [
    //                 'DetailType' => $detailType,
    //                 'Description' => $line['Description'] ?? null,
    //                 'Amount' => $line['Amount'] ?? 0,
    //                 'AccountId' => null,
    //                 'AccountName' => null,
    //                 'RawLine' => $line,
    //                 'HasProduct' => false,
    //             ];
    //             return $out;
    //         };

    //         $bills = $allBills->map(function ($bill) use ($parseBillLine, $accountsMap, $apAccount) {
    //             $parsedLines = [];
    //             foreach ($bill['Line'] ?? [] as $line) {
    //                 $parsedLines = array_merge($parsedLines, $parseBillLine($line));
    //             }

    //             return [
    //                 'BillId' => (string) ($bill['Id'] ?? null),
    //                 'Id' => $bill['Id'] ?? null,
    //                 'DocNumber' => $bill['DocNumber'] ?? null,
    //                 'VendorName' => $bill['VendorRef']['name'] ?? null,
    //                 'VendorId' => $bill['VendorRef']['value'] ?? null,
    //                 'TxnDate' => $bill['TxnDate'] ?? null,
    //                 'DueDate' => $bill['DueDate'] ?? null,
    //                 'TotalAmount' => (float) ($bill['TotalAmt'] ?? 0),
    //                 'Balance' => $bill['Balance'] ?? 0,
    //                 'Currency' => $bill['CurrencyRef']['name'] ?? null,
    //                 'Payments' => [],
    //                 'ParsedLines' => $parsedLines,
    //                 'RawBill' => $bill,
    //             ];
    //         });

    //         $billPayments = $allBillPayments->map(function ($payment) {
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
    //                 'VendorId' => $payment['VendorRef']['value'] ?? null,
    //                 'VendorName' => $payment['VendorRef']['name'] ?? null,
    //                 'TxnDate' => $payment['TxnDate'] ?? null,
    //                 'TotalAmount' => $payment['TotalAmt'] ?? 0,
    //                 'PaymentMethod' => $payment['PayType'] ?? null,
    //                 'LinkedTxn' => $linked,
    //                 'RawPayment' => $payment,
    //             ];
    //         });

    //         $billsById = $bills->keyBy('BillId')->toArray();
    //         foreach ($billsById as $billId => &$bill) {
    //             $bill['Payments'] = collect($billPayments)->filter(function ($p) use ($billId) {
    //                 return collect($p['LinkedTxn'])->contains(fn($txn) => isset($txn['TxnType'], $txn['TxnId']) && strcasecmp($txn['TxnType'], 'Bill') === 0 && (string) $txn['TxnId'] === (string) $billId);
    //             })->values()->toArray();
    //         }
    //         $billsWithPayments = collect($billsById);
    //         // dd($billsWithPayments);
    //         // Now, import logic
    //         $imported = 0;
    //         $skipped = 0;
    //         $failed = 0;

    //         DB::beginTransaction();
    //         try {
    //             foreach ($billsWithPayments as $qbBill) {
    //                 $qbId = $qbBill['BillId'];

    //                 // Check for duplicate
    //                 $existing = Bill::where('bill_id', $qbId)->first();
    //                 if ($existing) {
    //                     $skipped++;
    //                     continue;
    //                 }

    //                 // Map vendor_id - find local vendor by name from QuickBooks
    //                 $vendorName = $qbBill['VendorName'];
    //                 $vendor = Vender::where('name', $vendorName)
    //                     ->where('created_by', \Auth::user()->creatorId())
    //                     ->first();

    //                 if (!$vendor) {
    //                     // Skip this bill if vendor doesn't exist in local DB
    //                     $skipped++;
    //                     continue;
    //                 }

    //                 $vendorId = $vendor->id;

    //                 // Insert bill
    //                 $bill = Bill::create([
    //                     'bill_id' => $qbId ?: 0, // Generate unique ID if QB ID is missing
    //                     'vender_id' => $vendorId,
    //                     'bill_date' => $qbBill['TxnDate'],
    //                     'due_date' => $qbBill['DueDate'],
    //                     'order_number' => $qbBill['DocNumber'] ?? 0,
    //                     'status' => 3, // default
    //                     'created_by' => \Auth::user()->creatorId(),
    //                     'owned_by' => \Auth::user()->ownedId(),
    //                     'type' => 'Bill',
    //                     'user_type' => 'Vendor'
    //                 ]);

    //                 // Process lines: products vs accounts
    //                 foreach ($qbBill['ParsedLines'] as $line) {
    //                     if (empty($line['AccountId']))
    //                         continue; // Skip unmapped

    //                     if ($line['HasProduct']) {
    //                         // This is a product line - insert into bill_products
    //                         $itemName = $line['RawLine']['ItemBasedExpenseLineDetail']['ItemRef']['name'] ?? null;
    //                         if (!$itemName)
    //                             continue;

    //                         $product = ProductService::where('name', $itemName)
    //                             ->where('created_by', \Auth::user()->creatorId())
    //                             ->first();

    //                         if (!$product) {
    //                             // Create product if it doesn't exist
    //                             $unit = ProductServiceUnit::firstOrCreate(
    //                                 ['name' => 'pcs'],
    //                                 ['created_by' => \Auth::user()->creatorId()]
    //                             );

    //                             $productCategory = ProductServiceCategory::firstOrCreate(
    //                                 [
    //                                     'name' => 'Product',
    //                                     'created_by' => \Auth::user()->creatorId(),
    //                                 ],
    //                                 [
    //                                     'color' => '#4CAF50',
    //                                     'type' => 'Product',
    //                                     'chart_account_id' => 0,
    //                                     'created_by' => \Auth::user()->creatorId(),
    //                                     'owned_by' => \Auth::user()->ownedId(),
    //                                 ]
    //                             );

    //                             $productData = [
    //                                 'name' => $itemName,
    //                                 'sku' => $itemName,
    //                                 'sale_price' => 0,
    //                                 'purchase_price' => $line['Amount'] ?? 0,
    //                                 'quantity' => 0,
    //                                 'unit_id' => $unit->id,
    //                                 'type' => 'product',
    //                                 'category_id' => $productCategory->id,
    //                                 'created_by' => \Auth::user()->creatorId(),
    //                             ];

    //                             // Map chart accounts if available
    //                             if (!empty($line['AccountId'])) {
    //                                 $account = ChartOfAccount::where('code', $line['AccountId'])
    //                                     ->where('created_by', \Auth::user()->creatorId())
    //                                     ->first();
    //                                 if ($account) {
    //                                     $productData['expense_chartaccount_id'] = $account->id;
    //                                 }
    //                             }

    //                             $product = ProductService::create($productData);
    //                         }

    //                         BillProduct::create([
    //                             'bill_id' => $bill->id,
    //                             'product_id' => $product->id,
    //                             'quantity' => $line['RawLine']['ItemBasedExpenseLineDetail']['Qty'] ?? 1,
    //                             'price' => $line['Amount'],
    //                             'description' => $line['Description'],
    //                             // tax, discount as needed
    //                         ]);
    //                     } else {
    //                         // This is an account line - insert into bill_accounts
    //                         $account = ChartOfAccount::where('code', $line['AccountId'])
    //                             ->where('created_by', \Auth::user()->creatorId())
    //                             ->first();

    //                         if ($account) {
    //                             BillAccount::create([
    //                                 'chart_account_id' => $account->id,
    //                                 'price' => $line['Amount'],
    //                                 'description' => $line['Description'],
    //                                 'type' => 'Bill',
    //                                 'ref_id' => $bill->id,
    //                             ]);
    //                         }
    //                     }
    //                 }

    //                 // Insert payments
    //                 foreach ($qbBill['Payments'] as $payment) {
    //                     // Determine payment method based on payment data
    //                     $paymentMethod = $payment['PaymentMethod'];

    //                     // If payment method is null, try to determine from payment type or account
    //                     if (!$paymentMethod) {
    //                         // Check if it's a check payment
    //                         if (isset($payment['RawPayment']['CheckPayment'])) {
    //                             $paymentMethod = 'Check';
    //                         }
    //                         // Check deposit account type
    //                         elseif (isset($payment['RawPayment']['PayFromAccountRef'])) {
    //                             $accountId = $payment['RawPayment']['PayFromAccountRef']['value'];
    //                             $account = collect($accountsList)->firstWhere('Id', $accountId);
    //                             if ($account) {
    //                                 $accountType = strtolower($account['AccountType'] ?? '');
    //                                 if (strpos($accountType, 'bank') !== false || strpos($accountType, 'checking') !== false) {
    //                                     $paymentMethod = 'Bank Transfer';
    //                                 } elseif (strpos($accountType, 'credit') !== false) {
    //                                     $paymentMethod = 'Credit Card';
    //                                 } else {
    //                                     $paymentMethod = 'Cash';
    //                                 }
    //                             } else {
    //                                 $paymentMethod = 'Cash'; // Default fallback
    //                             }
    //                         }
    //                         // Default to Cash if nothing else matches
    //                         else {
    //                             $paymentMethod = 'Cash';
    //                         }
    //                     }
    //                     // Map account_id from QuickBooks payment account using the same logic as billsWithPayments()
    //                     $accountId = 0; // Default to 0
    //                     $paymentAccount = null;
    //                     if (isset($payment['RawPayment']['CreditCardPayment']['CCAccountRef'])) {
    //                         $paymentAccount = $payment['RawPayment']['CreditCardPayment']['CCAccountRef'];
    //                     } elseif (isset($payment['RawPayment']['CheckPayment']['BankAccountRef'])) {
    //                         $paymentAccount = $payment['RawPayment']['CheckPayment']['BankAccountRef'];
    //                     } elseif (isset($payment['RawPayment']['PayFromAccountRef'])) {
    //                         $paymentAccount = $payment['RawPayment']['PayFromAccountRef'];
    //                     }

    //                     if ($paymentAccount && isset($paymentAccount['value'])) {
    //                         $qbAccountId = $paymentAccount['value'];
    //                         $localAccount = ChartOfAccount::where('code', $qbAccountId)
    //                             ->where('created_by', \Auth::user()->creatorId())
    //                             ->first();
    //                         if ($localAccount) {
    //                             $accountId = $localAccount->id;
    //                         }
    //                     }

    //                     BillPayment::create([
    //                         'bill_id' => $bill->id,
    //                         'date' => $payment['TxnDate'],
    //                         'amount' => $payment['TotalAmount'],
    //                         'account_id' => $accountId,
    //                         'payment_method' => $paymentMethod,
    //                         'reference' => $payment['PaymentId'],
    //                         'description' => 'QuickBooks Bill Payment',
    //                     ]);
    //                 }

    //                 if (!empty($qbBill['Payments'])) {
    //                     $bill->status = 4;
    //                     $bill->send_date = $qbBill['TxnDate'];
    //                     $bill->save();
    //                 }
    //                 $bill->save();

    //                 $imported++;
    //             }

    //             DB::commit();
    //         } catch (\Exception $e) {
    //             dd($e);
    //             DB::rollBack();
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Import failed: ' . $e->getMessage(),
    //             ], 500);
    //         }

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => "Bills import completed. Imported: {$imported}, Skipped: {$skipped}, Failed: {$failed}",
    //             'imported' => $imported,
    //             'skipped' => $skipped,
    //             'failed' => $failed,
    //         ]);

    //     } catch (\Exception $e) {
    //         dd($e);
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Error: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function importBills(Request $request)
    {
        try {
            // === Fetch Bills ===
            $allBills = collect();
            $startPosition = 1;
            $maxResults = 50;

            do {
                $query = "SELECT * FROM Bill STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $billsResponse = $this->qbController->runQuery($query);
                if ($billsResponse instanceof \Illuminate\Http\JsonResponse) return $billsResponse;

                $billsData = $billsResponse['QueryResponse']['Bill'] ?? [];
                $allBills = $allBills->merge($billsData);
                $fetchedCount = count($billsData);
                $startPosition += $fetchedCount;
            } while ($fetchedCount === $maxResults);

            // === Fetch Bill Payments ===
            $allBillPayments = collect();
            $startPosition = 1;
            do {
                $query = "SELECT * FROM BillPayment STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $billPaymentsResponse = $this->qbController->runQuery($query);
                if ($billPaymentsResponse instanceof \Illuminate\Http\JsonResponse) return $billPaymentsResponse;

                $billPaymentsData = $billPaymentsResponse['QueryResponse']['BillPayment'] ?? [];
                $allBillPayments = $allBillPayments->merge($billPaymentsData);
                $fetchedCount = count($billPaymentsData);
                $startPosition += $fetchedCount;
            } while ($fetchedCount === $maxResults);

            // === Fetch Items & Accounts ===
            $itemsRaw = $this->qbController->runQuery("SELECT * FROM Item STARTPOSITION 1 MAXRESULTS 500");
            $accountsRaw = $this->qbController->runQuery("SELECT * FROM Account STARTPOSITION 1 MAXRESULTS 500");

            $itemsList = collect($itemsRaw['QueryResponse']['Item'] ?? []);
            $accountsList = collect($accountsRaw['QueryResponse']['Account'] ?? []);

            $itemsMap = $itemsList->keyBy(fn($it) => $it['Id'] ?? null)->toArray();
            $accountsMap = $accountsList->keyBy(fn($a) => $a['Id'] ?? null)->toArray();

            // === Helper Functions ===
            $findAPAccount = function () use ($accountsList) {
                $ap = $accountsList->first(fn($a) => isset($a['AccountType']) && strcasecmp($a['AccountType'], 'AccountsPayable') === 0);
                if ($ap) return ['Id' => $ap['Id'], 'Name' => $ap['Name'] ?? null];
                $ap = $accountsList->first(fn($a) => stripos($a['Name'] ?? '', 'payable') !== false);
                return $ap ? ['Id' => $ap['Id'], 'Name' => $ap['Name'] ?? null] : null;
            };
            $apAccount = $findAPAccount();

            $detectAccountForExpenseItem = function ($sid) use ($itemsMap, $accountsMap) {
                if (!empty($sid['AccountRef']['value'])) {
                    return [
                        'AccountId' => $sid['AccountRef']['value'],
                        'AccountName' => $sid['AccountRef']['name'] ?? ($accountsMap[$sid['AccountRef']['value']]['Name'] ?? null)
                    ];
                }
                if (!empty($sid['ItemRef']['value'])) {
                    $item = $itemsMap[$sid['ItemRef']['value']] ?? null;
                    if ($item) {
                        if (!empty($item['ExpenseAccountRef']['value'])) {
                            return [
                                'AccountId' => $item['ExpenseAccountRef']['value'],
                                'AccountName' => $item['ExpenseAccountRef']['name'] ?? ($accountsMap[$item['ExpenseAccountRef']['value']]['Name'] ?? null)
                            ];
                        }
                        if (!empty($item['AssetAccountRef']['value'])) {
                            return [
                                'AccountId' => $item['AssetAccountRef']['value'],
                                'AccountName' => $item['AssetAccountRef']['name'] ?? ($accountsMap[$item['AssetAccountRef']['value']]['Name'] ?? null)
                            ];
                        }
                    }
                }
                return ['AccountId' => null, 'AccountName' => null];
            };

            $parseBillLine = function ($line) use ($detectAccountForExpenseItem) {
                $out = [];
                $detailType = $line['DetailType'] ?? null;

                if (!empty($line['GroupLineDetail']) && !empty($line['GroupLineDetail']['Line'])) {
                    foreach ($line['GroupLineDetail']['Line'] as $child) {
                        if (!empty($child['ItemBasedExpenseLineDetail'])) {
                            $sid = $child['ItemBasedExpenseLineDetail'];
                            $acc = $detectAccountForExpenseItem($sid);
                            $out[] = [
                                'DetailType' => $child['DetailType'] ?? 'ItemBasedExpenseLineDetail',
                                'Description' => $child['Description'] ?? ($sid['ItemRef']['name'] ?? null),
                                'Amount' => $child['Amount'] ?? 0,
                                'AccountId' => $acc['AccountId'],
                                'AccountName' => $acc['AccountName'],
                                'RawLine' => $child,
                                'HasProduct' => true,
                            ];
                        } elseif (!empty($child['AccountBasedExpenseLineDetail'])) {
                            $accDetail = $child['AccountBasedExpenseLineDetail'];
                            $out[] = [
                                'DetailType' => $child['DetailType'] ?? 'AccountBasedExpenseLineDetail',
                                'Description' => $child['Description'] ?? null,
                                'Amount' => $child['Amount'] ?? 0,
                                'AccountId' => $accDetail['AccountRef']['value'] ?? null,
                                'AccountName' => $accDetail['AccountRef']['name'] ?? null,
                                'RawLine' => $child,
                                'HasProduct' => false,
                            ];
                        }
                    }
                    return $out;
                }

                if (!empty($line['ItemBasedExpenseLineDetail'])) {
                    $sid = $line['ItemBasedExpenseLineDetail'];
                    $acc = $detectAccountForExpenseItem($sid);
                    $out[] = [
                        'DetailType' => 'ItemBasedExpenseLineDetail',
                        'Description' => $line['Description'] ?? ($sid['ItemRef']['name'] ?? null),
                        'Amount' => $line['Amount'] ?? 0,
                        'AccountId' => $acc['AccountId'],
                        'AccountName' => $acc['AccountName'],
                        'RawLine' => $line,
                        'HasProduct' => true,
                    ];
                    return $out;
                }

                if (!empty($line['AccountBasedExpenseLineDetail'])) {
                    $accDetail = $line['AccountBasedExpenseLineDetail'];
                    $out[] = [
                        'DetailType' => 'AccountBasedExpenseLineDetail',
                        'Description' => $line['Description'] ?? null,
                        'Amount' => $line['Amount'] ?? 0,
                        'AccountId' => $accDetail['AccountRef']['value'] ?? null,
                        'AccountName' => $accDetail['AccountRef']['name'] ?? null,
                        'RawLine' => $line,
                        'HasProduct' => false,
                    ];
                    return $out;
                }

                return [[
                    'DetailType' => $detailType,
                    'Description' => $line['Description'] ?? null,
                    'Amount' => $line['Amount'] ?? 0,
                    'AccountId' => null,
                    'AccountName' => null,
                    'RawLine' => $line,
                    'HasProduct' => false,
                ]];
            };

            // === Extract payment account reference from different payment types ===
            $extractPaymentAccountRef = function ($payment) {
                if (!empty($payment['CreditCardPayment']['CCAccountRef'])) {
                    return $payment['CreditCardPayment']['CCAccountRef'];
                }
                if (!empty($payment['CheckPayment']['BankAccountRef'])) {
                    return $payment['CheckPayment']['BankAccountRef'];
                }
                if (!empty($payment['PayFromAccountRef'])) {
                    return $payment['PayFromAccountRef'];
                }
                return null;
            };

            // === Helper function to process and create bank accounts ===
            $processBankAccount = function ($payFromAccountRef) {
                if (empty($payFromAccountRef) || empty($payFromAccountRef['value'])) {
                    return null;
                }

                $qbAccountCode = $payFromAccountRef['value'];
                $qbAccountName = $payFromAccountRef['name'] ?? 'Bank Account';
                $creatorId = \Auth::user()->creatorId();

                // Check if chart of account exists with this code
                $chartAccount = ChartOfAccount::where('code', $qbAccountCode)
                    ->where('created_by', $creatorId)
                    ->first();

                if (!$chartAccount) {
                    return null;
                }

                // Check if bank account already exists for this chart account
                $bankAccount = BankAccount::where('chart_account_id', $chartAccount->id)
                    ->where('created_by', $creatorId)
                    ->first();

                if ($bankAccount) {
                    return $bankAccount->id;
                }

                // Create new bank account
                try {
                    $newBankAccount = BankAccount::create([
                        'bank_name' => $qbAccountName,
                        'chart_account_id' => $chartAccount->id,
                        'created_by' => $creatorId,
                        'owned_by' => \Auth::user()->ownedId(),
                    ]);

                    return $newBankAccount->id;
                } catch (\Exception $e) {
                    \Log::error("Failed to create bank account: " . $e->getMessage());
                    return null;
                }
            };

            // === Get or create default cash account for non-bank payments ===
            $getDefaultCashAccount = function () {
                $creatorId = \Auth::user()->creatorId();
                
                // Try to find existing default cash account
                $existingBankAccount = BankAccount::where('created_by', $creatorId)
                    ->where('name', 'like', '%Default%Cash%')
                    ->first();
                
                if ($existingBankAccount) {
                    return $existingBankAccount->id;
                }

                // Try to find a cash chart account
                $cashChartAccount = ChartOfAccount::where('created_by', $creatorId)
                    ->where('account_type', 'Cash')
                    ->orWhere('name', 'like', '%Cash%')
                    ->first();

                if ($cashChartAccount) {
                    // Create default cash bank account for this chart account if not exists
                    $bankAccount = BankAccount::firstOrCreate(
                        [
                            'chart_account_id' => $cashChartAccount->id,
                            'created_by' => $creatorId,
                        ],
                        [
                            'name' => 'Default Cash Account',
                            'owned_by' => \Auth::user()->ownedId(),
                        ]
                    );
                    return $bankAccount->id;
                }

                // Create bank account without chart account
                try {
                    $bankAccount = BankAccount::create([
                        'bank_name' => 'Default Cash Account',
                        'chart_account_id' => null,
                        'created_by' => $creatorId,
                        'owned_by' => \Auth::user()->ownedId(),
                    ]);

                    return $bankAccount->id;
                } catch (\Exception $e) {
                    \Log::error("Failed to create default cash account: " . $e->getMessage());
                    return null;
                }
            };

            // === Parse Bills ===
            $bills = $allBills->map(function ($bill) use ($parseBillLine) {
                $parsedLines = [];
                foreach ($bill['Line'] ?? [] as $line) {
                    $parsedLines = array_merge($parsedLines, $parseBillLine($line));
                }

                return [
                    'BillId' => (string) ($bill['Id'] ?? null),
                    'VendorId' => $bill['VendorRef']['value'] ?? null,
                    'VendorName' => $bill['VendorRef']['name'] ?? null,
                    'TxnDate' => $bill['TxnDate'] ?? null,
                    'DueDate' => $bill['DueDate'] ?? null,
                    'DocNumber' => $bill['DocNumber'] ?? null,
                    'TotalAmount' => (float) ($bill['TotalAmt'] ?? 0),
                    'Balance' => (float) ($bill['Balance'] ?? 0),
                    'ParsedLines' => $parsedLines,
                    'Payments' => [],
                ];
            });

            // === Match Payments ===
            $billPayments = $allBillPayments->map(function ($payment) use ($extractPaymentAccountRef) {
                $linked = [];
                foreach ($payment['Line'] ?? [] as $l) {
                    if (!empty($l['LinkedTxn'])) {
                        $linked = array_merge($linked, is_array($l['LinkedTxn']) ? $l['LinkedTxn'] : [$l['LinkedTxn']]);
                    }
                }
                return [
                    'PaymentId' => $payment['Id'] ?? null,
                    'VendorId' => $payment['VendorRef']['value'] ?? null,
                    'TxnDate' => $payment['TxnDate'] ?? null,
                    'TotalAmount' => (float) ($payment['TotalAmt'] ?? 0),
                    'LinkedTxn' => $linked,
                    'PaymentAccountRef' => $extractPaymentAccountRef($payment),
                    'RawPayment' => $payment,
                ];
            });

            $billsById = $bills->keyBy('BillId')->toArray();
            foreach ($billsById as $billId => &$bill) {
                $bill['Payments'] = collect($billPayments)->filter(function ($p) use ($billId) {
                    return collect($p['LinkedTxn'])->contains(fn($txn) => isset($txn['TxnType'], $txn['TxnId']) && strtolower($txn['TxnType']) === 'bill' && (string)$txn['TxnId'] === (string)$billId);
                })->values()->toArray();
            }

            // === Import Logic ===
            DB::beginTransaction();
            $imported = $skipped = $failed = 0;
            $defaultCashAccountId = null;

            foreach ($billsById as $qbBill) {
                try {
                    if (Bill::where('bill_id', $qbBill['BillId'])->exists()) {
                        $skipped++;
                        continue;
                    }

                    $vendor = Vender::where('vender_id', $qbBill['VendorId'])
                        ->where('created_by', \Auth::user()->creatorId())
                        ->first();

                    if (!$vendor) {
                        $skipped++;
                        continue;
                    }

                    $bill = Bill::create([
                        'bill_id' => $qbBill['BillId'],
                        'vender_id' => $vendor->id,
                        'bill_date' => $qbBill['TxnDate'],
                        'due_date' => $qbBill['DueDate'],
                        'order_number' => $qbBill['DocNumber'] ?? 0,
                        'status' => 2,
                        'created_by' => \Auth::user()->creatorId(),
                        'owned_by' => \Auth::user()->ownedId(),
                        'type' => 'Bill',
                        'user_type' => 'Vendor',
                        'created_at'  => Carbon::parse($qbBill['TxnDate'])->format('Y-m-d H:i:s'),
                        'updated_at'  => Carbon::parse($qbBill['TxnDate'])->format('Y-m-d H:i:s'),
                    ]);

                    // === Handle Bill Lines (Items + Accounts) ===
                    $totalAmount = 0;
                    foreach ($qbBill['ParsedLines'] as $line) {
                        if ($line['HasProduct']) {
                            // This is a product line
                            $itemName = $line['RawLine']['ItemBasedExpenseLineDetail']['ItemRef']['name'] ?? null;
                            if (!$itemName)
                                continue;

                            $product = ProductService::where('name', $itemName)
                                ->where('created_by', \Auth::user()->creatorId())
                                ->first();

                            if (!$product) {
                                // Create product if it doesn't exist
                                $unit = ProductServiceUnit::firstOrCreate(
                                    ['name' => 'pcs'],
                                    ['created_by' => \Auth::user()->creatorId()]
                                );

                                $productCategory = ProductServiceCategory::firstOrCreate(
                                    [
                                        'name' => 'Product',
                                        'created_by' => \Auth::user()->creatorId(),
                                    ],
                                    [
                                        'color' => '#4CAF50',
                                        'type' => 'Product',
                                        'chart_account_id' => 0,
                                        'created_by' => \Auth::user()->creatorId(),
                                        'owned_by' => \Auth::user()->ownedId(),
                                    ]
                                );

                                $productData = [
                                    'name' => $itemName,
                                    'sku' => $itemName,
                                    'sale_price' => 0,
                                    'purchase_price' => $line['Amount'] ?? 0,
                                    'quantity' => 0,
                                    'unit_id' => $unit->id,
                                    'type' => 'product',
                                    'category_id' => $productCategory->id,
                                    'created_by' => \Auth::user()->creatorId(),
                                ];

                                // Map chart accounts if available
                                if (!empty($line['AccountId'])) {
                                    $account = ChartOfAccount::where('code', $line['AccountId'])
                                        ->where('created_by', \Auth::user()->creatorId())
                                        ->first();
                                    if ($account) {
                                        $productData['expense_chartaccount_id'] = $account->id;
                                    }
                                }

                                $product = ProductService::create($productData);
                            }

                            BillProduct::create([
                                'bill_id' => $bill->id,
                                'product_id' => $product->id,
                                'quantity' => $line['RawLine']['ItemBasedExpenseLineDetail']['Qty'] ?? 1,
                                'price' => $line['Amount'],
                                'description' => $line['Description'],
                            ]);
                        } else {
                            // This is an account line
                            $account = ChartOfAccount::where('code', $line['AccountId'])
                                ->where('created_by', \Auth::user()->creatorId())
                                ->first();

                            if ($account) {
                                BillAccount::create([
                                    'bill_id' => $bill->id,
                                    'chart_account_id' => $account->id,
                                    'price' => $line['Amount'],
                                    'description' => $line['Description'],
                                    'type' => 'Bill',
                                    'ref_id' => $bill->id,
                                ]);
                            }
                        }
                        $totalAmount += $line['Amount'];
                    }

                    // === Payment Handling ===
                    $billPaid = $qbBill['TotalAmount'] - $qbBill['Balance'];
                    if ($billPaid > 0) {
                        $bankAccountId = null;

                        // Try to get bank account from linked payments
                        if (!empty($qbBill['Payments'])) {
                            foreach ($qbBill['Payments'] as $payment) {
                                $paymentAccountRef = $payment['PaymentAccountRef'] ?? null;
                                if ($paymentAccountRef) {
                                    $bankAccountId = $processBankAccount($paymentAccountRef);
                                    if ($bankAccountId) {
                                        break;
                                    }
                                }
                            }
                        }

                        // If no bank account found from payments, use default cash account
                        if (!$bankAccountId) {
                            if (!$defaultCashAccountId) {
                                $defaultCashAccountId = $getDefaultCashAccount();
                            }
                            $bankAccountId = $defaultCashAccountId;
                        }

                        // Create payment record
                        BillPayment::create([
                            'bill_id' => $bill->id,
                            'date' => $qbBill['TxnDate'],
                            'amount' => $billPaid,
                            'account_id' => $bankAccountId,
                            'payment_method' => 'QuickBooks Auto',
                            'reference' => 'Balance-based Settlement',
                            'description' => 'Auto Payment from Bill Balance',
                            'created_at'  => Carbon::parse($qbBill['TxnDate'])->format('Y-m-d H:i:s'),
                            'updated_at'  => Carbon::parse($qbBill['TxnDate'])->format('Y-m-d H:i:s'),
                        ]);

                        $bill->status = 4;
                        $bill->save();

                        if ($bankAccountId) {
                            Utility::bankAccountBalance($bankAccountId, $billPaid, 'debit');
                        }
                        Utility::updateUserBalance('vendor', $vendor->id, $billPaid, 'credit');
                    }

                    // === Vendor Balance Update ===
                    Utility::updateUserBalance('vendor', $vendor->id, $totalAmount, 'debit');

                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    \Log::error('Bill import error: ' . $e->getMessage());
                }
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => "Imported: {$imported}, Skipped: {$skipped}, Failed: {$failed}"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Bills import error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // 📊 Fetch Journal Entries (with all available fields)
    // public function journalReport(Request $request)
    // {
    //     $companyId = $this->qbController->realmId();
    //     $accessToken = $this->qbController->accessToken();
    //     $baseUrl = "{$this->qbController->baseUrl}/v3/company/{$companyId}";

    //     // Input or default range
    //     $startDate = Carbon::parse($request->input('start_date', '2023-10-26'));
    //     $endDate = Carbon::parse($request->input('end_date', now()->format('Y-m-d')));
    //     $accountingMethod = $request->input('accounting_method', 'Accrual');

    //     // Determine batch size (1 year chunks)
    //     $batchSizeMonths = 12;
    //     $batches = [];
    //     $current = $startDate->copy();

    //     while ($current->lt($endDate)) {
    //         $batchStart = $current->copy();
    //         $batchEnd = $current->copy()->addMonths($batchSizeMonths)->endOfMonth();
    //         if ($batchEnd->gt($endDate)) $batchEnd = $endDate->copy();
    //         $batches[] = [$batchStart->toDateString(), $batchEnd->toDateString()];
    //         $current = $batchEnd->copy()->addDay();
    //     }

    //     $groupedEntries = [];
    //     $totalImported = 0;

    //     foreach ($batches as [$batchStart, $batchEnd]) {
    //         $url = "{$baseUrl}/reports/JournalReport?start_date={$batchStart}&end_date={$batchEnd}&accounting_method={$accountingMethod}";

    //         try {
    //             $response = Http::withHeaders([
    //                 'Authorization' => "Bearer {$accessToken}",
    //                 'Accept' => 'application/json',
    //                 'Content-Type' => 'application/text',
    //             ])
    //             ->timeout(180)   // 3-minute timeout per batch
    //             ->retry(3, 5000) // Retry 3 times, 5s interval
    //             ->get($url);

    //             if ($response->failed()) {
    //                 \Log::warning("QuickBooks JournalReport batch failed", [
    //                     'url' => $url,
    //                     'status' => $response->status(),
    //                     'response' => $response->body(),
    //                 ]);
    //                 continue;
    //             }

    //             $data = $response->json();
    //             $rows = $data['Rows']['Row'] ?? [];
    //             $batchEntries = $this->processJournalRows($rows);
    //             $groupedEntries = array_merge($groupedEntries, $batchEntries);

    //         } catch (\Illuminate\Http\Client\ConnectionException $e) {
    //             \Log::error('QuickBooks JournalReport timeout', [
    //                 'url' => $url,
    //                 'message' => $e->getMessage(),
    //             ]);
    //             continue;
    //         }
    //     }

    //     // Create entries
    //     $createdEntries = [];
    //     foreach ($groupedEntries as $entryData) {
    //         $createdEntry = $this->createJournalEntry($entryData);
    //         if ($createdEntry) {
    //             $createdEntries[] = $createdEntry;
    //             $totalImported++;
    //         }
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Batched journal report import completed successfully.',
    //         'imported_batches' => count($batches),
    //         'imported_entries' => $totalImported,
    //         'date_range' => [
    //             'start' => $startDate->toDateString(),
    //             'end' => $endDate->toDateString(),
    //         ]
    //     ]);
    // }

    public function journalReport(Request $request)
    {
        $companyId = $this->qbController->realmId();
        $accessToken = $this->qbController->accessToken();
        $baseUrl = "{$this->qbController->baseUrl}/v3/company/{$companyId}";

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
                $accessToken = $this->qbController->accessToken(); // get fresh token
            } catch (\Throwable $e) {
                \Log::error("QuickBooks token refresh failed before batch {$index}", [
                    'error' => $e->getMessage(),
                ]);
                continue; // Skip this batch if refresh fails
            }

            $url = "{$baseUrl}/reports/JournalReport?start_date={$batchStart}&end_date={$batchEnd}&accounting_method={$accountingMethod}";

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
            $result = $this->createJournalEntry($entryData);
            
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
            $token = \App\Models\QuickBooksToken::where('user_id', $this->userId)
                ->latest()->first();

            if (!$token) throw new \Exception("No QuickBooks tokens for user {$this->userId}");

            if ($token->expires_at && now()->addMinutes(5)->greaterThan($token->expires_at)) {
                $this->logInfo('Refreshing QuickBooks token...');
                $api = new QuickBooksApiController();
                $new = $api->refreshToken($token->refresh_token);
                if ($new) $this->logSuccess('QuickBooks token refreshed successfully');
                else throw new \Exception('Token refresh failed');
            }
        } catch (\Throwable $e) {
            $this->logError('Token refresh failed: ' . $e->getMessage());
            throw $e;
        }
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
    public function downloadSkippedEntries($file)
    {
        try {
            $filePath = storage_path('app/exports/' . $file);

            // Security check: ensure file exists and is in the exports directory
            if (!file_exists($filePath) || strpos(realpath($filePath), realpath(storage_path('app/exports'))) !== 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'File not found.'
                ], 404);
            }

            return response()->download($filePath)->deleteFileAfterSend();

        } catch (\Exception $e) {
            \Log::error('Failed to download skipped entries: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download file.'
            ], 500);
        }
    }
    protected function logSuccess($msg) { $this->addLog('[SUCCESS]', $msg); }
    protected function logError($msg) { $this->addLog('[ERROR]', $msg); }
    protected function logInfo($msg) { $this->addLog('[INFO]', $msg); }

    protected function addLog($type, $msg)
    {
        $key = "qb_import_progress_{$this->userId}";
        $progress = Cache::get($key, []);
        $progress['logs'][] = "{$type} {$msg} at " . now();
        Cache::put($key, $progress, 3600);
    }
   public function importEstimates(Request $request)
    {
        try {
            $allEstimates = collect();
            $start = 1;
            $max = 100;

            // Paginate through all estimates
            do {
                $response = $this->qbController->getEstimates($start, $max);
                if (!$response['success']) {
                    \Log::error('QuickBooks Estimates fetch failed', ['response' => $response]);
                    return response()->json([
                        'status' => 'error',
                        'message' => $response['message'] ?? 'Error fetching estimates'
                    ]);
                }

                $estimates = collect($response['data']);
                $allEstimates = $allEstimates->merge($estimates);
                $fetched = $estimates->count();
                $start += $fetched;
            } while ($fetched === $max);

            if ($allEstimates->isEmpty()) {
                return response()->json(['status' => 'success', 'message' => 'No estimates found in QuickBooks.']);
            }

            $imported = 0;
            $skipped = 0;
            $failed = 0;
            $errors = [];

            DB::beginTransaction();
            try {
                foreach ($allEstimates as $estimate) {
                    try {
                        $estimateId = $estimate['Id'] ?? null;
                        if (!$estimateId) {
                            $skipped++;
                            continue;
                        }

                        // Skip if proposal already imported
                        $existing = Proposal::where('proposal_id', $estimateId)
                            ->where('created_by', \Auth::user()->creatorId())
                            ->first();
                        if ($existing) {
                            $skipped++;
                            continue;
                        }

                        // --- Find or create Customer ---
                        $customerName = $estimate['CustomerRef']['name'] ?? ($estimate['CustomerRefName'] ?? null);
                        $customerIdQB = $estimate['CustomerRef']['value'] ?? ($estimate['CustomerRef_id'] ?? null);

                        $customer = null;
                        if ($customerIdQB) {
                            $customer = Customer::where('customer_id', $customerIdQB)
                                ->where('created_by', \Auth::user()->creatorId())
                                ->first();
                        }
                        if (!$customer && $customerName) {
                            $customer = Customer::where('name', $customerName)
                                ->where('created_by', \Auth::user()->creatorId())
                                ->first();
                        }

                        if (!$customer) {
                            $errors[] = "Estimate {$estimateId}: Customer not found ({$customerName})";
                            $skipped++;
                            continue;
                        }

                        $category = ProductServiceCategory::firstOrCreate(
                            [
                                'name' => 'Estimates Category',
                                'created_by' => \Auth::user()->creatorId(),
                            ],
                            [
                                'color' => '#2196F3',
                                'type' => 'Product',
                                'chart_account_id' => 0,
                                'owned_by' => \Auth::user()->ownedId(),
                            ]
                        );

                        // --- Determine converted invoice id ---
                        $convertedInvoiceId = null;
                        $linkedTxnList = $estimate['LinkedTxn'] ?? $estimate['LinkedTransactions'] ?? [];

                        if (is_array($linkedTxnList)) {
                            foreach ($linkedTxnList as $txn) {
                                $txnType = strtolower($txn['TxnType'] ?? '');
                                $txnId = $txn['TxnId'] ?? null;
                                if ($txnType == 'invoice' && $txnId) {
                                    $inv = Invoice::where('invoice_id',$txnId)->first();
                                    $convertedInvoiceId = $inv->id;
                                    break;
                                }
                            }
                        }

                        // --- Map QuickBooks statuses to internal numeric codes ---
                        $statusText = strtolower($estimate['TxnStatus'] ?? '');
                        $statusMap = [
                            'pending'   => 1,
                            'accepted'  => 2,
                            'converted' => 2,
                            'rejected'  => 3,
                            'closed'    => 4,
                        ];
                        $statusCode = $statusMap[$statusText] ?? 0; // default 0 if unknown

                        // --- Set conversion flag ---
                        $isConvert = ($statusText == 'converted' || $convertedInvoiceId) ? 1 : 0;

                        // --- Create Proposal ---
                        $txnDate = $estimate['TxnDate'] ?? now()->toDateString();
                        $proposal = Proposal::create([
                            'proposal_id'   => $estimateId,
                            'customer_id'   => $customer->id,
                            'issue_date'    => $txnDate,
                            'send_date'     => $txnDate,
                            'category_id'   => $category->id,
                            'status'        => $statusCode,
                            'discount_apply'=> 0,
                            'is_convert'    => $isConvert,
                            'converted_invoice_id' => $convertedInvoiceId ?: 0,
                            'created_by'    => \Auth::user()->creatorId(),
                            'owned_by'      => \Auth::user()->ownedId(),
                            'created_at'    => Carbon::parse($txnDate)->format('Y-m-d H:i:s'),
                            'updated_at'    => Carbon::parse($txnDate)->format('Y-m-d H:i:s'),
                        ]);

                        // --- Parse estimate lines ---
                        $lines = $estimate['Line'] ?? [];
                        foreach ($lines as $line) {
                            if (!isset($line['SalesItemLineDetail'])) continue;

                            $sid = $line['SalesItemLineDetail'];
                            $itemName = $sid['ItemRef']['name'] ?? $line['Description'] ?? null;
                            if (!$itemName) continue;

                            $product = ProductService::where('name', $itemName)
                                ->where('created_by', \Auth::user()->creatorId())
                                ->first();

                            if (!$product) {
                                $unit = ProductServiceUnit::firstOrCreate(
                                    ['name' => 'pcs'],
                                    ['created_by' => \Auth::user()->creatorId()]
                                );

                                $product = ProductService::create([
                                    'name' => $itemName,
                                    'sku' => $itemName,
                                    'sale_price' => $line['Amount'] ?? 0,
                                    'purchase_price' => 0,
                                    'quantity' => 0,
                                    'unit_id' => $unit->id,
                                    'type' => 'product',
                                    'category_id' => $category->id,
                                    'created_by' => \Auth::user()->creatorId(),
                                ]);
                            }

                            $quantity = $sid['Qty'] ?? 1;
                            $amount = $line['Amount'] ?? 0;
                            $description = $line['Description'] ?? $itemName;
                            $price = $quantity != 0 ? ($amount / $quantity) : 0;
                            $rate  = ($amount);
                            ProposalProduct::create([
                                'proposal_id' => $proposal->id,
                                'product_id'  => $product->id,
                                'quantity'    => $quantity,
                                'tax'         => 0,
                                'discount'    => 0,
                                'price'       => $price,
                                'rate'        => $rate,
                                'description' => $description,
                                'created_at'  => now(),
                                'updated_at'  => now(),
                            ]);
                        }

                        $imported++;

                    } catch (\Exception $ex) {
                        \Log::error("Estimate import failed", ['error' => $ex->getMessage(), 'estimate' => $estimate]);
                        $failed++;
                        $errors[] = $ex->getMessage();
                        continue;
                    }
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Estimate import transaction error: ' . $e->getMessage());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transaction failed: ' . $e->getMessage(),
                    'errors' => $errors,
                ], 500);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Estimate import completed successfully',
                'summary' => [
                    'total_estimates_processed' => $imported + $skipped + $failed,
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'failed' => $failed,
                ],
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            \Log::error('Estimates import error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Import error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function importDeposits(Request $request)
{
    try {
        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        // configuration: set to false if you don't want new customers auto-created
        $autoCreateCustomer = true;

        // 1️⃣ Fetch deposits from QuickBooks
        $depositResponse = $this->qbController->runQuery("SELECT * FROM Deposit");

        if ($depositResponse instanceof \Illuminate\Http\JsonResponse) {
            return $depositResponse;
        }

        $depositsData = $depositResponse['QueryResponse']['Deposit'] ?? [];

        if (empty($depositsData)) {
            return response()->json([
                'status' => 'success',
                'count' => 0,
                'message' => 'No deposits found in QuickBooks response.',
            ]);
        }

        DB::beginTransaction();
        $creatorId = \Auth::user()->creatorId();
        $ownerId = \Auth::user()->ownedId();

        foreach ($depositsData as $deposit) {
            $qbDepositId = $deposit['Id'] ?? null;
            $docNumber = $deposit['DocNumber'] ?? null;
            $txnDate = $deposit['TxnDate'] ?? null;
            $totalAmt = $deposit['TotalAmt'] ?? 0;
            $privateNote = $deposit['PrivateNote'] ?? null;
            $currency = $deposit['CurrencyRef']['name'] ?? null;

            // ---------- Bank/DepositToAccountRef (inline as you requested) ----------
            $depositToAccountRef = $deposit['DepositToAccountRef'] ?? null;
            $bankAccountId = null;

            if (!empty($depositToAccountRef) && !empty($depositToAccountRef['value'])) {
                $qbAccountCode = $depositToAccountRef['value'];
                $qbAccountName = $depositToAccountRef['name'] ?? 'Bank Account';

                $chartAccount = ChartOfAccount::where('code', $qbAccountCode)
                    ->where('created_by', $creatorId)
                    ->first();

                if ($chartAccount) {
                    $bankAccount = BankAccount::where('chart_account_id', $chartAccount->id)
                        ->where('created_by', $creatorId)
                        ->first();

                    if (!$bankAccount) {
                        try {
                            $bankAccount = BankAccount::create([
                                'bank_name' => $qbAccountName,
                                'chart_account_id' => $chartAccount->id,
                                'created_by' => $creatorId,
                                'owned_by' => $ownerId,
                            ]);
                        } catch (\Exception $e) {
                            \Log::error("Failed to create bank account for deposit {$qbDepositId}: " . $e->getMessage());
                        }
                    }

                    $bankAccountId = $bankAccount->id ?? null;
                } else {
                    \Log::info("ChartOfAccount not found for DepositToAccountRef in deposit {$qbDepositId}", [
                        'DepositToAccountRef' => $depositToAccountRef
                    ]);
                }
            }

            // ---------- Create or update Deposit ----------
            $depositModel = Deposit::where('deposit_id', $qbDepositId)->first();
            if (!$depositModel) {
                $depositModel = Deposit::create([
                    'deposit_id' => $qbDepositId,
                    'doc_number' => $docNumber,
                    'txn_date' => $txnDate,
                    'total_amt' => $totalAmt,
                    'private_note' => $privateNote,
                    'currency' => $currency,
                    'bank_id' => $bankAccountId,
                ]);
            } else {
                $depositModel->update([
                    'doc_number' => $docNumber,
                    'txn_date' => $txnDate,
                    'total_amt' => $totalAmt,
                    'private_note' => $privateNote,
                    'currency' => $currency,
                    'bank_id' => $bankAccountId,
                ]);
                // clear existing lines for re-import
                $depositModel->lines()->delete();
            }

            // ---------- Process deposit lines ----------
            $lines = $deposit['Line'] ?? [];
            $firstCustomerId = null;
            $primaryChartAccountId = null;
            $otherAccountId = null;

            foreach ($lines as $lineIndex => $line) {
                $detail = $line['DepositLineDetail'] ?? [];
                // Robust entity extraction - handle multiple possible QuickBooks shapes
                $entityRef = null;

                // common possible locations:
                if (!empty($detail['Entity']['EntityRef'])) {
                    $entityRef = $detail['Entity']['EntityRef'];
                } elseif (!empty($detail['EntityRef'])) {
                    $entityRef = $detail['EntityRef'];
                } elseif (!empty($detail['Entity']) && is_array($detail['Entity'])) {
                    // sometimes Entity itself may be the ref array with 'value'/'name'
                    $ent = $detail['Entity'];
                    if (isset($ent['value']) || isset($ent['name'])) {
                        $entityRef = $ent;
                    } else {
                        // fallback: maybe indexed; log for inspection
                        \Log::debug("Deposit {$qbDepositId} line {$lineIndex} - unexpected Entity shape", [
                            'Entity' => $ent
                        ]);
                    }
                } elseif (!empty($line['CustomerRef'])) {
                    $entityRef = $line['CustomerRef'];
                }

                $customer = null;
                $customerQbId = $entityRef['value'] ?? null;
                $customerName = $entityRef['name'] ?? null;

                // If no entityRef found, log the whole line for debugging and continue
                if (empty($entityRef) || (empty($customerQbId) && empty($customerName))) {
                    \Log::warning("Deposit {$qbDepositId} line {$lineIndex}: missing EntityRef / customer info", [
                        'line' => $line,
                        'detail' => $detail,
                    ]);

                    // if you prefer to skip only this line but continue with other lines:
                    $skipped++;
                    continue;
                }

                // ---------- Customer lookup: try multiple columns (customer_id, quickbooks_id, name) ----------
                // some projects store the QB id in different columns — try both
                if (!empty($customerQbId)) {
                    $customer = Customer::where(function($q) use ($customerQbId) {
                        $q->where('customer_id', $customerQbId);
                    })->where('created_by', $creatorId)->first();
                }

                if (!$customer && !empty($customerName)) {
                    $customer = Customer::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($customerName))])
                        ->where('created_by', $creatorId)
                        ->first();
                }

                // If still not found, optionally auto-create (toggle above)
                if (!$customer && $autoCreateCustomer && !empty($customerName)) {
                    try {
                        $customer = Customer::create([
                            'name' => $customerName,
                            'customer_id' => $customerQbId ?? null,
                            'created_by' => $creatorId,
                        ]);
                        \Log::info("Auto-created customer for deposit {$qbDepositId}", [
                            'customer' => $customer->toArray()
                        ]);
                    } catch (\Exception $e) {
                        \Log::error("Failed to auto-create customer for deposit {$qbDepositId}: " . $e->getMessage(), [
                            'customerName' => $customerName,
                            'customerQbId' => $customerQbId,
                        ]);
                        $failed++;
                        $errors[] = "Deposit {$qbDepositId} line {$lineIndex}: failed to create customer {$customerName}";
                        continue;
                    }
                }

                if (!$customer) {
                    \Log::warning("⚠️ Customer not found (and not created) for Deposit {$qbDepositId} line {$lineIndex}", [
                        'customerQbId' => $customerQbId,
                        'customerName' => $customerName,
                        'entityRef' => $entityRef,
                    ]);
                    $skipped++;
                    $errors[] = "Deposit {$qbDepositId} line {$lineIndex}: customer not found ({$customerName})";
                    continue;
                }

                // Successful customer match
                \Log::info("Matched customer for deposit {$qbDepositId} line {$lineIndex}", [
                    'customer_id' => $customer->id,
                    'customer_qb_id' => $customer->customer_id ?? $customer->quickbooks_id ?? null,
                    'name' => $customer->name,
                ]);

                // ---------- Chart of account resolution ----------
                $accountRef = $detail['AccountRef'] ?? [];
                $chartAccount = null;
                if (!empty($accountRef)) {
                    $accountValue = $accountRef['value'] ?? null;
                    $accountName = $accountRef['name'] ?? null;

                    if ($accountValue) {
                        $chartAccount = ChartOfAccount::where('code', $accountValue)
                            ->where('created_by', $creatorId)
                            ->first();
                    }

                    if (!$chartAccount && $accountName) {
                        $chartAccount = ChartOfAccount::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($accountName))])
                            ->where('created_by', $creatorId)
                            ->first();
                    }

                    // If nothing found create a placeholder (optional)
                    if (!$chartAccount && !empty($accountName)) {
                        try {
                            $chartAccount = ChartOfAccount::create([
                                'name' => $accountName,
                                'code' => $accountValue ?? null,
                                'created_by' => $creatorId,
                            ]);
                            \Log::info("Created placeholder ChartOfAccount for deposit {$qbDepositId} line {$lineIndex}", [
                                'chartAccount' => $chartAccount->toArray(),
                            ]);
                        } catch (\Exception $e) {
                            \Log::error("Failed to create ChartOfAccount for deposit {$qbDepositId} line {$lineIndex}: " . $e->getMessage());
                        }
                    }
                }

                // ---------- Linked Txns ----------
                $linkedTxnRaw = $detail['LinkedTxn'] ?? [];
                $linkedTxns = collect($linkedTxnRaw)->map(function ($lt) {
                    return [
                        'TxnId' => $lt['TxnId'] ?? null,
                        'TxnType' => $lt['TxnType'] ?? null,
                        'TxnLineId' => $lt['TxnLineId'] ?? null,
                    ];
                })->values()->toArray();

                // ---------- Create DepositLine ----------
                DepositLines::create([
                    'deposit_id'       => $depositModel->id,
                    'amount'           => $line['Amount'] ?? 0,
                    'detail_type'      => $line['DetailType'] ?? null,
                    'customer_id'      => $customer->id ?? null,
                    'chart_account_id' => $chartAccount->id ?? null,
                    'payment_method'   => $detail['PaymentMethodRef']['name'] ?? null,
                    'check_num'        => $detail['CheckNum'] ?? null,
                    'linked_txns'      => !empty($linkedTxns) ? json_encode($linkedTxns) : null, 
                ]);

                // maintain top-level pointers
                if (!$firstCustomerId && $customer) $firstCustomerId = $customer->id;
                if (!$primaryChartAccountId && $chartAccount) $primaryChartAccountId = $chartAccount->id;
                $otherAccountId = $chartAccount->id ?? $otherAccountId;
                $imported++;
            } // end foreach lines

            // Update deposit top-level links
            $depositModel->update([
                'customer_id' => $firstCustomerId,
                'chart_account_id' => $primaryChartAccountId,
                'other_account_id' => $otherAccountId,
            ]);
        } // end foreach deposits

        DB::commit();

        return response()->json([
            'status' => 'success',
            'count' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
            'errors' => $errors,
            'message' => "Imported {$imported} deposit lines (skipped {$skipped}, failed {$failed}).",
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Deposit import error: ' . $e->getMessage(), ['exception' => $e]);
        return response()->json([
            'status' => 'error',
            'message' => 'Deposit import failed: ' . $e->getMessage(),
        ], 500);
    }
}



}