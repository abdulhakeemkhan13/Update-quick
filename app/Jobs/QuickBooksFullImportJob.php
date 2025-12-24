<?php

namespace App\Jobs;

use App\Http\Controllers\QuickBooksApiController;
use App\Http\Controllers\QuickBooksImportController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class QuickBooksFullImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 7200; // ⏱ Increased to 2 hours
    public $tries = 3;

    protected $importOrder = [
        'customers' => 'Importing Customers',
        // 'vendors' => 'Importing Vendors',
        // 'chartOfAccounts' => 'Importing Chart of Accounts',
        // 'items' => 'Importing Items/Products',
        // 'taxes' => 'Importing Taxes',
        // 'invoices' => 'Importing Invoices',
        // 'bills' => 'Importing Bills',
        // 'unappliedPayments' => 'Importing Unapplied Payments',
        // 'expenses' => 'Importing Expenses',
        // 'estimates' => 'Importing Estimates', not used raw
        // 'deposits' => 'Importing Deposits', not used raw
        // 'journalReport' => 'Importing Journal Reports',
        'importSalesReceipts' => 'Importing Sales Receipts',
    ];

    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function handle(): void
    {
        // 🧠 Increase memory limit for long import jobs
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 7200);

        \Auth::loginUsingId($this->userId);

        $totalSteps = count($this->importOrder);
        $this->initializeProgress($totalSteps);

        Log::info("QuickBooks Full Import Job started for user: {$this->userId}");

        try {
            $currentStep = 0;
            $controller = new QuickBooksImportController();

            foreach ($this->importOrder as $method => $description) {
                $currentStep++;
                $this->logInfo("Starting {$description}...");
                $this->updateProgress($currentStep, $totalSteps, $description);

                $this->refreshTokenIfNeeded();
                $this->handleRateLimit();

                // ✅ Run the import
                $result = $this->$method($controller);

                // Handle JSON errors
                if ($result instanceof \Illuminate\Http\JsonResponse) {
                    $data = json_decode($result->getContent(), true);
                    if (($data['status'] ?? '') === 'error') {
                        $msg = "Error in {$description}: " . ($data['message'] ?? 'Unknown error');
                        $this->logError($msg);
                        Log::error($msg);
                        $this->updateProgress($currentStep, $totalSteps, "{$description} (Failed)");
                        continue;
                    }
                }

                $successMsg = "{$description} completed successfully";
                $this->logSuccess($successMsg);
                Log::info($successMsg);

                $this->updateProgress($currentStep, $totalSteps, "{$description} ✓");

                // 🧹 Free memory after each import method
                unset($result);
                unset($controller);
                gc_collect_cycles(); // Force garbage collection

                // 🧩 Recreate controller for next iteration
                $controller = new QuickBooksImportController();

                // Optional: delay between imports (API safety)
                sleep(1);
            }

            $this->logSuccess('All imports completed successfully!');
            $this->updateProgress($totalSteps, $totalSteps, 'Import completed successfully', 'completed');
            Log::info("QuickBooks Full Import Job completed for user {$this->userId}");

        } catch (\Throwable $e) {
            $msg = "QuickBooks Full Import Job Failed: {$e->getMessage()}";
            Log::error($msg, ['trace' => $e->getTraceAsString(), 'user_id' => $this->userId]);
            $this->logError($msg);
            $this->updateProgress($currentStep ?? 0, $totalSteps ?? 0, $msg, 'failed');
        }
    }

    protected function customers($controller) { return $controller->customers(); }
    protected function unappliedPayments($controller) { return $controller->importUnappliedPayments(new Request()); }
    protected function deposits($controller) { return $controller->importDeposits(new Request()); }
    protected function taxes($controller) { return $controller->importTaxes(); }
    protected function vendors($controller) { return $controller->vendors(); }
    protected function chartOfAccounts($controller) { return $controller->chartOfAccounts(); }
    protected function items($controller) { return $controller->items(); }
    protected function invoices($controller) { return $controller->importInvoices(new Request()); }
    protected function bills($controller) { return $controller->importBills(new Request()); }
    protected function expenses($controller) { return $controller->importExpenses(new Request()); }
    protected function estimates($controller) { return $controller->importEstimates(new Request()); }

    protected function journalReport($controller)
    {
        $request = new Request([
            'start_date' => '2010-06-01',
            'end_date' => now()->format('Y-m-d'),
            'accounting_method' => 'Accrual'
        ]);
        return $controller->journalReport($request);
    }

    // --- Logging & Cache Helpers ---
    protected function initializeProgress($totalSteps)
    {
        $key = "qb_import_progress_{$this->userId}";
        $logs = (Cache::get($key)['logs'] ?? []);
        $logs[] = '[INFO] Import job started at ' . now();

        Cache::put($key, [
            'status' => 'running',
            'current_step' => 0,
            'total_steps' => $totalSteps,
            'current_import' => 'Initializing...',
            'logs' => $logs,
            'percentage' => 0,
        ], 3600);
    }

    protected function updateProgress($step, $total, $import, $status = 'running')
    {
        $key = "qb_import_progress_{$this->userId}";
        $progress = Cache::get($key, []);
        $progress['status'] = $status;
        $progress['current_step'] = $step;
        $progress['total_steps'] = $total;
        $progress['current_import'] = $import;
        $percentage = round(($step / $total) * 100);
        if ($percentage >= 100 && $status != 'completed') {
            $percentage = 95; 
        }
        $progress['percentage'] = $percentage;

        if (isset($progress['logs']) && count($progress['logs']) > 100) {
            $progress['logs'] = array_slice($progress['logs'], -100);
        }

        Cache::put($key, $progress, 3600);
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

    protected function handleRateLimit() { sleep(1); }

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
}
