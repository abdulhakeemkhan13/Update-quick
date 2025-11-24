<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function index()
    {

        if (\Auth::user()->can('manage designation')) {
            $user = \Auth::user();
            if ($user->type == 'company') {
                $designations = Designation::where('created_by', '=', \Auth::user()->creatorId())->get();
            } else {
                $designations = Designation::where(function ($query) use ($user) {
                    $query->where('created_by', $user->creatorId())
                        ->orWhere('owned_by', $user->ownedId());
                })->get();
            }
            return view('designation.index', compact('designations'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create designation')) {
            $user = \Auth::user();
            if ($user->type == 'company') {
                $branchs = Branch::where('created_by', $user->creatorId())->get()->pluck('name', 'id');
                $branchs->prepend('Select Branch', '');
                $departments = Department::where('created_by', $user->creatorId())->get();
            } else {
                $branchs = Branch::where('id', \Auth::user()->id)->get()->pluck('name', 'id');
                if ($branchs) {
                    $branchs = User::where('created_by', $user->creatorId())->where('id', $user->id)->get()->pluck('name', 'id');
                }
                $departments = Department::where(function ($query) use ($user) {
                    $query->where('created_by', $user->creatorId())
                        ->orWhere('owned_by', $user->ownedId());
                })->get();
            }
            $departments = $departments->pluck('name', 'id');

            return view('designation.create', compact('departments', 'branchs'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (!\Auth::user()->can('create designation')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => __('Permission denied.')], 403);
            }
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = \Validator::make($request->all(), [
            'branch_id'     => 'required',
            'department_id' => 'required',
            'name'          => 'required|max:20',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors'  => $validator->errors(),
                ], 422);
            }
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        $designation                = new Designation();
        $designation->branch_id     = $request->branch_id;
        $designation->department_id = $request->department_id;
        $designation->name          = $request->name;
        $designation->created_by    = \Auth::user()->creatorId();
        $designation->owned_by      = \Auth::user()->ownedId();
        $designation->save();

        Utility::makeActivityLog(\Auth::user()->id, 'Designation', $designation->id, 'Create Designation', $designation->name);

        // AJAX success → return JSON for appending/selecting without refresh
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'id'            => $designation->id,
                'name'          => $designation->name,
                'branch_id'     => $designation->branch_id,
                'department_id' => $designation->department_id,
                'data'          => $designation,
                'success'       => true,
            ], 201);
        }

        // Non-AJAX → keep existing redirect
        return redirect()->route('designation.index')->with('success', __('Designation  successfully created.'));
    }


    public function show(Designation $designation)
    {
        return redirect()->route('designation.index');
    }

    public function edit(Designation $designation)
    {

        if (\Auth::user()->can('edit designation')) {
            if ($designation->created_by == \Auth::user()->creatorId()) {
                if (!empty($designation->branch_id)) {
                    if (\Auth::user()->type == 'company') {
                        $branchs = Branch::where('id', $designation->branch_id)->where('created_by', '=', \Auth::user()->creatorId())->first()->pluck('name', 'id');
                    } else {
                        $branch = Branch::where('id', $designation->branch_id)->where('owned_by', '=', \Auth::user()->ownedId())->first();
                        $branchs[$branch->id] = $branch->name;
                    }
                } else {
                    if (\Auth::user()->type == 'company') {
                        $branchs = Branch::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                    } else {
                        $branchs = Branch::where('owned_by', '=', \Auth::user()->ownedId())->get()->pluck('name', 'id');
                    }
                }
                $departments = Department::where('id', $designation->department_id)->first()->pluck('name', 'id');
                return view('designation.edit', compact('designation', 'departments', 'branchs'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, Designation $designation)
    {
        // dd($request->all());
        if (\Auth::user()->can('edit designation')) {
            if ($designation->created_by == \Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'branch_id' => 'required',
                        'department_id' => 'required',
                        'name' => 'required|max:20',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }
                try {
                    $branch = Department::where('id', $request->department_id)->where('created_by', '=', \Auth::user()->creatorId())->first()->branch->id;
                } catch (\Exception $e) {
                    $branch = null;
                }
                $designation->name          = $request->name;
                $designation->branch_id     = $branch;
                $designation->department_id = $request->department_id;
                $designation->save();
                Utility::makeActivityLog(\Auth::user()->id, 'Designation', $designation->id, 'Update Designation', $designation->name);
                return redirect()->route('designation.index')->with('success', __('Designation  successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(Designation $designation)
    {
        if (\Auth::user()->can('delete designation')) {
            if ($designation->created_by == \Auth::user()->creatorId()) {
                Utility::makeActivityLog(\Auth::user()->id, 'Designation', $designation->id, 'Delete Designation', $designation->name);
                $designation->delete();

                return redirect()->route('designation.index')->with('success', __('Designation successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
