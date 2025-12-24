<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomField;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\Tax;
use App\Models\RefundReceipt;
use App\Models\RefundReceiptProduct;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountType;
use App\Models\BankAccount;
use Illuminate\Http\Request;

class RefundReceiptController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $refundReceipts = RefundReceipt::where('created_by', \Auth::user()->creatorId())->get();
        return view('refundReceipt.index', compact('refundReceipts'));
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
            $refund_receipt_number = \Auth::user()->refundReceiptNumberFormat($this->refundReceiptNumber());
            $customers = Customer::where($column, $ownerId)->get()->pluck('name', 'id')->toArray();
            $customers = ['__add__' => '➕ Add new customer'] + ['' => 'Select Customer'] + $customers;
            $category = ProductServiceCategory::where($column, $ownerId)->where('type', 'income')->get()->pluck('name', 'id')->toArray();
            $category = ['__add__' => '➕ Add new category'] + ['' => 'Select Category'] + $category;
            $product_services = ProductService::get()->pluck('name', 'id');
            $product_services->prepend('--', '');
            $taxes = Tax::where('created_by', \Auth::user()->creatorId())->get();
            
            // Get bank accounts for "Refund From" dropdown
            $bankAccounts = BankAccount::where('created_by', \Auth::user()->creatorId())
                ->get()
                ->mapWithKeys(function ($account) {
                    $displayName = !empty($account->bank_name) ? $account->bank_name :
                        (!empty($account->institution_name) ? $account->institution_name : $account->holder_name);
                    return [$account->id => $displayName];
                });
            $bankAccounts = ['' => 'Choose an account'] + $bankAccounts->toArray();

            // Get payment methods
            $paymentMethods = [
                '' => 'Choose payment method',
                'Cash' => 'Cash',
                'Check' => 'Check',
                'Credit Card' => 'Credit Card',
                'Debit Card' => 'Debit Card',
                'Bank Transfer' => 'Bank Transfer',
                'Other' => 'Other',
            ];

            return view('refundReceipt.create', compact('customers', 'refund_receipt_number', 'product_services', 'category', 'customFields', 'customerId', 'taxes', 'bankAccounts', 'paymentMethods'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    /**
     * Generate next refund receipt number
     */
    public function refundReceiptNumber()
    {
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = $user->type == 'company' ? 'created_by' : 'owned_by';
        $latest = RefundReceipt::where($column, '=', $ownerId)->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->refund_receipt_id + 1;
    }

    /**
     * Store a newly created resource in storage.
     */
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
                    'payment_method' => 'nullable|string',
                    'refund_from' => 'nullable|string',
                    'location_of_sale' => 'nullable|string',
                    'billing_address' => 'nullable|string',
                    'discount_type' => 'nullable|in:percent,value',
                    'discount_value' => 'nullable|numeric',
                    'sales_tax_rate' => 'nullable|string',
                    'subtotal' => 'nullable|numeric',
                    'taxable_subtotal' => 'nullable|numeric',
                    'total_discount' => 'nullable|numeric',
                    'total_tax' => 'nullable|numeric',
                    'sales_tax_amount' => 'nullable|numeric',
                    'total_amount' => 'nullable|numeric',
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

                // Create Refund Receipt
                $refundReceipt = new RefundReceipt();
                $refundReceipt->refund_receipt_id = $this->refundReceiptNumber();
                $refundReceipt->customer_id = $request->customer_id;
                $refundReceipt->customer_email = $request->customer_email;
                $refundReceipt->status = 0; // Draft by default
                $refundReceipt->issue_date = $request->issue_date;
                $refundReceipt->ref_number = $request->ref_number;
                $refundReceipt->payment_method = $request->payment_method;
                $refundReceipt->refund_from = $request->refund_from;
                $refundReceipt->location_of_sale = $request->location_of_sale;
                $refundReceipt->billing_address = $request->billing_address;
                $refundReceipt->category_id = $request->category_id ?? 1;
                $refundReceipt->created_by = \Auth::user()->creatorId();
                $refundReceipt->owned_by = \Auth::user()->ownedId();

                // Store calculated totals
                $refundReceipt->subtotal = $request->subtotal ?? 0;
                $refundReceipt->taxable_subtotal = $request->taxable_subtotal ?? 0;
                $refundReceipt->discount_type = $request->discount_type;
                $refundReceipt->discount_value = $request->discount_value ?? 0;
                $refundReceipt->total_discount = $request->total_discount ?? 0;
                $refundReceipt->sales_tax_rate = $request->sales_tax_rate;
                $refundReceipt->total_tax = $request->total_tax ?? 0;
                $refundReceipt->sales_tax_amount = $request->sales_tax_amount ?? 0;
                $refundReceipt->total_amount = $request->total_amount ?? 0;
                $refundReceipt->total_amount_refunded = $request->total_amount ?? 0;
                $refundReceipt->memo = $request->memo;
                $refundReceipt->statement_memo = $request->statement_memo;

                // Handle attachments
                if ($request->hasFile('attachments')) {
                    $attachments = [];
                    foreach ($request->file('attachments') as $attachment) {
                        $attachmentName = time() . '_' . uniqid() . '.' . $attachment->getClientOriginalExtension();
                        $attachment->storeAs('uploads/refund_receipt_attachments', $attachmentName, 'public');
                        $attachments[] = $attachmentName;
                    }
                    $refundReceipt->attachments = json_encode($attachments);
                }

                $refundReceipt->save();

                // Save Custom Fields
                CustomField::saveData($refundReceipt, $request->customField);

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

                foreach ($products as $i => $prod) {
                    $refundReceiptProduct = new RefundReceiptProduct();
                    $refundReceiptProduct->refund_receipt_id = $refundReceipt->id;

                    $itemType = $prod['type'] ?? 'product';

                    if ($itemType === 'product') {
                        $refundReceiptProduct->product_id = $prod['item_id'] ?? ($prod['item'] ?? null);
                        $refundReceiptProduct->quantity = $prod['quantity'] ?? 0;
                        $refundReceiptProduct->tax = $prod['tax'] ?? null;
                        $refundReceiptProduct->discount = $prod['discount'] ?? 0;
                        $refundReceiptProduct->price = $prod['price'] ?? 0;
                        $refundReceiptProduct->description = $prod['description'] ?? '';
                        $refundReceiptProduct->taxable = $prod['is_taxable'] ?? ($prod['taxable'] ?? 0);
                        $refundReceiptProduct->item_tax_price = $prod['itemTaxPrice'] ?? ($prod['item_tax_price'] ?? 0);
                        $refundReceiptProduct->item_tax_rate = $prod['itemTaxRate'] ?? ($prod['item_tax_rate'] ?? 0);
                        $refundReceiptProduct->amount = $prod['amount'] ?? 0;

                        // For refunds, we ADD inventory back (opposite of sales)
                        if ($refundReceiptProduct->product_id) {
                            \App\Models\Utility::total_quantity('plus', $refundReceiptProduct->quantity, $refundReceiptProduct->product_id);

                            // Stock Log
                            $type = 'refund_receipt';
                            $type_id = $refundReceipt->id;
                            $description = $refundReceiptProduct->quantity . ' ' . __(' quantity returned via refund receipt ') . \Auth::user()->invoiceNumberFormat($refundReceipt->refund_receipt_id);
                            \App\Models\Utility::addProductStock($refundReceiptProduct->product_id, $refundReceiptProduct->quantity, $type, $description, $type_id);
                        }
                    } elseif ($itemType === 'subtotal') {
                        $refundReceiptProduct->product_id = null;
                        $refundReceiptProduct->quantity = 0;
                        $refundReceiptProduct->price = 0;
                        $refundReceiptProduct->description = $prod['label'] ?? 'Subtotal';
                        $refundReceiptProduct->amount = $prod['amount'] ?? 0;
                        $refundReceiptProduct->discount = 0;
                        $refundReceiptProduct->tax = null;
                        $refundReceiptProduct->taxable = 0;
                        $refundReceiptProduct->item_tax_price = 0;
                        $refundReceiptProduct->item_tax_rate = 0;
                    } elseif ($itemType === 'text') {
                        $refundReceiptProduct->product_id = null;
                        $refundReceiptProduct->quantity = 0;
                        $refundReceiptProduct->price = 0;
                        $refundReceiptProduct->description = $prod['text'] ?? '';
                        $refundReceiptProduct->amount = 0;
                        $refundReceiptProduct->discount = 0;
                        $refundReceiptProduct->tax = null;
                        $refundReceiptProduct->taxable = 0;
                        $refundReceiptProduct->item_tax_price = 0;
                        $refundReceiptProduct->item_tax_rate = 0;
                    }

                    $refundReceiptProduct->save();
                }

                // Activity log
                \App\Models\Utility::makeActivityLog(\Auth::user()->id, 'Refund Receipt', $refundReceipt->id, 'Create Refund Receipt', 'Refund Receipt Created');

                \DB::commit();

                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => __('Refund receipt successfully created.'),
                        'redirect' => route('refund-receipt.index'),
                    ]);
                }

                return redirect()->route('refund-receipt.index')->with('success', __('Refund receipt successfully created.'));
            } else {
                if ($request->ajax()) {
                    return response()->json(['error' => __('Permission denied.')], 403);
                }
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Refund receipt creation error: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['error' => __('An error occurred while creating the refund receipt.')], 500);
            }

            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        if (\Auth::user()->can('show invoice')) {
            $refundReceipt = RefundReceipt::with(['items.product'])->find($id);

            if (!empty($refundReceipt->created_by) == \Auth::user()->creatorId()) {
                $customer = $refundReceipt->customer;
                $iteams = $refundReceipt->items;
                $user = \Auth::user();

                $refundReceipt->customField = CustomField::getData($refundReceipt, 'invoice');
                $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())
                    ->where('module', '=', 'invoice')
                    ->get();

                return view('refundReceipt.view', compact('refundReceipt', 'customer', 'iteams', 'customFields', 'user'));
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
            $refundReceipt = RefundReceipt::find($id);

            if (!empty($refundReceipt->created_by) == \Auth::user()->creatorId()) {
                $user = \Auth::user();
                $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
                $column = $user->type == 'company' ? 'created_by' : 'owned_by';
                $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())
                    ->where('module', '=', 'invoice')
                    ->get();
                $refund_receipt_number = \Auth::user()->refundReceiptNumberFormat($refundReceipt->refund_receipt_id);
                $customers = Customer::where($column, $ownerId)->get()->pluck('name', 'id')->toArray();
                $customers = ['__add__' => '➕ Add new customer'] + ['' => 'Select Customer'] + $customers;
                $category = ProductServiceCategory::where($column, $ownerId)->where('type', 'income')->get()->pluck('name', 'id')->toArray();
                $category = ['__add__' => '➕ Add new category'] + ['' => 'Select Category'] + $category;
                $product_services = ProductService::get()->pluck('name', 'id');
                $product_services->prepend('--', '');
                $taxes = Tax::where('created_by', \Auth::user()->creatorId())->get();

                // Get bank accounts for "Refund From" dropdown
                $bankAccounts = BankAccount::where('created_by', \Auth::user()->creatorId())
                    ->get()
                    ->mapWithKeys(function ($account) {
                        $displayName = !empty($account->bank_name) ? $account->bank_name :
                            (!empty($account->institution_name) ? $account->institution_name : $account->holder_name);
                        return [$account->id => $displayName];
                    });
                $bankAccounts = ['' => 'Choose an account'] + $bankAccounts->toArray();

                // Get payment methods
                $paymentMethods = [
                    '' => 'Choose payment method',
                    'Cash' => 'Cash',
                    'Check' => 'Check',
                    'Credit Card' => 'Credit Card',
                    'Debit Card' => 'Debit Card',
                    'Bank Transfer' => 'Bank Transfer',
                    'Other' => 'Other',
                ];

                // Populate customer data
                $customerId = $refundReceipt->customer_id;
                $customerData = Customer::find($customerId);
                $billingAddress = '';
                if ($customerData) {
                    $billingAddress = $customerData->billing_name . "\n" . $customerData->billing_phone . "\n" . $customerData->billing_address . "\n" . $customerData->billing_city . ' , ' . $customerData->billing_state . ' , ' . $customerData->billing_country . '.' . "\n" . $customerData->billing_zip;
                }

                // Load refund receipt items with product details
                $refundReceipt->load(['items.product']);

                // Prepare refund receipt data for JavaScript
                $refundReceiptData = [
                    'id' => $refundReceipt->id,
                    'refund_receipt_id' => $refundReceipt->refund_receipt_id,
                    'customer_id' => $refundReceipt->customer_id,
                    'issue_date' => $refundReceipt->issue_date,
                    'ref_number' => $refundReceipt->ref_number,
                    'payment_method' => $refundReceipt->payment_method,
                    'refund_from' => $refundReceipt->refund_from,
                    'location_of_sale' => $refundReceipt->location_of_sale,
                    'billing_address' => $refundReceipt->billing_address,
                    'category_id' => $refundReceipt->category_id,
                    'subtotal' => $refundReceipt->subtotal,
                    'taxable_subtotal' => $refundReceipt->taxable_subtotal,
                    'total_discount' => $refundReceipt->total_discount,
                    'total_tax' => $refundReceipt->total_tax,
                    'sales_tax_amount' => $refundReceipt->sales_tax_amount,
                    'total_amount' => $refundReceipt->total_amount,
                    'total_amount_refunded' => $refundReceipt->total_amount_refunded,
                    'memo' => $refundReceipt->memo,
                    'statement_memo' => $refundReceipt->statement_memo,
                    'items' => $refundReceipt->items
                        ->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'type' => 'product',
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

                return view('refundReceipt.edit', compact('customers', 'refund_receipt_number', 'product_services', 'category', 'customFields', 'customerId', 'taxes', 'billingAddress', 'refundReceiptData', 'refundReceipt', 'bankAccounts', 'paymentMethods'))->with('mode', 'edit');
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
                $refundReceipt = RefundReceipt::find($id);
                if (!$refundReceipt) {
                    return redirect()->back()->with('error', __('Refund Receipt not found.'));
                }

                if ($refundReceipt->created_by != \Auth::user()->creatorId()) {
                    return redirect()->back()->with('error', __('Permission denied.'));
                }

                $validator = \Validator::make($request->all(), [
                    'customer_id' => 'required',
                    'issue_date' => 'required',
                    'items' => 'required',
                    'items_payload' => 'nullable',
                    'customer_email' => 'nullable|email',
                    'payment_method' => 'nullable|string',
                    'refund_from' => 'nullable|string',
                    'location_of_sale' => 'nullable|string',
                    'billing_address' => 'nullable|string',
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
                $oldItems = $refundReceipt->items->toArray();

                // Update Refund Receipt
                $refundReceipt->customer_id = $request->customer_id;
                $refundReceipt->customer_email = $request->customer_email;
                $refundReceipt->issue_date = $request->issue_date;
                $refundReceipt->ref_number = $request->ref_number;
                $refundReceipt->payment_method = $request->payment_method;
                $refundReceipt->refund_from = $request->refund_from;
                $refundReceipt->location_of_sale = $request->location_of_sale;
                $refundReceipt->billing_address = $request->billing_address;
                $refundReceipt->category_id = $request->category_id ?? 1;
                $refundReceipt->subtotal = $request->subtotal ?? 0;
                $refundReceipt->taxable_subtotal = $request->taxable_subtotal ?? 0;
                $refundReceipt->discount_type = $request->discount_type;
                $refundReceipt->discount_value = $request->discount_value ?? 0;
                $refundReceipt->total_discount = $request->total_discount ?? 0;
                $refundReceipt->sales_tax_rate = $request->sales_tax_rate;
                $refundReceipt->total_tax = $request->total_tax ?? 0;
                $refundReceipt->sales_tax_amount = $request->sales_tax_amount ?? 0;
                $refundReceipt->total_amount = $request->total_amount ?? 0;
                $refundReceipt->total_amount_refunded = $request->total_amount ?? 0;
                $refundReceipt->memo = $request->memo;
                $refundReceipt->statement_memo = $request->statement_memo;

                // Handle logo upload
                if ($request->hasFile('company_logo')) {
                    if ($refundReceipt->logo && \Storage::exists('uploads/refund_receipt_logos/' . $refundReceipt->logo)) {
                        \Storage::delete('uploads/refund_receipt_logos/' . $refundReceipt->logo);
                    }
                    $logoFile = $request->file('company_logo');
                    $logoName = time() . '_logo.' . $logoFile->getClientOriginalExtension();
                    $logoFile->storeAs('uploads/refund_receipt_logos', $logoName, 'public');
                    $refundReceipt->logo = $logoName;
                }

                // Handle attachments
                if ($request->hasFile('attachments')) {
                    if ($refundReceipt->attachments) {
                        $oldAttachments = json_decode($refundReceipt->attachments, true);
                        if (is_array($oldAttachments)) {
                            foreach ($oldAttachments as $oldAttachment) {
                                if (\Storage::exists('uploads/refund_receipt_attachments/' . $oldAttachment)) {
                                    \Storage::delete('uploads/refund_receipt_attachments/' . $oldAttachment);
                                }
                            }
                        }
                    }
                    $attachments = [];
                    foreach ($request->file('attachments') as $attachment) {
                        $attachmentName = time() . '_' . uniqid() . '.' . $attachment->getClientOriginalExtension();
                        $attachment->storeAs('uploads/refund_receipt_attachments', $attachmentName, 'public');
                        $attachments[] = $attachmentName;
                    }
                    $refundReceipt->attachments = json_encode($attachments);
                }

                $refundReceipt->save();

                // Save Custom Fields
                CustomField::saveData($refundReceipt, $request->customField);

                // Reverse old inventory changes (for refunds, we previously added, so now subtract)
                foreach ($oldItems as $oldItem) {
                    if ($oldItem['product_id']) {
                        \App\Models\Utility::total_quantity('minus', $oldItem['quantity'], $oldItem['product_id']);
                        $type = 'refund_receipt';
                        $type_id = $refundReceipt->id;
                        $description = $oldItem['quantity'] . ' ' . __(' quantity adjustment in refund receipt update ') . \Auth::user()->invoiceNumberFormat($refundReceipt->refund_receipt_id);
                        \App\Models\Utility::addProductStock($oldItem['product_id'], -$oldItem['quantity'], $type, $description, $type_id);
                    }
                }

                // Delete old items
                RefundReceiptProduct::where('refund_receipt_id', $refundReceipt->id)->delete();

                // Parse new items
                $products = $request->items;
                if (is_string($products)) {
                    $products = json_decode($products, true);
                }

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
                    $refundReceiptProduct = new RefundReceiptProduct();
                    $refundReceiptProduct->refund_receipt_id = $refundReceipt->id;

                    $itemType = $prod['type'] ?? 'product';

                    if ($itemType === 'product') {
                        $refundReceiptProduct->product_id = $prod['item_id'] ?? ($prod['item'] ?? null);
                        $refundReceiptProduct->quantity = $prod['quantity'] ?? 0;
                        $refundReceiptProduct->tax = $prod['tax'] ?? null;
                        $refundReceiptProduct->discount = $prod['discount'] ?? 0;
                        $refundReceiptProduct->price = $prod['price'] ?? 0;
                        $refundReceiptProduct->description = $prod['description'] ?? '';
                        $refundReceiptProduct->taxable = $prod['is_taxable'] ?? ($prod['taxable'] ?? 0);
                        $refundReceiptProduct->item_tax_price = $prod['itemTaxPrice'] ?? ($prod['item_tax_price'] ?? 0);
                        $refundReceiptProduct->item_tax_rate = $prod['itemTaxRate'] ?? ($prod['item_tax_rate'] ?? 0);
                        $refundReceiptProduct->amount = $prod['amount'] ?? 0;

                        // For refunds, ADD inventory back
                        if ($refundReceiptProduct->product_id) {
                            \App\Models\Utility::total_quantity('plus', $refundReceiptProduct->quantity, $refundReceiptProduct->product_id);

                            $type = 'refund_receipt';
                            $type_id = $refundReceipt->id;
                            $description = $refundReceiptProduct->quantity . ' ' . __(' quantity returned via refund receipt ') . \Auth::user()->invoiceNumberFormat($refundReceipt->refund_receipt_id);
                            \App\Models\Utility::addProductStock($refundReceiptProduct->product_id, $refundReceiptProduct->quantity, $type, $description, $type_id);
                        }
                    } elseif ($itemType === 'subtotal') {
                        $refundReceiptProduct->product_id = null;
                        $refundReceiptProduct->quantity = 0;
                        $refundReceiptProduct->price = 0;
                        $refundReceiptProduct->description = $prod['label'] ?? 'Subtotal';
                        $refundReceiptProduct->amount = $prod['amount'] ?? 0;
                        $refundReceiptProduct->discount = 0;
                        $refundReceiptProduct->tax = null;
                        $refundReceiptProduct->taxable = 0;
                        $refundReceiptProduct->item_tax_price = 0;
                        $refundReceiptProduct->item_tax_rate = 0;
                    } elseif ($itemType === 'text') {
                        $refundReceiptProduct->product_id = null;
                        $refundReceiptProduct->quantity = 0;
                        $refundReceiptProduct->price = 0;
                        $refundReceiptProduct->description = $prod['text'] ?? '';
                        $refundReceiptProduct->amount = 0;
                        $refundReceiptProduct->discount = 0;
                        $refundReceiptProduct->tax = null;
                        $refundReceiptProduct->taxable = 0;
                        $refundReceiptProduct->item_tax_price = 0;
                        $refundReceiptProduct->item_tax_rate = 0;
                    }

                    $refundReceiptProduct->save();
                }

                \App\Models\Utility::makeActivityLog(\Auth::user()->id, 'Refund Receipt', $refundReceipt->id, 'Update Refund Receipt', 'Refund Receipt Updated');

                \DB::commit();

                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => __('Refund receipt successfully updated.'),
                        'redirect' => route('refund-receipt.index'),
                    ]);
                }

                return redirect()->route('refund-receipt.index')->with('success', __('Refund receipt successfully updated.'));
            } else {
                if ($request->ajax()) {
                    return response()->json(['error' => __('Permission denied.')], 403);
                }
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Refund receipt update error: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['error' => __('An error occurred while updating the refund receipt.')], 500);
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
            $refundReceipt = RefundReceipt::find($id);
            if (!$refundReceipt) {
                return redirect()->back()->with('error', __('Refund Receipt not found.'));
            }

            if ($refundReceipt->created_by != \Auth::user()->creatorId()) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            // Reverse inventory changes before deletion
            foreach ($refundReceipt->items as $item) {
                if ($item->product_id) {
                    \App\Models\Utility::total_quantity('minus', $item->quantity, $item->product_id);
                }
            }

            // Delete products
            RefundReceiptProduct::where('refund_receipt_id', $refundReceipt->id)->delete();

            // Delete attachments
            if ($refundReceipt->attachments) {
                $attachments = json_decode($refundReceipt->attachments, true);
                if (is_array($attachments)) {
                    foreach ($attachments as $attachment) {
                        if (\Storage::exists('uploads/refund_receipt_attachments/' . $attachment)) {
                            \Storage::delete('uploads/refund_receipt_attachments/' . $attachment);
                        }
                    }
                }
            }

            // Delete logo
            if ($refundReceipt->logo && \Storage::exists('uploads/refund_receipt_logos/' . $refundReceipt->logo)) {
                \Storage::delete('uploads/refund_receipt_logos/' . $refundReceipt->logo);
            }

            \App\Models\Utility::makeActivityLog(\Auth::user()->id, 'Refund Receipt', $refundReceipt->id, 'Delete Refund Receipt', 'Refund Receipt Deleted');

            $refundReceipt->delete();

            return redirect()->route('refund-receipt.index')->with('success', __('Refund receipt successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
