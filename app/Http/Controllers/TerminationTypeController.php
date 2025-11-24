<?php

namespace App\Http\Controllers;

use App\Models\TerminationType;
use App\Models\Utility;
use Illuminate\Http\Request;

class TerminationTypeController extends Controller
{
    public function index()
    {
        if (\Auth::user()->can('manage termination type')) {
            $user = \Auth::user();
            $terminationtypes = TerminationType::where('created_by', '=', $user->creatorId())->get();

            return view('terminationtype.index', compact('terminationtypes'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create termination type')) {
            return view('terminationtype.create');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create termination type')) {
            $validator = \Validator::make(
                $request->all(),
                ['name' => 'required']
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $terminationtype = new TerminationType();
            $terminationtype->name = $request->name;
            $terminationtype->created_by = \Auth::user()->creatorId();
            $terminationtype->owned_by = \Auth::user()->ownedId();
            $terminationtype->save();

            // Activity log for creation
            Utility::makeActivityLog(\Auth::user()->id, 'Termination Type', $terminationtype->id, 'Create Termination Type', $terminationtype->name);

            return redirect()->route('terminationtype.index')->with('success', __('Termination Type successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(TerminationType $terminationtype)
    {
        return redirect()->route('terminationtype.index');
    }

    public function edit($id)
    {
        if (\Auth::user()->can('edit termination type')) {
            $terminationtype = TerminationType::find($id);
            if ($terminationtype->created_by == \Auth::user()->creatorId()) {
                return view('terminationtype.edit', compact('terminationtype'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, $id)
    {
        if (\Auth::user()->can('edit termination type')) {
            $terminationtype = TerminationType::find($id);
            if ($terminationtype->created_by == \Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    ['name' => 'required|max:20']
                );

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }

                $terminationtype->name = $request->name;
                $terminationtype->save();

                // Activity log for update
                Utility::makeActivityLog(\Auth::user()->id, 'Termination Type', $terminationtype->id, 'Update Termination Type', $terminationtype->name);

                return redirect()->route('terminationtype.index')->with('success', __('Termination Type successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy($id)
    {
        if (\Auth::user()->can('delete termination type')) {
            $terminationtype = TerminationType::find($id);
            if ($terminationtype->created_by == \Auth::user()->creatorId()) {
                // Activity log for deletion
                Utility::makeActivityLog(\Auth::user()->id, 'Termination Type', $terminationtype->id, 'Delete Termination Type', $terminationtype->name);

                $terminationtype->delete();

                return redirect()->route('terminationtype.index')->with('success', __('Termination Type successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
