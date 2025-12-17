<?php

namespace App\Http\Controllers;

use App\DataTables\InventoryValuationSummaryDataTable;
use App\DataTables\IncomeByCustomerSummaryDataTable;
use App\DataTables\IncomeByCustomerSummaryTwoDataTable;
use App\DataTables\SalesByProductServiceSummaryDataTable;
use App\DataTables\SalesByProductServiceDetailDataTable;
use App\DataTables\TransactionListByCustomerDataTable;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountType;
use App\Models\CustomField;
use App\Exports\ProductServiceExport;
use App\Imports\ProductServiceImport;
use App\Models\Product;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\ProductServiceUnit;
use App\Models\Tax;
use App\Models\User;
use App\Models\Utility;
use App\Models\Vender;
use App\Models\WarehouseProduct;
use Google\Service\Dataproc\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;




class ProductServiceController extends Controller
{
    public function index(Request $request)
    {
        if (!\Auth::user()->can('manage product & service')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        $user = \Auth::user();
        if ($user->type == 'company') {
            $ownerId = $user->creatorId();
        } else {
            $ownerId = $user->ownedId();
        }
        $category = ProductServiceCategory::where('created_by', $ownerId)
            ->where('type', 'product & service')
            ->pluck('name', 'id')
            ->prepend('Select Category', '');

        $types = ProductService::$types;
        $types = array_merge(['' => 'Select Type'], $types);
        $productServicesQuery = ProductService::where('created_by', $ownerId)
            ->with(['category', 'unit']);
        if (!empty($request->category)) {
            $productServicesQuery->where('category_id', $request->category);
        }
        if (!empty($request->type)) {
            $productServicesQuery->where('type', $request->type);
        }
        $productServices = $productServicesQuery->get();
        return view('productservice.index', compact('productServices', 'category', 'types'));
    }

    public function inventoryValuationSummary(
        \App\DataTables\InventoryValuationSummaryDataTable $dataTable,
        \Illuminate\Http\Request $request
    ) {
        if (!\Auth::user()->can('manage product & service')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();

        // Dropdowns
        $category = \App\Models\ProductServiceCategory::where('created_by', $ownerId)
            ->where('type', 'product & service')
            ->pluck('name', 'id')
            ->prepend(__('Select Category'), '');

        $types = array_merge(['' => __('Select Type')], \App\Models\ProductService::$types);

        // Defaults for dates (current month, like ledger)
        $start = $request->get('start_date') ?: \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
        $end = $request->get('end_date') ?: \Carbon\Carbon::now()->format('Y-m-d');
        $reportPeriod = $request->get('report_period', 'this_month');

        $filter = [
            'startDateRange' => $start,
            'endDateRange' => $end,
            'reportPeriod' => $reportPeriod,
            'selectedCategory' => $request->get('category', ''),
            'selectedType' => $request->get('type', ''),
        ];

        // return $dataTable->render(
        //     'productservice.inventoryValuationSummary',
        //     compact('category', 'types', 'user', 'filter', 'reportPeriod')
        // );

        return $dataTable->render('sync.customerbalance.index', [ // ✅ keep same view, or create vendorbalance.index
            'pageTitle' => "Inventory Valuation Summary",
            'startDate' => $request->get('start_date', date('Y-01-01')),
            'endDate' => $request->get('end_date', date('Y-m-d', strtotime('+1 day')))
        ]);
    }

    public function incomeByCustomerSummary(
        \App\DataTables\IncomeByCustomerSummaryDataTable $dataTable,
        \Illuminate\Http\Request $request
    ) {
        if (!\Auth::user()->can('manage product & service')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();

        // Page title
        $pageTitle = __('Product/Service List');

        // Dropdowns for filters
        $category = \App\Models\ProductServiceCategory::where('created_by', $ownerId)
            ->where('type', 'product & service')
            ->pluck('name', 'id')
            ->prepend(__('Select Category'), '');

        $types = array_merge(['' => __('Select Type')], \App\Models\ProductService::$types);

        $filter = [
            'selectedCategory' => $request->get('category', ''),
            'selectedType' => $request->get('type', ''),
        ];

        // return $dataTable->render(
        //     'productservice.incomeByCustomerSummary',
        //     compact('pageTitle', 'category', 'types', 'user', 'filter')
        // );

        return $dataTable->render('sync.simpletable.index', [ // ✅ keep same view, or create vendorbalance.index
            'pageTitle' => $pageTitle,
            'startDate' => $request->get('start_date', date('Y-01-01')),
            'endDate' => $request->get('end_date', date('Y-m-d', strtotime('+1 day')))
        ]);
    }

    public function incomeByCustomerSummaryTwo(
        \App\DataTables\IncomeByCustomerSummaryTwoDataTable $dataTable,   // <-- use *Two* here
        \Illuminate\Http\Request $request
    ) {
        if (!\Auth::user()->can('manage product & service')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

        $pageTitle = __('Income By Customer Summary');

        // Get all customers for the dropdown filter
        $customers = \App\Models\Customer::where($column, $ownerId)
            ->where('is_active', 1)
            ->whereNotNull('name')
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values();

        $filter = [
            'selectedCustomerName' => $request->get('customer_name', ''),
            'reportPeriod' => $request->get('report_period', 'all_dates'),
            'startDateRange' => $request->get('start_date', ''),
            'endDateRange' => $request->get('end_date', ''),
            'accountingMethod' => $request->get('accounting_method', 'accrual'),
        ];

        // Pass Customer model to DataTable
        // return $dataTable->render(
        //     'productservice.incomeByCustomerSummaryTwo',
        //     compact('pageTitle', 'customers', 'user', 'filter')
        // );
        return $dataTable->render('sync.customerbalance.index', [ // ✅ keep same view, or create vendorbalance.index
            'pageTitle' => "Income By Customer Summary",
            'startDate' => $request->get('start_date', date('Y-01-01')),
            'endDate' => $request->get('end_date', date('Y-m-d', strtotime('+1 day')))
        ]);
    }

    public function SalesByProductServiceDetail(
        \App\DataTables\SalesByProductServiceDetailDataTable $dataTable,
        \Illuminate\Http\Request $request
    ) {
        if (!\Auth::user()->can('manage product & service')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

        $pageTitle = __('Sales by Product/Service Detail');

        // Get all product/service categories for the dropdown filter
        $categories = \App\Models\ProductServiceCategory::where($column, $ownerId)
            ->where('type', 'product & service')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->prepend(__('All Categories'), '');

        // Get product types
        $types = array_merge(['' => __('All Types')], \App\Models\ProductService::$types);

        // Get all customers for filtering
        $customers = \App\Models\Customer::where($column, $ownerId)
            ->where('is_active', 1)
            ->whereNotNull('name')
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values();

        $filter = [
            'selectedCategory' => $request->get('category', ''),
            'selectedType' => $request->get('type', ''),
            'selectedProductName' => $request->get('product_name', ''),
            'selectedCustomerName' => $request->get('customer_name', ''),
            'reportPeriod' => $request->get('report_period', 'all_dates'),
            'startDateRange' => $request->get('start_date', ''),
            'endDateRange' => $request->get('end_date', ''),
            'accountingMethod' => $request->get('accounting_method', 'accrual'),
        ];

        // return $dataTable->render(
        //     'productservice.salesByProductServiceDetail',
        //     compact('pageTitle', 'categories', 'types', 'customers', 'user', 'filter')
        // );

        return $dataTable->render('sync.DoubleDateReport.index', [ 
            'pageTitle' => $pageTitle,
            'startDate' => $request->get('start_date', date('Y-01-01')),
            'endDate' => $request->get('end_date', date('Y-m-d', strtotime('+1 day')))
        ]);

    }

    public function SalesByProductServiceSummary(
        \App\DataTables\SalesByProductServiceSummaryDataTable $dataTable,
        \Illuminate\Http\Request $request
    ) {
        if (!\Auth::user()->can('manage product & service')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

        $pageTitle = __('Sales by Product/Service Summary');

        // Get all product/service categories for the dropdown filter
        $categories = \App\Models\ProductServiceCategory::where($column, $ownerId)
            ->where('type', 'product & service')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->prepend(__('All Categories'), '');

        // Get product types
        $types = array_merge(['' => __('All Types')], \App\Models\ProductService::$types);

        $filter = [
            'selectedCategory' => $request->get('category', ''),
            'selectedType' => $request->get('type', ''),
            'selectedProductName' => $request->get('product_name', ''),
            'reportPeriod' => $request->get('report_period', 'all_dates'),
            'startDateRange' => $request->get('start_date', ''),
            'endDateRange' => $request->get('end_date', ''),
            'accountingMethod' => $request->get('accounting_method', 'accrual'),
        ];

        return $dataTable->render(
            'productservice.salesByProductServiceSummary',
            compact('pageTitle', 'categories', 'types', 'user', 'filter')
        );
    }

    /**
     * New method for Transaction List by Customer report
     */
    public function transactionListByCustomer(
        \App\DataTables\TransactionListByCustomerDataTable $dataTable,
        \Illuminate\Http\Request $request
    ) {
        if (!\Auth::user()->can('manage product & service')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

        $pageTitle = __('Transaction List by Customer');

        // Get all customers for the dropdown filter
        $customers = \App\Models\Customer::where($column, $ownerId)
            ->where('is_active', 1)
            ->whereNotNull('name')
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values();

        // Get transaction types for filter
        $transactionTypes = [
            '' => __('All Transaction Types'),
            'Invoice' => __('Invoice'),
            'Invoice Payment' => __('Payment'),
            'Revenue' => __('Deposit'),
            'Payment' => __('Expense'),
            'Expense' => __('Expense'),
            'Bill' => __('Bill'),
            'Bill Payment' => __('Bill Payment'),
            'Credit Note' => __('Credit Memo'),
        ];

        // Default to this month
        $start = $request->get('start_date') ?: \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
        $end = $request->get('end_date') ?: \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d');

        $filter = [
            'selectedCustomerName' => $request->get('customer_name', ''),
            'selectedTransactionType' => $request->get('transaction_type', ''),
            'startDateRange' => $start,
            'endDateRange' => $end,
        ];

        return $dataTable->render(
            'productservice.transactionListByCustomer',
            compact('pageTitle', 'customers', 'transactionTypes', 'user', 'filter')
        );
    }

    /**
     * New method for Estimates by Customer report
     */
    public function estimatesByCustomer(
        \App\DataTables\EstimatesByCustomerDataTable $dataTable,
        \Illuminate\Http\Request $request
    ) {
        if (!\Auth::user()->can('manage product & service')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $user = \Auth::user();
        $ownerId = $user->type == 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

        $pageTitle = __('Estimates by Customer');

        // Get all customers for the dropdown filter
        $customers = \App\Models\Customer::where($column, $ownerId)
            ->where('is_active', 1)
            ->whereNotNull('name')
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values();

        // Get proposal statuses for filter
        $statuses = [
            '' => __('All Statuses'),
            '0' => __('Draft'),
            '1' => __('Open'),
            '2' => __('Accepted'),
            '3' => __('Declined'),
            '4' => __('Close'),
        ];

        $filter = [
            'selectedCustomerName' => $request->get('customer_name', ''),
            'selectedStatus' => $request->get('status', ''),
            'startDateRange' => $request->get('start_date', ''),
            'endDateRange' => $request->get('end_date', ''),
        ];

        // return $dataTable->render(
        //     'productservice.estimatesByCustomer',
        //     compact('pageTitle', 'customers', 'statuses', 'user', 'filter')
        // );

        return $dataTable->render('sync.simpletable.index', [ // ✅ keep same view, or create vendorbalance.index
            'pageTitle' => $pageTitle,
            'startDate' => $request->get('start_date', date('Y-01-01')),
            'endDate' => $request->get('end_date', date('Y-m-d', strtotime('+1 day')))
        ]);
    }

    /**
     * Same date logic as ledger. If you already have getReportPeriodDates(),
     * call that instead. Otherwise this inline helper covers common periods.
     */
    private function computePeriod(?string $reportPeriod, ?string $start, ?string $end): array
    {
        if (!empty($reportPeriod)) {
            // Map a few common keys; extend as needed.
            $today = \Carbon\Carbon::today();
            switch ($reportPeriod) {
                case 'today':
                    return [$today->copy()->format('Y-m-d'), $today->copy()->format('Y-m-d')];
                case 'this_month':
                    return [$today->copy()->startOfMonth()->format('Y-m-d'), $today->copy()->endOfMonth()->format('Y-m-d')];
                case 'last_month':
                    $s = $today->copy()->subMonthNoOverflow()->startOfMonth();
                    $e = $today->copy()->subMonthNoOverflow()->endOfMonth();
                    return [$s->format('Y-m-d'), $e->format('Y-m-d')];
                case 'this_quarter':
                    return [$today->copy()->startOfQuarter()->format('Y-m-d'), $today->copy()->endOfQuarter()->format('Y-m-d')];
                case 'this_year':
                    return [$today->copy()->startOfYear()->format('Y-m-d'), $today->copy()->endOfYear()->format('Y-m-d')];
                case 'all_dates':
                    return ['1900-01-01', $today->copy()->format('Y-m-d')];
                default:
                    // For other options coming from the ledger select, fall back to custom if provided
                    break;
            }
        }

        if (!empty($start) && !empty($end)) {
            return [$start, $end];
        }

        // Default: current month (like ledger)
        return [
            \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d'),
            \Carbon\Carbon::now()->format('Y-m-d'),
        ];
    }



    public function create()
    {
        if (\Auth::user()->can('create product & service')) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'product')->get();
            $category = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 'product & service')->get()->pluck('name', 'id');
            $category->prepend('➕  Add New', 'add_new');
            $unit = ProductServiceUnit::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $unit->prepend('➕  Add New', 'add_new');
            $tax = Tax::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $tax = $tax->prepend('Select Tax', '');
            $tax->prepend('➕  Add New', 'add_new');
            $incomeChartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name, chart_of_accounts.id as id'))
                ->leftjoin('chart_of_account_types', 'chart_of_account_types.id', 'chart_of_accounts.type')
                ->where('chart_of_account_types.name', 'income')
                ->where('parent', '=', 0)
                ->where('chart_of_accounts.created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            // $incomeChartAccounts->prepend('Select Account', 0);

            $incomeSubAccounts = ChartOfAccount::select(\DB::raw('CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name,chart_of_accounts.id, chart_of_accounts.code, chart_of_account_parents.account'));
            $incomeSubAccounts->leftjoin('chart_of_account_parents', 'chart_of_accounts.parent', 'chart_of_account_parents.id');
            $incomeSubAccounts->leftjoin('chart_of_account_types', 'chart_of_account_types.id', 'chart_of_accounts.type');
            $incomeSubAccounts->where('chart_of_account_types.name', 'income');
            $incomeSubAccounts->where('chart_of_accounts.parent', '!=', 0);
            $incomeSubAccounts->where('chart_of_accounts.created_by', \Auth::user()->creatorId());
            $incomeSubAccounts = $incomeSubAccounts->get()->toArray();


            $expenseChartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name, chart_of_accounts.id as id'))
                ->leftjoin('chart_of_account_types', 'chart_of_account_types.id', 'chart_of_accounts.type')
                ->whereIn('chart_of_account_types.name', ['Expenses', 'Costs of Goods Sold'])
                ->where('chart_of_accounts.created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            // $expenseChartAccounts->prepend('Select Account', '');

            $expenseSubAccounts = ChartOfAccount::select(\DB::raw('CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name,chart_of_accounts.id, chart_of_accounts.code, chart_of_account_parents.account'));
            $expenseSubAccounts->leftjoin('chart_of_account_parents', 'chart_of_accounts.parent', 'chart_of_account_parents.id');
            $expenseSubAccounts->leftjoin('chart_of_account_types', 'chart_of_account_types.id', 'chart_of_accounts.type');
            $expenseSubAccounts->whereIn('chart_of_account_types.name', ['Expenses', 'Costs of Goods Sold']);
            $expenseSubAccounts->where('chart_of_accounts.parent', '!=', 0);
            $expenseSubAccounts->where('chart_of_accounts.created_by', \Auth::user()->creatorId());
            $expenseSubAccounts = $expenseSubAccounts->get()->toArray();

            return view('productservice.create', compact('category', 'unit', 'tax', 'customFields', 'incomeChartAccounts', 'incomeSubAccounts', 'expenseChartAccounts', 'expenseSubAccounts'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {

        if (\Auth::user()->can('create product & service')) {

            $rules = [
                'name' => 'required',
                'sku' => [
                    'required',
                    Rule::unique('product_services')->where(function ($query) {
                        return $query->where('created_by', \Auth::user()->id);
                    })
                ],
                'sale_price' => 'required|numeric',
                'purchase_price' => 'required|numeric',
                'category_id' => 'required',
                'unit_id' => 'required',
                'type' => 'required',
            ];

            $validator = \Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->route('productservice.index')->with('error', $messages->first());
            }

            $productService = new ProductService();
            $productService->name = $request->name;
            $productService->description = $request->description;
            $productService->sku = $request->sku;
            $productService->sale_price = $request->sale_price;
            $productService->purchase_price = $request->purchase_price;
            $productService->tax_id = !empty($request->tax_id) ? implode(',', $request->tax_id) : '';
            $productService->unit_id = $request->unit_id;
            if (!empty($request->quantity)) {
                $productService->quantity = $request->quantity;
            } else {
                $productService->quantity = 0;
            }
            $productService->type = $request->type;
            $productService->sale_chartaccount_id = $request->sale_chartaccount_id;
            $productService->expense_chartaccount_id = $request->expense_chartaccount_id;
            $productService->category_id = $request->category_id;

            if (!empty($request->pro_image)) {
                //storage limit
                $image_size = $request->file('pro_image')->getSize();
                $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);
                if ($result == 1) {
                    if ($productService->pro_image) {
                        $path = storage_path('uploads/pro_image' . $productService->pro_image);
                    }
                    $fileName = $request->pro_image->getClientOriginalName();
                    $productService->pro_image = $fileName;
                    $dir = 'uploads/pro_image';
                    $path = Utility::upload_file($request, 'pro_image', $fileName, $dir, []);
                }
            }

            $productService->created_by = \Auth::user()->creatorId();
            $productService->owned_by = \Auth::user()->ownedId();
            $productService->save();
            if (!empty($request->quantity)) {
                //Product Stock Report  
                $type = 'manually';
                $type_id = 0;
                $description = $request->quantity . '  ' . __('quantity added by manually');
                Utility::addProductStock($productService->id, $request->quantity, $type, $description, $type_id);
            }
            CustomField::saveData($productService, $request->customField);

            return redirect()->route('productservice.index')->with('success', __('Product successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show()
    {
        return redirect()->route('productservice.index');
    }

    public function edit($id)
    {
        $productService = ProductService::find($id);

        if (\Auth::user()->can('edit product & service')) {
            if ($productService->created_by == \Auth::user()->creatorId()) {
                $category = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 'product & service')->get()->pluck('name', 'id')->toArray();
                $category = ['__add__' => '➕ Add new category'] + ['' => 'Select Category'] + $category;
                $unit = ProductServiceUnit::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
                $unit = ['__add__' => '➕ Add new unit'] + ['' => 'Select Unit'] + $unit;
                $tax = Tax::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
                $tax = ['__add__' => '➕ Add new tax'] + ['' => 'Select Tax'] + $tax;
                $productService->customField = CustomField::getData($productService, 'product');
                $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'product')->get();
                $productService->tax_id = explode(',', $productService->tax_id);
                $incomeChartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name, chart_of_accounts.id as id'))
                    ->leftjoin('chart_of_account_types', 'chart_of_account_types.id', 'chart_of_accounts.type')
                    ->where('chart_of_account_types.name', 'income')
                    ->where('parent', '=', 0)
                    ->where('chart_of_accounts.created_by', \Auth::user()->creatorId())->get()
                    ->pluck('code_name', 'id');
                $incomeChartAccounts->prepend('Select Account', 0);

                $incomeSubAccounts = ChartOfAccount::select('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_account_parents.account');
                $incomeSubAccounts->leftjoin('chart_of_account_parents', 'chart_of_accounts.parent', 'chart_of_account_parents.id');
                $incomeSubAccounts->leftjoin('chart_of_account_types', 'chart_of_account_types.id', 'chart_of_accounts.type');
                $incomeSubAccounts->where('chart_of_account_types.name', 'income');
                $incomeSubAccounts->where('chart_of_accounts.parent', '!=', 0);
                $incomeSubAccounts->where('chart_of_accounts.created_by', \Auth::user()->creatorId());
                $incomeSubAccounts = $incomeSubAccounts->get()->toArray();


                $expenseChartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name, chart_of_accounts.id as id'))
                    ->leftjoin('chart_of_account_types', 'chart_of_account_types.id', 'chart_of_accounts.type')
                    ->whereIn('chart_of_account_types.name', ['Expenses', 'Costs of Goods Sold'])
                    ->where('chart_of_accounts.created_by', \Auth::user()->creatorId())->get()
                    ->pluck('code_name', 'id');
                $expenseChartAccounts->prepend('Select Account', '');

                $expenseSubAccounts = ChartOfAccount::select('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_account_parents.account');
                $expenseSubAccounts->leftjoin('chart_of_account_parents', 'chart_of_accounts.parent', 'chart_of_account_parents.id');
                $expenseSubAccounts->leftjoin('chart_of_account_types', 'chart_of_account_types.id', 'chart_of_accounts.type');
                $expenseSubAccounts->whereIn('chart_of_account_types.name', ['Expenses', 'Costs of Goods Sold']);
                $expenseSubAccounts->where('chart_of_accounts.parent', '!=', 0);
                $expenseSubAccounts->where('chart_of_accounts.created_by', \Auth::user()->creatorId());
                $expenseSubAccounts = $expenseSubAccounts->get()->toArray();

                return view('productservice.edit', compact('category', 'unit', 'tax', 'productService', 'customFields', 'incomeChartAccounts', 'expenseChartAccounts', 'incomeSubAccounts', 'expenseSubAccounts'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, $id)
    {

        if (\Auth::user()->can('edit product & service')) {
            $productService = ProductService::find($id);
            if ($productService->created_by == \Auth::user()->creatorId()) {
                $rules = [
                    'name' => 'required',
                    'sku' => 'required',
                    Rule::unique('product_services')->ignore($productService->id),
                    'sale_price' => 'required|numeric',
                    'purchase_price' => 'required|numeric',
                    'category_id' => 'required',
                    'unit_id' => 'required',
                    'type' => 'required',

                ];

                $validator = \Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->route('productservice.index')->with('error', $messages->first());
                }

                $productService->name = $request->name;
                $productService->description = $request->description;
                $productService->sku = $request->sku;
                $productService->sale_price = $request->sale_price;
                $productService->purchase_price = $request->purchase_price;
                $productService->tax_id = !empty($request->tax_id) ? implode(',', $request->tax_id) : '';
                $productService->unit_id = $request->unit_id;

                // if (!empty($request->quantity)) {
                //     $productService->quantity = $request->quantity;
                // } else {
                //     $productService->quantity = 0;
                // }
                $productService->type = $request->type;
                $productService->sale_chartaccount_id = $request->sale_chartaccount_id;
                $productService->expense_chartaccount_id = $request->expense_chartaccount_id;
                $productService->category_id = $request->category_id;

                if (!empty($request->pro_image)) {
                    //storage limit
                    $file_path = '/uploads/pro_image/' . $productService->pro_image;
                    $image_size = $request->file('pro_image')->getSize();
                    $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);
                    if ($result == 1) {
                        if ($productService->pro_image) {
                            Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
                            $path = storage_path('uploads/pro_image' . $productService->pro_image);
                            //                            if(file_exists($path))
                            //                            {
                            //                                \File::delete($path);
                            //                            }
                        }
                        $fileName = $request->pro_image->getClientOriginalName();
                        $productService->pro_image = $fileName;
                        $dir = 'uploads/pro_image';
                        $path = Utility::upload_file($request, 'pro_image', $fileName, $dir, []);
                    }
                }

                $productService->created_by = \Auth::user()->creatorId();
                $productService->save();
                CustomField::saveData($productService, $request->customField);

                return redirect()->route('productservice.index')->with('success', __('Product successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy($id)
    {
        if (\Auth::user()->can('delete product & service')) {
            $productService = ProductService::find($id);
            if ($productService->created_by == \Auth::user()->creatorId()) {
                if (!empty($productService->pro_image)) {
                    //storage limit
                    $file_path = '/uploads/pro_image/' . $productService->pro_image;
                    $result = Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
                }

                $productService->delete();

                return redirect()->route('productservice.index')->with('success', __('Product successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function export()
    {
        $name = 'product_service_' . date('Y-m-d i:h:s');
        $data = Excel::download(new ProductServiceExport(), $name . '.xlsx');

        return $data;
    }

    public function importFile()
    {
        return view('productservice.import');
    }

    public function import(Request $request)
    {
        $rules = [
            'file' => 'required|mimes:csv,txt',
        ];

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }
        $products = (new ProductServiceImport)->toArray(request()->file('file'))[0];
        $totalProduct = count($products) - 1;
        $errorArray = [];
        for ($i = 1; $i <= count($products) - 1; $i++) {
            $items = $products[$i];

            $taxes = explode(';', $items[5]);

            $taxesData = [];
            foreach ($taxes as $tax) {
                $taxes = Tax::where('id', $tax)->first();
                //                $taxesData[] = $taxes->id;
                $taxesData[] = !empty($taxes->id) ? $taxes->id : 0;
            }

            $taxData = implode(',', $taxesData);
            //            dd($taxData);

            if (!empty($productBySku)) {
                $productService = $productBySku;
            } else {
                $productService = new ProductService();
            }

            $productService->name = $items[0];
            $productService->sku = $items[1];
            $productService->sale_price = $items[2];
            $productService->purchase_price = $items[3];
            $productService->quantity = $items[4];
            $productService->tax_id = $items[5];
            $productService->category_id = $items[6];
            $productService->unit_id = $items[7];
            $productService->type = $items[8];
            $productService->description = $items[9];
            $productService->created_by = \Auth::user()->creatorId();

            if (empty($productService)) {
                $errorArray[] = $productService;
            } else {
                $productService->save();
            }
        }

        $errorRecord = [];
        if (empty($errorArray)) {

            $data['status'] = 'success';
            $data['msg'] = __('Record successfully imported');
        } else {
            $data['status'] = 'error';
            $data['msg'] = count($errorArray) . ' ' . __('Record imported fail out of' . ' ' . $totalProduct . ' ' . 'record');


            foreach ($errorArray as $errorData) {

                $errorRecord[] = implode(',', $errorData);
            }

            \Session::put('errorArray', $errorRecord);
        }

        return redirect()->back()->with($data['status'], $data['msg']);
    }

    public function warehouseDetail($id)
    {
        $products = WarehouseProduct::with(['warehouse'])->where('product_id', '=', $id)->where('created_by', '=', \Auth::user()->creatorId())->get();
        return view('productservice.detail', compact('products'));
    }

    public function searchProducts(Request $request)
    {
        $lastsegment = $request->session_key;

        if (Auth::user()->can('manage pos') && $request->ajax() && isset($lastsegment) && !empty($lastsegment)) {

            $output = "";
            if ($request->war_id == '0') {
                $ids = WarehouseProduct::where('warehouse_id', 1)->get()->pluck('product_id')->toArray();

                if ($request->cat_id !== '' && $request->search == '') {
                    if ($request->cat_id == '0') {
                        $products = ProductService::getallproducts()->whereIn('product_services.id', $ids)->with(['unit'])->get();

                    } else {
                        $products = ProductService::getallproducts()->where('category_id', $request->cat_id)->whereIn('product_services.id', $ids)->with(['unit'])->get();

                    }

                } else {
                    if ($request->cat_id == '0') {
                        $products = ProductService::getallproducts()->where('product_services.' . $request->type, 'LIKE', "%{$request->search}%")->with(['unit'])->get();
                    } else {
                        $products = ProductService::getallproducts()->where('product_services.' . $request->type, 'LIKE', "%{$request->search}%")->orWhere('category_id', $request->cat_id)->with(['unit'])->get();
                    }
                }
            } else {
                $ids = WarehouseProduct::where('warehouse_id', $request->war_id)->get()->pluck('product_id')->toArray();

                if ($request->cat_id == '0') {
                    $products = ProductService::getallproducts()->whereIn('product_services.id', $ids)->with(['unit'])->get();

                } else {
                    $products = ProductService::getallproducts()->whereIn('product_services.id', $ids)->where('category_id', $request->cat_id)->with(['unit'])->get();

                }

            }


            if (count($products) > 0) {
                foreach ($products as $key => $product) {
                    $quantity = $product->warehouseProduct($product->id, $request->war_id != 0 ? $request->war_id : 7);

                    $unit = (!empty($product) && !empty($product->unit)) ? $product->unit->name : '';

                    if (!empty($product->pro_image)) {
                        $image_url = ('uploads/pro_image') . '/' . $product->pro_image;
                    } else {
                        $image_url = ('uploads/pro_image') . '/default.png';
                    }
                    if ($request->session_key == 'purchases') {
                        $productprice = $product->purchase_price != 0 ? $product->purchase_price : 0;
                    } else if ($request->session_key == 'pos') {
                        $productprice = $product->sale_price != 0 ? $product->sale_price : 0;
                    } else {
                        $productprice = $product->sale_price != 0 ? $product->sale_price : $product->purchase_price;
                    }

                    $output .= '

                                    <div class="col-lg-2 col-md-2 col-sm-3 col-xs-4 col-12">
                                        <div class="tab-pane fade show active toacart w-100" data-url="' . url('add-to-cart/' . $product->id . '/' . $lastsegment) . '">
                                            <div class="position-relative card">
                                                <img alt="Image placeholder" src="' . asset(Storage::url($image_url)) . '" class="card-image avatar shadow hover-shadow-lg" style=" height: 6rem; width: 100%;">
                                                <div class="p-0 custom-card-body card-body d-flex ">
                                                    <div class="card-body my-2 p-2 text-left card-bottom-content">
                                                        <h6 class="mb-2 text-dark product-title-name">' . $product->name . '</h6>
                                                        <h6 class="mb-2 text-dark product-title-name">' . $product->sku . '</h6>
                                                        <small class="badge badge-primary mb-0">' . Auth::user()->priceFormat($productprice) . '</small>

                                                        <small class="top-badge badge badge-danger mb-0">' . $quantity . ' ' . $unit . '</small>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                            ';

                }

                return Response($output);
            } else {
                $output = '<div class="card card-body col-12 text-center">
                    <h5>' . __("No Product Available") . '</h5>
                    </div>';
                return Response($output);
            }
        }
    }

    public function addToCart(Request $request, $id, $session_key)
    {

        if (Auth::user()->can('manage product & service') && $request->ajax()) {
            $product = ProductService::find($id);
            $productquantity = 0;

            if ($product) {
                $productquantity = $product->getTotalProductQuantity();
            }

            if (!$product || ($session_key == 'pos' && $productquantity == 0)) {
                return response()->json(
                    [
                        'code' => 404,
                        'status' => 'Error',
                        'error' => __('This product is out of stock!'),
                    ],
                    404
                );
            }

            $productname = $product->name;

            if ($session_key == 'purchases') {

                $productprice = $product->purchase_price != 0 ? $product->purchase_price : 0;
            } else if ($session_key == 'pos') {

                $productprice = $product->sale_price != 0 ? $product->sale_price : 0;
            } else {

                $productprice = $product->sale_price != 0 ? $product->sale_price : $product->purchase_price;
            }

            $originalquantity = (int) $productquantity;

            $taxes = Utility::tax($product->tax_id);

            $totalTaxRate = Utility::totalTaxRate($product->tax_id);

            $product_tax = '';
            $product_tax_id = [];
            foreach ($taxes as $tax) {
                $product_tax .= !empty($tax) ? "<span class='badge badge-primary'>" . $tax->name . ' (' . $tax->rate . '%)' . "</span><br>" : '';
                $product_tax_id[] = !empty($tax) ? $tax->id : 0;
            }

            if (empty($product_tax)) {
                $product_tax = "-";
            }
            $producttax = $totalTaxRate;


            $tax = ($productprice * $producttax) / 100;

            $subtotal = $productprice + $tax;
            $cart = session()->get($session_key);
            $image_url = (!empty($product->pro_image) && Storage::exists($product->pro_image)) ? $product->pro_image : 'uploads/pro_image/' . $product->pro_image;

            $model_delete_id = 'delete-form-' . $id;

            $carthtml = '';

            $carthtml .= '<tr data-product-id="' . $id . '" id="product-id-' . $id . '">
                            <td class="cart-images">
                                <img alt="Image placeholder" src="' . asset(Storage::url($image_url)) . '" class="card-image avatar shadow hover-shadow-lg">
                            </td>

                            <td class="name">' . $productname . '</td>

                            <td class="">
                                   <span class="quantity buttons_added">
                                         <input type="button" value="-" class="minus">
                                         <input type="number" step="1" min="1" max="" name="quantity" title="' . __('Quantity') . '" class="input-number" size="4" data-url="' . url('update-cart/') . '" data-id="' . $id . '">
                                         <input type="button" value="+" class="plus">
                                   </span>
                            </td>


                            <td class="tax">' . $product_tax . '</td>

                            <td class="price">' . Auth::user()->priceFormat($productprice) . '</td>

                            <td class="subtotal">' . Auth::user()->priceFormat($subtotal) . '</td>

                            <td class="">
                                 <a href="#" class="action-btn bg-danger bs-pass-para-pos" data-confirm="' . __("Are You Sure?") . '" data-text="' . __("This action can not be undone. Do you want to continue?") . '" data-confirm-yes=' . $model_delete_id . ' title="' . __('Delete') . '}" data-id="' . $id . '" title="' . __('Delete') . '"   >
                                   <span class=""><i class="ti ti-trash btn btn-sm text-white"></i></span>
                                 </a>
                                 <form method="post" action="' . url('remove-from-cart') . '"  accept-charset="UTF-8" id="' . $model_delete_id . '">
                                      <input name="_method" type="hidden" value="DELETE">
                                      <input name="_token" type="hidden" value="' . csrf_token() . '">
                                      <input type="hidden" name="session_key" value="' . $session_key . '">
                                      <input type="hidden" name="id" value="' . $id . '">
                                 </form>

                            </td>
                        </td>';

            // if cart is empty then this the first product
            if (!$cart) {
                $cart = [
                    $id => [
                        "name" => $productname,
                        "quantity" => 1,
                        "price" => $productprice,
                        "id" => $id,
                        "tax" => $producttax,
                        "subtotal" => $subtotal,
                        "originalquantity" => $originalquantity,
                        "product_tax" => $product_tax,
                        "product_tax_id" => !empty($product_tax_id) ? implode(',', $product_tax_id) : 0,
                    ],
                ];


                if ($originalquantity < $cart[$id]['quantity'] && $session_key == 'pos') {
                    return response()->json(
                        [
                            'code' => 404,
                            'status' => 'Error',
                            'error' => __('This product is out of stock!'),
                        ],
                        404
                    );
                }

                session()->put($session_key, $cart);

                return response()->json(
                    [
                        'code' => 200,
                        'status' => 'Success',
                        'success' => $productname . __(' added to cart successfully!'),
                        'product' => $cart[$id],
                        'carthtml' => $carthtml,
                    ]
                );
            }

            // if cart not empty then check if this product exist then increment quantity
            if (isset($cart[$id])) {

                $cart[$id]['quantity']++;
                $cart[$id]['id'] = $id;

                $subtotal = $cart[$id]["price"] * $cart[$id]["quantity"];
                $tax = ($subtotal * $cart[$id]["tax"]) / 100;

                $cart[$id]["subtotal"] = $subtotal + $tax;
                $cart[$id]["originalquantity"] = $originalquantity;

                if ($originalquantity < $cart[$id]['quantity'] && $session_key == 'pos') {
                    return response()->json(
                        [
                            'code' => 404,
                            'status' => 'Error',
                            'error' => __('This product is out of stock!'),
                        ],
                        404
                    );
                }

                session()->put($session_key, $cart);

                return response()->json(
                    [
                        'code' => 200,
                        'status' => 'Success',
                        'success' => $productname . __(' added to cart successfully!'),
                        'product' => $cart[$id],
                        'carttotal' => $cart,
                    ]
                );
            }

            // if item not exist in cart then add to cart with quantity = 1
            $cart[$id] = [
                "name" => $productname,
                "quantity" => 1,
                "price" => $productprice,
                "tax" => $producttax,
                "subtotal" => $subtotal,
                "id" => $id,
                "originalquantity" => $originalquantity,
                "product_tax" => $product_tax,
            ];

            if ($originalquantity < $cart[$id]['quantity'] && $session_key == 'pos') {
                return response()->json(
                    [
                        'code' => 404,
                        'status' => 'Error',
                        'error' => __('This product is out of stock!'),
                    ],
                    404
                );
            }

            session()->put($session_key, $cart);

            return response()->json(
                [
                    'code' => 200,
                    'status' => 'Success',
                    'success' => $productname . __(' added to cart successfully!'),
                    'product' => $cart[$id],
                    'carthtml' => $carthtml,
                    'carttotal' => $cart,
                ]
            );
        } else {
            return response()->json(
                [
                    'code' => 404,
                    'status' => 'Error',
                    'error' => __('This Product is not found!'),
                ],
                404
            );
        }
    }

    public function updateCart(Request $request)
    {

        $id = $request->id;
        $quantity = $request->quantity;
        $discount = $request->discount;
        $session_key = $request->session_key;

        if (Auth::user()->can('manage product & service') && $request->ajax() && isset($id) && !empty($id) && isset($session_key) && !empty($session_key)) {
            $cart = session()->get($session_key);


            if (isset($cart[$id]) && $quantity == 0) {
                unset($cart[$id]);
            }

            if ($quantity) {

                $cart[$id]["quantity"] = $quantity;

                $producttax = isset($cart[$id]) ? $cart[$id]["tax"] : 0;
                $productprice = $cart[$id]["price"];

                $subtotal = $productprice * $quantity;
                $tax = ($subtotal * $producttax) / 100;

                $cart[$id]["subtotal"] = $subtotal + $tax;
            }

            if (isset($cart[$id]) && ($cart[$id]["originalquantity"]) < $cart[$id]['quantity'] && $session_key == 'pos') {
                return response()->json(
                    [
                        'code' => 404,
                        'status' => 'Error',
                        'error' => __('This product is out of stock!'),
                    ],
                    404
                );
            }

            $subtotal = array_sum(array_column($cart, 'subtotal'));
            $discount = $request->discount;
            $total = $subtotal - $discount;

            $totalDiscount = Auth::user()->priceFormat($total);
            $discount = $totalDiscount;


            session()->put($session_key, $cart);

            return response()->json(
                [
                    'code' => 200,
                    'success' => __('Cart updated successfully!'),
                    'product' => $cart,
                    'discount' => $discount,
                ]
            );
        } else {
            return response()->json(
                [
                    'code' => 404,
                    'status' => 'Error',
                    'error' => __('This Product is not found!'),
                ],
                404
            );
        }
    }

    public function emptyCart(Request $request)
    {
        $session_key = $request->session_key;

        if (Auth::user()->can('manage product & service') && isset($session_key) && !empty($session_key)) {
            $cart = session()->get($session_key);
            if (isset($cart) && count($cart) > 0) {
                session()->forget($session_key);
            }

            return redirect()->back()->with('error', __('Cart is empty!'));
        } else {
            return redirect()->back()->with('error', __('Cart cannot be empty!.'));
        }
    }

    public function warehouseemptyCart(Request $request)
    {
        $session_key = $request->session_key;

        $cart = session()->get($session_key);
        if (isset($cart) && count($cart) > 0) {
            session()->forget($session_key);
        }

        return response()->json();
    }

    public function removeFromCart(Request $request)
    {
        $id = $request->id;
        $session_key = $request->session_key;
        if (Auth::user()->can('manage product & service') && isset($id) && !empty($id) && isset($session_key) && !empty($session_key)) {
            $cart = session()->get($session_key);
            if (isset($cart[$id])) {
                unset($cart[$id]);
                session()->put($session_key, $cart);
            }

            return redirect()->back()->with('error', __('Product removed from cart!'));
        } else {
            return redirect()->back()->with('error', __('This Product is not found!'));
        }
    }

    public function inventoryValuationDetail(
        \App\DataTables\InventoryValuationDetailDataTable $dataTable,
        \Illuminate\Http\Request $request
    ) {
        // Temporarily bypass permission check for testing
        // if (!\Auth::user()->can('manage product & service')) {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }

        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();

        // Dropdowns
        $category = \App\Models\ProductServiceCategory::where('created_by', $ownerId)
            ->where('type', 'product & service')
            ->pluck('name', 'id')
            ->prepend(__('Select Category'), '');

        $types = array_merge(['' => __('Select Type')], \App\Models\ProductService::$types);

        // Defaults for dates (current month, like ledger)
        $start = $request->get('start_date') ?: \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
        $end = $request->get('end_date') ?: \Carbon\Carbon::now()->format('Y-m-d');
        $reportPeriod = $request->get('report_period', 'this_month');

        $filter = [
            'startDateRange' => $start,
            'endDateRange' => $end,
            'reportPeriod' => $reportPeriod,
            'selectedCategory' => $request->get('category', ''),
            'selectedType' => $request->get('type', ''),
        ];

        return $dataTable->render(
            'productservice.inventoryValuationDetail',
            compact('category', 'types', 'user', 'filter', 'reportPeriod')
        );
    }

    public function purchasesByProductServiceDetail(
        \App\DataTables\PurchasesByProductServiceDetailDataTable $dataTable,
        \Illuminate\Http\Request $request
    ) {
        if (!\Auth::user()->can('manage product & service')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

        $pageTitle = __('Purchases by Product/Service Detail');

        // Get all product/service categories for the dropdown filter
        $categories = \App\Models\ProductServiceCategory::where($column, $ownerId)
            ->where('type', 'product & service')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->prepend(__('All Categories'), '');

        // Get product types
        $types = array_merge(['' => __('All Types')], \App\Models\ProductService::$types);

        // Get all vendors for filtering
        $vendors = \App\Models\Vender::where($column, $ownerId)
            ->where('is_active', 1)
            ->whereNotNull('name')
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values();

        $filter = [
            'selectedCategory' => $request->get('category', ''),
            'selectedType' => $request->get('type', ''),
            'selectedProductName' => $request->get('product_name', ''),
            'selectedVendorName' => $request->get('vendor_name', ''),
            'reportPeriod' => $request->get('report_period', 'all_dates'),
            'startDateRange' => $request->get('start_date', ''),
            'endDateRange' => $request->get('end_date', ''),
        ];

        return $dataTable->render(
            'productservice.purchasesByProductServiceDetail',
            compact('pageTitle', 'categories', 'types', 'vendors', 'user', 'filter')
        );
    }

    /**
     * Sales Tax Liability Report
     */
    public function SalesTaxLiabilityReport(\App\DataTables\SalesTaxLiabilityReportDataTable $dataTable, \Illuminate\Http\Request $request)
    {
        if (!\Auth::user()->can('manage product & service')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

        $pageTitle = __('Sales Tax Liability Report');

        // Get all customers for the dropdown filter
        $customers = \App\Models\Customer::where($column, $ownerId)
            ->where('is_active', 1)
            ->whereNotNull('name')
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values();

        // Get all product/service categories for the dropdown filter
        $categories = \App\Models\ProductServiceCategory::where($column, $ownerId)
            ->where('type', 'product & service')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->prepend(__('All Categories'), '');

        // Get product types
        $types = array_merge(['' => __('All Types')], \App\Models\ProductService::$types);

        $filter = [
            'selectedCustomerName' => $request->get('customer_name', ''),
            'selectedCategory' => $request->get('category', ''),
            'selectedType' => $request->get('type', ''),
            'selectedProductName' => $request->get('product_name', ''),
            'reportPeriod' => $request->get('report_period', 'all_dates'),
            'startDateRange' => $request->get('start_date', ''),
            'endDateRange' => $request->get('end_date', ''),
            'accountingMethod' => $request->get('accounting_method', 'accrual'),
        ];

        // return $dataTable->render('report.sales_tax_liability_report', compact('pageTitle', 'customers', 'categories', 'types', 'user', 'filter'));
        return $dataTable->render('sync.customerbalance.index', [ // ✅ keep same view, or create vendorbalance.index
            'pageTitle' => $pageTitle,
            'startDate' => $request->get('start_date', date('Y-01-01')),
            'endDate' => $request->get('end_date', date('Y-m-d', strtotime('+1 day')))
        ]);
    }
}
