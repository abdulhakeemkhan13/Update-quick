<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\CustomField;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\Purchase;
use App\Models\PurchaseProduct;
use App\Models\PurchasePayment;
use App\Models\StockReport;
use App\Models\Transaction;
use App\Models\JournalItem;
use App\Models\TransactionLines;
use App\Models\ChartOfAccountType;
use App\Models\ChartOfAccountSubType;
use App\Models\ChartOfAccount;
use App\Models\PurchaseOrderAccount;
use App\Models\Vender;
use App\Models\User;
use App\Models\Tax;
use App\Models\Customer;
use App\Models\Utility;
use App\Models\WarehouseProduct;
use App\Models\WarehouseTransfer;
use Illuminate\Support\Facades\Crypt;
use App\Models\warehouse;
use Dflydev\DotAccessData\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $vender = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $vender->prepend('Select Vendor', '');
        $status = Purchase::$statues;
        $purchases = Purchase::where('created_by', '=', \Auth::user()->creatorId())->with(['vender', 'category'])->get();


        return view('purchase.index', compact('purchases', 'status', 'vender'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($vendorId)
    {
        if (\Auth::user()->can('create purchase')) {
             $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

            $po_number = \Auth::user()->purchaseNumberFormat($this->purchaseNumber());
            
            $vendors = Vender::where($column, $ownerId)->get()->pluck('name', 'id')->toArray();
            $vendors = ['__add__' => '➕ Add new vendor'] + ['' => 'Select Vendor'] + $vendors;

            $product_services = ProductService::where($column, $ownerId)->get()->pluck('name', 'id');
            $product_services->prepend('Select Item', '');

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');

            // Get taxes for the form
            $taxes = Tax::where('created_by', $ownerId)->get()->pluck('name', 'id');

            // Get customers for billable items
            $customers = Customer::where($column, $ownerId)->orderBy('name')->get();

            return view('purchase.create', compact('vendors', 'po_number', 'product_services', 'vendorId', 'chartAccounts', 'taxes', 'customers'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        if (\Auth::user()->can('create purchase')) {
            $validator = \Validator::make(
                    $request->all(),
                    [
                        'vendor_id' => 'required',
                        'po_date' => 'required',
                    ]
                );
                
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'status' => 'error',
                            'message' => $messages->first()
                        ], 422);
                    }
                    return redirect()->back()->with('error', $messages->first());
                }

                // Create Purchase Order
                $po = new Purchase();
                $po->vender_id = $request->vendor_id;
                $po->purchase_date = $request->po_date;
                $po->purchase_id = $this->purchaseNumber();
                $po->status = 0;
                $po->category_id = $request->category_id;
                $po->created_by = \Auth::user()->creatorId();
                $po->expected_date = $request->expected_date;
                $po->ship_to_address = $request->ship_to_address;
                $po->terms = $request->terms;
                $po->notes = $request->notes;
                $po->ship_via = $request->ship_via;
                $po->ref_no = $request->ref_no;
                $po->ship_to = $request->ship_to;
                $po->mailing_address = $request->mailing_address;
                $po->vendor_message = $request->vendor_message;
                $po->status = 1; // Open
                $po->type = 'Purchase Order';
                
                // Financial fields
                $po->subtotal = $request->subtotal ?? 0;
                $po->tax_total = $request->tax_total ?? 0;
                $po->shipping = $request->shipping ?? 0;
                $po->total = $request->total ?? 0;
                $po->created_by = \Auth::user()->creatorId();
                $po->owned_by = \Auth::user()->ownedId();
                $po->save();

                // Process CATEGORY DETAILS (Account-based expenses)
                if ($request->has('category') && is_array($request->category)) {
                    foreach ($request->category as $index => $categoryData) {
                        // Skip empty rows
                        if (empty($categoryData['account_id']) && empty($categoryData['amount'])) {
                            continue;
                        }

                        $poAccount = new PurchaseOrderAccount();
                        $poAccount->ref_id = $po->id;
                        $poAccount->type = 'Purchase Order';
                        $poAccount->chart_account_id = $categoryData['account_id'] ?? null;
                        $poAccount->description = $categoryData['description'] ?? '';
                        $poAccount->price = $categoryData['amount'] ?? 0;
                        $poAccount->quantity_ordered = $categoryData['quantity'] ?? 1;
                        $poAccount->quantity_received = 0;
                        
                        // Handle billable and customer fields
                        $poAccount->billable = isset($categoryData['billable']) ? 1 : 0;
                        $poAccount->customer_id = $categoryData['customer_id'] ?? null;
                        
                        // Handle tax checkbox
                        $poAccount->tax = isset($categoryData['tax']) ? 1 : 0;
                        
                        // Save order to maintain exact row position
                        $poAccount->order = $index;
                        
                        $poAccount->save();
                    }
                }
                // Process ITEM DETAILS (Product/Service-based)
                if ($request->has('items') && is_array($request->items)) {
                    foreach ($request->items as $index => $itemData) {
                        // Skip empty rows
                        if (empty($itemData['product_id']) && empty($itemData['quantity']) && empty($itemData['price'])) {
                            continue;
                        }
                        
                        $product = ProductService::find($itemData['product_id']);
                        $account = null;
                        if ($product) {
                            if($product->type == 'product'){
                                $account = $product->asset_chartaccount_id ?? $product->expense_chartaccount_id;
                            }else{
                                $account = $product->expense_chartaccount_id;
                            }
                        }

                        $poItem = new PurchaseProduct();
                        $poItem->purchase_id = $po->id;
                        $poItem->product_id = $itemData['product_id'] ?? null;
                        $poItem->description = $itemData['description'] ?? '';
                        $poItem->quantity = $itemData['quantity'] ?? 1;
                        $poItem->price = $itemData['price'] ?? 0;
                        $poItem->discount = $itemData['discount'] ?? 0;
                        $poItem->account_id = $account;
                        
                        // Handle tax checkbox
                        $poItem->tax = isset($itemData['tax']) ? 1 : 0;
                        
                        // Calculate line total
                        $poItem->line_total = $itemData['amount'] ?? ($poItem->quantity_ordered * $poItem->price);
                        
                        // Billable and customer
                        $poItem->billable = isset($itemData['billable']) ? 1 : 0;
                        $poItem->customer_id = $itemData['customer_id'] ?? null;
                        
                        // Save order to maintain exact row position
                        $poItem->order = $index;
                        
                        $poItem->save();
                    }
                }

                // Notifications
                $setting = Utility::settings(\Auth::user()->creatorId());
                $vendor = Vender::find($request->vendor_id);
                
                $poNotificationArr = [
                    'po_number' => \Auth::user()->purchaseNumberFormat($po->purchase_number),
                    'user_name' => \Auth::user()->name,
                    'po_date' => $po->purchase_date,
                    'expected_date' => $po->expected_date,
                    'vendor_name' => $vendor->name,
                ];

                // Slack, Telegram, Twilio notifications (if configured)
                if (isset($setting['po_notification']) && $setting['po_notification'] == 1) {
                    Utility::send_slack_msg('new_po', $poNotificationArr);
                }
                
                Utility::makeActivityLog(\Auth::user()->id, 'Purchase Order', $po->id, 'Create Purchase Order', 'Purchase Order Created');
                
                \DB::commit();
                
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'status' => 'success',
                        'message' => __('Purchase Order successfully created.'),
                        'po_id' => $po->id
                    ], 200);
                }
                
                return redirect()->route('purchase.index')->with('success', __('Purchase Order successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function show($ids)
    {

        if (\Auth::user()->can('show purchase')) {
            try {
                $id       = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Purchase Not Found.'));
            }

            $id   = Crypt::decrypt($ids);
            $purchase = Purchase::find($id);

            if ($purchase->created_by == \Auth::user()->creatorId()) {

                $purchasePayment = PurchasePayment::where('purchase_id', $purchase->id)->first();
                $vendor      = $purchase->vender;
                $iteams      = $purchase->items;

                return view('purchase.view', compact('purchase', 'vendor', 'iteams', 'purchasePayment'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit($idsd)
    {
        if (\Auth::user()->can('edit purchase')) {
            try {
                $id = Crypt::decrypt($idsd);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Purchase Not Found.'));
            }

            $purchase = Purchase::with(['items', 'accounts'])->findOrFail($id);
            
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

            $vendors = Vender::where($column, $ownerId)->get()->pluck('name', 'id')->toArray();
            $vendors = ['__add__' => '➕ Add new vendor'] + ['' => 'Select Vendor'] + $vendors;

            $product_services = ProductService::where($column, $ownerId)->get()->pluck('name', 'id');
            $product_services->prepend('Select Item', '');

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');

            // Get taxes for the form
            $taxes = Tax::where('created_by', $ownerId)->get()->pluck('name', 'id');

            // Get customers for billable items
            $customers = Customer::where($column, $ownerId)->orderBy('name')->get();
            $statuses = Purchase::$statues;

            return view('purchase.edit', compact('purchase', 'vendors', 'product_services', 'chartAccounts', 'taxes', 'customers', 'statuses'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, Purchase $purchase)
    {
        if(\Auth::user()->can('edit purchase'))
        {

            $validator = \Validator::make(
                    $request->all(),
                    [
                        'vendor_id' => 'required',
                        'po_date' => 'required',
                        ]
                    );
                    
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }
                $po = Purchase::find($purchase->id);
                // Update PO header
                $po->vender_id = $request->vendor_id;
                $po->purchase_date = $request->po_date;
                $po->expected_date = $request->expected_date;
                $po->ship_to_address = $request->ship_to_address;
                $po->terms = $request->terms;
                $po->notes = $request->notes;
                $po->vendor_message = $request->vendor_message;
                $po->subtotal = $request->subtotal ?? 0;
                $po->tax_total = $request->tax_total ?? 0;
                $po->shipping = $request->shipping ?? 0;
                $po->total = $request->total ?? 0;
                $po->ship_via = $request->ship_via;
                $po->ref_no = $request->ref_no;
                $po->ship_to = $request->ship_to;
                $po->mailing_address = $request->mailing_address;
                $po->save();

                // Delete old items and accounts, then recreate
                PurchaseProduct::where('purchase_id', $po->id)->delete();
                PurchaseOrderAccount::where('ref_id', $po->id)->delete();

                // Process CATEGORY DETAILS (Account-based expenses)
                if ($request->has('category') && is_array($request->category)) {
                    foreach ($request->category as $index => $categoryData) {
                        if (empty($categoryData['account_id']) && empty($categoryData['amount'])) {
                            continue;
                        }

                        $poAccount = new PurchaseOrderAccount();
                        $poAccount->ref_id = $po->id;
                        $poAccount->type = 'Purchase Order';
                        $poAccount->chart_account_id = $categoryData['account_id'] ?? null;
                        $poAccount->description = $categoryData['description'] ?? '';
                        $poAccount->price = $categoryData['amount'] ?? 0;
                        $poAccount->quantity_ordered = $categoryData['quantity'] ?? 1;
                        $poAccount->quantity_received = 0;
                        $poAccount->billable = isset($categoryData['billable']) ? 1 : 0;
                        $poAccount->customer_id = $categoryData['customer_id'] ?? null;
                        $poAccount->tax = isset($categoryData['tax']) ? 1 : 0;
                        $poAccount->order = $index;
                        $poAccount->save();
                    }
                }

                // Process ITEM DETAILS
                if ($request->has('items') && is_array($request->items)) {
                    foreach ($request->items as $index => $itemData) {
                        if (empty($itemData['product_id']) && empty($itemData['quantity']) && empty($itemData['price'])) {
                            continue;
                        }
                        
                        $product = ProductService::find($itemData['product_id']);
                        $account = null;
                        if ($product) {
                            if($product->type == 'product'){
                                $account = $product->asset_chartaccount_id ?? $product->expense_chartaccount_id;
                            }else{
                                $account = $product->expense_chartaccount_id;
                            }
                        }

                        $poItem = new PurchaseProduct();
                        $poItem->purchase_id = $po->id;
                        $poItem->product_id = $itemData['product_id'] ?? null;
                        $poItem->description = $itemData['description'] ?? '';
                        $poItem->quantity = $itemData['quantity'] ?? 1;
                        $poItem->price = $itemData['price'] ?? 0;
                        $poItem->discount = $itemData['discount'] ?? 0;
                        $poItem->account_id = $account;
                        $poItem->tax = isset($itemData['tax']) ? 1 : 0;
                        $poItem->line_total = $itemData['amount'] ?? ($poItem->quantity * $poItem->price);
                        $poItem->billable = isset($itemData['billable']) ? 1 : 0;
                        $poItem->customer_id = $itemData['customer_id'] ?? null;
                        $poItem->order = $index;
                        $poItem->save();
                    }
                }

                Utility::makeActivityLog(\Auth::user()->id, 'Purchase Order', $po->id, 'Update Purchase Order', 'Purchase Order Updated');
                
                \DB::commit();
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'status' => 'success',
                        'message' => __('Purchase Order successfully created.'),
                        'po_id' => $po->id
                    ], 200);
                }

                

                return redirect()->route('purchase-order.index')->with('success', __('Purchase Order successfully updated.'));
     
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function destroy(Purchase $purchase)
    {
        \DB::beginTransaction();
        try {
            if (!\Auth::user()->can('delete purchase')) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            if ($purchase->created_by != \Auth::user()->creatorId()) {
                return redirect()->back()->with('error', __('Permission denied.'));
            }

            \Log::info('Deleting purchase order: ' . $purchase->id);

            // Delete purchase payments
            $purchasepayments = $purchase->payments;
            foreach ($purchasepayments as $pay) {
                \Log::info('Deleting purchase payment: ' . $pay->id);
                $pay->delete();
            }

            // Delete purchase products (items)
            $purchase_products = PurchaseProduct::where('purchase_id', $purchase->id)->get();
            foreach ($purchase_products as $pp) {
                \Log::info('Deleting purchase product: ' . $pp->id);
                
                // Update product quantity if needed
                if ($pp->product_id) {
                    $product_qty = \App\Models\ProductService::find($pp->product_id);
                    if ($product_qty) {
                        $product_qty->quantity = max(0, $product_qty->quantity - $pp->quantity);
                        $product_qty->save();
                    }
                    
                    // Update global utility quantity tracking
                    if (class_exists(\App\Models\Utility::class)) {
                        \App\Models\Utility::total_quantity('minus', $pp->quantity, $pp->product_id);
                    }
                }
                
                $pp->delete();
            }

            // Delete purchase order accounts (category-based expenses)
            \App\Models\PurchaseOrderAccount::where('ref_id', $purchase->id)
                ->where('type', 'Purchase Order')
                ->delete();

            $purchaseId = $purchase->id;
            
            // Activity log
            \App\Models\Utility::makeActivityLog(
                \Auth::user()->id,
                'Purchase Order',
                $purchaseId,
                'Delete Purchase Order',
                'Purchase Order #' . \Auth::user()->purchaseNumberFormat($purchase->purchase_id)
            );

            // Delete the purchase order itself
            $purchase->delete();

            \DB::commit();
            return redirect()->route('purchase.index')->with('success', __('Purchase Order successfully deleted.'));
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error deleting purchase order: ' . $e->getMessage());
            return redirect()->back()->with('error', __('Error deleting purchase order: ') . $e->getMessage());
        }
    }
    /*******  4e213669-1ac8-4e09-b395-0727f13bf036  *******/


    function purchaseNumber()
    {
        $latest = Purchase::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->purchase_id + 1;
    }
    public function sent($id)
    {
        if (\Auth::user()->can('send purchase')) {
            $purchase            = Purchase::where('id', $id)->first();
            $purchase->send_date = date('Y-m-d');
            $purchase->status    = 1;
            $purchase->save();

            $vender = Vender::where('id', $purchase->vender_id)->first();

            $purchase->name = !empty($vender) ? $vender->name : '';
            $purchase->purchase = \Auth::user()->purchaseNumberFormat($purchase->purchase_id);

            $purchaseId    = Crypt::encrypt($purchase->id);
            $purchase->url = route('purchase.pdf', $purchaseId);

            Utility::userBalance('vendor', $vender->id, $purchase->getTotal(), 'credit');

            $vendorArr = [
                'vender_bill_name' => $purchase->name,
                'vender_bill_number' => $purchase->purchase,
                'vender_bill_url' => $purchase->url,

            ];
            $resp = \App\Models\Utility::sendEmailTemplate('vender_bill_sent', [$vender->id => $vender->email], $vendorArr);

            return redirect()->back()->with('success', __('Purchase successfully sent.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function resent($id)
    {

        if (\Auth::user()->can('send purchase')) {
            $purchase = Purchase::where('id', $id)->first();

            $vender = Vender::where('id', $purchase->vender_id)->first();

            $purchase->name = !empty($vender) ? $vender->name : '';
            $purchase->purchase = \Auth::user()->purchaseNumberFormat($purchase->purchase_id);

            $purchaseId    = Crypt::encrypt($purchase->id);
            $purchase->url = route('purchase.pdf', $purchaseId);
            //

            // Send Email
            //        $setings = Utility::settings();
            //
            //        if($setings['bill_resend'] == 1)
            //        {
            //            $bill = Bill::where('id', $id)->first();
            //            $vender = Vender::where('id', $bill->vender_id)->first();
            //            $bill->name = !empty($vender) ? $vender->name : '';
            //            $bill->bill = \Auth::user()->billNumberFormat($bill->bill_id);
            //            $billId    = Crypt::encrypt($bill->id);
            //            $bill->url = route('bill.pdf', $billId);
            //            $billResendArr = [
            //                'vender_name'   => $vender->name,
            //                'vender_email'  => $vender->email,
            //                'bill_name'  => $bill->name,
            //                'bill_number'   => $bill->bill,
            //                'bill_url' =>$bill->url,
            //            ];
            //
            //            $resp = Utility::sendEmailTemplate('bill_resend', [$vender->id => $vender->email], $billResendArr);
            //
            //
            //        }
            //
            //        return redirect()->back()->with('success', __('Bill successfully sent.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            //
            return redirect()->back()->with('success', __('Bill successfully sent.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function purchase($purchase_id)
    {

        $settings = Utility::settings();
        $purchaseId   = Crypt::decrypt($purchase_id);

        $purchase  = Purchase::where('id', $purchaseId)->first();
        $data  = DB::table('settings');
        $data  = $data->where('created_by', '=', $purchase->created_by);
        $data1 = $data->get();

        foreach ($data1 as $row) {
            $settings[$row->name] = $row->value;
        }

        $vendor = $purchase->vender;

        $totalTaxPrice = 0;
        $totalQuantity = 0;
        $totalRate     = 0;
        $totalDiscount = 0;
        $taxesData     = [];
        $items         = [];

        foreach ($purchase->items as $product) {

            $item              = new \stdClass();
            $item->name        = !empty($product->product) ? $product->product->name : '';
            $item->quantity    = $product->quantity;
            $item->tax         = $product->tax;
            $item->discount    = $product->discount;
            $item->price       = $product->price;
            $item->description = $product->description;

            $totalQuantity += $item->quantity;
            $totalRate     += $item->price;
            $totalDiscount += $item->discount;

            $taxes     = Utility::tax($product->tax);
            $itemTaxes = [];
            if (!empty($item->tax)) {
                foreach ($taxes as $tax) {
                    $taxPrice      = Utility::taxRate($tax->rate, $item->price, $item->quantity, $item->discount);
                    $totalTaxPrice += $taxPrice;

                    $itemTax['name']  = $tax->name;
                    $itemTax['rate']  = $tax->rate . '%';
                    $itemTax['price'] = Utility::priceFormat($settings, $taxPrice);
                    $itemTax['tax_price'] = $taxPrice;
                    $itemTaxes[]      = $itemTax;


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

        $purchase->itemData      = $items;
        $purchase->totalTaxPrice = $totalTaxPrice;
        $purchase->totalQuantity = $totalQuantity;
        $purchase->totalRate     = $totalRate;
        $purchase->totalDiscount = $totalDiscount;
        $purchase->taxesData     = $taxesData;


        //        $logo         = asset(Storage::url('uploads/logo/'));
        //        $company_logo = Utility::getValByName('company_logo_dark');
        //        $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));

        $logo         = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $purchase_logo = Utility::getValByName('purchase_logo');
        if (isset($purchase_logo) && !empty($purchase_logo)) {
            $img = Utility::get_file('purchase_logo/') . $purchase_logo;
        } else {
            $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        if ($purchase) {
            $color      = '#' . $settings['purchase_color'];
            $font_color = Utility::getFontColor($color);

            return view('purchase.templates.' . $settings['purchase_template'], compact('purchase', 'color', 'settings', 'vendor', 'img', 'font_color'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function previewPurchase($template, $color)
    {
        $objUser  = \Auth::user();
        $settings = Utility::settings();
        $purchase     = new Purchase();

        $vendor                   = new \stdClass();
        $vendor->email            = '<Email>';
        $vendor->shipping_name    = '<Vendor Name>';
        $vendor->shipping_country = '<Country>';
        $vendor->shipping_state   = '<State>';
        $vendor->shipping_city    = '<City>';
        $vendor->shipping_phone   = '<Vendor Phone Number>';
        $vendor->shipping_zip     = '<Zip>';
        $vendor->shipping_address = '<Address>';
        $vendor->billing_name     = '<Vendor Name>';
        $vendor->billing_country  = '<Country>';
        $vendor->billing_state    = '<State>';
        $vendor->billing_city     = '<City>';
        $vendor->billing_phone    = '<Vendor Phone Number>';
        $vendor->billing_zip      = '<Zip>';
        $vendor->billing_address  = '<Address>';

        $totalTaxPrice = 0;
        $taxesData     = [];
        $items         = [];
        for ($i = 1; $i <= 3; $i++) {
            $item           = new \stdClass();
            $item->name     = 'Item ' . $i;
            $item->quantity = 1;
            $item->tax      = 5;
            $item->discount = 50;
            $item->price    = 100;

            $taxes = [
                'Tax 1',
                'Tax 2',
            ];

            $itemTaxes = [];
            foreach ($taxes as $k => $tax) {
                $taxPrice         = 10;
                $totalTaxPrice    += $taxPrice;
                $itemTax['name']  = 'Tax ' . $k;
                $itemTax['rate']  = '10 %';
                $itemTax['price'] = '$10';
                $itemTax['tax_price'] = 10;
                $itemTaxes[]      = $itemTax;
                if (array_key_exists('Tax ' . $k, $taxesData)) {
                    $taxesData['Tax ' . $k] = $taxesData['Tax 1'] + $taxPrice;
                } else {
                    $taxesData['Tax ' . $k] = $taxPrice;
                }
            }
            $item->itemTax = $itemTaxes;
            $items[]       = $item;
        }

        $purchase->purchase_id    = 1;
        $purchase->issue_date = date('Y-m-d H:i:s');
        //        $purchase->due_date   = date('Y-m-d H:i:s');
        $purchase->itemData   = $items;

        $purchase->totalTaxPrice = 60;
        $purchase->totalQuantity = 3;
        $purchase->totalRate     = 300;
        $purchase->totalDiscount = 10;
        $purchase->taxesData     = $taxesData;
        $purchase->created_by     = $objUser->creatorId();

        $preview      = 1;
        $color        = '#' . $color;
        $font_color   = Utility::getFontColor($color);

        $logo         = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $settings_data = \App\Models\Utility::settingsById($purchase->created_by);
        $purchase_logo = $settings_data['purchase_logo'];

        if (isset($purchase_logo) && !empty($purchase_logo)) {
            $img = Utility::get_file('purchase_logo/') . $purchase_logo;
        } else {
            $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }


        return view('purchase.templates.' . $template, compact('purchase', 'preview', 'color', 'img', 'settings', 'vendor', 'font_color'));
    }

    public function savePurchaseTemplateSettings(Request $request)
    {

        $post = $request->all();
        unset($post['_token']);

        if (isset($post['purchase_template']) && (!isset($post['purchase_color']) || empty($post['purchase_color']))) {
            $post['purchase_color'] = "ffffff";
        }


        if ($request->purchase_logo) {
            $dir = 'purchase_logo/';
            $purchase_logo = \Auth::user()->id . '_purchase_logo.png';
            $validation = [
                'mimes:' . 'png',
                'max:' . '20480',
            ];
            $path = Utility::upload_file($request, 'purchase_logo', $purchase_logo, $dir, $validation);
            if ($path['flag'] == 0) {
                return redirect()->back()->with('error', __($path['msg']));
            }
            $post['purchase_logo'] = $purchase_logo;
        }


        foreach ($post as $key => $data) {
            \DB::insert(
                'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                [
                    $data,
                    $key,
                    \Auth::user()->creatorId(),
                ]
            );
        }

        return redirect()->back()->with('success', __('Purchase Setting updated successfully'));
    }

    public function items(Request $request)
    {

        $items = PurchaseProduct::where('purchase_id', $request->purchase_id)->where('product_id', $request->product_id)->first();

        return json_encode($items);
    }

    public function purchaseLink($purchaseId)
    {
        try {
            $id       = Crypt::decrypt($purchaseId);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Purchase Not Found.'));
        }

        $id             = Crypt::decrypt($purchaseId);
        $purchase       = Purchase::find($id);

        if (!empty($purchase)) {
            $user_id        = $purchase->created_by;
            $user           = User::find($user_id);
            $purchasePayment = PurchasePayment::where('purchase_id', $purchase->id)->first();
            $vendor = $purchase->vender;
            $iteams   = $purchase->items;

            return view('purchase.customer_bill', compact('purchase', 'vendor', 'iteams', 'purchasePayment', 'user'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function payment($purchase_id)
    {
        if (\Auth::user()->can('create payment purchase')) {
            $purchase    = Purchase::where('id', $purchase_id)->first();
            $venders = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            $categories = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $accounts   = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            return view('purchase.payment', compact('venders', 'categories', 'accounts', 'purchase'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function createPayment(Request $request, $purchase_id)
    {
        if (\Auth::user()->can('create payment purchase')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'date' => 'required',
                    'amount' => 'required',
                    'account_id' => 'required',

                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $purchasePayment                 = new PurchasePayment();
            $purchasePayment->purchase_id        = $purchase_id;
            $purchasePayment->date           = $request->date;
            $purchasePayment->amount         = $request->amount;
            $purchasePayment->account_id     = $request->account_id;
            $purchasePayment->payment_method = 0;
            $purchasePayment->reference      = $request->reference;
            $purchasePayment->description    = $request->description;
            if (!empty($request->add_receipt)) {
                $fileName = time() . "_" . $request->add_receipt->getClientOriginalName();
                $request->add_receipt->storeAs('uploads/payment', $fileName);
                $purchasePayment->add_receipt = $fileName;
            }
            $purchasePayment->save();

            $purchase  = Purchase::where('id', $purchase_id)->first();
            $due   = $purchase->getDue();
            $total = $purchase->getTotal();

            if ($purchase->status == 0) {
                $purchase->send_date = date('Y-m-d');
                $purchase->save();
            }

            if ($due <= 0) {
                $purchase->status = 4;
                $purchase->save();
            } else {
                $purchase->status = 3;
                $purchase->save();
            }
            $purchasePayment->user_id    = $purchase->vender_id;
            $purchasePayment->user_type  = 'Vender';
            $purchasePayment->type       = 'Partial';
            $purchasePayment->created_by = \Auth::user()->id;
            $purchasePayment->payment_id = $purchasePayment->id;
            $purchasePayment->category   = 'Bill';
            $purchasePayment->account    = $request->account_id;
            Transaction::addTransaction($purchasePayment);

            $vender = Vender::where('id', $purchase->vender_id)->first();

            $payment         = new PurchasePayment();
            $payment->name   = $vender['name'];
            $payment->method = '-';
            $payment->date   = \Auth::user()->dateFormat($request->date);
            $payment->amount = \Auth::user()->priceFormat($request->amount);
            $payment->bill   = 'bill ' . \Auth::user()->purchaseNumberFormat($purchasePayment->purchase_id);

            Utility::userBalance('vendor', $purchase->vender_id, $request->amount, 'debit');

            Utility::bankAccountBalance($request->account_id, $request->amount, 'debit');

            // Send Email
            $setings = Utility::settings();
            if ($setings['new_bill_payment'] == 1) {

                $vender = Vender::where('id', $purchase->vender_id)->first();
                $billPaymentArr = [
                    'vender_name'   => $vender->name,
                    'vender_email'  => $vender->email,
                    'payment_name'  => $payment->name,
                    'payment_amount' => $payment->amount,
                    'payment_bill'  => $payment->bill,
                    'payment_date'  => $payment->date,
                    'payment_method' => $payment->method,
                    'company_name' => $payment->method,

                ];


                $resp = Utility::sendEmailTemplate('new_bill_payment', [$vender->id => $vender->email], $billPaymentArr);

                return redirect()->back()->with('success', __('Payment successfully added.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            }

            return redirect()->back()->with('success', __('Payment successfully added.'));
        }
    }

    public function paymentDestroy(Request $request, $purchase_id, $payment_id)
    {

        if (\Auth::user()->can('delete payment purchase')) {
            $payment = PurchasePayment::find($payment_id);
            PurchasePayment::where('id', '=', $payment_id)->delete();

            $purchase = Purchase::where('id', $purchase_id)->first();

            $due   = $purchase->getDue();
            $total = $purchase->getTotal();

            if ($due > 0 && $total != $due) {
                $purchase->status = 3;
            } else {
                $purchase->status = 2;
            }

            Utility::userBalance('vendor', $purchase->vender_id, $payment->amount, 'credit');
            Utility::bankAccountBalance($payment->account_id, $payment->amount, 'credit');

            $purchase->save();
            $type = 'Partial';
            $user = 'Vender';
            Transaction::destroyTransaction($payment_id, $type, $user);

            return redirect()->back()->with('success', __('Payment successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function vender(Request $request)
    {
        $vender = Vender::where('id', '=', $request->id)->first();

        return view('purchase.vender_detail', compact('vender'));
    }
    public function product(Request $request)
    {
        $data['product']     = $product = ProductService::find($request->product_id);
        $data['unit']        = !empty($product->unit) ? $product->unit->name : '';
        $data['taxRate']     = $taxRate = !empty($product->tax_id) ? $product->taxRate($product->tax_id) : 0;
        $data['taxes']       = !empty($product->tax_id) ? $product->tax($product->tax_id) : 0;
        $salePrice           = $product->purchase_price;
        $quantity            = 1;
        $taxPrice            = ($taxRate / 100) * ($salePrice * $quantity);
        $data['totalAmount'] = ($salePrice * $quantity);

        return json_encode($data);
    }

    public function productDestroy(Request $request)
    { 
        \DB::beginTransaction();
        try {
            if (\Auth::user()->can('delete purchase')) {

                $purchaseProduct = PurchaseProduct::find($request->id);

                if ($purchaseProduct) {

                    // Get the purchase tied to this line item
                    $purchase = Purchase::find($purchaseProduct->purchase_id);

                    // Adjust warehouse stock (reverse the incoming purchase qty)
                    if ($purchase) {
                        $warehouse_id = $purchase->warehouse_id ?? null;

                        if ($warehouse_id) {
                            $ware_pro = WarehouseProduct::where('warehouse_id', $warehouse_id)
                                ->where('product_id', $purchaseProduct->product_id)
                                ->first();

                            if ($ware_pro) {
                                $qty = (float) $ware_pro->quantity;
                                $lineQty = (float) $purchaseProduct->quantity;

                                if ($lineQty >= $qty) {
                                    // Removing this line wipes the product from this warehouse
                                    $ware_pro->delete();
                                } else {
                                    // Decrease available stock by the line quantity
                                    $ware_pro->quantity = $qty - $lineQty;
                                    $ware_pro->save();
                                }
                            }
                        }
                    }


                    $prod_id = TransactionLines::where('reference_id', $purchase->voucher_id ?? null)
                        ->where('product_item_id', $purchaseProduct->id)
                        ->where('reference', 'Purchase Journal')
                        ->where('product_type', 'Purchase')
                        ->first();

                    $prod_tax = TransactionLines::where('reference_id', $purchase->voucher_id ?? null)
                        ->where('product_item_id', $purchaseProduct->id)
                        ->where('reference', 'Purchase Journal')
                        ->where('product_type', 'Purchase Tax')
                        ->first();

                    $inv_receviable = TransactionLines::where('reference_id', $purchase->voucher_id ?? null)
                        ->where('reference', 'Purchase Journal')
                        ->where('product_type', 'Purchase Payable')
                        ->first();
                    if ($inv_receviable && $prod_id) {
                        $inv_receviable->credit = (float) $inv_receviable->credit - ((float) $prod_id->debit + (float) (@$prod_tax->debit ?? 0));
                        $inv_receviable->save();
                    }

                    @$prod_id?->delete();
                    if ($prod_tax) {
                        @$prod_tax?->delete();
                    }

                    TransactionLines::where('reference_sub_id', $purchaseProduct->id)
                        ->where('reference', 'Purchase')
                        ->delete();

                    // Journal items
                    $journal_item = JournalItem::where('journal', $purchase->voucher_id ?? null)
                        ->where('product_ids', $purchaseProduct->id ?? null)
                        ->first();

                    $journal_tax = JournalItem::where('jou
                    rnal', $purchase->voucher_id ?? null)
                        ->where('prod_tax_id', $purchaseProduct->id ?? null)
                        ->first();

                    $types = ChartOfAccountType::where('created_by', '=', $purchase->created_by ?? null)
                        ->where('name', 'Liabilities')
                        ->first();

                    $account = null;
                    if ($types) {
                        $sub_type = ChartOfAccountSubType::where('type', $types->id)
                            ->where('name', 'Current Liabilities')
                            ->first();

                        if ($sub_type) {
                            $account = ChartOfAccount::where('type', $types->id)
                                ->where('sub_type', $sub_type->id)
                                ->where('name', 'Account Payable')
                                ->first();
                        }
                    }
                    if ($account) {
                        $item_last = JournalItem::where('journal', $purchase->voucher_id ?? null)
                            ->where('account', $account->id)
                            ->first();

                        if ($item_last && $journal_item) {
                            $item_last->credit = (float) $item_last->credit - ((float) $journal_item->debit + (float) (@$journal_tax->debit ?? 0));
                            $item_last->save();
                        }
                    } else {
                        // Fallback to the line referenced by transaction line's reference_sub_id
                        if ($inv_receviable && $journal_item) {
                            $item_last = JournalItem::where('journal', $purchase->voucher_id ?? null)
                                ->where('id', $inv_receviable->reference_sub_id)
                                ->first();

                            if ($item_last) {
                                $item_last->credit = (float) $item_last->credit - ((float) $journal_item->debit + (float) (@$journal_tax->debit ?? 0));
                                $item_last->save();
                            }
                        }
                    }
                    @$journal_item?->delete();
                    if ($journal_tax) {
                        @$journal_tax?->delete();
                    }

                    PurchaseProduct::where('id', '=', $request->id)->delete();

                    Utility::makeActivityLog(
                        \Auth::user()->id,
                        'Purchase Product',
                        $purchaseProduct->id,
                        'Delete Purchase Product',
                        @$purchaseProduct->product->name
                    );
                }

                \DB::commit();
                return redirect()->back()->with('success', __('Purchase product successfully deleted.'));
            } else {
                \DB::rollBack();
                
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            dd($e); // Uncomment for debugging
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }
}
