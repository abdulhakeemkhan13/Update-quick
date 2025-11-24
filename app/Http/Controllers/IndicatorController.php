<?php

namespace App\Http\Controllers;
use App\Models\Branch;
use App\Models\Competencies;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Indicator;
use App\Models\PerformanceType;
use App\Models\Utility;
use Illuminate\Http\Request;

class IndicatorController extends Controller
{
    public function index()
    {
        if (!\Auth::user()->can('manage indicator')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

        $indicatorsQuery = Indicator::with(['branches', 'departments', 'designations', 'user'])
            ->where($column, $ownerId);

        if ($user->type === 'Employee') {
            $employee = Employee::where('user_id', $user->id)->first();
            if (!$employee) {
                return redirect()->back()->with('error', __('Employee details not found.'));
            }
            $indicatorsQuery->where('branch', $employee->branch_id)
                ->where('department', $employee->department_id)
                ->where('designation', $employee->designation_id);
        }

        $indicators = $indicatorsQuery->get();

        return view('indicator.index', compact('indicators'));
    }


    public function create()
    {
        $user = \Auth::user();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
        $ownerId = ($user->type == 'company') ? $user->creatorId() : $user->ownedId();
        $brances = Branch::where($column, $ownerId)->get()->pluck('name', 'id');
        $performance = PerformanceType::where($column, $ownerId)->get();
        $departments = Department::where($column, $ownerId)->get()->pluck('name', 'id');
        $departments->prepend('Select Department', '');
        return view('indicator.create', compact('brances', 'departments', 'performance'));
    }



    public function store(Request $request)
    {
        if (\Auth::user()->can('create indicator')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'branch' => 'required',
                    'department' => 'required',
                    'designation' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }


            $indicator = new Indicator();
            $indicator->branch = $request->branch;
            $indicator->department = $request->department;
            $indicator->designation = $request->designation;

            $indicator->rating = json_encode($request->rating, true);

            if (\Auth::user()->type == 'company') {
                $indicator->created_user = \Auth::user()->creatorId();
            } else {
                $indicator->created_user = \Auth::user()->id;
            }

            $indicator->created_by = \Auth::user()->creatorId();
            $indicator->owned_by = \Auth::user()->ownedId();
            $indicator->save();
            Utility::makeActivityLog(\Auth::user()->id, 'Indicator', $indicator->id, 'Create Indicator', 'Indicator');
            return redirect()->route('indicator.index')->with('success', __('Indicator successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function show(Indicator $indicator)
    {
        $user = \Auth::user();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
        $ownerId = ($user->type == 'company') ? $user->creatorId() : $user->ownedId();
        $performance = PerformanceType::where($column, $ownerId)->get();
        $ratings = json_decode($indicator->rating, true);
        return view('indicator.show', compact('indicator', 'ratings', 'performance'));
    }



    public function edit(Indicator $indicator)
    {
        if (\Auth::user()->can('edit indicator')) {
            $user = \Auth::user();
            $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
            $ownerId = ($user->type == 'company') ? $user->creatorId() : $user->ownedId();
            $performance = PerformanceType::where($column, '=', $ownerId)->get();
            $brances = Branch::where($column, '=', $ownerId)->get()->pluck('name', 'id');
            $departments = Department::where($column, '=', $ownerId)->get()->pluck('name', 'id');
            $departments->prepend('Select Department', '');
            $ratings = json_decode($indicator->rating, true);
            return view('indicator.edit', compact('brances', 'departments', 'performance', 'indicator', 'ratings'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function update(Request $request, Indicator $indicator)
    {

        if (\Auth::user()->can('edit indicator')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'branch' => 'required',
                    'department' => 'required',
                    'designation' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }


            $indicator->branch = $request->branch;
            $indicator->department = $request->department;
            $indicator->designation = $request->designation;

            $indicator->rating = json_encode($request->rating, true);
            $indicator->save();
            Utility::makeActivityLog(\Auth::user()->id, 'Indicator', $indicator->id, 'Update Indicator', 'Indicator');
            return redirect()->route('indicator.index')->with('success', __('Indicator successfully updated.'));
        }
    }


    public function destroy(Indicator $indicator)
    {
        if (\Auth::user()->can('delete indicator')) {
            if ($indicator->created_by == \Auth::user()->creatorId()) {
                Utility::makeActivityLog(\Auth::user()->id, 'Indicator', $indicator->id, 'Delete Indicator', 'Indicator');
                $indicator->delete();

                return redirect()->route('indicator.index')->with('success', __('Indicator successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


}
