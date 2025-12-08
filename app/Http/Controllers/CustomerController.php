<?php

namespace App\Http\Controllers;

use App\DataTables\CustomerContactListDataTable;
use App\DataTables\CustomerContactListPhoneNumbersDataTable;
use App\Exports\CustomerExport;
use App\Imports\CustomerImport;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\Transaction;
use App\Models\Utility;
use App\Models\WorkFlow;
use App\Models\Notification;
use App\Models\WorkFlowAction;
use App\Models\Proposal;
use App\Models\Invoice;
use App\Models\Revenue;
use App\Models\CreditNote;
use Auth;
use App\Models\User;
use App\Models\Plan;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{

    public function contactList(
        CustomerContactListDataTable $dataTable,
        \Illuminate\Http\Request $request
    ) {
        if (!\Auth::user()->can('manage customer')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

        $pageTitle = __('Customer Contact List');

        $filter = [
            'selectedCustomerName' => $request->get('customer_name', ''),
        ];

        // NEW: all active customer names for the dropdown
        $customers = \App\Models\Customer::query()
            ->where($column, $ownerId)
            ->where('is_active', 1)
            ->whereNotNull('name')
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values();

        // return $dataTable->render(
        //     'customer.contactList',
        //     compact('pageTitle', 'user', 'filter', 'customers')
        // );

        return $dataTable->render('sync.simpletable.index', [ // ✅ keep same view, or create vendorbalance.index
            'pageTitle' => $pageTitle,
            'startDate' => $request->get('start_date', date('Y-01-01')),
            'endDate' => $request->get('end_date', date('Y-m-d', strtotime('+1 day')))
        ]);
    }
    public function customerContactListPhoneNumbers(
        CustomerContactListPhoneNumbersDataTable $dataTable,
        \Illuminate\Http\Request $request
    ) {
        if (!\Auth::user()->can('manage customer')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

        $pageTitle = __('Customer Phone List');

        $filter = [
            'selectedCustomerName' => $request->get('customer_name', ''),
        ];

        // NEW: all active customer names for the dropdown
        $customers = \App\Models\Customer::query()
            ->where($column, $ownerId)
            ->where('is_active', 1)
            ->whereNotNull('name')
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values();

        // return $dataTable->render(
        //     'customer.contactList',
        //     compact('pageTitle', 'user', 'filter', 'customers')
        // );

        return $dataTable->render('sync.simpletable.index', [ // ✅ keep same view, or create vendorbalance.index
            'pageTitle' => $pageTitle,
            'startDate' => $request->get('start_date', date('Y-01-01')),
            'endDate' => $request->get('end_date', date('Y-m-d', strtotime('+1 day')))
        ]);
    }


    public function dashboard()
    {
        $data['invoiceChartData'] = \Auth::user()->invoiceChartData();

        return view('customer.dashboard', $data);
    }

    public function index()
    {
        if (\Auth::user()->can('manage customer')) {
            $user = \Auth::user();
            $companyId = $user->creatorId();
            $userId = $user->id;

            // Scope helpers (match allSales())
            $ownedById = method_exists($user, 'ownedId') ? $user->ownedId() : $userId;
            $createdByScope = array_values(array_unique([$companyId, $userId]));

            // Default date window (current month). No Request here, so we pick safe defaults.
            $start = \Carbon\Carbon::now()->startOfMonth();
            $end = \Carbon\Carbon::now()->endOfMonth();
            $dateFilter = [$start->toDateString(), $end->toDateString()];

            // No customer filter on this page
            $customerFilter = null;

            // Calculate sales data for overview (same helper as allSales)
            $salesData = $this->calculateSalesData($createdByScope, $ownedById, $dateFilter, $customerFilter);

            // Customers list as before
            $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
            $ownerId = ($user->type === 'company') ? $companyId : $ownedById;
            $customers = Customer::where($column, $ownerId)->get();

            return view('customer.index', compact('customers', 'salesData'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
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
                $line = ($it->price * $it->quantity) - (float) ($it->discount ?? 0);
                $estimatesAmount += $line;
                $taxes = \App\Models\Utility::tax($it->tax);
                if (!empty($taxes)) {
                    foreach ($taxes as $t) {
                        if ($t === null)
                            continue;
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

    public function create()
    {
        if (\Auth::user()->can('create customer')) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'customer')->get();

            // return view('customer.create', compact('customFields'));

            // NEW global drawer modal view
            return view('customer.create-right', compact('customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function store(Request $request)
    {
        \DB::beginTransaction();
        try {
            if (!\Auth::user()->can('create customer')) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['message' => __('Permission denied.')], 403);
                }
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            $rules = [
                'name' => 'required',
                'contact' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/',
                'email' => [
                    'required',
                    Rule::unique('customers')->where(function ($query) {
                        return $query->where('created_by', \Auth::user()->id);
                    })
                ],
            ];

            $validator = \Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'message' => 'Validation error',
                        'errors' => $validator->errors(),
                    ], 422);
                }
                return redirect()->route('customer.index')->with('error', $messages->first());
            }

            $objCustomer = \Auth::user();
            $creator = User::find($objCustomer->creatorId());
            $total_customer = $objCustomer->countCustomers();
            $plan = Plan::find($creator->plan);

            $default_language = DB::table('settings')->select('value')->where('name', 'default_language')->first();

            if ($total_customer < $plan->max_customers || $plan->max_customers == -1) {
                $customer = new Customer();
                $customer->customer_id = $this->customerNumber();
                $customer->name = $request->name;
                $customer->contact = $request->contact;
                $customer->email = $request->email;
                $customer->tax_number = $request->tax_number;
                $customer->created_by = \Auth::user()->creatorId();
                $customer->owned_by = \Auth::user()->ownedId();
                $customer->billing_name = $request->billing_name;
                $customer->billing_country = $request->billing_country;
                $customer->billing_state = $request->billing_state;
                $customer->billing_city = $request->billing_city;
                $customer->billing_phone = $request->billing_phone;
                $customer->billing_zip = $request->billing_zip;
                $customer->billing_address = $request->billing_address;

                $customer->shipping_name = $request->shipping_name;
                $customer->shipping_country = $request->shipping_country;
                $customer->shipping_state = $request->shipping_state;
                $customer->shipping_city = $request->shipping_city;
                $customer->shipping_phone = $request->shipping_phone;
                $customer->shipping_zip = $request->shipping_zip;
                $customer->shipping_address = $request->shipping_address;

                $customer->lang = !empty($default_language) ? $default_language->value : '';

                $customer->save();

                CustomField::saveData($customer, $request->customField);

                // // WorkFlow get which is active
                $us_mail = 'false';
                $us_notify = 'false';
                $us_approve = 'false';
                $usr_Notification = [];
                $workflow = WorkFlow::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'accounts')->where('status', 1)->first();
                if ($workflow) {
                    $workflowaction = WorkFlowAction::where('workflow_id', $workflow->id)->where('status', 1)->get();
                    foreach ($workflowaction as $action) {
                        $useraction = json_decode($action->assigned_users);
                        if (strtolower('add-customer') == $action->node_id) {
                            // Pick that stage user assign or change on lead
                            if (@$useraction != '') {
                                $useraction = json_decode($useraction);
                                foreach ($useraction as $anyaction) {
                                    // make new user array
                                    if ($anyaction->type == 'user') {
                                        $usr_Notification[] = $anyaction->id;
                                    }
                                }
                            }
                            $raw_json = trim($action->applied_conditions, '"');
                            $cleaned_json = stripslashes($raw_json);
                            $applied_conditions = json_decode($cleaned_json, true);

                            if (isset($applied_conditions['conditions']) && is_array($applied_conditions['conditions'])) {
                                $arr = [
                                    'name' => 'name',
                                    'email' => 'email',
                                    'contact' => 'contact',
                                ];
                                $relate = [];
                                foreach ($applied_conditions['conditions'] as $conditionGroup) {

                                    if (in_array($conditionGroup['action'], ['send_email', 'send_notification', 'send_approval'])) {
                                        $query = Customer::where('id', $customer->id);
                                        foreach ($conditionGroup['conditions'] as $condition) {
                                            $field = $condition['field'];
                                            $operator = $condition['operator'];
                                            $value = $condition['value'];
                                            if (isset($arr[$field], $relate[$arr[$field]])) {
                                                $relatedField = strpos($arr[$field], '_') !== false ? explode('_', $arr[$field], 2)[1] : $arr[$field];
                                                $relation = $relate[$arr[$field]];

                                                // Apply condition to the related model
                                                $query->whereHas($relation, function ($relatedQuery) use ($relatedField, $operator, $value) {
                                                    $relatedQuery->where($relatedField, $operator, $value);
                                                });
                                            } else {
                                                // Apply condition directly to the contract model
                                                $query->where($arr[$field], $operator, $value);
                                            }
                                        }
                                        $result = $query->first();

                                        if (!empty($result)) {
                                            if ($conditionGroup['action'] === 'send_email') {
                                                $us_mail = 'true';
                                            } elseif ($conditionGroup['action'] === 'send_notification') {
                                                $us_notify = 'true';
                                            } elseif ($conditionGroup['action'] === 'send_approval') {
                                                $us_approve = 'true';
                                            }
                                        }
                                    }
                                }
                            }
                            if ($us_mail == 'true') {
                                // email send
                            }
                            if ($us_notify == 'true' || $us_approve == 'true') {
                                // notification generate
                                if (count($usr_Notification) > 0) {
                                    $usr_Notification[] = Auth::user()->creatorId();
                                    foreach ($usr_Notification as $usrLead) {
                                        $data = [
                                            "updated_by" => Auth::user()->id,
                                            "data_id" => $customer->id,
                                            "name" => @$customer->name,
                                        ];
                                        if ($us_notify == 'true') {
                                            Utility::makeNotification($usrLead, 'create_customer', $data, $customer->id, 'create Customer');
                                        } elseif ($us_approve == 'true') {
                                            Utility::makeNotification($usrLead, 'approve_customer', $data, $customer->id, 'For Approval Customer');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['message' => __('Your user limit is over, Please upgrade plan.')], 402);
                }
                return redirect()->back()->with('error', __('Your user limit is over, Please upgrade plan.'));
            }

            //For Notification
            $setting = Utility::settings(\Auth::user()->creatorId());
            $customerNotificationArr = [
                'user_name' => \Auth::user()->name,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
            ];

            //Twilio Notification
            if (isset($setting['twilio_customer_notification']) && $setting['twilio_customer_notification'] == 1) {
                Utility::send_twilio_msg($request->contact, 'new_customer', $customerNotificationArr);
            }
            Utility::makeActivityLog(\Auth::user()->id, 'Customer', $customer->id, 'Create Customer', $customer->name);
            \DB::commit();

            // If AJAX, return JSON (used by the invoice page to append/select)
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'data' => $customer,
                    'success' => true
                ], 201);
            }

            return redirect()->route('customer.index')->with('success', __('Customer successfully created.'));
        } catch (\Exception $e) {
            \DB::rollback();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', $e);
        }
    }


    public function show($ids)
    {
        try {
            $id = Crypt::decrypt($ids);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Customer Not Found.'));
        }
        $id = \Crypt::decrypt($ids);
        $customer = Customer::find($id);

        return view('customer.show', compact('customer'));
    }


    public function edit($id)
    {
        if (\Auth::user()->can('edit customer')) {
            $customer = Customer::find($id);
            $customer->customField = CustomField::getData($customer, 'customer');

            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'customer')->get();

            // return view('customer.edit', compact('customer', 'customFields'));
            return view('customer.edit-right', compact('customer', 'customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function update(Request $request, Customer $customer)
    {

        if (\Auth::user()->can('edit customer')) {

            $rules = [
                'name' => 'required',
                'contact' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/',
            ];


            $validator = \Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->route('customer.index')->with('error', $messages->first());
            }

            $customer->name = $request->name;
            $customer->contact = $request->contact;
            $customer->email = $request->email;
            $customer->tax_number = $request->tax_number;
            $customer->created_by = \Auth::user()->creatorId();
            $customer->billing_name = $request->billing_name;
            $customer->billing_country = $request->billing_country;
            $customer->billing_state = $request->billing_state;
            $customer->billing_city = $request->billing_city;
            $customer->billing_phone = $request->billing_phone;
            $customer->billing_zip = $request->billing_zip;
            $customer->billing_address = $request->billing_address;
            $customer->shipping_name = $request->shipping_name;
            $customer->shipping_country = $request->shipping_country;
            $customer->shipping_state = $request->shipping_state;
            $customer->shipping_city = $request->shipping_city;
            $customer->shipping_phone = $request->shipping_phone;
            $customer->shipping_zip = $request->shipping_zip;
            $customer->shipping_address = $request->shipping_address;
            $customer->lang = $request->lang;
            $customer->save();
            //log
            CustomField::saveData($customer, $request->customField);
            Utility::makeActivityLog(\Auth::user()->id, 'Customer', $customer->id, 'Update Customer', $customer->name);
            return redirect()->route('customer.index')->with('success', __('Customer successfully updated.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy(Customer $customer)
    {
        if (\Auth::user()->can('delete customer')) {
            if ($customer->created_by == \Auth::user()->creatorId()) {
                //log
                Utility::makeActivityLog(\Auth::user()->id, 'Customer', $customer->id, 'Delete Customer', $customer->name);
                $customer->delete();

                return redirect()->route('customer.index')->with('success', __('Customer successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    function customerNumber()
    {
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
        $latest = Customer::where($column, '=', $ownerId)->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->customer_id + 1;
    }

    public function customerLogout(Request $request)
    {
        \Auth::guard('customer')->logout();

        $request->session()->invalidate();

        return redirect()->route('customer.login');
    }

    public function payment(Request $request)
    {

        if (\Auth::user()->can('manage customer payment')) {
            $category = [
                'Invoice' => 'Invoice',
                'Deposit' => 'Deposit',
                'Sales' => 'Sales',
            ];

            $query = Transaction::where('user_id', \Auth::user()->id)->where('user_type', 'Customer')->where('type', 'Payment');
            if (!empty($request->date)) {
                $date_range = explode(' - ', $request->date);
                $query->whereBetween('date', $date_range);
            }

            if (!empty($request->category)) {
                $query->where('category', '=', $request->category);
            }
            $payments = $query->get();

            return view('customer.payment', compact('payments', 'category'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function transaction(Request $request)
    {
        if (\Auth::user()->can('manage customer payment')) {
            $category = [
                'Invoice' => 'Invoice',
                'Deposit' => 'Deposit',
                'Sales' => 'Sales',
            ];

            $query = Transaction::where('user_id', \Auth::user()->id)->where('user_type', 'Customer');

            if (!empty($request->date)) {
                $date_range = explode(' - ', $request->date);
                $query->whereBetween('date', $date_range);
            }

            if (!empty($request->category)) {
                $query->where('category', '=', $request->category);
            }
            $transactions = $query->get();

            return view('customer.transaction', compact('transactions', 'category'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function profile()
    {
        $userDetail = \Auth::user();
        $userDetail->customField = CustomField::getData($userDetail, 'customer');
        $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'customer')->get();

        return view('customer.profile', compact('userDetail', 'customFields'));
    }

    public function editprofile(Request $request)
    {
        $userDetail = \Auth::user();
        $user = Customer::findOrFail($userDetail['id']);

        $this->validate(
            $request,
            [
                'name' => 'required|max:120',
                'contact' => 'required',
                'email' => 'required|email|unique:users,email,' . $userDetail['id'],
            ]
        );

        if ($request->hasFile('profile')) {
            $filenameWithExt = $request->file('profile')->getClientOriginalName();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension = $request->file('profile')->getClientOriginalExtension();
            $fileNameToStore = $filename . '_' . time() . '.' . $extension;

            $dir = storage_path('uploads/avatar/');
            $image_path = $dir . $userDetail['avatar'];

            if (File::exists($image_path)) {
                File::delete($image_path);
            }

            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $path = $request->file('profile')->storeAs('uploads/avatar/', $fileNameToStore);
        }

        if (!empty($request->profile)) {
            $user['avatar'] = $fileNameToStore;
        }
        $user['name'] = $request['name'];
        $user['email'] = $request['email'];
        $user['contact'] = $request['contact'];
        $user->save();
        CustomField::saveData($user, $request->customField);

        return redirect()->back()->with(
            'success',
            'Profile successfully updated.'
        );
    }

    public function editBilling(Request $request)
    {
        $userDetail = \Auth::user();
        $user = Customer::findOrFail($userDetail['id']);
        $this->validate(
            $request,
            [
                'billing_name' => 'required',
                'billing_country' => 'required',
                'billing_state' => 'required',
                'billing_city' => 'required',
                'billing_phone' => 'required',
                'billing_zip' => 'required',
                'billing_address' => 'required',
            ]
        );
        $input = $request->all();
        $user->fill($input)->save();

        return redirect()->back()->with(
            'success',
            'Profile successfully updated.'
        );
    }

    public function editShipping(Request $request)
    {
        $userDetail = \Auth::user();
        $user = Customer::findOrFail($userDetail['id']);
        $this->validate(
            $request,
            [
                'shipping_name' => 'required',
                'shipping_country' => 'required',
                'shipping_state' => 'required',
                'shipping_city' => 'required',
                'shipping_phone' => 'required',
                'shipping_zip' => 'required',
                'shipping_address' => 'required',
            ]
        );
        $input = $request->all();
        $user->fill($input)->save();

        return redirect()->back()->with(
            'success',
            'Profile successfully updated.'
        );
    }


    public function changeLanquage($lang)
    {

        $user = Auth::user();
        $user->lang = $lang;
        $user->save();

        return redirect()->back()->with('success', __('Language Change Successfully!'));
    }


    public function export()
    {
        $name = 'customer_' . date('Y-m-d i:h:s');
        $data = Excel::download(new CustomerExport(), $name . '.xlsx');
        ob_end_clean();

        return $data;
    }

    public function importFile()
    {
        return view('customer.import');
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

        $customers = (new CustomerImport())->toArray(request()->file('file'))[0];

        $totalCustomer = count($customers) - 1;
        $errorArray = [];
        for ($i = 1; $i <= count($customers) - 1; $i++) {
            $customer = $customers[$i];

            $customerByEmail = Customer::where('email', $customer[2])->first();
            if (!empty($customerByEmail)) {
                $customerData = $customerByEmail;
            } else {
                $customerData = new Customer();
                $customerData->customer_id = $this->customerNumber();
            }

            $customerData->customer_id = $customer[0];
            $customerData->name = $customer[1];
            $customerData->email = $customer[2];
            $customerData->contact = $customer[3];
            $customerData->is_active = 1;
            $customerData->billing_name = $customer[4];
            $customerData->billing_country = $customer[5];
            $customerData->billing_state = $customer[6];
            $customerData->billing_city = $customer[7];
            $customerData->billing_phone = $customer[8];
            $customerData->billing_zip = $customer[9];
            $customerData->billing_address = $customer[10];
            $customerData->shipping_name = $customer[11];
            $customerData->shipping_country = $customer[12];
            $customerData->shipping_state = $customer[13];
            $customerData->shipping_city = $customer[14];
            $customerData->shipping_phone = $customer[15];
            $customerData->shipping_zip = $customer[16];
            $customerData->shipping_address = $customer[17];
            $customerData->balance = $customer[18];
            $customerData->created_by = \Auth::user()->creatorId();
            $customerData->owned_by = \Auth::user()->ownedId();

            if (empty($customerData)) {
                $errorArray[] = $customerData;
            } else {
                Utility::makeActivityLog(\Auth::user()->id, 'Customer Record', $customerData->id, 'Create Customer Record', $customerData->name);
                $customerData->save();
            }
        }

        $errorRecord = [];
        if (empty($errorArray)) {
            $data['status'] = 'success';
            $data['msg'] = __('Record successfully imported');
        } else {
            $data['status'] = 'error';
            $data['msg'] = count($errorArray) . ' ' . __('Record imported fail out of' . ' ' . $totalCustomer . ' ' . 'record');


            foreach ($errorArray as $errorData) {

                $errorRecord[] = implode(',', $errorData);
            }

            \Session::put('errorArray', $errorRecord);
        }

        return redirect()->back()->with($data['status'], $data['msg']);
    }

    public function searchCustomers(Request $request)
    {
        if (\Illuminate\Support\Facades\Auth::user()->can('manage customer')) {
            $customers = [];
            $search = $request->search;
            if ($request->ajax() && isset($search) && !empty($search)) {
                $user = \Auth::user();
                $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
                $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
                $customers = Customer::select('id as value', 'name as label', 'email')->where('is_active', '=', 1)->where($column, '=', $ownerId)->Where('name', 'LIKE', '%' . $search . '%')->orWhere('email', 'LIKE', '%' . $search . '%')->get();

                return json_encode($customers);
            }

            return $customers;
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
