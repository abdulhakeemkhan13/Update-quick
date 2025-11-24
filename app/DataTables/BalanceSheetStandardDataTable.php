<?php

namespace App\DataTables;

use App\Models\ChartOfAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Collection;

class BalanceSheetStandardDataTable extends DataTable
{
    protected $startDate;
    protected $asOfDate;
    protected $companyId;
    protected $owner;
    protected $accountingMethod;

    public function __construct()
    {
        parent::__construct();

        $this->startDate = request('startDate')
            ? Carbon::parse(request('startDate'))->startOfDay()
            : Carbon::now()->startOfYear();
        $this->asOfDate = request('endDate')
            ? Carbon::parse(request('endDate'))->endOfDay()
            : Carbon::now()->endOfDay();

        $this->companyId = \Auth::user()->type === 'company' ? \Auth::user()->creatorId() : \Auth::user()->ownedId();
        $this->owner = \Auth::user()->type === 'company' ? 'created_by' : 'owned_by';
        $this->accountingMethod = request('accounting_method', 'accrual'); // default accrual
    }

    public function dataTable($query)
    {
        return datatables()
            ->collection($query)
            ->addColumn('account', function ($row) {
                if ($row->is_section_header ?? false) {
                    return '<strong class="section-header">' . e($row->name) . '</strong>';
                }

                if ($row->is_subtype_header ?? false) {
                    $hasChildren = $row->has_children ?? false;
                    $subtypeId = $row->subtype_id ?? 'subtype_' . str_replace(' ', '_', strtolower($row->name));
                    $chevron = '';

                    if ($hasChildren) {
                        $chevron = '<i class=" chevron-icon" data-parent-type="subtype" data-parent-id="' . $subtypeId . '" style="margin-right: 8px; cursor: pointer;">▼</i>';
                    }

                    $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', (int) ($row->depth ?? 0));
                    return $indent . $chevron . '<strong class="subtotal-label">' . e($row->name) . '</strong>';
                }

                if ($row->is_subtotal ?? false) {
                    $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', (int) ($row->depth ?? 0));
                    return $indent . '<strong class="subtotal-label">' . e($row->name) . '</strong>';
                }

                if ($row->is_total ?? false) {
                    return '<strong class="total-label">' . e($row->name) . '</strong>';
                }

                // Individual account rows
                $depth = (int) ($row->depth ?? 0);
                $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', max(0, $depth));
                return $indent . e($row->name);
            })
            ->addColumn('amount', function ($row) {
                if ($row->is_section_header ?? false) {
                    return '';
                }

                $amount = (float) ($row->amount ?? 0);

                if ($amount == 0 && !($row->is_subtotal ?? false) && !($row->is_total ?? false)) {
                    return '';
                }

                if ($row->is_subtotal ?? false) {
                    return '<strong class="subtotal-amount">' . number_format($amount, 2) . '</strong>';
                }

                if ($row->is_total ?? false) {
                    return '<strong class="total-amount">' . number_format($amount, 2) . '</strong>';
                }

                return '<span class="amount-cell">' . number_format($amount, 2) . '</span>';
            })
            ->addColumn('DT_RowClass', function ($row) {
                $classes = [];

                if ($row->is_section_header ?? false) {
                    $classes[] = 'section-header-row';
                }

                if ($row->is_subtype_header ?? false) {
                    $classes[] = 'subtype-header-row';
                    $subtypeId = $row->subtype_id ?? 'subtype_' . str_replace(' ', '_', strtolower($row->name));
                    $classes[] = 'parent-subtype-' . $subtypeId;
                }

                if ($row->is_subtotal ?? false) {
                    $classes[] = 'subtotal-row';
                    if ($row->parent_subtype_id ?? false) {
                        $classes[] = 'child-of-subtype-' . $row->parent_subtype_id;
                    }
                }

                if ($row->is_total ?? false) {
                    $classes[] = 'total-row';
                }

                if (($row->depth ?? 0) > 1 && !($row->is_subtotal ?? false) && !($row->is_total ?? false) && !($row->is_section_header ?? false) && !($row->is_subtype_header ?? false)) {
                    $classes[] = 'child-row';
                    if ($row->parent_subtype_id ?? false) {
                        $classes[] = 'child-of-subtype-' . $row->parent_subtype_id;
                    }
                }

                return implode(' ', $classes);
            })
            ->addColumn('DT_RowData', function ($row) {
                $data = [];
                if ($row->subtype_id ?? false) {
                    $data['subtype-id'] = $row->subtype_id;
                }
                return $data;
            })
            ->rawColumns(['account', 'amount']);
    }

    public function query()
    {
        $cashSubTypes = ['bank', 'cash'];
        $allPaymentVouchers = [];

        if ($this->accountingMethod == 'cash') {
            $invoicePaymentVouchers = DB::table('invoice_payments')
                ->where('date', '<=', $this->asOfDate->format('Y-m-d 23:59:59'))
                ->whereNotNull('voucher_id')
                ->pluck('voucher_id')
                ->toArray();

            $billPaymentVouchers = DB::table('bill_payments')
                ->where('date', '<=', $this->asOfDate->format('Y-m-d 23:59:59'))
                ->whereNotNull('voucher_id')
                ->pluck('voucher_id')
                ->toArray();

            $allPaymentVouchers = array_values(array_unique(array_merge($invoicePaymentVouchers, $billPaymentVouchers)));
        }

        $query = ChartOfAccount::where('chart_of_accounts.created_by', $this->companyId)
            ->leftJoin('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
            ->leftJoin('chart_of_account_sub_types', 'chart_of_accounts.sub_type', '=', 'chart_of_account_sub_types.id') // ✅ added correct join
            ->leftJoin('journal_items', 'chart_of_accounts.id', '=', 'journal_items.account')
            ->leftJoin('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->where('journal_entries.date', '<=', $this->asOfDate->format('Y-m-d 23:59:59'));

        if ($this->accountingMethod == 'cash') {
            $cashSqlVariants = array_merge($cashSubTypes, array_map('ucfirst', $cashSubTypes));

            $query->where(function ($q) use ($allPaymentVouchers, $cashSqlVariants) {
                if (!empty($allPaymentVouchers)) {
                    $q->whereIn('journal_entries.id', $allPaymentVouchers)
                        ->orWhereIn('chart_of_accounts.sub_type', $cashSqlVariants);
                } else {
                    $q->whereIn('chart_of_accounts.sub_type', $cashSqlVariants);
                }
            });
        }

        $accounts = $query->select([
            'chart_of_accounts.id',
            'chart_of_accounts.name',
            'chart_of_accounts.parent',
            'chart_of_accounts.sub_type',
            'chart_of_account_sub_types.name as subtype_name', // ✅ corrected alias
            'chart_of_account_types.name as account_type',
            DB::raw('COALESCE(SUM(journal_items.debit), 0) as total_debit'),
            DB::raw('COALESCE(SUM(journal_items.credit), 0) as total_credit'),
        ])
            ->whereIn('chart_of_account_types.name', ['Assets', 'Liabilities', 'Equity'])
            ->groupBy(
                'chart_of_accounts.id',
                'chart_of_accounts.name',
                'chart_of_accounts.parent',
                'chart_of_accounts.sub_type',
                'chart_of_account_sub_types.name',
                'chart_of_account_types.name'
            )
            ->orderBy('chart_of_account_types.name')
            ->orderBy('chart_of_accounts.name')
            ->get();

        $accounts = $accounts->map(function ($acc) {
            if (($acc->account_type ?? '') === 'Assets') {
                $acc->balance = (float)$acc->total_debit - (float)$acc->total_credit;
            } else {
                $acc->balance = (float)$acc->total_credit - (float)$acc->total_debit;
            }
            return $acc;
        });

        if ($this->accountingMethod == 'cash') {
            $cashSubTypesNorm = array_map('strtolower', $cashSubTypes);

            $accounts = $accounts->filter(function ($acc) use ($cashSubTypesNorm) {
                $acctType = $acc->account_type ?? '';
                $subType = strtolower($acc->sub_type ?? '');
                $name = strtolower($acc->name ?? '');

                if ($acctType === 'Assets') {
                    return in_array($subType, $cashSubTypesNorm);
                }

                if ($acctType === 'Liabilities') {
                    if (stripos($name, 'payable') !== false || stripos($subType, 'payable') !== false) {
                        return false;
                    }
                    return true;
                }

                if ($acctType === 'Equity') {
                    return true;
                }

                return false;
            })->values();
        }

        return $this->buildHierarchicalBalanceSheet($accounts, $allPaymentVouchers, $cashSubTypes);
    }


    /**
     * Build hierarchical report rows (sections, subtypes, accounts, subtotals, totals)
     *
     * @param Collection $accounts  collection of accounts (with id, name, parent, sub_type, account_type, balance)
     * @param array $allPaymentVouchers (optional) used for cash-based P&L filtering
     * @param array $cashSubTypes (optional) canonical cash subtypes (lowercase)
     * @return Collection
     */
/**
 * Build hierarchical report rows (sections, subtypes, accounts, subtotals, totals)
 *
 * @param Collection $accounts  collection of accounts (with id, name, parent, sub_type, account_type, balance)
 * @param array $allPaymentVouchers (optional) used for cash-based P&L filtering
 * @param array $cashSubTypes (optional) canonical cash subtypes (lowercase)
 * @return Collection
 */
private function buildHierarchicalBalanceSheet(Collection $accounts, array $allPaymentVouchers = [], array $cashSubTypes = ['bank','cash'])
{
    $report = collect();

    // Group by account_type
    $types = $accounts->groupBy('account_type');

    // Calculate initial totals
    $totalAssets = $types->get('Assets', collect())->sum('balance');
    $totalLiabilities = $types->get('Liabilities', collect())->sum('balance');

    // Calculate net profit components early
    $netProfit = $this->calculateNetProfit();
    $previousProfit = $this->calculatePerviousNetProfit();

    // Calculate equity from accounts
    $totalEquityFromAccounts = $types->get('Equity', collect())->sum('balance');
    $totalEquity = $totalEquityFromAccounts + $netProfit + $previousProfit;

    // ---------- ASSETS & LIABILITIES ----------
    foreach (['Assets', 'Liabilities'] as $typeName) {
        $typeAccounts = $types->get($typeName, collect());
        if ($typeAccounts->isEmpty()) continue;

        $report->push((object)[
            'name' => strtoupper($typeName),
            'depth' => 0,
            'is_section_header' => true,
        ]);

        $subTypes = $typeAccounts->groupBy('subtype_name');

        foreach ($subTypes as $subTypeName => $subTypeAccounts) {
            $subtypeId = 'subtype_' . str_replace(' ', '_', strtolower($subTypeName ?: 'uncategorized'));

            $report->push((object)[
                'name' => $subTypeName ?: 'Uncategorized',
                'depth' => 1,
                'is_subtype_header' => true,
                'subtype_id' => $subtypeId,
                'has_children' => $subTypeAccounts->isNotEmpty(),
            ]);

            $roots = $subTypeAccounts->filter(fn($acc) =>
                empty($acc->parent) || !$subTypeAccounts->contains('id', $acc->parent)
            );

            foreach ($roots as $root) {
                $accountRows = $this->buildAccountTree($root, $subTypeAccounts, 2, $subtypeId);
                $report = $report->merge($accountRows);
            }

            $report->push((object)[
                'name' => "Total " . ($subTypeName ?: 'Uncategorized'),
                'amount' => $subTypeAccounts->sum('balance'),
                'depth' => 1,
                'is_subtotal' => true,
                'parent_subtype_id' => $subtypeId,
            ]);
        }

        $report->push((object)[
            'name' => "Total " . $typeName,
            'amount' => $typeAccounts->sum('balance'),
            'depth' => 0,
            'is_total' => true,
        ]);

        $report->push((object)['name' => '', 'amount' => null]); // spacing
    }

    // ---------- EQUITY ----------
    $typeAccounts = $types->get('Equity', collect());

    $report->push((object)[
        'name' => 'EQUITY',
        'depth' => 0,
        'is_section_header' => true,
    ]);

    $subTypes = $typeAccounts->groupBy('subtype_name');
    $ownerEquityFound = false;
    $ownerEquityAccount = null;

    // Check both subtypes and account names
    foreach ($typeAccounts as $acc) {
        
        $normalized = strtolower(str_replace('’', "'", trim($acc->name))); // normalize fancy apostrophe

        if (in_array($normalized, ["owner's equity", 'owners equity', 'owner equity'])) {
            $ownerEquityFound = true;
            $ownerEquityAccount = $acc;
            break;
        }
    }

    // Process all subtypes under Equity
    foreach ($subTypes as $subTypeName => $subTypeAccounts) {
        $subtypeId = 'subtype_' . str_replace(' ', '_', strtolower($subTypeName ?: 'uncategorized'));

        $report->push((object)[
            'name' => $subTypeName ?: 'Uncategorized',
            'depth' => 1,
            'is_subtype_header' => true,
            'subtype_id' => $subtypeId,
            'has_children' => $subTypeAccounts->isNotEmpty(),
        ]);

        $roots = $subTypeAccounts->filter(fn($acc) =>
            empty($acc->parent) || !$subTypeAccounts->contains('id', $acc->parent)
        );

        foreach ($roots as $root) {
            $accountRows = $this->buildAccountTree($root, $subTypeAccounts, 2, $subtypeId);
            $report = $report->merge($accountRows);

            // Inject retained earnings and net income directly under Owner’s Equity account
            if ($ownerEquityFound && $ownerEquityAccount && $root->id == $ownerEquityAccount->id) {
                    $totalEquityAdjustment = $previousProfit + $netProfit;
                    $ownerEquityRow = $report->first(function ($row) {
                        return isset($row->name) && stripos($row->name, "owners equity") !== false;
                    });

                    if ($ownerEquityRow) {
                        // ✅ Update its amount by adding current + previous profit
                        $ownerEquityRow->amount = ($ownerEquityRow->amount ?? 0) + $previousProfit;
                    } else {
                        // If not found, you can still add it as a fallback
                        $report->push((object)[
                            'name' => 'Retained Earnings',
                            'amount' => $previousProfit,
                            'depth' => 3,
                            'parent_id' => $ownerEquityAccount->id,
                        ]);
                    }
               

                $report->push((object)[
                    'name' => 'Net Income',
                    'amount' => $netProfit,
                    'depth' => 3,
                    'parent_id' => $ownerEquityAccount->id,
                ]);

                // 3. Add the amounts to Owner’s Equity total balance
              if ($ownerEquityFound && $ownerEquityAccount && $root->id == $ownerEquityAccount->id) {
                    $totalEquityAdjustment = $previousProfit + $netProfit;

                    // Directly modify the Owner’s Equity account balance in $accounts
                    $ownerEquityAccount->balance += $totalEquityAdjustment;

                    // Optional: keep $accountBalances consistent if used elsewhere
                    if (isset($accountBalances[$ownerEquityAccount->id])) {
                        $accountBalances[$ownerEquityAccount->id] += $totalEquityAdjustment;
                    } else {
                        $accountBalances[$ownerEquityAccount->id] = $totalEquityAdjustment;
                    }
                }
            }
        }

        $report->push((object)[
            'name' => "Total " . ($subTypeName ?: 'Uncategorized'),
            'amount' => $subTypeAccounts->sum('balance'),
            'depth' => 1,
            'is_subtotal' => true,
            'parent_subtype_id' => $subtypeId,
        ]);
    }

    // If no Owner’s Equity account found, create Retained Earnings section manually
    if (!$ownerEquityFound) {
        $subtypeId = 'subtype_retained_earnings';

        $report->push((object)[
            'name' => 'Retained Earnings',
            'depth' => 1,
            'is_subtype_header' => true,
            'subtype_id' => $subtypeId,
            'has_children' => true,
        ]);

        $report->push((object)[
            'name' => 'Retained Earnings',
            'amount' => $previousProfit,
            'depth' => 2,
            'parent_subtype_id' => $subtypeId,
        ]);

        $report->push((object)[
            'name' => 'Net Income',
            'amount' => $netProfit,
            'depth' => 2,
            'parent_subtype_id' => $subtypeId,
        ]);

        $report->push((object)[
            'name' => 'Total Retained Earnings',
            'amount' => $previousProfit + $netProfit,
            'depth' => 1,
            'is_subtotal' => true,
            'parent_subtype_id' => $subtypeId,
        ]);
    }

    // Total Equity (final)
    $report->push((object)[
        'name' => 'Total Equity',
        'amount' => $totalEquity,
        'depth' => 0,
        'is_total' => true,
    ]);

    $report->push((object)['name' => '', 'amount' => null]); // spacing

    // Final TOTAL LIABILITIES & EQUITY
    $report->push((object)[
        'name' => 'TOTAL LIABILITIES & EQUITY',
        'amount' => $totalLiabilities + $totalEquity,
        'depth' => 0,
        'is_total' => true,
    ]);

    return $report;
}

    /**
     * Recursive helper to build parent-child hierarchy for accounts
     *
     * @param object $account
     * @param Collection $allAccounts
     * @param int $depth
     * @param string|null $subtypeId
     * @return Collection
     */
    private function buildAccountTree($account, Collection $allAccounts, $depth = 2, $subtypeId = null)
    {
        $rows = collect();

        // Push parent account row
        $rows->push((object)[
            'name' => $account->name,
            'amount' => (float) ($account->balance ?? 0),
            'depth' => $depth,
            'parent_subtype_id' => $subtypeId,
        ]);

        // children (accounts where parent == this account id)
        $children = $allAccounts->filter(function ($a) use ($account) {
            return (string) ($a->parent ?? '') === (string) $account->id;
        });

        foreach ($children as $child) {
            $rows = $rows->merge($this->buildAccountTree($child, $allAccounts, $depth + 1, $subtypeId));
        }

        return $rows;
    }

    private function calculateNetProfit()
    {
        if ($this->accountingMethod !== 'cash') {
            // ---- Accrual ----
            $income = DB::table('journal_items')
                ->join('chart_of_accounts', 'journal_items.account', '=', 'chart_of_accounts.id')
                ->join('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
                ->join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
                ->where("journal_entries.{$this->owner}", $this->companyId)
                ->whereBetween('journal_entries.date', [$this->startDate, $this->asOfDate])
                ->where('chart_of_account_types.name', 'Income')
                ->selectRaw('COALESCE(SUM(journal_items.credit - journal_items.debit), 0) as total')
                ->value('total');
            $expensesAndCogs = DB::table('journal_items')
                ->join('chart_of_accounts', 'journal_items.account', '=', 'chart_of_accounts.id')
                ->join('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
                ->join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
                ->where("journal_entries.{$this->owner}", $this->companyId)
                ->whereBetween('journal_entries.date', [$this->startDate, $this->asOfDate])
                ->whereIn('chart_of_account_types.name', ['Expenses', 'Costs of Goods Sold'])
                ->selectRaw('COALESCE(SUM(journal_items.debit - journal_items.credit), 0) as total')
                ->value('total');
            return $income - $expensesAndCogs;
        }

        // ---- Cash-basis ----
        // 1. Get total payments grouped by invoice (up to cutoff date)
        $invoicePayments = DB::table('invoice_payments')
            ->select('invoice_id', DB::raw('SUM(amount) as paid_amount'))
            ->whereBetween('date', [$this->startDate, $this->asOfDate])
            ->groupBy('invoice_id');

        // 2. Join those payments to the original invoice JEs to get revenue lines
        $invoiceIncome = DB::table('journal_items')
            ->join('chart_of_accounts', 'journal_items.account', '=', 'chart_of_accounts.id')
            ->join('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
            ->join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->joinSub($invoicePayments, 'ip', function ($join) {
                $join->on('journal_entries.reference_id', '=', 'ip.invoice_id');
            })
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->whereIn('chart_of_account_types.name', ['Income'])
            ->selectRaw('SUM( LEAST(ip.paid_amount, journal_items.credit - journal_items.debit) ) as income')
            ->value('income') ?? 0;
        // 3. Do the same for bills/expenses
        $billPayments = DB::table('bill_payments')
            ->select('bill_id', DB::raw('SUM(amount) as paid_amount'))
            ->whereBetween('date', [$this->startDate, $this->asOfDate])
            ->groupBy('bill_id');

        $billExpenses = DB::table('journal_items')
            ->join('chart_of_accounts', 'journal_items.account', '=', 'chart_of_accounts.id')
            ->join('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
            ->join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->joinSub($billPayments, 'bp', function ($join) {
                $join->on('journal_entries.reference_id', '=', 'bp.bill_id');
            })
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->whereIn('journal_entries.voucher_type', ['Bill', 'Expense'])
            ->whereIn('chart_of_account_types.name', ['Expenses', 'Costs of Goods Sold'])
            ->selectRaw('SUM( LEAST(bp.paid_amount, journal_items.debit - journal_items.credit) ) as expense')
            ->value('expense') ?? 0;

        return $invoiceIncome - $billExpenses;
    }
    private function calculatePerviousNetProfit()
    {
        if ($this->accountingMethod !== 'cash') {
            // ---- Accrual ----
            $income = DB::table('journal_items')
                ->join('chart_of_accounts', 'journal_items.account', '=', 'chart_of_accounts.id')
                ->join('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
                ->join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
                ->where("journal_entries.{$this->owner}", $this->companyId)
                ->where('journal_entries.date', '<', $this->startDate)
                ->where('chart_of_account_types.name', 'Income')
                ->selectRaw('COALESCE(SUM(journal_items.credit - journal_items.debit), 0) as total')
                ->value('total');
            $expensesAndCogs = DB::table('journal_items')
                ->join('chart_of_accounts', 'journal_items.account', '=', 'chart_of_accounts.id')
                ->join('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
                ->join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
                ->where("journal_entries.{$this->owner}", $this->companyId)
                ->where('journal_entries.date', '<', $this->startDate)
                ->whereIn('chart_of_account_types.name', ['Expenses', 'Costs of Goods Sold'])
                ->selectRaw('COALESCE(SUM(journal_items.debit - journal_items.credit), 0) as total')
                ->value('total');
            return $income - $expensesAndCogs;
        }

        // ---- Cash-basis ----
        // 1. Get total payments grouped by invoice (up to cutoff date)
        $invoicePayments = DB::table('invoice_payments')
            ->select('invoice_id', DB::raw('SUM(amount) as paid_amount'))
            ->where('date', '<', $this->startDate)
            ->groupBy('invoice_id');

        // 2. Join those payments to the original invoice JEs to get revenue lines
        $invoiceIncome = DB::table('journal_items')
            ->join('chart_of_accounts', 'journal_items.account', '=', 'chart_of_accounts.id')
            ->join('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
            ->join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->joinSub($invoicePayments, 'ip', function ($join) {
                $join->on('journal_entries.reference_id', '=', 'ip.invoice_id');
            })
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->whereIn('chart_of_account_types.name', ['Income'])
            ->selectRaw('SUM( LEAST(ip.paid_amount, journal_items.credit - journal_items.debit) ) as income')
            ->value('income') ?? 0;
        // 3. Do the same for bills/expenses
        $billPayments = DB::table('bill_payments')
            ->select('bill_id', DB::raw('SUM(amount) as paid_amount'))
            ->where('date', '<', $this->startDate)
            ->groupBy('bill_id');

        $billExpenses = DB::table('journal_items')
            ->join('chart_of_accounts', 'journal_items.account', '=', 'chart_of_accounts.id')
            ->join('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
            ->join('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->joinSub($billPayments, 'bp', function ($join) {
                $join->on('journal_entries.reference_id', '=', 'bp.bill_id');
            })
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->whereIn('journal_entries.voucher_type', ['Bill', 'Expense'])
            ->whereIn('chart_of_account_types.name', ['Expenses', 'Costs of Goods Sold'])
            ->selectRaw('SUM( LEAST(bp.paid_amount, journal_items.debit - journal_items.credit) ) as expense')
            ->value('expense') ?? 0;

        return $invoiceIncome - $billExpenses;
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('customer-balance-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'scrollY' => '600px',
                'scrollCollapse' => true,
                'createdRow' => "function(row, data, dataIndex) {
                    if (data.DT_RowClass) {
                        $(row).addClass(data.DT_RowClass);
                    }
                    if (data.DT_RowData) {
                        for (let key in data.DT_RowData) {
                            $(row).attr('data-' + key, data.DT_RowData[key]);
                        }
                    }
                }"
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('account')->title('Account')->width('70%'),
            Column::make('amount')->title('TOTAL')->width('30%')->addClass('text-right'),
        ];
    }
}
