<?php

namespace App\DataTables;

use App\Models\TransactionLines;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class TransactionListByCustomerDataTable extends DataTable
{
    public function dataTable($query)
    {
        // fetch raw rows from query builder
        $rows = $query->get();

        // final collection that will be returned to DataTables
        $final = collect();

        // group rows by stable party_id (so names/spacing don't split groups)
        $grouped = $rows->groupBy('party_id');

        foreach ($grouped as $partyId => $transactions) {
            $groupKey = 'party-' . ($partyId ?? 'unknown');
            $displayName = $transactions->first()->customer_name ?? 'Unknown Customer';

            // compute group total (numeric). amount logic same as in amount() column
            $groupTotal = 0.0;
            foreach ($transactions as $t) {
                $amt = 0.0;
                if ((float)$t->credit > 0) {
                    $amt = (float)$t->credit;
                } elseif ((float)$t->debit > 0) {
                    $amt = -(float)$t->debit;
                }
                $groupTotal += $amt;
            }

            // push the group header first (header will appear above its items)
            $final->push([
                'group_key' => $groupKey,
                'is_group_header' => true,
                // date column used to render header (chevron + name + total)
                'date' => '',
                'transaction_type' => '',
                'num' => '',
                'posting' => '',
                'memo_description' => '',
                'account_full_name' => '',
                'amount' => number_format($groupTotal, 2),
                'customer_name' => $displayName,
            ]);

            // then push all transactions for this group with running balance
            $running = 0.0;
            foreach ($transactions as $t) {
                $amt = 0.0;
                if ((float)$t->credit > 0) {
                    $amt = (float)$t->credit;
                } elseif ((float)$t->debit > 0) {
                    $amt = -(float)$t->debit;
                }
                $running += $amt;

                $final->push([
                    'group_key' => $groupKey,
                    'is_group_header' => false,
                    'date' => $t->date ? Carbon::parse($t->date)->format('m/d/Y') : '-',
                    'transaction_type' => $this->mapReferenceToType($t->reference),
                    'num' => $this->formatReferenceNumber($t->reference, $t->reference_id),
                    'posting' => 'Y',
                    'memo_description' => $this->buildDescription($t),
                    'account_full_name' => $t->account_name ?? '-',
                    'amount' => number_format($amt, 2),
                    'customer_name' => $t->customer_name ?? 'Unknown Customer',
                ]);
            }
        }

        // Return datatables collection with header HTML + row attributes
        return datatables()
            ->collection($final)
            ->addColumn('date', function ($r) {
                if (!empty($r['is_group_header'])) {
                    // group header (chevron + customer name + total). data-group used by JS.
                    return '<span class="group-toggle" data-group="' . e($r['group_key']) . '" style="cursor:pointer;">'
                        . '<i class="fas fa-chevron-right me-2"></i>'
                        . '<strong>' . e($r['customer_name']) . '</strong>'
                        . ' <span class="text-muted"></span>'
                        . '</span>';
                }
                return e($r['date']);
            })
            ->addColumn('transaction_type', fn($r) => $r['is_group_header'] ? '' : e($r['transaction_type']))
            ->addColumn('num', fn($r) => $r['is_group_header'] ? '' : e($r['num']))
            ->addColumn('posting', fn($r) => $r['is_group_header'] ? '' : e($r['posting']))
            ->addColumn('memo_description', fn($r) => $r['is_group_header'] ? '' : e($r['memo_description']))
            ->addColumn('account_full_name', fn($r) => $r['is_group_header'] ? '' : e($r['account_full_name']))
            ->addColumn('amount', fn($r) => $r['is_group_header'] ? '' : e($r['amount']))
            ->addColumn('customer_name', fn($r) => e($r['customer_name']))
            ->setRowAttr([
                'class' => function ($r) {
                    return !empty($r['is_group_header']) ? 'group-header' : 'group-row group-' . $r['group_key'];
                },
                'data-group' => function ($r) {
                    return $r['group_key'];
                },
                // headers visible; rows hidden by default
                'style' => function ($r) {
                    return !empty($r['is_group_header']) ? 'background-color:#f8f9fa; font-weight:600; cursor:pointer;' : 'display:none;';
                },
            ])
            ->rawColumns(['date', 'amount']);
    }

    /**
     * Map raw reference string to nicer display label (same mapping you had).
     */
    protected function mapReferenceToType($reference)
    {
        $type = $reference ?? 'Transaction';
        $map = [
            'Invoice' => 'Invoice',
            'Invoice Payment' => 'Payment',
            'Invoice Account' => 'Invoice',
            'Bill' => 'Bill',
            'Bill Payment' => 'Payment',
            'Bill Account' => 'Bill',
            'Revenue' => 'Deposit',
            'Payment' => 'Expense',
            'Expense' => 'Expense',
            'Expense Payment' => 'Expense Payment',
            'Expense Account' => 'Expense',
        ];
        return $map[$type] ?? ucwords(str_replace('_', ' ', $type));
    }

    /**
     * Format a stable reference number for display.
     */
    protected function formatReferenceNumber($reference, $referenceId)
    {
        $prefix = 'TXN';
        switch ($reference) {
            case 'Invoice':
            case 'Invoice Payment':
            case 'Invoice Account':
                $prefix = 'INV';
                break;
            case 'Bill':
            case 'Bill Payment':
            case 'Bill Account':
                $prefix = 'BILL';
                break;
            case 'Revenue':
                $prefix = 'REV';
                break;
            case 'Payment':
            case 'Expense':
            case 'Expense Payment':
            case 'Expense Account':
                $prefix = 'EXP';
                break;
        }
        return $prefix . '-' . str_pad((int)$referenceId, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Build a reasonable description from available fields.
     */
    protected function buildDescription($t)
    {
        if (!empty($t->description)) return $t->description;
        if (!empty($t->memo)) return $t->memo;

        switch ($t->reference) {
            case 'Invoice':
                return 'Invoice to ' . ($t->customer_name ?? 'Customer');
            case 'Invoice Payment':
                return 'Payment for Invoice from ' . ($t->customer_name ?? 'Customer');
            case 'Bill':
                return 'Bill from ' . ($t->vendor_name ?? 'Vendor');
            case 'Bill Payment':
                return 'Payment for Bill to ' . ($t->vendor_name ?? 'Vendor');
            case 'Revenue':
                return 'Revenue from ' . ($t->customer_name ?? 'Customer');
            case 'Payment':
            case 'Expense':
                return 'Expense payment to ' . ($t->vendor_name ?? 'Vendor');
            default:
                return ucwords($t->reference ?? 'Transaction');
        }
    }

    public function query(TransactionLines $model)
    {
        $user = Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();

        // Build the same complex query you had but include party_id (alias) for grouping
        $query = $model->newQuery()
            ->select([
                'transaction_lines.id',
                'transaction_lines.date',
                'transaction_lines.reference',
                'transaction_lines.reference_id',
                'transaction_lines.reference_sub_id',
                'transaction_lines.debit',
                'transaction_lines.credit',
                'transaction_lines.created_by',
                'chart_of_accounts.name as account_name',
                'chart_of_accounts.code as account_code',
                DB::raw('COALESCE(customers.name, revenues_customers.name, payments_venders.name, bills_venders.name) as customer_name'),
                DB::raw('COALESCE(venders.name, payments_venders.name, bills_venders.name) as vendor_name'),
                DB::raw('COALESCE(invoices.customer_id, revenues.customer_id, bills.vender_id, payments.vender_id) as party_id'),
            ])
            ->leftJoin('chart_of_accounts', 'transaction_lines.account_id', '=', 'chart_of_accounts.id')
            ->leftJoin('invoices', function ($join) {
                $join->on('transaction_lines.reference_id', '=', 'invoices.id')
                    ->whereIn('transaction_lines.reference', ['Invoice', 'Invoice Payment', 'Invoice Account']);
            })
            ->leftJoin('customers', 'invoices.customer_id', '=', 'customers.id')
            ->leftJoin('bills', function ($join) {
                $join->on('transaction_lines.reference_id', '=', 'bills.id')
                    ->whereIn('transaction_lines.reference', ['Bill', 'Bill Payment', 'Bill Account']);
            })
            ->leftJoin('venders', 'bills.vender_id', '=', 'venders.id')
            ->leftJoin('revenues', function ($join) {
                $join->on('transaction_lines.reference_id', '=', 'revenues.id')
                    ->where('transaction_lines.reference', '=', 'Revenue');
            })
            ->leftJoin('customers as revenues_customers', 'revenues.customer_id', '=', 'revenues_customers.id')
            ->leftJoin('payments', function ($join) {
                $join->on('transaction_lines.reference_id', '=', 'payments.id')
                    ->whereIn('transaction_lines.reference', ['Payment', 'Expense', 'Expense Payment', 'Expense Account']);
            })
            ->leftJoin('venders as payments_venders', 'payments.vender_id', '=', 'payments_venders.id')
            ->leftJoin('venders as bills_venders', 'bills.vender_id', '=', 'bills_venders.id')
            ->where('transaction_lines.created_by', $ownerId)
            ->whereNotNull(DB::raw('COALESCE(customers.name, revenues_customers.name, payments_venders.name, bills_venders.name)'));

        // Apply optional filters (customer_name, transaction_type, date range)
        if (request()->filled('customer_name') && request('customer_name') !== '') {
            $name = request('customer_name');
            $query->where(function ($q) use ($name) {
                $q->where('customers.name', 'LIKE', "%{$name}%")
                  ->orWhere('revenues_customers.name', 'LIKE', "%{$name}%")
                  ->orWhere('payments_venders.name', 'LIKE', "%{$name}%")
                  ->orWhere('bills_venders.name', 'LIKE', "%{$name}%");
            });
        }

        if (request()->filled('transaction_type') && request('transaction_type') !== '') {
            $query->where('transaction_lines.reference', request('transaction_type'));
        }
        
        if (request()->filled('startDate') && request()->filled('endDate')) {
            $query->whereBetween('transaction_lines.date', [request('startDate'), request('endDate')]);
        }

        // ordering so grouped results are consistent
        return $query->orderBy('transaction_lines.date', 'desc')
                     ->orderBy(DB::raw('COALESCE(customers.name, revenues_customers.name, payments_venders.name, bills_venders.name)'), 'asc');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('transaction-list-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('rt')
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'colReorder' => true,
                'fixedHeader' => true,
                'scrollY' => '420px',
                'scrollX' => true,
                'scrollCollapse' => true,
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('date')->title(__('Date'))->addClass('text-center'),
            Column::make('transaction_type')->title(__('Transaction Type')),
            Column::make('num')->title(__('Num')),
            Column::make('posting')->title(__('Posting (Y/N)'))->addClass('text-center'),
            Column::make('memo_description')->title(__('Memo/Description')),
            Column::make('account_full_name')->title(__('Account Full Name')),
            Column::make('amount')->title(__('Amount'))->addClass('text-right'),
            // keep customer_name hidden if you still want to use it for other purposes
            Column::make('customer_name')->title(__('Customer'))->visible(false),
        ];
    }

    protected function filename(): string
    {
        return 'TransactionListByCustomer_' . date('YmdHis');
    }
}
