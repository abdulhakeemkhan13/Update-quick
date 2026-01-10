<?php

namespace App\Http\Controllers;

use App\Models\VendorCredit;
use App\Models\VendorCreditAccount;
use App\Models\VendorCreditProduct;
use App\Models\Vender;
use App\Models\Customer;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\ProductService;
use App\Models\Utility;
use App\Services\JournalService;
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
// dd($request->all());
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
            'vendor_credit_id' => VendorCredit::generateCreditNumber(),
            'status' => VendorCredit::STATUS_OPEN,
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

                         // Create journal entry using JournalService
        $this->createvendorCreditJournalEntry($vendorCredit);
                    
        Utility::makeActivityLog(\Auth::user()->id, 'Vendor Credit', $vendorCredit->id, 'Create Vendor Credit', 'Vendor Credit Created');

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
                    $rate = floatval($item['price'] ?? 0);
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
                    $rate = floatval($item['price'] ?? 0);
                    
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

         $journalEntry = JournalEntry::where('reference_id', $vendorCredit->id)
                ->where('module', 'vendor_credit')
                ->first();
            if ($journalEntry) {
;                // Update existing journal entry
                $this->updateVendorCreditJournalEntry($vendorCredit, $journalEntry);
            } else {
                // Create new journal entry if doesn't exist
                $this->createVendorCreditJournalEntry($vendorCredit);
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

    private function createvendorCreditJournalEntry($expense)
    {
        // Build journal items from expense categories and products
        $journalItems = [];

        $vendor = null;
     
        $vendor = Vender::find($expense->vender_id);
       
        
        $vendorName = $vendor ? $vendor->name : 'Unknown';

        // Add category-based expenses (VendorCreditAccount)
        $expenseAccounts = VendorCreditAccount::where('vendor_credit_id', $expense->id)->get();
        foreach ($expenseAccounts as $expenseAccount) {
            $journalItems[] = [
                'account_id' => $expenseAccount->chart_account_id,
                'debit' => $expenseAccount->price,
                'credit' => 0,
                'description' => $expenseAccount->description ?: 'Vendor Credit',
                'type' => 'Vendor Credit',
                'sub_type' => 'vendor credit',
                'name' => $vendorName,
                'ref_number' => $expense->ref_number,
                'user_type' => $expense->user_type,
                'vendor_id' => $expense->vender_id,
                'customer_id' => null,
                'created_user' => \Auth::user()->id,
                'created_by' => \Auth::user()->creatorId(),
                'company_id' => \Auth::user()->ownedId(),
            ];
        }

        // Add product/service items 
        $expenseProducts = VendorCreditProduct::where('vendor_credit_id', $expense->id)->get();
        foreach ($expenseProducts as $expenseProduct) {
            $product = $expenseProduct->product;
            
            // Determine account ID based on product type
            $accountId = null;
            if ($product) {
                $accountId = $expenseProduct->account_id ? $expenseProduct->account_id : $product->expense_chartaccount_id;
            }
            
            $journalItems[] = [
                'account_id' => $accountId,
                'debit' => $expenseProduct->line_total ?: ($expenseProduct->quantity * $expenseProduct->price),
                'credit' => 0,
                'description' => $expenseProduct->description ?: ($product ? $product->name : 'Product'),
                'product_id' => $expenseProduct->product_id,
                'type' => 'Vendor Credit',
                'sub_type' => 'vendor credit',
                'name' => $vendorName,
                'ref_number' => $expense->ref_number,
                'user_type' => $expense->user_type,
                'vendor_id' => $expense->vender_id,
                'customer_id' => null,
                'created_user' => \Auth::user()->id,
                'created_by' => \Auth::user()->creatorId(),
                'company_id' => \Auth::user()->ownedId(),
            ];
        }

        // $billPayment = BillPayment::where('bill_id', $expense->id)->first();
        // $bank = BankAccount::find($billPayment->account_id);
        // if($bank){
        //     $accountPayable = ChartOfAccount::where('id', $bank->chart_account_id)->first();
        // }else{
            $accountPayable = Utility::getAccountPayableAccount(\Auth::user()->creatorId());
        // }

        // Calculate total amount
        $totalAmount = 0;
        foreach ($journalItems as $item) {
            $totalAmount += $item['debit'];
        }

        // Create journal entry using JournalService
        $journalEntry = JournalService::createJournalEntry([
            'date' => $expense->bill_date,
            'backdate' => true,
            'reference' => $expense->vendor_credit_id,
            'description' => 'Vendor Credit from ' . $vendorName,
            'journal_id' => Utility::journalNumber(),
            'voucher_type' => 'JV',
            'reference_id' => $expense->id,
            'prod_id' => null,
            'category' => 'Vendor Credit',
            'module' => 'vendor_credit',
            'source' => 'vendor_credit_creation',
            'created_user' => \Auth::user()->id,
            'created_by' => \Auth::user()->creatorId(),
            'owned_by' => \Auth::user()->ownedId(),
            'ref_number' => $expense->ref_number,
            'user_type' => $expense->user_type,
            'vendor_id' => $expense->vender_id,
            'company_id' => \Auth::user()->ownedId(),
            'bill_id' => $expense->id,
            'items' => $journalItems,
            'ap_name' => $vendorName,
            'ap_account_id' => $accountPayable->id,
            'ap_amount' => $totalAmount,
            'ap_sub_type' => 'vendor credit',
            'ap_description' => 'Vendor Credit - ' . $expense->vendor_credit_id,
        ]);

        \Log::info('Journal entry created for vendor credit', [
            'expense_id' => $expense->id,
            'journal_entry_id' => $journalEntry->id,
        ]);
        
        return $journalEntry;
    }

     private function updateVendorCreditJournalEntry($expense, $journalEntry)
    {
        // Build journal items from expense categories and products
        $journalItems = [];

        // $vendor = null;
        // if ($expense->user_type == 'vendor') {
            $vendor = Vender::find($expense->vender_id);
     
        // } elseif ($expense->user_type == 'employee') {
        //     $vendor = Employee::find($expense->vender_id);
        // } elseif ($expense->user_type == 'customer') {
        //     $vendor = Customer::find($expense->vender_id);
        // }
        $expense->user_type = 'vendor';
        $vendorName = $vendor ? $vendor->name : 'Unknown';
 
        // Add category-based expenses (BillAccount)
        $expenseAccounts = VendorCreditAccount::where('vendor_credit_id', $expense->id)->get();
        foreach ($expenseAccounts as $expenseAccount) {
            $journalItems[] = [
                'account_id' => $expenseAccount->chart_account_id,
                'debit' => $expenseAccount->price,
                'credit' => 0,
                'description' => $expenseAccount->description ?: 'Vendor Credit',
                'type' => 'Vendor Credit',
                'sub_type' => 'vendor credit account',
                'name' => $vendorName,
                'vendor_id' => $expense->vender_id,
                'user_type' => $expense->user_type,
                'ref_number' => $expense->ref_number,
                'customer_id' => null,
                'created_user' => \Auth::user()->id,
                'created_by' => \Auth::user()->creatorId(),
                'company_id' => \Auth::user()->ownedId(),
            ];
        }

        // Add product/service items (BillProduct)
        $expenseProducts = VendorCreditProduct::where('vendor_credit_id', $expense->id)->get();
        foreach ($expenseProducts as $expenseProduct) {
            $product = $expenseProduct->product;
            
            
            // Determine account ID based on product type
            $accountId = null;
            if ($product) {
                $accountId = $expenseProduct->account_id ? $expenseProduct->account_id : $product->expense_chartaccount_id;
            }
            $journalItems[] = [
                'account_id' => $accountId,
                'debit' => $expenseProduct->line_total ?: ($expenseProduct->quantity * $expenseProduct->price),
                'credit' => 0,
                'description' => $expenseProduct->description ?: ($product ? $product->name : 'Product'),
                'product_id' => $expenseProduct->product_id,
                'type' => 'Vendor Credit',
                'sub_type' => 'vendor credit item',
                'user_type' => $expense->user_type,
                'ref_number' => $expense->ref_number,
                'name' => $vendorName,
                'vendor_id' => $expense->vender_id,
                'customer_id' => null,
                'created_user' => \Auth::user()->id,
                'created_by' => \Auth::user()->creatorId(),
                'company_id' => \Auth::user()->ownedId(),
            ];
        }

        // $billPayment = BillPayment::where('bill_id', $expense->id)->first();
        // $bank = BankAccount::find($billPayment->account_id);
        // if($bank){
        //     $accountPayable = ChartOfAccount::where('id', $bank->chart_account_id)->first();
        // }else{
            $accountPayable = Utility::getAccountPayableAccount(\Auth::user()->creatorId());
        // }

        // Calculate total amount
        $totalAmount = 0;
        foreach ($journalItems as $item) {
            $totalAmount += $item['debit'];
        }

        // Update journal entry using JournalService
        $updatedJournalEntry = JournalService::updateJournalEntry($journalEntry->id, [
            'date' => $expense->bill_date,
            'backdate' => true,
            'reference' => $expense->vendor_credit_id,
            'description' => 'Vendor Credit from ' . $vendorName,
            'reference_id' => $expense->id,
            'category' => 'Vendor Credit',
            'module' => 'vendor_credit',
            'source' => 'vendor_credit_update',
            'user_type' => $expense->user_type,
            'ref_number' => $expense->ref_number,
            'vendor_id' => $expense->vender_id,
            'bill_id' => $expense->id,
            'items' => $journalItems,
            'ap_name' => $vendorName,
            'ap_account_id' => $accountPayable->id,
            'ap_amount' => $totalAmount,
            'ap_sub_type' => 'vendor credit',
            'ap_description' => 'Vendor Credit - ' . $expense->vendor_credit_id,
        ]);

        \Log::info('Journal entry updated for vendor credit', [
            'expense_id' => $expense->id,
            'journal_entry_id' => $updatedJournalEntry->id,
        ]);
        
        return $updatedJournalEntry;
    }

}
