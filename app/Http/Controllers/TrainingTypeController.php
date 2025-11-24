<?php

namespace App\Http\Controllers;

use App\Models\TrainingType;
use App\Models\Utility;
use Illuminate\Http\Request;

class TrainingTypeController extends Controller
{
    public function index()
    {
        if (\Auth::user()->can('manage training type')) {
            $user = \Auth::user();
            if ($user->type == 'company') {
                $trainingtypes = TrainingType::where('created_by', '=', $user->creatorId())->get();
            } else {
                $trainingtypes = TrainingType::where(function ($query) use ($user) {
                    $query->where('created_by', $user->creatorId())
                          ->orWhere('owned_by', $user->ownedId());
                })->get();
            }

            return view('trainingtype.index', compact('trainingtypes'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create training type')) {
            return view('trainingtype.create');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create training type')) {
            $validator = \Validator::make(
                $request->all(),
                ['name' => 'required']
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $trainingtype = new TrainingType();
            $trainingtype->name = $request->name;
            $trainingtype->created_by = \Auth::user()->creatorId();
            $trainingtype->owned_by = \Auth::user()->ownedId();
            $trainingtype->save();

            Utility::makeActivityLog(\Auth::user()->id, 'Training Type', $trainingtype->id, 'Create Training Type', $trainingtype->name);

            return redirect()->route('trainingtype.index')->with('success', __('Training Type successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(TrainingType $trainingType)
    {
        return redirect()->route('trainingtype.index');
    }

    public function edit($id)
    {
        if (\Auth::user()->can('edit training type')) {
            $trainingType = TrainingType::find($id);
            if ($trainingType->created_by == \Auth::user()->creatorId() || $trainingType->owned_by == \Auth::user()->ownedId()) {
                return view('trainingtype.edit', compact('trainingType'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, $id)
    {
        if (\Auth::user()->can('edit training type')) {
            $trainingType = TrainingType::find($id);
            if ($trainingType->created_by == \Auth::user()->creatorId() || $trainingType->owned_by == \Auth::user()->ownedId()) {
                $validator = \Validator::make(
                    $request->all(),
                    ['name' => 'required']
                );

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }

                $trainingType->name = $request->name;
                $trainingType->save();

                Utility::makeActivityLog(\Auth::user()->id, 'Training Type', $trainingType->id, 'Update Training Type', $trainingType->name);

                return redirect()->route('trainingtype.index')->with('success', __('Training Type successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy($id)
    {
        if (\Auth::user()->can('delete training type')) {
            $trainingType = TrainingType::find($id);
            if ($trainingType->created_by == \Auth::user()->creatorId()) {
                Utility::makeActivityLog(\Auth::user()->id, 'Training Type', $trainingType->id, 'Delete Training Type', $trainingType->name);

                $trainingType->delete();

                return redirect()->route('trainingtype.index')->with('success', __('Training Type successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
