<?php

namespace App\Http\Controllers;

use App\Exports\TransactionExport;
use App\Models\BankAccount;
use App\Models\ProductServiceCategory;
use App\Models\Transaction;
use App\Models\InvoicePayment;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Proposal;
use App\Models\Revenue;
use App\Models\CreditNote;
use App\Models\Budget;
use App\Models\Utility;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{

    private function yearMonth()
    {
        return [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
        ];
    }

    private function yearList()
    {
        return range(date('Y') - 5, date('Y'));
    }

    public function index(Request $request)
    {

        if (Auth::user()->can('manage transaction')) {

            $filter['account']  = __('All');
            $filter['category'] = __('All');

            $account = BankAccount::where('created_by', '=', Auth::user()->creatorId())->get()->pluck('holder_name', 'id');
            $account->prepend(__('Stripe / Paypal'), 'strip-paypal');
            $account->prepend('Select Account', '');

            $accounts = Transaction::select('bank_accounts.id', 'bank_accounts.holder_name', 'bank_accounts.bank_name')
                ->leftjoin('bank_accounts', 'transactions.account', '=', 'bank_accounts.id')
                ->groupBy('transactions.account')->selectRaw('sum(amount) as total');

            $category = ProductServiceCategory::where('created_by', '=', Auth::user()->creatorId())->whereIn(
                'type',
                [
                    1,
                    2,
                ]
            )->get()->pluck('name', 'name');

            $category->prepend('Invoice', 'Invoice');
            $category->prepend('Bill', 'Bill');
            $category->prepend('Select Category', '');

            $transactions = Transaction::orderBy('id', 'desc');

            if (!empty($request->start_month) && !empty($request->end_month)) {
                $start = strtotime($request->start_month);
                $end   = strtotime($request->end_month);
            } else {
                $start = strtotime(date('Y-m'));
                $end   = strtotime(date('Y-m', strtotime("-5 month")));
            }

            $currentdate = $start;

            while ($currentdate <= $end) {
                $data['month'] = date('m', $currentdate);
                $data['year']  = date('Y', $currentdate);

                $transactions->Orwhere(
                    function ($query) use ($data) {
                        $query->whereMonth('date', $data['month'])->whereYear('date', $data['year']);
                        $query->where('transactions.created_by', '=', Auth::user()->creatorId());
                    }
                );

                $accounts->Orwhere(
                    function ($query) use ($data) {
                        $query->whereMonth('date', $data['month'])->whereYear('date', $data['year']);
                        $query->where('transactions.created_by', '=', Auth::user()->creatorId());
                    }
                );

                $currentdate = strtotime('+1 month', $currentdate);
            }

            $filter['startDateRange'] = date('M-Y', $start);
            $filter['endDateRange']   = date('M-Y', $end);


            if (!empty($request->account)) {
                $transactions->where('account', $request->account);

                if ($request->account == 'strip-paypal') {
                    $accounts->where('account', 0);
                    $filter['account'] = __('Stripe / Paypal');
                } else {
                    $accounts->where('account', $request->account);
                    $bankAccount       = BankAccount::find($request->account);
                    $filter['account'] = !empty($bankAccount) ? $bankAccount->holder_name . ' - ' . $bankAccount->bank_name : '';
                    if ($bankAccount->holder_name == 'Cash') {
                        $filter['account'] = 'Cash';
                    }
                }
            }
            if (!empty($request->category)) {
                $transactions->where('category', $request->category);
                $accounts->where('category', $request->category);

                $filter['category'] = $request->category;
            }

            $transactions->where('created_by', '=', Auth::user()->creatorId());
            $accounts->where('transactions.created_by', '=', Auth::user()->creatorId());
            $transactions = $transactions->with(['bankAccount'])->get();
            $accounts     = $accounts->get();

            return view('transaction.index', compact('transactions', 'account', 'category', 'filter', 'accounts'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function incomeSummary(Request $request)
    {
        if (\Auth::user()->can('income report')) {
            $account = BankAccount::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('holder_name', 'id');
            $account->prepend('All', '');
            $customer = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customer->prepend('Select Customer', '');
            $category = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 'income')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');

            if ($request->period === 'quarterly') {
                $month = [
                    'January-March',
                    'April-June',
                    'July-September',
                    'Octomber-December',
                ];
            } elseif ($request->period === 'half-yearly') {
                $month = [
                    'January-June',
                    'July-December',
                ];
            } elseif ($request->period === 'yearly') {
                $month = array_values(array_reverse($this->yearList()));
            } else {
                $month = $this->yearMonth();
            }
            $periods = Budget::$period;

            $data['monthList'] = $month;
            $data['yearList'] = $this->yearList();
            $data['periods'] = $periods;
            $filter['category'] = __('All');
            $filter['customer'] = __('All');

            if ($request->period === 'yearly') {
                $year = array_reverse($this->yearList());
                $yearList = [];
                foreach ($year as $value) {
                    $yearList[$value] = $value;
                }
            } else {
                $yearList[($request->year) ? $request->year : date('Y')] = ($request->year) ? $request->year : date('Y');
            }

            if (isset($request->year)) {
                $year = $request->year;
            } else {
                $year = date('Y');
            }
            if (isset($request->period)) {
                $period = $request->period;
            } else {
                $period = 'monthly';
            }
            $data['currentYear'] = $year;

            // ------------------------------REVENUE INCOME-----------------------------------

            $incomes = Revenue::selectRaw('sum(revenues.amount) as amount,MONTH(date) as month,YEAR(date) as year, product_service_categories.name as category_id')->leftjoin('product_service_categories', 'revenues.category_id', '=', 'product_service_categories.id')->where('product_service_categories.type', '=', 'income');
            $incomes->where('revenues.created_by', '=', \Auth::user()->creatorId());
            if ($request->period != 'yearly') {
                $incomes->whereRAW('YEAR(date) =?', [$year]);
            }

            if (!empty($request->category)) {
                $incomes->where('category_id', '=', $request->category);
                $cat = ProductServiceCategory::find($request->category);
                $filter['category'] = !empty($cat) ? $cat->name : '';
            }

            if (!empty($request->customer)) {
                $incomes->where('customer_id', '=', $request->customer);
                $cust = Customer::find($request->customer);
                $filter['customer'] = !empty($cust) ? $cust->name : '';
            }

            $incomes->groupBy('month', 'year', 'category_id');
            $incomes = $incomes->get();

            $tmpArray = [];
            foreach ($incomes as $income) {
                $tmpArray[$income->category_id][$income->year][$income->month] = $income->amount;
            }
            $array = [];

            foreach ($tmpArray as $key => $yearData) {
                $array[$key] = [];

                foreach ($yearList as $targetYear) {
                    $array[$key][$targetYear] = [];

                    for ($i = 1; $i <= 12; $i++) {
                        $array[$key][$targetYear][$i] = 0;
                    }

                    if (isset($yearData[$targetYear])) {
                        foreach ($yearData[$targetYear] as $month => $value) {
                            $array[$key][$targetYear][$month] = (float) $value; // Convert the value to float if needed
                        }
                    }
                }
            }

            //---------------------------INVOICE INCOME-----------------------------------------------

            $invoices = Invoice::selectRaw('MONTH(send_date) as month,YEAR(send_date) as year,product_service_categories.name as category_id,invoice_id,invoices.id')
                ->leftjoin('product_service_categories', 'invoices.category_id', '=', 'product_service_categories.id')
                ->where('invoices.created_by', \Auth::user()->creatorId())->where('status', '!=', 0);

            if ($request->period != 'yearly') {
                $invoices->whereRAW('YEAR(send_date) =?', [$year]);
            }

            if (!empty($request->customer)) {
                $invoices->where('customer_id', '=', $request->customer);
            }

            if (!empty($request->category)) {
                $invoices->where('category_id', '=', $request->category);
            }

            $invoices = $invoices->get();

            // ------------------------------------------ invoice ------------------------------------------

            $invoiceTmpArray = [];

            foreach ($invoices as $invoice) {
                $invoiceTmpArray[$invoice->category_id][$invoice->year][$invoice->month][] = $invoice->getTotal();
            }

            $invoiceArray = [];

            foreach ($invoiceTmpArray as $key => $yearData) {
                $invoiceArray[$key] = [];

                foreach ($yearList as $targetYear) {
                    $invoiceArray[$key][$targetYear] = [];

                    for ($i = 1; $i <= 12; $i++) {
                        $invoiceArray[$key][$targetYear][$i] = 0;
                    }

                    if (isset($yearData[$targetYear])) {
                        foreach ($yearData[$targetYear] as $month => $values) {
                            if (is_array($values)) {
                                $sum = array_sum($values);
                                $invoiceArray[$key][$targetYear][$month] = $sum;
                            } else {
                                $invoiceArray[$key][$targetYear][$month] = (float) $values;
                            }
                        }
                    }
                }
            }

            $invoicesum = Utility::billInvoiceData($invoiceArray, $request, $yearList);

            $invoiceTotalArray = [];

            foreach ($invoices as $invoice) {
                $invoiceTotalArray[$invoice->year][$invoice->month][] = $invoice->getTotal();
            }
            // ------------------------------------------ income ------------------------------------------

            $incomeArr = [];
            $invoiceArr = [];
            $incomesum = [];

            foreach ($yearList as $year) {
                $invoiceArr[$year] = [];

                for ($i = 1; $i <= 12; $i++) {
                    $invoiceArr[$year][$i] = 0;
                }

                if (isset($invoiceTotalArray[$year])) {
                    foreach ($invoiceTotalArray[$year] as $month => $values) {
                        $invoiceArr[$year][$month] = array_sum($values);
                    }
                }
            }


            foreach ($array as $key => $categoryData) {

                $incomesum[] = Utility::revenuePaymentData($key, $categoryData, $request, $yearList);
            }

            $revenueTotalArray = [];

            foreach ($incomes as $income) {
                $revenueTotalArray[$income->year][$income->month][] = $income->amount;
            }

            foreach ($yearList as $year) {
                $incomeArr[$year] = [];

                for ($i = 1; $i <= 12; $i++) {
                    $incomeArr[$year][$i] = 0;
                }

                if (isset($revenueTotalArray[$year])) {
                    foreach ($revenueTotalArray[$year] as $month => $values) {
                        $incomeArr[$year][$month] = array_sum($values);
                    }
                }
            }


            $chartIncomeArr = Utility::totalData($invoiceArr, $incomeArr, $request, $yearList);


            $data['chartIncomeArr'] = $chartIncomeArr;
            $data['incomeArr'] = $incomesum;
            $data['invoiceArray'] = $invoicesum;
            $data['account'] = $account;
            $data['customer'] = $customer;
            $data['category'] = $category;
            $filter['startDateRange'] = 'Jan-' . $year;
            $filter['endDateRange'] = 'Dec-' . $year;

            return view('report.income_summary', compact('filter', 'category'), $data);
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function allSales(Request $request)
    {
        if (!Auth::user()->can('manage transaction')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $user       = Auth::user();
        $companyId  = $user->creatorId();
        $userId     = $user->id;
        $ownedById  = method_exists($user, 'ownedId') ? $user->ownedId() : $userId;

        $createdByScope = array_values(array_unique([$companyId, $userId]));

        // Filters
        $type       = $request->get('type', 'all');
        $customer   = $request->get('customer', '');
        $startMonth = $request->get('start_month', date('Y-m'));
        $endMonth   = $request->get('end_month', date('Y-m'));

        // Date range
        $start = Carbon::parse($startMonth . '-01')->startOfMonth();
        $end   = Carbon::parse($endMonth . '-01')->endOfMonth();
        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }
        $dateFilter = [$start->toDateString(), $end->toDateString()];

        $typeOptions = [
            'all'          => __('All Sales'),
            'invoices'     => __('Invoices'),
            'estimates'    => __('Estimates'),
            'revenue'      => __('Revenue'),
            'credit_notes' => __('Credit Notes'),
        ];

        // Get customers for dropdown
        $customers = Customer::where('created_by', $companyId)->get()->pluck('name', 'id');
        $customers->prepend('Select Customer', '');

        // Calculate sales data for overview
        $salesData = $this->calculateSalesData($createdByScope, $ownedById, $dateFilter, $customer);

        $rows = collect();

        // Invoices (created_by might be company OR user)
        if ($type === 'all' || $type === 'invoices') {
            $invoices = Invoice::whereIn('created_by', $createdByScope)
                ->whereBetween('issue_date', $dateFilter)
                ->when($customer, fn($q) => $q->where('customer_id', $customer))
                ->with('customer')
                ->get();

            foreach ($invoices as $inv) {
                $amount = method_exists($inv, 'getTotal') ? $inv->getTotal() : ($inv->total ?? 0);

                $statusText = '-';
                if (method_exists($inv, 'getDue')) {
                    if ($inv->getDue() <= 0) {
                        $statusText = __('Closed');
                    } elseif (!empty($inv->due_date)) {
                        $d = Carbon::parse($inv->due_date);
                        $diff = $d->diffInDays(now(), false);
                        if ($diff < 0)      $statusText = __('Due in :d days', ['d' => abs($diff)]);
                        elseif ($diff == 0) $statusText = __('Due today');
                        else                $statusText = __('Overdue :d days', ['d' => $diff]);
                    }
                }

                $rows->push([
                    'date'      => $inv->issue_date,
                    'type'      => __('Invoice'),
                    'customer'  => optional($inv->customer)->name ?: '-',
                    'memo'      => $inv->reference ?? '',
                    'amount'    => $amount,
                    'status'    => $statusText,
                    'view_url'  => route('invoice.show', Crypt::encrypt($inv->id)),
                    'edit_url'  => route('invoice.edit', Crypt::encrypt($inv->id)),
                ]);
            }
        }

        // Estimates (Proposals) – allow created_by in (company,user) OR owned_by = staff
        if ($type === 'all' || $type === 'estimates') {
            $proposals = Proposal::where(function ($q) use ($createdByScope, $ownedById) {
                $q->whereIn('created_by', $createdByScope)
                    ->orWhere('owned_by', $ownedById);
            })
                ->when($dateFilter, fn($q) => $q->whereBetween('issue_date', $dateFilter))
                ->when($customer, fn($q) => $q->where('customer_id', $customer))
                ->with(['customer', 'items'])
                ->get();

            foreach ($proposals as $p) {
                $amount = 0;
                foreach ($p->items as $it) {
                    $line = ($it->price * $it->quantity) - (float)($it->discount ?? 0);
                    $amount += $line;
                    $taxes = \App\Models\Utility::tax($it->tax);
                    if (!empty($taxes)) {
                        foreach ($taxes as $t) {
                            if ($t === null) continue;
                            $amount += \App\Models\Utility::taxRate($t->rate, $it->price, $it->quantity, $it->discount);
                        }
                    }
                }

                $rows->push([
                    'date'      => $p->issue_date,
                    'type'      => __('Estimate'),
                    'customer'  => optional($p->customer)->name ?: '-',
                    'memo'      => '',
                    'amount'    => $amount,
                    'status'    => __(\App\Models\Proposal::$statues[$p->status] ?? '-'),
                    'view_url'  => route('proposal.show', Crypt::encrypt($p->id)),
                    'edit_url'  => route('proposal.edit', Crypt::encrypt($p->id)),
                ]);
            }
        }

        // Revenue – same scoping as proposals
        if ($type === 'all' || $type === 'revenue') {
            $revenues = Revenue::where(function ($q) use ($createdByScope, $ownedById) {
                $q->whereIn('created_by', $createdByScope)
                    ->orWhere('owned_by', $ownedById);
            })
                ->when($dateFilter, fn($q) => $q->whereBetween('date', $dateFilter))
                ->when($customer, fn($q) => $q->where('customer_id', $customer))
                ->with('customer')
                ->get();

            foreach ($revenues as $r) {
                $rows->push([
                    'date'      => $r->date,
                    'type'      => __('Revenue'),
                    'customer'  => optional($r->customer)->name ?: '-',
                    'memo'      => $r->reference ?: $r->description,
                    'amount'    => $r->amount,
                    'status'    => __('Closed'),
                    'view_url'  => route('revenue.index') . '?highlight=' . $r->id,
                    'edit_url'  => route('revenue.edit', $r->id),
                ]);
            }
        }

        // Credit notes – via invoice's created_by (company OR user)
        if ($type === 'all' || $type === 'credit_notes') {
            $credits = CreditNote::query()
                ->when($dateFilter, fn($q) => $q->whereBetween('date', $dateFilter))
                ->whereHas('invoice', function ($q) use ($createdByScope, $customer) {
                    $q->whereIn('created_by', $createdByScope);
                    if ($customer) {
                        $q->where('customer_id', $customer);
                    }
                })
                ->with(['invoice.customer'])
                ->get();

            foreach ($credits as $c) {
                $rows->push([
                    'date'      => $c->date,
                    'type'      => __('Credit Note'),
                    'customer'  => optional(optional($c->invoice)->customer)->name ?: '-',
                    'memo'      => $c->description,
                    'amount'    => -1 * $c->amount,
                    'status'    => __('Closed'),
                    'view_url'  => route('invoice.show', Crypt::encrypt($c->invoice)),
                    'edit_url'  => route('invoice.edit.credit.note', [$c->invoice, $c->id]),
                ]);
            }
        }

        $rows = $rows->sortByDesc('date')->values();

        return view('transaction.allSales', [
            'rows'         => $rows,
            'typeOptions'  => $typeOptions,
            'selectedType' => $type,
            'customers'    => $customers,
            'selectedCustomer' => $customer,
            'start_month'  => $startMonth,
            'end_month'    => $endMonth,
            'salesData'    => $salesData,
        ]);
    }

    private function calculateSalesData($createdByScope, $ownedById, $dateFilter, $customer = null)
    {
        // Get estimates data
        $estimates = Proposal::where(function ($q) use ($createdByScope, $ownedById) {
            $q->whereIn('created_by', $createdByScope)->orWhere('owned_by', $ownedById);
        })
            ->whereBetween('issue_date', $dateFilter)
            ->when($customer, fn($q) => $q->where('customer_id', $customer))
            ->with(['items'])->get();

        $estimatesAmount = 0;
        foreach ($estimates as $p) {
            foreach ($p->items as $it) {
                $line = ($it->price * $it->quantity) - (float)($it->discount ?? 0);
                $estimatesAmount += $line;
                $taxes = \App\Models\Utility::tax($it->tax);
                if (!empty($taxes)) {
                    foreach ($taxes as $t) {
                        if ($t === null) continue;
                        $estimatesAmount += \App\Models\Utility::taxRate($t->rate, $it->price, $it->quantity, $it->discount);
                    }
                }
            }
        }

        // Get invoices data
        $invoices = Invoice::whereIn('created_by', $createdByScope)
            ->whereBetween('issue_date', $dateFilter)
            ->when($customer, fn($q) => $q->where('customer_id', $customer))
            ->get();

        $overdueInvoices = Invoice::whereIn('created_by', $createdByScope)
            ->where('due_date', '<', now())
            ->whereNotIn('status', [4])
            ->when($customer, fn($q) => $q->where('customer_id', $customer))
            ->get();

        $openInvoices = Invoice::whereIn('created_by', $createdByScope)
            ->whereIn('status', [0, 1, 2, 3])
            ->when($customer, fn($q) => $q->where('customer_id', $customer))
            ->get();

        $paidInvoices = Invoice::whereIn('created_by', $createdByScope)
            ->where('status', 4)
            ->whereBetween('issue_date', $dateFilter)
            ->when($customer, fn($q) => $q->where('customer_id', $customer))
            ->get();

        // Get revenue data
        $revenues = Revenue::where(function ($q) use ($createdByScope, $ownedById) {
            $q->whereIn('created_by', $createdByScope)->orWhere('owned_by', $ownedById);
        })
            ->whereBetween('date', $dateFilter)
            ->when($customer, fn($q) => $q->where('customer_id', $customer))
            ->get();

        // Get credit notes data
        $credits = CreditNote::whereBetween('date', $dateFilter)
            ->whereHas('invoice', function ($q) use ($createdByScope, $customer) {
                $q->whereIn('created_by', $createdByScope);
                if ($customer) {
                    $q->where('customer_id', $customer);
                }
            })->get();

        return [
            'estimates' => [
                'amount' => $estimatesAmount,
                'count' => $estimates->count()
            ],
            'unbilled' => [
                'amount' => $revenues->sum('amount'),
                'count' => $revenues->count()
            ],
            'overdue' => [
                'amount' => $overdueInvoices->sum(function ($inv) {
                    return method_exists($inv, 'getDue') ? $inv->getDue() : ($inv->total ?? 0);
                }),
                'count' => $overdueInvoices->count()
            ],
            'open' => [
                'amount' => $openInvoices->sum(function ($inv) {
                    return method_exists($inv, 'getDue') ? $inv->getDue() : ($inv->total ?? 0);
                }),
                'count' => $openInvoices->count()
            ],
            'paid' => [
                'amount' => $paidInvoices->sum(function ($inv) {
                    return method_exists($inv, 'getTotal') ? $inv->getTotal() : ($inv->total ?? 0);
                }),
                'count' => $paidInvoices->count()
            ],
            'invoices' => [
                'amount' => $invoices->sum(function ($inv) {
                    return method_exists($inv, 'getTotal') ? $inv->getTotal() : ($inv->total ?? 0);
                }),
                'count' => $invoices->count()
            ],
            'revenue' => [
                'amount' => $revenues->sum('amount'),
                'count' => $revenues->count()
            ],
            'credits' => [
                'amount' => $credits->sum('amount'),
                'count' => $credits->count()
            ]
        ];
    }



    public function bankTransactions(Request $request)
    {
        if (!Auth::user()->can('manage transaction')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $filter = [
            'account'  => __('All'),
            'category' => __('All'),
            'bank'     => '',
        ];

        $allTransactions = Transaction::where('created_by', Auth::user()->creatorId())->get();

        $allBanks = BankAccount::where('created_by', Auth::user()->creatorId())
            ->orderBy('bank_name')
            ->get();

        // Banks dropdown - include accounts with empty bank names too
        $banks = BankAccount::where('created_by', Auth::user()->creatorId())
            ->select('bank_name', 'institution_name', 'holder_name')
            ->get()
            ->map(function ($account) {
                // Use institution_name if bank_name is empty, or holder_name as fallback
                $displayName = !empty($account->bank_name) ? $account->bank_name : (!empty($account->institution_name) ? $account->institution_name : $account->holder_name);
                return $displayName;
            })
            ->unique()
            ->filter() // Remove empty values
            ->sort()
            ->flip()
            ->map(function ($value, $key) {
                return $key;
            }); // Create key-value pairs

        $banks->prepend(__('Select Bank'), '');

        // Auto-select first bank if no bank is selected
        $selectedBank = $request->filled('bank') ? $request->bank : $banks->keys()->skip(1)->first();
        if ($selectedBank) {
            $filter['bank'] = $selectedBank;
        }

        // Categories
        $category = ProductServiceCategory::where('created_by', Auth::user()->creatorId())
            ->whereIn('type', [1, 2])->pluck('name', 'name');
        $category->prepend('Invoice', 'Invoice');
        $category->prepend('Bill', 'Bill');
        $category->prepend(__('Select Category'), '');

        $transactions = Transaction::orderBy('id', 'desc');

        // Summary-by-account (for the top total cards you already have)
        $accounts = Transaction::select('bank_accounts.id', 'bank_accounts.holder_name', 'bank_accounts.bank_name')
            ->leftJoin('bank_accounts', 'transactions.account', '=', 'bank_accounts.id')
            ->groupBy('transactions.account')
            ->selectRaw('sum(amount) as total');

        // Date range
        if ($request->filled('start_month') && $request->filled('end_month')) {
            $start = strtotime($request->start_month);
            $end   = strtotime($request->end_month);
        } else {
            $start = strtotime(date('Y-m', strtotime('-5 months')));
            $end   = strtotime(date('Y-m'));
        }
        $filter['startDateRange'] = date('M-Y', $start);
        $filter['endDateRange']   = date('M-Y', $end);

        $current = $start;
        while ($current <= $end) {
            $m = date('m', $current);
            $y = date('Y', $current);

            $transactions->orWhere(function ($q) use ($m, $y) {
                $q->whereMonth('date', $m)->whereYear('date', $y)
                    ->where('transactions.created_by', Auth::user()->creatorId());
            });

            $accounts->orWhere(function ($q) use ($m, $y) {
                $q->whereMonth('date', $m)->whereYear('date', $y)
                    ->where('transactions.created_by', Auth::user()->creatorId());
            });

            $current = strtotime('+1 month', $current);
        }

        // Filter by selected bank (use auto-selected or user-selected bank)
        if ($filter['bank']) {
            // Don't filter transactions by bank - only use bank to show sub-accounts
            $accounts->where(function ($q) use ($filter) {
                $q->where('bank_accounts.bank_name', $filter['bank'])
                    ->orWhere('bank_accounts.institution_name', $filter['bank'])
                    ->orWhere('bank_accounts.holder_name', $filter['bank']);
            });
        }

        // Filter by account subtype (comes from card click) - THIS is what filters transactions
        if ($request->filled('account')) {
            $transactions->whereHas('bankAccount', function ($q) use ($request) {
                $q->where('account_subtype', $request->account);
            });
            $accounts->whereHas('bankAccount', function ($q) use ($request) {
                $q->where('account_subtype', $request->account);
            });
            $filter['account'] = $request->account;
        } else {
            // If no sub-account is selected, don't show any transactions
            if ($filter['bank']) {
                $transactions->where('id', 0); // This will return no transactions
            }
        }

        $transactions->where('created_by', Auth::user()->creatorId());
        $accounts->where('transactions.created_by', Auth::user()->creatorId());

        $transactions = $transactions->with(['bankAccount'])->get();
        $accounts     = $accounts->get();

        // Sub-accounts ONLY for the selected bank
        $subAccountGroups = collect();
        if ($filter['bank']) {
            $from = \Carbon\Carbon::createFromTimestamp($start)->startOfMonth();
            $to   = \Carbon\Carbon::createFromTimestamp($end)->endOfMonth();

            $subAccounts = BankAccount::query()
                ->select('bank_accounts.account_subtype', 'bank_accounts.bank_name', 'bank_accounts.institution_name', 'bank_accounts.holder_name')
                ->selectRaw('COUNT(DISTINCT bank_accounts.id) as account_count')
                ->where('bank_accounts.created_by', Auth::user()->creatorId())
                ->where(function ($q) use ($filter) {
                    $q->where('bank_accounts.bank_name', $filter['bank'])
                        ->orWhere('bank_accounts.institution_name', $filter['bank'])
                        ->orWhere('bank_accounts.holder_name', $filter['bank']);
                })
                ->whereNotNull('bank_accounts.account_subtype')
                ->leftJoin('transactions', function ($join) use ($from, $to) {
                    $join->on('transactions.account', '=', 'bank_accounts.id')
                        ->whereBetween('transactions.date', [$from->toDateString(), $to->toDateString()])
                        ->where('transactions.created_by', Auth::user()->creatorId());
                })
                ->groupBy('bank_accounts.account_subtype', 'bank_accounts.bank_name', 'bank_accounts.institution_name', 'bank_accounts.holder_name')
                ->selectRaw('COALESCE(SUM(transactions.amount), 0) as total')
                ->selectRaw('COUNT(transactions.id) as txn_count')
                ->orderBy('bank_accounts.account_subtype')
                ->get();

            // Transform to match your blade expectations
            $subAccounts = $subAccounts->map(function ($item) {
                return (object) [
                    'id' => $item->account_subtype, // Use subtype as identifier
                    'holder_name' => $item->account_subtype, // Display subtype name
                    'bank_name' => $item->bank_name,
                    'total' => $item->total,
                    'txn_count' => $item->txn_count,
                    'account_count' => $item->account_count
                ];
            });

            $subAccountGroups = collect([$filter['bank'] => $subAccounts]);
        }

        return view('transaction.bankTransactions', compact(
            'transactions',
            'banks',
            'category',
            'filter',
            'accounts',
            'subAccountGroups',
            'allBanks'
        ));
    }



    public function receipts(Request $request, $invoiceId = null)
    {
        $invoiceId = $invoiceId ?? $request->get('invoice_id');

        $q = InvoicePayment::query()->with([
            'invoice:id,invoice_id,customer_id',
            'bankAccount:id,bank_name,holder_name',
        ]);

        if (!empty($invoiceId)) {
            $q->where('invoice_id', $invoiceId);
        }

        if ($request->filled('start_month')) {
            $q->whereDate('date', '>=', Carbon::parse($request->start_month . '-01')->startOfMonth());
        }
        if ($request->filled('end_month')) {
            $q->whereDate('date', '<=', Carbon::parse($request->end_month . '-01')->endOfMonth());
        }

        $receipts = $q->orderByDesc('date')->paginate(25)->withQueryString();
        $invoiceOptions = Invoice::orderByDesc('id')->pluck('invoice_id', 'id');

        return view('transaction.reciepts', [
            'receipts'        => $receipts,
            'invoiceOptions'  => $invoiceOptions,
            'selectedInvoice' => $invoiceId,
        ]);
    }

    //for export in transaction report
    public function export()
    {
        $name = 'transaction_' . date('Y-m-d i:h:s');
        $data = Excel::download(new TransactionExport(), $name . '.xlsx');

        return $data;
    }

    public function recurringTrans()
    {
        return view('transaction.reccuringtrans');
    }
}
