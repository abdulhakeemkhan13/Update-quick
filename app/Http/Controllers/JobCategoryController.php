<?php

namespace App\Http\Controllers;

use App\Models\JobCategory;
use App\Models\Utility;
use Illuminate\Http\Request;

class JobCategoryController extends Controller
{
    public function index()
    {
        if (\Auth::user()->can('manage job category')) {
            $user = \Auth::user();
            if ($user->type == 'company') {
                $categories = JobCategory::where('created_by', '=', \Auth::user()->creatorId())->get();
            } else {
                $categories = JobCategory::where(function ($query) use ($user) {
                    $query->where('created_by', $user->creatorId())
                          ->orWhere('owned_by', $user->ownedId());
                })->get();
            }
            return view('jobCategory.index', compact('categories'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create job category')) {
            return view('jobCategory.create');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create job category')) {

            $validator = \Validator::make(
                $request->all(),
                ['title' => 'required']
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $jobCategory = new JobCategory();
            $jobCategory->title = $request->title;
            $jobCategory->created_by = \Auth::user()->creatorId();
            $jobCategory->owned_by = \Auth::user()->ownedId();
            $jobCategory->save();

            // Activity log for creation
            Utility::makeActivityLog(\Auth::user()->id, 'Job Category', $jobCategory->id, 'Create Job Category', $jobCategory->title);

            return redirect()->back()->with('success', __('Job category successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(JobCategory $jobCategory)
    {
        // Show method can be left empty or redirected
        return redirect()->route('jobcategory.index');
    }

    public function edit(JobCategory $jobCategory)
    {
        if (\Auth::user()->can('edit job category')) {
            if ($jobCategory->created_by == \Auth::user()->creatorId()) {
                return view('jobCategory.edit', compact('jobCategory'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, JobCategory $jobCategory)
    {
        if (\Auth::user()->can('edit job category')) {
            if ($jobCategory->created_by == \Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    ['title' => 'required']
                );

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }

                $jobCategory->title = $request->title;
                $jobCategory->save();

                // Activity log for update
                Utility::makeActivityLog(\Auth::user()->id, 'Job Category', $jobCategory->id, 'Update Job Category', $jobCategory->title);

                return redirect()->back()->with('success', __('Job category successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(JobCategory $jobCategory)
    {
        if (\Auth::user()->can('delete job category')) {
            if ($jobCategory->created_by == \Auth::user()->creatorId()) {
                // Activity log for deletion
                Utility::makeActivityLog(\Auth::user()->id, 'Job Category', $jobCategory->id, 'Delete Job Category', $jobCategory->title);

                $jobCategory->delete();

                return redirect()->back()->with('success', __('Job category successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
