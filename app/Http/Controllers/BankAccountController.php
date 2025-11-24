<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BillPayment;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountType;
use App\Models\CustomField;
use App\Models\InvoicePayment;
use App\Models\Payment;
use App\Models\Revenue;
use App\Models\Utility;
use App\Models\Transaction;
use App\Models\TransactionLines;
use App\Models\WorkFlow;
use App\Models\Notification;
use App\Models\WorkFlowAction;
use Illuminate\Http\Request;
use Auth;
use illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class BankAccountController extends Controller
{

    public function index()
    {
         if(\Auth::user()->can('create bank account'))
        {
            $accounts = BankAccount::where('created_by', '=', \Auth::user()->creatorId())->with(['chartAccount','holdings'])->get();
            // dd($accounts);
            return view('bankAccount.index', compact('accounts'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

public function create()
{
    if(\Auth::user()->can('create bank account'))
    {
        $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
            ->where('parent', 0)
            ->where('created_by', \Auth::user()->creatorId())
            ->get()
            ->pluck('code_name', 'id');
        $chartAccounts->prepend('Select Account', 0);

        $subAccounts = ChartOfAccount::select(\DB::raw(
                'CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name, ' .
                'chart_of_accounts.id, chart_of_accounts.code, chart_of_accounts.name , chart_of_account_parents.account'
            ))
            ->leftjoin('chart_of_account_parents', 'chart_of_accounts.parent', 'chart_of_account_parents.id')
            ->where('chart_of_accounts.parent', '!=', 0)
            ->where('chart_of_accounts.created_by', \Auth::user()->creatorId())
            ->get()
            ->toArray();

        // Dynamic subtypes from existing bank accounts (tenant-scoped)
        // $account_subtypes = BankAccount::where('created_by', \Auth::user()->creatorId())
        //     ->whereNotNull('account_subtype')
        //     ->select('account_subtype')
        //     ->distinct()
        //     ->orderBy('account_subtype')
        //     ->pluck('account_subtype', 'account_subtype')
        //     ->toArray();
            $account_subtypes = [
                'saving'   => 'Saving Account',
                'current'  => 'Current Account',
                'fixed'    => 'Fixed Deposit',
            ];
        $account_subtypes = ['' => __('Select Type')] + $account_subtypes;

        $customFields = CustomField::where('created_by', \Auth::user()->creatorId())
            ->where('module', 'account')
            ->get();

        return view('bankAccount.create', compact('customFields','chartAccounts','subAccounts','account_subtypes'));
    }

    return response()->json(['error' => __('Permission denied.')], 401);
}



public function store(Request $request)
{
    \DB::beginTransaction();
    try {
        if (!\Auth::user()->can('create bank account')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => __('Permission denied.')], 403);
            }
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $rules = [
            'holder_name'     => 'required',
            'bank_name'       => 'required',
            'account_number'  => 'required',
            'account_subtype' => ['required','string','max:100'],
        ];
        if ($request->contact_number != null) {
            $rules['contact_number'] = ['regex:/^([0-9\s\-\+\(\)]*)$/'];
        }

        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors'  => $validator->errors(),
                ], 422);
            }
            $messages = $validator->getMessageBag();
            return redirect()->route('bank-account.index')->with('error', $messages->first());
        }

        // Prevent duplicate subtype per bank (tenant-scoped)
        $duplicate = BankAccount::where('created_by', \Auth::user()->creatorId())
            ->where('bank_name', $request->bank_name)
            ->where('account_subtype', $request->account_subtype)
            ->exists();

        if ($duplicate) {
            $dupMsg = __('A ":type" sub-account already exists for ":bank".', [
                'type' => ucfirst($request->account_subtype),
                'bank' => $request->bank_name,
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $dupMsg], 409);
            }

            return redirect()
                ->route('bank-account.index')
                ->with('error', $dupMsg);
        }

        $account                   = new BankAccount();
        $account->chart_account_id = $request->chart_account_id;
        $account->holder_name      = $request->holder_name;
        $account->bank_name        = $request->bank_name;
        $account->account_number   = $request->account_number;
        $account->opening_balance  = $request->opening_balance ?: 0;
        $account->contact_number   = $request->contact_number ?: '-';
        $account->bank_address     = $request->bank_address ?: '-';
        $account->account_subtype  = $request->account_subtype;
        $account->created_by       = \Auth::user()->creatorId();
        $account->save();

        CustomField::saveData($account, $request->customField);

        // (Your existing workflow/notifications code stays as-is)

        $data = [
            'account_id'         => $account->chart_account_id,
            'transaction_type'   => 'Credit',
            'transaction_amount' => $account->opening_balance,
            'reference'          => 'Bank Account',
            'reference_id'       => $account->id,
            'reference_sub_id'   => 0,
            'date'               => date('Y-m-d'),
        ];
        Utility::addTransactionLines($data , 'create');

        \DB::commit();

        // If AJAX, return JSON so the page can append/select without refresh
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'id'           => $account->id,
                // Common labels you might use in a <select>
                'name'         => $account->bank_name . ' - ' . $account->account_subtype,
                'bank_name'    => $account->bank_name,
                'subtype'      => $account->account_subtype,
                'holder_name'  => $account->holder_name,
                'account_no'   => $account->account_number,
                'data'         => $account,
                'success'      => true,
            ], 201);
        }

        return redirect()->route('bank-account.index')->with('success', __('Account successfully created.'));

    } catch (\Exception $e) {
        \DB::rollback();
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
        return redirect()->back()->with('error', $e);
    }
}

    public function show()
    {
        return redirect()->route('bank-account.index');
    }


public function edit(BankAccount $bankAccount)
{
    if(\Auth::user()->can('edit bank account'))
    {
        if($bankAccount->created_by == \Auth::user()->creatorId())
        {
            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('parent', 0)
                ->where('created_by', \Auth::user()->creatorId())
                ->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', 0);

            $subAccounts = ChartOfAccount::select(\DB::raw(
                    'CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name, ' .
                    'chart_of_accounts.id, chart_of_accounts.code, chart_of_accounts.name , chart_of_account_parents.account'
                ))
                ->leftjoin('chart_of_account_parents', 'chart_of_accounts.parent', 'chart_of_account_parents.id')
                ->where('chart_of_accounts.parent', '!=', 0)
                ->where('chart_of_accounts.created_by', \Auth::user()->creatorId())
                ->get()
                ->toArray();

            // Dynamic subtypes list
            $account_subtypes = BankAccount::where('created_by', \Auth::user()->creatorId())
                ->whereNotNull('account_subtype')
                ->select('account_subtype')
                ->distinct()
                ->orderBy('account_subtype')
                ->pluck('account_subtype', 'account_subtype')
                ->toArray();
            $account_subtypes = ['' => __('Select Type')] + $account_subtypes;

            $bankAccount->customField = CustomField::getData($bankAccount, 'account');
            $customFields = CustomField::where('created_by', \Auth::user()->creatorId())
                ->where('module', 'account')
                ->get();

            return view('bankAccount.edit', compact('bankAccount','customFields','chartAccounts','subAccounts','account_subtypes'));
        }

        return response()->json(['error' => __('Permission denied.')], 401);
    }

    return response()->json(['error' => __('Permission denied.')], 401);
}




public function update(Request $request, BankAccount $bankAccount)
{
    if(\Auth::user()->can('create bank account'))
    {
        $rules = [
            'holder_name'     => 'required',
            'bank_name'       => 'required',
            'account_number'  => 'required',
            // Accept any non-empty subtype
            'account_subtype' => ['required','string','max:100'],
        ];
        if ($request->contact_number != null) {
            $rules['contact_number'] = ['regex:/^([0-9\s\-\+\(\)]*)$/'];
        }

        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->route('bank-account.index')->with('error', $messages->first());
        }

        // Prevent duplicate subtype per bank on update (ignore current)
        $duplicate = BankAccount::where('created_by', \Auth::user()->creatorId())
            ->where('bank_name', $request->bank_name)
            ->where('account_subtype', $request->account_subtype)
            ->where('id', '<>', $bankAccount->id)
            ->exists();

        if ($duplicate) {
            return redirect()
                ->route('bank-account.index')
                ->with('error', __('A ":type" sub-account already exists for ":bank".', [
                    'type' => ucfirst($request->account_subtype),
                    'bank' => $request->bank_name,
                ]));
        }

        $bankAccount->chart_account_id = $request->chart_account_id;
        $bankAccount->holder_name      = $request->holder_name;
        $bankAccount->bank_name        = $request->bank_name;
        $bankAccount->account_number   = $request->account_number;
        $bankAccount->opening_balance  = $request->opening_balance ?: 0;
        $bankAccount->contact_number   = $request->contact_number ?: '-';
        $bankAccount->bank_address     = $request->bank_address ?: '-';
        $bankAccount->account_subtype  = $request->account_subtype;
        $bankAccount->created_by       = \Auth::user()->creatorId();
        $bankAccount->save();

        CustomField::saveData($bankAccount, $request->customField);

        $data = [
            'account_id'         => $bankAccount->chart_account_id,
            'transaction_type'   => 'Credit',
            'transaction_amount' => $bankAccount->opening_balance,
            'reference'          => 'Bank Account',
            'reference_id'       => $bankAccount->id,
            'reference_sub_id'   => 0,
            'date'               => date('Y-m-d'),
        ];
        Utility::addTransactionLines($data , 'edit');

        return redirect()->route('bank-account.index')->with('success', __('Account successfully updated.'));
    }

    return redirect()->back()->with('error', __('Permission denied.'));
}




    public function destroy(BankAccount $bankAccount)
    {
        if(\Auth::user()->can('delete bank account'))
        {
            if($bankAccount->created_by == \Auth::user()->creatorId())
            {
                $revenue        = Revenue::where('account_id', $bankAccount->id)->first();
                $invoicePayment = InvoicePayment::where('account_id', $bankAccount->id)->first();
                $transaction    = Transaction::where('account', $bankAccount->id)->first();
                $payment        = Payment::where('account_id', $bankAccount->id)->first();
                $billPayment    = BillPayment::first();

            TransactionLines::where('reference_id', $bankAccount->id)->where('reference', 'Bank Account')->delete();

                if(!empty($revenue) && !empty($invoicePayment) && !empty($transaction) && !empty($payment) && !empty($billPayment))
                {
                    return redirect()->route('bank-account.index')->with('error', __('Please delete related record of this account.'));
                }
                else
                {
                    $bankAccount->delete();

                    return redirect()->route('bank-account.index')->with('success', __('Account successfully deleted.'));
                }

            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
