<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Vender;
use App\Models\ProductService;
use App\Models\Bill;
use App\Models\Transaction;
use App\Services\SearchAIService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class GlobalSearchController extends Controller
{
    protected $aiService;

    public function __construct(SearchAIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Get recent transactions for initial display
     */
    public function recentTransactions(Request $request)
    {
        $user = Auth::user();
        $results = collect();

        try {
            // Get recent invoices
            $invoices = Invoice::where('created_by', $user->creatorId())
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            foreach ($invoices as $i) {
                $customerName = $i->customer ? $i->customer->name : 'Unknown';
                $results->push([
                    'type' => 'Invoice',
                    'icon' => 'ti ti-file-invoice',
                    'title' => 'Invoice | ' . $customerName,
                    'date' => $i->issue_date ? Carbon::parse($i->issue_date)->format('m/d/Y') : '',
                    'amount' => $user->priceFormat($i->getTotal()),
                    'url' => route('invoice.show', Crypt::encrypt($i->id)),
                    'created_at' => $i->created_at,
                ]);
            }

            // Get recent bills
            $bills = Bill::where('created_by', $user->creatorId())
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            foreach ($bills as $b) {
                $vendorName = $b->vender ? $b->vender->name : 'Unknown';
                $results->push([
                    'type' => 'Bill',
                    'icon' => 'ti ti-receipt',
                    'title' => 'Bill | ' . $vendorName,
                    'date' => $b->bill_date ? Carbon::parse($b->bill_date)->format('m/d/Y') : '',
                    'amount' => $user->priceFormat($b->getTotal()),
                    'url' => route('vender.show', Crypt::encrypt($b->vender_id)),
                    'created_at' => $b->created_at,
                ]);
            }

            // Sort by date and take top 10
            $results = $results->sortByDesc('created_at')->take(10)->values();

        } catch (\Throwable $e) {
            // Return empty on error
        }

        return response()->json([
            'recent' => $results,
            'ai_enabled' => $this->aiService->isEnabled(),
        ]);
    }

    /**
     * Main search endpoint with AI + Meilisearch
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        
        if (empty($query)) {
            return response()->json([]);
        }

        $user = Auth::user();
        $results = collect();
        $lowerQuery = strtolower(trim($query));

        // =====================================================
        // 1. AI-POWERED NATURAL LANGUAGE PROCESSING
        // =====================================================
        if ($this->aiService->isEnabled() && $this->aiService->isComplexQuery($query)) {
            $intent = $this->aiService->parseQuery($query);

            // Handle aggregate queries (sum, count, etc.)
            if ($intent['type'] === 'aggregate') {
                $answer = $this->aiService->executeAggregate($intent);
                if ($answer) {
                    $results->push($answer);
                }
            }

            // Handle navigation
            if ($intent['type'] === 'navigation' && !empty($intent['target'])) {
                $navResult = $this->handleNavigation($intent['target']);
                if ($navResult) {
                    $results->push($navResult);
                }
            }

            // Handle filtered queries
            if ($intent['type'] === 'filter' || $intent['type'] === 'top_n') {
                $filtered = $this->executeFilteredQuery($intent);
                $results = $results->merge($filtered);
            }
        }

        // =====================================================
        // 2. NAVIGATION COMMANDS (Regex fallback)
        // =====================================================
        $navigationCommands = [
            ['patterns' => ['show me profile', 'show profile', 'my profile', 'profile'], 'route' => 'profile', 'label' => 'My Profile'],
            ['patterns' => ['settings', 'show settings'], 'route' => 'settings', 'label' => 'Settings'],
            ['patterns' => ['invoices', 'show invoices', 'all invoices'], 'route' => 'invoice.index', 'label' => 'All Invoices'],
            ['patterns' => ['bills', 'show bills', 'all bills'], 'route' => 'bill.index', 'label' => 'All Bills'],
            ['patterns' => ['customers', 'show customers'], 'route' => 'customer.index', 'label' => 'All Customers'],
            ['patterns' => ['vendors', 'show vendors'], 'route' => 'vender.index', 'label' => 'All Vendors'],
            ['patterns' => ['products', 'show products'], 'route' => 'productservice.index', 'label' => 'Products & Services'],
            ['patterns' => ['create invoice', 'new invoice'], 'route' => 'invoice.create', 'label' => 'Create Invoice', 'param' => 0],
            ['patterns' => ['create bill', 'new bill'], 'route' => 'bill.create', 'label' => 'Create Bill'],
            ['patterns' => ['create customer', 'new customer'], 'route' => 'customer.create', 'label' => 'Create Customer'],
            ['patterns' => ['profit and loss', 'p&l', 'pnl'], 'route' => 'report.profit.loss', 'label' => 'Profit & Loss Report'],
            ['patterns' => ['balance sheet'], 'route' => 'report.balance.sheet', 'label' => 'Balance Sheet'],
            ['patterns' => ['dashboard', 'home'], 'route' => 'home', 'label' => 'Dashboard'],
        ];

        foreach ($navigationCommands as $cmd) {
            foreach ($cmd['patterns'] as $pattern) {
                if (str_contains($lowerQuery, $pattern) || $lowerQuery === $pattern) {
                    try {
                        if (Route::has($cmd['route'])) {
                            $url = isset($cmd['param']) ? route($cmd['route'], $cmd['param']) : route($cmd['route']);
                            $results->push([
                                'type' => 'Action',
                                'icon' => 'ti ti-bolt',
                                'label' => $cmd['label'],
                                'sub_label' => 'Quick Navigation',
                                'url' => $url,
                            ]);
                        }
                    } catch (\Throwable $e) {}
                    break;
                }
            }
        }

        // =====================================================
        // 3. DATE/YEAR FILTERS (Regex)
        // =====================================================
        $yearMatch = null;
        $dateFilter = null;

        // Match specific year: "2024 transactions"
        if (preg_match('/\b(20\d{2})\b/', $query, $matches)) {
            $yearMatch = $matches[1];
        }

        // Match "last X years" or "past X years"
        if (preg_match('/(?:last|past)\s*(\d+)\s*years?/i', $query, $matches)) {
            $years = intval($matches[1]);
            $dateFilter = ['start' => Carbon::now()->subYears($years)->startOfYear(), 'end' => Carbon::now()->endOfDay()];
        }
        // Match "last X months" or "past X months"
        elseif (preg_match('/(?:last|past)\s*(\d+)\s*months?/i', $query, $matches)) {
            $months = intval($matches[1]);
            $dateFilter = ['start' => Carbon::now()->subMonths($months)->startOfMonth(), 'end' => Carbon::now()->endOfDay()];
        }
        // Match "last X weeks"
        elseif (preg_match('/(?:last|past)\s*(\d+)\s*weeks?/i', $query, $matches)) {
            $weeks = intval($matches[1]);
            $dateFilter = ['start' => Carbon::now()->subWeeks($weeks)->startOfWeek(), 'end' => Carbon::now()->endOfDay()];
        }
        // Match "last X days"
        elseif (preg_match('/(?:last|past)\s*(\d+)\s*days?/i', $query, $matches)) {
            $days = intval($matches[1]);
            $dateFilter = ['start' => Carbon::now()->subDays($days)->startOfDay(), 'end' => Carbon::now()->endOfDay()];
        }
        // Simple relative patterns
        elseif (str_contains($lowerQuery, 'last month')) {
            $dateFilter = ['start' => Carbon::now()->subMonth()->startOfMonth(), 'end' => Carbon::now()->subMonth()->endOfMonth()];
        } elseif (str_contains($lowerQuery, 'this month')) {
            $dateFilter = ['start' => Carbon::now()->startOfMonth(), 'end' => Carbon::now()->endOfMonth()];
        } elseif (str_contains($lowerQuery, 'last week')) {
            $dateFilter = ['start' => Carbon::now()->subWeek()->startOfWeek(), 'end' => Carbon::now()->subWeek()->endOfWeek()];
        } elseif (str_contains($lowerQuery, 'this week')) {
            $dateFilter = ['start' => Carbon::now()->startOfWeek(), 'end' => Carbon::now()->endOfWeek()];
        } elseif (str_contains($lowerQuery, 'this year')) {
            $dateFilter = ['start' => Carbon::now()->startOfYear(), 'end' => Carbon::now()->endOfYear()];
        } elseif (str_contains($lowerQuery, 'last year')) {
            $dateFilter = ['start' => Carbon::now()->subYear()->startOfYear(), 'end' => Carbon::now()->subYear()->endOfYear()];
        } elseif (str_contains($lowerQuery, 'today')) {
            $dateFilter = ['start' => Carbon::today(), 'end' => Carbon::today()->endOfDay()];
        } elseif (str_contains($lowerQuery, 'yesterday')) {
            $dateFilter = ['start' => Carbon::yesterday(), 'end' => Carbon::yesterday()->endOfDay()];
        }

        // =====================================================
        // 4. AMOUNT FILTERS (Regex)
        // =====================================================
        $amountFilter = null;
        
        if (preg_match('/(?:over|above|more than|greater than)\s*\$?(\d+(?:,\d{3})*(?:\.\d{2})?)/i', $query, $matches)) {
            $amountFilter = ['type' => 'gt', 'value' => floatval(str_replace(',', '', $matches[1]))];
        } elseif (preg_match('/(?:under|below|less than)\s*\$?(\d+(?:,\d{3})*(?:\.\d{2})?)/i', $query, $matches)) {
            $amountFilter = ['type' => 'lt', 'value' => floatval(str_replace(',', '', $matches[1]))];
        }

        // =====================================================
        // 5. MEILISEARCH / SCOUT SEARCH
        // =====================================================
        if ($results->count() < 5) {
            // Customers - Try Scout first, fallback to LIKE
            try {
                $customers = Customer::search($query)->where('created_by', $user->creatorId())->take(3)->get();
                foreach ($customers as $c) {
                    $results->push([
                        'type' => 'Customer',
                        'icon' => 'ti ti-user',
                        'label' => $c->name,
                        'sub_label' => $c->email ?? 'Customer',
                        'url' => route('customer.show', Crypt::encrypt($c->id)),
                    ]);
                }
            } catch (\Throwable $e) {
                $customers = Customer::where('created_by', $user->creatorId())
                    ->where('name', 'LIKE', "%$query%")
                    ->take(3)->get();
                foreach ($customers as $c) {
                    $results->push([
                        'type' => 'Customer',
                        'icon' => 'ti ti-user',
                        'label' => $c->name,
                        'sub_label' => $c->email ?? 'Customer',
                        'url' => route('customer.show', Crypt::encrypt($c->id)),
                    ]);
                }
            }

            // Vendors
            try {
                $vendors = Vender::search($query)->where('created_by', $user->creatorId())->take(3)->get();
                foreach ($vendors as $v) {
                    $results->push([
                        'type' => 'Vendor',
                        'icon' => 'ti ti-building-store',
                        'label' => $v->name,
                        'sub_label' => $v->email ?? 'Vendor',
                        'url' => route('vender.show', Crypt::encrypt($v->id)),
                    ]);
                }
            } catch (\Throwable $e) {
                $vendors = Vender::where('created_by', $user->creatorId())
                    ->where('name', 'LIKE', "%$query%")
                    ->take(3)->get();
                foreach ($vendors as $v) {
                    $results->push([
                        'type' => 'Vendor',
                        'icon' => 'ti ti-building-store',
                        'label' => $v->name,
                        'sub_label' => $v->email ?? 'Vendor',
                        'url' => route('vender.show', Crypt::encrypt($v->id)),
                    ]);
                }
            }

            // Invoices with filters
            $invoiceQuery = Invoice::where('created_by', $user->creatorId());
            if ($yearMatch) {
                $invoiceQuery->whereYear('issue_date', $yearMatch);
            }
            if ($dateFilter) {
                $invoiceQuery->whereBetween('issue_date', [$dateFilter['start'], $dateFilter['end']]);
            }
            
            // If we have date/year filter, don't require keyword match
            $hasDateFilter = $yearMatch || $dateFilter;
            $searchingTransactions = str_contains($lowerQuery, 'transaction') || str_contains($lowerQuery, 'invoice');
            
            if ($hasDateFilter || $searchingTransactions) {
                // Get invoices based on date filter (no keyword needed)
                $invoices = $invoiceQuery->orderBy('issue_date', 'desc')->take(5)->get();
            } else {
                // Normal keyword search
                $invoices = $invoiceQuery->where(function($q) use ($query) {
                    $q->where('ref_number', 'LIKE', "%$query%")
                      ->orWhere('invoice_id', 'LIKE', "%$query%");
                })->take(3)->get();
            }
            
            foreach ($invoices as $i) {
                $total = $i->getTotal();
                if ($amountFilter) {
                    if ($amountFilter['type'] === 'gt' && $total <= $amountFilter['value']) continue;
                    if ($amountFilter['type'] === 'lt' && $total >= $amountFilter['value']) continue;
                }
                
                $customerName = $i->customer ? $i->customer->name : '';
                $results->push([
                    'type' => 'Invoice',
                    'icon' => 'ti ti-file-invoice',
                    'label' => 'Invoice ' . $user->invoiceNumberFormat($i->invoice_id),
                    'sub_label' => $customerName . ' | ' . $user->priceFormat($total),
                    'url' => route('invoice.show', Crypt::encrypt($i->id)),
                ]);
            }

            // Bills with amount/date filters
            $billQuery = Bill::where('created_by', $user->creatorId());
            if ($yearMatch) {
                $billQuery->whereYear('bill_date', $yearMatch);
            }
            if ($dateFilter) {
                $billQuery->whereBetween('bill_date', [$dateFilter['start'], $dateFilter['end']]);
            }
            
            // If searching for bills/transactions or has date/amount filter
            $searchingBills = str_contains($lowerQuery, 'bill') || str_contains($lowerQuery, 'transaction');
            if ($searchingBills || $amountFilter || $hasDateFilter) {
                $bills = $billQuery->orderBy('bill_date', 'desc')->take(5)->get();
                
                foreach ($bills as $b) {
                    $total = $b->getTotal();
                    
                    // Apply amount filter
                    if ($amountFilter) {
                        if ($amountFilter['type'] === 'gt' && $total <= $amountFilter['value']) continue;
                        if ($amountFilter['type'] === 'lt' && $total >= $amountFilter['value']) continue;
                    }
                    
                    $vendorName = $b->vender ? $b->vender->name : 'Unknown Vendor';
                    $results->push([
                        'type' => 'Bill',
                        'icon' => 'ti ti-receipt',
                        'label' => 'Bill #' . $b->bill_id . ' - ' . $vendorName,
                        'sub_label' => Carbon::parse($b->bill_date)->format('m/d/Y') . ' | ' . $user->priceFormat($total),
                        'url' => route('bill.index'),
                    ]);
                }
            }

            // Products
            try {
                $products = ProductService::search($query)->where('created_by', $user->creatorId())->take(3)->get();
                foreach ($products as $p) {
                    $results->push([
                        'type' => 'Product',
                        'icon' => 'ti ti-box',
                        'label' => $p->name,
                        'sub_label' => 'SKU: ' . ($p->sku ?? 'N/A'),
                        'url' => route('productservice.index'),
                    ]);
                }
            } catch (\Throwable $e) {
                $products = ProductService::where('created_by', $user->creatorId())
                    ->where('name', 'LIKE', "%$query%")
                    ->take(3)->get();
                foreach ($products as $p) {
                    $results->push([
                        'type' => 'Product',
                        'icon' => 'ti ti-box',
                        'label' => $p->name,
                        'sub_label' => 'SKU: ' . ($p->sku ?? 'N/A'),
                        'url' => route('productservice.index'),
                    ]);
                }
            }
        }

        // Limit and return
        $results = $results->unique('label')->take(12)->values();

        return response()->json($results);
    }

    /**
     * Handle navigation intent
     */
    protected function handleNavigation(string $target): ?array
    {
        $routes = [
            'profile' => ['route' => 'profile', 'label' => 'My Profile'],
            'settings' => ['route' => 'settings', 'label' => 'Settings'],
            'dashboard' => ['route' => 'home', 'label' => 'Dashboard'],
            'invoices' => ['route' => 'invoice.index', 'label' => 'All Invoices'],
            'bills' => ['route' => 'bill.index', 'label' => 'All Bills'],
            'customers' => ['route' => 'customer.index', 'label' => 'All Customers'],
            'vendors' => ['route' => 'vender.index', 'label' => 'All Vendors'],
        ];

        if (isset($routes[$target]) && Route::has($routes[$target]['route'])) {
            return [
                'type' => 'Action',
                'icon' => 'ti ti-bolt',
                'label' => $routes[$target]['label'],
                'sub_label' => 'Quick Navigation',
                'url' => route($routes[$target]['route']),
            ];
        }

        return null;
    }

    /**
     * Execute filtered query based on AI intent
     */
    protected function executeFilteredQuery(array $intent): array
    {
        $results = [];
        $user = Auth::user();
        $entity = $intent['entity'] ?? 'invoice';
        $filters = $intent['filters'] ?? [];
        $limit = $intent['limit'] ?? 5;
        $orderBy = $intent['order_by'] ?? 'created_at';
        $orderDir = $intent['order_dir'] ?? 'desc';

        try {
            if ($entity === 'invoice') {
                $query = Invoice::where('created_by', $user->creatorId());
                
                // Apply status filter
                if (!empty($filters['status'])) {
                    if ($filters['status'] === 'unpaid') {
                        $query->whereIn('status', [0, 1, 2]);
                    } elseif ($filters['status'] === 'paid') {
                        $query->where('status', 4);
                    }
                }

                // Apply date filter
                if (!empty($filters['date_range'])) {
                    $this->applyDateFilter($query, 'issue_date', $filters['date_range']);
                }

                $invoices = $query->orderBy($orderBy === 'amount' ? 'total' : 'created_at', $orderDir)
                    ->take($limit)->get();

                foreach ($invoices as $i) {
                    $customerName = $i->customer ? $i->customer->name : '';
                    $results[] = [
                        'type' => 'Invoice',
                        'icon' => 'ti ti-file-invoice',
                        'label' => 'Invoice ' . $user->invoiceNumberFormat($i->invoice_id),
                        'sub_label' => $customerName . ' | ' . $user->priceFormat($i->getTotal()),
                        'url' => route('invoice.show', Crypt::encrypt($i->id)),
                    ];
                }
            }

            if ($entity === 'customer') {
                $query = Customer::where('created_by', $user->creatorId());
                
                $customers = $query->orderBy($orderBy === 'name' ? 'name' : 'created_at', $orderDir)
                    ->take($limit)->get();

                foreach ($customers as $c) {
                    $results[] = [
                        'type' => 'Customer',
                        'icon' => 'ti ti-user',
                        'label' => $c->name,
                        'sub_label' => $c->email ?? 'Customer',
                        'url' => route('customer.show', Crypt::encrypt($c->id)),
                    ];
                }
            }

        } catch (\Throwable $e) {
            \Log::error('Filtered Query Error: ' . $e->getMessage());
        }

        return $results;
    }

    /**
     * Apply date filter to query
     */
    protected function applyDateFilter($query, string $field, string $range)
    {
        switch ($range) {
            case 'today':
                $query->whereDate($field, now()->toDateString());
                break;
            case 'this_week':
                $query->whereBetween($field, [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'this_month':
                $query->whereMonth($field, now()->month)->whereYear($field, now()->year);
                break;
            case 'last_month':
                $query->whereMonth($field, now()->subMonth()->month)->whereYear($field, now()->subMonth()->year);
                break;
            case 'this_year':
                $query->whereYear($field, now()->year);
                break;
            default:
                if (preg_match('/^\d{4}$/', $range)) {
                    $query->whereYear($field, $range);
                }
        }
    }
}
