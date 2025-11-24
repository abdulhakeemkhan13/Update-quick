<?php

namespace App\Http\Controllers;

use App\Models\InterviewSchedule;
use App\Models\JobApplication;
use App\Models\JobStage;
use App\Models\User;
use App\Models\Utility;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\GoogleCalendar\Event as GoogleEvent;

class InterviewScheduleController extends Controller
{

    public function index()
    {
        $transdate = date('Y-m-d', time());

        if (\Auth::user()->type == 'company') {
            $schedules   = InterviewSchedule::where('created_by', \Auth::user()->creatorId())->get();
        } else {
            $schedules   = InterviewSchedule::where('owned_by', \Auth::user()->ownedId())->get();
        }
        $arrSchedule = [];

        foreach($schedules as $schedule)
        {
            $arr['id']     = $schedule['id'];
            $arr['title']  = !empty($schedule->applications) ? !empty($schedule->applications->jobs) ? $schedule->applications->jobs->title : '' : '';
            $arr['start']  = $schedule['date'];
            $arr['className'] = 'event-primary';
            $arr['url']    = route('interview-schedule.show', $schedule['id']);
            $arrSchedule[] = $arr;
        }
        $arrSchedule = str_replace('"[', '[', str_replace(']"', ']', json_encode($arrSchedule)));

        return view('interviewSchedule.index', compact('arrSchedule', 'schedules','transdate'));
    }

    public function create($candidate=0)
    {
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
        $employees = User::where($column, $ownerId)->where('type', 'employee')->where('type','!=','company')->where('type','!=','branch')->where('type','!=','client')->orWhere('id', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $employees->prepend('--', '');

        $candidates = JobApplication::where($column, $ownerId)->get()->pluck('name', 'id');
        $candidates->prepend('--', '');

        $settings = Utility::settings();

        return view('interviewSchedule.create', compact('employees', 'candidates','candidate','settings'));
    }

    public function store(Request $request)
    {
        if(\Auth::user()->can('create interview schedule'))
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'candidate' => 'required',
                                   'employee' => 'required',
                                   'date' => 'required',
                                   'time' => 'required',
                               ]
            );

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }


            $schedule             = new InterviewSchedule();
            $schedule->candidate  = $request->candidate;
            $schedule->employee   = $request->employee;
            $schedule->date       = $request->date;
            $schedule->time       = $request->time;
            $schedule->comment    = $request->comment;
            $schedule->created_by = \Auth::user()->creatorId();
            $schedule->owned_by = \Auth::user()->ownedId();
            $schedule->save();

            //For Google Calendar
            if($request->get('synchronize_type')  == 'google_calender')
            {
                $type ='interview_schedule';
                $request1=new InterviewSchedule();
                $request1->title=$request->comment;
                $request1->start_date=$request->date;
                $request1->end_date=$request->date;

                Utility::addCalendarData($request1 , $type);

            }
            Utility::makeActivityLog(\Auth::user()->id,'Interview Schedule',$schedule->id,'Create Interview Schedule',$schedule->comment);
            return redirect()->back()->with('success', __('Interview schedule successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(InterviewSchedule $interviewSchedule)
    {
        $stages=JobStage::where('created_by',\Auth::user()->creatorId())->get();

        return view('interviewSchedule.show', compact('interviewSchedule','stages'));
    }

    public function edit(InterviewSchedule $interviewSchedule)
    {
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
        $employees = User::where($column,$ownerId)->where('type', 'employee')->orWhere('id', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $employees->prepend('--', '');

        $candidates = JobApplication::where($column,$ownerId)->get()->pluck('name', 'id');
        $candidates->prepend('--', '');

        return view('interviewSchedule.edit', compact('employees', 'candidates', 'interviewSchedule'));
    }

    public function update(Request $request, InterviewSchedule $interviewSchedule)
    {
        if(\Auth::user()->can('edit interview schedule'))
        {
            $validator = \Validator::make(
                $request->all(), [
                    'candidate' => 'required',
                    'employee' => 'required',
                    'date' => 'required',
                    'time' => 'required',
                ]
            );

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $interviewSchedule->candidate = $request->candidate;
            $interviewSchedule->employee  = $request->employee;
            $interviewSchedule->date      = $request->date;
            $interviewSchedule->time      = $request->time;
            $interviewSchedule->comment   = $request->comment;
            $interviewSchedule->save();
            // activity log 
            Utility::makeActivityLog(\Auth::user()->id,'Interview Schedule',$interviewSchedule->id,'Update Interview Schedule',$interviewSchedule->comment);
            return redirect()->back()->with('success', __('Interview schedule successfully updated.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(InterviewSchedule $interviewSchedule)
    {
        // activity log 
        Utility::makeActivityLog(\Auth::user()->id,'Interview Schedule',$interviewSchedule->id,'Delete Interview Schedule',$interviewSchedule->comment);
        $interviewSchedule->delete();

        return redirect()->back()->with('success', __('Interview schedule successfully deleted.'));
    }


    //For Google Calender
    public function get_interview_data(Request $request)
    {
        if($request->get('calender_type') == 'goggle_calender')
        {
            $type ='interview_schedule';
            $arrayJson =  Utility::getCalendarData($type);
        }
        else
        {
            $data =InterviewSchedule::where('created_by', '=', \Auth::user()->creatorId())->get();
            $arrayJson = [];
            foreach($data as $val)
            {
                $arrayJson[] = [
                    "id"=> $val->id,
                    "title" => $val->comment,
                    "start" => $val->date.' '.$val->time,
                    "className" =>'event-primary',
                    "textColor" => '#51459d',
                    'url'      => route('interview-schedule.show', $val->id),
                    "allDay" => false,
                ];
            }
        }

        return $arrayJson;
    }

}
