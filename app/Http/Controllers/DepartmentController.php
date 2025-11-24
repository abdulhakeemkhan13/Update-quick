<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        if (\Auth::user()->can('manage department')) {
            $user = \Auth::user();

            if ($user->type == 'company') {
                $departments = Department::where('created_by', $user->creatorId())->get();
            } else {
                $departments = Department::where(function ($query) use ($user) {
                    $query->where('created_by', $user->creatorId())
                        ->orWhere('owned_by', $user->ownedId());
                })->get();
            }

            return view('department.index', compact('departments'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create department')) {
            $user = \Auth::user();

            if ($user->type == 'company') {
                $branch = Branch::where('created_by', $user->creatorId())->get()->pluck('name', 'id');
                $branch->prepend('Select Branch', '');
            } else {
                $branch = Branch::where('id', \Auth::user()->id)->get()->pluck('name', 'id');
                if ($branch) {
                    $branch = User::where('created_by', $user->creatorId())->where('id', $user->id)->get()->pluck('name', 'id');
                }
            }
            return view('department.create', compact('branch'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function store(Request $request)
    {
        if (!\Auth::user()->can('create department')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => __('Permission denied.')], 403);
            }
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required',
            'name'      => 'required|max:20',
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

        $department              = new Department();
        $department->branch_id   = $request->branch_id;
        $department->name        = $request->name;
        $department->created_by  = \Auth::user()->creatorId();
        $department->owned_by    = \Auth::user()->ownedId();
        $department->save();

        Utility::makeActivityLog(\Auth::user()->id, 'Department', $department->id, 'Create Department', $department->name);

        // AJAX success → JSON for appending/selecting without refresh
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'id'        => $department->id,
                'name'      => $department->name,
                'branch_id' => $department->branch_id,
                'data'      => $department->toArray(),
                'success'   => true,
            ], 201);
        }

        // Non-AJAX → keep existing redirect
        return redirect()->route('department.index')->with('success', __('Department  successfully created.'));
    }


    public function show(Department $department)
    {
        return redirect()->route('department.index');
    }

    public function edit(Department $department)
    {
        if (\Auth::user()->can('edit department')) {
            if ($department->created_by == \Auth::user()->creatorId()) {
                $user = \Auth::user();

                if ($user->type == 'company') {
                    $branch = Branch::where('created_by', $user->creatorId())->get()->pluck('name', 'id');
                    $branch->prepend('Select Branch', '');
                } else {
                    $branch = Branch::where('id', \Auth::user()->id)->get()->pluck('name', 'id');
                    if ($branch) {
                        $branch = User::where('created_by', $user->creatorId())->where('id', $user->id)->get()->pluck('name', 'id');
                    }
                }
                return view('department.edit', compact('department', 'branch'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, Department $department)
    {
        if (\Auth::user()->can('edit department')) {
            if ($department->created_by == \Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'branch_id' => 'required',
                        'name' => 'required|max:20',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $department->branch_id = $request->branch_id;
                $department->name = $request->name;
                $department->save();
                Utility::makeActivityLog(\Auth::user()->id, 'Department', $department->id, 'Update Department', $department->name);
                return redirect()->route('department.index')->with('success', __('Department successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(Department $department)
    {
        if (\Auth::user()->can('delete department')) {
            if ($department->created_by == \Auth::user()->creatorId()) {
                Utility::makeActivityLog(\Auth::user()->id, 'Department', $department->id, 'Delete Department', $department->name);
                $department->delete();

                return redirect()->route('department.index')->with('success', __('Department successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
