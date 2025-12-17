<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\DelayedCharges;
use App\Models\DelayedChargeLines;
use App\Models\DelayedCredits;
use App\Models\DelayedCreditLines;
use App\Models\CustomerType;
use App\Models\UnappliedPayment;
use App\Models\Purchase;
use App\Models\CreditNoteProduct;
use App\Models\CreditNote;
use App\Models\PurchaseOrderAccount;
use App\Models\TimeActivity;
use App\Models\User;
use App\Models\Utility;
use App\Models\VendorCredit;
use App\Models\VendorCreditAccount;
use App\Models\VendorCreditProduct;
use Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\Transaction;
use App\Models\ProposalProduct;
use App\Models\InvoiceProduct;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\Tax;
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
                if (
                    strpos($line, "user {$userId}") !== false ||
                    strpos($line, "QuickBooks") !== false ||
                    strpos($line, "Importing") !== false
                ) {

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
     * Map invoices with their payments using LINE-LEVEL amounts from QBO
     * This ensures accurate allocation - each payment line specifies exactly how much goes to each invoice
     */
    private function mapInvoicesWithPayments($allInvoices, $allPayments)
{
    $invoicesMap = [];
    $paymentsMap = [];
    $allocations = [];

    // ---------------------------------------------------------
    // 1. Build invoice map
    // ---------------------------------------------------------
    foreach ($allInvoices as $invoice) {
        $invoiceId   = (string)($invoice['Id'] ?? null);
        $totalAmount = (float)($invoice['TotalAmt'] ?? 0);

        if (!$invoiceId) {
            continue;
        }

        $invoicesMap[$invoiceId] = [
            'invoice_id'        => $invoiceId,
            'doc_number'        => $invoice['DocNumber'] ?? null,
            'customer_id'       => $invoice['CustomerRef']['value'] ?? null,
            'customer_name'     => $invoice['CustomerRef']['name'] ?? null,
            'txn_date'          => $invoice['TxnDate'] ?? null,
            'due_date'          => $invoice['DueDate'] ?? null,
            'total_amount'      => $totalAmount,
            'currency'          => $invoice['CurrencyRef']['name'] ?? 'USD',
            'raw_data'          => $invoice,
            'allocated_amount'  => 0.0,
            'status'            => 'unpaid',
            'allocations'       => [],
            'remaining_balance' => $totalAmount,
        ];
    }

    // ---------------------------------------------------------
    // 2. Build payments map + allocations using LINE amounts
    // ---------------------------------------------------------
    foreach ($allPayments as $payment) {
        $paymentId   = (string)($payment['Id'] ?? null);
        $totalAmount = (float)($payment['TotalAmt'] ?? 0);

        if (!$paymentId) {
            continue;
        }

        $paymentsMap[$paymentId] = [
            'payment_id'      => $paymentId,
            'customer_id'     => $payment['CustomerRef']['value'] ?? null,
            'customer_name'   => $payment['CustomerRef']['name'] ?? null,
            'txn_date'        => $payment['TxnDate'] ?? null,
            'total_amount'    => $totalAmount,
            'payment_method'  => $payment['PaymentMethodRef']['name'] ?? null,
            'linked_invoices' => [],
            'raw_data'        => $payment,
            'allocated_amount'=> 0.0,
        ];

        $appliedToInvoices = 0.0;

        // Each Line.Amount represents a distribution of this payment
        foreach ($payment['Line'] ?? [] as $line) {
            $lineAmountRemaining = (float)($line['Amount'] ?? 0);

            if ($lineAmountRemaining <= 0) {
                continue;
            }

            if (empty($line['LinkedTxn'])) {
                // Line without invoice link => will become unapplied / credit
                continue;
            }

            // Normalise LinkedTxn (can be single or array)
            $linkedTxns = is_array($line['LinkedTxn'][0] ?? null)
                ? $line['LinkedTxn']
                : [$line['LinkedTxn']];

            foreach ($linkedTxns as $txn) {
                if (($txn['TxnType'] ?? null) !== 'Invoice') {
                    continue;
                }

                $invId = (string)($txn['TxnId'] ?? null);
                if (!$invId || !isset($invoicesMap[$invId])) {
                    continue;
                }

                // In QBO, line Amount is already the amount applied to this invoice.
                $amountForThisInvoice = $lineAmountRemaining;
                if ($amountForThisInvoice <= 0) {
                    continue;
                }

                $allocations[] = [
                    'payment_id'       => $paymentId,
                    'invoice_id'       => $invId,
                    'allocated_amount' => $amountForThisInvoice,
                    'allocation_type'  => 'line_link',
                    'reason'           => 'Allocated based on QBO Payment Line.Amount',
                    'payment_date'     => $payment['TxnDate'] ?? null,
                ];

                $invoicesMap[$invId]['allocated_amount'] += $amountForThisInvoice;
                $invoicesMap[$invId]['allocations'][]     = [
                    'payment_id' => $paymentId,
                    'amount'     => $amountForThisInvoice,
                    'date'       => $payment['TxnDate'] ?? null,
                ];

                $paymentsMap[$paymentId]['allocated_amount'] += $amountForThisInvoice;
                $paymentsMap[$paymentId]['linked_invoices'][] = $invId;

                $appliedToInvoices   += $amountForThisInvoice;
                $lineAmountRemaining -= $amountForThisInvoice;

                // In practice a Payment line in QBO is tied to a single invoice,
                // so once we've used its Amount we break.
                if ($lineAmountRemaining <= 0.00001) {
                    break;
                }
            }
        }

        // Make linked_invoices unique
        $paymentsMap[$paymentId]['linked_invoices'] = array_values(
            array_unique($paymentsMap[$paymentId]['linked_invoices'])
        );

        // Any leftover on the payment is unapplied (customer credit, etc.)
        $unapplied = round($totalAmount - $appliedToInvoices, 2);
        if ($unapplied > 0.01) {
            $allocations[] = [
                'payment_id'       => $paymentId,
                'invoice_id'       => null,
                'allocated_amount' => $unapplied,
                'allocation_type'  => 'unapplied',
                'reason'           => 'Unapplied portion of payment',
                'payment_date'     => $payment['TxnDate'] ?? null,
            ];

            // For completeness – total allocated from this payment equals its TotalAmt
            $paymentsMap[$paymentId]['allocated_amount'] += $unapplied;
        }
    }

    // ---------------------------------------------------------
    // 3. Finalise invoice status & remaining balance
    // ---------------------------------------------------------
    foreach ($invoicesMap as $invId => &$invoice) {
        $total  = (float)$invoice['total_amount'];
        $alloc  = (float)$invoice['allocated_amount'];

        if ($alloc <= 0.01) {
            $invoice['status'] = 'unpaid';
        } elseif ($alloc >= $total - 0.01) {
            $invoice['status'] = 'fully_paid';
        } else {
            $invoice['status'] = 'partially_paid';
        }

        $invoice['remaining_balance'] = max(0, $total - $alloc);
    }
    unset($invoice);

    return [
        'invoices'   => array_values($invoicesMap),
        'payments'   => array_values($paymentsMap),
        'allocations'=> $allocations,
    ];
}


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

    //         // Get comprehensive mapping
    //         $mappedData = $this->mapInvoicesWithPayments($allInvoices, $allPayments);
    //         $invoicesData = collect($mappedData['invoices'])->keyBy('invoice_id')->toArray();
    //         $allocationsData = $mappedData['allocations'];

    //         // Helper functions for parsing (unchanged)
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

    //         // Updated parse: returns array lines (unchanged besides keeping RawLine intact)
    //         $parseInvoiceLine = function ($line) use ($detectAccountForSalesItem) {
    //             $out = [];
    //             $detailType = $line['DetailType'] ?? null;

    //             if (!empty($line['GroupLineDetail']) && !empty($line['GroupLineDetail']['Line'])) {
    //                 foreach ($line['GroupLineDetail']['Line'] as $child) {
    //                     if (!empty($child['SalesItemLineDetail'])) {
    //                         $sid = $child['SalesItemLineDetail'];
    //                         $acc = $detectAccountForSalesItem($sid);
    //                         $qty = (float) ($sid['Qty'] ?? 0);
    //                         if ($qty < 1) {
    //                             $qty = 1;
    //                         }

    //                         $out[] = [
    //                             'DetailType' => $child['DetailType'] ?? 'SalesItemLineDetail',
    //                             'Description' => $child['Description'] ?? $sid['ItemRef']['name'] ?? null,
    //                             'Amount' => $child['Amount'] ?? 0,
    //                             'Quantity' => $qty,
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
    //                 $qty = (float) ($sid['Qty'] ?? 0);
    //                 if ($qty < 1) {
    //                     $qty = 1;
    //                 }

    //                 $out[] = [
    //                     'DetailType' => $line['DetailType'] ?? 'SalesItemLineDetail',
    //                     'Description' => $line['Description'] ?? ($sid['ItemRef']['name'] ?? null),
    //                     'Amount' => $line['Amount'] ?? 0,
    //                     'Quantity' => $qty,
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

    //         $arAccount = $findARAccount();
    //         $taxAccount = $findTaxPayableAccount();

    //         // Import statistics
    //         $imported = 0;
    //         $skipped = 0;
    //         $failed = 0;
    //         $errors = [];

    //         DB::beginTransaction();
    //         try {
    //             // Process each invoice
    //             foreach ($invoicesData as $qbInvoiceData) {
    //                 try {
    //                     $qbId = $qbInvoiceData['invoice_id'];
    //                     $qbRawInvoice = $qbInvoiceData['raw_data'];

    //                     // Check for duplicate
    //                     $existing = Invoice::where('invoice_id', $qbId)
    //                         ->where('created_by', \Auth::user()->creatorId())
    //                         ->first();
    //                     if ($existing) {
    //                         \Log::warning("Invoice already exists: " . $qbId);
    //                         $skipped++;
    //                         continue;
    //                     }

    //                     // Find customer (unchanged)
    //                     $qbCustomerId = $qbInvoiceData['customer_id'] ?? null;
    //                     $qbCustomerName = $qbInvoiceData['customer_name'];

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
    //                         $errors[] = "Invoice {$qbId}: Customer not found ({$qbCustomerName})";
    //                         $skipped++;
    //                         continue;
    //                     }

    //                     $customerId = $customer->id;
    //                     $invoiceStatus = $qbInvoiceData['status'] == 'fully_paid' ? 4 : ($qbInvoiceData['status'] == 'partially_paid' ? 3 : 2);

    //                     // Create invoice (we will update totals later)
    //                     $invoice = Invoice::create([
    //                         'invoice_id' => $qbId,
    //                         'customer_id' => $customerId,
    //                         'issue_date' => $qbInvoiceData['txn_date'],
    //                         'due_date' => $qbInvoiceData['due_date'],
    //                         'ref_number' => $qbInvoiceData['doc_number'],
    //                         'send_date' => $qbInvoiceData['txn_date'],
    //                         'status' => $invoiceStatus,
    //                         'created_by' => \Auth::user()->creatorId(),
    //                         'owned_by' => \Auth::user()->ownedId(),
    //                         'created_at' => Carbon::parse($qbInvoiceData['txn_date'])->format('Y-m-d H:i:s'),
    //                         'updated_at' => Carbon::parse($qbInvoiceData['txn_date'])->format('Y-m-d H:i:s'),
    //                     ]);

    //                     // Parse and create invoice lines
    //                     $parsedLines = [];
    //                     foreach ($qbRawInvoice['Line'] ?? [] as $line) {
    //                         $parsedLines = array_merge($parsedLines, $parseInvoiceLine($line));
    //                     }

    //                     // Separate product lines and tax lines
    //                     $productLines = array_values(array_filter($parsedLines, fn($l) => $l['HasProduct']));
    //                     $taxLines = array_values(array_filter($parsedLines, fn($l) => stripos($l['DetailType'] ?? '', 'Tax') !== false || !empty($l['RawLine']['TaxLineDetail'])));

    //                     // Sum totals for allocation
    //                     $productLinesTotal = array_sum(array_map(fn($l) => (float) $l['Amount'], $productLines));
    //                     $invoiceTaxTotal = array_sum(array_map(fn($t) => (float) $t['Amount'], $taxLines));

    //                     // Some QuickBooks payloads might store a top-level SalesTax or TxnTaxDetail
    //                     // try to fallback to qbInvoiceData if available
    //                     if ($invoiceTaxTotal <= 0) {
    //                         $invoiceTaxTotal = $qbInvoiceData['sales_tax_amount'] ?? $qbInvoiceData['total_tax'] ?? $qbRawInvoice['TxnTaxDetail']['TotalTax'] ?? $invoiceTaxTotal;
    //                         $invoiceTaxTotal = $invoiceTaxTotal ? (float) $invoiceTaxTotal : 0;
    //                     }

    //                     // If QuickBooks provides per-line tax amounts use them; otherwise allocate proportionally.
    //                     // Build per-product tax amounts
    //                     $productTaxAmounts = []; // keyed by product index
    //                     foreach ($productLines as $idx => $pline) {
    //                         // look for inline tax amount inside RawLine or SalesItemLineDetail
    //                         $raw = $pline['RawLine'] ?? [];
    //                         $inlineTax = null;

    //                         // Common places QuickBooks might put line-level tax
    //                         if (isset($raw['SalesItemLineDetail']['TaxAmount'])) {
    //                             $inlineTax = (float) $raw['SalesItemLineDetail']['TaxAmount'];
    //                         } elseif (isset($raw['TaxAmount'])) {
    //                             $inlineTax = (float) $raw['TaxAmount'];
    //                         } elseif (isset($raw['Amount']) && isset($raw['SalesItemLineDetail']['TaxRateRef'])) {
    //                             // sometimes tax rate ref exists but no explicit amount; skip
    //                             $inlineTax = null;
    //                         }

    //                         if ($inlineTax !== null) {
    //                             $productTaxAmounts[$idx] = $inlineTax;
    //                         } else {
    //                             // allocate proportionally (line amount / product total) * invoiceTaxTotal
    //                             if ($productLinesTotal > 0 && $invoiceTaxTotal > 0) {
    //                                 $productTaxAmounts[$idx] = round(((float) $pline['Amount'] / $productLinesTotal) * $invoiceTaxTotal, 2);
    //                             } else {
    //                                 $productTaxAmounts[$idx] = 0.00;
    //                             }
    //                         }
    //                     }

    //                     // Now create product records with tax fields
    //                     $subtotal = 0.00;
    //                     $taxable_subtotal = 0.00;
    //                     $total_tax = 0.00;
    //                     $total_discount = 0.00; // not provided in your payload; keep 0 unless you derive discounts later
    //                     // dd($productLines);
    //                     foreach ($productLines as $idx => $line) {
    //                         if (!$line['HasProduct'])
    //                             continue;

    //                         $itemName = $line['ItemName'];
    //                         if (!$itemName)
    //                             continue;

    //                         $product = ProductService::where('name', $itemName)
    //                             ->where('created_by', \Auth::user()->creatorId())
    //                             ->first();

    //                         if (!$product) {
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
    //                                 'sale_price' => $line['Amount'] ?? 0,
    //                                 'purchase_price' => 0,
    //                                 'quantity' => 0,
    //                                 'unit_id' => $unit->id,
    //                                 'type' => 'product',
    //                                 'category_id' => $productCategory->id,
    //                                 'created_by' => \Auth::user()->creatorId(),
    //                             ];

    //                             if (!empty($line['AccountId'])) {
    //                                 $account = ChartOfAccount::where('code', $line['AccountId'])
    //                                     ->where('created_by', \Auth::user()->creatorId())
    //                                     ->first();
    //                                 if ($account) {
    //                                     $productData['sale_chartaccount_id'] = $account->id;
    //                                 }
    //                             }

    //                             $product = ProductService::create($productData);
    //                         }

    //                         $quantity = (float) ($line['Quantity'] ?? 1);
    //                         if ($quantity < 1) {
    //                             $quantity = 1;
    //                         }

    //                         $lineBaseAmount = (float) ($line['Amount'] ?? 0); // amount excluding tax (QB)
    //                         $lineTaxAmount = $productTaxAmounts[$idx] ?? 0.00;
    //                         $lineTaxable = $lineTaxAmount > 0 ? 1 : 0;

    //                         // compute tax rate (guard division by zero)
    //                         $lineTaxRatePercent = 0.00;
    //                         if ($lineBaseAmount > 0 && $lineTaxAmount > 0) {
    //                             $lineTaxRatePercent = round(($lineTaxAmount / $lineBaseAmount) * 100, 4);
    //                         }

    //                         $lineTotal = $lineBaseAmount + $lineTaxAmount; // per-line total including tax

    //                         // create invoice product with tax fields
    //                         InvoiceProduct::create([
    //                             'invoice_id' => $invoice->id,
    //                             'product_id' => $product->id,
    //                             'quantity' => $quantity,
    //                             'tax' => $lineTaxAmount,                        // total tax amount for this line
    //                             'discount' => 0.00,
    //                             'total' => $lineTotal,
    //                             'price' => $lineBaseAmount / $quantity,
    //                             'description' => $line['Description'] ?? null,
    //                             'taxable' => $lineTaxable,
    //                             'item_tax_price' => $lineTaxAmount,             // tax monetary amount
    //                             'item_tax_rate' => $lineTaxRatePercent,         // tax percent
    //                             'amount' => $lineBaseAmount,                    // base amount excluding tax
    //                             'estimate_id' => null,
    //                             'line_type' => 'item',
    //                             'proposal_product_id' => null,
    //                         ]);

    //                         $subtotal += $lineBaseAmount;
    //                         if ($lineTaxable) {
    //                             $taxable_subtotal += $lineBaseAmount;
    //                         }
    //                         $total_tax += $lineTaxAmount;
    //                     }

    //                     // If there are standalone tax lines not allocated above, make sure totals include them
    //                     // We used invoiceTaxTotal (derived from tax lines) to allocate; but in rare cases if something left add it:
    //                     $remainingTaxFromTaxLines = max(0, $invoiceTaxTotal - $total_tax);
    //                     if ($remainingTaxFromTaxLines > 0) {
    //                         // add to invoice total tax (don't attach to any product)
    //                         $total_tax += $remainingTaxFromTaxLines;
    //                     }

    //                     // Compute invoice totals
    //                     $computedTotalAmount = $subtotal + $total_tax - $total_discount;

    //                     // Update invoice with totals and tax fields
    //                     $invoice->update([
    //                         'subtotal' => round($subtotal, 2),
    //                         'taxable_subtotal' => round($taxable_subtotal, 2),
    //                         'total_discount' => round($total_discount, 2),
    //                         'total_tax' => round($total_tax, 2),
    //                         'sales_tax_amount' => round($total_tax, 2),
    //                         'total_amount' => round($computedTotalAmount, 2),
    //                     ]);

    //                     // Create invoice payments based on allocations (unchanged)
    //                     $invoiceAllocations = array_filter($allocationsData, fn($a) => $a['invoice_id'] == $qbId);

    //                     foreach ($invoiceAllocations as $allocation) {
    //                         $paymentId = $allocation['payment_id'];
    //                         $allocatedAmount = $allocation['allocated_amount'];

    //                         // Find payment details
    //                         $paymentData = collect($allPayments)->firstWhere('Id', $paymentId);
    //                         if (!$paymentData) {
    //                             continue;
    //                         }

    //                         $bankAccountId = null;
    //                         $paymentMethod = $paymentData['PaymentMethodRef']['name'] ?? null;

    //                         if (!$paymentMethod) {
    //                             if (isset($paymentData['CreditCardPayment'])) {
    //                                 $paymentMethod = 'Credit Card';
    //                             } elseif (isset($paymentData['CheckPayment'])) {
    //                                 $paymentMethod = 'Check';
    //                             } elseif (isset($paymentData['DepositToAccountRef'])) {
    //                                 $depositAccountRef = json_decode(json_encode($paymentData['DepositToAccountRef'] ?? []), true);
    //                                 $accountCode = $depositAccountRef['value'] ?? null;
    //                                 $accountName = $depositAccountRef['name'] ?? 'Bank Account';

    //                                 $bankAccountId = $this->getOrCreateBankAccountFromChartAccount($accountCode, $accountName);

    //                                 if ($bankAccountId) {
    //                                     $accountId = $depositAccountRef['value'] ?? null;
    //                                     $account = collect($accountsMap)->firstWhere('Id', $accountId);

    //                                     if ($account) {
    //                                         $accountType = strtolower($account['AccountType'] ?? '');
    //                                         if (strpos($accountType, 'bank') !== false || strpos($accountType, 'checking') !== false) {
    //                                             $paymentMethod = 'Bank Transfer';
    //                                         } elseif (strpos($accountType, 'credit') !== false) {
    //                                             $paymentMethod = 'Credit Card';
    //                                         } else {
    //                                             $paymentMethod = 'Cash';
    //                                         }
    //                                     } else {
    //                                         $paymentMethod = 'Bank Transfer';
    //                                     }
    //                                 } else {
    //                                     $errors[] = "Invoice {$qbId}: Could not find bank account for payment {$paymentId}";
    //                                     continue;
    //                                 }
    //                             } else {
    //                                 $paymentMethod = 'Cash';
    //                             }
    //                         }

    //                         if (!$bankAccountId && !$paymentMethod) {
    //                             $errors[] = "Invoice {$qbId}: No bank account or payment method found for payment {$paymentId}";
    //                             continue;
    //                         }

    //                         // Create payment record
    //                         InvoicePayment::create([
    //                             'invoice_id' => $invoice->id,
    //                             'date' => $allocation['payment_date'],
    //                             'amount' => $allocatedAmount,
    //                             'account_id' => $bankAccountId,
    //                             'payment_method' => $paymentMethod,
    //                             'txn_id' => $paymentId,
    //                             'currency' => $qbInvoiceData['currency'],
    //                             'reference' => $paymentId,
    //                             'description' => 'Payment for Invoice ' . $qbInvoiceData['doc_number'],
    //                             'created_at' => Carbon::parse($allocation['payment_date'])->format('Y-m-d H:i:s'),
    //                             'updated_at' => Carbon::parse($allocation['payment_date'])->format('Y-m-d H:i:s'),
    //                         ]);

    //                         // Update bank account balance
    //                         if ($bankAccountId) {
    //                             Utility::bankAccountBalance($bankAccountId, $allocatedAmount, 'credit');
    //                         }
    //                     }

    //                     // Update customer balance
    //                     if ($customer) {
    //                         // Debit for invoice amount
    //                         Utility::updateUserBalance('customer', $customer->id, $invoice->total_amount, 'debit');

    //                         // Credit for paid amount
    //                         if ($qbInvoiceData['allocated_amount'] > 0) {
    //                             Utility::updateUserBalance('customer', $customer->id, $qbInvoiceData['allocated_amount'], 'credit');
    //                         }
    //                     }

    //                     $imported++;

    //                 } catch (\Exception $e) {
    //                     \Log::error("Failed to import invoice {$qbId}: " . $e->getMessage());
    //                     $errors[] = "Invoice {$qbId}: " . $e->getMessage();
    //                     $failed++;
    //                     continue;
    //                 }
    //             }

    //             DB::commit();
    //         } catch (\Exception $e) {
    //             DB::rollBack();
    //             \Log::error("Invoices import transaction error: " . $e->getMessage());
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Import transaction failed: ' . $e->getMessage(),
    //                 'errors' => $errors,
    //             ], 500);
    //         }

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => "Invoices import completed successfully",
    //             'imported' => $imported,
    //             'skipped' => $skipped,
    //             'failed' => $failed,
    //             'errors' => $errors,
    //             'summary' => [
    //                 'total_invoices_processed' => $imported + $skipped + $failed,
    //                 'successfully_imported' => $imported,
    //                 'skipped_invoices' => $skipped,
    //                 'failed_invoices' => $failed,
    //                 'invoice_count' => count($invoicesData),
    //                 'allocation_count' => count($allocationsData),
    //             ]
    //         ]);

    //     } catch (\Exception $e) {
    //         \Log::error("Invoices import error: " . $e->getMessage());
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Error: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }


    public function importInvoices(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(600);

        try {
            \Log::info("==========================================");
            \Log::info("STARTING QBO IMPORT PROCESS");
            \Log::info("==========================================");

            // =================================================================
            // STEP 1: FETCH DATA BATCHES
            // =================================================================

            // 1.1 Invoices
            $allInvoices = collect();
            $startPosition = 1; $maxResults = 50;
            do {
                $query = "SELECT * FROM Invoice STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $invoicesResponse = $this->qbController->runQuery($query);
                if ($invoicesResponse instanceof \Illuminate\Http\JsonResponse) return $invoicesResponse;
                $invoicesData = $invoicesResponse['QueryResponse']['Invoice'] ?? [];
                $allInvoices = $allInvoices->merge($invoicesData);
                $startPosition += count($invoicesData);
            } while (count($invoicesData) === $maxResults);

            // 1.2 Payments
            $allPayments = collect();
            $startPosition = 1;
            do {
                $query = "SELECT * FROM Payment STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $res = $this->qbController->runQuery($query);
                $data = $res['QueryResponse']['Payment'] ?? [];
                $allPayments = $allPayments->merge($data);
                $startPosition += count($data);
            } while (count($data) === $maxResults);

            // 1.3 Deposits
            $allDeposits = collect();
            $startPosition = 1;
            do {
                $query = "SELECT * FROM Deposit STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $res = $this->qbController->runQuery($query);
                $data = $res['QueryResponse']['Deposit'] ?? [];
                $allDeposits = $allDeposits->merge($data);
                $startPosition += count($data);
            } while (count($data) === $maxResults);

            // 1.4 Credit Memos
            $allCreditMemos = collect();
            $startPosition = 1;
            do {
                $query = "SELECT * FROM CreditMemo STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $res = $this->qbController->runQuery($query);
                $data = $res['QueryResponse']['CreditMemo'] ?? [];
                $allCreditMemos = $allCreditMemos->merge($data);
                $startPosition += count($data);
            } while (count($data) === $maxResults);

            // 1.5 Estimates
            $allEstimates = collect();
            $startPosition = 1;
            do {
                $query = "SELECT * FROM Estimate STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $res = $this->qbController->runQuery($query);
                $data = $res['QueryResponse']['Estimate'] ?? [];
                $allEstimates = $allEstimates->merge($data);
                $startPosition += count($data);
            } while (count($data) === $maxResults);

            // 1.6 Metadata
            $itemsRaw    = $this->qbController->runQuery("SELECT * FROM Item STARTPOSITION 1 MAXRESULTS 500");
            $accountsRaw = $this->qbController->runQuery("SELECT * FROM Account STARTPOSITION 1 MAXRESULTS 500");
            $taxCodesRaw = $this->qbController->runQuery("SELECT * FROM TaxCode STARTPOSITION 1 MAXRESULTS 100");
            $taxRatesRaw = $this->qbController->runQuery("SELECT * FROM TaxRate STARTPOSITION 1 MAXRESULTS 100");

            $itemsList    = collect($itemsRaw['QueryResponse']['Item'] ?? []);
            $accountsList = collect($accountsRaw['QueryResponse']['Account'] ?? []);
            $taxCodesList = collect($taxCodesRaw['QueryResponse']['TaxCode'] ?? []);
            $taxRatesList = collect($taxRatesRaw['QueryResponse']['TaxRate'] ?? []);

            $itemsMap    = $itemsList->keyBy('Id')->toArray();
            $accountsMap = $accountsList->keyBy('Id')->toArray();
            $taxCodesMap = $taxCodesList->keyBy('Id')->toArray();
            $taxRatesMap = $taxRatesList->keyBy('Id')->toArray();

            DB::beginTransaction();

            // =================================================================
            // STEP 2: CREDIT MEMOS (unchanged)
            // =================================================================
            \Log::info("--- START STEP 2: CREDIT MEMOS ---");
            foreach ($allCreditMemos as $qbCredit) {
                try {
                    $creditId  = $qbCredit['Id'];
                    $qbCustRef = $qbCredit['CustomerRef']['value'] ?? null;

                    $cust = null;
                    if ($qbCustRef) {
                        $cust = \App\Models\Customer::where('customer_id', $qbCustRef)->first();
                    }
                    if (!$cust) {
                        continue;
                    }

                    $localCredit = \App\Models\CreditNote::where('credit_note_id', $creditId)->first();
                    if (!$localCredit) {
                        $localCredit = \App\Models\CreditNote::create([
                            'credit_note_id' => $creditId,
                            'customer'       => $cust->id,
                            'date'           => $qbCredit['TxnDate'],
                            'amount'         => $qbCredit['TotalAmt'] ?? 0,
                            'description'    => $qbCredit['PrivateNote'] ?? null,
                            'created_by'     => \Auth::user()->creatorId(),
                            'payment_id'     => null,
                        ]);

                        if (!empty($qbCredit['Line'])) {
                            foreach ($qbCredit['Line'] as $line) {
                                if (isset($line['SalesItemLineDetail'])) {
                                    $d = $line['SalesItemLineDetail'];
                                    $itemName = $d['ItemRef']['name'] ?? 'Item';

                                    $localProd = ProductService::where('name', $itemName)->first();
                                    if (!$localProd) {
                                        $u = ProductServiceUnit::firstOrCreate(['name' => 'pcs']);
                                        $c = ProductServiceCategory::firstOrCreate(['name' => 'General']);
                                        $localProd = ProductService::create([
                                            'name'       => $itemName,
                                            'sku'        => $itemName,
                                            'sale_price' => $line['Amount'],
                                            'unit_id'    => $u->id,
                                            'category_id'=> $c->id,
                                            'created_by' => \Auth::user()->creatorId(),
                                        ]);
                                    }

                                    \App\Models\CreditNoteProduct::create([
                                        'credit_note_id' => $localCredit->id,
                                        'product_id'     => $localProd->id,
                                        'quantity'       => $d['Qty'] ?? 1,
                                        'price'          => $d['UnitPrice'] ?? 0,
                                        'description'    => $line['Description'] ?? null,
                                    ]);
                                }
                            }
                        }

                        Utility::updateUserBalance('customer', $cust->id, $qbCredit['TotalAmt'], 'credit');
                    }
                } catch (\Exception $e) {
                    \Log::error("Credit Memo Import Fail {$qbCredit['Id']}: " . $e->getMessage());
                }
            }

            // =================================================================
            // STEP 3: ESTIMATES (unchanged)
            // =================================================================
            \Log::info("--- START STEP 3: ESTIMATES ---");
            $localMap = [
                'Estimate'      => [],
                'TimeActivity'  => [],
                'Charge'        => [],
                'DelayedCredit' => [],
            ];
            $proposalProductMap = [];

            foreach ($allEstimates as $qbEst) {
                $qbCustRef = $qbEst['CustomerRef']['value'] ?? null;
                $cust = $qbCustRef
                    ? \App\Models\Customer::where('customer_id', $qbCustRef)
                        ->where('created_by', \Auth::user()->creatorId())
                        ->first()
                    : null;
                if (!$cust) {
                    continue;
                }

                $localProp = \App\Models\Proposal::where('proposal_id', $qbEst['Id'])
                    ->where('created_by', \Auth::user()->creatorId())
                    ->first();

                if (!$localProp) {
                    $localProp = \App\Models\Proposal::create([
                        'proposal_id' => $qbEst['Id'],
                        'customer_id' => $cust->id,
                        'issue_date'  => $qbEst['TxnDate'],
                        'send_date'   => $qbEst['TxnDate'],
                        'status'      => 0,
                        'is_convert'  => 0,
                        'subtotal'    => $qbEst['TotalAmt'] ?? 0,
                        'total_amount'=> $qbEst['TotalAmt'] ?? 0,
                        'created_by'  => \Auth::user()->creatorId(),
                        'owned_by'    => \Auth::user()->ownedId(),
                    ]);

                    if (!empty($qbEst['Line'])) {
                        foreach ($qbEst['Line'] as $estLine) {
                            if (isset($estLine['SalesItemLineDetail'])) {
                                $itemName = $estLine['SalesItemLineDetail']['ItemRef']['name'] ?? 'Item';
                                $localProd = ProductService::where('name', $itemName)->first();
                                $prodId = $localProd ? $localProd->id : 0;

                                $pp = \App\Models\ProposalProduct::create([
                                    'proposal_id' => $localProp->id,
                                    'product_id'  => $prodId,
                                    'quantity'    => $estLine['SalesItemLineDetail']['Qty'] ?? 1,
                                    'price'       => $estLine['SalesItemLineDetail']['UnitPrice'] ?? 0,
                                    'amount'      => $estLine['Amount'] ?? 0,
                                    'description' => $estLine['Description'] ?? null,
                                ]);

                                $proposalProductMap[$qbEst['Id']][$itemName] = $pp->id;
                            }
                        }
                    }
                } else {
                    $prods = \App\Models\ProposalProduct::where('proposal_id', $localProp->id)->get();
                    foreach ($prods as $p) {
                        $pName = ProductService::find($p->product_id)->name ?? null;
                        if ($pName) {
                            $proposalProductMap[$qbEst['Id']][$pName] = $p->id;
                        }
                    }
                }

                $localMap['Estimate'][$qbEst['Id']] = $localProp->id;
            }

            // =================================================================
            // STEP 4: PRE-PROCESS LINKED TXNS (unchanged)
            // =================================================================
            $linkedIds = ['TimeActivity' => [], 'Charge' => [], 'CreditMemo' => []];

            foreach ($allInvoices as $inv) {
                if (!empty($inv['Line'])) {
                    foreach ($inv['Line'] as $line) {
                        if (!empty($line['LinkedTxn'])) {
                            foreach ($line['LinkedTxn'] as $link) {
                                $type = $link['TxnType'];
                                if (array_key_exists($type, $linkedIds)) {
                                    $linkedIds[$type][] = $link['TxnId'];
                                }
                            }
                        }
                    }
                }
            }

            foreach ($linkedIds as $key => $ids) {
                $linkedIds[$key] = array_unique($ids);
            }

            // 4.1 Time Activities
            if (!empty($linkedIds['TimeActivity'])) {
                $chunks = array_chunk($linkedIds['TimeActivity'], 30);
                foreach ($chunks as $chunk) {
                    $idsString = "'" . implode("', '", $chunk) . "'";
                    $res = $this->qbController->runQuery("SELECT * FROM TimeActivity WHERE Id IN ($idsString)");
                    $fetched = $res['QueryResponse']['TimeActivity'] ?? [];
                    foreach ($fetched as $qbTime) {
                        $newTime = \App\Models\TimeActivity::create([
                            'user_id'   => \Auth::user()->id,
                            'date'      => $qbTime['TxnDate'],
                            'duration'  => sprintf(
                                '%02d:%02d',
                                floor(($qbTime['Hours'] ?? 0)),
                                ($qbTime['Minutes'] ?? 0)
                            ),
                            'billable'  => ($qbTime['BillableStatus'] ?? '') == 'Billable' ? 1 : 0,
                            'rate'      => $qbTime['HourlyRate'] ?? 0,
                            'created_by'=> \Auth::user()->creatorId(),
                        ]);
                        $localMap['TimeActivity'][$qbTime['Id']] = $newTime->id;
                    }
                }
            }

            // 4.2 Delayed Charges
            if (!empty($linkedIds['Charge'])) {
                $chunks = array_chunk($linkedIds['Charge'], 30);
                foreach ($chunks as $chunk) {
                    $idsString = "'" . implode("', '", $chunk) . "'";
                    $res = $this->qbController->runQuery("SELECT * FROM Charge WHERE Id IN ($idsString)");
                    $fetched = $res['QueryResponse']['Charge'] ?? [];
                    foreach ($fetched as $qbCharge) {
                        $qbCustRef = $qbCharge['CustomerRef']['value'] ?? null;
                        $cust = $qbCustRef
                            ? \App\Models\Customer::where('customer_id', $qbCustRef)->first()
                            : null;
                        if (!$cust) {
                            continue;
                        }

                        $localCharge = \App\Models\DelayedCharge::create([
                            'charge_id'   => $qbCharge['Id'],
                            'customer_id' => $cust->id,
                            'date'        => $qbCharge['TxnDate'],
                            'amount'      => $qbCharge['TotalAmt'] ?? 0,
                            'is_invoiced' => 1,
                            'created_by'  => \Auth::user()->creatorId(),
                        ]);

                        $localMap['Charge'][$qbCharge['Id']] = $localCharge->id;
                    }
                }
            }

            // 4.3 Delayed Credits
            if (!empty($linkedIds['CreditMemo'])) {
                $chunks = array_chunk($linkedIds['CreditMemo'], 30);
                foreach ($chunks as $chunk) {
                    $idsString = "'" . implode("', '", $chunk) . "'";
                    $res = $this->qbController->runQuery("SELECT * FROM CreditMemo WHERE Id IN ($idsString)");
                    $fetched = $res['QueryResponse']['CreditMemo'] ?? [];
                    foreach ($fetched as $qbCredit) {
                        $qbCustRef = $qbCredit['CustomerRef']['value'] ?? null;
                        $cust = $qbCustRef
                            ? \App\Models\Customer::where('customer_id', $qbCustRef)->first()
                            : null;
                        if (!$cust) {
                            continue;
                        }

                        $localCredit = \App\Models\DelayedCredit::create([
                            'credit_id'     => $qbCredit['Id'],
                            'type'          => 'DelayedCredit',
                            'customer_id'   => $cust->id,
                            'date'          => $qbCredit['TxnDate'],
                            'total_amount'  => $qbCredit['TotalAmt'] ?? 0,
                            'created_by'    => \Auth::user()->creatorId(),
                        ]);

                        $localMap['DelayedCredit'][$qbCredit['Id']] = $localCredit->id;
                    }
                }
            }

            // =================================================================
            // STEP 5: HELPERS & PAYMENT MAPPING
            // =================================================================
            $mappedData     = $this->mapInvoicesWithPayments($allInvoices, $allPayments);
            $invoicesData   = collect($mappedData['invoices'])->keyBy('invoice_id')->toArray();
            $allocationsData= $mappedData['allocations'];

            $detectAccount = function ($sid) use ($accountsMap) {
                if (!empty($sid['ItemAccountRef']['value'])) {
                    return $sid['ItemAccountRef']['value'];
                }
                return null;
            };

            $getOrCreateTaxRate = function ($qbTaxRateRef) use ($taxRatesMap) {
                if (empty($qbTaxRateRef)) {
                    return ['id' => 0, 'rate' => 0];
                }

                $rateData = $taxRatesMap[$qbTaxRateRef] ?? null;
                if (!$rateData) {
                    return ['id' => 0, 'rate' => 0];
                }

                $rateName = $rateData['Name'] ?? 'Tax';
                $localTax = \App\Models\Tax::where('name', $rateName)->first();
                if (!$localTax) {
                    $localTax = \App\Models\Tax::create([
                        'name'       => $rateName,
                        'rate'       => $rateData['RateValue'],
                        'created_by' => \Auth::user()->creatorId(),
                    ]);
                }

                return ['id' => $localTax->id, 'rate' => $localTax->rate];
            };

            $parseInvoiceLine = function ($line) use ($detectAccount, $localMap, $proposalProductMap) {
                $out = [];
                $linkData = [
                    'estimate_id'        => null,
                    'proposal_product_id'=> null,
                    'time_activity_id'   => null,
                    'delayed_charge_id'  => null,
                    'delayed_credit_id'  => null,
                    'line_type'          => 'item',
                ];

                if (!empty($line['LinkedTxn'])) {
                    foreach ($line['LinkedTxn'] as $link) {
                        $tid = $link['TxnId'];
                        switch ($link['TxnType']) {
                            case 'Estimate':
                                $linkData['estimate_id'] = $localMap['Estimate'][$tid] ?? null;
                                $linkData['line_type'] = 'proposal';
                                break;
                            case 'TimeActivity':
                                $linkData['time_activity_id'] = $localMap['TimeActivity'][$tid] ?? null;
                                $linkData['line_type'] = 'time_activity';
                                break;
                            case 'Charge':
                                $linkData['delayed_charge_id'] = $localMap['Charge'][$tid] ?? null;
                                $linkData['line_type'] = 'delayed_charge';
                                break;
                            case 'CreditMemo':
                                $linkData['delayed_credit_id'] = $localMap['DelayedCredit'][$tid] ?? null;
                                $linkData['line_type'] = 'delayed_credit';
                                break;
                        }
                    }
                }

                $processDetail = function ($detail, $rawLine) use ($detectAccount, $linkData, $proposalProductMap) {
                    $sid = $detail;

                    if ($linkData['estimate_id']) {
                        $itemName = $sid['ItemRef']['name'] ?? null;
                        if ($itemName) {
                            $linkData['proposal_product_id'] =
                                $proposalProductMap[$linkData['estimate_id']][$itemName] ?? null;
                        }
                    }

                    return array_merge($linkData, [
                        'DetailType'   => 'SalesItemLineDetail',
                        'Description'  => $rawLine['Description'] ?? $sid['ItemRef']['name'] ?? null,
                        'Amount'       => $rawLine['Amount'] ?? 0,
                        'Quantity'     => $sid['Qty'] ?? 1,
                        'ItemName'     => $sid['ItemRef']['name'] ?? 'Unknown Item',
                        'AccountId'    => $detectAccount($sid),
                        'RawLine'      => $rawLine,
                        'HasProduct'   => true,
                        'QBTaxCodeRef' => $sid['TaxCodeRef']['value'] ?? null,
                    ]);
                };

                if (!empty($line['GroupLineDetail']['Line'])) {
                    foreach ($line['GroupLineDetail']['Line'] as $child) {
                        if (!empty($child['SalesItemLineDetail'])) {
                            $out[] = $processDetail($child['SalesItemLineDetail'], $child);
                        }
                    }
                } elseif (!empty($line['SalesItemLineDetail'])) {
                    $out[] = $processDetail($line['SalesItemLineDetail'], $line);
                } else {
                    // Check if it's a DescriptionOnly line (usually 0 amount)
                    $detailType = $line['DetailType'] ?? null;
                    $isDescOnly = ($detailType == 'DescriptionOnly');

                    $out[] = array_merge($linkData, [
                        'DetailType'   => $detailType,
                        'Description'  => $line['Description'] ?? null,
                        'Amount'       => $line['Amount'] ?? 0,
                        'Quantity'     => 1, // Default qty for calculation safety
                        'ItemName'     => 'Description', // Default item name
                        'HasProduct'   => $isDescOnly, // Process it if it's a description line
                        'RawLine'      => $line,
                        'AccountId'    => null,
                        'QBTaxCodeRef' => null,
                    ]);
                }

                return $out;
            };

            // =================================================================
            // STEP 6: IMPORT INVOICES
            // =================================================================
            \Log::info("--- START STEP 6: INVOICES ---");
            $imported = 0;
            $skipped  = 0;
            $failed   = 0;
            $errors   = [];

            foreach ($invoicesData as $qbInvoiceData) {
                try {
                    $qbId        = $qbInvoiceData['invoice_id'];
                    $qbRawInvoice= $qbInvoiceData['raw_data'];

                    \Log::info("PROCESSING INVOICE ID: {$qbId}");

                    if (Invoice::where('invoice_id', $qbId)->exists()) {
                        $skipped++;
                        continue;
                    }

                    $qbCustRef = $qbInvoiceData['customer_id'] ?? null;
                    $customer  = $qbCustRef
                        ? Customer::where('customer_id', $qbCustRef)
                            ->where('created_by', \Auth::user()->creatorId())
                            ->first()
                        : null;

                    if (!$customer) {
                        $errors[] = "Invoice {$qbId}: Customer missing";
                        $skipped++;
                        continue;
                    }

                    // Header tax
                    $invoiceTaxId        = 0;
                    $invoiceTaxRate      = 0;
                    $invoiceSalesTaxAmount = 0;

                    if (isset($qbRawInvoice['TxnTaxDetail'])) {
                        $invoiceSalesTaxAmount = (float)($qbRawInvoice['TxnTaxDetail']['TotalTax'] ?? 0);
                        if (!empty($qbRawInvoice['TxnTaxDetail']['TaxLine'])) {
                            foreach ($qbRawInvoice['TxnTaxDetail']['TaxLine'] as $taxLine) {
                                if (($taxLine['DetailType'] ?? '') == 'TaxLineDetail'
                                    && isset($taxLine['TaxLineDetail']['TaxRateRef']['value'])) {
                                    $tData = $getOrCreateTaxRate($taxLine['TaxLineDetail']['TaxRateRef']['value']);
                                    $invoiceTaxId   = $tData['id'];
                                    $invoiceTaxRate = $tData['rate'];
                                    break;
                                }
                            }
                        }
                    }

                    $invoice = Invoice::create([
                        'invoice_id'  => $qbId,
                        'customer_id' => $customer->id,
                        'issue_date'  => $qbInvoiceData['txn_date'],
                        'due_date'    => $qbInvoiceData['due_date'],
                        'ref_number'  => $qbInvoiceData['doc_number'],
                        'send_date'   => $qbInvoiceData['txn_date'],
                        'status'      => $qbInvoiceData['status'] == 'fully_paid'
                            ? 4
                            : ($qbInvoiceData['status'] == 'partially_paid' ? 3 : 2),
                        'created_by'  => \Auth::user()->creatorId(),
                        'owned_by'    => \Auth::user()->ownedId(),
                        'tax_id'      => $invoiceTaxId,
                        'tax_rate'    => $invoiceTaxRate,
                        'sales_tax_amount' => $invoiceSalesTaxAmount,
                    ]);

                    // Lines / products
                    $productLines = [];
                    foreach ($qbRawInvoice['Line'] ?? [] as $line) {
                        $productLines = array_merge($productLines, $parseInvoiceLine($line));
                    }

                    $subtotal         = 0;
                    $taxableSubtotal  = 0;

                    foreach ($productLines as $line) {
                        if (!$line['HasProduct']) {
                            continue;
                        }

                        $itemName = $line['ItemName'];
                        $product  = ProductService::where('name', $itemName)->first();

                        if (!$product) {
                            $unit = ProductServiceUnit::firstOrCreate(['name' => 'pcs']);
                            $cat  = ProductServiceCategory::firstOrCreate(['name' => 'Product']);
                            $product = ProductService::create([
                                'name'       => $itemName,
                                'sku'        => $itemName,
                                'sale_price' => $line['Amount'],
                                'unit_id'    => $unit->id,
                                'category_id'=> $cat->id,
                                'created_by' => \Auth::user()->creatorId(),
                            ]);
                        }

                        $isTaxable = ($line['QBTaxCodeRef'] ?? 'NON') !== 'NON';
                        $lineAmt   = (float)$line['Amount'];
                        $lineTax   = 0;

                        if ($isTaxable && $invoiceTaxRate > 0) {
                            $lineTax = round(($lineAmt * $invoiceTaxRate) / 100, 2);
                            $taxableSubtotal += $lineAmt;
                        }

                        $finalLinkedId = null;
                        if ($line['delayed_charge_id']) {
                            $finalLinkedId = $line['delayed_charge_id'];
                        } elseif ($line['delayed_credit_id']) {
                            $finalLinkedId = $line['delayed_credit_id'];
                        } elseif ($line['estimate_id']) {
                            $finalLinkedId = $line['estimate_id'];
                            \App\Models\Proposal::where('id', $finalLinkedId)->update([
                                'converted_invoice_id' => $invoice->id,
                                'status'               => 2,
                                'is_convert'           => 1,
                            ]);
                        } elseif ($line['time_activity_id']) {
                            $finalLinkedId = $line['time_activity_id'];
                        }

                        InvoiceProduct::create([
                            'invoice_id'        => $invoice->id,
                            'product_id'        => $product->id,
                            'quantity'          => $line['Quantity'],
                            'tax'               => $isTaxable ? $invoiceTaxId : 0,
                            'total'             => $lineAmt + $lineTax,
                            'price'             => $line['Quantity'] ? ($lineAmt / $line['Quantity']) : $lineAmt,
                            'description'       => $line['Description'],
                            'taxable'           => $isTaxable ? 1 : 0,
                            'item_tax_price'    => $lineTax,
                            'item_tax_rate'     => $isTaxable ? $invoiceTaxRate : 0,
                            'amount'            => $lineAmt,
                            'estimate_id'       => $finalLinkedId,
                            'line_type'         => $line['line_type'],
                            'proposal_product_id'=> $line['proposal_product_id'],
                        ]);

                        $subtotal += $lineAmt;
                    }

                    $invoice->update([
                        'subtotal'          => $subtotal,
                        'taxable_subtotal'  => $taxableSubtotal,
                        'total_tax'         => $invoiceSalesTaxAmount,
                        'total_amount'      => $subtotal + $invoiceSalesTaxAmount,
                    ]);

                    // =================================================================
                    // STEP 7: PAYMENTS & CREDIT MEMO LINKING  (FIXED)
                    // =================================================================
                    $invoiceAllocations = array_filter(
                        $allocationsData,
                        fn($a) => $a['invoice_id'] == $qbId
                    );

                    foreach ($invoiceAllocations as $allocation) {
                        $paymentId   = $allocation['payment_id'];
                        $paymentData = collect($allPayments)->firstWhere('Id', $paymentId);
                        if (!$paymentData) {
                            continue;
                        }

                        // FIX: use the exact allocated amount that mapInvoicesWithPayments calculated
                        $allocatedToInvoice = (float)$allocation['allocated_amount'];

                        // ---- detect linked credit memos on this payment ----
                        $linkedCreditMemoIds = [];
                        if (!empty($paymentData['Line'])) {
                            foreach ($paymentData['Line'] as $pLine) {
                                if (!empty($pLine['LinkedTxn'])) {
                                    $linked = is_array($pLine['LinkedTxn'][0] ?? null)
                                        ? $pLine['LinkedTxn']
                                        : [$pLine['LinkedTxn']];
                                    foreach ($linked as $link) {
                                        if (($link['TxnType'] ?? null) === 'CreditMemo') {
                                            $linkedCreditMemoIds[] = $link['TxnId'];
                                        }
                                    }
                                }
                            }
                        }

                        // --- determine bank account & payment method ---
                        $accName = 'Undeposited Funds';
                        $accCode = null;

                        if (!empty($linkedCreditMemoIds) && (float)$paymentData['TotalAmt'] == 0) {
                            $accName = 'Credit Memo Application';
                        }

                        if (isset($paymentData['CreditCardPayment'])) {
                            $accName = 'Credit Card';
                        } elseif (isset($paymentData['DepositToAccountRef'])) {
                            $accName = $paymentData['DepositToAccountRef']['name'] ?? 'Bank';
                            $accCode = $paymentData['DepositToAccountRef']['value'] ?? null;
                        }

                        $bankAccountId = $this->getOrCreateBankAccountFromChartAccount($accCode, $accName);
                        if (!$bankAccountId) {
                            $bankAccountId = $this->getOrCreateBankAccountFromChartAccount(null, 'Bank');
                        }

                        $payMethod = !empty($linkedCreditMemoIds) && (float)$paymentData['TotalAmt'] == 0
                            ? 'Credit Memo'
                            : ($paymentData['PaymentMethodRef']['name'] ?? 'Unknown');

                        $description = 'Payment for Invoice ' . $qbInvoiceData['doc_number'];

                        $newIP = InvoicePayment::create([
                            'invoice_id'     => $invoice->id,
                            'date'           => $allocation['payment_date'],
                            'amount'         => $allocatedToInvoice,
                            'account_id'     => $bankAccountId,
                            'payment_method' => $payMethod,
                            'txn_id'         => $paymentId,
                            'description'    => $description,
                        ]);

                        // Link credit notes to this payment if needed
                        if (!empty($linkedCreditMemoIds)) {
                            foreach ($linkedCreditMemoIds as $cmId) {
                                $localCN = \App\Models\CreditNote::where('credit_note_id', $cmId)->first();
                                if (!$localCN) {
                                    try {
                                        $res = $this->qbController->runQuery("SELECT * FROM CreditMemo WHERE Id = '{$cmId}'");
                                        $fetchedCM = $res['QueryResponse']['CreditMemo'][0] ?? null;
                                        if ($fetchedCM) {
                                            $localCN = \App\Models\CreditNote::create([
                                                'credit_note_id' => $cmId,
                                                'customer'       => $customer->id,
                                                'date'           => $fetchedCM['TxnDate'],
                                                'amount'         => $fetchedCM['TotalAmt'] ?? 0,
                                                'created_by'     => \Auth::user()->creatorId(),
                                            ]);
                                            Utility::updateUserBalance(
                                                'customer',
                                                $customer->id,
                                                $fetchedCM['TotalAmt'],
                                                'credit'
                                            );
                                        }
                                    } catch (\Exception $e) {
                                        // swallow – logging above
                                    }
                                }

                                if ($localCN) {
                                    \App\Models\CreditNote::where('id', $localCN->id)
                                        ->update(['payment_id' => $newIP->id]);
                                    \Log::info("Linked Credit Note (QBO: {$cmId}) to Payment {$newIP->id}");
                                }
                            }
                        }

                        Transaction::create([
                            'user_id'    => $customer->id,
                            'user_type'  => 'Customer',
                            'type'       => 'Payment',
                            'amount'     => $allocatedToInvoice,
                            'account'    => $bankAccountId,
                            'description'=> $description,
                            'date'       => $allocation['payment_date'],
                            'category'   => 'Invoice',
                            'payment_id' => $newIP->id,
                            'payment_no' => $paymentId,
                            'created_by' => \Auth::user()->creatorId(),
                        ]);

                        // credit bank only for REAL money
                        if ($bankAccountId && (float)$paymentData['TotalAmt'] > 0) {
                            Utility::bankAccountBalance($bankAccountId, $allocatedToInvoice, 'credit');
                        }

                        // EXCESS LOGIC (unchanged, but now uses true line amounts so overpayment is correct)
                        $totalPaymentAmt  = (float)($paymentData['TotalAmt'] ?? 0);
                        $totalRealApplied = 0;

                        if (!empty($paymentData['Line'])) {
                            foreach ($paymentData['Line'] as $pLine) {
                                if (!empty($pLine['LinkedTxn'])) {
                                    $linked = is_array($pLine['LinkedTxn'][0] ?? null)
                                        ? $pLine['LinkedTxn']
                                        : [$pLine['LinkedTxn']];
                                    foreach ($linked as $link) {
                                        if (($link['TxnType'] ?? null) == 'Invoice') {
                                            $linkInv = Invoice::where('invoice_id', $link['TxnId'])->first();
                                            $lineAmt = (float)($pLine['Amount'] ?? 0);
                                            $totalRealApplied += $linkInv
                                                ? min($lineAmt, $linkInv->total_amount)
                                                : $lineAmt;
                                        }
                                    }
                                }
                            }
                        }

                        $excess = round($totalPaymentAmt - $totalRealApplied, 2);
                        if ($excess > 0
                            && !Transaction::where('payment_no', $paymentId)
                                ->where('category', 'Customer Credit')
                                ->exists()) {
                            Transaction::create([
                                'user_id'    => $customer->id,
                                'user_type'  => 'Customer',
                                'type'       => 'Payment',
                                'amount'     => $excess,
                                'account'    => $bankAccountId,
                                'description'=> 'Overpayment / Credit',
                                'date'       => $allocation['payment_date'],
                                'category'   => 'Customer Credit',
                                'payment_no' => $paymentId,
                                'created_by' => \Auth::user()->creatorId(),
                            ]);

                            if ($bankAccountId) {
                                Utility::bankAccountBalance($bankAccountId, $excess, 'credit');
                            }

                            Utility::updateUserBalance('customer', $customer->id, $excess, 'credit');
                        }
                    }

                    Utility::updateUserBalance('customer', $customer->id, $invoice->total_amount, 'debit');

                    if ($qbInvoiceData['allocated_amount'] > 0) {
                        $creditToApply = min($qbInvoiceData['allocated_amount'], $invoice->total_amount);
                        Utility::updateUserBalance('customer', $customer->id, $creditToApply, 'credit');
                    }

                    $imported++;

                } catch (\Exception $e) {
                    \Log::error("Invoice Import Fail {$qbId}: " . $e->getMessage());
                    $errors[] = $e->getMessage();
                    $failed++;
                }
            }

            // =================================================================
            // STEP 8: DEPOSITS (unchanged)
            // =================================================================
            foreach ($allDeposits as $qbDeposit) {
                try {
                    $depId = $qbDeposit['Id'];
                    if (\App\Models\Deposit::where('deposit_id', $depId)->exists()) {
                        continue;
                    }

                    \Log::info("PROCESSING DEPOSIT ID: {$depId}");
                    $bankAcctId = null;

                    if (isset($qbDeposit['DepositToAccountRef'])) {
                        $acctCode = $qbDeposit['DepositToAccountRef']['value'] ?? null;
                        $acctName = $qbDeposit['DepositToAccountRef']['name'] ?? 'Bank';
                        $bankAcctId = $this->getOrCreateBankAccountFromChartAccount($acctCode, (string)$acctName);
                    }

                    $deposit = \App\Models\Deposit::create([
                        'deposit_id' => $depId,
                        'doc_number' => $qbDeposit['DocNumber'] ?? null,
                        'txn_date'   => $qbDeposit['TxnDate'],
                        'total_amt'  => $qbDeposit['TotalAmt'] ?? 0,
                        'bank_id'    => $bankAcctId,
                        'created_by' => \Auth::user()->creatorId(),
                    ]);

                    $lines = $qbDeposit['Line'] ?? [];
                    if (isset($lines['Amount']) || isset($lines['Id'])) {
                        $lines = [$lines];
                    }

                    foreach ($lines as $dLine) {
                        $lineDetail = $dLine['DepositLineDetail'] ?? [];
                        $lineCustId = 0;

                        if (isset($lineDetail['Entity']['value'])) {
                            $c = Customer::where('customer_id', $lineDetail['Entity']['value'])->first();
                            if ($c) {
                                $lineCustId = $c->id;
                            }
                        } else {
                            if (!empty($dLine['LinkedTxn'])) {
                                foreach ($dLine['LinkedTxn'] as $link) {
                                    if ($link['TxnType'] === 'Payment') {
                                        $linkedPayment = collect($allPayments)->firstWhere('Id', $link['TxnId']);
                                        if ($linkedPayment && isset($linkedPayment['CustomerRef']['value'])) {
                                            $c = Customer::where('customer_id', $linkedPayment['CustomerRef']['value'])->first();
                                            if ($c) {
                                                $lineCustId = $c->id;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        \App\Models\DepositLines::create([
                            'deposit_id'  => $deposit->id,
                            'amount'      => $dLine['Amount'] ?? 0,
                            'detail_type' => $dLine['DetailType'] ?? null,
                            'customer_id' => $lineCustId,
                        ]);

                        if (!empty($dLine['LinkedTxn'])) {
                            foreach ($dLine['LinkedTxn'] as $link) {
                                if ($link['TxnType'] === 'Invoice') {
                                    $localInvoice = Invoice::where('invoice_id', $link['TxnId'])->first();
                                    if ($localInvoice) {
                                        $appliedAmount = min(
                                            (float)$dLine['Amount'],
                                            $localInvoice->total_amount
                                        );

                                        $invPay = InvoicePayment::create([
                                            'invoice_id'     => $localInvoice->id,
                                            'date'           => $qbDeposit['TxnDate'],
                                            'amount'         => $appliedAmount,
                                            'account_id'     => $bankAcctId,
                                            'payment_method' => 'Deposit',
                                            'txn_id'         => $depId,
                                            'description'    => 'Deposit Application',
                                        ]);

                                        if ($lineCustId) {
                                            Transaction::create([
                                                'user_id'    => $lineCustId,
                                                'user_type'  => 'Customer',
                                                'type'       => 'Payment',
                                                'amount'     => $appliedAmount,
                                                'account'    => $bankAcctId,
                                                'description'=> 'Deposit for Invoice ' . (string)$localInvoice->ref_number,
                                                'date'       => $qbDeposit['TxnDate'],
                                                'category'   => 'Invoice',
                                                'payment_id' => $invPay->id,
                                                'payment_no' => $depId,
                                                'created_by' => \Auth::user()->creatorId(),
                                            ]);

                                            if ($bankAcctId) {
                                                Utility::bankAccountBalance($bankAcctId, $appliedAmount, 'credit');
                                            }

                                            Utility::updateUserBalance('customer', $lineCustId, $appliedAmount, 'credit');
                                        }
                                    }
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error("Deposit Import Fail {$qbDeposit['Id']}: " . $e->getMessage());
                }
            }

            DB::commit();
            \Log::info("IMPORT COMPLETE: Imported {$imported}, Skipped {$skipped}, Failed {$failed}");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Fatal Import Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }

        return response()->json(['status' => 'success', 'imported' => $imported, 'errors' => $errors]);
    }


    public function getOrCreateBankAccountFromChartAccount($accountCode, $accountName)
    {
        try {
            $creatorId = \Auth::user()->creatorId();

            // 1. If no code provided, find/create a COA based on the Name
            if (!$accountCode) {
                $fallbackName = $accountName ?? 'Bank'; // Default to 'Bank' if both are null
                $defaultCoa = $this->getBankCoa($creatorId, $fallbackName);
                $accountCode = $defaultCoa->code;
                $accountName = $defaultCoa->name;
            }

            $accountCode = trim($accountCode);

            // 2. Find the Chart of Account by Code
            $chartAccount = ChartOfAccount::withoutGlobalScopes()
                ->where('created_by', $creatorId)
                ->where(function ($query) use ($accountCode) {
                    $query->where('code', $accountCode)
                        ->orWhere('id', $accountCode);
                })
                ->first();

            // 3. If COA doesn't exist yet, Create it using the Name provided
            if (!$chartAccount) {
                // Use the helper to create it
                $chartAccount = $this->getBankCoa($creatorId, $accountName);
            }

            // 4. Find existing BankAccount linked to this COA
            $existingBankAccount = BankAccount::where('chart_account_id', $chartAccount->id)
                ->where('created_by', $creatorId)
                ->first();

            if ($existingBankAccount) {
                return $existingBankAccount->id;
            }

            // 5. Create new BankAccount if missing
            $newBankAccount = BankAccount::create([
                'bank_name' => $chartAccount->name,
                'holder_name' => \Auth::user()->name ?? 'System',
                'account_number' => $chartAccount->code,
                'opening_balance' => 0,
                'contact_number' => '0000000000',
                'bank_address' => 'System Imported',
                'chart_account_id' => $chartAccount->id,
                'created_by' => $creatorId,
                'owned_by' => \Auth::user()->ownedId(),
            ]);

            return $newBankAccount->id;
        } catch (\Throwable $e) {
            \Log::error('getOrCreateBankAccountFromChartAccount failed: ' . $e->getMessage());
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
    // public function customers()
    // {
    //     try {
    //         $allCustomers = collect();
    //         $startPosition = 1;
    //         $maxResults = 50; // Adjust batch size as needed

    //         do {
    //             // Fetch paginated batch
    //             $query = "SELECT * FROM Customer WHERE Active IN (true, false) STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
    //             $customersResponse = $this->qbController->runQuery($query);

    //             // Handle API errors
    //             if ($customersResponse instanceof \Illuminate\Http\JsonResponse) {
    //                 return $customersResponse;
    //             }
    //             // Get customers from response
    //             $customersData = $customersResponse['QueryResponse']['Customer'] ?? [];

    //             // Merge entire objects (keep all keys)
    //             $allCustomers = $allCustomers->merge($customersData);

    //             // Move to next page
    //             $fetchedCount = count($customersData);
    //             $startPosition += $fetchedCount;
    //         } while ($fetchedCount === $maxResults); // continue if page is full

    //         // Import customers to local database
    //         $importedCount = 0;
    //         $updatedCount = 0;
    //         $errors = [];

    //         foreach ($allCustomers as $qbCustomer) {
    //             try {
    //                 $isActive = $qbCustomer['Active'];
    //                 if (is_string($isActive)) {
    //                     $isActive = strtolower($isActive) === 'true';
    //                 }
    //                 $existingCustomer = Customer::Where('customer_id', $qbCustomer['Id'] ?? null)
    //                     ->where('created_by', \Auth::user()->creatorId())->first();
    //                 if ($existingCustomer) {
    //                     \Log::warning("Exisitng Customers: '{$existingCustomer}'");
    //                     // Update existing customer
    //                     $existingCustomer->update([
    //                         'name' => $qbCustomer['Name'] ?? $qbCustomer['FullyQualifiedName'] ?? '',
    //                         'email' => $qbCustomer['PrimaryEmailAddr']['Address'] ?? null,
    //                         'contact' => $qbCustomer['PrimaryPhone']['FreeFormNumber'] ?? null,
    //                         'billing_name' => $qbCustomer['BillAddr']['Line1'] ?? null,
    //                         'billing_city' => $qbCustomer['BillAddr']['City'] ?? null,
    //                         'billing_state' => $qbCustomer['BillAddr']['CountrySubDivisionCode'] ?? null,
    //                         'billing_country' => $qbCustomer['BillAddr']['Country'] ?? null,
    //                         'billing_zip' => $qbCustomer['BillAddr']['PostalCode'] ?? null,
    //                         'billing_address' => implode(', ', array_filter([
    //                             $qbCustomer['BillAddr']['Line1'] ?? null,
    //                             $qbCustomer['BillAddr']['Line2'] ?? null,
    //                             $qbCustomer['BillAddr']['City'] ?? null,
    //                             $qbCustomer['BillAddr']['CountrySubDivisionCode'] ?? null,
    //                             $qbCustomer['BillAddr']['PostalCode'] ?? null,
    //                             $qbCustomer['BillAddr']['Country'] ?? null,
    //                         ])),
    //                         'shipping_name' => $qbCustomer['ShipAddr']['Line1'] ?? null,
    //                         'shipping_city' => $qbCustomer['ShipAddr']['City'] ?? null,
    //                         'shipping_state' => $qbCustomer['ShipAddr']['CountrySubDivisionCode'] ?? null,
    //                         'shipping_country' => $qbCustomer['ShipAddr']['Country'] ?? null,
    //                         'shipping_zip' => $qbCustomer['ShipAddr']['PostalCode'] ?? null,
    //                         'shipping_address' => implode(', ', array_filter([
    //                             $qbCustomer['ShipAddr']['Line1'] ?? null,
    //                             $qbCustomer['ShipAddr']['Line2'] ?? null,
    //                             $qbCustomer['ShipAddr']['City'] ?? null,
    //                             $qbCustomer['ShipAddr']['CountrySubDivisionCode'] ?? null,
    //                             $qbCustomer['ShipAddr']['PostalCode'] ?? null,
    //                             $qbCustomer['ShipAddr']['Country'] ?? null,
    //                         ])),
    //                         'is_active' => $isActive ? 1 : 0,
    //                         'owned_by' => \Auth::user()->ownedId(),
    //                         'qb_balance' => $qbCustomer['Balance'] ?? null,
    //                     ]);
    //                     $updatedCount++;
    //                 } else {
    //                     // Create new customer
    //                     $customer = Customer::create([
    //                         'customer_id' => $qbCustomer['Id'],
    //                         'name' => $qbCustomer['Name'] ?? $qbCustomer['FullyQualifiedName'] ?? '',
    //                         'email' => $qbCustomer['PrimaryEmailAddr']['Address'] ?? null,
    //                         'contact' => $qbCustomer['PrimaryPhone']['FreeFormNumber'] ?? null,
    //                         'created_by' => \Auth::user()->creatorId(),
    //                         'owned_by' => \Auth::user()->ownedId(),
    //                         'billing_name' => $qbCustomer['BillAddr']['Line1'] ?? null,
    //                         'billing_city' => $qbCustomer['BillAddr']['City'] ?? null,
    //                         'billing_state' => $qbCustomer['BillAddr']['CountrySubDivisionCode'] ?? null,
    //                         'billing_country' => $qbCustomer['BillAddr']['Country'] ?? null,
    //                         'billing_zip' => $qbCustomer['BillAddr']['PostalCode'] ?? null,
    //                         'billing_address' => implode(', ', array_filter([
    //                             $qbCustomer['BillAddr']['Line1'] ?? null,
    //                             $qbCustomer['BillAddr']['Line2'] ?? null,
    //                             $qbCustomer['BillAddr']['City'] ?? null,
    //                             $qbCustomer['BillAddr']['CountrySubDivisionCode'] ?? null,
    //                             $qbCustomer['BillAddr']['PostalCode'] ?? null,
    //                             $qbCustomer['BillAddr']['Country'] ?? null,
    //                         ])),
    //                         'shipping_name' => $qbCustomer['ShipAddr']['Line1'] ?? null,
    //                         'shipping_city' => $qbCustomer['ShipAddr']['City'] ?? null,
    //                         'shipping_state' => $qbCustomer['ShipAddr']['CountrySubDivisionCode'] ?? null,
    //                         'shipping_country' => $qbCustomer['ShipAddr']['Country'] ?? null,
    //                         'shipping_zip' => $qbCustomer['ShipAddr']['PostalCode'] ?? null,
    //                         'shipping_address' => implode(', ', array_filter([
    //                             $qbCustomer['ShipAddr']['Line1'] ?? null,
    //                             $qbCustomer['ShipAddr']['Line2'] ?? null,
    //                             $qbCustomer['ShipAddr']['City'] ?? null,
    //                             $qbCustomer['ShipAddr']['CountrySubDivisionCode'] ?? null,
    //                             $qbCustomer['ShipAddr']['PostalCode'] ?? null,
    //                             $qbCustomer['ShipAddr']['Country'] ?? null,
    //                         ])),
    //                         'is_active' => $isActive ? 1 : 0,
    //                         'qb_balance' => $qbCustomer['Balance'] ?? null,
    //                     ]);
    //                     $customer->save();
    //                     $importedCount++;
    //                 }
    //             } catch (\Exception $e) {
    //                 $errors[] = "Error importing customer {$qbCustomer['Id']}: " . $e->getMessage();
    //             }
    //         }
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => "Customers import completed. Imported: {$importedCount}, Updated: {$updatedCount}",
    //             'imported' => $importedCount,
    //             'updated' => $updatedCount,
    //             'errors' => $errors,
    //             'total_fetched' => $allCustomers->count(),
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
   public function customers()
{
    try {
        \Log::info("======= START CUSTOMER + CUSTOMER TYPES IMPORT =======");

        $createdBy = \Auth::user()->creatorId();
        $ownedBy   = \Auth::user()->ownedId();

        /* ---------------------------------------------------
         *  STEP 1: FETCH ALL CUSTOMER TYPES FROM QUICKBOOKS
         * --------------------------------------------------- */
        \Log::info("Fetching Customer Types from QuickBooks...");

        $allTypes = collect();
        $startPosition = 1;
        $maxResults = 50;

        do {
            $query = "SELECT * FROM CustomerType STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
            $response = $this->qbController->runQuery($query);

            if ($response instanceof \Illuminate\Http\JsonResponse) {
                return $response;
            }

            $typesBatch = $response['QueryResponse']['CustomerType'] ?? [];
            $allTypes = $allTypes->merge($typesBatch);

            $count = count($typesBatch);
            $startPosition += $count;

        } while ($count === $maxResults);

        \Log::info("Total Customer Types fetched from QB: " . $allTypes->count());


        /* ---------------------------------------------------
         *  STEP 2: SYNC CUSTOMER TYPES IN LOCAL DB
         * --------------------------------------------------- */
        foreach ($allTypes as $type) {
            $qbTypeId = $type['Id'] ?? null;
            $name = $type['Name'] ?? 'Unnamed Type';

            if (!$qbTypeId) {
                \Log::warning("Skipping invalid CustomerType without ID.");
                continue;
            }

            $typeModel = \App\Models\CustomerType::firstOrCreate(
                ['qb_type_id' => $qbTypeId],
                ['name' => $name]
            );

            if ($typeModel->wasRecentlyCreated) {
                \Log::info("ADDED NEW Customer Type: {$name} (QB ID: {$qbTypeId})");
            } else {
                \Log::info("Customer Type already exists: {$name} (QB ID: {$qbTypeId})");
            }
        }


        /* ---------------------------------------------------
         *  STEP 3: FETCH ALL CUSTOMERS FROM QUICKBOOKS
         * --------------------------------------------------- */
        \Log::info("Fetching Customers from QuickBooks...");

        $allCustomers = collect();
        $startPosition = 1;

        do {
            $query = "SELECT * FROM Customer WHERE Active IN (true, false) 
                      STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";

            $response = $this->qbController->runQuery($query);

            if ($response instanceof \Illuminate\Http\JsonResponse) {
                return $response;
            }

            $batch = $response['QueryResponse']['Customer'] ?? [];
            $allCustomers = $allCustomers->merge($batch);

            $count = count($batch);
            $startPosition += $count;

        } while ($count === $maxResults);

        \Log::info("Total Customers fetched: " . $allCustomers->count());


        /* ---------------------------------------------------
         *  STEP 4: IMPORT / UPDATE CUSTOMERS
         * --------------------------------------------------- */
        $imported = 0;
        $updated  = 0;
        $errors   = [];

        foreach ($allCustomers as $qbCustomer) {
            try {
                $qbId = $qbCustomer['Id'];
                \Log::info("---- Processing Customer ID: {$qbId} ----");

                /* ---- TYPE HANDLING ---- */
                $typeRef = $qbCustomer['CustomerTypeRef']['value'] ?? null;
                $typeId = null;

                if ($typeRef) {
                    $typeModel = \App\Models\CustomerType::where('qb_type_id', $typeRef)->first();

                    if ($typeModel) {
                        \Log::info("Mapped Customer Type => {$typeModel->name} ({$typeRef})");
                        $typeId = $typeModel->id;
                    } else {
                        \Log::warning("No matching Customer Type found for TypeRef: {$typeRef}");
                    }
                } else {
                    \Log::info("No CustomerTypeRef found — setting type_id = NULL");
                }

                /* ---- Customer Data ---- */
                $data = [
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
                    'qb_balance' => $qbCustomer['Balance'] ?? null,
                    'is_active' => ($qbCustomer['Active'] == true || $qbCustomer['Active'] == "true") ? 1 : 0,
                    'type_id' => $typeId,
                    'owned_by' => $ownedBy,
                ];

                /* ---- Update or Insert ---- */
                $existing = Customer::where('customer_id', $qbId)
                                    ->where('created_by', $createdBy)
                                    ->first();

                if ($existing) {
                    $existing->update($data);
                    $updated++;
                    \Log::info("Customer UPDATED: {$qbId}");
                } else {
                    Customer::create(array_merge($data, [
                        'customer_id' => $qbId,
                        'created_by' => $createdBy,
                    ]));
                    $imported++;
                    \Log::info("Customer INSERTED: {$qbId}");
                }
            } catch (\Exception $e) {
                $errors[] = "Error importing customer {$qbCustomer['Id']}: " . $e->getMessage();
                \Log::error("Error importing customer {$qbId}: " . $e->getMessage());
            }
        }

        \Log::info("======= CUSTOMER IMPORT COMPLETE =======");

        return response()->json([
            'status' => 'success',
            'message' => "Customers import completed.",
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
            'total_types' => $allTypes->count(),
            'total_customers' => $allCustomers->count(),
        ]);

    } catch (\Exception $e) {
        \Log::error("Fatal Error in customers(): " . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}


    public function importTaxes()
    {
        try {
            $startPosition = 1;
            $maxResults = 50;
            $allTaxRates = collect();

            do {
                $query = "SELECT * FROM TaxRate STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $response = $this->qbController->runQuery($query);

                if ($response instanceof \Illuminate\Http\JsonResponse) {
                    return $response;
                }

                $taxRates = $response['QueryResponse']['TaxRate'] ?? [];
                $allTaxRates = $allTaxRates->merge($taxRates);

                $count = count($taxRates);
                $startPosition += $count;
            } while ($count === $maxResults);


            $indexedRates = [];
            foreach ($allTaxRates as $rate) {
                $indexedRates[$rate['Id']] = [
                    'id' => $rate['Id'],
                    'name' => $rate['Name'] ?? null,
                    'rate_value' => $rate['RateValue'] ?? null,
                    'active' => $rate['Active'] ?? null,
                ];
            }

            $taxCodeQuery = "SELECT * FROM TaxCode";
            $taxCodeResponse = $this->qbController->runQuery($taxCodeQuery);

            if ($taxCodeResponse instanceof \Illuminate\Http\JsonResponse) {
                return $taxCodeResponse;
            }

            $taxCodes = $taxCodeResponse['QueryResponse']['TaxCode'] ?? [];

            $mergedTaxList = [];

            foreach ($taxCodes as $code) {
                $codeName = $code['Name'] ?? '';
                $rates = [];
                if (!empty($code['SalesTaxRateList']['TaxRateDetail'])) {
                    foreach ($code['SalesTaxRateList']['TaxRateDetail'] as $detail) {
                        $rateId = $detail['TaxRateRef']['value'] ?? null;

                        if ($rateId && isset($indexedRates[$rateId])) {
                            $rates[] = $indexedRates[$rateId];
                        }
                    }
                }

                if (empty($rates)) {
                    $mergedTaxList[] = [
                        'taxid' => $code['Id'],
                        'name' => $codeName,
                        'rate' => 0,
                        'is_group' => $code['TaxGroup'] ?? false,
                        'is_custom_zero' => true,
                    ];
                } else {
                    foreach ($rates as $r) {
                        $mergedTaxList[] = [
                            'taxid' => $r['id'],
                            'name' => $r['name'],
                            'rate' => $r['rate_value'],
                            'is_group' => $code['TaxGroup'] ?? false,
                        ];
                    }
                }
            }

            $imported = 0;
            $updated = 0;
            $errors = [];

            foreach ($mergedTaxList as $tax) {
                try {
                    $existing = Tax::where('name', $tax['name'])
                        ->where('created_by', auth()->user()->creatorId())
                        ->first();

                    if ($existing) {
                        $existing->update([
                            'rate' => $tax['rate']
                        ]);
                        $updated++;
                    } else {
                        Tax::create([
                            'taxid' => $tax['taxid'],
                            'name' => $tax['name'],
                            'rate' => $tax['rate'],
                            'created_by' => auth()->user()->creatorId(),
                            'owned_by' => \Auth::user()->ownedId(),
                        ]);
                        $imported++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error importing tax {$tax['name']}: {$e->getMessage()}";
                }
            }
            return response()->json([
                'status' => 'success',
                'message' => "Tax import completed.",
                'imported' => $imported,
                'updated' => $updated,
                'errors' => $errors,
                'total_real_taxes' => count($mergedTaxList),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
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
                $query = "SELECT * FROM Vendor WHERE Active IN (true, false) STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
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
                    $isActive = $qbVendor['Active'];
                    // Check if vendor already exists (by email)
                    $existingVendor = Vender::where('vender_id', $qbVendor['Id'] ?? null)
                        ->where('created_by', \Auth::user()->creatorId())->first();
                    \Log::warning("Exisitng Vendor: '{$existingVendor}'");
                    if ($existingVendor) {
                        // Update existing vendor
                        $existingVendor->update([
                            // 'title' => $qbVendor['Title'] ?? '',
                            // 'name' => $qbVendor['Name'] ?? $qbVendor['DisplayName'] ?? '',
                            // 'first_name' => $qbVendor['Name'] ?? $qbVendor['DisplayName'] ?? '',
                            // 'company_name' => $qbVendor['CompanyName'] ?? '',
                            // 'email' => $qbVendor['PrimaryEmailAddr']['Address'] ?? null,
                            // 'contact' => $qbVendor['PrimaryPhone']['FreeFormNumber'] ?? null,
                            // 'billing_name' => $qbVendor['BillAddr']['Line1'] ?? null,
                            // 'billing_city' => $qbVendor['BillAddr']['City'] ?? null,
                            // 'billing_state' => $qbVendor['BillAddr']['CountrySubDivisionCode'] ?? null,
                            // 'billing_country' => $qbVendor['BillAddr']['Country'] ?? null,
                            // 'billing_zip' => $qbVendor['BillAddr']['PostalCode'] ?? null,
                            // 'billing_address' => implode(', ', array_filter([
                            //     $qbVendor['BillAddr']['Line1'] ?? null,
                            //     $qbVendor['BillAddr']['Line2'] ?? null,
                            //     $qbVendor['BillAddr']['City'] ?? null,
                            //     $qbVendor['BillAddr']['CountrySubDivisionCode'] ?? null,
                            //     $qbVendor['BillAddr']['PostalCode'] ?? null,
                            //     $qbVendor['BillAddr']['Country'] ?? null,
                            // ])),
                            // 'shipping_name' => $qbVendor['ShipAddr']['Line1'] ?? null,
                            // 'shipping_city' => $qbVendor['ShipAddr']['City'] ?? null,
                            // 'shipping_state' => $qbVendor['ShipAddr']['CountrySubDivisionCode'] ?? null,
                            // 'shipping_country' => $qbVendor['ShipAddr']['Country'] ?? null,
                            // 'shipping_zip' => $qbVendor['ShipAddr']['PostalCode'] ?? null,
                            // 'shipping_address' => implode(', ', array_filter([
                            //     $qbVendor['ShipAddr']['Line1'] ?? null,
                            //     $qbVendor['ShipAddr']['Line2'] ?? null,
                            //     $qbVendor['ShipAddr']['City'] ?? null,
                            //     $qbVendor['ShipAddr']['CountrySubDivisionCode'] ?? null,
                            //     $qbVendor['ShipAddr']['PostalCode'] ?? null,
                            //     $qbVendor['ShipAddr']['Country'] ?? null,
                            // ])),
                            // 'owned_by' => \Auth::user()->ownedId(),
                            // 'qb_balance' => $qbVendor['Balance'] ?? 0,
                            'is_active' => $isActive ? 1 : 0,
                        ]);
                        $updatedCount++;
                    } else {
                        // Create new vendor
                        $vender = Vender::create([
                            'vender_id' => $qbVendor['Id'],
                            'title' => $qbVendor['Title'] ?? '',
                            'name' => $qbVendor['Name'] ?? $qbVendor['DisplayName'] ?? '',
                            'first_name' => $qbVendor['Name'] ?? $qbVendor['DisplayName'] ?? '',
                            'company_name' => $qbVendor['CompanyName'] ?? '',
                            'email' => $qbVendor['PrimaryEmailAddr']['Address'] ?? null,
                            'contact' => $qbVendor['PrimaryPhone']['FreeFormNumber'] ?? null,
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
                            'is_active' => $isActive ? 1 : 0,
                        ]);
                        $vender->save();
                        $importedCount++;
                    }
                } catch (\Exception $e) {
                    \Log::warning("Error importing vendor {$qbVendor['Id']}:'" . $e->getMessage());
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
            \Log::warning("Error importing vendor:" . $e);
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
            } while ($fetchedCount == $maxResults);

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
        $matchTypes = [
            'bank',
            'banks',
            'cost of goods sold',
            'cost of goods solds'
        ];
        $accType = strtolower(trim($qbAccountData['AccountType'] ?? ''));
        // ✅ Compare safely (case-insensitive + plural-friendly)
        if (in_array($accType, $matchTypes)) {
            $detailType = ucwords(strtolower($qbAccountData['AccountType']));
        }
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
                'qb_balance' => $qbAccountData['CurrentBalance'] ?? 0,
                'description' => $qbAccountData['AccountType'] ?? null,
                'type' => $type->id,
                'sub_type' => $subType->id,
                'created_by' => $creatorId,
            ]
        );
        if (
    strtolower($subType->name) == 'banks' ||
    strtolower($subType->name) == 'bank' ||
    strtolower($subType->name) == 'credit card' ||
    strtolower($subType->name) == 'credit cards' ||
    strtolower($subType->name) == 'creditcard' ||
    strtolower($subType->name) == 'creditcards'
) {
    // detect if it's a credit card type
    $isCreditCard = in_array(strtolower($subType->name), [
        'credit card', 'credit cards', 'creditcard', 'creditcards'
    ]);

        $newBankAccount = BankAccount::create([
            'bank_name'       => $account->name,
            'holder_name'     => \Auth::user()->name ?? $account->name,
            'account_number'  => $account->code,
            'opening_balance' => 0,
            'contact_number'  => '0000000000',
            'bank_address'    => 'System Imported',
            'chart_account_id'=> $account->id,
            'created_by'      => $creatorId,
            'owned_by'        => \Auth::user()->ownedId(),

            // set sub_type dynamically
            'account_subtype'        => $isCreditCard ? 'credit_card' : 'current_account'
        ]);
    }

        return $account;
    }
    public function getBankCoa($createdBy, $accountName = 'Bank')
    {
        // 1. Try to find an existing account with this specific name
        $existing = ChartOfAccount::where('created_by', $createdBy)
            ->where('name', 'LIKE', '%' . $accountName . '%')
            ->first();

        // Ensure Types exist (Asset -> Bank)
        $type = \App\Models\ChartOfAccountType::firstOrCreate(
            ['created_by' => $createdBy, 'name' => 'Assets']
        );
        $subType = \App\Models\ChartOfAccountSubType::firstOrCreate(
            ['created_by' => $createdBy, 'type' => $type->id, 'name' => 'Bank']
        );

        if ($existing && $existing->type == $type->id) {
            return $existing;
        }

        // 2. If not found, create it using the passed Name
        // Generate a random code or sequential one to avoid collision (simplest is random 5 digits for now)
        $randomCode = rand(10000, 99999);

        return \App\Models\ChartOfAccount::create([
            'name' => $accountName,
            'code' => $randomCode,
            'type' => $type->id,
            'sub_type' => $subType->id,
            'is_enabled' => 1,
            'created_by' => $createdBy,
        ]);
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
                    'item_id' => $item['ItemId'] ?? '',
                    'name' => $item['Name'] ?? '',
                    'sku' => $item['Name'] ?? '',
                    'sale_price' => $item['UnitPrice'] ?? 0,
                    'purchase_price' => $item['PurchaseCost'] ?? 0,
                    'quantity' => $item['QtyOnHand'] ?? 0,
                    'qb_balance' => $item['QtyOnHand'] ?? 0,
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
        \Log::info('[QB Import] Starting Direct Expense Import with PO Reverse Linking.');
        
        $metrics = ['imported' => 0, 'skipped' => 0, 'failed' => 0, 'linked_pos' => 0];
        $skippedDetails = []; 
        
        DB::beginTransaction();
        try {
            $creatorId = \Auth::user()->creatorId();
            $ownedId   = \Auth::user()->ownedId();

            // =======================================================================
            // 1. FETCH PURCHASES (EXPENSES, CHECKS, CC)
            // =======================================================================
            \Log::info('[QB Import] Fetching Expenses...');
            
            $allExpenses   = collect();
            $startPosition = 1;
            $maxResults    = 50;

            do {
                $query = "SELECT * FROM Purchase STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $resp  = $this->qbController->runQuery($query);

                if ($resp instanceof \Illuminate\Http\JsonResponse) {
                    \Log::error('[QB Import] API Error: ' . $resp->getContent());
                    return $resp;
                }

                $data = $resp['QueryResponse']['Purchase'] ?? [];
                if (array_key_exists('Id', $data)) $data = [$data];

                $fetchedCount  = count($data);
                $allExpenses   = $allExpenses->merge($data);
                $startPosition += $fetchedCount;
                
                \Log::info("[QB Import] Fetched batch of $fetchedCount expenses.");
                sleep(1);

            } while ($fetchedCount === $maxResults);

            // =======================================================================
            // 2. FETCH REFERENCES (ITEMS & ACCOUNTS)  - unchanged logic
            // =======================================================================
            \Log::info('[QB Import] Fetching Reference Data...');
            
            $allItems = collect(); 
            $pos      = 1;
            do {
                $resp = $this->qbController->runQuery("SELECT * FROM Item STARTPOSITION {$pos} MAXRESULTS 100");
                $data = $resp['QueryResponse']['Item'] ?? [];
                if (array_key_exists('Id', $data)) $data = [$data];
                $allItems = $allItems->merge($data);
                $pos     += count($data);
            } while (count($data) === 100);

            $allAccounts = collect();
            $pos         = 1;
            do {
                $resp = $this->qbController->runQuery("SELECT * FROM Account STARTPOSITION {$pos} MAXRESULTS 100");
                $data = $resp['QueryResponse']['Account'] ?? [];
                if (array_key_exists('Id', $data)) $data = [$data];
                $allAccounts = $allAccounts->merge($data);
                $pos        += count($data);
            } while (count($data) === 100);

            // Helper: Default Cash Account Logic
            $getDefaultCashAccount = function () use ($creatorId, $ownedId) {
                $existing = \App\Models\BankAccount::where('created_by', $creatorId)
                    ->where('bank_name', 'like', '%Default%Cash%')->first();
                if ($existing) return $existing->id;

                $cashChart = \App\Models\ChartOfAccount::where('created_by', $creatorId)
                    ->where(function ($q) {                       // *** CHANGED: group OR condition
                        $q->where('account_type', 'Cash')
                        ->orWhere('name', 'like', '%Cash%');
                    })
                    ->first();

                try {
                    $bank = \App\Models\BankAccount::create([
                        'bank_name'        => 'Default Cash Account',
                        'chart_account_id' => $cashChart ? $cashChart->id : 0,
                        'created_by'       => $creatorId, 
                        'owned_by'         => $ownedId,
                    ]);
                    return $bank->id;
                } catch (\Exception $e) { 
                    return 0; 
                }
            };
            $defaultCashAccountId = null;

            // =======================================================================
            // 3. PROCESS EXPENSES LOOP
            // =======================================================================
            foreach ($allExpenses as $qbExpense) {
                $qbId     = $qbExpense['Id'];
                $docNum   = $qbExpense['DocNumber'] ?? 'N/A';
                $totalAmt = $qbExpense['TotalAmt'] ?? 0;
                
                try {
                    // ---------------------------------------------------------
                    // VALIDATION CHECKS
                    // ---------------------------------------------------------
                    $existingBill = \App\Models\Bill::where('bill_id', $qbId)->first();
                    $isDuplicate  = false; 
                    $bill         = null;

                    if ($existingBill) {
                        $isDuplicate = true;
                        $bill        = $existingBill;
                        $metrics['skipped']++;
                        $skippedDetails[] = [
                            'qb_id'  => $qbId, 
                            'reason' => 'Duplicate (Skipped Creation)',
                            'data'   => ['DocNumber' => $docNum]
                        ];
                    } else {
                        // -----------------------------------------------------
                        // ENTITY CHECK (VENDOR OR CUSTOMER)  *** CHANGED BLOCK
                        // -----------------------------------------------------
                        $entityRef = $qbExpense['EntityRef'] ?? null;
                        $entityId  = $entityRef['value'] ?? null;

                        if (!$entityId) {
                            $metrics['skipped']++;
                            $skippedDetails[] = [
                                'qb_id'  => $qbId, 
                                'reason' => 'No Entity Reference (Vendor/Customer)'
                            ];
                            continue; 
                        }

                        $localEntityId = null; // will be stored in vender_id
                        $userType      = 'Vendor';

                        // 1) Try vendor
                        $vendor = \App\Models\Vender::where('vender_id', $entityId)
                            ->where('created_by', $creatorId)
                            ->first();

                        if ($vendor) {
                            $localEntityId = $vendor->id;
                            $userType      = 'Vendor';
                        } else {
                            // 2) Try customer  *** NEW
                            $customer = \App\Models\Customer::where('customer_id', $entityId)
                                ->where('created_by', $creatorId)
                                ->first();

                            if ($customer) {
                                $localEntityId = $customer->id;
                                $userType      = 'Customer';
                            } else {
                                $metrics['skipped']++;
                                $skippedDetails[] = [
                                    'qb_id'  => $qbId, 
                                    'reason' => "Vendor/Customer ID [$entityId] not found"
                                ];
                                continue;
                            }
                        }

                        // Type Mapping (same as before)
                        $qbPaymentType = $qbExpense['PaymentType'] ?? 'Cash';
                        $typeMap       = [
                            'CreditCard' => 'Credit Card', 
                            'Check'      => 'Check', 
                            'Cash'       => 'Expense'
                        ];
                        $mappedType = $typeMap[$qbPaymentType] ?? 'Expense';

                        // -----------------------------------------------------
                        // Create Bill (Vendor OR Customer)  *** CHANGED
                        // -----------------------------------------------------
                        $bill = \App\Models\Bill::create([
                            'bill_id'      => $qbId,
                            'vender_id'    => $localEntityId,      // <- vendor_id OR customer_id
                            'bill_date'    => $qbExpense['TxnDate'],
                            'due_date'     => $qbExpense['TxnDate'],
                            'order_number' => $qbExpense['DocNumber'] ?? $qbId,
                            'status'       => 4, // Paid
                            'created_by'   => $creatorId,
                            'owned_by'     => $ownedId,
                            'type'         => $mappedType,
                            'user_type'    => $userType,           // <- 'Vendor' or 'Customer' *** NEW
                            'subtotal'     => $totalAmt,
                            'total'        => $totalAmt,
                            'created_at'   => now(),
                            'updated_at'   => now(),
                        ]);
                        $metrics['imported']++;
                    }

                    if (!is_object($bill)) continue;

                    // ---------------------------------------------------------
                    // PROCESS LINES (Skip creation if duplicate)
                    // ---------------------------------------------------------
                    $rawLines = $qbExpense['Line'] ?? [];
                    if (array_key_exists('DetailType', $rawLines) || array_key_exists('Amount', $rawLines)) {
                        $rawLines = [$rawLines];
                    }

                    $calculatedBillTotal = 0;

                    foreach ($rawLines as $line) {
                        $lineAmount  = $line['Amount'] ?? 0;
                        $description = $line['Description'] ?? null;
                        
                        if ($isDuplicate) {
                            $calculatedBillTotal += $lineAmount;
                            continue;
                        }

                        $isBillable     = 0;
                        $localCustomerId = null;

                        // PRODUCT LINE
                        if (!empty($line['ItemBasedExpenseLineDetail'])) {
                            $detail = $line['ItemBasedExpenseLineDetail'];
                            
                            if (isset($detail['BillableStatus']) && $detail['BillableStatus'] === 'Billable') {
                                $isBillable = 1;
                                if (isset($detail['CustomerRef']['value'])) {
                                    $customer = \App\Models\Customer::where('customer_id', $detail['CustomerRef']['value'])
                                        ->where('created_by', $creatorId)->first();
                                    if ($customer) $localCustomerId = $customer->id;
                                }
                            }

                            $itemName = $detail['ItemRef']['name'] ?? 'Unknown Item';
                            $product  = \App\Models\ProductService::firstOrCreate(
                                ['name' => $itemName, 'created_by' => $creatorId],
                                [
                                    'sku'            => $itemName,
                                    'purchase_price' => $lineAmount,
                                    'sale_price'     => 0,
                                    'quantity'       => 0,
                                    'type'           => 'product',
                                    'unit_id'        => 1,
                                    'category_id'    => 1,
                                    'created_by'     => $creatorId
                                ]
                            );

                            \App\Models\BillProduct::create([
                                'bill_id'     => $bill->id, 
                                'product_id'  => $product->id, 
                                'quantity'    => $detail['Qty'] ?? 1, 
                                'price'       => $lineAmount, 
                                'description' => $description, 
                                'tax'         => 0, 
                                'billable'    => $isBillable, 
                                'customer_id' => $localCustomerId
                            ]);
                            $calculatedBillTotal += $lineAmount;
                        } 
                        // ACCOUNT LINE
                        elseif (!empty($line['AccountBasedExpenseLineDetail'])) {
                            $detail = $line['AccountBasedExpenseLineDetail'];
                            
                            if (isset($detail['BillableStatus']) && $detail['BillableStatus'] == 'Billable') {
                                $isBillable = 1;
                                if (isset($detail['CustomerRef']['value'])) {
                                    $customer = \App\Models\Customer::where('customer_id', $detail['CustomerRef']['value'])
                                        ->where('created_by', $creatorId)->first();
                                    if ($customer) $localCustomerId = $customer->id;
                                }
                            }

                            $accRef = $detail['AccountRef']['value'] ?? null;
                            if ($accRef) {
                                $chartAcc = \App\Models\ChartOfAccount::where('code', $accRef)
                                    ->where('created_by', $creatorId)->first();
                                if ($chartAcc) {
                                    \App\Models\BillAccount::create([
                                        'bill_id'          => $bill->id, 
                                        'chart_account_id' => $chartAcc->id, 
                                        'price'            => $lineAmount, 
                                        'description'      => $description, 
                                        'type'             => $bill->type, 
                                        'ref_id'           => $bill->id, 
                                        'status'           => 1, 
                                        'tax'              => 0, 
                                        'billable'         => $isBillable, 
                                        'customer_id'      => $localCustomerId, 
                                        'created_at'       => now(), 
                                        'updated_at'       => now()
                                    ]);
                                    $calculatedBillTotal += $lineAmount;
                                }
                            }
                        }
                    }

                    if ($isDuplicate) continue;

                    // ---------------------------------------------------------
                    // PAYMENT & TRANSACTION LOGIC (Only for New)
                    // ---------------------------------------------------------
                    $sourceAccount = $qbExpense['AccountRef'] ?? null;
                    $bankAccountId = 0;

                    if ($sourceAccount && method_exists($this, 'getOrCreateBankAccountFromChartAccount')) {
                        $bankAccountId = $this->getOrCreateBankAccountFromChartAccount(
                            $sourceAccount['value'] ?? null, 
                            $sourceAccount['name'] ?? null
                        );
                    }

                    if (!$bankAccountId) {
                        if (!$defaultCashAccountId) $defaultCashAccountId = $getDefaultCashAccount();
                        $bankAccountId = $defaultCashAccountId;
                    }

                    $payRef = $qbExpense['DocNumber'] ?? 'Expense-' . $qbId;
                    
                    $settledAmount     = $totalAmt;
                    $overpaymentAmount = 0;

                    if ($totalAmt > $calculatedBillTotal && $calculatedBillTotal > 0) {
                        $settledAmount     = $calculatedBillTotal;
                        $overpaymentAmount = $totalAmt - $calculatedBillTotal;
                    }

                    $paymentRecord = \App\Models\BillPayment::create([
                        'bill_id'       => $bill->id, 
                        'date'          => $qbExpense['TxnDate'], 
                        'amount'        => $totalAmt, 
                        'account_id'    => $bankAccountId, 
                        'payment_method'=> $bill->type, 
                        'reference'     => $payRef, 
                        'description'   => 'QB Expense Import', 
                        'created_at'    => \Carbon\Carbon::parse($qbExpense['TxnDate'])->format('Y-m-d H:i:s'), 
                        'updated_at'    => \Carbon\Carbon::parse($qbExpense['TxnDate'])->format('Y-m-d H:i:s'),
                    ]);

                    if (is_object($paymentRecord) && isset($paymentRecord->id)) {
                        // -----------------------------------------------------
                        // TRANSACTION FOR VENDOR OR CUSTOMER *** CHANGED
                        // -----------------------------------------------------
                        \App\Models\Transaction::create([
                            'user_id'     => $bill->vender_id,          // vendor_id OR customer_id
                            'user_type'   => $bill->user_type,          // 'Vendor' or 'Customer'
                            'type'        => $bill->type, 
                            'payment_id'  => $paymentRecord->id, 
                            'amount'      => $settledAmount, 
                            'date'        => $qbExpense['TxnDate'], 
                            'payment_no'  => $payRef, 
                            'description' => "{$bill->type} Imported from QuickBooks", 
                            'account'     => $bankAccountId, 
                            'category'    => 'Expense', 
                            'created_by'  => $creatorId, 
                            'owned_by'    => $ownedId, 
                            'created_at'  => now(), 
                            'updated_at'  => now(),
                        ]);

                        if ($overpaymentAmount > 0) {
                            \App\Models\Transaction::create([
                                'user_id'     => $bill->vender_id,          // vendor_id OR customer_id
                                'user_type'   => $bill->user_type,          // 'Vendor' or 'Customer'
                                'type'        => $bill->type, 
                                'payment_id'  => $paymentRecord->id, 
                                'amount'      => $overpaymentAmount, 
                                'date'        => $qbExpense['TxnDate'], 
                                'payment_no'  => $payRef, 
                                'description' => 'Overpayment / Credit', 
                                'account'     => $bankAccountId, 
                                'category'    => 'Vendor Credit',           // you may want a different label for customers
                                'created_by'  => $creatorId, 
                                'owned_by'    => $ownedId, 
                                'created_at'  => now(), 
                                'updated_at'  => now(),
                            ]);
                        }

                        if ($bankAccountId) {
                            Utility::bankAccountBalance($bankAccountId, $totalAmt, 'debit');
                        }
                    }

                    // ---------------------------------------------------------
                    // Update Balance (Vendor or Customer)  *** CHANGED
                    // ---------------------------------------------------------
                    if ($bill->user_type === 'Vendor') {
                        if ($vendor = \App\Models\Vender::find($bill->vender_id)) {
                            Utility::updateUserBalance('vendor', $vendor->id, $totalAmt, 'debit');
                            Utility::updateUserBalance('vendor', $vendor->id, $totalAmt, 'credit');
                        }
                    } elseif ($bill->user_type === 'Customer') {
                        if ($customer = \App\Models\Customer::find($bill->vender_id)) {
                            Utility::updateUserBalance('customer', $customer->id, $totalAmt, 'debit');
                            Utility::updateUserBalance('customer', $customer->id, $totalAmt, 'credit');
                        }
                    }

                } catch (\Exception $e) {
                    $metrics['failed']++;
                    $skippedDetails[] = [
                        'qb_id'  => $qbId, 
                        'reason' => 'Exception: ' . $e->getMessage(),
                        'trace'  => $e->getLine()
                    ];
                    \Log::error("[QB Import] Expense Failed $qbId: " . $e->getMessage());
                }
            }

            // =======================================================================
            // 4. REVERSE LINKING POs (unchanged logic)
            // =======================================================================
            \Log::info('[QB Import] Phase 4: Reverse Linking POs...');
            
            $poLinksStart = 1;
            do {
                $query = "SELECT * FROM PurchaseOrder STARTPOSITION {$poLinksStart} MAXRESULTS 50";
                $resp  = $this->qbController->runQuery($query);
                
                $poData = $resp['QueryResponse']['PurchaseOrder'] ?? [];
                if (array_key_exists('Id', $poData)) $poData = [$poData];
                
                foreach ($poData as $qbPO) {
                    if (!empty($qbPO['LinkedTxn'])) {
                        $links = $qbPO['LinkedTxn'];
                        if (array_key_exists('TxnId', $links)) $links = [$links];

                        foreach ($links as $link) {
                            $targetType = $link['TxnType'] ?? '';
                            $targetId   = $link['TxnId'] ?? '';

                            // Valid targets: Bill (Invoices) or Purchase (Expenses/Checks)
                            if ($targetType === 'Purchase' || $targetType === 'Bill') {
                                
                                // 1. Find the Local Bill/Expense by QB ID
                                $localBill = \App\Models\Bill::where('bill_id', $targetId)
                                    ->where('created_by', $creatorId)->first();

                                if ($localBill) {
                                    // 2. Find the Local Purchase Order by QB ID
                                    $localPO = \App\Models\Purchase::where('purchase_id', $qbPO['Id'])
                                        ->where('created_by', $creatorId)->first();

                                    if ($localPO) {
                                        // 3. Update the PO
                                        $localPO->update([
                                            'txn_id'   => $localBill->id,
                                            'txn_type' => $localBill->type,
                                            'status'   => 4
                                        ]);
                                        $metrics['linked_pos']++;
                                        \Log::info("[QB Import] REVERSE LINK: PO {$localPO->purchase_number} linked to {$localBill->type} (ID:{$localBill->id})");
                                    }
                                }
                            }
                        }
                    }
                }
                $poLinksStart += count($poData);
            } while (count($poData) === 50);

            DB::commit();
            
            \Log::info("[QB Import] Expenses Completed.", [
                'counts'         => $metrics,
                'skipped_items'  => $skippedDetails
            ]);
            
            return response()->json([
                'status'          => 'success',
                'message'         => "Import & Linking completed.",
                'metrics'         => $metrics,
                'skipped_details' => $skippedDetails
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("[QB Import] Expense Critical Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
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

    //         // Fetch chart accounts for mapping
    //         $accountsRaw = $this->qbController->runQuery("SELECT * FROM Account STARTPOSITION 1 MAXRESULTS 500");
    //         $accountsList = collect($accountsRaw['QueryResponse']['Account'] ?? []);

    //         // Now it's safe to access data
    //         $expensesData = collect($responseData['data'] ?? []);

    //         // === Helper function to process and create bank accounts ===
    //         $processBankAccount = function ($paymentAccount) {
    //             if (empty($paymentAccount)) {
    //                 return null;
    //             }

    //             $creatorId = \Auth::user()->creatorId();

    //             // Extract account ID and name from payment account
    //             $qbAccountCode = $paymentAccount['Id'] ?? null;
    //             $qbAccountName = $paymentAccount['Name'] ?? 'Bank Account';

    //             if (!$qbAccountCode) {
    //                 return null;
    //             }

    //             // Check if chart of account exists with this code
    //             $chartAccount = ChartOfAccount::where('code', $qbAccountCode)
    //                 ->where('created_by', $creatorId)
    //                 ->first();

    //             if (!$chartAccount) {
    //                 return null;
    //             }

    //             // Check if bank account already exists for this chart account
    //             $bankAccount = BankAccount::where('chart_account_id', $chartAccount->id)
    //                 ->where('created_by', $creatorId)
    //                 ->first();

    //             if ($bankAccount) {
    //                 return $bankAccount->id;
    //             }

    //             // Create new bank account
    //             try {
    //                 $newBankAccount = BankAccount::create([
    //                     'bank_name' => $qbAccountName,
    //                     'chart_account_id' => $chartAccount->id,
    //                     'created_by' => $creatorId,
    //                     'owned_by' => \Auth::user()->ownedId(),
    //                 ]);

    //                 return $newBankAccount->id;
    //             } catch (\Exception $e) {
    //                 \Log::error("Failed to create bank account: " . $e->getMessage());
    //                 return null;
    //             }
    //         };

    //         // === Helper: Get or create default cash account for non-bank payments ===
    //         $getDefaultCashAccount = function () {
    //             $creatorId = \Auth::user()->creatorId();

    //             // Try to find existing default cash account
    //             $existingBankAccount = BankAccount::where('created_by', $creatorId)
    //                 ->where('bank_name', 'like', '%Default%Cash%')
    //                 ->first();

    //             if ($existingBankAccount) {
    //                 return $existingBankAccount->id;
    //             }

    //             // Try to find a cash chart account
    //             $cashChartAccount = ChartOfAccount::where('created_by', $creatorId)
    //                 ->where('account_type', 'Cash')
    //                 ->orWhere('name', 'like', '%Cash%')
    //                 ->first();

    //             if ($cashChartAccount) {
    //                 // Create default cash bank account for this chart account if not exists
    //                 $bankAccount = BankAccount::firstOrCreate(
    //                     [
    //                         'chart_account_id' => $cashChartAccount->id,
    //                         'created_by' => $creatorId,
    //                     ],
    //                     [
    //                         'bank_name' => 'Default Cash Account',
    //                         'owned_by' => \Auth::user()->ownedId(),
    //                     ]
    //                 );
    //                 return $bankAccount->id;
    //             }

    //             // Create bank account without chart account
    //             try {
    //                 $bankAccount = BankAccount::create([
    //                     'bank_name' => 'Default Cash Account',
    //                     'chart_account_id' => null,
    //                     'created_by' => $creatorId,
    //                     'owned_by' => \Auth::user()->ownedId(),
    //                 ]);

    //                 return $bankAccount->id;
    //             } catch (\Exception $e) {
    //                 \Log::error("Failed to create default cash account: " . $e->getMessage());
    //                 return null;
    //             }
    //         };

    //         // Counters
    //         $imported = 0;
    //         $skipped = 0;
    //         $failed = 0;
    //         $defaultCashAccountId = null;

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

    //                     // Get vendor
    //                     $qbvendorId = $qbExpense['VendorId'] ?? null;
    //                     $vendor = null;

    //                     if ($qbvendorId) {
    //                         $vendor = Vender::where('vender_id', $qbvendorId)
    //                             ->where('created_by', \Auth::user()->creatorId())
    //                             ->first();
    //                     }

    //                     if (!$vendor) {
    //                         $skipped++;
    //                         continue;
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
    //                         'user_type' => 'Vendor',
    //                         'created_at' => Carbon::parse($qbExpense['TxnDate'])->format('Y-m-d H:i:s'),
    //                         'updated_at' => Carbon::parse($qbExpense['TxnDate'])->format('Y-m-d H:i:s'),
    //                     ]);

    //                     // Track total amount for vendor balance update
    //                     $totalAmount = 0;

    //                     // Process parsed lines (both products and accounts)
    //                     if (!empty($qbExpense['ParsedLines']) && is_array($qbExpense['ParsedLines'])) {
    //                         foreach ($qbExpense['ParsedLines'] as $line) {
    //                             if ($line['HasProduct']) {
    //                                 // This is a product line
    //                                 $itemName = $line['ItemName'];
    //                                 if (!$itemName)
    //                                     continue;

    //                                 $product = ProductService::where('name', $itemName)
    //                                     ->where('created_by', \Auth::user()->creatorId())
    //                                     ->first();

    //                                 if (!$product) {
    //                                     // Create product if it doesn't exist
    //                                     $unit = ProductServiceUnit::firstOrCreate(
    //                                         ['name' => 'pcs'],
    //                                         ['created_by' => \Auth::user()->creatorId()]
    //                                     );

    //                                     $productCategory = ProductServiceCategory::firstOrCreate(
    //                                         [
    //                                             'name' => 'Product',
    //                                             'created_by' => \Auth::user()->creatorId(),
    //                                         ],
    //                                         [
    //                                             'color' => '#4CAF50',
    //                                             'type' => 'Product',
    //                                             'chart_account_id' => 0,
    //                                             'created_by' => \Auth::user()->creatorId(),
    //                                             'owned_by' => \Auth::user()->ownedId(),
    //                                         ]
    //                                     );

    //                                     $productData = [
    //                                         'name' => $itemName,
    //                                         'sku' => $itemName,
    //                                         'sale_price' => 0,
    //                                         'purchase_price' => $line['Amount'] ?? 0,
    //                                         'quantity' => 0,
    //                                         'unit_id' => $unit->id,
    //                                         'type' => 'product',
    //                                         'category_id' => $productCategory->id,
    //                                         'created_by' => \Auth::user()->creatorId(),
    //                                     ];

    //                                     // Map chart accounts if available
    //                                     if (!empty($line['AccountId'])) {
    //                                         $account = ChartOfAccount::where('code', $line['AccountId'])
    //                                             ->where('created_by', \Auth::user()->creatorId())
    //                                             ->first();
    //                                         if ($account) {
    //                                             $productData['expense_chartaccount_id'] = $account->id;
    //                                         }
    //                                     }

    //                                     $product = ProductService::create($productData);
    //                                 }

    //                                 BillProduct::create([
    //                                     'bill_id' => $bill->id,
    //                                     'product_id' => $product->id,
    //                                     'quantity' => $line['Quantity'] ?? 1,
    //                                     'price' => $line['Amount'],
    //                                     'description' => $line['Description'],
    //                                 ]);

    //                                 $totalAmount += $line['Amount'];

    //                             } else {
    //                                 // This is an account line
    //                                 if (empty($line['AccountId']))
    //                                     continue;

    //                                 $account = ChartOfAccount::where('code', $line['AccountId'])
    //                                     ->where('created_by', \Auth::user()->creatorId())
    //                                     ->first();

    //                                 if (!$account) {
    //                                     $account = ChartOfAccount::where('name', $line['AccountName'] ?? '')
    //                                         ->where('created_by', \Auth::user()->creatorId())
    //                                         ->first();
    //                                 }

    //                                 if ($account) {
    //                                     BillAccount::create([
    //                                         'bill_id' => $bill->id,
    //                                         'chart_account_id' => $account->id,
    //                                         'price' => $line['Amount'] ?? 0,
    //                                         'description' => $line['Description'] ?? '',
    //                                         'type' => 'Expense',
    //                                         'ref_id' => $bill->id,
    //                                     ]);

    //                                     $totalAmount += $line['Amount'];
    //                                 }
    //                             }
    //                         }
    //                     }

    //                     // Track total payments for vendor balance update
    //                     $totalPayments = 0;

    //                     // Process payments if exist
    //                     $payments = $qbExpense['Payments'] ?? null;
    //                     if ($payments) {
    //                         $paymentsArray = $payments instanceof \Illuminate\Support\Collection
    //                             ? $payments->toArray()
    //                             : (is_array($payments) ? $payments : []);

    //                         if (!empty($paymentsArray)) {
    //                             foreach ($paymentsArray as $payment) {
    //                                 // Process bank account from payment
    //                                 $bankAccountId = null;

    //                                 // First try to use BankAccountId if already set by expensesWithPayments
    //                                 if (!empty($payment['BankAccountId'])) {
    //                                     $bankAccountId = $payment['BankAccountId'];
    //                                 } else {
    //                                     // Fallback: Process payment account
    //                                     $paymentAccount = $payment['PaymentAccount'] ?? null;

    //                                     if ($paymentAccount) {
    //                                         $bankAccountId = $processBankAccount($paymentAccount);
    //                                     }

    //                                     // If still no bank account, use default cash account
    //                                     if (!$bankAccountId) {
    //                                         if (!$defaultCashAccountId) {
    //                                             $defaultCashAccountId = $getDefaultCashAccount();
    //                                         }
    //                                         $bankAccountId = $defaultCashAccountId;
    //                                     }
    //                                 }

    //                                 // Determine payment method
    //                                 $paymentMethod = $payment['TxnTypeRaw'] ?? 'Other';
    //                                 if (isset($payment['Raw']['PaymentType'])) {
    //                                     $paymentMethod = $payment['Raw']['PaymentType'];
    //                                 }

    //                                 $paymentAmount = $payment['TotalAmount'] ?? 0;

    //                                 BillPayment::create([
    //                                     'bill_id' => $bill->id,
    //                                     'date' => $payment['TxnDate'] ?? $qbExpense['TxnDate'],
    //                                     'amount' => $paymentAmount,
    //                                     'account_id' => $bankAccountId,
    //                                     'payment_method' => $paymentMethod,
    //                                     'reference' => $payment['PaymentId'],
    //                                     'description' => 'QB Expense Payment',
    //                                     'created_at' => Carbon::parse($payment['TxnDate'] ?? $qbExpense['TxnDate'])->format('Y-m-d H:i:s'),
    //                                     'updated_at' => Carbon::parse($payment['TxnDate'] ?? $qbExpense['TxnDate'])->format('Y-m-d H:i:s'),
    //                                 ]);

    //                                 $totalPayments += $paymentAmount;

    //                                 if ($bankAccountId) {
    //                                     Utility::bankAccountBalance($bankAccountId, $paymentAmount, 'debit');
    //                                 }
    //                             }

    //                             // Mark as paid if payments exist
    //                             $bill->status = 4;
    //                             $bill->send_date = $qbExpense['TxnDate'];
    //                             $bill->save();
    //                         }
    //                     }

    //                     // Update vendor balance
    //                     if ($vendor) {
    //                         // Debit: expenses increase vendor's liability
    //                         if ($totalAmount > 0) {
    //                             Utility::updateUserBalance('vendor', $vendor->id, $totalAmount, 'debit');
    //                         }

    //                         // Credit: payments decrease vendor's liability
    //                         if ($totalPayments > 0) {
    //                             Utility::updateUserBalance('vendor', $vendor->id, $totalPayments, 'credit');
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
        \Log::info('[QB Import] Starting Consolidated Purchase, Bill & Credit Import.');

        $metrics = [
            'PO'     => ['imported' => 0, 'skipped' => 0, 'failed' => 0],
            'Bill'   => ['imported' => 0, 'skipped' => 0, 'failed' => 0],
            'Credit' => ['imported' => 0, 'skipped' => 0, 'failed' => 0]
        ];

        $poLinks = [];

        DB::beginTransaction();
        try {
            $creatorId = \Auth::user()->creatorId();
            $ownedId = \Auth::user()->ownedId();

            // -------------------------
            // PHASE 1: IMPORT PURCHASE ORDERS
            // -------------------------
            \Log::info('[QB Import] Phase 1: Fetching Purchase Orders...');
            $allPOs = collect();
            $startPosition = 1;
            $maxResults = 50;
            do {
                $query = "SELECT * FROM PurchaseOrder STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $poResponse = $this->qbController->runQuery($query);
                $poData = $poResponse['QueryResponse']['PurchaseOrder'] ?? [];
                if (array_key_exists('Id', $poData)) $poData = [$poData];
                $fetchedCount = count($poData);
                $allPOs = $allPOs->merge($poData);
                $startPosition += $fetchedCount;
            } while ($fetchedCount === $maxResults);

            foreach ($allPOs as $qbPO) {
                $qbId = $qbPO['Id'];
                if (!empty($qbPO['LinkedTxn'])) {
                    $linkedTxns = $qbPO['LinkedTxn'];
                    if (array_key_exists('TxnId', $linkedTxns)) $linkedTxns = [$linkedTxns];
                    foreach ($linkedTxns as $txn) {
                        $poLinks[] = [
                            'po_qb_id' => $qbId,
                            'target_qb_id' => $txn['TxnId'],
                            'target_type' => $txn['TxnType']
                        ];
                    }
                }

                try {
                    if (Purchase::where('purchase_id', $qbId)->exists()) {
                        $metrics['PO']['skipped']++;
                        continue;
                    }

                    $vendorId = $qbPO['VendorRef']['value'] ?? null;
                    $vendor = Vender::where('vender_id', $vendorId)->where('created_by', $creatorId)->first();
                    if (!$vendor) { $metrics['PO']['skipped']++; continue; }

                    $status = ($qbPO['POStatus'] ?? '') == 'Closed' ? 2 : 1;

                    Purchase::create([
                        'purchase_id'     => $qbId,
                        'vender_id'       => $vendor->id,
                        'purchase_date'   => $qbPO['TxnDate'],
                        'purchase_number' => $qbPO['DocNumber'] ?? 0,
                        'status'          => $status,
                        'type'            => 'PurchaseOrder',
                        'subtotal'        => $qbPO['TotalAmt'] ?? 0,
                        'total'           => $qbPO['TotalAmt'] ?? 0,
                        'ship_to_address' => $qbPO['ShipAddr']['Line1'] ?? null,
                        'created_by'      => $creatorId,
                        'owned_by'        => $ownedId,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);

                    $metrics['PO']['imported']++;
                } catch (\Exception $e) {
                    $metrics['PO']['failed']++;
                    \Log::error("[QB Import] PO Failed $qbId: " . $e->getMessage());
                }
            }

            // -------------------------
            // PHASE 2: IMPORT BILLS & PAYMENTS
            // -------------------------
            \Log::info('[QB Import] Phase 2: Fetching Bills...');
            $allBills = collect();
            $startPosition = 1;
            do {
                $query = "SELECT * FROM Bill STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $resp = $this->qbController->runQuery($query);
                $data = $resp['QueryResponse']['Bill'] ?? [];
                if (array_key_exists('Id', $data)) $data = [$data];
                $allBills = $allBills->merge($data);
                $startPosition += count($data);
            } while (count($data) === $maxResults);

            \Log::info('[QB Import] Fetching Bill Payments...');
            $allBillPayments = collect();
            $startPosition = 1;
            do {
                $query = "SELECT * FROM BillPayment STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $resp = $this->qbController->runQuery($query);
                $data = $resp['QueryResponse']['BillPayment'] ?? [];
                if (array_key_exists('Id', $data)) $data = [$data];
                $allBillPayments = $allBillPayments->merge($data);
                $startPosition += count($data);
            } while (count($data) === $maxResults);

            // Map payments to bills (useful to process payments seen from each bill)
            $billPaymentsMap = [];
            foreach ($allBillPayments as $payment) {
                $lines = $payment['Line'] ?? [];
                if (array_key_exists('Amount', $lines) || array_key_exists('LinkedTxn', $lines)) $lines = [$lines];
                foreach ($lines as $line) {
                    $linked = $line['LinkedTxn'] ?? [];
                    if (array_key_exists('TxnId', $linked)) $linked = [$linked];
                    foreach ($linked as $txn) {
                        if (($txn['TxnType'] ?? '') === 'Bill') {
                            $billPaymentsMap[$txn['TxnId']][] = $payment;
                        }
                    }
                }
            }

            // We'll also need quick lookup of created BillPayment records by payment ref if we want to link later
            $createdBillPaymentByRef = [];

            // Process each bill
            foreach ($allBills as $qbBill) {
                $qbId = $qbBill['Id'];
                $docNumber = $qbBill['DocNumber'] ?? 'N/A';

                try {
                    if (Bill::where('bill_id', $qbId)->exists()) {
                        $metrics['Bill']['skipped']++;
                        continue;
                    }

                    $vendorId = $qbBill['VendorRef']['value'] ?? null;
                    $vendor = Vender::where('vender_id', $vendorId)->where('created_by', $creatorId)->first();
                    if (!$vendor) { $metrics['Bill']['skipped']++; continue; }

                    $billTotal = floatval($qbBill['TotalAmt'] ?? 0);

                    $bill = Bill::create([
                        'bill_id' => $qbId,
                        'vender_id' => $vendor->id,
                        'bill_date' => $qbBill['TxnDate'],
                        'due_date' => $qbBill['DueDate'] ?? $qbBill['TxnDate'],
                        'order_number' => $docNumber,
                        'subtotal' => $billTotal,
                        'total' => $billTotal,
                        'status' => ($qbBill['Balance'] ?? 0) == 0 ? 4 : 2,
                        'type' => 'Bill',
                        'user_type' => 'Vendor',
                        'created_by' => $creatorId,
                        'owned_by' => $ownedId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Process bill lines and compute combined line total
                    $rawLines = $qbBill['Line'] ?? [];
                    if (array_key_exists('DetailType', $rawLines) || array_key_exists('Amount', $rawLines)) $rawLines = [$rawLines];
                    $lineTotal = 0.0;
                    foreach ($rawLines as $line) {
                        $amount = floatval($line['Amount'] ?? 0);
                        $lineTotal += $amount;
                        $desc = $line['Description'] ?? null;
                        if (!empty($line['ItemBasedExpenseLineDetail'])) {
                            $d = $line['ItemBasedExpenseLineDetail'];
                            $itemName = $d['ItemRef']['name'] ?? 'Unknown';
                            $prod = ProductService::firstOrCreate(
                                ['name' => $itemName, 'created_by' => $creatorId],
                                ['sku' => $itemName, 'type'=>'product', 'unit_id'=>1, 'category_id'=>1]
                            );
                            BillProduct::create([
                                'bill_id' => $bill->id, 'product_id' => $prod->id, 'quantity' => $d['Qty'] ?? 1, 'price' => $amount, 'tax' => 0, 'description' => $desc
                            ]);
                        } elseif (!empty($line['AccountBasedExpenseLineDetail'])) {
                            $d = $line['AccountBasedExpenseLineDetail'];
                            $qbAccId = $d['AccountRef']['value'] ?? null;
                            $lAccId = 0;
                            if ($qbAccId) {
                                $la = ChartOfAccount::where('code', $qbAccId)->first();
                                if ($la) $lAccId = $la->id;
                            }
                            BillAccount::create([
                                'ref_id' => $bill->id, 'type' => 'Bill', 'chart_account_id' => $lAccId, 'price' => $amount, 'description' => $desc, 'tax' => 0, 'quantity_ordered' => 1
                            ]);
                        }
                    }

                    // -------------------------
                    // PROCESS PAYMENTS FOR THIS BILL
                    // -------------------------
                    if (isset($billPaymentsMap[$qbId])) {
                        foreach ($billPaymentsMap[$qbId] as $paymentData) {
                            // Grab values and normalize
                            $payAmountTotal = floatval($paymentData['TotalAmt'] ?? 0.0);
                            $payDate = $paymentData['TxnDate'] ?? now();
                            $payRef = $paymentData['PaymentRefNum'] ?? 'QB-' . ($paymentData['Id'] ?? uniqid('qb_'));
                            $qbPaymentId = $paymentData['Id'] ?? null;

                            // Get bank/chart account (same helper you already use)
                            $sourceAccount = $paymentData['CheckPayment']['BankAccountRef'] ?? $paymentData['CreditCardPayment']['CCAccountRef'] ?? null;
                            $bankAccountId = 0; $chartAccountId = 0;
                            if ($sourceAccount) {
                                $bankAccountId = $this->getOrCreateBankAccountFromChartAccount(
                                    $sourceAccount['value'] ?? null,
                                    $sourceAccount['name'] ?? null
                                );
                                if ($bankAccountId) {
                                    $bankAccountModel = \App\Models\BankAccount::find($bankAccountId);
                                    if ($bankAccountModel) $chartAccountId = $bankAccountModel->chart_account_id;
                                }
                            }

                            // Determine the amount from payment lines that was applied to THIS bill (if QuickBooks shows it)
                            $appliedAmountFromLine = null; // null means no explicit line amount; we'll fallback to billTotal
                            $paymentLines = $paymentData['Line'] ?? [];
                            if (array_key_exists('Amount', $paymentLines)) $paymentLines = [$paymentLines];
                            if (!is_array($paymentLines)) $paymentLines = [];

                            foreach ($paymentLines as $pl) {
                                $linked = $pl['LinkedTxn'] ?? [];
                                if (array_key_exists('TxnId', $linked)) $linked = [$linked];
                                foreach ($linked as $l) {
                                    if (($l['TxnType'] ?? '') === 'Bill' && ($l['TxnId'] == $qbId)) {
                                        // if QB provides Amount on this line, use it
                                        if (isset($pl['Amount'])) {
                                            $appliedAmountFromLine = floatval($pl['Amount']);
                                        } else {
                                            // no explicit amount on line; use bill total
                                            $appliedAmountFromLine = $billTotal;
                                        }
                                        break 2;
                                    }
                                }
                            }

                            // Final applied amount recorded for this bill: use appliedAmountFromLine if present, else assume full bill amount
                            $appliedAmountToRecord = $appliedAmountFromLine !== null ? $appliedAmountFromLine : $billTotal;
                            // Ensure we never record more than the bill total here (main payments)
                            if ($appliedAmountToRecord > $billTotal) $appliedAmountToRecord = $billTotal;

                            // Round to cents to avoid floating errors
                            $appliedAmountToRecord = round($appliedAmountToRecord, 2);
                            $payAmountTotal = round($payAmountTotal, 2);

                            // Detailed logging per bill+payment
                            \Log::info("[QB Import] Processing Bill {$qbId} ({$docNumber}) Vendor: {$vendor->name}");
                            \Log::info("  Bill Total: " . number_format($billTotal, 2));
                            \Log::info("  Combined Line Total: " . number_format($lineTotal, 2));
                            \Log::info("  Payment Total from QB (payment id {$qbPaymentId}): " . number_format($payAmountTotal, 2));
                            \Log::info("  Applied Amount to THIS bill (to record): " . number_format($appliedAmountToRecord, 2));

                            // Create BillPayment (local) for the applied amount
                            $paymentRecord = BillPayment::create([
                                'bill_id' => $bill->id,
                                'date' => $payDate,
                                'amount' => $appliedAmountToRecord,
                                'account_id' => $bankAccountId,
                                'chart_account_id' => $chartAccountId,
                                'payment_method' => 'QB Import',
                                'reference' => $payRef,
                                'description' => 'Imported from QuickBooks',
                            ]);

                            if ($paymentRecord && isset($paymentRecord->id)) {
                                // store mapping so we can link unapplied later if needed
                                $createdBillPaymentByRef[$payRef][] = $paymentRecord->id;

                                // Create main Transaction for the applied amount (category Bill)
                                \App\Models\Transaction::create([
                                    'user_id' => $vendor->id,
                                    'user_type' => 'Vendor',
                                    'type' => 'Payment',
                                    'payment_id' => $paymentRecord->id,
                                    'amount' => $appliedAmountToRecord,
                                    'date' => $payDate,
                                    'payment_no' => $payRef,
                                    'description' => 'Bill Payment Imported from QuickBooks',
                                    'account' => $bankAccountId,
                                    'category' => 'Bill',
                                    'created_by' => $creatorId,
                                    'owned_by' => $ownedId,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            } else {
                                \Log::warning("[QB Import] Could not create local BillPayment for QB Payment {$qbPaymentId} / Bill {$qbId}");
                            }

                            // Update vendor balance for the bill's total (same as before)
                            Utility::updateUserBalance('vendor', $vendor->id, $billTotal, 'debit');
                        } // end foreach payments for this bill
                    } // end if payments for bill exist

                    $metrics['Bill']['imported']++;
                } catch (\Exception $e) {
                    $metrics['Bill']['failed']++;
                    \Log::error("[QB Import] Bill Failed $qbId: " . $e->getMessage());
                }
            } // end foreach allBills

            // =======================================================================
            // POST-PROCESS: For each QuickBooks BillPayment, compute unapplied amount:
            // unapplied = payment.TotalAmt - sum(amounts on linked bill lines)
            // Create a single Vendor Credit transaction per QB payment if unapplied > threshold.
            // =======================================================================
            \Log::info('[QB Import] Phase 2: Post-processing BillPayments for unapplied overpayments (one record per QB payment)...');

            foreach ($allBillPayments as $paymentData) {
                try {
                    $qbPaymentId = $paymentData['Id'] ?? null;
                    $payRef = $paymentData['PaymentRefNum'] ?? 'QB-' . ($qbPaymentId ?? uniqid('qb_'));
                    $paymentTotal = round(floatval($paymentData['TotalAmt'] ?? 0.0), 2);

                    // Sum amounts from lines that were linked to Bills (these are the QB-applied amounts)
                    $sumApplied = 0.0;
                    $paymentLines = $paymentData['Line'] ?? [];
                    if (array_key_exists('Amount', $paymentLines)) $paymentLines = [$paymentLines];
                    if (!is_array($paymentLines)) $paymentLines = [];

                    foreach ($paymentLines as $pl) {
                        $linked = $pl['LinkedTxn'] ?? [];
                        if (array_key_exists('TxnId', $linked)) $linked = [$linked];
                        foreach ($linked as $l) {
                            if (($l['TxnType'] ?? '') === 'Bill') {
                                // Some QB lines may not include Amount; guard it
                                if (isset($pl['Amount'])) $sumApplied += floatval($pl['Amount']);
                            }
                        }
                    }

                    $sumApplied = round($sumApplied, 2);
                    $unapplied = round($paymentTotal - $sumApplied, 2);

                    \Log::info("[QB Import] QB PaymentID: {$qbPaymentId} Ref: {$payRef}");
                    \Log::info("  Payment Total: " . number_format($paymentTotal, 2));
                    \Log::info("  Sum Applied to Bills (from payment lines): " . number_format($sumApplied, 2));
                    \Log::info("  Calculated Unapplied (payment - applied): " . number_format($unapplied, 2));

                    // small epsilon to avoid floating rounding noise
                    if ($unapplied >= 0.01) {
                        // Find vendor for this payment
                        $vendorQbId = $paymentData['VendorRef']['value'] ?? null;
                        $vendor = null;
                        if ($vendorQbId) {
                            $vendor = Vender::where('vender_id', $vendorQbId)->where('created_by', $creatorId)->first();
                        }
                        if (!$vendor) {
                            \Log::warning("[QB Import] Unapplied overpayment skipped: vendor not found for payment {$qbPaymentId}");
                            continue;
                        }

                        // Resolve bank/chart account similarly
                        $sourceAccount = $paymentData['CheckPayment']['BankAccountRef'] ?? $paymentData['CreditCardPayment']['CCAccountRef'] ?? null;
                        $bankAccountId = 0; $chartAccountId = 0;
                        if ($sourceAccount) {
                            $bankAccountId = $this->getOrCreateBankAccountFromChartAccount(
                                $sourceAccount['value'] ?? null,
                                $sourceAccount['name'] ?? null
                            );
                            if ($bankAccountId) {
                                $bankAccountModel = \App\Models\BankAccount::find($bankAccountId);
                                if ($bankAccountModel) $chartAccountId = $bankAccountModel->chart_account_id;
                            }
                        }

                        // Try to link to a created BillPayment local record (if any) using payRef
                        $linkPaymentId = null;
                        if (isset($createdBillPaymentByRef[$payRef]) && count($createdBillPaymentByRef[$payRef]) > 0) {
                            // pick the first created BillPayment id for this payment as a reference
                            $linkPaymentId = $createdBillPaymentByRef[$payRef][0];
                        }

                        // Create single Vendor Credit transaction for unapplied amount
                        $txn = \App\Models\Transaction::create([
                            'user_id' => $vendor->id,
                            'user_type' => 'Vendor',
                            'type' => 'Payment',
                            'payment_id' => $linkPaymentId, // may be null
                            'amount' => $unapplied,
                            'date' => $paymentData['TxnDate'] ?? now(),
                            'payment_no' => $payRef,
                            'description' => 'Unapplied overpayment from QuickBooks (imported)',
                            'account' => $bankAccountId,
                            'chart_account_id' => $chartAccountId,
                            'category' => 'Vendor Credit',
                            'created_by' => $creatorId,
                            'owned_by' => $ownedId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        Utility::updateUserBalance('vendor', $vendor->id, $unapplied, 'credit');

                        \Log::info("[QB Import] Created Vendor Credit transaction (unapplied) for payment {$qbPaymentId}: amount " . number_format($unapplied, 2) . " txn_id={$txn->id}");
                    } else {
                        \Log::info("[QB Import] No unapplied amount for payment {$qbPaymentId} (unapplied={$unapplied})");
                    }
                } catch (\Exception $e) {
                    \Log::error("[QB Import] Error while processing unapplied overpayment for QB Payment {$paymentData['Id']}: " . $e->getMessage());
                }
            }

            // -------------------------
            // PHASE 3: IMPORT VENDOR CREDITS (existing QB VendorCredit objects)
            // -------------------------
            \Log::info('[QB Import] Phase 3: Fetching Vendor Credits...');
            $allCredits = collect();
            $startPosition = 1;
            do {
                $query = "SELECT * FROM VendorCredit STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $resp = $this->qbController->runQuery($query);
                $data = $resp['QueryResponse']['VendorCredit'] ?? [];
                if (array_key_exists('Id', $data)) $data = [$data];
                $fetchedCount = count($data);
                $allCredits = $allCredits->merge($data);
                $startPosition += $fetchedCount;
            } while ($fetchedCount === $maxResults);

            foreach ($allCredits as $qbCredit) {
                $qbId = $qbCredit['Id'];
                try {
                    if (\App\Models\VendorCredit::where('vendor_credit_id', $qbId)->exists()) {
                        $metrics['Credit']['skipped']++;
                        continue;
                    }

                    $vendorId = $qbCredit['VendorRef']['value'] ?? null;
                    $vendor = Vender::where('vender_id', $vendorId)->where('created_by', $creatorId)->first();
                    if (!$vendor) {
                        $metrics['Credit']['skipped']++;
                        continue;
                    }

                    $credit = \App\Models\VendorCredit::create([
                        'vendor_credit_id' => $qbId,
                        'vender_id' => $vendor->id,
                        'date' => $qbCredit['TxnDate'],
                        'amount' => $qbCredit['TotalAmt'] ?? 0,
                        'memo' => $qbCredit['PrivateNote'] ?? null,
                        'created_by' => $creatorId,
                        'owned_by' => $ownedId,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);

                    $rawLines = $qbCredit['Line'] ?? [];
                    if (array_key_exists('DetailType', $rawLines) || array_key_exists('Amount', $rawLines)) $rawLines = [$rawLines];

                    foreach ($rawLines as $line) {
                        $amount = $line['Amount'] ?? 0;
                        $description = $line['Description'] ?? null;
                        $isBillable = 0;
                        $localCustomerId = null;

                        if (!empty($line['ItemBasedExpenseLineDetail'])) {
                            $detail = $line['ItemBasedExpenseLineDetail'];
                            if (isset($detail['BillableStatus']) && $detail['BillableStatus'] === 'Billable') {
                                $isBillable = 1;
                                if (isset($detail['CustomerRef']['value'])) {
                                    $customer = \App\Models\Customer::where('customer_id', $detail['CustomerRef']['value'])->where('created_by', $creatorId)->first();
                                    if ($customer) $localCustomerId = $customer->id;
                                }
                            }
                            $itemName = $detail['ItemRef']['name'] ?? 'Unknown Item';
                            $product = ProductService::firstOrCreate(
                                ['name' => $itemName, 'created_by' => $creatorId],
                                ['sku' => $itemName, 'purchase_price' => $amount, 'type' => 'product', 'unit_id' => 1, 'category_id' => 1]
                            );

                            \App\Models\VendorCreditProduct::create([
                                'vendor_credit_id' => $credit->id,
                                'product_id' => $product->id,
                                'quantity' => $detail['Qty'] ?? 1,
                                'price' => $amount,
                                'description' => $description,
                                'tax' => 0,
                                'billable' => $isBillable,
                                'customer_id' => $localCustomerId
                            ]);
                        } elseif (!empty($line['AccountBasedExpenseLineDetail'])) {
                            $detail = $line['AccountBasedExpenseLineDetail'];
                            if (isset($detail['BillableStatus']) && $detail['BillableStatus'] === 'Billable') {
                                $isBillable = 1;
                                if (isset($detail['CustomerRef']['value'])) {
                                    $customer = \App\Models\Customer::where('customer_id', $detail['CustomerRef']['value'])->where('created_by', $creatorId)->first();
                                    if ($customer) $localCustomerId = $customer->id;
                                }
                            }
                            $accRef = $detail['AccountRef']['value'] ?? null;
                            if ($accRef) {
                                $chartAcc = ChartOfAccount::where('code', $accRef)->where('created_by', $creatorId)->first();
                                if ($chartAcc) {
                                    \App\Models\VendorCreditAccount::create([
                                        'vendor_credit_id' => $credit->id,
                                        'chart_account_id' => $chartAcc->id,
                                        'price' => $amount,
                                        'description' => $description,
                                        'tax' => 0,
                                        'billable' => $isBillable,
                                        'customer_id' => $localCustomerId,
                                        'created_at' => now(), 'updated_at' => now()
                                    ]);
                                }
                            }
                        }
                    }

                    Utility::updateUserBalance('vendor', $vendor->id, $qbCredit['TotalAmt'], 'credit');
                    $metrics['Credit']['imported']++;
                } catch (\Exception $e) {
                    $metrics['Credit']['failed']++;
                    \Log::error("[QB Import] Credit Failed $qbId: " . $e->getMessage());
                }
            }

            // -------------------------
            // PHASE 4: RECONCILIATION (PO links)
            // -------------------------
            foreach ($poLinks as $link) {
                try {
                    $localPo = Purchase::where('purchase_id', $link['po_qb_id'])->where('created_by', $creatorId)->first();
                    if (!$localPo) continue;

                    if ($link['target_type'] === 'Bill' || $link['target_type'] === 'Purchase') {
                        $localBill = Bill::where('bill_id', $link['target_qb_id'])->where('created_by', $creatorId)->first();
                        if ($localBill) {
                            $localPo->txn_id = $localBill->id;
                            $localPo->txn_type = $link['target_type'];
                            $localPo->status = 2; // Closed
                            $localPo->save();
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning("[QB Import] PO Link reconcile failed: " . $e->getMessage());
                }
            }

            DB::commit();
            \Log::info('[QB Import] Import completed.', $metrics);
            return response()->json(['status'=>true, 'metrics'=>$metrics]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('[QB Import] Failed: ' . $e->getMessage());
            return response()->json(['status'=>false, 'error'=>$e->getMessage()]);
        }
    }

    public function importUnappliedPayments(Request $request)
    {
        try {
            \Log::info("========== UNAPPLIED PAYMENT IMPORT START ==========");

            $creatorId = \Auth::user()->creatorId();
            $ownedId = \Auth::user()->ownedId();

            $allPayments = collect();
            $startPosition = 1;
            $maxResults = 50;

            // Fetch BillPayments from QuickBooks
            \Log::info("[QB Import] Fetching BillPayments from QuickBooks...");
            do {
                $query = "SELECT * FROM BillPayment STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
                $resp = $this->qbController->runQuery($query);
                $data = $resp['QueryResponse']['BillPayment'] ?? [];
                
                // Normalize single result to array
                if (array_key_exists('Id', $data)) {
                    $data = [$data];
                }
                
                $fetchedCount = count($data);
                \Log::info("Fetched Batch → Start: {$startPosition}, Count: {$fetchedCount}");
                
                $allPayments = $allPayments->merge($data);
                $startPosition += $fetchedCount;

            } while ($fetchedCount === $maxResults);

            \Log::info("[QB Import] Total BillPayments fetched: " . $allPayments->count());

            $imported = 0;
            $skipped = 0;
            $failed = 0;

            // Process each payment
            foreach ($allPayments as $paymentData) {
                try {
                    $qbPaymentId = $paymentData['Id'] ?? null;
                    $payRef = $paymentData['PaymentRefNum'] ?? 'QB-' . ($qbPaymentId ?? uniqid('qb_'));
                    $paymentTotal = round(floatval($paymentData['TotalAmt'] ?? 0.0), 2);
                    $txnDate = $paymentData['TxnDate'] ?? null;
                    $vendorQbId = $paymentData['VendorRef']['value'] ?? null;

                    \Log::info("\n================ PROCESSING PAYMENT ================");
                    \Log::info("QB Payment ID: {$qbPaymentId}");
                    \Log::info("Reference: {$payRef}");
                    \Log::info("Payment Total: " . number_format($paymentTotal, 2));
                    \Log::info("Vendor QB ID: {$vendorQbId}");

                    // Skip if already imported
                    if (\App\Models\UnappliedPayment::where('qb_payment_id', $qbPaymentId)->exists()) {
                        \Log::info("Skipping: Already imported");
                        $skipped++;
                        continue;
                    }

                    // Calculate applied amount from payment lines
                    $sumApplied = 0.0;
                    $linkedBillTxns = [];
                    $paymentLines = $paymentData['Line'] ?? [];
                    
                    // Normalize single line to array
                    if (array_key_exists('Amount', $paymentLines) || array_key_exists('LinkedTxn', $paymentLines)) {
                        $paymentLines = [$paymentLines];
                    }
                    
                    if (!is_array($paymentLines)) {
                        $paymentLines = [];
                    }

                    foreach ($paymentLines as $pl) {
                        $linked = $pl['LinkedTxn'] ?? [];
                        
                        // Normalize single linked to array
                        if (array_key_exists('TxnId', $linked)) {
                            $linked = [$linked];
                        }
                        
                        foreach ($linked as $l) {
                            if (($l['TxnType'] ?? '') === 'Bill') {
                                // Track linked bills for reference
                                $linkedBillTxns[] = [
                                    'TxnId' => $l['TxnId'],
                                    'TxnType' => $l['TxnType']
                                ];
                                
                                // Sum applied amounts
                                if (isset($pl['Amount'])) {
                                    $sumApplied += floatval($pl['Amount']);
                                }
                            }
                        }
                    }

                    $sumApplied = round($sumApplied, 2);
                    $unappliedAmount = round($paymentTotal - $sumApplied, 2);

                    \Log::info("Sum Applied to Bills: " . number_format($sumApplied, 2));
                    \Log::info("Calculated Unapplied Amount: " . number_format($unappliedAmount, 2));

                    // Only import if there's an unapplied amount (threshold: $0.01 to avoid floating point errors)
                    if ($unappliedAmount < 0.01) {
                        \Log::info("Skipping: No unapplied amount");
                        $skipped++;
                        continue;
                    }

                    // Find local vendor
                    $vendorId = null;
                    $vendor = null;
                    if ($vendorQbId) {
                        $vendor = \App\Models\Vender::where('vender_id', $vendorQbId)
                            ->where('created_by', $creatorId)
                            ->first();
                        $vendorId = $vendor?->id;
                    }

                    if (!$vendor) {
                        \Log::warning("Skipping: Vendor not found locally (QB ID: {$vendorQbId})");
                        $skipped++;
                        continue;
                    }

                    \Log::info("Matched Local Vendor: {$vendor->name} (ID: {$vendorId})");

                    // Resolve bank account and chart account
                    $sourceAccount = $paymentData['CheckPayment']['BankAccountRef'] 
                        ?? $paymentData['CreditCardPayment']['CCAccountRef'] 
                        ?? $paymentData['PayFromAccountRef']
                        ?? null;
                    
                    $bankAccountId = null;
                    $chartAccountId = null;

                    if ($sourceAccount && isset($sourceAccount['value'])) {
                        // This function returns bank account ID (integer), not a model
                        $bankAccountId = $this->getOrCreateBankAccountFromChartAccount(
                            $sourceAccount['value'] ?? null,
                            $sourceAccount['name'] ?? null
                        );
                        
                        // If we got a bank account ID, fetch the model to get chart_account_id
                        if ($bankAccountId) {
                            $bankAccountModel = \App\Models\BankAccount::find($bankAccountId);
                            if ($bankAccountModel) {
                                $chartAccountId = $bankAccountModel->chart_account_id;
                            }
                        }
                    }

                    \Log::info("Bank Account ID: {$bankAccountId}");
                    \Log::info("Chart Account ID: {$chartAccountId}");

                    // Insert unapplied payment
                    \App\Models\UnappliedPayment::updateOrCreate(
                        [
                            'qb_payment_id' => $qbPaymentId,
                        ],
                        [
                            'reference'         => $payRef,
                            'vendor_id'         => $vendorId,
                            'vendor_qb_id'      => $vendorQbId,
                            'total_amount'      => $paymentTotal,
                            'applied_amount'    => $sumApplied,
                            'unapplied_amount'  => $unappliedAmount,
                            'txn_date'          => $txnDate,
                            'account_id'        => $bankAccountId,
                            'chart_account_id'  => $chartAccountId,
                            'linked_bill_txns'  => !empty($linkedBillTxns) ? json_encode($linkedBillTxns) : null,
                            'raw'               => json_encode($paymentData),
                            'created_by'        => $creatorId,
                            'owned_by'          => $ownedId,
                        ]
                    );

                    \Log::info("✓ Unapplied payment saved successfully!");
                    $imported++;

                } catch (\Exception $e) {
                    $failed++;
                    \Log::error("Failed to import payment {$qbPaymentId}: " . $e->getMessage());
                    \Log::error($e->getTraceAsString());
                }
            }

            \Log::info("========== UNAPPLIED PAYMENT IMPORT END ==========");
            \Log::info("Imported: {$imported}, Skipped: {$skipped}, Failed: {$failed}");

            return response()->json([
                'status'   => 'success',
                'message'  => "Unapplied Payments import completed. Imported: {$imported}, Skipped: {$skipped}, Failed: {$failed}",
                'imported' => $imported,
                'skipped'  => $skipped,
                'failed'   => $failed,
            ]);

        } catch (\Exception $ex) {

            \Log::error("Unapplied Payment Import Error: " . $ex->getMessage());
            \Log::error($ex->getTraceAsString());

            return response()->json([
                'error'   => 'Something went wrong',
                'details' => $ex->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | REUSABLE FETCH FUNCTION
    |--------------------------------------------------------------------------
    */
    private function fetchQbObjects($entity)
    {
        $results = collect();
        $start = 1;
        $limit = 50;

        do {
            $query = "SELECT * FROM {$entity} ORDER BY Id STARTPOSITION {$start} MAXRESULTS {$limit}";
            $batch = $this->qbController->runQuery($query);

            if ($batch && is_array($batch)) {
                $results = $results->merge($batch);
            }

            $count = $batch ? count($batch) : 0;
            $start += $limit;

        } while ($count === $limit);

        return $results;
    }





    // public function importBills(Request $request)
    // {
    //     try {
    //         // === Fetch Bills ===
    //         $allBills = collect();
    //         $startPosition = 1;
    //         $maxResults = 50;

    //         do {
    //             $query = "SELECT * FROM Bill STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
    //             $billsResponse = $this->qbController->runQuery($query);
    //             if ($billsResponse instanceof \Illuminate\Http\JsonResponse)
    //                 return $billsResponse;

    //             $billsData = $billsResponse['QueryResponse']['Bill'] ?? [];
    //             $allBills = $allBills->merge($billsData);
    //             $fetchedCount = count($billsData);
    //             $startPosition += $fetchedCount;
    //         } while ($fetchedCount === $maxResults);

    //         // === Fetch Bill Payments ===
    //         $allBillPayments = collect();
    //         $startPosition = 1;
    //         do {
    //             $query = "SELECT * FROM BillPayment STARTPOSITION {$startPosition} MAXRESULTS {$maxResults}";
    //             $billPaymentsResponse = $this->qbController->runQuery($query);
    //             if ($billPaymentsResponse instanceof \Illuminate\Http\JsonResponse)
    //                 return $billPaymentsResponse;

    //             $billPaymentsData = $billPaymentsResponse['QueryResponse']['BillPayment'] ?? [];
    //             $allBillPayments = $allBillPayments->merge($billPaymentsData);
    //             $fetchedCount = count($billPaymentsData);
    //             $startPosition += $fetchedCount;
    //         } while ($fetchedCount === $maxResults);

    //         // === Fetch Items & Accounts ===
    //         $itemsRaw = $this->qbController->runQuery("SELECT * FROM Item STARTPOSITION 1 MAXRESULTS 500");
    //         $accountsRaw = $this->qbController->runQuery("SELECT * FROM Account STARTPOSITION 1 MAXRESULTS 500");

    //         $itemsList = collect($itemsRaw['QueryResponse']['Item'] ?? []);
    //         $accountsList = collect($accountsRaw['QueryResponse']['Account'] ?? []);

    //         $itemsMap = $itemsList->keyBy(fn($it) => $it['Id'] ?? null)->toArray();
    //         $accountsMap = $accountsList->keyBy(fn($a) => $a['Id'] ?? null)->toArray();

    //         // === Helper Functions ===
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
    //                 $item = $itemsMap[$sid['ItemRef']['value']] ?? null;
    //                 if ($item) {
    //                     if (!empty($item['ExpenseAccountRef']['value'])) {
    //                         return [
    //                             'AccountId' => $item['ExpenseAccountRef']['value'],
    //                             'AccountName' => $item['ExpenseAccountRef']['name'] ?? ($accountsMap[$item['ExpenseAccountRef']['value']]['Name'] ?? null)
    //                         ];
    //                     }
    //                     if (!empty($item['AssetAccountRef']['value'])) {
    //                         return [
    //                             'AccountId' => $item['AssetAccountRef']['value'],
    //                             'AccountName' => $item['AssetAccountRef']['name'] ?? ($accountsMap[$item['AssetAccountRef']['value']]['Name'] ?? null)
    //                         ];
    //                     }
    //                 }
    //             }
    //             return ['AccountId' => null, 'AccountName' => null];
    //         };

    //         $parseBillLine = function ($line) use ($detectAccountForExpenseItem) {
    //             $out = [];
    //             $detailType = $line['DetailType'] ?? null;

    //             if (!empty($line['GroupLineDetail']) && !empty($line['GroupLineDetail']['Line'])) {
    //                 foreach ($line['GroupLineDetail']['Line'] as $child) {
    //                     if (!empty($child['ItemBasedExpenseLineDetail'])) {
    //                         $sid = $child['ItemBasedExpenseLineDetail'];
    //                         $acc = $detectAccountForExpenseItem($sid);
    //                         $out[] = [
    //                             'DetailType' => $child['DetailType'] ?? 'ItemBasedExpenseLineDetail',
    //                             'Description' => $child['Description'] ?? ($sid['ItemRef']['name'] ?? null),
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
    //                     }
    //                 }
    //                 return $out;
    //             }

    //             if (!empty($line['ItemBasedExpenseLineDetail'])) {
    //                 $sid = $line['ItemBasedExpenseLineDetail'];
    //                 $acc = $detectAccountForExpenseItem($sid);
    //                 $out[] = [
    //                     'DetailType' => 'ItemBasedExpenseLineDetail',
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
    //                     'DetailType' => 'AccountBasedExpenseLineDetail',
    //                     'Description' => $line['Description'] ?? null,
    //                     'Amount' => $line['Amount'] ?? 0,
    //                     'AccountId' => $accDetail['AccountRef']['value'] ?? null,
    //                     'AccountName' => $accDetail['AccountRef']['name'] ?? null,
    //                     'RawLine' => $line,
    //                     'HasProduct' => false,
    //                 ];
    //                 return $out;
    //             }

    //             return [
    //                 [
    //                     'DetailType' => $detailType,
    //                     'Description' => $line['Description'] ?? null,
    //                     'Amount' => $line['Amount'] ?? 0,
    //                     'AccountId' => null,
    //                     'AccountName' => null,
    //                     'RawLine' => $line,
    //                     'HasProduct' => false,
    //                 ]
    //             ];
    //         };

    //         // === Extract payment account reference from different payment types ===
    //         $extractPaymentAccountRef = function ($payment) {
    //             if (!empty($payment['CreditCardPayment']['CCAccountRef'])) {
    //                 return $payment['CreditCardPayment']['CCAccountRef'];
    //             }
    //             if (!empty($payment['CheckPayment']['BankAccountRef'])) {
    //                 return $payment['CheckPayment']['BankAccountRef'];
    //             }
    //             if (!empty($payment['PayFromAccountRef'])) {
    //                 return $payment['PayFromAccountRef'];
    //             }
    //             return null;
    //         };

    //         // === Helper function to process and create bank accounts ===
    //         $processBankAccount = function ($payFromAccountRef) {
    //             if (empty($payFromAccountRef) || empty($payFromAccountRef['value'])) {
    //                 return null;
    //             }

    //             $qbAccountCode = $payFromAccountRef['value'];
    //             $qbAccountName = $payFromAccountRef['name'] ?? 'Bank Account';
    //             $creatorId = \Auth::user()->creatorId();

    //             // Check if chart of account exists with this code
    //             $chartAccount = ChartOfAccount::where('code', $qbAccountCode)
    //                 ->where('created_by', $creatorId)
    //                 ->first();

    //             if (!$chartAccount) {
    //                 return null;
    //             }

    //             // Check if bank account already exists for this chart account
    //             $bankAccount = BankAccount::where('chart_account_id', $chartAccount->id)
    //                 ->where('created_by', $creatorId)
    //                 ->first();

    //             if ($bankAccount) {
    //                 return $bankAccount->id;
    //             }

    //             // Create new bank account
    //             try {
    //                 $newBankAccount = BankAccount::create([
    //                     'bank_name' => $qbAccountName,
    //                     'chart_account_id' => $chartAccount->id,
    //                     'created_by' => $creatorId,
    //                     'owned_by' => \Auth::user()->ownedId(),
    //                 ]);

    //                 return $newBankAccount->id;
    //             } catch (\Exception $e) {
    //                 \Log::error("Failed to create bank account: " . $e->getMessage());
    //                 return null;
    //             }
    //         };

    //         // === Get or create default cash account for non-bank payments ===
    //         $getDefaultCashAccount = function () {
    //             $creatorId = \Auth::user()->creatorId();

    //             // Try to find existing default cash account
    //             $existingBankAccount = BankAccount::where('created_by', $creatorId)
    //                 ->where('name', 'like', '%Default%Cash%')
    //                 ->first();

    //             if ($existingBankAccount) {
    //                 return $existingBankAccount->id;
    //             }

    //             // Try to find a cash chart account
    //             $cashChartAccount = ChartOfAccount::where('created_by', $creatorId)
    //                 ->where('account_type', 'Cash')
    //                 ->orWhere('name', 'like', '%Cash%')
    //                 ->first();

    //             if ($cashChartAccount) {
    //                 // Create default cash bank account for this chart account if not exists
    //                 $bankAccount = BankAccount::firstOrCreate(
    //                     [
    //                         'chart_account_id' => $cashChartAccount->id,
    //                         'created_by' => $creatorId,
    //                     ],
    //                     [
    //                         'name' => 'Default Cash Account',
    //                         'owned_by' => \Auth::user()->ownedId(),
    //                     ]
    //                 );
    //                 return $bankAccount->id;
    //             }

    //             // Create bank account without chart account
    //             try {
    //                 $bankAccount = BankAccount::create([
    //                     'bank_name' => 'Default Cash Account',
    //                     'chart_account_id' => null,
    //                     'created_by' => $creatorId,
    //                     'owned_by' => \Auth::user()->ownedId(),
    //                 ]);

    //                 return $bankAccount->id;
    //             } catch (\Exception $e) {
    //                 \Log::error("Failed to create default cash account: " . $e->getMessage());
    //                 return null;
    //             }
    //         };

    //         // === Parse Bills ===
    //         $bills = $allBills->map(function ($bill) use ($parseBillLine) {
    //             $parsedLines = [];
    //             foreach ($bill['Line'] ?? [] as $line) {
    //                 $parsedLines = array_merge($parsedLines, $parseBillLine($line));
    //             }

    //             return [
    //                 'BillId' => (string) ($bill['Id'] ?? null),
    //                 'VendorId' => $bill['VendorRef']['value'] ?? null,
    //                 'VendorName' => $bill['VendorRef']['name'] ?? null,
    //                 'TxnDate' => $bill['TxnDate'] ?? null,
    //                 'DueDate' => $bill['DueDate'] ?? null,
    //                 'DocNumber' => $bill['DocNumber'] ?? null,
    //                 'TotalAmount' => (float) ($bill['TotalAmt'] ?? 0),
    //                 'Balance' => (float) ($bill['Balance'] ?? 0),
    //                 'ParsedLines' => $parsedLines,
    //                 'Payments' => [],
    //             ];
    //         });

    //         // === Match Payments ===
    //         $billPayments = $allBillPayments->map(function ($payment) use ($extractPaymentAccountRef) {
    //             $linked = [];
    //             foreach ($payment['Line'] ?? [] as $l) {
    //                 if (!empty($l['LinkedTxn'])) {
    //                     $linked = array_merge($linked, is_array($l['LinkedTxn']) ? $l['LinkedTxn'] : [$l['LinkedTxn']]);
    //                 }
    //             }
    //             return [
    //                 'PaymentId' => $payment['Id'] ?? null,
    //                 'VendorId' => $payment['VendorRef']['value'] ?? null,
    //                 'TxnDate' => $payment['TxnDate'] ?? null,
    //                 'TotalAmount' => (float) ($payment['TotalAmt'] ?? 0),
    //                 'LinkedTxn' => $linked,
    //                 'PaymentAccountRef' => $extractPaymentAccountRef($payment),
    //                 'RawPayment' => $payment,
    //             ];
    //         });

    //         $billsById = $bills->keyBy('BillId')->toArray();
    //         foreach ($billsById as $billId => &$bill) {
    //             $bill['Payments'] = collect($billPayments)->filter(function ($p) use ($billId) {
    //                 return collect($p['LinkedTxn'])->contains(fn($txn) => isset($txn['TxnType'], $txn['TxnId']) && strtolower($txn['TxnType']) === 'bill' && (string) $txn['TxnId'] === (string) $billId);
    //             })->values()->toArray();
    //         }

    //         // === Import Logic ===
    //         DB::beginTransaction();
    //         $imported = $skipped = $failed = 0;
    //         $defaultCashAccountId = null;

    //         foreach ($billsById as $qbBill) {
    //             try {
    //                 if (Bill::where('bill_id', $qbBill['BillId'])->exists()) {
    //                     $skipped++;
    //                     continue;
    //                 }

    //                 $vendor = Vender::where('vender_id', $qbBill['VendorId'])
    //                     ->where('created_by', \Auth::user()->creatorId())
    //                     ->first();

    //                 if (!$vendor) {
    //                     $skipped++;
    //                     continue;
    //                 }

    //                 $bill = Bill::create([
    //                     'bill_id' => $qbBill['BillId'],
    //                     'vender_id' => $vendor->id,
    //                     'bill_date' => $qbBill['TxnDate'],
    //                     'due_date' => $qbBill['DueDate'],
    //                     'order_number' => $qbBill['DocNumber'] ?? 0,
    //                     'status' => 2,
    //                     'created_by' => \Auth::user()->creatorId(),
    //                     'owned_by' => \Auth::user()->ownedId(),
    //                     'type' => 'Bill',
    //                     'user_type' => 'Vendor',
    //                     'created_at' => Carbon::parse($qbBill['TxnDate'])->format('Y-m-d H:i:s'),
    //                     'updated_at' => Carbon::parse($qbBill['TxnDate'])->format('Y-m-d H:i:s'),
    //                 ]);

    //                 // === Handle Bill Lines (Items + Accounts) ===
    //                 $totalAmount = 0;
    //                 foreach ($qbBill['ParsedLines'] as $line) {
    //                     if ($line['HasProduct']) {
    //                         // This is a product line
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
    //                         ]);
    //                     } else {
    //                         // This is an account line
    //                         $account = ChartOfAccount::where('code', $line['AccountId'])
    //                             ->where('created_by', \Auth::user()->creatorId())
    //                             ->first();

    //                         if ($account) {
    //                             BillAccount::create([
    //                                 'bill_id' => $bill->id,
    //                                 'chart_account_id' => $account->id,
    //                                 'price' => $line['Amount'],
    //                                 'description' => $line['Description'],
    //                                 'type' => 'Bill',
    //                                 'ref_id' => $bill->id,
    //                             ]);
    //                         }
    //                     }
    //                     $totalAmount += $line['Amount'];
    //                 }

    //                 // === Payment Handling ===
    //                 $billPaid = $qbBill['TotalAmount'] - $qbBill['Balance'];
    //                 if ($billPaid > 0) {
    //                     $bankAccountId = null;

    //                     // Try to get bank account from linked payments
    //                     if (!empty($qbBill['Payments'])) {
    //                         foreach ($qbBill['Payments'] as $payment) {
    //                             $paymentAccountRef = $payment['PaymentAccountRef'] ?? null;
    //                             if ($paymentAccountRef) {
    //                                 $bankAccountId = $processBankAccount($paymentAccountRef);
    //                                 if ($bankAccountId) {
    //                                     break;
    //                                 }
    //                             }
    //                         }
    //                     }

    //                     // If no bank account found from payments, use default cash account
    //                     if (!$bankAccountId) {
    //                         if (!$defaultCashAccountId) {
    //                             $defaultCashAccountId = $getDefaultCashAccount();
    //                         }
    //                         $bankAccountId = $defaultCashAccountId;
    //                     }

    //                     // Create payment record
    //                     BillPayment::create([
    //                         'bill_id' => $bill->id,
    //                         'date' => $qbBill['TxnDate'],
    //                         'amount' => $billPaid,
    //                         'account_id' => $bankAccountId,
    //                         'payment_method' => 'QuickBooks Auto',
    //                         'reference' => 'Balance-based Settlement',
    //                         'description' => 'Auto Payment from Bill Balance',
    //                         'created_at' => Carbon::parse($qbBill['TxnDate'])->format('Y-m-d H:i:s'),
    //                         'updated_at' => Carbon::parse($qbBill['TxnDate'])->format('Y-m-d H:i:s'),
    //                     ]);

    //                     $bill->status = 4;
    //                     $bill->save();

    //                     if ($bankAccountId) {
    //                         Utility::bankAccountBalance($bankAccountId, $billPaid, 'debit');
    //                     }
    //                     Utility::updateUserBalance('vendor', $vendor->id, $billPaid, 'credit');
    //                 }

    //                 // === Vendor Balance Update ===
    //                 Utility::updateUserBalance('vendor', $vendor->id, $totalAmount, 'debit');

    //                 $imported++;
    //             } catch (\Exception $e) {
    //                 $failed++;
    //                 \Log::error('Bill import error: ' . $e->getMessage());
    //             }
    //         }

    //         DB::commit();
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => "Imported: {$imported}, Skipped: {$skipped}, Failed: {$failed}"
    //         ]);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         \Log::error("Bills import error: " . $e->getMessage());
    //         return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    //     }
    // }

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
            if ($batchEnd->gt($endDate))
                $batchEnd = $endDate->copy();
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

                if (empty($firstValue) && empty($currentDateValue))
                    continue;

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

            if ($accountId) {
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

            if (!$token)
                throw new \Exception("No QuickBooks tokens for user {$this->userId}");

            if ($token->expires_at && now()->addMinutes(5)->greaterThan($token->expires_at)) {
                $this->logInfo('Refreshing QuickBooks token...');
                $api = new QuickBooksApiController();
                $new = $api->refreshToken($token->refresh_token);
                if ($new)
                    $this->logSuccess('QuickBooks token refreshed successfully');
                else
                    throw new \Exception('Token refresh failed');
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
    protected function logSuccess($msg)
    {
        $this->addLog('[SUCCESS]', $msg);
    }
    protected function logError($msg)
    {
        $this->addLog('[ERROR]', $msg);
    }
    protected function logInfo($msg)
    {
        $this->addLog('[INFO]', $msg);
    }

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
                                    $inv = Invoice::where('invoice_id', $txnId)->first();
                                    $convertedInvoiceId = $inv->id;
                                    break;
                                }
                            }
                        }

                        // --- Map QuickBooks statuses to internal numeric codes ---
                        $statusText = strtolower($estimate['TxnStatus'] ?? '');
                        $statusMap = [
                            'pending' => 1,
                            'accepted' => 2,
                            'converted' => 2,
                            'rejected' => 3,
                            'closed' => 4,
                        ];
                        $statusCode = $statusMap[$statusText] ?? 0; // default 0 if unknown

                        // --- Set conversion flag ---
                        $isConvert = ($statusText == 'converted' || $convertedInvoiceId) ? 1 : 0;

                        // --- Create Proposal ---
                        $txnDate = $estimate['TxnDate'] ?? now()->toDateString();
                        $proposal = Proposal::create([
                            'proposal_id' => $estimateId,
                            'customer_id' => $customer->id,
                            'issue_date' => $txnDate,
                            'send_date' => $txnDate,
                            'category_id' => $category->id,
                            'status' => $statusCode,
                            'discount_apply' => 0,
                            'is_convert' => $isConvert,
                            'converted_invoice_id' => $convertedInvoiceId ?: 0,
                            'created_by' => \Auth::user()->creatorId(),
                            'owned_by' => \Auth::user()->ownedId(),
                            'created_at' => Carbon::parse($txnDate)->format('Y-m-d H:i:s'),
                            'updated_at' => Carbon::parse($txnDate)->format('Y-m-d H:i:s'),
                        ]);

                        // --- Parse estimate lines ---
                        $lines = $estimate['Line'] ?? [];
                        foreach ($lines as $line) {
                            if (!isset($line['SalesItemLineDetail']))
                                continue;

                            $sid = $line['SalesItemLineDetail'];
                            $itemName = $sid['ItemRef']['name'] ?? $line['Description'] ?? null;
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
                            $rate = ($amount);
                            ProposalProduct::create([
                                'proposal_id' => $proposal->id,
                                'product_id' => $product->id,
                                'quantity' => $quantity,
                                'tax' => 0,
                                'discount' => 0,
                                'price' => $price,
                                'rate' => $rate,
                                'description' => $description,
                                'created_at' => now(),
                                'updated_at' => now(),
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
            $batchSize = 1000;
            $startPosition = 1;

            // configuration: set to false if you don't want new customers auto-created
            $autoCreateCustomer = true;

            DB::beginTransaction();
            $creatorId = \Auth::user()->creatorId();
            $ownerId = \Auth::user()->ownedId();

            do {
                // 1️⃣ Fetch deposits from QuickBooks
                $query = "SELECT * FROM Deposit STARTPOSITION {$startPosition} MAXRESULTS {$batchSize}";
                $depositResponse = $this->qbController->runQuery($query);

                if ($depositResponse instanceof \Illuminate\Http\JsonResponse) {
                     // If error, rollback and return
                     DB::rollBack();
                     return $depositResponse;
                }

                $depositsData = $depositResponse['QueryResponse']['Deposit'] ?? [];

                if (empty($depositsData)) {
                    break; 
                }



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
                $firstEntityType = null;
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

                        // We don't skip anymore, we just proceed with no entity info
                        // $skipped++;
                        // continue;
                    }

                    $entityTypeRaw = $detail['Entity']['Type'] ?? $detail['Entity']['type'] ?? null;
                    $entityType = ucfirst(strtolower($entityTypeRaw));
                    $entityId = null;
                    $entityModel = null;

                    if ($entityType === 'Vendor') {
                        // Vendor lookup
                        if (!empty($customerQbId)) {
                             $entityModel = Vender::where('vender_id', $customerQbId)
                                ->where('created_by', $creatorId)
                                ->first();
                        }
                        // Fallback by name
                        if (!$entityModel && !empty($customerName)) {
                             $entityModel = Vender::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($customerName))])
                                ->where('created_by', $creatorId)
                                ->first();
                        }
                        
                        // Auto-create Vendor
                        if (!$entityModel && $autoCreateCustomer && !empty($customerName)) {
                            try {
                                $entityModel = Vender::create([
                                    'name' => $customerName,
                                    'vender_id' => $customerQbId ?? null, // Note: Vender model uses vender_id
                                    'created_by' => $creatorId,
                                    'billing_name' => $customerName, // minimal required fields often needed
                                    'email' => '', // placeholder
                                ]);
                                \Log::info("Auto-created vendor for deposit {$qbDepositId}", [
                                    'vendor' => $entityModel->toArray()
                                ]);
                            } catch (\Exception $e) {
                                \Log::error("Failed to auto-create vendor for deposit {$qbDepositId}: " . $e->getMessage());
                                // don't fail hard, just skip linking
                            }
                        }

                        if ($entityModel) {
                            $entityId = $entityModel->id;
                            \Log::info("Matched vendor for deposit {$qbDepositId} line {$lineIndex}", [
                                'vendor_id' => $entityModel->id,
                                'name' => $entityModel->name,
                            ]);
                        }

                    } elseif ($entityType === 'Customer') {
                        // Customer lookup
                        // Rename variable for clarity, though we kept $customerQbId above
                        if (!empty($customerQbId)) {
                            $entityModel = Customer::where(function ($q) use ($customerQbId) {
                                $q->where('customer_id', $customerQbId);
                            })->where('created_by', $creatorId)->first();
                        }
    
                        if (!$entityModel && !empty($customerName)) {
                            $entityModel = Customer::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($customerName))])
                                ->where('created_by', $creatorId)
                                ->first();
                        }
    
                        if (!$entityModel && $autoCreateCustomer && !empty($customerName)) {
                            try {
                                $entityModel = Customer::create([
                                    'name' => $customerName,
                                    'customer_id' => $customerQbId ?? null,
                                    'created_by' => $creatorId,
                                ]);
                                \Log::info("Auto-created customer for deposit {$qbDepositId}", [
                                    'customer' => $entityModel->toArray()
                                ]);
                            } catch (\Exception $e) {
                                \Log::error("Failed to auto-create customer for deposit {$qbDepositId}: " . $e->getMessage());
                            }
                        }

                        if ($entityModel) {
                            $entityId = $entityModel->id;
                            \Log::info("Matched customer for deposit {$qbDepositId} line {$lineIndex}", [
                                'customer_id' => $entityModel->id,
                                'name' => $entityModel->name,
                            ]);
                        }
                    }

                    if (!$entityModel) {
                        \Log::warning("⚠️ Entity ({$entityType}) not found for Deposit {$qbDepositId} line {$lineIndex}", [
                            'qbId' => $customerQbId,
                            'name' => $customerName,
                        ]);
                        // We continue without ID 
                    }

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

                    DepositLines::create([
                        'deposit_id' => $depositModel->id,
                        'amount' => $line['Amount'] ?? 0,
                        'detail_type' => $line['DetailType'] ?? null,
                        'customer_id' => $entityId, // Store ID here regardless of type (Vendor/Customer)
                        'entity_type' => $entityType,
                        'chart_account_id' => $chartAccount->id ?? null,
                        'payment_method' => $detail['PaymentMethodRef']['name'] ?? null,
                        'check_num' => $detail['CheckNum'] ?? null,
                        'linked_txns' => !empty($linkedTxns) ? json_encode($linkedTxns) : null,
                    ]);

                    // maintain top-level pointers
                    if (!$firstCustomerId && $entityId) {
                        $firstCustomerId = $entityId;
                        $firstEntityType = $entityType;
                    }
                    if (!$primaryChartAccountId && $chartAccount)
                        $primaryChartAccountId = $chartAccount->id;
                    $otherAccountId = $chartAccount->id ?? $otherAccountId;
                    $imported++;
                } // end foreach lines

                // Update deposit top-level links
                $depositModel->update([
                    'customer_id' => $firstCustomerId,
                    'entity_type' => $firstEntityType,
                    'chart_account_id' => $primaryChartAccountId,
                    'other_account_id' => $otherAccountId,
                ]);
            } // end foreach deposits

            $startPosition += $batchSize;

        } while (count($depositsData) >= $batchSize);

        DB::commit();

        if ($imported == 0 && $skipped == 0 && $failed == 0) {
             return response()->json([
                'status' => 'success',
                'count' => 0,
                'message' => 'No deposits found in QuickBooks response.',
            ]);
        }
        
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