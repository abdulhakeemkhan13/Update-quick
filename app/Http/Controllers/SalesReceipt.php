<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomField;
use App\Models\Invoice;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\Tax;
use Illuminate\Http\Request;

class SalesReceipt extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
        $salesReceipts = \App\Models\SalesReceipt::where('created_by', \Auth::user()->creatorId())->get();
        return view('sales-reciepts.index', compact('salesReceipts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($customerId = null)
    {
        if (\Auth::user()->can('create invoice')) {
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = $user->type == 'company' ? 'created_by' : 'owned_by';
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())
                ->where('module', '=', 'invoice')
                ->get();
            $invoice_number = \Auth::user()->salesReceiptNumberFormat($this->salesReceiptNumber());
            $customers = Customer::where($column, $ownerId)->get()->pluck('name', 'id')->toArray();
            $customers = ['__add__' => '➕ Add new customer'] + ['' => 'Select Customer'] + $customers;
            $category = ProductServiceCategory::where($column, $ownerId)->where('type', 'income')->get()->pluck('name', 'id')->toArray();
            $category = ['__add__' => '➕ Add new category'] + ['' => 'Select Category'] + $category;
            $product_services = ProductService::get()->pluck('name', 'id');
            $product_services->prepend('--', '');
            $taxes = Tax::where('created_by', \Auth::user()->creatorId())->get();

            // Always return modal view
            return view('sales-reciepts.sales-reciepts', compact('customers', 'invoice_number', 'product_services', 'category', 'customFields', 'customerId', 'taxes'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }
    // public function create()
    // {
    //     if(request()->ajax()){
    //         return view('sales-reciepts.sales-reciepts');
    //     }
    //     return view('sales-reciepts.sales-reciepts');
    // }

    /**
     * Store a newly created resource in storage.
     */
    public function salesReceiptNumber()
    {
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = $user->type == 'company' ? 'created_by' : 'owned_by';
        $latest = \App\Models\SalesReceipt::where($column, '=', $ownerId)->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->sales_receipt_id + 1;
    }

    public function store(Request $request)
    {
        \DB::beginTransaction();
        try {
            if (\Auth::user()->can('create invoice')) {
                $validator = \Validator::make($request->all(), [
                    'customer_id' => 'required',
                    'issue_date' => 'required',
                    'items' => 'required',
                    'items_payload' => 'nullable',
                    'customer_email' => 'nullable|email',
                    'payment_type' => 'nullable|string',
                    'payment_method' => 'nullable|string',
                    'deposit_to' => 'nullable|string',
                    'location_of_sale' => 'nullable|string',
                    'bill_to' => 'nullable|string',
                    'discount_type' => 'nullable|in:percent,value',
                    'discount_value' => 'nullable|numeric',
                    'sales_tax_rate' => 'nullable|string',
                    'subtotal' => 'nullable|numeric',
                    'taxable_subtotal' => 'nullable|numeric',
                    'total_discount' => 'nullable|numeric',
                    'total_tax' => 'nullable|numeric',
                    'sales_tax_amount' => 'nullable|numeric',
                    'total_amount' => 'nullable|numeric',
                    // 'ship_to' => 'nullable|string',
                    'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                    'attachments.*' => 'nullable|file|max:20480',
                ]);

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    if ($request->ajax()) {
                        return response()->json(['errors' => $validator->errors()], 422);
                    }
                    return redirect()->back()->with('error', $messages->first());
                }

                // Check if customer is selected
                if (empty($request->customer_id) || $request->customer_id == '') {
                    if ($request->ajax()) {
                        return response()->json(['error' => __('Please select a customer.')], 422);
                    }
                    return redirect()->back()->with('error', __('Please select a customer.'));
                }

                // Create Sales Receipt
                $salesReceipt = new \App\Models\SalesReceipt();
                $salesReceipt->sales_receipt_id = $this->salesReceiptNumber();
                $salesReceipt->customer_id = $request->customer_id;
                $salesReceipt->customer_email = $request->customer_email;
                $salesReceipt->status = 0; // Draft by default
                $salesReceipt->issue_date = $request->issue_date;
                $salesReceipt->ref_number = $request->ref_number;
                $salesReceipt->payment_type = $request->payment_type;
                $salesReceipt->payment_method = $request->payment_method;
                $salesReceipt->deposit_to = $request->deposit_to;
                $salesReceipt->location_of_sale = $request->location_of_sale;
                $salesReceipt->bill_to = $request->bill_to;
                $salesReceipt->category_id = $request->category_id ?? 1;
                $salesReceipt->created_by = \Auth::user()->creatorId();
                $salesReceipt->owned_by = \Auth::user()->ownedId();

                // Store calculated totals
                $salesReceipt->subtotal = $request->subtotal ?? 0;
                $salesReceipt->taxable_subtotal = $request->taxable_subtotal ?? 0;
                $salesReceipt->discount_type = $request->discount_type;
                $salesReceipt->discount_value = $request->discount_value ?? 0;
                $salesReceipt->total_discount = $request->total_discount ?? 0;
                $salesReceipt->sales_tax_rate = $request->sales_tax_rate;
                $salesReceipt->total_tax = $request->total_tax ?? 0;
                $salesReceipt->sales_tax_amount = $request->sales_tax_amount ?? 0;
                $salesReceipt->total_amount = $request->total_amount ?? 0;
                $salesReceipt->amount_received = $request->total_amount ?? 0; // For sales receipt, amount received = total
                $salesReceipt->balance_due = 0; // Fully paid
                $salesReceipt->memo = $request->memo;
                $salesReceipt->note = $request->note;

                // Store bill_to, ship_to
                $salesReceipt->bill_to = $request->bill_to;
                // $salesReceipt->ship_to = $request->ship_to;

                // Handle logo upload
                if ($request->hasFile('company_logo')) {
                    $logoFile = $request->file('company_logo');
                    $logoName = time() . '_logo.' . $logoFile->getClientOriginalExtension();
                    $logoFile->storeAs('uploads/sales_receipt_logos', $logoName, 'public');
                    $salesReceipt->logo = $logoName;
                }

                // Handle attachments
                if ($request->hasFile('attachments')) {
                    $attachments = [];
                    foreach ($request->file('attachments') as $attachment) {
                        $attachmentName = time() . '_' . uniqid() . '.' . $attachment->getClientOriginalExtension();
                        $attachment->storeAs('uploads/sales_receipt_attachments', $attachmentName, 'public');
                        $attachments[] = $attachmentName;
                    }
                    $salesReceipt->attachments = json_encode($attachments);
                }

                $salesReceipt->save();

                // Save Custom Fields
                \App\Models\CustomField::saveData($salesReceipt, $request->customField);

                // Parse items
                $products = $request->items;
                if (is_string($products)) {
                    $products = json_decode($products, true);
                }

                // If items_payload is provided, use ALL items
                $itemsPayload = $request->items_payload;
                if ($itemsPayload) {
                    if (is_string($itemsPayload)) {
                        $itemsPayload = json_decode($itemsPayload, true);
                    }
                    if (is_array($itemsPayload)) {
                        $products = $itemsPayload;
                    }
                }
                // dd($products);
                foreach ($products as $i => $prod) {
                    $salesReceiptProduct = new \App\Models\SalesReceiptProduct();
                    $salesReceiptProduct->sales_receipt_id = $salesReceipt->id;

                    $itemType = $prod['type'] ?? 'product';

                    if ($itemType === 'product') {
                        $salesReceiptProduct->product_id = $prod['item_id'] ?? ($prod['item'] ?? null);
                        $salesReceiptProduct->quantity = $prod['quantity'] ?? 0;
                        $salesReceiptProduct->tax = $prod['tax'] ?? null;
                        $salesReceiptProduct->discount = $prod['discount'] ?? 0;
                        $salesReceiptProduct->price = $prod['price'] ?? 0;
                        $salesReceiptProduct->description = $prod['description'] ?? '';
                        $salesReceiptProduct->taxable = $prod['is_taxable'] ?? ($prod['taxable'] ?? 0);
                        $salesReceiptProduct->item_tax_price = $prod['itemTaxPrice'] ?? ($prod['item_tax_price'] ?? 0);
                        $salesReceiptProduct->item_tax_rate = $prod['itemTaxRate'] ?? ($prod['item_tax_rate'] ?? 0);
                        $salesReceiptProduct->amount = $prod['amount'] ?? 0;

                        // Inventory management for products only
                        if ($salesReceiptProduct->product_id) {
                            \App\Models\Utility::total_quantity('minus', $salesReceiptProduct->quantity, $salesReceiptProduct->product_id);

                            // Stock Log
                            $type = 'sales_receipt';
                            $type_id = $salesReceipt->id;
                            $description = $salesReceiptProduct->quantity . ' ' . __(' quantity sold in sales receipt ') . \Auth::user()->invoiceNumberFormat($salesReceipt->sales_receipt_id);
                            \App\Models\Utility::addProductStock($salesReceiptProduct->product_id, $salesReceiptProduct->quantity, $type, $description, $type_id);
                        }
                    } elseif ($itemType === 'subtotal') {
                        $salesReceiptProduct->product_id = null;
                        $salesReceiptProduct->quantity = 0;
                        $salesReceiptProduct->price = 0;
                        $salesReceiptProduct->description = $prod['label'] ?? 'Subtotal';
                        $salesReceiptProduct->amount = $prod['amount'] ?? 0;
                        $salesReceiptProduct->discount = 0;
                        $salesReceiptProduct->tax = null;
                        $salesReceiptProduct->taxable = 0;
                        $salesReceiptProduct->item_tax_price = 0;
                        $salesReceiptProduct->item_tax_rate = 0;
                    } elseif ($itemType === 'text') {
                        $salesReceiptProduct->product_id = null;
                        $salesReceiptProduct->quantity = 0;
                        $salesReceiptProduct->price = 0;
                        $salesReceiptProduct->description = $prod['text'] ?? '';
                        $salesReceiptProduct->amount = 0;
                        $salesReceiptProduct->discount = 0;
                        $salesReceiptProduct->tax = null;
                        $salesReceiptProduct->taxable = 0;
                        $salesReceiptProduct->item_tax_price = 0;
                        $salesReceiptProduct->item_tax_rate = 0;
                    }

                    $salesReceiptProduct->save();
                }

                // Create Journal Voucher for sales receipt
                if (\Auth::user()->type == 'company') {
                    // $this->createSalesReceiptJournalVoucher($salesReceipt);
                    // $salesReceipt->status = 2; // Approved
                    // $salesReceipt->save();
                    \App\Models\Utility::makeActivityLog(\Auth::user()->id, 'Sales Receipt', $salesReceipt->id, 'Create Sales Receipt', 'Sales Receipt Created & Approved');
                }

                // Webhook
                $module = 'New Sales Receipt';
                $webhook = \App\Models\Utility::webhookSetting($module);
                if ($webhook) {
                    $parameter = json_encode($salesReceipt);
                    $status = \App\Models\Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                    if (!$status) {
                        \DB::commit();
                        return redirect()->back()->with('error', __('Webhook call failed.'));
                    }
                }

                \App\Models\Utility::makeActivityLog(\Auth::user()->id, 'Sales Receipt', $salesReceipt->id, 'Create Sales Receipt', 'Sales Receipt Created');

                \DB::commit();

                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => __('Sales receipt successfully created.'),
                        'redirect' => route('sales-receipt.index'),
                    ]);
                }

                return redirect()->route('sales-receipt.index')->with('success', __('Sales receipt successfully created.'));
            } else {
                if ($request->ajax()) {
                    return response()->json(['error' => __('Permission denied.')], 403);
                }
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            dd($e);
            \Log::error('Sales receipt creation error: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['error' => __('An error occurred while creating the sales receipt.')], 500);
            }

            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    private function createSalesReceiptJournalVoucher(\App\Models\SalesReceipt $salesReceipt)
    {
        $salesReceiptProducts = $salesReceipt->items;
        $newitems = [];

        foreach ($salesReceiptProducts as $product) {
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
            'id' => $salesReceipt->id,
            'no' => $salesReceipt->sales_receipt_id,
            'date' => $salesReceipt->issue_date,
            'created_at' => now()->format('Y-m-d h:i:s'),
            'reference' => $salesReceipt->ref_number,
            'category' => 'Sales Receipt',
            'owned_by' => $salesReceipt->owned_by,
            'created_by' => $salesReceipt->created_by,
            'prod_id' => $salesReceiptProducts->where('product_id', '!=', null)->first()->product_id ?? null,
            'items' => $newitems,
        ];

        $voucherId = \App\Models\Utility::jrentry($data);
        $salesReceipt->voucher_id = $voucherId;
        $salesReceipt->save();

        return $voucherId;
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        if (\Auth::user()->can('show invoice')) {
            // try {
            //     $id = \Crypt::decrypt($id);
            // } catch (\Throwable $th) {
            //     return redirect()->back()->with('error', __('Sales Receipt Not Found.'));
            // }
            $salesReceipt = \App\Models\SalesReceipt::with(['items.product.unit'])->find($id);

            if (!empty($salesReceipt->created_by) == \Auth::user()->creatorId()) {
                // Check if request is AJAX (for modal loading)
                if (request()->ajax()) {
                    $user = \Auth::user();
                    $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
                    $column = $user->type == 'company' ? 'created_by' : 'owned_by';
                    $customers = \App\Models\Customer::where($column, '=', $ownerId)->get()->pluck('name', 'id')->toArray();
                    $customers = ['__add__' => '➕ Add new customer'] + ['' => 'Select Customer'] + $customers;
                    $category = \App\Models\ProductServiceCategory::where($column, $ownerId)->where('type', 'income')->get()->pluck('name', 'id')->toArray();
                    $category = ['__add__' => '➕ Add new category'] + ['' => 'Select Category'] + $category;
                    $product_services = \App\Models\ProductService::get()->pluck('name', 'id');
                    $product_services->prepend('--', '');
                    $taxes = \App\Models\Tax::where('created_by', \Auth::user()->creatorId())->get();
                    $customFields = \App\Models\CustomField::where('created_by', '=', \Auth::user()->creatorId())
                        ->where('module', '=', 'invoice')
                        ->get();

                    // Populate customer data
                    $customerId = $salesReceipt->customer_id;
                    $customerData = \App\Models\Customer::find($customerId);
                    $billTo = '';
                    if ($customerData) {
                        $billTo = $customerData->billing_name . "\n" . $customerData->billing_phone . "\n" . $customerData->billing_address . "\n" . $customerData->billing_city . ' , ' . $customerData->billing_state . ' , ' . $customerData->billing_country . '.' . "\n" . $customerData->billing_zip;
                    }

                    // Load sales receipt items with product details
                    $salesReceipt->load(['items.product']);

                    // Prepare sales receipt data for JavaScript
                    $salesReceiptData = [
                        'id' => $salesReceipt->id,
                        'sales_receipt_id' => $salesReceipt->sales_receipt_id,
                        'customer_id' => $salesReceipt->customer_id,
                        'issue_date' => $salesReceipt->issue_date,
                        'ref_number' => $salesReceipt->ref_number,
                        'payment_type' => $salesReceipt->payment_type,
                        'payment_method' => $salesReceipt->payment_method,
                        'deposit_to' => $salesReceipt->deposit_to,
                        'location_of_sale' => $salesReceipt->location_of_sale,
                        'bill_to' => $salesReceipt->bill_to,
                        'category_id' => $salesReceipt->category_id,
                        'subtotal' => $salesReceipt->subtotal,
                        'taxable_subtotal' => $salesReceipt->taxable_subtotal,
                        'total_discount' => $salesReceipt->total_discount,
                        'total_tax' => $salesReceipt->total_tax,
                        'sales_tax_amount' => $salesReceipt->sales_tax_amount,
                        'total_amount' => $salesReceipt->total_amount,
                        'amount_received' => $salesReceipt->amount_received,
                        'balance_due' => $salesReceipt->balance_due,
                        'memo' => $salesReceipt->memo,
                        'note' => $salesReceipt->note,
                        'items' => $salesReceipt->items
                                ->map(function ($item) {
                                    return [
                                        'id' => $item->id,
                                        'type' => 'product', // Add type field for JavaScript processing
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
                                    ];
                                })
                                ->toArray(),
                    ];

                    return view('sales-reciepts.sales-reciepts', compact('customers', 'salesReceipt', 'product_services', 'category', 'customFields', 'customerId', 'taxes', 'billTo', 'salesReceiptData'))->with('mode', 'show');
                }

                $customer = $salesReceipt->customer;
                $iteams = $salesReceipt->items;
                $user = \Auth::user();

                // start for storage limit note
                $salesReceipt_user = \App\Models\User::find($salesReceipt->created_by);
                $user_plan = \App\Models\Plan::getPlan($salesReceipt_user->plan);
                // end for storage limit note

                $salesReceipt->customField = \App\Models\CustomField::getData($salesReceipt, 'invoice');
                $customFields = \App\Models\CustomField::where('created_by', '=', \Auth::user()->creatorId())
                    ->where('module', '=', 'invoice')
                    ->get();

                return view('sales-reciepts.view', compact('salesReceipt', 'customer', 'iteams', 'customFields', 'user', 'salesReceipt_user', 'user_plan'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        if (\Auth::user()->can('edit invoice')) {
            // try {
            //     $id = \Crypt::decrypt($id);
            // } catch (\Throwable $th) {
            //     return redirect()->back()->with('error', __('Sales Receipt Not Found.'));
            // }
            // $id = \Crypt::decrypt($id);
            $salesReceipt = \App\Models\SalesReceipt::find($id);

            if (!empty($salesReceipt->created_by) == \Auth::user()->creatorId()) {
                $user = \Auth::user();
                $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
                $column = $user->type == 'company' ? 'created_by' : 'owned_by';
                $customFields = \App\Models\CustomField::where('created_by', '=', \Auth::user()->creatorId())
                    ->where('module', '=', 'invoice')
                    ->get();
                $invoice_number = \Auth::user()->salesReceiptNumberFormat($salesReceipt->sales_receipt_id);
                $customers = \App\Models\Customer::where($column, $ownerId)->get()->pluck('name', 'id')->toArray();
                $customers = ['__add__' => '➕ Add new customer'] + ['' => 'Select Customer'] + $customers;
                $category = \App\Models\ProductServiceCategory::where($column, $ownerId)->where('type', 'income')->get()->pluck('name', 'id')->toArray();
                $category = ['__add__' => '➕ Add new category'] + ['' => 'Select Category'] + $category;
                $product_services = \App\Models\ProductService::get()->pluck('name', 'id');
                $product_services->prepend('--', '');
                $taxes = \App\Models\Tax::where('created_by', \Auth::user()->creatorId())->get();

                // Populate customer data
                $customerId = $salesReceipt->customer_id;
                $customerData = \App\Models\Customer::find($customerId);
                $billTo = '';
                if ($customerData) {
                    $billTo = $customerData->billing_name . "\n" . $customerData->billing_phone . "\n" . $customerData->billing_address . "\n" . $customerData->billing_city . ' , ' . $customerData->billing_state . ' , ' . $customerData->billing_country . '.' . "\n" . $customerData->billing_zip;
                }

                // Load sales receipt items with product details
                $salesReceipt->load(['items.product']);

                // Prepare sales receipt data for JavaScript
                $salesReceiptData = [
                    'id' => $salesReceipt->id,
                    'sales_receipt_id' => $salesReceipt->sales_receipt_id,
                    'customer_id' => $salesReceipt->customer_id,
                    'issue_date' => $salesReceipt->issue_date,
                    'ref_number' => $salesReceipt->ref_number,
                    'payment_type' => $salesReceipt->payment_type,
                    'payment_method' => $salesReceipt->payment_method,
                    'deposit_to' => $salesReceipt->deposit_to,
                    'location_of_sale' => $salesReceipt->location_of_sale,
                    'bill_to' => $salesReceipt->bill_to,
                    'category_id' => $salesReceipt->category_id,
                    'subtotal' => $salesReceipt->subtotal,
                    'taxable_subtotal' => $salesReceipt->taxable_subtotal,
                    'total_discount' => $salesReceipt->total_discount,
                    'total_tax' => $salesReceipt->total_tax,
                    'sales_tax_amount' => $salesReceipt->sales_tax_amount,
                    'total_amount' => $salesReceipt->total_amount,
                    'amount_received' => $salesReceipt->amount_received,
                    'balance_due' => $salesReceipt->balance_due,
                    'memo' => $salesReceipt->memo,
                    'note' => $salesReceipt->note,
                    'items' => $salesReceipt->items
                        ->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'type' => 'product', // Add type field for JavaScript processing
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
                            ];
                        })
                        ->toArray(),
                ];

                return view('sales-reciepts.sales-reciepts', compact('customers', 'invoice_number', 'product_services', 'category', 'customFields', 'customerId', 'taxes', 'billTo', 'salesReceiptData'))->with('mode', 'edit');
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        \DB::beginTransaction();
        try {
            if (\Auth::user()->can('edit invoice')) {
                $salesReceipt = \App\Models\SalesReceipt::find($id);
                if (!$salesReceipt) {
                    return redirect()->back()->with('error', __('Sales Receipt not found.'));
                }

                if ($salesReceipt->created_by != \Auth::user()->creatorId()) {
                    return redirect()->back()->with('error', __('Permission denied.'));
                }

                $validator = \Validator::make($request->all(), [
                    'customer_id' => 'required',
                    'issue_date' => 'required',
                    'items' => 'required',
                    'items_payload' => 'nullable',
                    'customer_email' => 'nullable|email',
                    'payment_type' => 'nullable|string',
                    'payment_method' => 'nullable|string',
                    'deposit_to' => 'nullable|string',
                    'location_of_sale' => 'nullable|string',
                    'bill_to' => 'nullable|string',
                    'discount_type' => 'nullable|in:percent,value',
                    'discount_value' => 'nullable|numeric',
                    'sales_tax_rate' => 'nullable|string',
                    'subtotal' => 'nullable|numeric',
                    'taxable_subtotal' => 'nullable|numeric',
                    'total_discount' => 'nullable|numeric',
                    'total_tax' => 'nullable|numeric',
                    'sales_tax_amount' => 'nullable|numeric',
                    'total_amount' => 'nullable|numeric',
                    'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                    'attachments.*' => 'nullable|file|max:20480',
                ]);

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    if ($request->ajax()) {
                        return response()->json(['errors' => $validator->errors()], 422);
                    }
                    return redirect()->back()->with('error', $messages->first());
                }

                // Check if customer is selected
                if (empty($request->customer_id) || $request->customer_id == '') {
                    if ($request->ajax()) {
                        return response()->json(['error' => __('Please select a customer.')], 422);
                    }
                    return redirect()->back()->with('error', __('Please select a customer.'));
                }

                // Store old items for inventory reversal
                $oldItems = $salesReceipt->items->toArray();

                // Update Sales Receipt
                $salesReceipt->customer_id = $request->customer_id;
                $salesReceipt->customer_email = $request->customer_email;
                $salesReceipt->issue_date = $request->issue_date;
                $salesReceipt->ref_number = $request->ref_number;
                $salesReceipt->payment_type = $request->payment_type;
                $salesReceipt->payment_method = $request->payment_method;
                $salesReceipt->deposit_to = $request->deposit_to;
                $salesReceipt->location_of_sale = $request->location_of_sale;
                $salesReceipt->bill_to = $request->bill_to;
                $salesReceipt->category_id = $request->category_id ?? 1;
                $salesReceipt->subtotal = $request->subtotal ?? 0;
                $salesReceipt->taxable_subtotal = $request->taxable_subtotal ?? 0;
                $salesReceipt->discount_type = $request->discount_type;
                $salesReceipt->discount_value = $request->discount_value ?? 0;
                $salesReceipt->total_discount = $request->total_discount ?? 0;
                $salesReceipt->sales_tax_rate = $request->sales_tax_rate;
                $salesReceipt->total_tax = $request->total_tax ?? 0;
                $salesReceipt->sales_tax_amount = $request->sales_tax_amount ?? 0;
                $salesReceipt->total_amount = $request->total_amount ?? 0;
                $salesReceipt->amount_received = $request->total_amount ?? 0; // For sales receipt, amount received = total
                $salesReceipt->balance_due = 0; // Fully paid
                $salesReceipt->memo = $request->memo;
                $salesReceipt->note = $request->note;

                // Handle logo upload
                if ($request->hasFile('company_logo')) {
                    // Delete old logo if exists
                    if ($salesReceipt->logo && \Storage::exists('uploads/sales_receipt_logos/' . $salesReceipt->logo)) {
                        \Storage::delete('uploads/sales_receipt_logos/' . $salesReceipt->logo);
                    }
                    $logoFile = $request->file('company_logo');
                    $logoName = time() . '_logo.' . $logoFile->getClientOriginalExtension();
                    $logoFile->storeAs('uploads/sales_receipt_logos', $logoName, 'public');
                    $salesReceipt->logo = $logoName;
                }

                // Handle attachments
                if ($request->hasFile('attachments')) {
                    // Delete old attachments
                    if ($salesReceipt->attachments) {
                        $oldAttachments = json_decode($salesReceipt->attachments, true);
                        if (is_array($oldAttachments)) {
                            foreach ($oldAttachments as $oldAttachment) {
                                if (\Storage::exists('uploads/sales_receipt_attachments/' . $oldAttachment)) {
                                    \Storage::delete('uploads/sales_receipt_attachments/' . $oldAttachment);
                                }
                            }
                        }
                    }
                    $attachments = [];
                    foreach ($request->file('attachments') as $attachment) {
                        $attachmentName = time() . '_' . uniqid() . '.' . $attachment->getClientOriginalExtension();
                        $attachment->storeAs('uploads/sales_receipt_attachments', $attachmentName, 'public');
                        $attachments[] = $attachmentName;
                    }
                    $salesReceipt->attachments = json_encode($attachments);
                }

                $salesReceipt->save();

                // Save Custom Fields
                \App\Models\CustomField::saveData($salesReceipt, $request->customField);

                // Reverse old inventory changes
                foreach ($oldItems as $oldItem) {
                    if ($oldItem['product_id']) {
                        \App\Models\Utility::total_quantity('plus', $oldItem['quantity'], $oldItem['product_id']);
                        // Remove old stock log
                        $type = 'sales_receipt';
                        $type_id = $salesReceipt->id;
                        $description = $oldItem['quantity'] . ' ' . __(' quantity returned in sales receipt update ') . \Auth::user()->invoiceNumberFormat($salesReceipt->sales_receipt_id);
                        \App\Models\Utility::addProductStock($oldItem['product_id'], -$oldItem['quantity'], $type, $description, $type_id);
                    }
                }

                // Delete old items
                \App\Models\SalesReceiptProduct::where('sales_receipt_id', $salesReceipt->id)->delete();

                // Parse new items
                $products = $request->items;
                if (is_string($products)) {
                    $products = json_decode($products, true);
                }

                // If items_payload is provided, use ALL items
                $itemsPayload = $request->items_payload;
                if ($itemsPayload) {
                    if (is_string($itemsPayload)) {
                        $itemsPayload = json_decode($itemsPayload, true);
                    }
                    if (is_array($itemsPayload)) {
                        $products = $itemsPayload;
                    }
                }

                foreach ($products as $i => $prod) {
                    $salesReceiptProduct = new \App\Models\SalesReceiptProduct();
                    $salesReceiptProduct->sales_receipt_id = $salesReceipt->id;

                    $itemType = $prod['type'] ?? 'product';

                    if ($itemType === 'product') {
                        $salesReceiptProduct->product_id = $prod['item_id'] ?? ($prod['item'] ?? null);
                        $salesReceiptProduct->quantity = $prod['quantity'] ?? 0;
                        $salesReceiptProduct->tax = $prod['tax'] ?? null;
                        $salesReceiptProduct->discount = $prod['discount'] ?? 0;
                        $salesReceiptProduct->price = $prod['price'] ?? 0;
                        $salesReceiptProduct->description = $prod['description'] ?? '';
                        $salesReceiptProduct->taxable = $prod['is_taxable'] ?? ($prod['taxable'] ?? 0);
                        $salesReceiptProduct->item_tax_price = $prod['itemTaxPrice'] ?? ($prod['item_tax_price'] ?? 0);
                        $salesReceiptProduct->item_tax_rate = $prod['itemTaxRate'] ?? ($prod['item_tax_rate'] ?? 0);
                        $salesReceiptProduct->amount = $prod['amount'] ?? 0;

                        // Inventory management for products only
                        if ($salesReceiptProduct->product_id) {
                            \App\Models\Utility::total_quantity('minus', $salesReceiptProduct->quantity, $salesReceiptProduct->product_id);

                            // Stock Log
                            $type = 'sales_receipt';
                            $type_id = $salesReceipt->id;
                            $description = $salesReceiptProduct->quantity . ' ' . __(' quantity sold in sales receipt ') . \Auth::user()->invoiceNumberFormat($salesReceipt->sales_receipt_id);
                            \App\Models\Utility::addProductStock($salesReceiptProduct->product_id, $salesReceiptProduct->quantity, $type, $description, $type_id);
                        }
                    } elseif ($itemType === 'subtotal') {
                        $salesReceiptProduct->product_id = null;
                        $salesReceiptProduct->quantity = 0;
                        $salesReceiptProduct->price = 0;
                        $salesReceiptProduct->description = $prod['label'] ?? 'Subtotal';
                        $salesReceiptProduct->amount = $prod['amount'] ?? 0;
                        $salesReceiptProduct->discount = 0;
                        $salesReceiptProduct->tax = null;
                        $salesReceiptProduct->taxable = 0;
                        $salesReceiptProduct->item_tax_price = 0;
                        $salesReceiptProduct->item_tax_rate = 0;
                    } elseif ($itemType === 'text') {
                        $salesReceiptProduct->product_id = null;
                        $salesReceiptProduct->quantity = 0;
                        $salesReceiptProduct->price = 0;
                        $salesReceiptProduct->description = $prod['text'] ?? '';
                        $salesReceiptProduct->amount = 0;
                        $salesReceiptProduct->discount = 0;
                        $salesReceiptProduct->tax = null;
                        $salesReceiptProduct->taxable = 0;
                        $salesReceiptProduct->item_tax_price = 0;
                        $salesReceiptProduct->item_tax_rate = 0;
                    }

                    $salesReceiptProduct->save();
                }

                \App\Models\Utility::makeActivityLog(\Auth::user()->id, 'Sales Receipt', $salesReceipt->id, 'Update Sales Receipt', 'Sales Receipt Updated');

                \DB::commit();

                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => __('Sales receipt successfully updated.'),
                        'redirect' => route('sales-receipt.index'),
                    ]);
                }

                return redirect()->route('sales-receipt.index')->with('success', __('Sales receipt successfully updated.'));
            } else {
                if ($request->ajax()) {
                    return response()->json(['error' => __('Permission denied.')], 403);
                }
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Sales receipt update error: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['error' => __('An error occurred while updating the sales receipt.')], 500);
            }

            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (\Auth::user()->can('delete invoice')) {
            $salesReceipt = \App\Models\SalesReceipt::find($id);
            if (!$salesReceipt) {
                return redirect()->back()->with('error', __('Sales Receipt not found.'));
            }

            if ($salesReceipt->created_by != \Auth::user()->creatorId()) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            \DB::beginTransaction();
            try {
                // Reverse inventory changes
                foreach ($salesReceipt->items as $item) {
                    if ($item->product_id) {
                        \App\Models\Utility::total_quantity('plus', $item->quantity, $item->product_id);

                        // Remove stock log
                        $type = 'sales_receipt';
                        $type_id = $salesReceipt->id;
                        $description = $item->quantity . ' ' . __(' quantity returned on sales receipt deletion ') . \Auth::user()->invoiceNumberFormat($salesReceipt->sales_receipt_id);
                        \App\Models\Utility::addProductStock($item->product_id, -$item->quantity, $type, $description, $type_id);
                    }
                }

                // Delete journal entry if exists
                if ($salesReceipt->voucher_id) {
                    \App\Models\JournalEntry::where('id', $salesReceipt->voucher_id)->where('category', 'Sales Receipt')->delete();
                    \App\Models\JournalItem::where('journal', $salesReceipt->voucher_id)->delete();
                }

                // Delete attachments
                if ($salesReceipt->attachments) {
                    $attachments = json_decode($salesReceipt->attachments, true);
                    if (is_array($attachments)) {
                        foreach ($attachments as $attachment) {
                            if (\Storage::exists('uploads/sales_receipt_attachments/' . $attachment)) {
                                \Storage::delete('uploads/sales_receipt_attachments/' . $attachment);
                            }
                        }
                    }
                }

                // Delete logo
                if ($salesReceipt->logo && \Storage::exists('uploads/sales_receipt_logos/' . $salesReceipt->logo)) {
                    \Storage::delete('uploads/sales_receipt_logos/' . $salesReceipt->logo);
                }

                // Delete the sales receipt (this will cascade delete products due to foreign key)
                $salesReceipt->delete();

                \App\Models\Utility::makeActivityLog(\Auth::user()->id, 'Sales Receipt', $id, 'Delete Sales Receipt', 'Sales Receipt Deleted');

                \DB::commit();

                return redirect()->route('sales-receipt.index')->with('success', __('Sales receipt successfully deleted.'));
            } catch (\Exception $e) {
                \DB::rollBack();
                \Log::error('Sales receipt deletion error: ' . $e->getMessage());
                return redirect()->back()->with('error', __('An error occurred while deleting the sales receipt.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function resent($id)
    {
        if (\Auth::user()->can('show invoice')) {
            $salesReceipt = \App\Models\SalesReceipt::find($id);
            if (!$salesReceipt) {
                return redirect()->back()->with('error', __('Sales Receipt not found.'));
            }

            if ($salesReceipt->created_by != \Auth::user()->creatorId()) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            $customer = \App\Models\Customer::find($salesReceipt->customer_id);
            if ($customer && $customer->email) {
                try {
                    \Mail::to($customer->email)->send(new \App\Mail\SalesReceipt($salesReceipt, $customer));
                    return redirect()->back()->with('success', __('Sales Receipt successfully sent.'));
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', __('Something went wrong.'));
                }
            } else {
                return redirect()->back()->with('error', __('Customer email not found.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function pdf($id)
    {
        if (\Auth::user()->can('show invoice')) {
            $salesReceipt = \App\Models\SalesReceipt::find($id);
            if (!$salesReceipt) {
                return redirect()->back()->with('error', __('Sales Receipt not found.'));
            }

            if ($salesReceipt->created_by != \Auth::user()->creatorId()) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            $customer = $salesReceipt->customer;
            $iteams = $salesReceipt->items;

            $settings = \App\Models\Utility::settings();

            $pdf = \PDF::loadView('sales-reciepts.template', compact('salesReceipt', 'customer', 'iteams', 'settings'));
            return $pdf->download('sales_receipt_' . \Auth::user()->salesReceiptNumberFormat($salesReceipt->sales_receipt_id) . '.pdf');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function linkCopy($id)
    {
        $salesReceipt = \App\Models\SalesReceipt::find($id);
        if (!$salesReceipt) {
            return redirect()->back()->with('error', __('Sales Receipt not found.'));
        }

        if ($salesReceipt->created_by != \Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        return view('sales-reciepts.view', compact('salesReceipt', 'customer', 'iteams', 'customFields', 'user', 'salesReceipt_user', 'user_plan'));
    }
}
