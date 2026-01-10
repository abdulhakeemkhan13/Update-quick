<?php

namespace App\Http\Controllers;

use App\Models\BillProduct;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountType;
use App\Models\InvoiceProduct;
use App\Models\ProposalProduct;
use App\Models\Tax;
use App\Models\Utility;
use Auth;
use Illuminate\Http\Request;

class TaxController extends Controller
{


    public function index()
    {
        if(\Auth::user()->can('manage constant tax'))
        {
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
            $taxes = Tax::where($column, '=',$ownerId)->get();

            // return view('taxes.index')->with('taxes', $taxes);
            return view('taxes.taxes')->with('taxes', $taxes);
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function create()
    {
        if(\Auth::user()->can('create constant tax'))
        {
            // chart of account type liabilities
            $chartOfAccountType = ChartOfAccountType::where('created_by', \Auth::user()->creatorId())->where('name', 'Liabilities')->first();
            $chartAccounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())
                ->where('type', $chartOfAccountType->id)
                ->pluck('name', 'id')
                ->prepend(__('Select Account'), '');
            
            return view('taxes.create', compact('chartAccounts'));
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if(\Auth::user()->can('create constant tax'))
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'name' => 'required|max:20',
                                   'rate' => 'required|numeric',
                               ]
            );
            if($request->ajax()) {
                if($validator->fails())
                {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors()->all(),
                    ], 422);
                }
            }
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $tax             = new Tax();
            $tax->name       = $request->name;
            $tax->rate       = $request->rate;
            $tax->chart_account_id = $request->chart_account_id;
            $tax->created_by = \Auth::user()->creatorId();
            $tax->owned_by = \Auth::user()->ownedId();
            $tax->save();
            Utility::makeActivityLog(\Auth::user()->id,'Tax',$tax->id,'Create Tax',$tax->name);
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data'    => $tax,
                    'message' => __('Tax successfully created.'),
                ]);
            }
            return redirect()->route('taxes.index')->with('success', __('Tax rate successfully created.'));
        }
        else
        {
            if($request->ajax()) {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(Tax $tax)
    {
        return redirect()->route('taxes.index');
    }


    public function edit(Tax $tax)
    {
        if(\Auth::user()->can('edit constant tax'))
        {
            if($tax->created_by == \Auth::user()->creatorId())
            {
                 $chartOfAccountType = ChartOfAccountType::where('created_by', \Auth::user()->creatorId())->where('name', 'Liabilities')->first();
            $chartAccounts = ChartOfAccount::where('created_by', \Auth::user()->creatorId())
                ->where('type', $chartOfAccountType->id)
                ->pluck('name', 'id')
                ->prepend(__('Select Account'), '');
                
                return view('taxes.edit', compact('tax', 'chartAccounts'));
            }
            else
            {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function update(Request $request, Tax $tax)
    {
        if(\Auth::user()->can('edit constant tax'))
        {
            if($tax->created_by == \Auth::user()->creatorId())
            {
                $validator = \Validator::make(
                    $request->all(), [
                                       'name' => 'required|max:20',
                                       'rate' => 'required|numeric',
                                   ]
                );
                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $tax->name = $request->name;
                $tax->rate = $request->rate;
                $tax->chart_account_id = $request->chart_account_id;
                $tax->save();
                Utility::makeActivityLog(\Auth::user()->id,'Tax',$tax->id,'Update Tax',$tax->name);
                return redirect()->route('taxes.index')->with('success', __('Tax rate successfully updated.'));
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

    public function destroy(Tax $tax)
    {
        if(\Auth::user()->can('delete constant tax'))
        {
            if($tax->created_by == \Auth::user()->creatorId())
            {
                $proposalData = ProposalProduct::whereRaw("find_in_set('$tax->id',tax)")->first();
                $billData     = BillProduct::whereRaw("find_in_set('$tax->id',tax)")->first();
                $invoiceData  = InvoiceProduct::whereRaw("find_in_set('$tax->id',tax)")->first();

                if(!empty($proposalData) || !empty($billData) || !empty($invoiceData))
                {
                    return redirect()->back()->with('error', __('this tax is already assign to proposal or bill or invoice so please move or remove this tax related data.'));
                }
                
                Utility::makeActivityLog(\Auth::user()->id,'Tax',$tax->id,'Delete Tax',$tax->name);
                $tax->delete();

                return redirect()->route('taxes.index')->with('success', __('Tax rate successfully deleted.'));
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
