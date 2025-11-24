<?php

namespace App\Http\Controllers;

use App\Models\LoanOption;
use App\Models\Utility;
use Illuminate\Http\Request;

class LoanOptionController extends Controller
{
    public function index()
    {
        if (\Auth::user()->can('manage loan option')) {
            $user = \Auth::user();

            if ($user->type == 'company') {
                $loanoptions = LoanOption::where('created_by', $user->creatorId())->get();
            } else {
                $loanoptions = LoanOption::where(function ($query) use ($user) {
                    $query->where('created_by', $user->creatorId())
                        ->orWhere('owned_by', $user->ownedId());
                })->get();
            }
            return view('loanoption.index', compact('loanoptions'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create loan option')) {
            return view('loanoption.create');
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create loan option')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'name' => 'required|max:20',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $loanoption = new LoanOption();
            $loanoption->name = $request->name;
            $loanoption->created_by = \Auth::user()->creatorId();
            $loanoption->owned_by = \Auth::user()->ownedId(); // Added owned_by
            $loanoption->save();

            Utility::makeActivityLog(\Auth::user()->id, 'Loan Option', $loanoption->id, 'Create Loan Option', $loanoption->name);

            return redirect()->route('loanoption.index')->with('success', __('LoanOption successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(LoanOption $loanoption)
    {
        return redirect()->route('loanoption.index');
    }

    public function edit(LoanOption $loanoption)
    {
        if (\Auth::user()->can('edit loan option')) {
            if ($loanoption->created_by == \Auth::user()->creatorId()) {
                return view('loanoption.edit', compact('loanoption'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, LoanOption $loanoption)
    {
        if (\Auth::user()->can('edit loan option')) {
            if ($loanoption->created_by == \Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'name' => 'required|max:20',
                    ]
                );

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }

                $loanoption->name = $request->name;
                $loanoption->save();

                Utility::makeActivityLog(\Auth::user()->id, 'Loan Option', $loanoption->id, 'Update Loan Option', $loanoption->name);

                return redirect()->route('loanoption.index')->with('success', __('LoanOption successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(LoanOption $loanoption)
    {
        if (\Auth::user()->can('delete loan option')) {
            if ($loanoption->created_by == \Auth::user()->creatorId()) {
                Utility::makeActivityLog(\Auth::user()->id, 'Loan Option', $loanoption->id, 'Delete Loan Option', $loanoption->name);
                $loanoption->delete();

                return redirect()->route('loanoption.index')->with('success', __('LoanOption successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
