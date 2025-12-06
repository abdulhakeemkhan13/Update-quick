<?php

namespace App\DataTables;

use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Models\VendorCredit;
use App\Models\Vender;
use Illuminate\Support\Collection;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class VendorsSingleDetailsShowDataTable extends DataTable
{
    protected $vendor_id;

    public function __construct($vendor_id)
    {
        $this->vendor_id = $vendor_id;
    }

    public function dataTable($query)
    {
        return datatables()
            ->collection($query)
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="form-check-input row-checkbox" value="' . $row['id'] . '">';
            })
            ->editColumn('date', function ($row) {
                return '<span style="color:#333;">' . \Auth::user()->dateFormat($row['date']) . '</span>';
            })
            ->editColumn('type', function ($row) {
                $color = '#0077c5';
                return '<span style="color:' . $color . ';">' . $row['type'] . '</span>';
            })
            ->editColumn('number', function ($row) {
                $url = $row['url'] ?? '#';
                return '<a href="' . $url . '" style="color:#0077c5; text-decoration:none; font-weight:500;">' . $row['number'] . '</a>';
            })
            ->editColumn('payee', function ($row) {
                 return '<span style="color:#0077c5;">' . ($row['payee'] ?? '-') . '</span>';
            })
            ->editColumn('category', function ($row) {
                 $categoryName = $row['category'] ?? '-';
                 if ($categoryName != '-') {
                     return '<select class="form-select form-select-sm" style="width: auto; display: inline-block; font-size: 12px;"><option>' . $categoryName . '</option></select>';
                 }
                 return '-';
            })
            ->editColumn('total', function ($row) {
                $prefix = ($row['type'] == 'Bill Payment' || $row['type'] == 'Vendor Credit') ? '-' : '';
                return '<span style="font-weight:500;">' . $prefix . '$ ' . number_format($row['total'], 2) . '</span>';
            })
            ->editColumn('status', function ($row) {
                $status = $row['status'] ?? '';
                $statusClass = 'bg-secondary';
                
                if ($status == 'Draft') $statusClass = 'bg-primary';
                elseif ($status == 'Sent') $statusClass = 'bg-warning';
                elseif ($status == 'Unpaid') $statusClass = 'bg-danger';
                elseif ($status == 'Partialy Paid') $statusClass = 'bg-info';
                elseif ($status == 'Paid') $statusClass = 'bg-success';
                elseif ($status == 'Open') $statusClass = 'bg-warning';
                elseif ($status == 'Closed') $statusClass = 'bg-success';
                
                return '<span class="badge ' . $statusClass . ' p-2 px-3 rounded">' . __($status) . '</span>';
            })
            ->addColumn('action', function ($row) {
                $actions = '<div class="d-flex justify-content-end align-items-center">
                                <div class="dropdown">
                                    <a class="text-secondary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        ' . __('View/Edit') . ' <i class="ti ti-chevron-down"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">';
                
                if ($row['url']) {
                    $actions .= '<li><a class="dropdown-item" href="' . $row['url'] . '">' . __('View') . '</a></li>';
                }
                if ($row['edit_url'] ?? false) {
                    $actions .= '<li><a class="dropdown-item" href="' . $row['edit_url'] . '">' . __('Edit') . '</a></li>';
                }

                $actions .= '</ul></div></div>';
                return $actions;
            })
            ->rawColumns(['checkbox', 'date', 'type', 'number', 'payee', 'category', 'total', 'action']);
    }

    public function query()
    {
        $transactions = collect();
        $vendorId = $this->vendor_id;
        $creatorId = \Auth::user()->creatorId();
        $transactionType = request()->get('transaction_type', '');
        
        $vendor = Vender::find($vendorId);
        $vendorName = $vendor->name ?? '-';
        
        // Get date filters
        $dateFrom = request()->get('date_from');
        $dateTo = request()->get('date_to');
        $status = request()->get('status');
        $categoryId = request()->get('category');
        
        // Bills (type = 'Bill')
        if (empty($transactionType) || $transactionType == 'bill') {
            $billsQuery = Bill::with('category')
                ->where('vender_id', $vendorId)
                ->where('created_by', $creatorId)
                ->where('type', 'Bill');
            
            if ($dateFrom) $billsQuery->whereDate('bill_date', '>=', $dateFrom);
            if ($dateTo) $billsQuery->whereDate('bill_date', '<=', $dateTo);
            if ($status !== null && $status !== '') $billsQuery->where('status', $status);
            if ($categoryId) $billsQuery->where('category_id', $categoryId);
            
            $bills = $billsQuery->get();
            
            foreach ($bills as $bill) {
                $categoryName = '-';
                if ($bill->category) {
                    $categoryName = $bill->category->name;
                }
                
                $transactions->push([
                    'id' => 'bill_' . $bill->id,
                    'date' => $bill->bill_date,
                    'type' => 'Bill',
                    'number' => '#' . \Auth::user()->billNumberFormat($bill->bill_id),
                    'payee' => $vendorName,
                    'category' => $categoryName,
                    'total' => $bill->getTotal(),
                    'status' => Bill::$statues[$bill->status] ?? '-',
                    'url' => route('bill.show', \Crypt::encrypt($bill->id)),
                    'edit_url' => route('bill.edit', \Crypt::encrypt($bill->id)),
                ]);
            }
        }
        
        // Expenses (stored in bills table with type = 'Expense')
        if (empty($transactionType) || $transactionType == 'expense') {
            $expensesQuery = Bill::with('category')
                ->where('vender_id', $vendorId)
                ->where('created_by', $creatorId)
                ->where('type', 'Expense');
            
            if ($dateFrom) $expensesQuery->whereDate('bill_date', '>=', $dateFrom);
            if ($dateTo) $expensesQuery->whereDate('bill_date', '<=', $dateTo);
            if ($status !== null && $status !== '') $expensesQuery->where('status', $status);
            if ($categoryId) $expensesQuery->where('category_id', $categoryId);
            
            $expenses = $expensesQuery->get();
            
            foreach ($expenses as $expense) {
                $categoryName = '-';
                if ($expense->category) {
                    $categoryName = $expense->category->name;
                }
                
                $transactions->push([
                    'id' => 'expense_' . $expense->id,
                    'date' => $expense->bill_date,
                    'type' => 'Expense',
                    'number' => '#' . \Auth::user()->billNumberFormat($expense->bill_id),
                    'payee' => $vendorName,
                    'category' => $categoryName,
                    'total' => $expense->getTotal(),
                    'status' => Bill::$statues[$expense->status] ?? '-',
                    'url' => route('bill.show', \Crypt::encrypt($expense->id)),
                    'edit_url' => route('bill.edit', \Crypt::encrypt($expense->id)),
                ]);
            }
        }
        
        // Bill Payments
        if (empty($transactionType) || $transactionType == 'bill_payment') {
            $paymentsQuery = BillPayment::whereHas('bill', function($q) use ($vendorId) {
                $q->where('vender_id', $vendorId);
            });
            
            if ($dateFrom) $paymentsQuery->whereDate('date', '>=', $dateFrom);
            if ($dateTo) $paymentsQuery->whereDate('date', '<=', $dateTo);
            
            $payments = $paymentsQuery->get();
            
            foreach ($payments as $payment) {
                $bill = $payment->bill;
                $transactions->push([
                    'id' => 'payment_' . $payment->id,
                    'date' => $payment->date,
                    'type' => 'Bill Payment',
                    'number' => '#' . $payment->id,
                    'payee' => $vendorName,
                    'category' => '-',
                    'total' => $payment->amount,
                    'status' => 'Paid',
                    'url' => $bill ? route('bill.show', \Crypt::encrypt($bill->id)) : '#',
                    'edit_url' => null,
                ]);
            }
        }
        // Purchase Orders
        if (empty($transactionType) || $transactionType == 'purchase_order') {
            $purchasesQuery = Purchase::where('vender_id', $vendorId)
                ->where('created_by', $creatorId);
            
            if ($dateFrom) $purchasesQuery->whereDate('purchase_date', '>=', $dateFrom);
            if ($dateTo) $purchasesQuery->whereDate('purchase_date', '<=', $dateTo);
            if ($categoryId) $purchasesQuery->where('category_id', $categoryId);
            
            $purchases = $purchasesQuery->get();
            
            foreach ($purchases as $purchase) {
                $transactions->push([
                    'id' => 'purchase_' . $purchase->id,
                    'date' => $purchase->purchase_date,
                    'type' => 'Purchase Order',
                    'number' => '#' . ($purchase->purchase_number ?? $purchase->id),
                    'payee' => $vendorName,
                    'category' => $purchase->category->name ?? '-',
                    'total' => $purchase->getTotal(),
                    'status' => Purchase::$statues[$purchase->status ?? 0] ?? 'Open',
                    'url' => route('purchase.show', \Crypt::encrypt($purchase->id)),
                    'edit_url' => route('purchase.edit', \Crypt::encrypt($purchase->id)),
                ]);
            }
        }
        
        // Vendor Credits
        if (empty($transactionType) || $transactionType == 'vendor_credit') {
            try {
                $creditsQuery = VendorCredit::where('vendor_id', $vendorId)
                    ->where('created_by', $creatorId);
                
                if ($dateFrom) $creditsQuery->whereDate('credit_date', '>=', $dateFrom);
                if ($dateTo) $creditsQuery->whereDate('credit_date', '<=', $dateTo);
                
                $credits = $creditsQuery->get();
                
                foreach ($credits as $credit) {
                    $transactions->push([
                        'id' => 'credit_' . $credit->id,
                        'date' => $credit->credit_date,
                        'type' => 'Vendor Credit',
                        'number' => '#' . ($credit->credit_number ?? $credit->id),
                        'payee' => $vendorName,
                        'category' => '-',
                        'total' => $credit->amount ?? 0,
                        'status' => ucfirst($credit->status ?? 'Open'),
                        'url' => '#',
                        'edit_url' => null,
                    ]);
                }
            } catch (\Exception $e) {
                // VendorCredit table may not exist, skip silently
            }
        }
        
        // Transaction table records (for expenses, checks etc)
        if (empty($transactionType) || $transactionType == 'expense' || $transactionType == 'check') {
            $transQuery = Transaction::where('user_id', $vendorId)
                ->where('user_type', 'Vender')
                ->where('created_by', $creatorId);
            
            if ($transactionType == 'expense') {
                $transQuery->where('category', 'Bill');
            } elseif ($transactionType == 'check') {
                $transQuery->where('type', 'check');
            }
            
            if ($dateFrom) $transQuery->whereDate('date', '>=', $dateFrom);
            if ($dateTo) $transQuery->whereDate('date', '<=', $dateTo);
            
            $trans = $transQuery->get();
            
            foreach ($trans as $t) {
                $type = ucfirst($t->type ?? 'Expense');
                if ($t->category == 'Bill') $type = 'Expense';
                
                $transactions->push([
                    'id' => 'trans_' . $t->id,
                    'date' => $t->date,
                    'type' => $type,
                    'number' => '#' . ($t->payment_no ?? $t->id),
                    'payee' => $vendorName,
                    'category' => $t->category ?? '-',
                    'total' => $t->amount,
                    'status' => '-',
                    'url' => '#',
                    'edit_url' => null,
                ]);
            }
        }
        
        // Recently paid filter
        if ($transactionType == 'recently_paid') {
            // Get bills that were paid in last 30 days
            $recentBills = Bill::where('vender_id', $vendorId)
                ->where('created_by', $creatorId)
                ->where('status', 4) // Paid
                ->where('updated_at', '>=', now()->subDays(30))
                ->get();
                
            foreach ($recentBills as $bill) {
                $transactions->push([
                    'id' => 'bill_' . $bill->id,
                    'date' => $bill->bill_date,
                    'type' => 'Bill',
                    'number' => '#' . \Auth::user()->billNumberFormat($bill->bill_id),
                    'payee' => $vendorName,
                    'category' => $bill->category->name ?? '-',
                    'total' => $bill->getTotal(),
                    'status' => 'Paid',
                    'url' => route('bill.show', \Crypt::encrypt($bill->id)),
                    'edit_url' => route('bill.edit', \Crypt::encrypt($bill->id)),
                ]);
            }
        }
        
        // Sort by date descending
        return $transactions->sortByDesc('date')->values();
    }

    public function html()
    {
        return $this->builder()
                    ->setTableId('vendor-transactions-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->dom('t')
                    ->orderBy(1, 'desc')
                    ->parameters([
                        "dom" =>  "<'row'<'col-sm-12'tr>>",
                        'language' => [
                            'paginate' => [
                                'next' => '<i class="ti ti-chevron-right"></i>',
                                'previous' => '<i class="ti ti-chevron-left"></i>'
                            ]
                        ],
                        'drawCallback' => "function() {
                            $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
                        }"
                    ]);
    }

    protected function getColumns()
    {
        return [
            Column::computed('checkbox')
                  ->title('<input type="checkbox" class="form-check-input" id="select-all">')
                  ->exportable(false)
                  ->printable(false)
                  ->width(20)
                  ->addClass('text-center align-middle'),
            Column::make('date')->title('DATE')->addClass('align-middle'),
            Column::make('type')->title('TYPE')->addClass('align-middle'),
            Column::make('number')->title('NO.')->addClass('align-middle'),
            Column::make('payee')->title('PAYEE')->addClass('align-middle'),
            Column::make('category')->title('CATEGORY')->addClass('align-middle'),
            Column::make('total')->title('TOTAL')->addClass('text-end align-middle'),
            Column::computed('action')
                  ->exportable(false)
                  ->printable(false)
                  ->width(100)
                  ->addClass('text-end align-middle')
                  ->title('ACTION'),
        ];
    }

    protected function filename(): string
    {
        return 'VendorTransactions_' . date('YmdHis');
    }
}
