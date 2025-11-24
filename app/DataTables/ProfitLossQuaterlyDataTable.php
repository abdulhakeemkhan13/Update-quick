<?php

namespace App\DataTables;

use App\Models\ChartOfAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Http\JsonResponse;

class ProfitLossQuaterlyDataTable extends DataTable
{
    protected $startDate;
    protected $endDate;
    protected $companyId;
    protected $owner;
    protected $quarters = [];

    public function __construct()
    {
        parent::__construct();

        $this->startDate = request('startDate')
            ? Carbon::parse(request('startDate'))->startOfDay()
            : Carbon::now()->startOfYear();

        $this->endDate = request('endDate')
            ? Carbon::parse(request('endDate'))->endOfDay()
            : Carbon::now()->endOfDay();

        $this->companyId = \Auth::user()->type === 'company'
            ? \Auth::user()->creatorId()
            : \Auth::user()->ownedId();

        $this->owner = \Auth::user()->type === 'company' ? 'created_by' : 'owned_by';

        $this->quarters = $this->generateQuarters();
    }

    protected function generateQuarters()
    {
        $quarters = [];
        $current = $this->startDate->copy()->startOfQuarter();
        $end = $this->endDate->copy()->endOfQuarter();

        while ($current <= $end) {
            $quarterEnd = $current->copy()->endOfQuarter();
            if ($quarterEnd > $this->endDate) {
                $quarterEnd = $this->endDate->copy();
            }

            $label = $current->copy()->format('j M Y') . ' - ' . $quarterEnd->format('j M Y');

            $quarters[] = [
                'label' => $label,
                'start' => $current->copy()->format('Y-m-d'),
                'end' => $quarterEnd->format('Y-m-d'),
                'key' => 'q' . $current->quarter . '_' . $current->year
            ];

            $current->addQuarter();
        }

        return $quarters;
    }

    public function ajax(): JsonResponse
    {
        $rows = $this->query();
        return response()->json(['data' => $rows->values()->toArray()]);
    }

    public function dataTable($query)
    {
        return datatables()->collection($query);
    }

    protected function getAccountsForQuarter($quarterStart, $quarterEnd)
    {
        $quarterEnd = Carbon::parse($quarterEnd)->endOfDay();
        $quarterStart = Carbon::parse($quarterStart)->startOfDay();

        return ChartOfAccount::where('chart_of_accounts.created_by', $this->companyId)
            ->leftJoin('chart_of_account_types', 'chart_of_accounts.type', '=', 'chart_of_account_types.id')
            ->leftJoin('chart_of_account_sub_types', 'chart_of_accounts.sub_type', '=', 'chart_of_account_sub_types.id')
            ->leftJoin('journal_items', 'chart_of_accounts.id', '=', 'journal_items.account')
            ->leftJoin('journal_entries', 'journal_items.journal', '=', 'journal_entries.id')
            ->where("journal_entries.{$this->owner}", $this->companyId)
            ->whereBetween('journal_items.created_at', [$quarterStart, $quarterEnd])
            ->select([
                'chart_of_accounts.id',
                'chart_of_accounts.name',
                'chart_of_accounts.code',
                'chart_of_account_types.name as account_type',
                'chart_of_account_sub_types.name as sub_type',
                DB::raw('COALESCE(SUM(journal_items.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(journal_items.credit), 0) as total_credit'),
            ])
            ->whereIn('chart_of_account_types.name', ['Income', 'Expenses', 'Costs of Goods Sold'])
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.name', 'chart_of_accounts.code', 'chart_of_account_types.name')
            ->orderBy('chart_of_account_types.name')
            ->get();
    }

    public function query()
    {
        try {
            $allAccounts = collect();
            $quarterData = [];

            foreach ($this->quarters as $quarter) {
                $accounts = $this->getAccountsForQuarter($quarter['start'], $quarter['end']);
                $quarterData[$quarter['key']] = $accounts->keyBy('id');
                $allAccounts = $allAccounts->merge($accounts)->unique('id');
            }

            $reportRows = collect();

            $initQuarterMap = function () {
                $map = [];
                foreach ($this->quarters as $q) {
                    $map[$q['key']] = 0;
                }
                $map['total'] = 0;
                return $map;
            };

            $buildSection = function ($sectionName, $accountsCollection) use (&$reportRows, $initQuarterMap) {
                $totals = [];
                $grandTotal = 0;

                foreach ($this->quarters as $q) {
                    $totals[$q['key']] = $accountsCollection->sum(function ($acc) use ($q) {
                        return $acc->{$q['key']} ?? 0;
                    });
                    $grandTotal += $totals[$q['key']];
                }

                // header row (NOTE: we intentionally do NOT add group-... in DT_RowClass for this header)
                $header = $initQuarterMap();
                $header = array_merge($header, [
                    'name' => $sectionName,
                    'group_key' => strtolower(str_replace(' ', '_', $sectionName)),
                    'is_section_header' => true,
                    'has_children' => $accountsCollection->count() > 0,
                    'section_total' => $grandTotal, // exposed for front-end collapsed total
                ]);
                foreach ($this->quarters as $q) $header[$q['key']] = $totals[$q['key']] ?? 0;
                $header['total'] = $grandTotal;
                $reportRows->push($header);

                // accounts (children get group class)
                foreach ($accountsCollection as $acc) {
                    $row = $initQuarterMap();
                    $row['name'] = $acc->name ?? '';
                    $row['code'] = $acc->code ?? null;
                    $row['group_key'] = $header['group_key'];
                    $row['is_child'] = true;
                    foreach ($this->quarters as $q) {
                        $row[$q['key']] = $acc->{$q['key']} ?? 0;
                    }
                    $row['total'] = $acc->total ?? 0;
                    $reportRows->push($row);
                }

                // subtotal (belongs to group)
                $subtotal = $initQuarterMap();
                $subtotal = array_merge($subtotal, [
                    'name' => 'Total ' . $sectionName,
                    'group_key' => $header['group_key'],
                    'is_subtotal' => true,
                ]);
                foreach ($this->quarters as $q) $subtotal[$q['key']] = $totals[$q['key']] ?? 0;
                $subtotal['total'] = $grandTotal;
                $reportRows->push($subtotal);

                return [$totals, $grandTotal];
            };

            // Income
            $incomeAccounts = $allAccounts->where('account_type', 'Income')->values()->map(function ($acc) use ($quarterData) {
                $acc->total = 0;
                foreach ($this->quarters as $q) {
                    $qa = $quarterData[$q['key']]->get($acc->id);
                    $amt = $qa ? ($qa->total_credit - $qa->total_debit) : 0;
                    $acc->{$q['key']} = $amt;
                    $acc->total += $amt;
                }
                return $acc;
            });

            [$incomeTotals, $incomeGrandTotal] = $buildSection('Income', $incomeAccounts);

            // COGS
            // $cogsAccounts = $allAccounts->where('account_type', 'Costs of Goods Sold')->values()->map(function ($acc) use ($quarterData) {
            $cogsAccounts = $allAccounts->filter(function ($acc) {
            return $acc->account_type == 'Costs of Goods Sold' || ($acc->sub_type == 'Costs of Goods Sold');
                })->values()->map(function ($acc) use ($quarterData) {
                $acc->total = 0;
                foreach ($this->quarters as $q) {
                    $qa = $quarterData[$q['key']]->get($acc->id);
                    $amt = $qa ? ($qa->total_debit - $qa->total_credit) : 0;
                    $acc->{$q['key']} = $amt;
                    $acc->total += $amt;
                }
                return $acc;
            });

            [$cogsTotals, $cogsGrandTotal] = $buildSection('Costs of Goods Sold', $cogsAccounts);

            // Gross Profit
            $gross = $initQuarterMap();
            $gross['name'] = 'Gross Profit';
            $gross['is_total'] = true;
            foreach ($this->quarters as $q) {
                $gross[$q['key']] = ($incomeTotals[$q['key']] ?? 0) - ($cogsTotals[$q['key']] ?? 0);
            }
            $gross['total'] = ($incomeGrandTotal ?? 0) - ($cogsGrandTotal ?? 0);
            $reportRows->push($gross);

            // Expenses
            // $expenseAccounts = $allAccounts->where('account_type', 'Expenses')->values()->map(function ($acc) use ($quarterData) {
            $expenseAccounts = $allAccounts->filter(function ($acc) {
                return $acc->account_type === 'Expenses'
                    && !in_array($acc->sub_type, ['COGS', 'Costs of Goods Sold'])
                    && $acc->account_type !== 'Costs of Goods Sold';
                })->values()->map(function ($acc) use ($quarterData) {
                $acc->total = 0;
                foreach ($this->quarters as $q) {
                    $qa = $quarterData[$q['key']]->get($acc->id);
                    $amt = $qa ? ($qa->total_debit - $qa->total_credit) : 0;
                    $acc->{$q['key']} = $amt;
                    $acc->total += $amt;
                }
                return $acc;
            });

            [$expenseTotals, $expenseGrandTotal] = $buildSection('Expenses', $expenseAccounts);

            // Net Income
            $net = $initQuarterMap();
            $net['name'] = 'NET INCOME';
            $net['is_total'] = true;
            foreach ($this->quarters as $q) {
                $net[$q['key']] = ($incomeTotals[$q['key']] ?? 0) - ($cogsTotals[$q['key']] ?? 0) - ($expenseTotals[$q['key']] ?? 0);
            }
            $net['total'] = ($incomeGrandTotal ?? 0) - ($cogsGrandTotal ?? 0) - ($expenseGrandTotal ?? 0);
            $reportRows->push($net);

            // FINALIZE: convert to plain arrays, prepare names, display keys and DT_RowClass
            $final = $reportRows->map(function ($row) {
                $row = (array) $row;

                $row['name'] = $row['name'] ?? '';
                $row['code'] = $row['code'] ?? null;
                $row['group_key'] = $row['group_key'] ?? '';
                $row['is_section_header'] = $row['is_section_header'] ?? false;
                $row['is_subtotal'] = $row['is_subtotal'] ?? false;
                $row['is_total'] = $row['is_total'] ?? false;
                $row['is_child'] = $row['is_child'] ?? false;
                $row['has_children'] = $row['has_children'] ?? false;
                $row['section_total'] = $row['section_total'] ?? ($row['total'] ?? 0);

                // Build row classes (do NOT assign group-... to section header)
                $classes = [];
                if ($row['is_section_header']) {
                    $classes[] = 'section-row';
                } else {
                    if ($row['is_subtotal']) $classes[] = 'subtotal-row';
                    if ($row['is_total']) $classes[] = 'total-row';
                    if ($row['is_child']) $classes[] = 'child-row';
                    if (!empty($row['group_key'])) $classes[] = 'group-' . $row['group_key'];
                }
                $row['DT_RowClass'] = implode(' ', $classes);

                // account_name HTML
                if ($row['is_section_header']) {
                    $chev = $row['has_children'] ? '<i class="fas fa-caret-down toggle-caret mr-2"></i>' : '';
                    $row['account_name'] = '<span class="account-name-cell toggle-section" data-group="' . e($row['group_key']) . '" style="cursor:' . ($row['has_children'] ? 'pointer' : 'default') . '">'
                        . $chev . '<strong class="section-header">' . e($row['name']) . '</strong>'
                        // optional collapsed small total next to header (visible when collapsed)
                        . ' <span class="section-total-display" data-group="' . e($row['group_key']) . '" style="display:none;color:#6c757d;font-weight:normal;margin-left:8px;">(' . number_format($row['section_total'], 2) . ')</span>'
                        . '</span>';
                } elseif ($row['is_total']) {
                    $row['account_name'] = '<span class="account-name-cell"><strong class="total-label">' . e($row['name']) . '</strong></span>';
                } elseif ($row['is_subtotal']) {
                    $row['account_name'] = '<span class="account-name-cell"><strong class="subtotal-label">' . e($row['name']) . '</strong></span>';
                } else {
                    $indent = $row['is_child'] ? '<span class="pl-3"></span>' : '';
                    $codeHtml = $row['code'] ? '<span class="account-code">' . e($row['code']) . ' - </span>' : '';
                    $row['account_name'] = '<span class="account-name-cell">' . $indent . $codeHtml . e($row['name']) . '</span>';
                }

                // per-quarter display keys (always present)
                foreach ($this->quarters as $q) {
                    $key = $q['key'];
                    $val = $row[$key] ?? 0;
                    $row[$key] = $val;
                    if ($row['is_section_header']) {
                        $row[$key . '_display'] = '';
                    } elseif ($row['is_total'] || $row['is_subtotal']) {
                        $row[$key . '_display'] = '<strong class="total-amount">' . number_format($val, 2) . '</strong>';
                    } else {
                        $row[$key . '_display'] = '<span class="amount-cell">' . number_format($val, 2) . '</span>';
                    }
                }

                // total numeric and display; also add optional section total amount element for header (hidden initially)
                $row['total'] = $row['total'] ?? 0;
                if ($row['is_section_header']) {
                    $row['total_display'] = '';
                    $row['total_section_amount_html'] = '<span class="section-total-amount" data-group="' . e($row['group_key']) . '" style="display:none;font-weight:bold;color:#6c757d;">' . number_format($row['section_total'], 2) . '</span>';
                } elseif ($row['is_total'] || $row['is_subtotal']) {
                    $row['total_display'] = '<strong class="total-amount">' . number_format($row['total'], 2) . '</strong>';
                } else {
                    $row['total_display'] = '<span class="amount-cell">' . number_format($row['total'], 2) . '</span>';
                }

                return $row;
            });

            return $final;

        } catch (\Exception $e) {
            \Log::error('ProfitLoss Query Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return collect();
        }
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('profit-loss-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'scrollX' => true,
            ]);
    }

    protected function getColumns()
    {
        $columns = [
            Column::make('account_name')->title('Account')->width('30%')->orderable(false)->data('account_name'),
        ];

        foreach ($this->quarters as $quarter) {
            $columns[] = Column::make($quarter['key'] . '_display')
                ->title($quarter['label'])
                ->addClass('text-right')
                ->orderable(false)
                ->data($quarter['key'] . '_display')
                ->width((60 / (count($this->quarters) + 1)) . '%');
        }

        $columns[] = Column::make('total_display')
            ->title('Total')
            ->addClass('text-right')
            ->orderable(false)
            ->data('total_display')
            ->width((60 / (count($this->quarters) + 1)) . '%');

        return $columns;
    }
}
