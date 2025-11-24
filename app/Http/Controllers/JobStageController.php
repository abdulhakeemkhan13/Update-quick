<?php

namespace App\Http\Controllers;

use App\Models\JobStage;
use App\Models\Utility;
use Illuminate\Http\Request;

class JobStageController extends Controller
{
    public function index()
    {
        if (\Auth::user()->can('manage job stage')) {
            // Only fetch stages owned by the authenticated user
            $user = \Auth::user();
            if ($user->type == 'company') {
                $stages = JobStage::where('created_by', '=', \Auth::user()->creatorId())->orderBy('order', 'asc')->get();
            } else {
                $stages = JobStage::where(function ($query) use ($user) {
                    $query->where('created_by', $user->creatorId())
                          ->orWhere('owned_by', $user->ownedId());
                })->get();
            }
            return view('jobStage.index', compact('stages'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create job stage')) {
            return view('jobStage.create');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create job stage')) {

            $validator = \Validator::make(
                $request->all(),
                ['title' => 'required']
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $jobStage = new JobStage();
            $jobStage->title = $request->title;
            $jobStage->created_by = \Auth::user()->creatorId();
            $jobStage->owned_by = \Auth::user()->ownedId();
            $jobStage->save();

            // Log activity
            Utility::makeActivityLog(\Auth::user()->id, 'Job Stage', $jobStage->id, 'Create Job Stage', $jobStage->title);

            return redirect()->back()->with('success', __('Job stage successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit(JobStage $jobStage)
    {
        if (\Auth::user()->can('edit job stage')) {
            if ($jobStage->created_by == \Auth::user()->creatorId()) {
                return view('jobStage.edit', compact('jobStage'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, JobStage $jobStage)
    {
        if (\Auth::user()->can('edit job stage')) {
            if ($jobStage->created_by == \Auth::user()->creatorId()) {

                $validator = \Validator::make(
                    $request->all(),
                    ['title' => 'required']
                );

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }

                $jobStage->title = $request->title;
                $jobStage->save();

                // Log activity
                Utility::makeActivityLog(\Auth::user()->id, 'Job Stage', $jobStage->id, 'Update Job Stage', $jobStage->title);

                return redirect()->back()->with('success', __('Job stage successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(JobStage $jobStage)
    {
        if (\Auth::user()->can('delete job stage')) {
            if ($jobStage->created_by == \Auth::user()->creatorId()) {
                // Log activity
                Utility::makeActivityLog(\Auth::user()->id, 'Job Stage', $jobStage->id, 'Delete Job Stage', $jobStage->title);

                $jobStage->delete();

                return redirect()->back()->with('success', __('Job stage successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function order(Request $request)
    {
        $post = $request->all();
        foreach ($post['order'] as $key => $item) {
            $stage = JobStage::where('id', '=', $item)->first();
            $stage->order = $key;
            $stage->save();
        }
    }
}
