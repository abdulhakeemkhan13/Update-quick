<?php

namespace App\Http\Controllers;

use App\Models\VendorCredit;
use App\Models\VendorCreditAccount;
use App\Models\VendorCreditProduct;
use App\Models\Vender;
use App\Models\Customer;
use App\Models\ChartOfAccount;
use App\Models\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorCreditController extends Controller
{
    /**
     * Display a listing of vendor credits.
     */
    public function index()
    {
        $vendorCredits = VendorCredit::with('vendor')
            ->where('created_by', Auth::user()->creatorId())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('expense.vendor-credits-index', compact('vendorCredits'));
    }

    /**
     * Show the form for creating a new vendor credit.
     */
    public function create($vendorId = 0)
    {
        $venders = Vender::where('created_by', Auth::user()->creatorId())
            ->pluck('name', 'id');

        $customers = Customer::where('created_by', Auth::user()->creatorId())
            ->pluck('name', 'id');

        $chartAccounts = ChartOfAccount::where('created_by', Auth::user()->creatorId())
            ->pluck('name', 'id');

        $product_services = ProductService::where('created_by', Auth::user()->creatorId())
            ->pluck('name', 'id');

        $Id = $vendorId;

        return view('expense.vendor-credits-create', compact(
            'venders',
            'customers',
            'chartAccounts',
            'product_services',
            'Id'
        ));
    }

    /**
     * Store a newly created vendor credit in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'vender_id' => 'required|exists:venders,id',
            'date' => 'required|date',
        ]);

        // Calculate total from category and item lines
        $categoryTotal = 0;
        $itemTotal = 0;

        // Form uses 'categories' (repeater list name)
        if ($request->has('categories')) {
            foreach ($request->categories as $line) {
                if (!empty($line['account_id']) && isset($line['amount'])) {
                    $categoryTotal += floatval($line['amount'] ?? 0);
                }
            }
        }

        if ($request->has('items')) {
            foreach ($request->items as $item) {
                if (!empty($item['item_id'])) {
                    $qty = floatval($item['quantity'] ?? 1);
                    $rate = floatval($item['rate'] ?? 0);
                    $itemTotal += $qty * $rate;
                }
            }
        }

        $totalAmount = $categoryTotal + $itemTotal;

        // Create vendor credit
        $vendorCredit = VendorCredit::create([
            'vendor_credit_id' => $request->ref_no ?: VendorCredit::generateCreditNumber(),
            'vender_id' => $request->vender_id,
            'date' => $request->date,
            'amount' => $totalAmount,
            'memo' => $request->memo,
            'created_by' => Auth::user()->creatorId(),
            'owned_by' => Auth::user()->ownedId(),
        ]);

        // Save category lines (account-based expenses)
        if ($request->has('categories')) {
            foreach ($request->categories as $line) {
                if (!empty($line['account_id'])) {
                    VendorCreditAccount::create([
                        'vendor_credit_id' => $vendorCredit->id,
                        'chart_account_id' => $line['account_id'],
                        'price' => floatval($line['amount'] ?? 0),
                        'description' => $line['description'] ?? null,
                        'tax' => isset($line['tax']) ? 1 : 0,
                        'billable' => isset($line['billable']) ? 1 : 0,
                        'customer_id' => !empty($line['customer_id']) ? $line['customer_id'] : null,
                    ]);
                }
            }
        }

        // Save item lines (product-based expenses)
        if ($request->has('items')) {
            foreach ($request->items as $item) {
                if (!empty($item['item_id'])) {
                    $qty = floatval($item['quantity'] ?? 1);
                    $rate = floatval($item['rate'] ?? 0);
                    
                    VendorCreditProduct::create([
                        'vendor_credit_id' => $vendorCredit->id,
                        'product_id' => $item['item_id'],
                        'quantity' => $qty,
                        'price' => $rate,
                        'description' => $item['description'] ?? null,
                        'tax' => isset($item['tax_id']) ? 1 : 0,
                        'billable' => isset($item['billable']) ? 1 : 0,
                        'customer_id' => !empty($item['customer_id']) ? $item['customer_id'] : null,
                    ]);
                }
            }
        }

        // Handle AJAX request
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('Vendor Credit created successfully'),
                'redirect' => route('expense.index'),
            ]);
        }

        return redirect()->route('expense.index')->with('success', __('Vendor Credit created successfully'));
    }

    /**
     * Display the specified vendor credit.
     */
    public function show(VendorCredit $vendorCredit)
    {
        $vendorCredit->load(['vendor', 'accounts', 'products']);
        return view('expense.vendor-credits-show', compact('vendorCredit'));
    }

    /**
     * Show the form for editing the specified vendor credit.
     */
    public function edit(VendorCredit $vendorCredit)
    {
        $venders = Vender::where('created_by', Auth::user()->creatorId())
            ->pluck('name', 'id');

        $customers = Customer::where('created_by', Auth::user()->creatorId())
            ->pluck('name', 'id');

        $chartAccounts = ChartOfAccount::where('created_by', Auth::user()->creatorId())
            ->pluck('name', 'id');

        $product_services = ProductService::where('created_by', Auth::user()->creatorId())
            ->pluck('name', 'id');

        // Load related data
        $vendorCredit->load(['accounts', 'products']);

        // Format category accounts data for the view
        $categoriesAccountData = [];
        if ($vendorCredit->accounts && $vendorCredit->accounts->count() > 0) {
            foreach ($vendorCredit->accounts as $account) {
                $categoriesAccountData[] = [
                    'id' => $account->id,
                    'chart_account_id' => $account->chart_account_id,
                    'account_id' => $account->chart_account_id,
                    'description' => $account->description,
                    'amount' => $account->price,
                    'billable' => $account->billable,
                    'tax' => $account->tax,
                    'customer_id' => $account->customer_id,
                ];
            }
        }

        // Format items/products data for the view
        $items = [];
        if ($vendorCredit->products && $vendorCredit->products->count() > 0) {
            foreach ($vendorCredit->products as $product) {
                $lineTotal = floatval($product->quantity) * floatval($product->price);
                $items[] = [
                    'id' => $product->id,
                    'product_id' => $product->product_id,
                    'item_id' => $product->product_id,
                    'description' => $product->description,
                    'quantity' => $product->quantity,
                    'price' => $product->price,
                    'line_total' => $lineTotal,
                    'billable' => $product->billable,
                    'tax' => $product->tax,
                    'customer_id' => $product->customer_id,
                ];
            }
        }

        // Build vendor address
        $vendorAddress = '';
        if ($vendorCredit->vendor) {
            $vendor = $vendorCredit->vendor;
            if ($vendor->name) $vendorAddress .= $vendor->name . "\n";
            if ($vendor->billing_address) $vendorAddress .= $vendor->billing_address . "\n";
            if ($vendor->billing_city) $vendorAddress .= $vendor->billing_city;
            if ($vendor->billing_state) $vendorAddress .= ', ' . $vendor->billing_state;
            if ($vendor->billing_zip) $vendorAddress .= ' ' . $vendor->billing_zip;
            if ($vendor->billing_country) $vendorAddress .= "\n" . $vendor->billing_country;
        }

        $Id = $vendorCredit->vender_id;

        return view('expense.vendor-credits-edit', compact(
            'vendorCredit',
            'venders',
            'customers',
            'chartAccounts',
            'product_services',
            'vendorAddress',
            'Id',
            'categoriesAccountData',
            'items'
        ));
    }

    /**
     * Update the specified vendor credit in storage.
     */
    public function update(Request $request, VendorCredit $vendorCredit)
    {
        $request->validate([
            'vender_id' => 'required|exists:venders,id',
            'date' => 'required|date',
        ]);

        // Calculate total from category and item lines
        $categoryTotal = 0;
        $itemTotal = 0;

        // Form uses 'categories' (repeater list name)
        if ($request->has('categories')) {
            foreach ($request->categories as $line) {
                if (!empty($line['account_id']) && isset($line['amount'])) {
                    $categoryTotal += floatval($line['amount'] ?? 0);
                }
            }
        }

        if ($request->has('items')) {
            foreach ($request->items as $item) {
                if (!empty($item['item_id'])) {
                    $qty = floatval($item['quantity'] ?? 1);
                    $rate = floatval($item['rate'] ?? 0);
                    $itemTotal += $qty * $rate;
                }
            }
        }

        $totalAmount = $categoryTotal + $itemTotal;

        // Update vendor credit
        $vendorCredit->update([
            'vender_id' => $request->vender_id,
            'date' => $request->date,
            'amount' => $totalAmount,
            'memo' => $request->memo,
        ]);

        if ($request->ref_no) {
            $vendorCredit->update(['vendor_credit_id' => $request->ref_no]);
        }

        // Delete old account lines and recreate
        VendorCreditAccount::where('vendor_credit_id', $vendorCredit->id)->delete();
        
        if ($request->has('categories')) {
            foreach ($request->categories as $line) {
                if (!empty($line['account_id'])) {
                    VendorCreditAccount::create([
                        'vendor_credit_id' => $vendorCredit->id,
                        'chart_account_id' => $line['account_id'],
                        'price' => floatval($line['amount'] ?? 0),
                        'description' => $line['description'] ?? null,
                        'tax' => isset($line['tax']) ? 1 : 0,
                        'billable' => isset($line['billable']) ? 1 : 0,
                        'customer_id' => !empty($line['customer_id']) ? $line['customer_id'] : null,
                    ]);
                }
            }
        }

        // Delete old product lines and recreate
        VendorCreditProduct::where('vendor_credit_id', $vendorCredit->id)->delete();
        
        if ($request->has('items')) {
            foreach ($request->items as $item) {
                if (!empty($item['item_id'])) {
                    $qty = floatval($item['quantity'] ?? 1);
                    $rate = floatval($item['rate'] ?? 0);
                    
                    VendorCreditProduct::create([
                        'vendor_credit_id' => $vendorCredit->id,
                        'product_id' => $item['item_id'],
                        'quantity' => $qty,
                        'price' => $rate,
                        'description' => $item['description'] ?? null,
                        'tax' => isset($item['tax_id']) ? 1 : 0,
                        'billable' => isset($item['billable']) ? 1 : 0,
                        'customer_id' => !empty($item['customer_id']) ? $item['customer_id'] : null,
                    ]);
                }
            }
        }

        // Handle AJAX request
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('Vendor Credit updated successfully'),
                'redirect' => route('expense.index'),
            ]);
        }

        return redirect()->route('expense.index')->with('success', __('Vendor Credit updated successfully'));
    }

    /**
     * Remove the specified vendor credit from storage.
     */
    public function destroy(VendorCredit $vendorCredit)
    {
        // Delete related records first
        VendorCreditAccount::where('vendor_credit_id', $vendorCredit->id)->delete();
        VendorCreditProduct::where('vendor_credit_id', $vendorCredit->id)->delete();
        
        $vendorCredit->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('Vendor Credit deleted successfully'),
        ]);
    }
}
