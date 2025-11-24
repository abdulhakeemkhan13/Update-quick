<?php

namespace App\Http\Controllers;

use App\Models\AllowanceOption;
use App\Models\Utility;
use Illuminate\Http\Request;

class AllowanceOptionController extends Controller
{
    public function index()
    {

        if (\Auth::user()->can('manage allowance option')) {
            $user = \Auth::user();
            if ($user->type == 'company') {
                $allowanceoptions = AllowanceOption::where('created_by', '=', \Auth::user()->creatorId())->get();
            } else {
                $allowanceoptions = AllowanceOption::where(function ($query) use ($user) {
                    $query->where('created_by', $user->creatorId())
                        ->orWhere('owned_by', $user->ownedId());
                })->get();
            }
            return view('allowanceoption.index', compact('allowanceoptions'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create allowance option')) {
            return view('allowanceoption.create');
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create allowance option')) {

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

            $allowanceoption = new AllowanceOption();
            $allowanceoption->name = $request->name;
            $allowanceoption->created_by = \Auth::user()->creatorId();
            $allowanceoption->owned_by = \Auth::user()->ownedId();
            $allowanceoption->save();
            Utility::makeActivityLog(\Auth::user()->id, 'Allowane Option', $allowanceoption->id, 'Create Allowane Option', $allowanceoption->name);
            return redirect()->route('allowanceoption.index')->with('success', __('AllowanceOption  successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(AllowanceOption $allowanceoption)
    {
        return redirect()->route('allowanceoption.index');
    }

    public function edit(AllowanceOption $allowanceoption)
    {
        if (\Auth::user()->can('edit allowance option')) {
            if ($allowanceoption->created_by == \Auth::user()->creatorId()) {

                return view('allowanceoption.edit', compact('allowanceoption'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, AllowanceOption $allowanceoption)
    {
        if (\Auth::user()->can('edit allowance option')) {
            if ($allowanceoption->created_by == \Auth::user()->creatorId()) {
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
                $allowanceoption->name = $request->name;
                $allowanceoption->save();
                Utility::makeActivityLog(\Auth::user()->id, 'Allowance Option', $allowanceoption->id, 'Update Allowance Option', $allowanceoption->name);
                return redirect()->route('allowanceoption.index')->with('success', __('AllowanceOption successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(AllowanceOption $allowanceoption)
    {
        if (\Auth::user()->can('delete allowance option')) {
            if ($allowanceoption->created_by == \Auth::user()->creatorId()) {
                Utility::makeActivityLogged(\Auth::user()->id, 'AllowanceOption', $allowanceoption->id, 'delete AllowanceOption', $allowanceoption->name);
                $allowanceoption->delete();

                return redirect()->route('allowanceoption.index')->with('success', __('AllowanceOption successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

}
