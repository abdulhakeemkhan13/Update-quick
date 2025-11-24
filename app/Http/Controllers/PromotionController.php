<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use App\Models\Employee;
use App\Models\Promotion;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PromotionController extends Controller
{
    public function index()
    {
        if(\Auth::user()->can('manage promotion'))
        {
            if(Auth::user()->type == 'Employee')
            {
                $emp        = Employee::where('user_id', '=', \Auth::user()->id)->first();
                $promotions = Promotion::where('created_by', '=', \Auth::user()->creatorId())->where('employee_id', '=', $emp->id)->with(['designation','employee'])->get();
            }
            else
            {
                $user = \Auth::user();
                $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
                $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
                $promotions = Promotion::where($column, '=',$ownerId)->with(['designation','employee'])->get();
            }

            return view('promotion.index', compact('promotions'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if(\Auth::user()->can('create promotion'))
        {
            $user = \Auth::user();
            $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
            $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
            $designations = Designation::where($column,$ownerId)->get()->pluck('name', 'id');
            $employees    = Employee::where($column,$ownerId)->get()->pluck('name', 'id');

            return view('promotion.create', compact('employees', 'designations'));
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if(\Auth::user()->can('create promotion'))
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'employee_id' => 'required',
                                   'designation_id' => 'required',
                                   'promotion_title' => 'required',
                                   'promotion_date' => 'required',
                               ]
            );

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $promotion                  = new Promotion();
            $promotion->employee_id     = $request->employee_id;
            $promotion->designation_id  = $request->designation_id;
            $promotion->promotion_title = $request->promotion_title;
            $promotion->promotion_date  = $request->promotion_date;
            $promotion->description     = $request->description;
            $promotion->created_by      = \Auth::user()->creatorId();
            $promotion->owned_by      = \Auth::user()->ownedId();
            $promotion->save();
            $userarr = [
                \Auth::user()->id,
                $promotion->employee->report_to,
                $promotion->employee->id,
            ];
            // dd($userarr);
            $dataarr = [
                "updated_by" => Auth::user()->id,
                "data_id" => $promotion->id,
                "promotion"=>$promotion->promotion_title,
                "name" => @$promotion->employee->name,
            ];
            foreach($userarr as $key => $notifyto){
                Utility::makeNotification($notifyto,'promotion',$dataarr,$promotion->id,'Promoted as ',\Auth::user()->name);
            }
            $setings = Utility::settings();
            if($setings['promotion_sent'] == 1)
            {
                $employee               = Employee::find($promotion->employee_id);
                $designation            = Designation::find($promotion->designation_id);
                $promotion->designation = $designation->name;
                $promotionArr = [
                    'employee_name'=>$employee->name,
                    'promotion_designation'  =>$promotion->designation,
                    'promotion_title'  =>$promotion->promotion_title,
                    'promotion_date'  =>$promotion->promotion_date,

                ];

                $resp = Utility::sendEmailTemplate('promotion_sent', [$employee->email], $promotionArr);
                Utility::makeActivityLog(\Auth::user()->id,'Promotion',$promotion->id,'Create Promotion',$employee->name);
                return redirect()->route('promotion.index')->with('success', __('Promotion  successfully created.'). ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
                
                
            }
            
            Utility::makeActivityLog(\Auth::user()->id,'Promotion',$promotion->id,'Create Promotion',$promotion->title);
            return redirect()->route('promotion.index')->with('success', __('Promotion  successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(Promotion $promotion)
    {
        return redirect()->route('promotion.index');
    }

    public function edit(Promotion $promotion)
    {
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
        $designations = Designation::where($column,$ownerId)->get()->pluck('name', 'id');
        $employees    = Employee::where($column,$ownerId)->get()->pluck('name', 'id');
        if(\Auth::user()->can('edit promotion'))
        {
            if($promotion->created_by == \Auth::user()->creatorId())
            {
                return view('promotion.edit', compact('promotion', 'employees', 'designations'));
            }
            else
            {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, Promotion $promotion)
    {
        if(\Auth::user()->can('edit promotion'))
        {
            if($promotion->created_by == \Auth::user()->creatorId())
            {
                $validator = \Validator::make(
                    $request->all(), [
                                       'employee_id' => 'required',
                                       'designation_id' => 'required',
                                       'promotion_title' => 'required',
                                       'promotion_date' => 'required',
                                   ]
                );

                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $promotion->employee_id     = $request->employee_id;
                $promotion->designation_id  = $request->designation_id;
                $promotion->promotion_title = $request->promotion_title;
                $promotion->promotion_date  = $request->promotion_date;
                $promotion->description     = $request->description;
                $promotion->save();
                Utility::makeActivityLog(\Auth::user()->id,'Promotion',$promotion->id,'Update Promotion',$promotion->title);
                return redirect()->route('promotion.index')->with('success', __('Promotion successfully updated.'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(Promotion $promotion)
    {
        if(\Auth::user()->can('delete promotion'))
        {
            if($promotion->created_by == \Auth::user()->creatorId())
            {
                Utility::makeActivityLog(\Auth::user()->id,'Promotion',$promotion->id,'Delete Promotion',$promotion->title);
                $promotion->delete();

                return redirect()->route('promotion.index')->with('success', __('Promotion successfully deleted.'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
