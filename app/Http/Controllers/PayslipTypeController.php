<?php

namespace App\Http\Controllers;

use App\Models\PayslipType;
use App\Models\Utility;
use Illuminate\Http\Request;

class PayslipTypeController extends Controller
{
    public function index()
    {
        if(\Auth::user()->can('manage payslip type'))
        {
            $user = \Auth::user();
            
            if ($user->type == 'company') {
                $paysliptypes = PayslipType::where('created_by', '=', \Auth::user()->creatorId())->get();
            } else {
                $paysliptypes = PayslipType::where(function ($query) use ($user) {
                    $query->where('created_by', $user->creatorId())  
                        ->orWhere('owned_by', $user->ownedId()); 
                })->get();
            }
            return view('paysliptype.index', compact('paysliptypes'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if(\Auth::user()->can('create payslip type'))
        {
            return view('paysliptype.create');
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {

        if(\Auth::user()->can('create payslip type'))
        {

            $validator = \Validator::make(
                $request->all(), [
                                   'name' => 'required|max:20',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $paysliptype             = new PayslipType();
            $paysliptype->name       = $request->name;
            $paysliptype->created_by = \Auth::user()->creatorId();
            $paysliptype->owned_by = \Auth::user()->ownedId();
            $paysliptype->save();
            Utility::makeActivityLog(\Auth::user()->id,'Payslip Type',$paysliptype->id,'Create Payslip Type',$paysliptype->name);
            return redirect()->route('paysliptype.index')->with('success', __('PayslipType  successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(PayslipType $paysliptype)
    {
        return redirect()->route('paysliptype.index');
    }

    public function edit(PayslipType $paysliptype)
    {
        if(\Auth::user()->can('edit payslip type'))
        {
            if($paysliptype->created_by == \Auth::user()->creatorId())
            {

                return view('paysliptype.edit', compact('paysliptype'));
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

    public function update(Request $request, PayslipType $paysliptype)
    {
        if(\Auth::user()->can('edit payslip type'))
        {
            if($paysliptype->created_by == \Auth::user()->creatorId())
            {
                $validator = \Validator::make(
                    $request->all(), [
                                       'name' => 'required|max:20',
                                   ]
                );

                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $paysliptype->name = $request->name;
                $paysliptype->save();
                Utility::makeActivityLog(\Auth::user()->id,'Payslip Type',$paysliptype->id,'Update Payslip Type',$paysliptype->name);
                return redirect()->route('paysliptype.index')->with('success', __('PayslipType successfully updated.'));
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

    public function destroy(PayslipType $paysliptype)
    {
        if(\Auth::user()->can('delete payslip type'))
        {
            if($paysliptype->created_by == \Auth::user()->creatorId())
            {
                Utility::makeActivityLog(\Auth::user()->id,'Payslip Type',$paysliptype->id,'Delete Payslip Type',$paysliptype->name);
                $paysliptype->delete();
                return redirect()->route('paysliptype.index')->with('success', __('PayslipType successfully deleted.'));
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
