<?php

namespace App\Http\Controllers;

use App\Exports\InvoiceExport;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountSubType;
use App\Models\ChartOfAccountType;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\Invoice;
use App\Models\InvoiceBankTransfer;
use App\Models\InvoicePayment;
use App\Models\InvoiceProduct;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Notification;
use App\Models\WorkFlow;
use App\Models\WorkFlowAction;
use App\Models\Plan;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\StockReport;
use App\Models\Tax;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Utility;
use App\Models\TransactionLines;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Auth;

class InvoiceController extends Controller
{
    public function __construct() {}

    public function index(Request $request)
    {
        if (\Auth::user()->can('manage invoice')) {
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = $user->type == 'company' ? 'created_by' : 'owned_by';
            $customer = Customer::where($column, '=', $ownerId)->get()->pluck('name', 'id');
            $customer->prepend('Select Customer', '');
            $status = Invoice::$statues;
            $query = Invoice::where($column, '=', $ownerId);

            if (!empty($request->customer)) {
                $query->where('customer_id', '=', $request->customer);
            }
            if (count(explode('to', $request->issue_date)) > 1) {
                $date_range = explode(' to ', $request->issue_date);
                $query->whereBetween('issue_date', $date_range);
            } elseif (!empty($request->issue_date)) {
                $date_range = [$request->issue_date, $request->issue_date];
                $query->whereBetween('issue_date', $date_range);
            }
            if ($request->status != null) {
                $query->where('status', '=', $request->status);
            }
            $invoices = $query->get();

            // Calculate invoice summary data for the bars
            $invoiceData = $this->calculateInvoiceSummary($ownerId, $column);

        return view('invoice.index', compact('invoices', 'customer', 'status', 'invoiceData'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Display the Sales & Get Paid Overview page.
     */
    public function overview()
    {
        if (\Auth::user()->can('manage invoice')) {
            return view('invoice.invoice-overview');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function recurringInvoicesOrPayments(Request $request)
    {
        if (!\Auth::user()->can('manage invoice')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = $user->type == 'company' ? 'created_by' : 'owned_by';

        $customer = Customer::where($column, '=', $ownerId)->pluck('name', 'id');
        $customer->prepend('Select Customer', '');

        $status = Invoice::$statues;

        // Base query: ONLY recurring invoices
        $query = Invoice::where($column, '=', $ownerId)
            ->where('is_recurring', true) // <<— only recurring
            ->select('invoices.*')->selectRaw("
            CASE
                WHEN invoices.recurring_parent_id IS NULL
                     OR invoices.recurring_parent_id = invoices.id
                THEN NULL
                ELSE invoices.recurring_parent_id
            END AS recurring_parent_display
        ");

        // Optional: masters vs children (all|masters|children)
        $recurringType = $request->input('recurring_type', 'all');
        if ($recurringType === 'masters') {
            $query->where(function ($q) {
                $q->whereNull('invoices.recurring_parent_id')->orWhereColumn('invoices.recurring_parent_id', 'invoices.id');
            });
        } elseif ($recurringType === 'children') {
            $query->whereNotNull('invoices.recurring_parent_id')->whereColumn('invoices.recurring_parent_id', '<>', 'invoices.id');
        }

        // Existing filters
        if (!empty($request->customer)) {
            $query->where('customer_id', '=', $request->customer);
        }
        if (count(explode('to', (string) $request->issue_date)) > 1) {
            $date_range = explode(' to ', $request->issue_date);
            $query->whereBetween('issue_date', $date_range);
        } elseif (!empty($request->issue_date)) {
            $date_range = [$request->issue_date, $request->issue_date];
            $query->whereBetween('issue_date', $date_range);
        }
        if ($request->status != null) {
            $query->where('status', '=', $request->status);
        }

        $invoices = $query->get();

        $invoiceData = $this->calculateInvoiceSummary($ownerId, $column);

        return view('invoice.recurring_invoices_orPayments', compact('invoices', 'customer', 'status', 'invoiceData', 'recurringType'));
    }

    private function calculateInvoiceSummary($ownerId, $column)
    {
        $now = Carbon::today();
        $from365 = $now->copy()->subDays(365);
        $from30 = $now->copy()->subDays(30);

        $invoices = Invoice::where($column, '=', $ownerId)->get();

        $data = [
            'draft' => ['amount' => 0, 'count' => 0],
            'sent' => ['amount' => 0, 'count' => 0],
            'unpaid' => ['amount' => 0, 'count' => 0],
            'partially_paid' => ['amount' => 0, 'count' => 0],
            'paid' => ['amount' => 0, 'count' => 0],
            'approved' => ['amount' => 0, 'count' => 0],
            'overdue' => ['amount' => 0, 'count' => 0], // 365d window
            'not_due_yet' => ['amount' => 0, 'count' => 0], // 365d window
        ];

        foreach ($invoices as $invoice) {
            $total = $invoice->getTotal();
            $due = $invoice->getDue();

            $issueAt = $invoice->issue_date ? Carbon::parse($invoice->issue_date) : null;
            $dueAt = $invoice->due_date ? Carbon::parse($invoice->due_date) : null;

            $in365 = $issueAt ? $issueAt->betweenIncluded($from365, $now) : false;
            $in30 = $issueAt ? $issueAt->betweenIncluded($from30, $now) : false;

            // ---- Left panel: overdue / not due yet (365 days) ----
            if ($due > 0 && $dueAt && $in365) {
                if ($dueAt->lt($now)) {
                    $data['overdue']['amount'] += $due;
                    $data['overdue']['count']++;
                } elseif ($dueAt->gt($now)) {
                    $data['not_due_yet']['amount'] += $due;
                    $data['not_due_yet']['count']++;
                }
            }

            // ---- Status buckets ----
            switch ((int) $invoice->status) {
                case 0:
                    $data['draft']['amount'] += $total;
                    $data['draft']['count']++;
                    break;

                case 1:
                    $data['sent']['amount'] += $total;
                    $data['sent']['count']++;
                    break;

                case 2:
                    $data['unpaid']['amount'] += $due;
                    $data['unpaid']['count']++;
                    break;

                case 3: // partially paid — only count if within 30 days
                    if ($in30) {
                        $data['partially_paid']['amount'] += $due;
                        $data['partially_paid']['count']++;
                    }
                    break;

                case 4: // paid — only count if within 30 days
                    if ($in30) {
                        $data['paid']['amount'] += $total;
                        $data['paid']['count']++;
                    }
                    break;
            }
        }

        return $data;
    }

    public function create($customerId)
    {
        if (\Auth::user()->can('create invoice')) {
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = $user->type == 'company' ? 'created_by' : 'owned_by';
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())
                ->where('module', '=', 'invoice')
                ->get();
            $invoice_number = \Auth::user()->invoiceNumberFormat($this->invoiceNumber());
            $customers = Customer::where($column, $ownerId)->get()->pluck('name', 'id')->toArray();
            $customers = ['__add__' => '➕ Add new customer'] + ['' => 'Select Customer'] + $customers;
            $category = ProductServiceCategory::where($column, $ownerId)->where('type', 'income')->get()->pluck('name', 'id')->toArray();
            $category = ['__add__' => '➕ Add new category'] + ['' => 'Select Category'] + $category;
            $product_services = ProductService::get()->pluck('name', 'id');
            $product_services->prepend('--', '');
            $taxes = Tax::where('created_by', \Auth::user()->creatorId())->get();

            // Always return modal view
            return view('invoice.create_modal', compact('customers', 'invoice_number', 'product_services', 'category', 'customFields', 'customerId', 'taxes'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function customer(Request $request)
    {
        $customer = Customer::where('id', '=', $request->id)->first();

        if (!$customer) {
            return response()->json(['error' => 'Customer not found'], 404);
        }

        // Return JSON data for auto-filling fields
        return response()->json([
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'billing_name' => $customer->billing_name ?? $customer->name,
                'billing_address' => $customer->billing_address,
                'billing_city' => $customer->billing_city,
                'billing_state' => $customer->billing_state,
                'billing_zip' => $customer->billing_zip,
                'billing_country' => $customer->billing_country,
                'billing_phone' => $customer->billing_phone,
                //ship to
                'shipping_name' => $customer->shipping_name ?? $customer->name,
                'shipping_address' => $customer->shipping_address,
                'shipping_city' => $customer->shipping_city,
                'shipping_state' => $customer->shipping_state,
                'shipping_zip' => $customer->shipping_zip,
                'shipping_country' => $customer->shipping_country,
                'shipping_phone' => $customer->shipping_phone,
            ],
        ]);
    }

    public function product(Request $request)
    {
        $data['product'] = $product = ProductService::find($request->product_id);
        $data['unit'] = !empty($product->unit) ? $product->unit->name : '';
        $data['taxRate'] = $taxRate = !empty($product->tax_id) ? $product->taxRate($product->tax_id) : 0;
        $data['taxes'] = !empty($product->tax_id) ? $product->tax($product->tax_id) : 0;
        $salePrice = $product->sale_price;
        $quantity = 1;
        $taxPrice = ($taxRate / 100) * ($salePrice * $quantity);
        $data['totalAmount'] = $salePrice * $quantity;

        return json_encode($data);
    }

    // public function store(Request $request)
    // {
    //     \DB::beginTransaction();
    //     try {
    //         if (\Auth::user()->can('create invoice')) {
    //             $validator = \Validator::make(
    //                 $request->all(),
    //                 [
    //                     'customer_id' => 'required',
    //                     'issue_date' => 'required',
    //                     'due_date' => 'required',
    //                     // 'category_id' => 'required',
    //                     'items' => 'required',
    //                 ]
    //             );
    //             if ($validator->fails()) {
    //                 $messages = $validator->getMessageBag();
    //                 return redirect()->back()->with('error', $messages->first());
    //             }
    //             $status = Invoice::$statues;
    //             $invoice = new Invoice();
    //             $invoice->invoice_id = $this->invoiceNumber();
    //             $invoice->customer_id = $request->customer_id;
    //             $invoice->status = 0;
    //             $invoice->issue_date = $request->issue_date;
    //             $invoice->due_date = $request->due_date;
    //             $invoice->category_id = $request->category_id ?? 1;
    //             $invoice->ref_number = $request->ref_number;
    //             //            $invoice->discount_apply = isset($request->discount_apply) ? 1 : 0;
    //             $invoice->created_by = \Auth::user()->creatorId();
    //             $invoice->owned_by = \Auth::user()->ownedId();
    //             $invoice->save();
    //             CustomField::saveData($invoice, $request->customField);
    //             $products = $request->items;
    //             $newitems = $request->items;
    //             $reciveable = 0;
    //             for ($i = 0; $i < count($products); $i++) {

    //                 $invoiceProduct = new InvoiceProduct();
    //                 $invoiceProduct->invoice_id = $invoice->id;
    //                 $invoiceProduct->product_id = $products[$i]['item'];
    //                 $invoiceProduct->quantity = $products[$i]['quantity'];
    //                 $invoiceProduct->tax = $products[$i]['tax'];
    //                 // $invoiceProduct->discount    = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;
    //                 $invoiceProduct->discount = $products[$i]['discount'];
    //                 $invoiceProduct->price = $products[$i]['price'];
    //                 $invoiceProduct->description = $products[$i]['description'];
    //                 $invoiceProduct->save();
    //                 $newitems[$i]['prod_id'] = $invoiceProduct->id; // Add the key and value

    //                 //inventory management (Quantity)
    //                 Utility::total_quantity('minus', $invoiceProduct->quantity, $invoiceProduct->product_id);

    //                 //For Notification
    //                 $setting = Utility::settings(\Auth::user()->creatorId());
    //                 $customer = Customer::find($request->customer_id);
    //                 $invoiceNotificationArr = [
    //                     'invoice_number' => \Auth::user()->invoiceNumberFormat($invoice->invoice_id),
    //                     'user_name' => \Auth::user()->name,
    //                     'invoice_issue_date' => $invoice->issue_date,
    //                     'invoice_due_date' => $invoice->due_date,
    //                     'customer_name' => $customer->name,
    //                 ];
    //                 //Slack Notification
    //                 if (isset($setting['invoice_notification']) && $setting['invoice_notification'] == 1) {
    //                     Utility::send_slack_msg('new_invoice', $invoiceNotificationArr);
    //                 }
    //                 //Telegram Notification
    //                 if (isset($setting['telegram_invoice_notification']) && $setting['telegram_invoice_notification'] == 1) {
    //                     Utility::send_telegram_msg('new_invoice', $invoiceNotificationArr);
    //                 }
    //                 //Twilio Notification
    //                 if (isset($setting['twilio_invoice_notification']) && $setting['twilio_invoice_notification'] == 1) {
    //                     Utility::send_twilio_msg($customer->contact, 'new_invoice', $invoiceNotificationArr);
    //                 }

    //                 $type = 'invoice';
    //                 $type_id = $invoice->id;
    //                 $description = $invoiceProduct->quantity . '  ' . __(' quantity sold in invoice') . ' ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
    //                 Utility::addProductStock($invoiceProduct->product_id, $invoiceProduct->quantity, $type, $description, $type_id);

    //             }
    //             $data['id'] = $invoice->id;
    //             $data['no'] = $invoice->invoice_id;
    //             $data['date'] = $invoice->issue_date;
    //             $data['created_at'] = date('Y-m-d', strtotime($invoice->issue_date)) . ' ' . date('h:i:s');
    //             $data['reference'] = $invoice->ref_number;
    //             $data['category'] = 'Invoice';
    //             $data['owned_by'] = $invoice->owned_by;
    //             $data['created_by'] = $invoice->created_by;
    //             $data['prod_id'] = $invoiceProduct->product_id;
    //             $data['items'] = $newitems;
    //             $dataret = Utility::jrentry($data);
    //             $invoice->voucher_id = $dataret;
    //             $invoice->save();

    //             $us_mail = 'false';
    //             $us_notify = 'false';
    //             $us_approve = 'false';
    //             $usr_Notification = [];
    //             $workflow = WorkFlow::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'crm')->where('status', 1)->first();
    //             if ($workflow) {
    //                 $workflowaction = WorkFlowAction::where('workflow_id', $workflow->id)->where('status', 1)->where('level_id', 4)->get();
    //                 foreach ($workflowaction as $action) {
    //                     $useraction = json_decode($action->assigned_users);
    //                     if ('create-invoice' == $action->node_id) {
    //                         if (@$useraction != '') {

    //                             $useraction = json_decode($useraction);
    //                             foreach ($useraction as $anyaction) {
    //                                 // make new user array
    //                                 if ($anyaction->type == 'user') {
    //                                     $usr_Notification[] = $anyaction->id;
    //                                 }
    //                             }
    //                         }
    //                         $raw_json = trim($action->applied_conditions, '"');
    //                         $cleaned_json = stripslashes($raw_json);
    //                         $applied_conditions = json_decode($cleaned_json, true);

    //                         if (isset($applied_conditions['conditions']) && is_array($applied_conditions['conditions'])) {
    //                             $arr = [
    //                                 'category' => 'category_name',
    //                                 'customer' => 'customer_name',
    //                                 'referance number' => 'ref_number',
    //                             ];
    //                             $relate = [
    //                                 'category_name' => 'category',
    //                                 'customer_name' => 'customer',
    //                             ];

    //                             foreach ($applied_conditions['conditions'] as $conditionGroup) {

    //                                 if (in_array($conditionGroup['action'], ['send_email', 'send_notification', 'send_approval'])) {
    //                                     $query = Invoice::where('id', $invoice->id);
    //                                     foreach ($conditionGroup['conditions'] as $condition) {
    //                                         $field = $condition['field'];
    //                                         $operator = $condition['operator'];
    //                                         $value = $condition['value'];
    //                                         if (isset($arr[$field], $relate[$arr[$field]])) {
    //                                             $relatedField = strpos($arr[$field], '_') !== false ? explode('_', $arr[$field], 2)[1] : $arr[$field];
    //                                             $relation = $relate[$arr[$field]];

    //                                             // Apply condition to the related model
    //                                             $query->whereHas($relation, function ($relatedQuery) use ($relatedField, $operator, $value) {
    //                                                 $relatedQuery->where($relatedField, $operator, $value);
    //                                             });
    //                                         } else {
    //                                             // Apply condition directly to the contract model
    //                                             $query->where($arr[$field], $operator, $value);
    //                                         }
    //                                     }
    //                                     $result = $query->first();

    //                                     if (!empty($result)) {
    //                                         if ($conditionGroup['action'] === 'send_email') {
    //                                             $us_mail = 'true';
    //                                         } elseif ($conditionGroup['action'] === 'send_notification') {
    //                                             $us_notify = 'true';
    //                                         } elseif ($conditionGroup['action'] === 'send_approval') {
    //                                             $us_approve = 'true';
    //                                         }
    //                                     }
    //                                 }
    //                             }
    //                         }
    //                         if ($us_mail == 'true') {
    //                             // email send
    //                         }
    //                         if ($us_notify == 'true' || $us_approve == 'true') {
    //                             // notification generate
    //                             if (count($usr_Notification) > 0) {
    //                                 $usr_Notification[] = Auth::user()->creatorId();
    //                                 foreach ($usr_Notification as $usrLead) {
    //                                     $data = [
    //                                         "updated_by" => Auth::user()->id,
    //                                         "data_id" => $invoice->id,
    //                                         "name" => '',
    //                                     ];
    //                                     if ($us_notify == 'true') {
    //                                         Utility::makeNotification($usrLead, 'create_invoice', $data, $invoice->id, 'create Invoice');
    //                                     } elseif ($us_approve == 'true') {
    //                                         Utility::makeNotification($usrLead, 'approve_invoice', $data, $invoice->id, 'For Approval Invoice');
    //                                     }
    //                                 }
    //                             }
    //                         }
    //                     }
    //                 }
    //             }
    //             //Product Stock Report
    //             // $type = 'invoice';
    //             // $type_id = $invoice->id;
    //             // StockReport::where('type', '=', 'invoice')->where('type_id', '=', $invoice->id)->delete();
    //             // $description = $invoiceProduct->quantity . '  ' . __(' quantity sold in invoice') . ' ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
    //             // Utility::addProductStock($invoiceProduct->product_id, $invoiceProduct->quantity, $type, $description, $type_id);

    //             //webhook
    //             $module = 'New Invoice';
    //             $webhook = Utility::webhookSetting($module);
    //             if ($webhook) {
    //                 $parameter = json_encode($invoice);
    //                 $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
    //                 if ($status == true) {
    //                     // Log
    //                     Utility::makeActivityLog(\Auth::user()->id, 'Invoice', $invoiceProduct->id, 'Create Invoice', $invoiceProduct->description);
    //                     \DB::commit();
    //                     return redirect()->route('invoice.index', $invoice->id)->with('success', __('Invoice successfully created.'));
    //                 } else {
    //                     \DB::commit();
    //                     return redirect()->back()->with('error', __('Webhook call failed.'));
    //                 }
    //             }
    //             Utility::makeActivityLog(\Auth::user()->id, 'Invoice', $invoiceProduct->id, 'Create Invoice', $invoiceProduct->description);
    //             \DB::commit();
    //             return redirect()->route('invoice.index', $invoice->id)->with('success', __('Invoice successfully created.'));
    //         } else {
    //             return redirect()->back()->with('error', __('Permission denied.'));
    //         }
    //     } catch (\Exception $e) {
    //         \DB::rollBack();
    //         dd($e);
    //         return redirect()->back()->with('error', __($e->getMessage()));
    //     }
    // }

    public function store(Request $request)
    {
        dd($request->all());
        \DB::beginTransaction();
        try {
            if (\Auth::user()->can('create invoice')) {
                $validator = \Validator::make($request->all(), [
                    'customer_id' => 'required',
                    'issue_date' => 'required',
                    'due_date' => 'required',
                    'items' => 'required',
                    'items_payload' => 'nullable', // Accept as string (JSON) or array
                    'subtotal' => 'nullable|numeric',
                    'taxable_subtotal' => 'nullable|numeric',
                    'total_discount' => 'nullable|numeric',
                    'total_tax' => 'nullable|numeric',
                    'sales_tax_amount' => 'nullable|numeric',
                    'total_amount' => 'nullable|numeric',
                    'bill_to' => 'nullable|string',
                    'ship_to' => 'nullable|string',
                    'terms' => 'nullable|string',
                    'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                    'attachments.*' => 'nullable|file|max:20480', // 20MB max per file
                ]);

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    if ($request->ajax()) {
                        return response()->json(['errors' => $validator->errors()], 422);
                    }
                    return redirect()->back()->with('error', $messages->first());
                }

                /** --------------------------------------------
                 * Recurring helper (added; non-breaking)
                 * - recurring_every_n is treated as TOTAL COUNT (master + children)
                 * - master keeps recurring_parent_id = NULL
                 * - next_run_at is scheduled strictly after start date
                 * -------------------------------------------- */
                $isRecurring = $request->input('recurring') === 'yes';
                $recurringWhen = $request->input('recurring_when', 'future'); // now|future
                $recurringStartDate = $request->input('recurring_start_date');
                $recurringEndType = $request->input('recurring_end_type', 'never'); // never|by
                $recurringEndDate = $request->input('recurring_end_date');
                $recurringRepeat = $request->input('recurring_repeat', 'monthly'); // monthly|quarterly|6months|yearly
                $recurringEveryN = (int) $request->input('recurring_every_n', 1); // TOTAL count requested
                $recurringEveryN = max(1, $recurringEveryN);

                // If "When to charge" = now, auto set start date to today if empty
                if ($isRecurring && $recurringWhen === 'now' && empty($recurringStartDate)) {
                    $recurringStartDate = \Carbon\Carbon::today()->toDateString();
                }

                // Create Invoice
                $invoice = new Invoice();
                $invoice->invoice_id = $this->invoiceNumber();
                $invoice->customer_id = $request->customer_id;
                $invoice->status = 0; // Draft by default
                $invoice->issue_date = $request->issue_date;
                $invoice->due_date = $request->due_date;
                $invoice->category_id = $request->category_id ?? 1;
                $invoice->ref_number = $request->ref_number;
                $invoice->created_by = \Auth::user()->creatorId();
                $invoice->owned_by = \Auth::user()->ownedId();

                // Store calculated totals
                $invoice->subtotal = $request->subtotal ?? 0;
                $invoice->taxable_subtotal = $request->taxable_subtotal ?? 0;
                $invoice->total_discount = $request->total_discount ?? 0;
                $invoice->total_tax = $request->total_tax ?? 0;
                $invoice->sales_tax_amount = $request->sales_tax_amount ?? 0;
                $invoice->total_amount = $request->total_amount ?? 0;
                $invoice->tax_id = $request->tax_id;
                $invoice->tax_rate = $request->tax_rate;
                $invoice->memo = $request->memo;
                $invoice->note = $request->note;

                // Store bill_to, ship_to and terms
                // $invoice->bill_to = $request->bill_to;
                // $invoice->ship_to = $request->ship_to;
                $invoice->terms = $request->terms;

                // Handle logo upload
                if ($request->hasFile('company_logo')) {
                    $logoFile = $request->file('company_logo');
                    $logoName = time() . '_logo.' . $logoFile->getClientOriginalExtension();
                    $logoFile->storeAs('uploads/invoice_logos', $logoName, 'public');
                    $invoice->logo = $logoName;
                }

                // Handle attachments
                if ($request->hasFile('attachments')) {
                    $attachments = [];
                    foreach ($request->file('attachments') as $attachment) {
                        $attachmentName = time() . '_' . uniqid() . '.' . $attachment->getClientOriginalExtension();
                        $attachment->storeAs('uploads/invoice_attachments', $attachmentName, 'public');
                        $attachments[] = $attachmentName;
                    }
                    $invoice->attachments = json_encode($attachments);
                }

                // --- NEW: set recurring fields (master row)
                if ($isRecurring) {
                    $invoice->is_recurring = true;
                    $invoice->recurring_repeat = $recurringRepeat;

                    // Store remaining CHILD count on master (total-1 accounts for this master)
                    $invoice->recurring_every_n = max(0, $recurringEveryN - 1);

                    $invoice->recurring_end_type = in_array($recurringEndType, ['never', 'by']) ? $recurringEndType : 'never';
                    $invoice->recurring_start_date = $recurringStartDate ?: \Carbon\Carbon::today()->toDateString();
                    $invoice->recurring_end_date = $invoice->recurring_end_type === 'by' ? $recurringEndDate : null;

                    // master must NOT point to itself
                    $invoice->recurring_parent_id = null;
                }

                $invoice->save();

                // --- NEW: compute next_run_at strictly after start date (only if children remain)
                if ($isRecurring) {
                    $start = \Carbon\Carbon::parse($invoice->recurring_start_date)->startOfDay();

                    $baseMonths = match ($invoice->recurring_repeat) {
                        'monthly' => 1,
                        'quarterly' => 4,
                        '6months' => 6,
                        'yearly' => 12,
                        default => 1,
                    };

                    $nextRun = $start->copy()->addMonthsNoOverflow($baseMonths);

                    if ((int) $invoice->recurring_every_n > 0) {
                        if ($invoice->recurring_end_type === 'by' && !empty($invoice->recurring_end_date)) {
                            $endBy = \Carbon\Carbon::parse($invoice->recurring_end_date)->endOfDay();
                            $invoice->next_run_at = $nextRun > $endBy ? null : $nextRun->toDateTimeString();
                        } else {
                            $invoice->next_run_at = $nextRun->toDateTimeString();
                        }
                    } else {
                        $invoice->next_run_at = null;
                    }

                    $invoice->save();
                }

                // Save Custom Fields
                CustomField::saveData($invoice, $request->customField);

                // Parse items - handle both array and JSON format
                $products = $request->items;
                if (is_string($products)) {
                    $products = json_decode($products, true);
                }

                // If items_payload is provided, use ALL items (products, subtotals, text)
                $itemsPayload = $request->items_payload;
                if ($itemsPayload) {
                    // If it's a JSON string, decode it first
                    if (is_string($itemsPayload)) {
                        $itemsPayload = json_decode($itemsPayload, true);
                    }

                    // Use all items from payload (no filtering)
                    if (is_array($itemsPayload)) {
                        $products = $itemsPayload;
                    }
                }

                $newitems = $products;
                // dd($products);
                foreach ($products as $i => $prod) {
                    $invoiceProduct = new InvoiceProduct();
                    $invoiceProduct->invoice_id = $invoice->id;

                    // Determine item type
                    $itemType = $prod['type'] ?? 'product';

                    // Set line_type, estimate_id and proposal_product_id if present (for items from estimates)
                    $invoiceProduct->line_type = $prod['line_type'] ?? null;
                    $invoiceProduct->estimate_id = $prod['estimate_id'] ?? null;
                    $invoiceProduct->proposal_product_id = $prod['proposal_product_id'] ?? null;

                    if ($itemType === 'product') {
                        // Handle product items
                        $invoiceProduct->product_id = $prod['item_id'] ?? ($prod['item'] ?? null);
                        $invoiceProduct->quantity = $prod['quantity'] ?? 0;
                        $invoiceProduct->tax = $prod['tax'] ?? null;
                        $invoiceProduct->discount = $prod['discount'] ?? 0;
                        $invoiceProduct->price = $prod['price'] ?? 0;
                        $invoiceProduct->description = $prod['description'] ?? '';
                        $invoiceProduct->taxable = $prod['is_taxable'] ?? ($prod['taxable'] ?? 0);
                        $invoiceProduct->item_tax_price = $prod['itemTaxPrice'] ?? ($prod['item_tax_price'] ?? 0);
                        $invoiceProduct->item_tax_rate = $prod['itemTaxRate'] ?? ($prod['item_tax_rate'] ?? 0);
                        $invoiceProduct->amount = $prod['amount'] ?? 0;

                        // Inventory management for products only
                        if ($invoiceProduct->product_id) {
                            Utility::total_quantity('minus', $invoiceProduct->quantity, $invoiceProduct->product_id);

                            // Stock Log
                            $type = 'invoice';
                            $type_id = $invoice->id;
                            $description = $invoiceProduct->quantity . ' ' . __(' quantity sold in invoice ') . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
                            Utility::addProductStock($invoiceProduct->product_id, $invoiceProduct->quantity, $type, $description, $type_id);
                        }
                    } elseif ($itemType === 'subtotal') {
                        // Handle subtotal items
                        $invoiceProduct->product_id = null;
                        $invoiceProduct->quantity = 0;
                        $invoiceProduct->price = 0;
                        $invoiceProduct->description = $prod['label'] ?? 'Subtotal';
                        $invoiceProduct->amount = $prod['amount'] ?? 0;
                        $invoiceProduct->discount = 0;
                        $invoiceProduct->tax = null;
                        $invoiceProduct->taxable = 0;
                        $invoiceProduct->item_tax_price = 0;
                        $invoiceProduct->item_tax_rate = 0;
                    } elseif ($itemType === 'text') {
                        // Handle text items
                        $invoiceProduct->product_id = null;
                        $invoiceProduct->quantity = 0;
                        $invoiceProduct->price = 0;
                        $invoiceProduct->description = $prod['text'] ?? '';
                        $invoiceProduct->amount = 0;
                        $invoiceProduct->discount = 0;
                        $invoiceProduct->tax = null;
                        $invoiceProduct->taxable = 0;
                        $invoiceProduct->item_tax_price = 0;
                        $invoiceProduct->item_tax_rate = 0;
                    }

                    $invoiceProduct->save();
                    $newitems[$i]['prod_id'] = $invoiceProduct->id;
                }

                // Update estimate status based on invoiced items
                $this->updateEstimateStatusAfterInvoice($products);

                // Notifications (Slack, Telegram, Twilio)
                $setting = Utility::settings(\Auth::user()->creatorId());
                $customer = Customer::find($request->customer_id);
                $notifData = [
                    'invoice_number' => \Auth::user()->invoiceNumberFormat($invoice->invoice_id),
                    'user_name' => \Auth::user()->name,
                    'invoice_issue_date' => $invoice->issue_date,
                    'invoice_due_date' => $invoice->due_date,
                    'customer_name' => $customer->name,
                ];

                if (isset($setting['invoice_notification']) && $setting['invoice_notification'] == 1) {
                    Utility::send_slack_msg('new_invoice', $notifData);
                }
                if (isset($setting['telegram_invoice_notification']) && $setting['telegram_invoice_notification'] == 1) {
                    Utility::send_telegram_msg('new_invoice', $notifData);
                }
                if (isset($setting['twilio_invoice_notification']) && $setting['twilio_invoice_notification'] == 1) {
                    Utility::send_twilio_msg($customer->contact, 'new_invoice', $notifData);
                }

                $us_mail = 'false';
                $us_notify = 'false';
                $us_approve = 'false';
                $usr_Notification = [];
                $workflow = WorkFlow::where('created_by', '=', \Auth::user()->creatorId())
                    ->where('module', '=', 'crm')
                    ->where('status', 1)
                    ->first();
                if ($workflow) {
                    $workflowaction = WorkFlowAction::where('workflow_id', $workflow->id)->where('status', 1)->where('level_id', 4)->get();
                    foreach ($workflowaction as $action) {
                        $useraction = json_decode($action->assigned_users);
                        if ('create-invoice' == $action->node_id) {
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
                                    'category' => 'category_name',
                                    'customer' => 'customer_name',
                                    'referance number' => 'ref_number',
                                ];
                                $relate = [
                                    'category_name' => 'category',
                                    'customer_name' => 'customer',
                                ];

                                foreach ($applied_conditions['conditions'] as $conditionGroup) {
                                    if (in_array($conditionGroup['action'], ['send_email', 'send_notification', 'send_approval'])) {
                                        $query = Invoice::where('id', $invoice->id);
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
                                            'updated_by' => Auth::user()->id,
                                            'data_id' => $invoice->id,
                                            'name' => '',
                                        ];
                                        if ($us_notify == 'true') {
                                            Utility::makeNotification($usrLead, 'create_invoice', $data, $invoice->id, 'create Invoice');
                                            $invoice->status = 6; // Under Approval
                                            $invoice->save();
                                        } elseif ($us_approve == 'true') {
                                            Utility::makeNotification($usrLead, 'approve_invoice', $data, $invoice->id, 'For Approval Invoice');
                                            $invoice->status = 6; // Under Approval
                                            $invoice->save();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                //Product Stock Report
                // $type = 'invoice';
                // $type_id = $invoice->id;
                // StockReport::where('type', '=', 'invoice')->where('type_id', '=', $invoice->id)->delete();
                // $description = $invoiceProduct->quantity . '  ' . __(' quantity sold in invoice') . ' ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
                // Utility::addProductStock($invoiceProduct->product_id, $invoiceProduct->quantity, $type, $description, $type_id);
                if (Auth::user()->type == 'company') {
                    $this->createInvoiceJournalVoucher($invoice);
                    // $this->approveInvoice($invoice->id);
                    $invoice->status = 6; // Approved
                    $invoice->save();
                    Utility::makeActivityLog(\Auth::user()->id, 'Invoice', $invoice->id, 'Create Invoice', 'Invoice Created & Approved');
                }

                // Webhook
                $module = 'New Invoice';
                $webhook = Utility::webhookSetting($module);
                if ($webhook) {
                    $parameter = json_encode($invoice);
                    $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                    if (!$status) {
                        \DB::commit();
                        return redirect()->back()->with('error', __('Webhook call failed.'));
                    }
                }

                Utility::makeActivityLog(\Auth::user()->id, 'Invoice', $invoice->id, 'Create Invoice', 'Invoice Created (Pending Approval)');

                \DB::commit();

                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => __('Invoice successfully created and waiting for approval.'),
                        'redirect' => route('invoice.index'),
                    ]);
                }

                return redirect()->route('invoice.index')->with('success', __('Invoice successfully created and waiting for approval.'));
            } else {
                if ($request->ajax()) {
                    return response()->json(['error' => __('Permission denied.')], 403);
                }
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            dd($e);
            \Log::error('Invoice creation error: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['error' => __('An error occurred while creating the invoice.')], 500);
            }

            return redirect()
                ->back()
                ->with('error', __($e->getMessage()));
        }
    }

    private function createInvoiceJournalVoucher(Invoice $invoice)
    {
        $invoiceProducts = $invoice->items; // must have relation in Invoice model
        // dd($invoiceProducts,'invpro');
        $newitems = [];

        // Include all items (products, subtotals, text lines)
        // Utility::jrentry will handle skipping non-product items safely
        foreach ($invoiceProducts as $product) {
            $newitems[] = [
                'prod_id' => $product->id,
                'item' => $product->product_id,
                'quantity' => $product->quantity,
                'price' => $product->price,
                'discount' => $product->discount,
                'itemTaxPrice' => $product->tax,
                'description' => $product->description,
            ];
        }

        $data = [
            'id' => $invoice->id,
            'no' => $invoice->invoice_id,
            'date' => $invoice->issue_date,
            'created_at' => now()->format('Y-m-d h:i:s'),
            'reference' => $invoice->ref_number,
            'category' => 'Invoice',
            'owned_by' => $invoice->owned_by,
            'created_by' => $invoice->created_by,
            'prod_id' => $invoiceProducts->where('product_id', '!=', null)->first()->product_id ?? null,
            'items' => $newitems,
            'customer_id' => $invoice->customer_id,
            'total' => $invoice->getTotal(),
        ];

        $voucherId = Utility::jrentry($data);
        $invoice->voucher_id = $voucherId;
        $invoice->save();

        return $voucherId;
    }
    public function approveInvoice($id)
    {
        \DB::beginTransaction();
        try {
            $invoice = Invoice::findOrFail($id);

            // Check if already approved
            if ($invoice->status == 6) {
                return redirect()->back()->with('error', __('Invoice already approved.'));
            }

            // Check if in pending approval status
            if ($invoice->status != 5 && $invoice->status != 0) {
                return redirect()->back()->with('error', __('Invoice must be in pending approval status.'));
            }

            // Update status to Approved (6)
            $invoice->status = 6;
            // $invoice->approved_date = now();

            // Create Journal Voucher
            $this->createInvoiceJournalVoucher($invoice);

            $invoice->save();

            Utility::makeActivityLog(\Auth::user()->id, 'Invoice', $invoice->id, 'Approve Invoice', 'Invoice approved and JV posted');

            // Send notification to invoice creator
            $data = [
                'updated_by' => \Auth::user()->id,
                'data_id' => $invoice->id,
                'name' => '',
            ];
            Utility::makeNotification($invoice->created_by, 'invoice_approved', $data, $invoice->id, 'Invoice Approved');

            \DB::commit();
            return redirect()->route('invoice.index')->with('success', __('Invoice approved successfully and JV posted.'));
        } catch (\Exception $e) {
            \DB::rollBack();
            dd($e);
            \Log::error('Invoice Approval Error: ' . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', __('Error approving invoice: ' . $e->getMessage()));
        }
    }

    public function rejectInvoice($id)
    {
        \DB::beginTransaction();
        try {
            $invoice = Invoice::findOrFail($id);

            // Check if already approved or rejected
            if ($invoice->status == 6) {
                return redirect()->back()->with('error', __('Cannot reject an approved invoice.'));
            }

            if ($invoice->status == 7) {
                return redirect()->back()->with('error', __('Invoice already rejected.'));
            }

            // Check if in pending approval status
            if ($invoice->status != 5) {
                return redirect()->back()->with('error', __('Invoice must be in pending approval status.'));
            }

            // Update status to Rejected (7)
            $invoice->status = 7;
            $invoice->rejected_date = now();
            $invoice->rejected_by = \Auth::user()->id;
            $invoice->save();

            Utility::makeActivityLog(\Auth::user()->id, 'Invoice', $invoice->id, 'Reject Invoice', 'Invoice rejected');

            // Send notification to invoice creator
            $data = [
                'updated_by' => \Auth::user()->id,
                'data_id' => $invoice->id,
                'name' => '',
            ];
            Utility::makeNotification($invoice->created_by, 'invoice_rejected', $data, $invoice->id, 'Invoice Rejected');

            \DB::commit();
            return redirect()->route('invoice.index')->with('success', __('Invoice rejected successfully.'));
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Invoice Rejection Error: ' . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', __('Error rejecting invoice: ' . $e->getMessage()));
        }
    }

    public function requestApproval($id)
    {
        \DB::beginTransaction();
        try {
            $invoice = Invoice::findOrFail($id);

            // Check if already approved
            if ($invoice->status == 6) {
                return redirect()->back()->with('error', __('Invoice already approved.'));
            }

            // Check if already in pending approval
            if ($invoice->status == 5) {
                return redirect()->back()->with('error', __('Invoice already sent for approval.'));
            }

            // Check if already sent
            if (in_array($invoice->status, [1, 2, 3, 4])) {
                return redirect()->back()->with('error', __('Cannot request approval for a sent or paid invoice.'));
            }

            // Update status to Pending Approval (5)
            $invoice->status = 5;
            $invoice->save();

            Utility::makeActivityLog(\Auth::user()->id, 'Invoice', $invoice->id, 'Request Approval', 'Invoice sent for approval');

            // Clear old notifications
            Notification::where('data_id', $invoice->id)->where('type', 'approval_request_invoice')->where('is_read', 0)->delete();

            // Send notification to approver (creator or designated approver)
            $usrLead = \Auth::user()->creatorId();
            $data = [
                'updated_by' => \Auth::user()->id,
                'data_id' => $invoice->id,
                'name' => '',
            ];
            Utility::makeNotification($usrLead, 'approval_request_invoice', $data, $invoice->id, 'Invoice Approval Request');

            \DB::commit();
            return redirect()->back()->with('success', __('Invoice sent for approval successfully.'));
        } catch (\Exception $e) {
            \DB::rollBack();
            dd($e);
            \Log::error('Request Approval Error: ' . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', __('Error requesting approval: ' . $e->getMessage()));
        }
    }

    public function edit($ids)
    {
        if (\Auth::user()->can('edit invoice')) {
            $id = Crypt::decrypt($ids);
            $invoice = Invoice::find($id);
            $invoice_number = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = $user->type == 'company' ? 'created_by' : 'owned_by';
            $customers = Customer::where($column, $ownerId)->get()->pluck('name', 'id')->toArray();
            $customers = ['__add__' => '➕ Add new customer'] + ['' => 'Select Customer'] + $customers;
            $category = ProductServiceCategory::where($column, $ownerId)->where('type', 'income')->get()->pluck('name', 'id')->toArray();
            $category = ['__add__' => '➕ Add new category'] + ['' => 'Select Category'] + $category;
            $product_services = ProductService::where($column, $ownerId)->get()->pluck('name', 'id');
            $product_services->prepend('--', '');
            $taxes = Tax::where('created_by', \Auth::user()->creatorId())->get();

            // Always return modal view for AJAX requests
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())
                ->where('module', '=', 'invoice')
                ->get();

            // Populate customer data
            $customerId = $invoice->customer_id;
            $customerData = Customer::find($customerId);
            $billTo = '';
            $shipTo = '';
            if ($customerData) {
                $billTo = $customerData->billing_name . "\n" . $customerData->billing_phone . "\n" . $customerData->billing_address . "\n" . $customerData->billing_city . ' , ' . $customerData->billing_state . ' , ' . $customerData->billing_country . '.' . "\n" . $customerData->billing_zip;

                $shipTo = $customerData->shipping_name . "\n" . $customerData->shipping_phone . "\n" . $customerData->shipping_address . "\n" . $customerData->shipping_city . ' , ' . $customerData->shipping_state . ' , ' . $customerData->shipping_country . '.' . "\n" . $customerData->shipping_zip;
            }

            // Load invoice items with product details
            $invoice->load(['items.product']);

            // Prepare logo URL if exists
            $logoUrl = null;
            if ($invoice->logo) {
                $logoUrl = asset('storage/uploads/invoice_logos/' . $invoice->logo);
            }

            // Prepare attachments with full URLs and metadata
            $attachmentsData = [];
            if ($invoice->attachments) {
                $attachmentFiles = json_decode($invoice->attachments, true);
                if (is_array($attachmentFiles)) {
                    foreach ($attachmentFiles as $index => $filename) {
                        $filePath = storage_path('app/public/uploads/invoice_attachments/' . $filename);
                        $fileSize = file_exists($filePath) ? filesize($filePath) : 0;

                        $attachmentsData[] = [
                            'id' => $index,
                            'name' => $filename,
                            'url' => asset('storage/uploads/invoice_attachments/' . $filename),
                            'size' => $fileSize,
                            'attach_to_email' => true, // Default to true for existing attachments
                        ];
                    }
                }
            }

            // Prepare invoice data for JavaScript
            $invoiceData = [
                'id' => $invoice->id,
                'encrypted_id' => Crypt::encrypt($invoice->id),
                'invoice_id' => $invoice->invoice_id,
                'customer_id' => $invoice->customer_id,
                'issue_date' => $invoice->issue_date,
                'due_date' => $invoice->due_date,
                'category_id' => $invoice->category_id,
                'ref_number' => $invoice->ref_number,
                'bill_to' => $invoice->bill_to,
                'ship_to' => $invoice->ship_to,
                'terms' => $invoice->terms,
                'logo' => $logoUrl,
                'attachments' => $attachmentsData,
                'subtotal' => $invoice->subtotal,
                'taxable_subtotal' => $invoice->taxable_subtotal,
                'total_discount' => $invoice->total_discount,
                'total_tax' => $invoice->total_tax,
                'sales_tax_amount' => $invoice->sales_tax_amount,
                'total_amount' => $invoice->total_amount,
                'memo' => $invoice->memo,
                'note' => $invoice->note,
                'items' => $invoice->items
                    ->map(function ($item) {
                        $type = 'product';
                        if (empty($item->product_id)) {
                            if ($item->amount != 0 || strtolower($item->description) == 'subtotal') {
                                $type = 'subtotal';
                            } else {
                                $type = 'text';
                            }
                        }

                        return [
                            'id' => $item->id,
                            'type' => $type,
                            'item' => $item->product_id,
                            'description' => $item->description,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'discount' => $item->discount,
                            'tax' => $item->tax,
                            'taxable' => $item->taxable,
                            'itemTaxPrice' => $item->item_tax_price,
                            'itemTaxRate' => $item->item_tax_rate,
                            'amount' => $item->amount,
                            'estimate_id' => $item->estimate_id,
                            'line_type' => $item->line_type,
                            'proposal_product_id' => $item->proposal_product_id,
                        ];
                    })
                    ->toArray(),
            ];
            // dd($invoiceData);
            return view('invoice.edit_modal', compact('customers', 'invoice', 'product_services', 'category', 'customFields', 'customerId', 'taxes', 'billTo', 'shipTo', 'invoiceData'))->with('mode', 'edit');
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }
    //     public function update(Request $request, Invoice $invoice)
    //     {

    //         if (\Auth::user()->can('edit invoice')) {
    //             if ($invoice->created_by == \Auth::user()->creatorId()) {
    //                 $validator = \Validator::make(
    //                     $request->all(),
    //                     [
    //                         'customer_id' => 'required',
    //                         'issue_date' => 'required',
    //                         'due_date' => 'required',
    //                         'category_id' => 'required',
    //                         'items' => 'required',
    //                     ]
    //                 );
    //                 if ($validator->fails()) {
    //                     $messages = $validator->getMessageBag();

    //                     return redirect()->route('invoice.index')->with('error', $messages->first());
    //                 }
    //                 $invoice->customer_id = $request->customer_id;
    //                 $invoice->issue_date = $request->issue_date;
    //                 $invoice->due_date = $request->due_date;
    //                 $invoice->ref_number = $request->ref_number;
    // //                $invoice->discount_apply = isset($request->discount_apply) ? 1 : 0;
    //                 $invoice->category_id = $request->category_id;
    //                 $invoice->save();

    //                 Utility::starting_number($invoice->invoice_id + 1, 'invoice');
    //                 CustomField::saveData($invoice, $request->customField);

    //                 $voucher = JournalEntry::where('category', 'Invoice')->where('reference_id', $invoice->id)->where('voucher_type', 'JV')->first();
    //                 $products = $request->items;
    //                 StockReport::where('type', '=', 'invoice')->where('type_id', '=', $invoice->id)->delete();
    //                 // dd('noman');
    //                 for ($i = 0; $i < count($products); $i++) {
    //                     $invoiceProduct = InvoiceProduct::find($products[$i]['id']);
    //                     $tax = 0;
    //                     if ($invoiceProduct == null) {
    //                         $invoiceProduct = new InvoiceProduct();
    //                         $invoiceProduct->invoice_id = $invoice->id;
    //                         $invoiceProduct->product_id = $products[$i]['item'];
    //                         $invoiceProduct->quantity = $products[$i]['quantity'];
    //                         $invoiceProduct->tax = $products[$i]['tax'];
    //                         $invoiceProduct->discount = $products[$i]['discount'];
    //                         $invoiceProduct->price = $products[$i]['price'];
    //                         $invoiceProduct->description = $products[$i]['description'];
    //                         $invoiceProduct->save();
    //                         $invoiceProduct->created_at = date('Y-m-d H:i:s', strtotime($invoice->created_at));
    //                         $invoiceProduct->updated_at = date('Y-m-d H:i:s', strtotime($invoice->created_at));
    //                         $invoiceProduct->save();

    //                         $product = ProductService::where('id',$invoiceProduct->product_id)->first();

    //                         $journalItem              = new JournalItem();
    //                         $journalItem->journal     = $voucher->id;
    //                         $journalItem->account     = @$product->sale_chartaccount_id;
    //                         $journalItem->product_ids  = $invoiceProduct->id;
    //                         $journalItem->description  = $invoiceProduct->description;
    //                         $journalItem->credit       = ((floatval($products[$i]['quantity']) * floatval($products[$i]['price']))- floatval($products[$i]['discount']));
    //                         $journalItem->debit        =  0;
    //                         $journalItem->save();
    //                         $journalItem->created_at   =  date('Y-m-d H:i:s', strtotime($invoice->created_at));
    //                         $journalItem->updated_at   =  date('Y-m-d H:i:s', strtotime($invoice->created_at));
    //                         $journalItem->save();
    //                         $tax += floatval($products[$i]['itemTaxPrice']);
    //                         $reciveable += ((floatval($products[$i]['quantity']) * floatval($products[$i]['price']))- floatval($products[$i]['discount'])) + floatval($products[$i]['itemTaxPrice']);

    //                         $dataline = [
    //                             'account_id' => $product->sale_chartaccount_id,
    //                             'transaction_type' => 'Credit',
    //                             'transaction_amount' => $journalItem->credit,
    //                             'reference' => 'Invoice Journal',
    //                             'reference_id' => $voucher->id,
    //                             'reference_sub_id' => $journalItem->id,
    //                             'date' => $voucher->date,
    //                             'created_at' => date('Y-m-d H:i:s', strtotime($invoice->created_at)),
    //                             'product_id' => $invoice->id,
    //                             'product_type' => 'Invoice',
    //                             'product_item_id' => $invoiceProduct->id,
    //                         ];
    //                         Utility::addTransactionLines($dataline , 'create');

    //                         if($tax != 0){
    //                             $accounttax = Tax::where('id', $product->tax_id)->first();
    //                             $account_tax = ChartOfAccount::where('id', $accounttax->account_id)->first();
    //                             if(!$account_tax){
    //                                 $types_t = ChartOfAccountType::where('created_by', '=', $invoice->created_by)->where('name', 'Liabilities')->first();
    //                                 if ($types_t) {
    //                                     $sub_type_t = ChartOfAccountSubType::where('type', $types_t->id)->where('name', 'Current Liabilities')->first();
    //                                     $account_tax = ChartOfAccount::where('type', $types_t->id)->where('sub_type', $sub_type_t->id)->where('name', 'TAX')->first();
    //                                     if(!$account_tax){
    //                                         $account_tax = ChartOfAccount::create([
    //                                             'name' => 'TAX',
    //                                             'code' => '10000',
    //                                             'type' => $types_t->id,
    //                                             'sub_type' => $sub_type_t->id,
    //                                             'is_enabled' => 1,
    //                                             'created_by' => $invoice->created_by,
    //                                         ]);
    //                                     }
    //                                 }
    //                             }

    //                             if($account_tax){
    //                                 $journalItem              = new JournalItem();
    //                                 $journalItem->journal     = $voucher->id;
    //                                 $journalItem->account     = @$account_tax->id;
    //                                 $journalItem->prod_tax_id  = $invoiceProduct->id;
    //                                 $journalItem->description = 'Tax on Invoice No : '.@$invoice->invoice_id;
    //                                 $journalItem->credit       =  $tax;
    //                                 $journalItem->debit        = 0;
    //                                 $journalItem->save();
    //                                 $journalItem->created_at   = date('Y-m-d H:i:s', strtotime($invoice->created_at));
    //                                 $journalItem->updated_at   = date('Y-m-d H:i:s', strtotime($invoice->created_at));
    //                                 $journalItem->save();

    //                                 $dataline = [
    //                                         'account_id' => $account_tax->id,
    //                                         'transaction_type' => 'Credit',
    //                                         'transaction_amount' => $journalItem->credit,
    //                                         'reference' => 'Invoice Journal',
    //                                         'reference_id' => $voucher->id,
    //                                         'reference_sub_id' => $journalItem->id,
    //                                         'date' => $voucher->date,
    //                                         'created_at' => date('Y-m-d H:i:s', strtotime($invoice->created_at)),
    //                                         'product_id' => $invoice->id,
    //                                         'product_type' => 'Invoice Tax',
    //                                         'product_item_id' => $invoiceProduct->id,
    //                                 ];
    //                                 Utility::addTransactionLines($dataline , 'create');
    //                             }
    //                         }

    //                         Utility::total_quantity('minus', $products[$i]['quantity'], $products[$i]['item']);
    //                          $type = 'invoice';
    //                         $type_id = $invoice->id;
    //                         // StockReport::where('type', '=', 'invoice')->where('type_id', '=', $invoice->id)->delete();
    //                         $description = $products[$i]['quantity'] . '  ' . __(' quantity sold in invoice') . ' ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
    //                         if (empty($products[$i]['id'])) {
    //                             Utility::addProductStock($products[$i]['item'], $products[$i]['quantity'], $type, $description, $type_id);
    //                         }
    //                         $updatePrice = ($products[$i]['price'] * $products[$i]['quantity']) + ($products[$i]['itemTaxPrice']) - ($products[$i]['discount']);
    //                         Utility::updateUserBalance('customer', $request->customer_id, $updatePrice, 'credit');

    //                     } else {

    //                         $invoiceProduct->quantity = $products[$i]['quantity'];
    //                         $invoiceProduct->tax = $products[$i]['tax'];
    //                         $invoiceProduct->discount = $products[$i]['discount'];
    //                         $invoiceProduct->price = $products[$i]['price'];
    //                         $invoiceProduct->description = $products[$i]['description'];
    //                         $invoiceProduct->save();
    //                         if($voucher){

    //                             $journalItem = JournalItem::where('journal', $voucher->id)->where('product_ids', $invoiceProduct->id)->first();
    //                             $journalItem->credit       = ((floatval($products[$i]['quantity']) * floatval($products[$i]['price']))- floatval($products[$i]['discount']));
    //                             $journalItem->save();
    //                             // also update transaction lines
    //                             $transaction_line = TransactionLines::where('reference_id',$invoice->voucher_id)->where('product_id',$invoice->id)->where('reference','Invoice Journal')->where('product_item_id',$invoiceProduct->id)->where('product_type','Invoice')->first();
    //                             $transaction_line->credit = $journalItem->credit;
    //                             $transaction_line->save();
    //                         }

    //                         $tax += floatval($products[$i]['itemTaxPrice']);
    //                         $reciveable += ((floatval($products[$i]['quantity']) * floatval($products[$i]['price']))- floatval($products[$i]['discount'])) + floatval($products[$i]['itemTaxPrice']);

    //                         if($tax != 0){
    //                             $journal_tax = JournalItem::where('journal', $voucher->id)->where('prod_tax_id', $invoiceProduct->id)->first();
    //                             $journal_tax->credit       = $tax;
    //                             $journal_tax->save();

    //                             // also update transaction lines
    //                             $transaction_tax = TransactionLines::where('reference_id',$invoice->voucher_id)->where('product_id',$invoice->id)->where('reference','Invoice Journal')->where('product_item_id',$invoiceProduct->id)->where('product_type','Invoice Tax')->first();
    //                             $transaction_tax->credit = $journal_tax->credit;
    //                             $transaction_tax->save();
    //                         }
    //                         Utility::total_quantity('plus', $invoiceProduct->quantity, $invoiceProduct->product_id);
    //                         //Product Stock Report
    //                         $type = 'invoice';
    //                         $type_id = $invoice->id;
    //                         $description = $products[$i]['quantity'] . '  ' . __(' quantity sold in invoice') . ' ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
    //                         Utility::addProductStock($invoiceProduct->product_id, $products[$i]['quantity'], $type, $description, $type_id);

    //                     }

    //                     $inv_receviable = TransactionLines::where('reference_id',$invoice->voucher_id)->where('reference','Invoice Journal')->where('product_type','Invoice Reciveable')->first();
    //                     $inv_receviable->debit = $reciveable;
    //                     $inv_receviable->save();

    //                     $types = ChartOfAccountType::where('created_by', '=', $invoice->created_by)->where('name', 'Assets')->first();
    //                     if ($types) {
    //                         $sub_type = ChartOfAccountSubType::where('type', $types->id)->where('name', 'Current Asset')->first();
    //                         $account = ChartOfAccount::where('type', $types->id)->where('sub_type', $sub_type->id)->where('name', 'Account Receivables')->first();
    //                     }
    //                     if($account){
    //                         $item_last = JournalItem::where('journal', $voucher->id)->where('account', $account->id)->first();
    //                         $item_last->debit = $reciveable;
    //                         $item_last->save();
    //                     }else{
    //                         $item_last = JournalItem::where('journal', $voucher->id)->where('id', $inv_receviable->reference_sub_id)->first();
    //                         $item_last->debit = $reciveable;
    //                         $item_last->save();
    //                     }

    //                     if (isset($products[$i]['item'])) {
    //                         $invoiceProduct->product_id = $products[$i]['item'];
    //                     }

    //                     if ($products[$i]['id'] > 0) {
    //                         Utility::total_quantity('minus', $products[$i]['quantity'], $invoiceProduct->product_id);
    //                     }

    //                     // //Product Stock Report
    //                     // $type = 'invoice';
    //                     // $type_id = $invoice->id;
    //                     // // StockReport::where('type', '=', 'invoice')->where('type_id', '=', $invoice->id)->delete();
    //                     // $description = $products[$i]['quantity'] . '  ' . __(' quantity sold in invoice') . ' ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
    //                     // if (!empty($products[$i]['id'])) {
    //                     //     dd($products, $products[$i]);
    //                     //     Utility::addProductStock($products[$i]['item'], $products[$i]['quantity'], $type, $description, $type_id);
    //                     // }

    //                 }

    //                 // TransactionLines::where('reference_id',$invoice->id)->where('reference','Invoice')->delete();

    //                 // $invoice_products = InvoiceProduct::where('invoice_id', $invoice->id)->get();
    //                 // foreach ($invoice_products as $invoice_product) {
    //                 //     $product = ProductService::find($invoice_product->product_id);
    //                 //     $totalTaxPrice = 0;
    //                 //     if($invoice_product->tax != null){
    //                 //         $taxes = \App\Models\Utility::tax($invoice_product->tax);
    //                 //         foreach ($taxes as $tax) {
    //                 //             $taxPrice = \App\Models\Utility::taxRate($tax->rate, $invoice_product->price, $invoice_product->quantity, $invoice_product->discount);
    //                 //             $totalTaxPrice += $taxPrice;
    //                 //         }
    //                 //     }

    //                 //     $itemAmount = ($invoice_product->price * $invoice_product->quantity) - ($invoice_product->discount) + $totalTaxPrice;

    //                 //     $data = [
    //                 //         'account_id' => $product->sale_chartaccount_id,
    //                 //         'transaction_type' => 'Credit',
    //                 //         'transaction_amount' => $itemAmount,
    //                 //         'reference' => 'Invoice',
    //                 //         'reference_id' => $invoice->id,
    //                 //         'reference_sub_id' => $product->id,
    //                 //         'date' => $invoice->issue_date,
    //                 //     ];
    //                 //     Utility::addTransactionLines($data , 'edit');
    //                 // }
    //                 //log
    //                 Utility::makeActivityLog(\Auth::user()->id,'Invoice',$invoice->id,'Update Invoice',$invoice->description);
    //                 return redirect()->route('invoice.index')->with('success', __('Invoice successfully updated.'));
    //             } else {
    //                 return redirect()->back()->with('error', __('Permission denied.'));
    //             }
    //         } else {
    //             return redirect()->back()->with('error', __('Permission denied.'));
    //         }
    //     }

    public function update(Request $request, $invoiceId)
    {
        // dd($request->all(),$invoiceId);
        \DB::beginTransaction();
        try {
            $id = $invoiceId;

            $invoice = Invoice::find($id);

            if (!$invoice) {
                if ($request->ajax()) {
                    return response()->json(['error' => __('Invoice not found.')], 404);
                }
                return redirect()->back()->with('error', __('Invoice not found.'));
            }

            if (\Auth::user()->can(abilities: 'edit invoice')) {
                if ($invoice->created_by == \Auth::user()->creatorId()) {
                    $validator = \Validator::make($request->all(), [
                        'customer_id' => 'required',
                        'issue_date' => 'required',
                        'due_date' => 'required',
                        'items' => 'required',
                        'items_payload' => 'nullable', // Accept as string (JSON) or array
                        // 'bill_to' => 'nullable|string',
                        // 'ship_to' => 'nullable|string',
                        'terms' => 'nullable|string',
                    ]);

                    if ($validator->fails()) {
                        $messages = $validator->getMessageBag();
                        dd($messages);
                        if (request()->ajax()) {
                            return response()->json(['errors' => $validator->errors()], 422);
                        }
                        return redirect()->route('invoice.index')->with('error', $messages->first());
                    }

                    // Update invoice basic details
                    $invoice->customer_id = $request->customer_id;
                    $invoice->issue_date = $request->issue_date;
                    $invoice->due_date = $request->due_date;
                    $invoice->ref_number = $request->ref_number;
                    // $invoice->category_id = $request->category_id;
                    $invoice->memo = $request->memo;
                    $invoice->note = $request->note;
                    // $invoice->bill_to = $request->bill_to;
                    // $invoice->ship_to = $request->ship_to;
                    $invoice->terms = $request->terms;

                    // Store calculated totals (Added for Update)
                    $invoice->subtotal = $request->subtotal ?? 0;
                    $invoice->taxable_subtotal = $request->taxable_subtotal ?? 0;
                    $invoice->total_discount = $request->total_discount ?? 0;
                    $invoice->total_tax = $request->total_tax ?? 0;
                    $invoice->sales_tax_amount = $request->sales_tax_amount ?? 0;
                    $invoice->total_amount = $request->total_amount ?? 0;
                    $invoice->tax_id = $request->tax_id;
                    $invoice->tax_rate = $request->tax_rate;

                    // Handle logo upload
                    if ($request->hasFile('company_logo')) {
                        // Delete old logo if exists
                        if ($invoice->logo) {
                            $oldLogoPath = storage_path('app/public/uploads/invoice_logos/' . $invoice->logo);
                            if (file_exists($oldLogoPath)) {
                                @unlink($oldLogoPath);
                            }
                        }

                        $logoFile = $request->file('company_logo');
                        $logoName = time() . '_logo.' . $logoFile->getClientOriginalExtension();
                        $logoFile->storeAs('uploads/invoice_logos', $logoName, 'public');
                        $invoice->logo = $logoName;
                    }

                    // Handle attachments
                    $existingAttachments = $invoice->attachments ? json_decode($invoice->attachments, true) : [];

                    // Handle deleted attachments
                    if ($request->has('delete_attachments')) {
                        $deleteIds = $request->delete_attachments;
                        foreach ($deleteIds as $deleteId) {
                            if (isset($existingAttachments[$deleteId])) {
                                $filename = $existingAttachments[$deleteId];
                                $filePath = storage_path('app/public/uploads/invoice_attachments/' . $filename);
                                if (file_exists($filePath)) {
                                    @unlink($filePath);
                                }
                                unset($existingAttachments[$deleteId]);
                            }
                        }
                    }

                    // Handle new attachments
                    if ($request->hasFile('attachments')) {
                        foreach ($request->file('attachments') as $attachment) {
                            $attachmentName = time() . '_' . uniqid() . '.' . $attachment->getClientOriginalExtension();
                            $attachment->storeAs('uploads/invoice_attachments', $attachmentName, 'public');
                            $existingAttachments[] = $attachmentName;
                        }
                    }

                    // Save updated attachments
                    $invoice->attachments = !empty($existingAttachments) ? json_encode(array_values($existingAttachments)) : null;

                    $invoice->save();

                    Utility::starting_number($invoice->invoice_id + 1, 'invoice');
                    CustomField::saveData($invoice, $request->customField);

                    // Check if invoice has been approved (has voucher)
                    $voucher = JournalEntry::where('category', 'Invoice')->where('reference_id', $invoice->id)->where('voucher_type', 'JV')->first();

                    // Parse items - handle both array and JSON format
                    $products = $request->items;
                    if (is_string($products)) {
                        $products = json_decode($products, true);
                    }

                    // If items_payload is provided, use ALL items (products, subtotals, text)
                    $itemsPayload = $request->items_payload;
                    if ($itemsPayload) {
                        // If it's a JSON string, decode it first
                        if (is_string($itemsPayload)) {
                            $itemsPayload = json_decode($itemsPayload, true);
                        }

                        // Use all items from payload (no filtering)
                        if (is_array($itemsPayload)) {
                            $products = $itemsPayload;
                        }
                    }

                    $isApproved = !is_null($voucher);
                    // dd($products);
                    if ($isApproved) {
                        // SCENARIO 1: Invoice is approved - Update journal entries
                        $this->updateApprovedInvoice($invoice, $voucher, $products, $request);
                    } else {
                        // SCENARIO 2: Invoice is not approved yet - Just update invoice products
                        $this->updateDraftInvoice($invoice, $products);
                    }

                    // Log activity
                    Utility::makeActivityLog(\Auth::user()->id, 'Invoice', $invoice->id, 'Update Invoice', $invoice->description);
                    \DB::commit();

                    // Check for return_url in request
                    $returnUrl = $request->input('return_url');
                    if ($returnUrl) {
                        return redirect($returnUrl)->with('success', __('Invoice successfully updated.'));
                    }
                    return redirect()->route('invoice.index')->with('success', __('Invoice successfully updated.'));
                } else {
                    return redirect()->back()->with('error', __('Permission denied.'));
                }
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } catch (\Exception $th) {
            \DB::rollback();
            dd($th);
        }
    }

    private function updateDraftInvoice($invoice, $products)
    {
        StockReport::where('type', '=', 'invoice')->where('type_id', '=', $invoice->id)->delete();

        // Collect all submitted product IDs to track which ones to delete
        $submittedProductIds = [];

        foreach ($products as $i => $prod) {
            // Determine item type
            $itemType = $prod['type'] ?? 'product';
            $productId = $prod['item_id'] ?? ($prod['item'] ?? null);

            $invoiceProduct = !empty($prod['id']) ? InvoiceProduct::find($prod['id']) : null;

            if ($invoiceProduct == null) {
                // New item - Create it
                $invoiceProduct = new InvoiceProduct();
                $invoiceProduct->invoice_id = $invoice->id;

                // Set line_type, estimate_id and proposal_product_id if present (for items from estimates)
                $invoiceProduct->line_type = $prod['line_type'] ?? null;
                $invoiceProduct->estimate_id = $prod['estimate_id'] ?? null;
                $invoiceProduct->proposal_product_id = $prod['proposal_product_id'] ?? null;

                if ($itemType === 'product') {
                    // Handle product items
                    $invoiceProduct->product_id = $productId;
                    $invoiceProduct->quantity = $prod['quantity'] ?? 0;
                    $invoiceProduct->tax = $prod['tax'] ?? null;
                    $invoiceProduct->discount = $prod['discount'] ?? 0;
                    $invoiceProduct->price = $prod['price'] ?? 0;
                    $invoiceProduct->description = $prod['description'] ?? '';
                    $invoiceProduct->taxable = $prod['is_taxable'] ?? ($prod['taxable'] ?? 0);
                    $invoiceProduct->item_tax_price = $prod['itemTaxPrice'] ?? ($prod['item_tax_price'] ?? 0);
                    $invoiceProduct->item_tax_rate = $prod['itemTaxRate'] ?? ($prod['item_tax_rate'] ?? 0);
                    $invoiceProduct->amount = $prod['amount'] ?? 0;
                    $invoiceProduct->save();

                    // Update inventory (decrease stock) for products only
                    if ($productId) {
                        Utility::total_quantity('minus', $invoiceProduct->quantity, $productId);

                        // Add stock report
                        $type = 'invoice';
                        $type_id = $invoice->id;
                        $description = $invoiceProduct->quantity . ' ' . __(' quantity sold in invoice ') . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
                        Utility::addProductStock($productId, $invoiceProduct->quantity, $type, $description, $type_id);
                    }
                } elseif ($itemType === 'subtotal') {
                    // Handle subtotal items
                    $invoiceProduct->product_id = null;
                    $invoiceProduct->quantity = 0;
                    $invoiceProduct->price = 0;
                    $invoiceProduct->description = $prod['label'] ?? 'Subtotal';
                    $invoiceProduct->amount = $prod['amount'] ?? 0;
                    $invoiceProduct->discount = 0;
                    $invoiceProduct->tax = null;
                    $invoiceProduct->taxable = 0;
                    $invoiceProduct->item_tax_price = 0;
                    $invoiceProduct->item_tax_rate = 0;
                    $invoiceProduct->save();
                } elseif ($itemType === 'text') {
                    // Handle text items
                    $invoiceProduct->product_id = null;
                    $invoiceProduct->quantity = 0;
                    $invoiceProduct->price = 0;
                    $invoiceProduct->description = $prod['text'] ?? '';
                    $invoiceProduct->amount = 0;
                    $invoiceProduct->discount = 0;
                    $invoiceProduct->tax = null;
                    $invoiceProduct->taxable = 0;
                    $invoiceProduct->item_tax_price = 0;
                    $invoiceProduct->item_tax_rate = 0;
                    $invoiceProduct->save();
                }
            } else {
                // Existing item - Update it

                // First, restore the old quantity to inventory if it was a product
                if ($invoiceProduct->product_id) {
                    Utility::total_quantity('plus', $invoiceProduct->quantity, $invoiceProduct->product_id);
                }

                // Update line_type, estimate_id and proposal_product_id if present
                $invoiceProduct->line_type = $prod['line_type'] ?? $invoiceProduct->line_type;
                $invoiceProduct->estimate_id = $prod['estimate_id'] ?? $invoiceProduct->estimate_id;
                $invoiceProduct->proposal_product_id = $prod['proposal_product_id'] ?? $invoiceProduct->proposal_product_id;

                if ($itemType === 'product') {


                    // Update product details
                    $invoiceProduct->product_id = $productId;
                    $invoiceProduct->quantity = $prod['quantity'] ?? 0;
                    $invoiceProduct->tax = $prod['tax'] ?? null;
                    $invoiceProduct->discount = $prod['discount'] ?? 0;
                    $invoiceProduct->price = $prod['price'] ?? 0;
                    $invoiceProduct->description = $prod['description'] ?? '';
                    $invoiceProduct->taxable = $prod['is_taxable'] ?? ($prod['taxable'] ?? 0);
                    $invoiceProduct->item_tax_price = $prod['itemTaxPrice'] ?? ($prod['item_tax_price'] ?? 0);
                    $invoiceProduct->item_tax_rate = $prod['itemTaxRate'] ?? ($prod['item_tax_rate'] ?? 0);
                    $invoiceProduct->amount = $prod['amount'] ?? 0;
                    $invoiceProduct->save();

                    // Deduct the new quantity from inventory
                    if ($productId) {
                        Utility::total_quantity('minus', $invoiceProduct->quantity, $invoiceProduct->product_id);

                        // Add stock report for new quantity
                        $type = 'invoice';
                        $type_id = $invoice->id;
                        $description = $invoiceProduct->quantity . ' ' . __(' quantity sold in invoice ') . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
                        Utility::addProductStock($invoiceProduct->product_id, $invoiceProduct->quantity, $type, $description, $type_id);
                    }
                } elseif ($itemType === 'subtotal') {
                    // Update subtotal
                    $invoiceProduct->product_id = null;
                    $invoiceProduct->quantity = 0;
                    $invoiceProduct->price = 0;
                    $invoiceProduct->description = $prod['label'] ?? 'Subtotal';
                    $invoiceProduct->amount = $prod['amount'] ?? 0;
                    $invoiceProduct->discount = 0;
                    $invoiceProduct->tax = null;
                    $invoiceProduct->taxable = 0;
                    $invoiceProduct->item_tax_price = 0;
                    $invoiceProduct->item_tax_rate = 0;
                    $invoiceProduct->save();
                } elseif ($itemType === 'text') {
                    // Update text
                    $invoiceProduct->product_id = null;
                    $invoiceProduct->quantity = 0;
                    $invoiceProduct->price = 0;
                    $invoiceProduct->description = $prod['text'] ?? '';
                    $invoiceProduct->amount = 0;
                    $invoiceProduct->discount = 0;
                    $invoiceProduct->tax = null;
                    $invoiceProduct->taxable = 0;
                    $invoiceProduct->item_tax_price = 0;
                    $invoiceProduct->item_tax_rate = 0;
                    $invoiceProduct->save();
                }
            }

            // Track this product ID as submitted
            $submittedProductIds[] = $invoiceProduct->id;
        }

        // Delete products that were removed from the invoice (not in submitted list)
        $productsToDelete = InvoiceProduct::where('invoice_id', $invoice->id)->whereNotIn('id', $submittedProductIds)->get();

        foreach ($productsToDelete as $productToDelete) {
            // Restore inventory for deleted product
            if ($productToDelete->product_id) {
                Utility::total_quantity('plus', $productToDelete->quantity, $productToDelete->product_id);
            }
            $productToDelete->delete();
        }

        // Update estimate status based on invoiced items
        $this->updateEstimateStatusAfterInvoice($products);
    }

    private function updateApprovedInvoice($invoice, $voucher, $products, $request)
    {
        // Store old total for customer balance adjustment
        $oldTotal = $invoice->getTotal();

        // Delete old stock reports for this invoice
        StockReport::where('type', '=', 'invoice')->where('type_id', '=', $invoice->id)->delete();
        $reciveable = 0;

        // Collect all submitted product IDs to track which ones to delete
        $submittedProductIds = [];

        foreach ($products as $i => $prod) {
            // Determine item type
            $itemType = $prod['type'] ?? 'product';
            $productId = $prod['item_id'] ?? ($prod['item'] ?? null);

            $invoiceProduct = !empty($prod['id']) ? InvoiceProduct::find($prod['id']) : null;
            $tax = 0;

            if ($invoiceProduct == null) {
                // New item added after approval
                $invoiceProduct = new InvoiceProduct();
                $invoiceProduct->invoice_id = $invoice->id;
                $invoiceProduct->line_type = $prod['line_type'] ?? null;
                $invoiceProduct->estimate_id = $prod['estimate_id'] ?? null;
                $invoiceProduct->proposal_product_id = $prod['proposal_product_id'] ?? null;
                if ($itemType === 'product') {
                    // Handle product items
                    $invoiceProduct->product_id = $productId;
                    $invoiceProduct->quantity = $prod['quantity'] ?? 0;
                    $invoiceProduct->tax = $prod['tax'] ?? null;
                    $invoiceProduct->discount = $prod['discount'] ?? 0;
                    $invoiceProduct->price = $prod['price'] ?? 0;
                    $invoiceProduct->description = $prod['description'] ?? '';
                    $invoiceProduct->taxable = $prod['is_taxable'] ?? ($prod['taxable'] ?? 0);
                    $invoiceProduct->item_tax_price = $prod['itemTaxPrice'] ?? ($prod['item_tax_price'] ?? 0);
                    $invoiceProduct->item_tax_rate = $prod['itemTaxRate'] ?? ($prod['item_tax_rate'] ?? 0);
                    $invoiceProduct->amount = $prod['amount'] ?? 0;
                    $invoiceProduct->save();
                    $invoiceProduct->created_at = date('Y-m-d H:i:s', strtotime($invoice->created_at));
                    $invoiceProduct->updated_at = date('Y-m-d H:i:s', strtotime($invoice->created_at));
                    $invoiceProduct->save();

                    $product = ProductService::where('id', $invoiceProduct->product_id)->first();

                    // Skip journal entry creation if product not found or no sales account (like jrentry does)
                    if (!$product || !isset($product->sale_chartaccount_id)) {
                        // Track this product ID and skip journal creation
                        $submittedProductIds[] = $invoiceProduct->id;
                        continue;
                    }

                    // Create journal item for product
                    $journalItem = new JournalItem();
                    $journalItem->journal = $voucher->id;
                    $journalItem->account = @$product->sale_chartaccount_id;
                    $journalItem->product_ids = $invoiceProduct->id;
                    $journalItem->description = $invoiceProduct->description;
                    $journalItem->credit = floatval($prod['quantity'] ?? 0) * floatval($prod['price'] ?? 0) - floatval($prod['discount'] ?? 0);
                    $journalItem->debit = 0;
                    $journalItem->save();
                    $journalItem->created_at = date('Y-m-d H:i:s', strtotime($invoice->created_at));
                    $journalItem->updated_at = date('Y-m-d H:i:s', strtotime($invoice->created_at));
                    $journalItem->save();

                    $tax += floatval($prod['itemTaxPrice'] ?? ($prod['item_tax_price'] ?? 0));
                    $reciveable += floatval($prod['quantity'] ?? 0) * floatval($prod['price'] ?? 0) - floatval($prod['discount'] ?? 0) + floatval($prod['itemTaxPrice'] ?? ($prod['item_tax_price'] ?? 0));

                    // Create transaction line for product
                    $dataline = [
                        'account_id' => $product->sale_chartaccount_id,
                        'transaction_type' => 'Credit',
                        'transaction_amount' => $journalItem->credit,
                        'reference' => 'Invoice Journal',
                        'reference_id' => $voucher->id,
                        'reference_sub_id' => $journalItem->id,
                        'date' => $voucher->date,
                        'created_at' => date('Y-m-d H:i:s', strtotime($invoice->created_at)),
                        'product_id' => $invoice->id,
                        'product_type' => 'Invoice',
                        'product_item_id' => $invoiceProduct->id,
                    ];
                    Utility::addTransactionLines($dataline, 'create');

                    // Handle tax if exists
                    if ($tax != 0) {
                        $accounttax = Tax::where('id', $product->tax_id)->first();
                        $account_tax = ChartOfAccount::where('id', $accounttax->account_id)->first();

                        if (!$account_tax) {
                            $types_t = ChartOfAccountType::where('created_by', '=', $invoice->created_by)->where('name', 'Liabilities')->first();
                            if ($types_t) {
                                $sub_type_t = ChartOfAccountSubType::where('type', $types_t->id)->where('name', 'Current Liabilities')->first();
                                $account_tax = ChartOfAccount::where('type', $types_t->id)->where('sub_type', $sub_type_t->id)->where('name', 'TAX')->first();
                                if (!$account_tax) {
                                    $account_tax = ChartOfAccount::create([
                                        'name' => 'TAX',
                                        'code' => '10000',
                                        'type' => $types_t->id,
                                        'sub_type' => $sub_type_t->id,
                                        'is_enabled' => 1,
                                        'created_by' => $invoice->created_by,
                                    ]);
                                }
                            }
                        }

                        if ($account_tax) {
                            $journalItem = new JournalItem();
                            $journalItem->journal = $voucher->id;
                            $journalItem->account = @$account_tax->id;
                            $journalItem->prod_tax_id = $invoiceProduct->id;
                            $journalItem->description = 'Tax on Invoice No : ' . @$invoice->invoice_id;
                            $journalItem->credit = $tax;
                            $journalItem->debit = 0;
                            $journalItem->save();
                            $journalItem->created_at = date('Y-m-d H:i:s', strtotime($invoice->created_at));
                            $journalItem->updated_at = date('Y-m-d H:i:s', timestamp: strtotime($invoice->created_at));
                            $journalItem->save();

                            $dataline = [
                                'account_id' => $account_tax->id,
                                'transaction_type' => 'Credit',
                                'transaction_amount' => $journalItem->credit,
                                'reference' => 'Invoice Journal',
                                'reference_id' => $voucher->id,
                                'reference_sub_id' => $journalItem->id,
                                'date' => $voucher->date,
                                'created_at' => date('Y-m-d H:i:s', strtotime($invoice->created_at)),
                                'product_id' => $invoice->id,
                                'product_type' => 'Invoice Tax',
                                'product_item_id' => $invoiceProduct->id,
                            ];
                            Utility::addTransactionLines($dataline, 'create');
                        }
                    }

                    // Update inventory
                    Utility::total_quantity('minus', $prod['quantity'], $productId);

                    // Add stock report
                    $type = 'invoice';
                    $type_id = $invoice->id;
                    $description = $prod['quantity'] . '  ' . __(' quantity sold in invoice') . ' ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
                    Utility::addProductStock($productId, $prod['quantity'], $type, $description, $type_id);
                } elseif ($itemType === 'subtotal') {
                    // Handle subtotal items - NO journal entries
                    $invoiceProduct->product_id = null;
                    $invoiceProduct->quantity = 0;
                    $invoiceProduct->price = 0;
                    $invoiceProduct->description = $prod['label'] ?? 'Subtotal';
                    $invoiceProduct->amount = $prod['amount'] ?? 0;
                    $invoiceProduct->discount = 0;
                    $invoiceProduct->tax = null;
                    $invoiceProduct->taxable = 0;
                    $invoiceProduct->item_tax_price = 0;
                    $invoiceProduct->item_tax_rate = 0;
                    $invoiceProduct->save();
                    $invoiceProduct->created_at = date('Y-m-d H:i:s', strtotime($invoice->created_at));
                    $invoiceProduct->updated_at = date('Y-m-d H:i:s', strtotime($invoice->created_at));
                    $invoiceProduct->save();
                } elseif ($itemType === 'text') {
                    // Handle text items - NO journal entries
                    $invoiceProduct->product_id = null;
                    $invoiceProduct->quantity = 0;
                    $invoiceProduct->price = 0;
                    $invoiceProduct->description = $prod['text'] ?? '';
                    $invoiceProduct->amount = 0;
                    $invoiceProduct->discount = 0;
                    $invoiceProduct->tax = null;
                    $invoiceProduct->taxable = 0;
                    $invoiceProduct->item_tax_price = 0;
                    $invoiceProduct->item_tax_rate = 0;
                    $invoiceProduct->save();
                    $invoiceProduct->created_at = date('Y-m-d H:i:s', strtotime($invoice->created_at));
                    $invoiceProduct->updated_at = date('Y-m-d H:i:s', strtotime($invoice->created_at));
                    $invoiceProduct->save();
                }

            } else {
                // Existing item - Update it based on type

                // 1. Restore inventory if it was a product
                if ($invoiceProduct->product_id) {
                    Utility::total_quantity('plus', $invoiceProduct->quantity, $invoiceProduct->product_id);
                }

                // 2. Handle Journal Entries cleanup if switching from Product to Non-Product
                if ($itemType !== 'product' && $invoiceProduct->product_id) {
                     // Delete associated journal items and transaction lines
                    JournalItem::where('journal', $voucher->id)->where('product_ids', $invoiceProduct->id)->delete();
                    JournalItem::where('journal', $voucher->id)->where('prod_tax_id', $invoiceProduct->id)->delete();
                    TransactionLines::where('reference_id', $voucher->id)->where('product_item_id', $invoiceProduct->id)->where('reference', 'Invoice Journal')->delete();
                }

                if ($itemType === 'product') {
                    // Handle product items - Update journal entries and inventory

                    // Update product details
                    $invoiceProduct->product_id = $productId;
                    $invoiceProduct->quantity = $prod['quantity'] ?? 0;
                    $invoiceProduct->tax = $prod['tax'] ?? null;
                    $invoiceProduct->discount = $prod['discount'] ?? 0;
                    $invoiceProduct->price = $prod['price'] ?? 0;
                    $invoiceProduct->description = $prod['description'] ?? '';
                    $invoiceProduct->taxable = $prod['is_taxable'] ?? ($prod['taxable'] ?? 0);
                    $invoiceProduct->item_tax_price = $prod['itemTaxPrice'] ?? ($prod['item_tax_price'] ?? 0);
                    $invoiceProduct->item_tax_rate = $prod['itemTaxRate'] ?? ($prod['item_tax_rate'] ?? 0);
                    $invoiceProduct->amount = $prod['amount'] ?? 0;
                    $invoiceProduct->save();

                    $product = ProductService::where('id', $invoiceProduct->product_id)->first();

                    // Skip journal entry update if product not found or no sales account
                    if (!$product || !isset($product->sale_chartaccount_id)) {
                        // Track this product ID and skip journal update
                        $submittedProductIds[] = $invoiceProduct->id;
                        continue;
                    }

                    // Update or Create journal item for product
                    $journalItem = JournalItem::where('journal', $voucher->id)->where('product_ids', $invoiceProduct->id)->first();
                    
                    if (!$journalItem) {
                        // Create new Journal Item (if it didn't exist, e.g. converted from text)
                        $journalItem = new JournalItem();
                        $journalItem->journal = $voucher->id;
                        $journalItem->account = $product->sale_chartaccount_id;
                        $journalItem->product_ids = $invoiceProduct->id;
                        $journalItem->description = $invoiceProduct->description;
                        $journalItem->credit = floatval($prod['quantity'] ?? 0) * floatval($prod['price'] ?? 0) - floatval($prod['discount'] ?? 0);
                        $journalItem->debit = 0;
                        $journalItem->save();
                        $journalItem->created_at = date('Y-m-d H:i:s', strtotime($invoice->created_at));
                        $journalItem->updated_at = date('Y-m-d H:i:s', strtotime($invoice->created_at));
                        $journalItem->save();

                        // Create Transaction Line
                        $dataline = [
                            'account_id' => $product->sale_chartaccount_id,
                            'transaction_type' => 'Credit',
                            'transaction_amount' => $journalItem->credit,
                            'reference' => 'Invoice Journal',
                            'reference_id' => $voucher->id,
                            'reference_sub_id' => $journalItem->id,
                            'date' => $voucher->date,
                            'created_at' => date('Y-m-d H:i:s', strtotime($invoice->created_at)),
                            'product_id' => $invoice->id,
                            'product_type' => 'Invoice',
                            'product_item_id' => $invoiceProduct->id,
                        ];
                        Utility::addTransactionLines($dataline, 'create');
                    } else {
                        // Update existing Journal Item
                        $journalItem->credit = floatval($prod['quantity'] ?? 0) * floatval($prod['price'] ?? 0) - floatval($prod['discount'] ?? 0);
                        $journalItem->save();

                        // Update transaction line
                        $transaction_line = TransactionLines::where('reference_id', $invoice->voucher_id)->where('product_id', $invoice->id)->where('reference', 'Invoice Journal')->where('product_item_id', $invoiceProduct->id)->where('product_type', 'Invoice')->first();
                        if ($transaction_line) {
                            $transaction_line->credit = $journalItem->credit;
                            $transaction_line->save();
                        }
                    }

                    $tax += floatval($prod['itemTaxPrice'] ?? ($prod['item_tax_price'] ?? 0));
                    $reciveable += floatval($prod['quantity'] ?? 0) * floatval($prod['price'] ?? 0) - floatval($prod['discount'] ?? 0) + floatval($prod['itemTaxPrice'] ?? ($prod['item_tax_price'] ?? 0));

                    // Update tax journal item if exists
                    if ($tax != 0) {
                        $journal_tax = JournalItem::where('journal', $voucher->id)->where('prod_tax_id', $invoiceProduct->id)->first();
                        
                        if (!$journal_tax) {
                            // Create Tax Journal Item if not exists
                            $accounttax = Tax::where('id', $product->tax_id)->first();
                            if ($accounttax) {
                                $account_tax = ChartOfAccount::where('id', $accounttax->account_id)->first();
                                // (Assuming account tax exists or created previously logic... simplified here as usually tax account exists)
                                if ($account_tax) {
                                    $journal_tax = new JournalItem();
                                    $journal_tax->journal = $voucher->id;
                                    $journal_tax->account = $account_tax->id;
                                    $journal_tax->prod_tax_id = $invoiceProduct->id;
                                    $journal_tax->description = 'Tax on Invoice No : ' . @$invoice->invoice_id;
                                    $journal_tax->credit = $tax;
                                    $journal_tax->debit = 0;
                                    $journal_tax->save();
                                    $journal_tax->created_at = date('Y-m-d H:i:s', strtotime($invoice->created_at));
                                    $journal_tax->updated_at = date('Y-m-d H:i:s', strtotime($invoice->created_at));
                                    $journal_tax->save();

                                    $dataline = [
                                        'account_id' => $account_tax->id,
                                        'transaction_type' => 'Credit',
                                        'transaction_amount' => $journal_tax->credit,
                                        'reference' => 'Invoice Journal',
                                        'reference_id' => $voucher->id,
                                        'reference_sub_id' => $journal_tax->id,
                                        'date' => $voucher->date,
                                        'created_at' => date('Y-m-d H:i:s', strtotime($invoice->created_at)),
                                        'product_id' => $invoice->id,
                                        'product_type' => 'Invoice Tax',
                                        'product_item_id' => $invoiceProduct->id,
                                    ];
                                    Utility::addTransactionLines($dataline, 'create');
                                }
                            }
                        } else {
                            // Update existing Tax Journal Item
                            $journal_tax->credit = $tax;
                            $journal_tax->save();

                            // Update tax transaction line
                            $transaction_tax = TransactionLines::where('reference_id', $invoice->voucher_id)->where('product_id', $invoice->id)->where('reference', 'Invoice Journal')->where('product_item_id', $invoiceProduct->id)->where('product_type', 'Invoice Tax')->first();
                            if ($transaction_tax) {
                                $transaction_tax->credit = $journal_tax->credit;
                                $transaction_tax->save();
                            }
                        }
                    }

                    // Deduct new quantity from inventory
                    if ($productId) {
                        Utility::total_quantity('minus', $prod['quantity'], $invoiceProduct->product_id);

                        // Add stock report
                        $type = 'invoice';
                        $type_id = $invoice->id;
                        $description = $prod['quantity'] . '  ' . __(' quantity sold in invoice') . ' ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
                        Utility::addProductStock($invoiceProduct->product_id, $prod['quantity'], $type, $description, $type_id);
                    }
                } elseif ($itemType === 'subtotal') {
                    // Handle subtotal items - NO journal entries, NO inventory
                    $invoiceProduct->product_id = null;
                    $invoiceProduct->quantity = 0;
                    $invoiceProduct->price = 0;
                    $invoiceProduct->description = $prod['label'] ?? 'Subtotal';
                    $invoiceProduct->amount = $prod['amount'] ?? 0;
                    $invoiceProduct->discount = 0;
                    $invoiceProduct->tax = null;
                    $invoiceProduct->taxable = 0;
                    $invoiceProduct->item_tax_price = 0;
                    $invoiceProduct->item_tax_rate = 0;
                    $invoiceProduct->save();
                } elseif ($itemType === 'text') {
                    // Handle text items - NO journal entries, NO inventory
                    $invoiceProduct->product_id = null;
                    $invoiceProduct->quantity = 0;
                    $invoiceProduct->price = 0;
                    $invoiceProduct->description = $prod['text'] ?? '';
                    $invoiceProduct->amount = 0;
                    $invoiceProduct->discount = 0;
                    $invoiceProduct->tax = null;
                    $invoiceProduct->taxable = 0;
                    $invoiceProduct->item_tax_price = 0;
                    $invoiceProduct->item_tax_rate = 0;
                    $invoiceProduct->save();
                }
            }

            // Track this product ID as submitted
            $submittedProductIds[] = $invoiceProduct->id;
        }

        // Delete products that were removed from the invoice (not in submitted list)
        $productsToDelete = InvoiceProduct::where('invoice_id', $invoice->id)->whereNotIn('id', $submittedProductIds)->get();

        foreach ($productsToDelete as $productToDelete) {
            // Restore inventory for deleted product
            if ($productToDelete->product_id) {
                Utility::total_quantity('plus', $productToDelete->quantity, $productToDelete->product_id);
            }

            // Delete associated journal items and transaction lines
            JournalItem::where('journal', $voucher->id)->where('product_ids', $productToDelete->id)->delete();
            JournalItem::where('journal', $voucher->id)->where('prod_tax_id', $productToDelete->id)->delete();
            TransactionLines::where('reference_id', $voucher->id)->where('product_item_id', $productToDelete->id)->where('reference', 'Invoice Journal')->delete();

            $productToDelete->delete();
        }

        // Update receivable journal item and transaction line
        $inv_receviable = TransactionLines::where('reference_id', $invoice->voucher_id)->where('reference', 'Invoice Journal')->where('product_type', 'Invoice Reciveable')->first();

        if ($inv_receviable) {
            $inv_receviable->credit = $reciveable;
            $inv_receviable->save();
        }

        // Update Account Receivables journal item
        $types = ChartOfAccountType::where('created_by', '=', $invoice->created_by)->where('name', 'Assets')->first();
        if ($types) {
            $sub_type = ChartOfAccountSubType::where('type', $types->id)->where('name', 'Current Asset')->first();
            $account = ChartOfAccount::where('type', $types->id)->where('sub_type', $sub_type->id)->where('name', 'Account Receivables')->first();

            if ($account) {
                $item_last = JournalItem::where('journal', $voucher->id)->where('account', $account->id)->first();
                if ($item_last) {
                    $item_last->debit = $reciveable;
                    $item_last->save();
                }
            } elseif ($inv_receviable) {
                $item_last = JournalItem::where('journal', $voucher->id)->where('id', $inv_receviable->reference_sub_id)->first();
                if ($item_last) {
                    $item_last->debit = $reciveable;
                    $item_last->save();
                }
            }
        }

        // Update estimate status based on invoiced items
        $this->updateEstimateStatusAfterInvoice($products);

        // Update customer balance if invoice total has changed
        // Refresh the invoice to get updated totals after product changes
        $invoice->refresh();
        $newTotal = $invoice->getTotal();

        if ($invoice->customer_id != 0 && $oldTotal != $newTotal) {
            $difference = $newTotal - $oldTotal;
            if ($difference > 0) {
                // New total is higher, increase customer balance (debit)
                Utility::updateUserBalance('customer', $invoice->customer_id, $difference, 'debit');
            } else {
                // New total is lower, decrease customer balance (credit)
                Utility::updateUserBalance('customer', $invoice->customer_id, abs($difference), 'credit');
            }
        }
    }

    public function invoiceNumber()
    {
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = $user->type == 'company' ? 'created_by' : 'owned_by';
        $latest = Invoice::where($column, '=', $ownerId)->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->invoice_id + 1;
    }

    public function show($ids)
    {
        if (\Auth::user()->can('show invoice')) {
            try {
                $id = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Invoice Not Found.'));
            }
            $id = Crypt::decrypt($ids);
            $invoice = Invoice::with(['creditNote', 'payments.bankAccount', 'items.product.unit'])->find($id);

            if (!empty($invoice->created_by) == \Auth::user()->creatorId()) {
                // Check if request is AJAX (for modal loading)
                if (request()->ajax()) {
                    $user = \Auth::user();
                    $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
                    $column = $user->type == 'company' ? 'created_by' : 'owned_by';
                    $customers = Customer::where($column, $ownerId)->get()->pluck('name', 'id')->toArray();
                    $customers = ['__add__' => '➕ Add new customer'] + ['' => 'Select Customer'] + $customers;
                    $category = ProductServiceCategory::where($column, $ownerId)->where('type', 'income')->get()->pluck('name', 'id')->toArray();
                    $category = ['__add__' => '➕ Add new category'] + ['' => 'Select Category'] + $category;
                    $product_services = ProductService::where($column, $ownerId)->get()->pluck('name', 'id');
                    $product_services->prepend('--', '');
                    $taxes = Tax::where('created_by', \Auth::user()->creatorId())->get();
                    $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())
                        ->where('module', '=', 'invoice')
                        ->get();

                    // Populate customer data
                    $customerId = $invoice->customer_id;
                    $customerData = Customer::find($customerId);
                    $billTo = '';
                    $shipTo = '';
                    if ($customerData) {
                        $billTo = $customerData->billing_name . "\n" . $customerData->billing_phone . "\n" . $customerData->billing_address . "\n" . $customerData->billing_city . ' , ' . $customerData->billing_state . ' , ' . $customerData->billing_country . '.' . "\n" . $customerData->billing_zip;

                        $shipTo = $customerData->shipping_name . "\n" . $customerData->shipping_phone . "\n" . $customerData->shipping_address . "\n" . $customerData->shipping_city . ' , ' . $customerData->shipping_state . ' , ' . $customerData->shipping_country . '.' . "\n" . $customerData->shipping_zip;
                    }

                    // Load invoice items with product details
                    $invoice->load(['items.product']);

                    // Prepare invoice data for JavaScript
                    $invoiceData = [
                        'id' => $invoice->id,
                        'invoice_id' => $invoice->invoice_id,
                        'customer_id' => $invoice->customer_id,
                        'issue_date' => $invoice->issue_date,
                        'due_date' => $invoice->due_date,
                        'category_id' => $invoice->category_id,
                        'ref_number' => $invoice->ref_number,
                        'logo' => $invoice->logo,
                        'attachments' => $invoice->attachments ? json_decode($invoice->attachments) : [],
                        'subtotal' => $invoice->subtotal,
                        'taxable_subtotal' => $invoice->taxable_subtotal,
                        'total_discount' => $invoice->total_discount,
                        'total_tax' => $invoice->total_tax,
                        'sales_tax_amount' => $invoice->sales_tax_amount,
                        'total_amount' => $invoice->total_amount,
                        'items' => $invoice->items
                            ->map(function ($item) {
                                return [
                                    'id' => $item->id,
                                    'item' => $item->product_id,
                                    'description' => $item->description,
                                    'quantity' => $item->quantity,
                                    'price' => $item->price,
                                    'discount' => $item->discount,
                                    'tax' => $item->tax,
                                    'taxable' => $item->taxable,
                                    'itemTaxPrice' => $item->item_tax_price,
                                    'itemTaxRate' => $item->item_tax_rate,
                                    'amount' => $item->amount,
                                    'estimate_id' => $item->estimate_id,
                                    'line_type' => $item->line_type,
                                    'proposal_product_id' => $item->proposal_product_id,
                                ];
                            })
                            ->toArray(),
                    ];

                    return view('invoice.create_modal', compact('customers', 'invoice', 'product_services', 'category', 'customFields', 'customerId', 'taxes', 'billTo', 'shipTo', 'invoiceData'))->with('mode', 'show');
                }

                $invoicePayment = InvoicePayment::where('invoice_id', $invoice->id)->first();

                $customer = $invoice->customer;
                $iteams = $invoice->items;
                $user = \Auth::user();

                // start for storage limit note
                $invoice_user = User::find($invoice->created_by);
                $user_plan = Plan::getPlan($invoice_user->plan);
                // end for storage limit note

                $invoice->customField = CustomField::getData($invoice, 'invoice');
                $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())
                    ->where('module', '=', 'invoice')
                    ->get();

                $creditnote = CreditNote::where('invoice', $invoice->id)->first();

                return view('invoice.view', compact('invoice', 'customer', 'iteams', 'invoicePayment', 'customFields', 'user', 'invoice_user', 'user_plan', 'creditnote'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(Invoice $invoice, Request $request)
    {
        if (\Auth::user()->can('delete invoice')) {
            if ($invoice->created_by == \Auth::user()->creatorId()) {
                foreach ($invoice->payments as $invoices) {
                    Utility::bankAccountBalance($invoices->account_id, $invoices->amount, 'debit');

                    $invoicepayment = InvoicePayment::find($invoices->id);
                    $invoices->delete();
                    $invoicepayment->delete();
                    if (@$invoices->voucher_id != 0 || @$invoices->voucher_id != null) {
                        JournalEntry::where('id', $invoices->voucher_id)->where('category', 'Invoice')->delete();
                        JournalItem::where('journal', $invoices->voucher_id)->delete();
                    }
                }

                if ($invoice->customer_id != 0 && $invoice->status != 0) {
                    Utility::updateUserBalance('customer', $invoice->customer_id, $invoice->getDue(), 'debit');
                }

                TransactionLines::where('product_id', $invoice->id)->where('reference', 'Invoice Journal')->delete();
                TransactionLines::where('reference_id', $invoice->id)->where('reference', 'Invoice')->delete();
                TransactionLines::where('reference_id', $invoice->id)->Where('reference', 'Invoice Payment')->delete();

                CreditNote::where('invoice', '=', $invoice->id)->delete();

                InvoiceProduct::where('invoice_id', '=', $invoice->id)->delete();
                // /log
                Utility::makeActivityLog(\Auth::user()->id, 'Invoice', $invoice->id, 'Delete Invoice', $invoice->description);
                if (@$invoice->voucher_id != 0 || @$invoice->voucher_id != null) {
                    JournalEntry::where('id', $invoice->voucher_id)->where('category', 'Invoice')->delete();
                    JournalItem::where('journal', $invoice->voucher_id)->delete();
                }
                $invoice->delete();
                return redirect()->route('invoice.index')->with('success', __('Invoice successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function productDestroy(Request $request)
    {
        \DB::beginTransaction();
        try {
            if (\Auth::user()->can('delete invoice product')) {
                $invoiceProduct = InvoiceProduct::find($request->id);

                if ($invoiceProduct) {
                    $invoice = Invoice::find($invoiceProduct->invoice_id);
                    $productService = ProductService::find($invoiceProduct->product_id);
                    StockReport::where('type', '=', 'invoice')->where('type_id', '=', $invoice->id)->where('product_id', '=', $invoiceProduct->product_id)->delete();
                    Utility::total_quantity('plus', $invoiceProduct->quantity, $invoiceProduct->product_id);

                    Utility::updateUserBalance('customer', $invoice->customer_id, $request->amount, 'debit');
                    if ($invoice->status != 0 && $invoice->status != 5 && $invoice->status != 7) {
                        $prod_id = TransactionLines::where('reference_id', $invoice->voucher_id)->where('product_item_id', $invoiceProduct->id)->where('reference', 'Invoice Journal')->where('product_type', 'Invoice')->first();
                        $prod_tax = TransactionLines::where('reference_id', $invoice->voucher_id)->where('product_item_id', $invoiceProduct->id)->where('reference', 'Invoice Journal')->where('product_type', 'Invoice Tax')->first();
                        $inv_receviable = TransactionLines::where('reference_id', $invoice->voucher_id)->where('reference', 'Invoice Journal')->where('product_type', 'Invoice Reciveable')->first();
                        // dd($inv_receviable);
                        $inv_receviable->debit = $inv_receviable->debit - ($prod_id->credit + @$prod_tax->credit);
                        $inv_receviable->save();
                        @$prod_id->delete();
                        if ($prod_tax) {
                            @$prod_tax->delete();
                        }
                        TransactionLines::where('reference_sub_id', $productService->id)->where('reference', 'Invoice')->delete();

                        $journal_item = JournalItem::where('journal', $invoice->voucher_id)->where('product_ids', $invoiceProduct->id)->first();
                        $journal_tax = JournalItem::where('journal', $invoice->voucher_id)->where('prod_tax_id', $invoiceProduct->id)->first();
                        $types = ChartOfAccountType::where('created_by', '=', $invoice->created_by)->where('name', 'Assets')->first();
                        if ($types) {
                            $sub_type = ChartOfAccountSubType::where('type', $types->id)->where('name', 'Current Asset')->first();
                            $account = ChartOfAccount::where('type', $types->id)->where('sub_type', $sub_type->id)->where('name', 'Account Receivables')->first();
                        }
                        if ($account) {
                            $item_last = JournalItem::where('journal', $invoice->voucher_id)->where('account', $account->id)->first();
                            $item_last->debit = $item_last->debit - ($journal_item->credit + @$journal_tax->credit);
                            $item_last->save();
                        } else {
                            $item_last = JournalItem::where('journal', $invoice->voucher_id)->where('id', $inv_receviable->reference_sub_id)->first();
                            $item_last->debit = $item_last->debit - ($journal_item->credit + @$journal_tax->credit);
                            $item_last->save();
                        }
                        @$journal_item->delete();
                        if ($journal_tax) {
                            @$journal_tax->delete();
                        }
                    }
                    InvoiceProduct::where('id', '=', $request->id)->delete();
                }

                // /log
                Utility::makeActivityLog(\Auth::user()->id, 'Invoice Product', $invoiceProduct->id, 'Delete Invoice Product', $invoiceProduct->product->name);
                \DB::commit();
                return redirect()->back()->with('success', __('Invoice product successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
            \DB::commit();
        } catch (\Exception $e) {
            dd($e);
            \DB::rollBack();
            return redirect()
                ->back()
                ->with('error', __($e->getMessage()));
        }
    }

    public function customerInvoice(Request $request)
    {
        if (\Auth::user()->can('manage customer invoice')) {
            $status = Invoice::$statues;
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = $user->type == 'company' ? 'created_by' : 'owned_by';
            $query = Invoice::where('customer_id', '=', \Auth::user()->id)
                ->where('status', '!=', '0')
                ->where($column, $ownerId);

            if (!empty($request->issue_date)) {
                $date_range = explode(' - ', $request->issue_date);
                $query->whereBetween('issue_date', $date_range);
            }

            if (!empty($request->status)) {
                $query->where('status', '=', $request->status);
            }
            $invoices = $query->get();

            return view('invoice.index', compact('invoices', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function customerInvoiceShow($id)
    {
        $invoice = Invoice::with('payments.bankAccount')->find($id);

        $user = User::where('id', $invoice->created_by)->first();
        if ($invoice->created_by == $user->creatorId()) {
            $customer = $invoice->customer;
            $iteams = $invoice->items;

            if ($user->type == 'super admin') {
                return view('invoice.view', compact('invoice', 'customer', 'iteams', 'user'));
            } elseif ($user->type == 'company') {
                return view('invoice.customer_invoice', compact('invoice', 'customer', 'iteams', 'user'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function sent($id)
    {
        if (\Auth::user()->can('send invoice')) {
            // Send Email
            $setings = Utility::settings();

            if ($setings['customer_invoice_sent'] == 1) {
                $invoice = Invoice::where('id', $id)->first();
                $invoice->send_date = date('Y-m-d');
                $invoice->status = 1;
                $invoice->save();

                $customer = Customer::where('id', $invoice->customer_id)->first();
                $invoice->name = !empty($customer) ? $customer->name : '';
                $invoice->invoice = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);

                $invoiceId = Crypt::encrypt($invoice->id);
                $invoice->url = route('invoice.pdf', $invoiceId);

                Utility::updateUserBalance('customer', $customer->id, $invoice->getTotal(), 'credit');

                $invoice_products = InvoiceProduct::where('invoice_id', $invoice->id)->get();
                foreach ($invoice_products as $invoice_product) {
                    $product = ProductService::find($invoice_product->product_id);
                    $totalTaxPrice = 0;
                    if ($invoice_product->tax != null) {
                        $taxes = \App\Models\Utility::tax($invoice_product->tax);
                        foreach ($taxes as $tax) {
                            $taxPrice = \App\Models\Utility::taxRate($tax->rate, $invoice_product->price, $invoice_product->quantity, $invoice_product->discount);
                            $totalTaxPrice += $taxPrice;
                        }
                    }

                    $itemAmount = $invoice_product->price * $invoice_product->quantity - $invoice_product->discount + $totalTaxPrice;

                    // $data = [
                    //     'account_id' => $product->sale_chartaccount_id,
                    //     'transaction_type' => 'Credit',
                    //     'transaction_amount' => $itemAmount,
                    //     'reference' => 'Invoice',
                    //     'reference_id' => $invoice->id,
                    //     'reference_sub_id' => $product->id,
                    //     'date' => $invoice->issue_date,
                    // ];
                    // Utility::addTransactionLines($data , 'create');
                }

                $customerArr = [
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'invoice_name' => $customer->name,
                    'invoice_number' => $invoice->invoice,
                    'invoice_url' => $invoice->url,
                ];
                $resp = Utility::sendEmailTemplate('customer_invoice_sent', [$customer->id => $customer->email], $customerArr);
                //log
                Utility::makeActivityLog(\Auth::user()->id, 'Invoice', $invoice->id, 'Send Invoice to Customer', $invoice->description);
                return redirect()
                    ->back()
                    ->with('success', __('Invoice successfully sent.') . ($resp['is_success'] == false && !empty($resp['error']) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function resent($id)
    {
        if (\Auth::user()->can('send invoice')) {
            $invoice = Invoice::where('id', $id)->first();

            $customer = Customer::where('id', $invoice->customer_id)->first();
            $invoice->name = !empty($customer) ? $customer->name : '';
            $invoice->invoice = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);

            $invoiceId = Crypt::encrypt($invoice->id);
            $invoice->url = route('invoice.pdf', $invoiceId);
            $customerArr = [
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'invoice_name' => $customer->name,
                'invoice_number' => $invoice->invoice,
                'invoice_url' => $invoice->url,
            ];
            $resp = Utility::sendEmailTemplate('customer_invoice_sent', [$customer->id => $customer->email], $customerArr);
            //log
            Utility::makeActivityLog(\Auth::user()->id, 'Invoice', $invoice->id, 'Resend Invoice to Customer', $invoice->description);

            return redirect()
                ->back()
                ->with('success', __('Invoice successfully sent.') . ($resp['is_success'] == false && !empty($resp['error']) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function payment($invoice_id)
    {
        if (\Auth::user()->can('create payment invoice')) {
            $invoice = Invoice::where('id', $invoice_id)->first();
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = $user->type == 'company' ? 'created_by' : 'owned_by';
            $customers = Customer::where($column, '=', $ownerId)->get()->pluck('name', 'id');
            $categories = ProductServiceCategory::where($column, '=', $ownerId)->get()->pluck('name', 'id');
            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))
                ->where('created_by', \Auth::user()->creatorId())
                ->get()
                ->pluck('name', 'id');

            return view('invoice.payment', compact('customers', 'categories', 'accounts', 'invoice'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function newQboRecievePayment($invoice_id)
{
    if (\Auth::user()->can('create payment invoice')) {
        // Fetch invoice with customer relationship
        $invoice = Invoice::with('customer')->where('id', $invoice_id)->first();
        
        if (!$invoice) {
            return redirect()->back()->with('error', __('Invoice not found.'));
        }
        
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
        
        // Get all customers for the dropdown
        $customers = Customer::where($column, '=', $ownerId)->get()->pluck('name', 'id');
        $customerId = $invoice->customer_id;
        
        // Payment methods
        $paymentMethods = [
            '' => 'Select method',
            'Cash' => 'Cash',
            'Check' => 'Check',
            'Credit Card' => 'Credit Card',
            'Debit Card' => 'Debit Card',
            'Bank Transfer' => 'Bank Transfer',
            'PayPal' => 'PayPal',
            'Other' => 'Other',
        ];

        // Get bank accounts for "Deposit to"
        $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))
            ->where('created_by', \Auth::user()->creatorId())
            ->get()
            ->pluck('name', 'id');
        
        // Get ALL open invoices for this customer (Sent, Unpaid, Partially Paid)
        // We include the current invoice_id in this list so it appears in the table
        $openInvoices = Invoice::where('customer_id', $invoice->customer_id)
            ->whereIn('status', [1, 2, 3]) 
            ->orderBy('due_date', 'asc')
            ->get();

        // Calculate total open balance for the customer
        $totalOpenBalance = $openInvoices->sum(function($inv) {
            return $inv->getDue();
        });

        return view('invoice.newQboRecievePayment', compact(
            'invoice', 
            'customers', 
            'customerId',
            'paymentMethods', 
            'accounts', 
            'totalOpenBalance',
            'openInvoices'
        ));
    } else {
        return redirect()->back()->with('error', __('Permission denied.'));
    }
} 

    public function createPayment(Request $request, $invoice_id)
    {
        $invoice = Invoice::find($invoice_id);
        if ($invoice->getDue() < $request->amount) {
            return redirect()->back()->with('error', __('Invoice payment amount should not greater than subtotal.'));
        }

        \DB::beginTransaction();
        try {
            if (\Auth::user()->can('create payment invoice')) {
                $validator = \Validator::make($request->all(), [
                    'date' => 'required',
                    'amount' => 'required',
                    'account_id' => 'required',
                ]);
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $invoicePayment = new InvoicePayment();
                $invoicePayment->invoice_id = $invoice_id;
                $invoicePayment->date = $request->date;
                $invoicePayment->amount = $request->amount;
                $invoicePayment->account_id = $request->account_id;
                $invoicePayment->payment_method = 0;
                $invoicePayment->reference = $request->reference;
                $invoicePayment->description = $request->description;
                if (!empty($request->add_receipt)) {
                    //storage limit
                    $image_size = $request->file('add_receipt')->getSize();
                    $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);
                    if ($result == 1) {
                        $fileName = time() . '_' . $request->add_receipt->getClientOriginalName();
                        $request->add_receipt->storeAs('uploads/payment', $fileName);
                        $invoicePayment->add_receipt = $fileName;
                    }
                }

                $invoicePayment->save();

                $invoice = Invoice::where('id', $invoice_id)->first();
                $due = $invoice->getDue();
                $total = $invoice->getTotal();
                if ($invoice->status == 0) {
                    $invoice->send_date = date('Y-m-d');
                    $invoice->save();
                }

                if ($due <= 0) {
                    $invoice->status = 4;
                    $invoice->save();
                } else {
                    $invoice->status = 3;
                    $invoice->save();
                }
                $invoicePayment->user_id = $invoice->customer_id;
                $invoicePayment->user_type = 'Customer';
                $invoicePayment->type = 'Partial';
                $invoicePayment->created_by = \Auth::user()->id;
                $invoicePayment->owned_by = \Auth::user()->ownedId();
                $invoicePayment->payment_id = $invoicePayment->id;
                $invoicePayment->category = 'Invoice';
                $invoicePayment->account = $request->account_id;

                Transaction::addTransaction($invoicePayment);
                $customer = Customer::where('id', $invoice->customer_id)->first();

                $payment = new InvoicePayment();
                $payment->name = $customer['name'];
                $payment->date = \Auth::user()->dateFormat($request->date);
                $payment->amount = \Auth::user()->priceFormat($request->amount);
                $payment->invoice = 'invoice ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
                $payment->dueAmount = \Auth::user()->priceFormat($invoice->getDue());

                Utility::updateUserBalance('customer', $invoice->customer_id, $request->amount, 'debit');

                Utility::bankAccountBalance($request->account_id, $request->amount, 'credit');

                // $invoicePayments = InvoicePayment::where('invoice_id', $invoice->id)->get();
                // foreach ($invoicePayments as $invoicePayment) {

                //     $accountId = BankAccount::find($invoicePayment->account_id);
                //     $data = [
                //         'account_id' => $accountId->chart_account_id,
                //         'transaction_type' => 'Debit',
                //         'transaction_amount' => $invoicePayment->amount,
                //         'reference' => 'Invoice Payment',
                //         'reference_id' => $invoice->id,
                //         'reference_sub_id' => $invoicePayment->id,
                //         'date' => $invoicePayment->date,
                //     ];
                //     Utility::addTransactionLines($data , 'create');
                // }
                $bankAccount = BankAccount::find($request->account_id);
                if (($bankAccount && $bankAccount->chart_account_id != 0) || $bankAccount->chart_account_id != null) {
                    $data['account_id'] = $bankAccount->chart_account_id;
                } else {
                    return redirect()->back()->with('error', __('Please select chart of account in bank account.'));
                }

                $data['id'] = $invoice_id;
                $data['no'] = $invoice->invoice_id;
                $data['date'] = $invoicePayment->date;
                $data['reference'] = $invoicePayment->reference;
                $data['description'] = $invoicePayment->description;
                $data['amount'] = $invoicePayment->amount;
                $data['prod_id'] = $invoicePayment->id;
                // $data['result'] = $result;
                $data['category'] = 'Invoice';
                $data['owned_by'] = $invoicePayment->owned_by;
                $data['created_by'] = \Auth::user()->creatorId();
                $data['created_at'] = date('Y-m-d', strtotime($invoicePayment->date)) . ' ' . date('h:i:s');

                if (preg_match('/\bcash\b/i', $bankAccount->bank_name) || preg_match('/\bcash\b/i', $bankAccount->holder_name)) {
                    $dataret = Utility::crv_entry($data);
                } else {
                    $dataret = Utility::brv_entry($data);
                }
                InvoicePayment::where('id', $invoicePayment->id)->update([
                    'voucher_id' => $dataret,
                ]);

                // Send Email
                $setings = Utility::settings();
                if ($setings['new_invoice_payment'] == 1) {
                    $customer = Customer::where('id', $invoice->customer_id)->first();
                    $invoicePaymentArr = [
                        'invoice_payment_name' => $customer->name,
                        'invoice_payment_amount' => $payment->amount,
                        'invoice_payment_date' => $payment->date,
                        'payment_dueAmount' => $payment->dueAmount,
                    ];

                    $resp = Utility::sendEmailTemplate('new_invoice_payment', [$customer->id => $customer->email], $invoicePaymentArr);
                }

                //webhook
                $module = 'New Invoice Payment';
                $webhook = Utility::webhookSetting($module);
                if ($webhook) {
                    $parameter = json_encode($invoice);
                    $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                    if ($status == true) {
                        \DB::commit();
                        return redirect()
                            ->back()
                            ->with('success', __('Payment successfully added.') . (isset($result) && $result != 1 ? '<br> <span class="text-danger">' . $result . '</span>' : '') . ($resp['is_success'] == false && !empty($resp['error']) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
                    } else {
                        \DB::commit();
                        return redirect()->back()->with('error', __('Webhook call failed.'));
                    }
                }
                //activity log
                Utility::makeActivityLog(\Auth::user()->id, 'Invoice Payment', $invoicePayment->id, 'Create Invoice Payment', $customer->name);
                \DB::commit();
                return redirect()
                    ->back()
                    ->with('success', __('Payment successfully added.') . (isset($result) && $result != 1 ? '<br> <span class="text-danger">' . $result . '</span>' : '') . ($resp['is_success'] == false && !empty($resp['error']) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->back()->with('error', __($e));
        }
    }

    public function paymentDestroy(Request $request, $invoice_id, $payment_id)
    {
        //        dd($invoice_id,$payment_id);

        if (\Auth::user()->can('delete payment invoice')) {
            $payment = InvoicePayment::find($payment_id);
            if (!$payment) {
                return redirect()->back()->with('error', __('Payment not found.'));
            }

            InvoiceBankTransfer::where('id', '=', $payment_id)->delete();

            TransactionLines::where('reference_sub_id', $payment_id)->where('reference', 'Invoice Payment')->delete();
            if (@$payment->voucher_id != 0 || @$payment->voucher_id != null) {
                JournalEntry::where('id', $payment->voucher_id)->where('category', 'Invoice')->delete();
                JournalItem::where('journal', $payment->voucher_id)->delete();
            }
            $invoice = Invoice::where('id', $invoice_id)->first();
            $due = $invoice->getDue();
            $total = $invoice->getTotal();

            if ($due > 0 && $total != $due) {
                $invoice->status = 3;
            } else {
                $invoice->status = 2;
            }

            if (!empty($payment->add_receipt)) {
                //storage limit
                $file_path = '/uploads/payment/' . $payment->add_receipt;
                $result = Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
            }
            InvoicePayment::where('id', '=', $payment_id)->delete();
            $invoice->save();
            $type = 'Partial';
            $user = 'Customer';
            Transaction::destroyTransaction($payment_id, $type, $user);

            Utility::updateUserBalance('customer', $invoice->customer_id, $payment->amount, 'credit');

            Utility::bankAccountBalance($payment->account_id, $payment->amount, 'debit');
            //log
            $customer = Customer::where('id', $invoice->customer_id)->first();
            Utility::makeActivityLog(\Auth::user()->id, 'Invoice Payment', $payment_id, 'Delete Invoice Payment', $customer->name);
            return redirect()->back()->with('success', __('Payment successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function paymentReminder($invoice_id)
    {
        //        dd($invoice_id);
        $invoice = Invoice::find($invoice_id);
        $customer = Customer::where('id', $invoice->customer_id)->first();
        $invoice->dueAmount = \Auth::user()->priceFormat($invoice->getDue());
        $invoice->name = $customer['name'];
        $invoice->date = \Auth::user()->dateFormat($invoice->send_date);
        $invoice->invoice = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);

        //For Notification
        $setting = Utility::settings(\Auth::user()->creatorId());
        $customer = Customer::find($invoice->customer_id);
        $reminderNotificationArr = [
            'invoice_number' => \Auth::user()->invoiceNumberFormat($invoice->invoice_id),
            'customer_name' => $customer->name,
            'user_name' => \Auth::user()->name,
        ];

        //Twilio Notification
        if (isset($setting['twilio_reminder_notification']) && $setting['twilio_reminder_notification'] == 1) {
            Utility::send_twilio_msg($customer->contact, 'invoice_payment_reminder', $reminderNotificationArr);
        }

        // Send Email
        $setings = Utility::settings();
        if ($setings['new_payment_reminder'] == 1) {
            $invoice = Invoice::find($invoice_id);
            $customer = Customer::where('id', $invoice->customer_id)->first();
            $invoice->dueAmount = \Auth::user()->priceFormat($invoice->getDue());
            $invoice->name = $customer['name'];
            $invoice->date = \Auth::user()->dateFormat($invoice->send_date);
            $invoice->invoice = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);

            $reminderArr = [
                'payment_reminder_name' => $invoice->name,
                'invoice_payment_number' => $invoice->invoice,
                'invoice_payment_dueAmount' => $invoice->dueAmount,
                'payment_reminder_date' => $invoice->date,
            ];

            $resp = Utility::sendEmailTemplate('new_payment_reminder', [$customer->id => $customer->email], $reminderArr);
        }
        //log
        Utility::makeActivityLog(\Auth::user()->id, 'Invoice Payment', $invoice_id, 'Send Payment Reminder', $customer->name);
        return redirect()
            ->back()
            ->with('success', __('Payment reminder successfully send.') . ($resp['is_success'] == false && !empty($resp['error']) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
    }

    public function customerInvoiceSend($invoice_id)
    {
        return view('customer.invoice_send', compact('invoice_id'));
    }

    public function customerInvoiceSendMail(Request $request, $invoice_id)
    {
        $validator = \Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $email = $request->email;
        $invoice = Invoice::where('id', $invoice_id)->first();

        $customer = Customer::where('id', $invoice->customer_id)->first();
        $invoice->name = !empty($customer) ? $customer->name : '';
        $invoice->invoice = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);

        $invoiceId = Crypt::encrypt($invoice->id);
        $invoice->url = route('invoice.pdf', $invoiceId);

        try {
            Mail::to($email)->send(new CustomerInvoiceSend($invoice));
        } catch (\Exception $e) {
            $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
        }
        // /log
        Utility::makeActivityLog(\Auth::user()->id, 'Invoice Payment', $invoice_id, 'Send Invoice Email', $customer->name);
        return redirect()
            ->back()
            ->with('success', __('Invoice successfully sent.') . (isset($smtp_error) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
    }

    public function shippingDisplay(Request $request, $id)
    {
        $invoice = Invoice::find($id);

        if ($request->is_display == 'true') {
            $invoice->shipping_display = 1;
        } else {
            $invoice->shipping_display = 0;
        }
        $invoice->save();
        //log
        Utility::makeActivityLog(\Auth::user()->id, 'Invoice Payment', $id, 'Change Shipping Display Status', $invoice->customer->name);
        return redirect()->back()->with('success', __('Shipping address status successfully changed.'));
    }

    public function duplicate($invoice_id)
    {
        if (\Auth::user()->can('duplicate invoice')) {
            $invoice = Invoice::where('id', $invoice_id)->first();
            $duplicateInvoice = new Invoice();
            $duplicateInvoice->invoice_id = $this->invoiceNumber();
            $duplicateInvoice->customer_id = $invoice['customer_id'];
            $duplicateInvoice->issue_date = date('Y-m-d');
            $duplicateInvoice->due_date = $invoice['due_date'];
            $duplicateInvoice->send_date = null;
            $duplicateInvoice->category_id = $invoice['category_id'];
            $duplicateInvoice->ref_number = $invoice['ref_number'];
            $duplicateInvoice->status = 0;
            $duplicateInvoice->shipping_display = $invoice['shipping_display'];
            $duplicateInvoice->created_by = $invoice['created_by'];
            $duplicateInvoice->save();

            if ($duplicateInvoice) {
                $invoiceProduct = InvoiceProduct::where('invoice_id', $invoice_id)->get();
                foreach ($invoiceProduct as $product) {
                    $duplicateProduct = new InvoiceProduct();
                    $duplicateProduct->invoice_id = $duplicateInvoice->id;
                    $duplicateProduct->product_id = $product->product_id;
                    $duplicateProduct->quantity = $product->quantity;
                    $duplicateProduct->tax = $product->tax;
                    $duplicateProduct->discount = $product->discount;
                    $duplicateProduct->price = $product->price;
                    $duplicateProduct->save();
                }
            }
            //log
            Utility::makeActivityLog(\Auth::user()->id, 'Invoice Payment', $invoice_id, 'Duplicate Invoice', $invoice->customer->name);
            return redirect()->back()->with('success', __('Invoice duplicate successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function previewInvoice($template, $color)
    {
        $objUser = \Auth::user();
        $settings = Utility::settings();
        $invoice = new Invoice();

        $customer = new \stdClass();
        $customer->email = '<Email>';
        $customer->shipping_name = '<Customer Name>';
        $customer->shipping_country = '<Country>';
        $customer->shipping_state = '<State>';
        $customer->shipping_city = '<City>';
        $customer->shipping_phone = '<Customer Phone Number>';
        $customer->shipping_zip = '<Zip>';
        $customer->shipping_address = '<Address>';
        $customer->billing_name = '<Customer Name>';
        $customer->billing_country = '<Country>';
        $customer->billing_state = '<State>';
        $customer->billing_city = '<City>';
        $customer->billing_phone = '<Customer Phone Number>';
        $customer->billing_zip = '<Zip>';
        $customer->billing_address = '<Address>';

        $totalTaxPrice = 0;
        $taxesData = [];

        $items = [];
        for ($i = 1; $i <= 3; $i++) {
            $item = new \stdClass();
            $item->name = 'Item ' . $i;
            $item->quantity = 1;
            $item->tax = 5;
            $item->discount = 50;
            $item->price = 100;
            $item->unit = 1;
            $item->description = 'XYZ';

            $taxes = ['Tax 1', 'Tax 2'];

            $itemTaxes = [];
            foreach ($taxes as $k => $tax) {
                $taxPrice = 10;
                $totalTaxPrice += $taxPrice;
                $itemTax['name'] = 'Tax ' . $k;
                $itemTax['rate'] = '10 %';
                $itemTax['price'] = '$10';
                $itemTax['tax_price'] = 10;
                $itemTaxes[] = $itemTax;
                if (array_key_exists('Tax ' . $k, $taxesData)) {
                    $taxesData['Tax ' . $k] = $taxesData['Tax 1'] + $taxPrice;
                } else {
                    $taxesData['Tax ' . $k] = $taxPrice;
                }
            }
            $item->itemTax = $itemTaxes;
            $items[] = $item;
        }

        $invoice->invoice_id = 1;
        $invoice->issue_date = date('Y-m-d H:i:s');
        $invoice->due_date = date('Y-m-d H:i:s');
        $invoice->itemData = $items;
        $invoice->status = 0;
        $invoice->totalTaxPrice = 60;
        $invoice->totalQuantity = 3;
        $invoice->totalRate = 300;
        $invoice->totalDiscount = 10;
        $invoice->taxesData = $taxesData;
        $invoice->created_by = $objUser->creatorId();

        $invoice->customField = [];
        $customFields = [];

        $preview = 1;
        $color = '#' . $color;
        $font_color = Utility::getFontColor($color);

        $logo = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $invoice_logo = Utility::getValByName('invoice_logo');
        if (isset($invoice_logo) && !empty($invoice_logo)) {
            $img = Utility::get_file('invoice_logo/') . $invoice_logo;
        } else {
            $img = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        return view('invoice.templates.' . $template, compact('invoice', 'preview', 'color', 'img', 'settings', 'customer', 'font_color', 'customFields'));
    }

    public function invoice($invoice_id)
    {
        $settings = Utility::settings();

        $invoiceId = Crypt::decrypt($invoice_id);
        $invoice = Invoice::where('id', $invoiceId)->first();

        $data = DB::table('settings');
        $data = $data->where('created_by', '=', $invoice->created_by);
        $data1 = $data->get();

        foreach ($data1 as $row) {
            $settings[$row->name] = $row->value;
        }

        $customer = $invoice->customer;
        $items = [];
        $totalTaxPrice = 0;
        $totalQuantity = 0;
        $totalRate = 0;
        $totalDiscount = 0;
        $taxesData = [];
        foreach ($invoice->items as $product) {
            $item = new \stdClass();
            $item->name = !empty($product->product) ? $product->product->name : '';
            $item->quantity = $product->quantity;
            $item->tax = $product->tax;
            $item->unit = !empty($product->product) ? $product->product->unit_id : '';
            $item->discount = $product->discount;
            $item->price = $product->price;
            $item->description = $product->description;

            $totalQuantity += $item->quantity;
            $totalRate += $item->price;
            $totalDiscount += $item->discount;

            $taxes = Utility::tax($product->tax);

            $itemTaxes = [];
            if (!empty($item->tax)) {
                foreach ($taxes as $tax) {
                    $taxPrice = Utility::taxRate($tax->rate, $item->price, $item->quantity, $item->discount);
                    $totalTaxPrice += $taxPrice;

                    $itemTax['name'] = $tax->name;
                    $itemTax['rate'] = $tax->rate . '%';
                    $itemTax['price'] = Utility::priceFormat($settings, $taxPrice);
                    $itemTax['tax_price'] = $taxPrice;
                    $itemTaxes[] = $itemTax;

                    if (array_key_exists($tax->name, $taxesData)) {
                        $taxesData[$tax->name] = $taxesData[$tax->name] + $taxPrice;
                    } else {
                        $taxesData[$tax->name] = $taxPrice;
                    }
                }
                $item->itemTax = $itemTaxes;
            } else {
                $item->itemTax = [];
            }
            $items[] = $item;
        }

        $invoice->itemData = $items;
        $invoice->totalTaxPrice = $totalTaxPrice;
        $invoice->totalQuantity = $totalQuantity;
        $invoice->totalRate = $totalRate;
        $invoice->totalDiscount = $totalDiscount;
        $invoice->taxesData = $taxesData;
        $invoice->customField = CustomField::getData($invoice, 'invoice');
        $customFields = [];
        if (!empty(\Auth::user())) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())
                ->where('module', '=', 'invoice')
                ->get();
        }

        $logo = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $settings_data = \App\Models\Utility::settingsById($invoice->created_by);
        $invoice_logo = $settings_data['invoice_logo'];
        if (isset($invoice_logo) && !empty($invoice_logo)) {
            $img = Utility::get_file('invoice_logo/') . $invoice_logo;
        } else {
            $img = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        if ($invoice) {
            $color = '#' . $settings['invoice_color'];
            $font_color = Utility::getFontColor($color);

            return view('invoice.templates.' . $settings['invoice_template'], compact('invoice', 'color', 'settings', 'customer', 'img', 'font_color', 'customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function saveTemplateSettings(Request $request)
    {
        $post = $request->all();
        unset($post['_token']);

        if (isset($post['invoice_template']) && (!isset($post['invoice_color']) || empty($post['invoice_color']))) {
            $post['invoice_color'] = 'ffffff';
        }

        if ($request->invoice_logo) {
            $dir = 'invoice_logo/';
            $invoice_logo = \Auth::user()->id . '_invoice_logo.png';
            $validation = ['mimes:' . 'png', 'max:' . '20480'];
            $path = Utility::upload_file($request, 'invoice_logo', $invoice_logo, $dir, $validation);

            if ($path['flag'] == 0) {
                return redirect()
                    ->back()
                    ->with('error', __($path['msg']));
            }
            $post['invoice_logo'] = $invoice_logo;
        }

        foreach ($post as $key => $data) {
            \DB::insert('insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ', [$data, $key, \Auth::user()->creatorId()]);
        }
        //log
        return redirect()->back()->with('success', __('Invoice Setting updated successfully'));
    }

    public function items(Request $request)
    {
        $items = InvoiceProduct::where('invoice_id', $request->invoice_id)->where('product_id', $request->product_id)->first();

        return json_encode($items);
    }

    public function invoiceLink($invoiceId)
    {
        // dd('link');
        try {
            $id = Crypt::decrypt($invoiceId);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Invoice Not Found.'));
        }

        $id = Crypt::decrypt($invoiceId);
        $invoice = Invoice::with(['creditNote', 'payments.bankAccount', 'items.product.unit'])->find($id);
        $settings = Utility::settingsById($invoice->created_by);

        if (!empty($invoice)) {
            $user_id = $invoice->created_by;
            $user = User::find($user_id);
            $invoicePayment = InvoicePayment::where('invoice_id', $invoice->id)->get();
            $customer = $invoice->customer;
            $iteams = $invoice->items;
            $invoice->customField = CustomField::getData($invoice, 'invoice');
            $customFields = CustomField::where('module', '=', 'invoice')->where('created_by', $invoice->created_by)->get();
            $company_payment_setting = Utility::getCompanyPaymentSetting($user_id);

            // start for storage limit note
            $user_plan = Plan::find($user->plan);
            // end for storage limit note

            return view('invoice.customer_invoice', compact('settings', 'invoice', 'customer', 'iteams', 'invoicePayment', 'customFields', 'user', 'company_payment_setting', 'user_plan'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function export()
    {
        $name = 'invoice_' . date('Y-m-d i:h:s');
        $data = Excel::download(new InvoiceExport(), $name . '.xlsx');
        ob_end_clean();

        return $data;
    }

        public function salesRecieptsIndex()
    {
        return view('sales-reciepts.index');
    }

    public function salesRecieptsCreate($customerId)
    {
        if (\Auth::user()->can('create invoice')) {
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())
                ->where('module', '=', 'invoice')->get();
            $invoice_number = \Auth::user()->invoiceNumberFormat($this->invoiceNumber());

            $customers = Customer::where($column, $ownerId)->get()->pluck('name', 'id')->toArray();
            $customers = ['__add__' => '➕ Add new customer'] + ['' => 'Select Customer'] + $customers;

            $category = ProductServiceCategory::where($column, $ownerId)
                ->where('type', 'income')->get()->pluck('name', 'id')->toArray();
            $category = ['__add__' => '➕ Add new category'] + ['' => 'Select Category'] + $category;

            $product_services = ProductService::where($column, $ownerId)->get()->pluck('name', 'id');
            $product_services->prepend('--', '');

            return view('sales-reciepts.sales-reciepts', compact(
                'customers',
                'invoice_number',
                'product_services',
                'category',
                'customFields',
                'customerId'
            ));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    /**
     * Update estimate/proposal status based on invoiced items
     * - If all items from an estimate are invoiced, set status to "Close" (4)
     * - Otherwise, keep the status as is
     */
    private function updateEstimateStatusAfterInvoice(array $products)
    {
        // Collect unique estimate IDs from the products
        $estimateIds = collect($products)
            ->filter(function ($prod) {
                return !empty($prod['estimate_id']) && ($prod['line_type'] ?? null) === 'estimate';
            })
            ->pluck('estimate_id')
            ->unique()
            ->values()
            ->all();

        if (empty($estimateIds)) {
            return;
        }

        foreach ($estimateIds as $estimateId) {
            // Get total line items in the estimate
            $totalEstimateItems = \App\Models\ProposalProduct::where('proposal_id', $estimateId)->count();

            // Get count of unique proposal_product_ids that have been invoiced
            $invoicedItemsCount = \App\Models\InvoiceProduct::where('estimate_id', $estimateId)
                ->where('line_type', 'estimate')
                ->whereNotNull('proposal_product_id')
                ->distinct('proposal_product_id')
                ->count('proposal_product_id');

            // If all line items have been invoiced, update the estimate status to "Close" (4)
            if ($invoicedItemsCount >= $totalEstimateItems) {
                $proposal = \App\Models\Proposal::find($estimateId);
                if ($proposal) {
                    $proposal->status = 4; // Close
                    $proposal->save();
                }
            }
        }
    }

    /**
     * Get all suggested transactions (estimates, billable time, billable expenses) for a customer
     * Used in the invoice creation modal's suggestion panel
     */
    public function customerSuggestionsForInvoice(Request $request)
    {
        $user = \Auth::user();
        
        // Permission check
        if (!$user->can('create invoice')) {
            return response()->json(['message' => 'Permission denied.'], 403);
        }

        $customerId = $request->get('customer_id');
        
        if (!$customerId) {
            return response()->json([
                'estimates' => [],
                'billable_time' => [],
                'billable_expenses' => []
            ]);
        }

        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

        // 1. Get Estimates (reuse logic from ProposalController)
        $estimates = $this->getCustomerEstimates($customerId, $ownerId, $column, $user);

        // 2. Get Billable Time Activities
        $billableTime = $this->getCustomerBillableTime($customerId, $ownerId);

        // 3. Get Billable Expenses
        $billableExpenses = $this->getCustomerBillableExpenses($customerId, $ownerId);

        return response()->json([
            'estimates' => $estimates,
            'billable_time' => $billableTime,
            'billable_expenses' => $billableExpenses
        ]);
    }

    /**
     * Get estimates/proposals for a customer
     */
    private function getCustomerEstimates($customerId, $ownerId, $column, $user)
    {
        $proposals = \App\Models\Proposal::where('customer_id', $customerId)
            ->where($column, $ownerId)
            ->orderByDesc('issue_date')
            ->take(10)
            ->get();

        $proposalIds = $proposals->pluck('id');

        $itemsByProposal = \App\Models\ProposalProduct::whereIn('proposal_id', $proposalIds)
            ->orderBy('id')
            ->get()
            ->groupBy('proposal_id');

        // Get already invoiced items
        $invoicedProposalProductIds = \App\Models\InvoiceProduct::whereIn('estimate_id', $proposalIds)
            ->where('line_type', 'estimate')
            ->whereNotNull('proposal_product_id')
            ->pluck('proposal_product_id')
            ->toArray();

        $data = [];
        foreach ($proposals as $p) {
            $proposalItems = $itemsByProposal->get($p->id, collect());

            // Filter out already invoiced items
            $remainingItems = $proposalItems->filter(function ($item) use ($invoicedProposalProductIds) {
                return !in_array($item->id, $invoicedProposalProductIds);
            });

            if ($remainingItems->isEmpty()) {
                continue;
            }

            $items = $remainingItems->map(function ($item) {
                return [
                    'id'             => $item->id,
                    'product_id'     => $item->product_id,
                    'quantity'       => $item->quantity,
                    'price'          => $item->price,
                    'description'    => $item->description,
                    'amount'         => $item->amount,
                    'discount'       => $item->discount,
                    'tax'            => $item->tax,
                    'taxable'        => $item->taxable,
                    'item_tax_price' => $item->item_tax_price,
                    'item_tax_rate'  => $item->item_tax_rate,
                ];
            })->values()->all();

            $data[] = [
                'id'               => $p->id,
                'type'             => 'estimate',
                'encrypted_id'     => Crypt::encrypt($p->id),
                'proposal_number'  => $user->proposalNumberFormat($p->proposal_id),
                'issue_date'       => $p->issue_date,
                'total_amount'     => $p->total_amount ?? $p->subtotal ?? 0,
                'note'             => $p->note ?? '',
                'items'            => $items,
            ];
        }

        return $data;
    }

    /**
     * Get billable time activities for a customer
     */
    private function getCustomerBillableTime($customerId, $ownerId)
    {
        $timeActivities = \App\Models\TimeActivity::where('customer_id', $customerId)
            ->where('billable', true)
            ->where('created_by', $ownerId)
            ->whereNull('invoiced_at')
            ->with(['service', 'employee', 'vendor'])
            ->orderByDesc('date')
            ->take(20)
            ->get();

        return $timeActivities->map(function ($entry) {
            // Calculate amount based on duration and rate
            $amount = $this->calculateTimeAmount($entry);
            
            // Get the name of who did the work
            $workerName = '';
            if ($entry->user_type === 'employee' && $entry->employee) {
                $workerName = $entry->employee->name ?? '';
            } elseif ($entry->user_type === 'vendor' && $entry->vendor) {
                $workerName = $entry->vendor->name ?? '';
            }

            return [
                'id' => $entry->id,
                'type' => 'time',
                'date' => $entry->date,
                'duration' => $entry->duration,
                'rate' => $entry->rate,
                'amount' => $amount,
                'service_id' => $entry->service_id,
                'service_name' => $entry->service->name ?? 'Time Entry',
                'notes' => $entry->notes,
                'worker_name' => $workerName,
                'taxable' => $entry->taxable,
            ];
        })->values()->all();
    }

    /**
     * Calculate the amount for a time activity
     */
    private function calculateTimeAmount($entry)
    {
        $rate = floatval($entry->rate ?? 0);
        $duration = $entry->duration;
        
        // Duration might be in "HH:MM" format or decimal hours
        if (strpos($duration, ':') !== false) {
            $parts = explode(':', $duration);
            $hours = floatval($parts[0] ?? 0);
            $minutes = floatval($parts[1] ?? 0);
            $totalHours = $hours + ($minutes / 60);
        } else {
            $totalHours = floatval($duration);
        }
        
        return round($rate * $totalHours, 2);
    }

    /**
     * Get billable expenses for a customer
     */
    private function getCustomerBillableExpenses($customerId, $ownerId)
    {
        // Get billable items from BillAccount that are marked as billable to this customer
        $billableAccounts = \App\Models\BillAccount::where('customer_id', $customerId)
            ->where('billable', true)
            ->whereNull('invoiced_at')
            ->whereHas('bill', function($q) use ($ownerId) {
                $q->where('created_by', $ownerId);
            })
            ->with(['bill.vender', 'chartAccount'])
            ->orderByDesc('id')
            ->take(20)
            ->get();

        return $billableAccounts->map(function ($account) {
            return [
                'id' => $account->id,
                'type' => 'expense',
                'source_type' => 'bill',
                'source_id' => $account->ref_id,
                'date' => $account->bill->bill_date ?? null,
                'amount' => floatval($account->price),
                'description' => $account->description,
                'account_name' => $account->chartAccount->name ?? '',
                'vendor_name' => $account->bill->vender->name ?? '',
            ];
        })->values()->all();
    }
}

