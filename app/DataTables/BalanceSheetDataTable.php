<?php

namespace App\DataTables;

use App\Models\ChartOfAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class BalanceSheetDataTable extends DataTable
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
            ? Carbon::parse(request('endDate'))->endOfDay()->format('Y-m-d')
            : Carbon::now()->endOfDay()->format('Y-m-d');

        $this->companyId = \Auth::user()->type === 'company' ? \Auth::user()->creatorId() : \Auth::user()->ownedId();
        $this->owner = \Auth::user()->type === 'company' ? 'created_by' : 'owned_by';
        $this->accountingMethod = request('accounting_method', 'accrual'); // default accrual
    }

    public function dataTable($query)
    {
        // dd(request()->all());
        return datatables()
            ->collection($query)
            ->addColumn('DT_RowClass', function ($row) {
                $classes = [];

                if (!empty($row->is_section_header)) {
                    $classes[] = 'section-header-row';
                    $classes[] = 'parent-row';
                } elseif (!empty($row->is_subtotal)) {
                    $classes[] = 'subtotal-row';
                    $classes[] = 'child-row';
                } elseif (!empty($row->is_total)) {
                    $classes[] = 'total-row';
                } elseif (!empty($row->is_child)) {
                    $classes[] = 'account-detail';
                    $classes[] = 'child-row';
                }

                return implode(' ', $classes);
            })
            ->addColumn('DT_RowData', function ($row) {
                $data = [];

                if (!empty($row->parent_id)) {
                    $data['parent'] = $row->parent_id;
                }

                if (!empty($row->has_children)) {
                    $data['has-children'] = 'true';
                }

                $data['row-id'] = $row->id ?? 'row-' . uniqid();

                return $data;
            })
            ->addColumn('account_name', function ($row) {
                $indent = $this->getIndentation($row);

                // Section header with toggle functionality
                if ($row->is_section_header) {
                    $sectionTotal = isset($row->section_total) ? number_format($row->section_total, 2) : '0.00';

                    return $indent . '
                        <div class="toggle-section" data-section="' . $row->id . '">
                            <i class="toggle-chevron">▼</i>
                            <strong class="section-header">' . e($row->name) . '</strong>
                            <span class="section-total-amount" data-group="' . $row->id . '"> :  ' . $sectionTotal . '</span>
                        </div>';
                }

                // Total row
                if ($row->is_total) {
                    return '<strong class="total-label">' . e($row->name) . '</strong>';
                }

                // Subtotal row
                if ($row->is_subtotal) {
                    return $indent . '<strong class="subtotal-label">' . e($row->name) . '</strong>';
                }

                // Child account row
                if ($row->is_child) {
                    return $indent . '<span class="account-name">' . e($row->name) . '</span>';
                }

                // Empty row
                return '';
            })
            ->addColumn('amount', function ($row) {
                if ($row->is_section_header) {
                    // Don't show amount in header
                    return '&nbsp;';
                }

                if ($row->is_total) {
                    $amount = isset($row->net) ? $row->net : 0;
                    return '<strong class="total-amount">' . number_format($amount, 2) . '</strong>';
                }

                if ($row->is_subtotal) {
                    $amount = isset($row->net) ? $row->net : 0;
                    return '<strong class="subtotal-amount">' . number_format($amount, 2) . '</strong>';
                }

                if ($row->is_child && isset($row->amount) && $row->amount != 0) {
                    return '<span class="amount-cell">' . number_format($row->amount, 2) . '</span>';
                }


                // Default → keep column aligned
                return '&nbsp;';

            })

            ->rawColumns(['account_name', 'amount']);
    }

    private function getIndentation($row)
    {
        if (!empty($row->is_section_header) || !empty($row->is_total)) {
            return '';
        } elseif (!empty($row->is_subtotal)) {
            return '<span class="indent-spacer"></span>';
        } elseif (!empty($row->is_child)) {
            return '<span class="indent-spacer"></span><span class="indent-spacer"></span>';
        }

        return '';
    }


    /*public function query()
    {
        $accounts = ChartOfAccount::where('chart_of_accounts.created_by', $this->companyId)
            ->leftJoin('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
            ->leftJoin('journal_items', 'chart_of_accounts.id', '=', 'journal_items.account')
            ->leftJoin('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->where('journal_entries.date', '<=', $this->asOfDate)
            ->select([
                'chart_of_accounts.id',
                'chart_of_accounts.name',
                'chart_of_account_types.name as account_type',
                DB::raw('COALESCE(SUM(journal_items.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(journal_items.credit), 0) as total_credit'),
            ])
            ->whereIn('chart_of_account_types.name', ['Assets', 'Liabilities', 'Equity'])
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.name', 'chart_of_account_types.name')
            ->orderBy('chart_of_account_types.name')
            ->orderBy('chart_of_accounts.name')
            ->get();

        return $this->buildHierarchicalBalanceSheet($accounts);
    }*/


    public function query()
    {
        // Base query (chart_of_accounts -> journal_items -> journal_entries)
        $query = ChartOfAccount::where('chart_of_accounts.created_by', $this->companyId)
            ->leftJoin('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
            ->leftJoin('journal_items', 'chart_of_accounts.id', '=', 'journal_items.account')
            ->leftJoin('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->where('journal_entries.date', '<=', $this->asOfDate);

        // cash mode: limit journal_entries to payment vouchers OR include direct cash/bank account lines
        $cashSubTypes = ['bank', 'cash', 'Bank', 'Cash']; // tweak to match your data
        if ($this->accountingMethod == 'cash') {
            $invoicePaymentVouchers = DB::table('invoice_payments')
                ->where('date', '<=', $this->asOfDate)
                ->whereNotNull('voucher_id')
                ->pluck('voucher_id')
                ->toArray();

            $billPaymentVouchers = DB::table('bill_payments')
                ->where('date', '<=', $this->asOfDate)
                ->whereNotNull('voucher_id')
                ->pluck('voucher_id')
                ->toArray();

            $allPaymentVouchers = array_values(array_unique(array_merge($invoicePaymentVouchers, $billPaymentVouchers)));

            $query->where(function ($q) use ($allPaymentVouchers, $cashSubTypes) {
                if (!empty($allPaymentVouchers)) {
                    $q->whereIn('journal_entries.id', $allPaymentVouchers)
                        ->orWhereIn('chart_of_accounts.sub_type', $cashSubTypes);
                } else {
                    $q->whereIn('chart_of_accounts.sub_type', $cashSubTypes);
                }
            });
        }

        // include sub_type so we can filter AR/AP after grouping
        $accounts = $query->select([
            'chart_of_accounts.id',
            'chart_of_accounts.name',
            'chart_of_accounts.sub_type',
            'chart_of_account_types.name as account_type',
            DB::raw('COALESCE(SUM(journal_items.debit), 0) as total_debit'),
            DB::raw('COALESCE(SUM(journal_items.credit), 0) as total_credit'),
        ])
            ->whereIn('chart_of_account_types.name', ['Assets', 'Liabilities', 'Equity'])
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.name', 'chart_of_accounts.sub_type', 'chart_of_account_types.name')
            ->orderBy('chart_of_account_types.name')
            ->orderBy('chart_of_accounts.name')
            ->get();

        // --- Post-query filtering for cash basis: remove AR/AP and non-cash assets ---
        if ($this->accountingMethod == 'cash') {
            // Normalize cash subtypes for comparison
            $cashSubTypesNorm = array_map(fn($s) => strtolower($s), $cashSubTypes);

            $accounts = $accounts->filter(function ($acc) use ($cashSubTypesNorm) {
                $acctType = $acc->account_type ?? '';
                $subType = strtolower($acc->sub_type ?? '');
                $name = strtolower($acc->name ?? '');

                // 1) Assets: only keep explicit cash/bank subtypes
                if ($acctType === 'Assets') {
                    return in_array($subType, $cashSubTypesNorm);
                }

                // 2) Liabilities: remove Accounts Payable-like accounts (we don't want AP on cash BS)
                //    - remove if name contains 'payable' or 'accounts payable'
                if ($acctType === 'Liabilities') {
                    if (str_contains($name, 'payable') || str_contains($subType, 'payable')) {
                        return false;
                    }
                    // keep other liabilities (if any) — adjust logic if you want to remove all liabilities on cash basis
                    return true;
                }

                // 3) Equity: keep (equity = retained earnings from cash P&L)
                if ($acctType === 'Equity') {
                    return true;
                }

                // default: exclude
                return false;
            })->values(); // reindex
        }

        return $this->buildHierarchicalBalanceSheet($accounts);
    }
    private function buildHierarchicalBalanceSheet($accounts)
    {
        $report = collect();

        $emptyRow = function ($name = '', $amount = 0, $net = 0, $flags = []) {
            return (object) array_merge([
                'id' => 'row-' . uniqid(),
                'parent_id' => null,
                'name' => $name,
                'amount' => $amount,
                'net' => $net,
                'is_section_header' => false,
                'is_subtotal' => false,
                'is_total' => false,
                'is_child' => false,
                'has_children' => false,
            ], $flags);
        };

        // ---------- Assets Section ----------
        $assetAccounts = $accounts->where('account_type', 'Assets')->map(function ($acc) {
            $amount = $acc->total_debit - $acc->total_credit;
            return (object) [
                'id' => 'asset-acc-' . $acc->id,
                'parent_id' => 'assets-section',
                'name' => $acc->name,
                'amount' => $amount,
                'net' => $amount,
                'is_child' => true,
                'is_section_header' => false,
                'is_total' => false,
                'is_subtotal' => false,
                'has_children' => false,
            ];
        });
        $totalAssets = $assetAccounts->sum(fn($acc) => $acc->amount);

        // Add Assets section header
        $report->push($emptyRow('ASSETS', 0, 0, [
            'id' => 'assets-section',
            'is_section_header' => true,
            'has_children' => true,
            'section_total' => $totalAssets
        ]));

        // Add asset accounts (initially hidden)
        $report = $report->merge($assetAccounts);

        // Add assets subtotal (initially hidden)
        $report->push($emptyRow('Total Assets', 0, $totalAssets, [
            'id' => 'assets-subtotal',
            'parent_id' => 'assets-section',
            'is_subtotal' => true
        ]));

        // Empty row for spacing
        $report->push($emptyRow(''));

        // ---------- Liabilities Section ----------
        $liabilityAccounts = $accounts->where('account_type', 'Liabilities')->map(function ($acc) {
            $amount = $acc->total_credit - $acc->total_debit;
            return (object) [
                'id' => 'liability-acc-' . $acc->id,
                'parent_id' => 'liabilities-section',
                'name' => $acc->name,
                'amount' => $amount,
                'net' => $amount,
                'is_child' => true,
                'is_section_header' => false,
                'is_total' => false,
                'is_subtotal' => false,
                'has_children' => false,
            ];
        });
        $totalLiabilities = $liabilityAccounts->sum(fn($acc) => $acc->amount);

        // Add Liabilities section header
        $report->push($emptyRow('LIABILITIES', 0, 0, [
            'id' => 'liabilities-section',
            'is_section_header' => true,
            'has_children' => true,
            'section_total' => $totalLiabilities
        ]));

        // Add liability accounts (initially hidden)
        $report = $report->merge($liabilityAccounts);

        // Add liabilities subtotal (initially hidden)
        $report->push($emptyRow('Total Liabilities', 0, $totalLiabilities, [
            'id' => 'liabilities-subtotal',
            'parent_id' => 'liabilities-section',
            'is_subtotal' => true
        ]));

        // Empty row for spacing
        $report->push($emptyRow(''));

        // ---------- Equity Section ----------
        $equityAccounts = $accounts->where('account_type', 'Equity')->map(function ($acc) {
            $amount = $acc->total_credit - $acc->total_debit;
            return (object) [
                'id' => 'equity-acc-' . $acc->id,
                'parent_id' => 'equity-section',
                'name' => $acc->name,
                'amount' => $amount,
                'net' => $amount,
                'is_child' => true,
                'is_section_header' => false,
                'is_total' => false,
                'is_subtotal' => false,
                'has_children' => false,
            ];
        });
        
        // ---------- Net Profit/Loss ----------
        $netProfit = $this->calculateNetProfit();
        $netpreviousProfit = $this->calculatePerviousNetProfit();

        
        // 🔍 Check for Owner's Equity or Owners Equity
        $ownerEquity = $equityAccounts->first(function ($acc) {
            return in_array(
                strtolower(str_replace("’", "'", $acc->name)), // normalize fancy apostrophe
                ["owner's equity", 'owners equity']
            );
        });

        if ($ownerEquity) {
            // ✅ If found, add net profit/loss into Owner's Equity account
            $ownerEquity->amount += $netpreviousProfit;
            $ownerEquity->net += $netpreviousProfit;
        } else {
            // ⚙️ If not found, append a new "Retained Earnings" row
            $equityAccounts->push($emptyRow('Retained Earnings', 0, $netpreviousProfit, [
                'id' => 'Retained Earnings',
                'parent_id' => 'equity-section',
                'amount' => $netpreviousProfit,
                'net' => $netpreviousProfit,
                'is_child' => true,
                'is_section_header' => false,
                'is_total' => false,
                'is_subtotal' => false,
                'has_children' => false,
            ]));

        }
        // // --- Add accumulated profit/loss row ---
        // $equityAccounts->push($emptyRow('Retained Earnings', 0, $netpreviousProfit, [
        //     'id' => 'Retained Earnings',
        //     'parent_id' => 'equity-section',
        //     'amount' => $netpreviousProfit,
        //     'net' => $netpreviousProfit,
        //     'is_child' => true,
        //     'is_section_header' => false,
        //     'is_total' => false,
        //     'is_subtotal' => false,
        //     'has_children' => false,
        // ]));

        $equityAccounts->push($emptyRow('Net Income', 0, $netProfit, [
            'id' => 'net-income',
            'parent_id' => 'equity-section',
            'amount' => $netProfit,
            'net' => $netProfit,
            'is_child' => true,
            'is_section_header' => false,
            'is_total' => false,
            'is_subtotal' => false,
            'has_children' => false,
        ]));

        $totalEquity = $equityAccounts->sum(fn($acc) => $acc->amount);
        // $totalEquity += $netProfit + $netpreviousProfit;

        // Add Equity section header
        $report->push($emptyRow('EQUITY', 0, 0, [
            'id' => 'equity-section',
            'is_section_header' => true,
            'has_children' => true,
            'section_total' => $totalEquity
        ]));

        // Add equity accounts (initially hidden)
        $report = $report->merge($equityAccounts);

        // Add equity subtotal (initially hidden)
        $report->push($emptyRow('Total Equity', 0, $totalEquity, [
            'id' => 'equity-subtotal',
            'parent_id' => 'equity-section',
            'is_subtotal' => true
        ]));

        // Empty row for spacing
        $report->push($emptyRow(''));

        // ---------- Final Total ----------
        $grandTotal = $totalLiabilities + $totalEquity;
        $report->push($emptyRow('TOTAL LIABILITIES & EQUITY', 0, $grandTotal, [
            'id' => 'grand-total',
            'is_total' => true
        ]));

        return $report;
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
                'autoWidth' => false,
                'responsive' => true,
                'createdRow' => "function(row, data, dataIndex) {
                    // Add CSS classes
                    if (data.DT_RowClass) {
                        $(row).addClass(data.DT_RowClass);
                    }
                    
                    // Add data attributes
                    if (data.DT_RowData) {
                        for (let key in data.DT_RowData) {
                            $(row).attr('data-' + key, data.DT_RowData[key]);
                        }
                    }
                    
                    // Add group class for parent-child relationship
                    if (data.DT_RowData && data.DT_RowData.parent) {
                        $(row).addClass('group-' + data.DT_RowData.parent);
                    }
                }",
                'drawCallback' => "function(settings) {
                    // Trigger custom draw event for our JavaScript
                    $('#balance-sheet-table').trigger('table-redrawn');
                }"
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('account_name')
                ->title('Account')
                ->width('70%')
                ->addClass('account-name-col'),
            Column::make('amount')
                ->title('Amount')
                ->width('30%')
                ->addClass('text-right amount-col'),
        ];
    }
}
