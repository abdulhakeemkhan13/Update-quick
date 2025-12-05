<?php

namespace App\Http\Controllers;

use App\Models\PaymentTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentTermController extends Controller
{
    /**
     * Display a listing of payment terms.
     */
    public function index()
    {
        if (!Auth::user()->can('manage customer')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $paymentTerms = PaymentTerm::where('created_by', Auth::user()->creatorId())
            ->orderBy('name')
            ->get();

        return view('terms.index', compact('paymentTerms'));
    }

    /**
     * Show the form for creating a new payment term.
     */
    public function create()
    {
        if (!Auth::user()->can('create customer')) {
            return response()->json(['error' => __('Permission denied.')], 401);
        }

        return view('terms.create-right');
    }

    /**
     * Store a newly created payment term.
     */
    public function store(Request $request)
    {
        if (!Auth::user()->can('create customer')) {
            return response()->json(['error' => __('Permission denied.')], 401);
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:fixed_days,day_of_month,next_month_if_within',
            'due_in_days' => 'nullable|integer|min:0',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'cutoff_days' => 'nullable|integer|min:0',
        ]);

        $type = $request->type;
        // If type is day_of_month but cutoff_days is provided, switch to next_month_if_within
        if ($type === 'day_of_month' && !empty($request->cutoff_days)) {
            $type = 'next_month_if_within';
        }

        $paymentTerm = PaymentTerm::create([
            'name' => $request->name,
            'type' => $type,
            'due_in_days' => $type === 'fixed_days' ? $request->due_in_days : null,
            'day_of_month' => in_array($type, ['day_of_month', 'next_month_if_within']) ? $request->day_of_month : null,
            'cutoff_days' => $type === 'next_month_if_within' ? $request->cutoff_days : null,
            'is_active' => true,
            'created_by' => Auth::user()->creatorId(),
        ]);

        return redirect()->route('payment-terms.index')->with('success', __('Payment term created successfully.'));
    }

    /**
     * Show the form for editing the specified payment term.
     */
    public function edit($id)
    {
        if (!Auth::user()->can('edit customer')) {
            return response()->json(['error' => __('Permission denied.')], 401);
        }

        $paymentTerm = PaymentTerm::where('created_by', Auth::user()->creatorId())
            ->findOrFail($id);

        return view('terms.edit-right', compact('paymentTerm'));
    }

    /**
     * Update the specified payment term.
     */
    public function update(Request $request, $id)
    {
        if (!Auth::user()->can('edit customer')) {
            return response()->json(['error' => __('Permission denied.')], 401);
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:fixed_days,day_of_month,next_month_if_within',
            'due_in_days' => 'nullable|integer|min:0',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'cutoff_days' => 'nullable|integer|min:0',
        ]);

        $paymentTerm = PaymentTerm::where('created_by', Auth::user()->creatorId())
            ->findOrFail($id);

        $type = $request->type;
        // If type is day_of_month but cutoff_days is provided, switch to next_month_if_within
        if ($type === 'day_of_month' && !empty($request->cutoff_days)) {
            $type = 'next_month_if_within';
        }

        $paymentTerm->update([
            'name' => $request->name,
            'type' => $type,
            'due_in_days' => $type === 'fixed_days' ? $request->due_in_days : null,
            'day_of_month' => in_array($type, ['day_of_month', 'next_month_if_within']) ? $request->day_of_month : null,
            'cutoff_days' => $type === 'next_month_if_within' ? $request->cutoff_days : null,
        ]);

        return redirect()->route('payment-terms.index')->with('success', __('Payment term updated successfully.'));
    }

    /**
     * Remove the specified payment term.
     */
    public function destroy($id)
    {
        if (!Auth::user()->can('delete customer')) {
            return response()->json(['error' => __('Permission denied.')], 401);
        }

        $paymentTerm = PaymentTerm::where('created_by', Auth::user()->creatorId())
            ->findOrFail($id);

        $paymentTerm->delete();

        return redirect()->route('payment-terms.index')->with('success', __('Payment term deleted successfully.'));
    }
}
