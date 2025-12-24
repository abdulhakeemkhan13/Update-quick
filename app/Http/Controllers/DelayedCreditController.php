<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ProductService;
use App\Models\Tax;
use App\Models\DelayedCredits;
use App\Models\DelayedCreditLines;
use Illuminate\Http\Request;

class DelayedCreditController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (\Auth::user()->can('manage invoice')) {
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = $user->type == 'company' ? 'created_by' : 'owned_by';

            $delayedCredits = DelayedCredits::where($column, $ownerId)
                ->with('customer')
                ->orderBy('date', 'desc')
                ->get();

            return view('delayedCredit.index', compact('delayedCredits'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
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

            $delayed_credit_number = 'DC-' . str_pad($this->delayedCreditNumber(), 5, '0', STR_PAD_LEFT);
            
            $customers = Customer::where($column, $ownerId)->get()->pluck('name', 'id')->toArray();
            $customers = ['__add__' => '➕ Add new customer'] + ['' => 'Select Customer'] + $customers;
            
            $product_services = ProductService::get()->pluck('name', 'id');
            $product_services->prepend('--', '');
            
            $taxes = Tax::where('created_by', \Auth::user()->creatorId())->get();

            // Return partial view for AJAX requests
            if (request()->ajax()) {
                return view('delayedCredit.create_modal', compact('customers', 'delayed_credit_number', 'product_services', 'customerId', 'taxes'));
            }

            return view('delayedCredit.create', compact('customers', 'delayed_credit_number', 'product_services', 'customerId', 'taxes'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    /**
     * Generate next delayed credit number.
     */
    public function delayedCreditNumber()
    {
        $latest = DelayedCredits::where('created_by', \Auth::user()->creatorId())
            ->orderBy('id', 'desc')
            ->first();

        if ($latest) {
            return $latest->id + 1;
        }
        return 1;
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
                    'date' => 'required|date',
                    'items' => 'required',
                    'memo' => 'nullable|string',
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

                // Create Delayed Credit
                $delayedCredit = new DelayedCredits();
                $delayedCredit->credit_id = 'DC-' . $this->delayedCreditNumber();
                $delayedCredit->type = 'DelayedCredit';
                $delayedCredit->customer_id = $request->customer_id;
                $delayedCredit->date = $request->date;
                $delayedCredit->private_note = $request->private_note ?? '';
                $delayedCredit->memo = $request->memo;
                $delayedCredit->created_by = \Auth::user()->creatorId();
                $delayedCredit->owned_by = \Auth::user()->ownedId();

                // Handle attachments
                if ($request->hasFile('attachments')) {
                    $attachments = [];
                    foreach ($request->file('attachments') as $attachment) {
                        $attachmentName = time() . '_' . uniqid() . '.' . $attachment->getClientOriginalExtension();
                        $attachment->storeAs('uploads/delayed_credit_attachments', $attachmentName, 'public');
                        $attachments[] = $attachmentName;
                    }
                    $delayedCredit->attachments = $attachments;
                }

                $delayedCredit->save();

                // Parse items
                $products = $request->items;
                if (is_string($products)) {
                    $products = json_decode($products, true);
                }

                $totalAmount = 0;

                // Save line items
                if (!empty($products)) {
                    foreach ($products as $product) {
                        if (empty($product['item']) && empty($product['description'])) {
                            continue;
                        }

                        $quantity = floatval($product['quantity'] ?? 1);
                        $rate = floatval($product['price'] ?? 0);
                        $amount = $quantity * $rate;
                        $totalAmount += $amount;

                        $line = new DelayedCreditLines();
                        $line->delayed_credit_id = $delayedCredit->id;
                        $line->product_id = $product['item'] ?? null;
                        $line->quantity = $quantity;
                        $line->rate = $rate;
                        $line->amount = $amount;
                        $line->description = $product['description'] ?? '';
                        $line->tax = isset($product['tax']) && $product['tax'] ? true : false;
                        $line->created_by = \Auth::user()->creatorId();
                        $line->owned_by = \Auth::user()->ownedId();
                        $line->save();
                    }
                }

                // Update total amount
                $delayedCredit->total_amount = $totalAmount;
                $delayedCredit->remaining_balance = $totalAmount;
                $delayedCredit->save();

                \DB::commit();

                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => __('Delayed Credit created successfully.'),
                        'redirect' => route('delayed-credit.index')
                    ]);
                }

                return redirect()->route('delayed-credit.index')->with('success', __('Delayed Credit created successfully.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        if (\Auth::user()->can('show invoice')) {
            $delayedCredit = DelayedCredits::with(['customer', 'lines.product'])->findOrFail($id);
            return view('delayedCredit.show', compact('delayedCredit'));
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
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = $user->type == 'company' ? 'created_by' : 'owned_by';

            $delayedCredit = DelayedCredits::with(['customer', 'lines.product'])->findOrFail($id);
            
            $customers = Customer::where($column, $ownerId)->get()->pluck('name', 'id')->toArray();
            $customers = ['__add__' => '➕ Add new customer'] + ['' => 'Select Customer'] + $customers;
            
            $product_services = ProductService::get()->pluck('name', 'id');
            $product_services->prepend('--', '');
            
            $taxes = Tax::where('created_by', \Auth::user()->creatorId())->get();

            // Return partial view for AJAX requests
            if (request()->ajax()) {
                return view('delayedCredit.edit_modal', compact('delayedCredit', 'customers', 'product_services', 'taxes'));
            }

            return view('delayedCredit.edit', compact('delayedCredit', 'customers', 'product_services', 'taxes'));
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
                $validator = \Validator::make($request->all(), [
                    'customer_id' => 'required',
                    'date' => 'required|date',
                    'items' => 'required',
                    'memo' => 'nullable|string',
                    'attachments.*' => 'nullable|file|max:20480',
                ]);

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    if ($request->ajax()) {
                        return response()->json(['errors' => $validator->errors()], 422);
                    }
                    return redirect()->back()->with('error', $messages->first());
                }

                $delayedCredit = DelayedCredits::findOrFail($id);
                $delayedCredit->customer_id = $request->customer_id;
                $delayedCredit->date = $request->date;
                $delayedCredit->private_note = $request->private_note ?? '';
                $delayedCredit->memo = $request->memo;

                // Handle attachments
                if ($request->hasFile('attachments')) {
                    $existingAttachments = $delayedCredit->attachments ?? [];
                    $attachments = is_array($existingAttachments) ? $existingAttachments : [];
                    
                    foreach ($request->file('attachments') as $attachment) {
                        $attachmentName = time() . '_' . uniqid() . '.' . $attachment->getClientOriginalExtension();
                        $attachment->storeAs('uploads/delayed_credit_attachments', $attachmentName, 'public');
                        $attachments[] = $attachmentName;
                    }
                    $delayedCredit->attachments = $attachments;
                }

                $delayedCredit->save();

                // Delete existing line items
                DelayedCreditLines::where('delayed_credit_id', $id)->delete();

                // Parse items
                $products = $request->items;
                if (is_string($products)) {
                    $products = json_decode($products, true);
                }

                $totalAmount = 0;

                // Save line items
                if (!empty($products)) {
                    foreach ($products as $product) {
                        if (empty($product['item']) && empty($product['description'])) {
                            continue;
                        }

                        $quantity = floatval($product['quantity'] ?? 1);
                        $rate = floatval($product['price'] ?? 0);
                        $amount = $quantity * $rate;
                        $totalAmount += $amount;

                        $line = new DelayedCreditLines();
                        $line->delayed_credit_id = $delayedCredit->id;
                        $line->product_id = $product['item'] ?? null;
                        $line->quantity = $quantity;
                        $line->rate = $rate;
                        $line->amount = $amount;
                        $line->description = $product['description'] ?? '';
                        $line->tax = isset($product['tax']) && $product['tax'] ? true : false;
                        $line->created_by = \Auth::user()->creatorId();
                        $line->owned_by = \Auth::user()->ownedId();
                        $line->save();
                    }
                }

                // Update total amount
                $delayedCredit->total_amount = $totalAmount;
                $delayedCredit->remaining_balance = $totalAmount;
                $delayedCredit->save();

                \DB::commit();

                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => __('Delayed Credit updated successfully.'),
                        'redirect' => route('delayed-credit.index')
                    ]);
                }

                return redirect()->route('delayed-credit.index')->with('success', __('Delayed Credit updated successfully.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (\Auth::user()->can('delete invoice')) {
            try {
                $delayedCredit = DelayedCredits::findOrFail($id);
                
                // Delete line items first
                DelayedCreditLines::where('delayed_credit_id', $id)->delete();
                
                // Delete the delayed credit
                $delayedCredit->delete();

                return redirect()->route('delayed-credit.index')->with('success', __('Delayed Credit deleted successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
