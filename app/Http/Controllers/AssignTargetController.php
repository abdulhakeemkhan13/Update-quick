<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AssignTarget;
use App\Models\Utility;
use Illuminate\Http\Request;

class AssignTargetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
        $targets = AssignTarget::with('user')->where($column, $ownerId)->get();
        return view('assign_target.index',compact('targets'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

        $assign_user = User::where($column, '=', $ownerId)
        ->whereNotIn('type', ['client', 'branch', 'company'])
        ->get()
        ->pluck('name', 'id');
        $assign_user->prepend('Select User', '');
            // dd($assign_user);
        return view('assign_target.create',compact('assign_user'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \DB::beginTransaction();
        try{
            $target = new AssignTarget();
            $target->user_id = $request->user;
            $target->lead_target = $request->lead_tar;
            $target->deal_target = $request->deal_tar;
            $target->month = $request->month;
            $target->created_by = \Auth::user()->creatorId();
            $target->owned_by = \Auth::user()->ownedId();
            $target->save();
            $user = User::find($target->user_id);
            Utility::makeActivityLog(\Auth::user()->id,'Assign Target',$target->id,'Create Assign Target',$user->name);
            \DB::commit();
            return redirect()->route('assign-target.index')->with('success', 'Target Assigned Successfully');
        } catch (\Exception $e) {
            \DB::rollBack();
            dd($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $target= AssignTarget::find($id);
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

        $assign_user = User::where($column, '=', $ownerId)
        ->whereNotIn('type', ['client', 'branch', 'company'])
        ->get()
        ->pluck('name', 'id');
        $assign_user->prepend('Select User', '');
        return view('assign_target.edit',compact('target','assign_user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        \DB::beginTransaction();
        try {
            $target = AssignTarget::find($id);
            $target->user_id = $request->user_id;
            $target->lead_target = $request->lead_tar;
            $target->deal_target = $request->deal_tar;
            $target->month = $request->month;
            $target->save();
            $user = User::find($target->user_id);
            Utility::makeActivityLog(\Auth::user()->id,'Assign Target',$target->id,'Update Assign Target',$user->name);
            \DB::commit();
            return redirect()->route('assign-target.index')->with('success', 'Target Updated Successfully');
        } catch (\Exception $e) {
            \DB::rollBack();
            dd($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        \DB::beginTransaction();
        try{
            $target = AssignTarget::find($id);
            $user = User::find($target->user_id);
            Utility::makeActivityLog(\Auth::user()->id,'Assign Target',$target->id,'Delete Assign Target',$user->name);
            $target->delete();
            \DB::commit();
            return redirect()->route('assign-target.index')->with('success', 'Target Deleted Successfully');
        }
        catch (\Exception $e) {
            \DB::rollBack();
            dd($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
