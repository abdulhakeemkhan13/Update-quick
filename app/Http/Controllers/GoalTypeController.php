<?php

namespace App\Http\Controllers;

use App\Models\GoalType;
use App\Models\Utility;
use Illuminate\Http\Request;

class GoalTypeController extends Controller
{
    public function index()
    {
        if (\Auth::user()->can('manage goal type')) {
            $user = \Auth::user();
            if ($user->type == 'company') {
                $goaltypes = GoalType::where('created_by', '=', $user->creatorId())->get();
            } else {
                $goaltypes = GoalType::where(function ($query) use ($user) {
                    $query->where('created_by', $user->creatorId())
                          ->orWhere('owned_by', $user->ownedId());
                })->get();
            }
            return view('goaltype.index', compact('goaltypes'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create goal type')) {
            return view('goaltype.create');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create goal type')) {
            $validator = \Validator::make(
                $request->all(),
                ['name' => 'required']
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $goaltype = new GoalType();
            $goaltype->name = $request->name;
            $goaltype->created_by = \Auth::user()->creatorId();
            $goaltype->owned_by = \Auth::user()->ownedId();
            $goaltype->save();

            Utility::makeActivityLog(\Auth::user()->id, 'Goal Type', $goaltype->id, 'Create Goal Type', $goaltype->name);

            return redirect()->route('goaltype.index')->with('success', __('Goal Type successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(GoalType $goalType)
    {
        return redirect()->route('goaltype.index');
    }

    public function edit($id)
    {
        if (\Auth::user()->can('edit goal type')) {
            $goalType = GoalType::find($id);
            if ($goalType->created_by == \Auth::user()->creatorId() || $goalType->owned_by == \Auth::user()->ownedId()) {
                return view('goaltype.edit', compact('goalType'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, $id)
    {
        if (\Auth::user()->can('edit goal type')) {
            $validator = \Validator::make(
                $request->all(),
                ['name' => 'required']
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $goalType = GoalType::find($id);
            if ($goalType->created_by == \Auth::user()->creatorId() || $goalType->owned_by == \Auth::user()->ownedId()) {
                $goalType->name = $request->name;
                $goalType->save();

                Utility::makeActivityLog(\Auth::user()->id, 'Goal Type', $goalType->id, 'Update Goal Type', $goalType->name);

                return redirect()->route('goaltype.index')->with('success', __('Goal Type successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy($id)
    {
        if (\Auth::user()->can('delete goal type')) {
            $goalType = GoalType::find($id);
            if ($goalType->created_by == \Auth::user()->creatorId()) {
                Utility::makeActivityLog(\Auth::user()->id, 'Goal Type', $goalType->id, 'Delete Goal Type', $goalType->name);

                $goalType->delete();

                return redirect()->route('goaltype.index')->with('success', __('Goal Type successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
