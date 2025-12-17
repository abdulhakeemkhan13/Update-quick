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

        $grandTotal = 0.0;

        foreach ($grouped as $partyId => $transactions) {
            $groupKey = 'party-' . ($partyId ?? 'unknown');
            $displayName = $transactions->first()->customer_name ?? 'Unknown Customer';

            // compute group total (numeric)
            $groupTotal = 0.0;
            foreach ($transactions as $t) {
                $amt = $this->calculateAmount($t);
                $groupTotal += $amt;
            }

            $grandTotal += $groupTotal;

            // push the group header first (header will appear above its items)
            $final->push([
                'group_key' => $groupKey,
                'is_group_header' => true,
                'date' => '',
                'transaction_type' => '',
                'num' => '',
                'posting' => '',
                'memo_description' => '',
                'account_full_name' => '',
                'amount' => $groupTotal,
                'customer_name' => $displayName,
            ]);

            // then push all transactions for this group
            foreach ($transactions as $t) {
                $amt = $this->calculateAmount($t);

                $final->push([
                    'group_key' => $groupKey,
                    'is_group_header' => false,
                    'is_total_row' => false,
                    'is_grand_total' => false,
                    'date' => $t->date ? Carbon::parse($t->date)->format('m/d/Y') : '-',
                    'transaction_type' => $this->mapReferenceToType($t->reference),
                    'num' => $this->formatReferenceNumber($t->reference, $t->reference_id),
                    'posting' => 'Y',
                    'memo_description' => $this->buildDescription($t),
                    'account_full_name' => $t->account_name ?? '-',
                    'amount' => $amt,
                    'customer_name' => $t->customer_name ?? 'Unknown Customer',
                ]);
            }

            // Add total row for the customer
            $final->push([
                'group_key' => $groupKey,
                'is_group_header' => false,
                'is_total_row' => true,
                'is_grand_total' => false,
                'date' => '<strong>Total for ' . e($displayName) . '</strong>',
                'transaction_type' => '',
                'num' => '',
                'posting' => '',
                'memo_description' => '',
                'account_full_name' => '',
                'amount' => $groupTotal,
                'customer_name' => $displayName,
            ]);
        }

        // Add grand total row
        if ($final->isNotEmpty()) {
            $final->push([
                'group_key' => 'grand-total',
                'is_group_header' => false,
                'is_total_row' => false,
                'is_grand_total' => true,
                'date' => '<strong>Grand Total</strong>',
                'transaction_type' => '',
                'num' => '',
                'posting' => '',
                'memo_description' => '',
                'account_full_name' => '',
                'amount' => $grandTotal,
                'customer_name' => 'Grand Total',
            ]);
        }

        // Return datatables collection with header HTML + row attributes
        return datatables()
            ->collection($final)
            ->addColumn('date', function ($r) {
                if (!empty($r['is_group_header'])) {
                    return '<span class="group-toggle" data-group="' . e($r['group_key']) . '" style="cursor:pointer;">'
                        . '<i class="fas fa-chevron-right me-2"></i>'
                        . '<strong>' . e($r['customer_name']) . '</strong>'
                        . ' <span class="text-muted"></span>'
                        . '</span>';
                }
                if (!empty($r['is_total_row']) || !empty($r['is_grand_total'])) {
                    return $r['date'];
                }
                return e($r['date']);
            })
            ->addColumn('transaction_type', fn($r) => (!empty($r['is_group_header']) || !empty($r['is_total_row']) || !empty($r['is_grand_total'])) ? '' : e($r['transaction_type']))
            ->addColumn('num', fn($r) => (!empty($r['is_group_header']) || !empty($r['is_total_row']) || !empty($r['is_grand_total'])) ? '' : e($r['num']))
            ->addColumn('posting', fn($r) => (!empty($r['is_group_header']) || !empty($r['is_total_row']) || !empty($r['is_grand_total'])) ? '' : e($r['posting']))
            ->addColumn('memo_description', fn($r) => (!empty($r['is_group_header']) || !empty($r['is_total_row']) || !empty($r['is_grand_total'])) ? '' : e($r['memo_description']))
            ->addColumn('account_full_name', fn($r) => (!empty($r['is_group_header']) || !empty($r['is_total_row']) || !empty($r['is_grand_total'])) ? '' : e($r['account_full_name']))
            ->addColumn('amount', function ($r) {
                $amount = (float)($r['amount'] ?? 0);
                $formatted = number_format($amount, 2);
                
                if (!empty($r['is_total_row']) || !empty($r['is_grand_total'])) {
                    return '<strong>' . $formatted . '</strong>';
                }
                
                return $formatted;
            })
            ->addColumn('customer_name', fn($r) => e($r['customer_name']))
            ->setRowAttr([
                'class' => function ($r) {
                    if (!empty($r['is_grand_total'])) {
                        return 'summary-total';
                    }
                    if (!empty($r['is_total_row'])) {
                        return 'customer-total-row font-weight-bold';
                    }
                    return !empty($r['is_group_header']) ? 'group-header' : 'group-row group-' . $r['group_key'];
                },
                'data-group' => function ($r) {
                    return $r['group_key'];
                },
                'style' => function ($r) {
                    if (!empty($r['is_grand_total'])) {
                        return 'background-color:#e2e8f0; font-weight:700;';
                    }
                    if (!empty($r['is_total_row'])) {
                        return 'background-color:#fefeff; font-weight:700; display:table-row;';
                    }
                    return !empty($r['is_group_header']) ? 'background-color:#f8f9fa; font-weight:600; cursor:pointer;' : 'display:none;';
                },
            ])
            ->rawColumns(['date', 'amount']);
    }

    /**
     * Calculate the amount for a transaction consistently
     * Invoice = +600 (credit)
     * Payment = -10 (debit, negative in ledger)
     * Credit Memo = -500, -10, -50 (debit, negative in ledger)
     */
    protected function calculateAmount($transaction)
    {
        // Safely extract and cast all values
        $credit = 0.0;
        $debit = 0.0;
        $reference = '';

        // Handle credit - cast to float, handle null
        if (isset($transaction->credit) && $transaction->credit !== null && $transaction->credit !== '') {
            $credit = floatval($transaction->credit);
        }

        // Handle debit - cast to float, handle null
        if (isset($transaction->debit) && $transaction->debit !== null && $transaction->debit !== '') {
            $debit = floatval($transaction->debit);
        }

        // Handle reference - cast to string, handle null
        if (isset($transaction->reference) && $transaction->reference !== null) {
            $reference = strtolower(strval($transaction->reference));
        }

        $amt = 0.0;

        // Logic:
        // If credit > 0: amount is positive (Invoice, Revenue) = +600
        // If debit > 0: amount is negative (Payment, Credit Memo) = -10, -500, -10, -50
        
        if ($credit > 0) {
            $amt = $credit;  // +600
        } elseif ($debit > 0) {
            $amt = -$debit;  // -10, -500, -10, -50
        }

        return $amt;
    }

    /**
     * Map raw reference string to nicer display label
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
            'Credit Memo' => 'Credit Memo',
        ];
        return $map[$type] ?? ucwords(str_replace('_', ' ', $type));
    }

    /**
     * Format a stable reference number for display
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
            case 'Credit Memo':
                $prefix = 'CN';
                break;
        }
        return $prefix . '-' . str_pad((int)$referenceId, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Build a reasonable description from available fields
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
            case 'Credit Memo':
                return 'Credit Memo to ' . ($t->customer_name ?? 'Customer');
            default:
                return ucwords($t->reference ?? 'Transaction');
        }
    }

    public function query()
    {
        $user = Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();

        // Invoices
        $invoiceQuery = DB::table('invoices')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->selectRaw("invoices.id as id, invoices.issue_date as date, 'Invoice' as reference, invoices.id as reference_id, NULL as reference_sub_id, 0 as debit, invoices.total_amount as credit, invoices.created_by, 'Accounts Receivable' as account_name, NULL as account_code, customers.name as customer_name, NULL as vendor_name, invoices.customer_id as party_id, invoices.memo as description")
            ->where('invoices.created_by', $ownerId)
            ->where('invoices.status', '!=', 0);

        // Invoice Payments
        $invoicePaymentQuery = DB::table('invoice_payments')
            ->join('invoices', 'invoice_payments.invoice_id', '=', 'invoices.id')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->leftJoin('bank_accounts', 'invoice_payments.account_id', '=', 'bank_accounts.id')
            ->selectRaw("invoice_payments.id as id, invoice_payments.date, 'Invoice Payment' as reference, invoice_payments.invoice_id as reference_id, NULL as reference_sub_id, 0 as debit, (invoice_payments.amount - COALESCE((SELECT SUM(credit_notes.amount) FROM credit_notes WHERE credit_notes.payment_id = invoice_payments.id), 0) + COALESCE((SELECT SUM(t2.amount) FROM transactions t2 WHERE t2.payment_no = (SELECT t1.payment_no FROM transactions t1 WHERE t1.payment_id = invoice_payments.id LIMIT 1) AND t2.category = 'customer credit'), 0)) as credit, NULL as created_by, COALESCE(bank_accounts.bank_name, 'Cash') as account_name, NULL as account_code, customers.name as customer_name, NULL as vendor_name, invoices.customer_id as party_id, invoice_payments.description as description")
            ->where('invoices.created_by', $ownerId);

        // Credit Notes
        $creditQuery = DB::table('credit_notes')
            ->join('customers', 'credit_notes.customer', '=', 'customers.id')
            ->selectRaw("credit_notes.id as id, credit_notes.date, 'Credit Memo' as reference, credit_notes.id as reference_id, NULL as reference_sub_id, credit_notes.amount as debit, 0 as credit, credit_notes.created_by, 'Accounts Receivable' as account_name, NULL as account_code, customers.name as customer_name, NULL as vendor_name, credit_notes.customer as party_id, credit_notes.description")
            ->where('credit_notes.created_by', $ownerId);

        // Revenues
        $revenueQuery = DB::table('revenues')
            ->join('customers', 'revenues.customer_id', '=', 'customers.id')
            ->selectRaw("revenues.id as id, revenues.date, 'Revenue' as reference, revenues.id as reference_id, NULL as reference_sub_id, 0 as debit, revenues.amount as credit, revenues.created_by, 'Revenue' as account_name, NULL as account_code, customers.name as customer_name, NULL as vendor_name, revenues.customer_id as party_id, revenues.description")
            ->where('revenues.created_by', $ownerId);

        // Payments (expenses)
        $paymentQuery = DB::table('payments')
            ->join('venders', 'payments.vender_id', '=', 'venders.id')
            ->selectRaw("payments.id as id, payments.date, 'Payment' as reference, payments.id as reference_id, NULL as reference_sub_id, payments.amount as debit, 0 as credit, payments.created_by, 'Expense' as account_name, NULL as account_code, NULL as customer_name, venders.name as vendor_name, payments.vender_id as party_id, payments.description")
            ->where('payments.created_by', $ownerId);

        // Apply optional filters
        if (request()->filled('customer_name') && request('customer_name') !== '') {
            $name = request('customer_name');
            $invoiceQuery->where('customers.name', 'LIKE', "%{$name}%");
            $invoicePaymentQuery->where('customers.name', 'LIKE', "%{$name}%");
            $creditQuery->where('customers.name', 'LIKE', "%{$name}%");
            $revenueQuery->where('customers.name', 'LIKE', "%{$name}%");
        }

        if (request()->filled('start_date') && request()->filled('end_date')) {
            $start = request('start_date');
            $end = request('end_date');
            $invoiceQuery->where('invoices.issue_date', '>=', $start)->where('invoices.issue_date', '<=', $end);
            $invoicePaymentQuery->where('invoice_payments.date', '>=', $start)->where('invoice_payments.date', '<=', $end);
            $creditQuery->where('credit_notes.date', '>=', $start)->where('credit_notes.date', '<=', $end);
            $revenueQuery->where('revenues.date', '>=', $start)->where('revenues.date', '<=', $end);
            $paymentQuery->where('payments.date', '>=', $start)->where('payments.date', '<=', $end);
        }

        if (request()->filled('transaction_type') && request('transaction_type') !== '') {
            $type = request('transaction_type');
            if ($type === 'Invoice') {
                $query = $invoiceQuery;
            } elseif ($type === 'Invoice Payment') {
                $query = $invoicePaymentQuery;
            } elseif ($type === 'Credit Memo') {
                $query = $creditQuery;
            } elseif ($type === 'Revenue') {
                $query = $revenueQuery;
            } elseif ($type === 'Payment') {
                $query = $paymentQuery;
            } else {
                $query = $invoiceQuery->unionAll($invoicePaymentQuery)->unionAll($creditQuery)->unionAll($revenueQuery)->unionAll($paymentQuery);
            }
        } else {
            $query = $invoiceQuery->unionAll($invoicePaymentQuery)->unionAll($creditQuery)->unionAll($revenueQuery)->unionAll($paymentQuery);
        }

        return $query->orderBy('date', 'desc')->orderBy('customer_name', 'asc');
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
            Column::make('customer_name')->title(__('Customer'))->visible(false),
        ];
    }

    protected function filename(): string
    {
        return 'TransactionListByCustomer_' . date('YmdHis');
    }
}