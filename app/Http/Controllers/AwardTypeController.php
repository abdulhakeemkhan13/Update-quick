<?php

namespace App\Http\Controllers;

use App\Models\AwardType;
use App\Models\Utility;
use Illuminate\Http\Request;

class AwardTypeController extends Controller
{
    public function index()
    {
        if (\Auth::user()->can('manage award type')) {
            $user = \Auth::user();
            if ($user->type == 'company') {
                $awardtypes = AwardType::where('created_by', '=', $user->creatorId())->get();
            } else {
                $awardtypes = AwardType::where(function ($query) use ($user) {
                    $query->where('created_by', $user->creatorId())
                          ->orWhere('owned_by', $user->ownedId());
                })->get();
            }

            return view('awardtype.index', compact('awardtypes'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create award type')) {
            return view('awardtype.create');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create award type')) {
            $validator = \Validator::make(
                $request->all(),
                ['name' => 'required|max:20']
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $awardtype = new AwardType();
            $awardtype->name = $request->name;
            $awardtype->created_by = \Auth::user()->creatorId();
            $awardtype->owned_by = \Auth::user()->ownedId();
            $awardtype->save();

            Utility::makeActivityLog(\Auth::user()->id, 'Award Type', $awardtype->id, 'Create Award Type', $awardtype->name);

            return redirect()->route('awardtype.index')->with('success', __('AwardType successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(AwardType $awardType)
    {
        return redirect()->route('awardtype.index');
    }

    public function edit($id)
    {
        if (\Auth::user()->can('edit award type')) {
            $awardType = AwardType::find($id);
            if ($awardType->created_by == \Auth::user()->creatorId() || $awardType->owned_by == \Auth::user()->ownedId()) {
                return view('awardtype.edit', compact('awardType'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, $id)
    {
        if (\Auth::user()->can('edit award type')) {
            $awardType = AwardType::find($id);
            if ($awardType->created_by == \Auth::user()->creatorId() || $awardType->owned_by == \Auth::user()->ownedId()) {
                $validator = \Validator::make(
                    $request->all(),
                    ['name' => 'required|max:20']
                );

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }

                $awardType->name = $request->name;
                $awardType->save();

                Utility::makeActivityLog(\Auth::user()->id, 'Award Type', $awardType->id, 'Update Award Type', $awardType->name);

                return redirect()->route('awardtype.index')->with('success', __('AwardType successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy($id)
    {
        if (\Auth::user()->can('delete award type')) {
            $awardType = AwardType::find($id);
            if ($awardType->created_by == \Auth::user()->creatorId()) {
                Utility::makeActivityLog(\Auth::user()->id, 'Award Type', $awardType->id, 'Delete Award Type', $awardType->name);

                $awardType->delete();

                return redirect()->route('awardtype.index')->with('success', __('AwardType successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
