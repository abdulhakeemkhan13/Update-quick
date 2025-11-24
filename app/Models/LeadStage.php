<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadStage extends Model
{
    protected $fillable = [
        'name',
        'pipeline_id',
        'created_by',
        'order',
    ];

    public function lead()
    {
        if(\Auth::user()->type=='company'){
            return Lead::select('leads.*')->where('leads.created_by', '=', \Auth::user()->creatorId())->where('leads.stage_id', '=', $this->id)->orderBy('leads.order')->get();
        }else{
            return Lead::select('leads.*')->join('user_leads', 'user_leads.lead_id', '=', 'leads.id')->where('user_leads.user_id', '=', \Auth::user()->id)->where('leads.stage_id', '=', $this->id)->orderBy('leads.order')->get();

        }

    }

    public function search($query,$id,$a)
    {
        $query = clone $query;
        $a=$this->id;
        if(\Auth::user()->type=='company'){
            return $b=$query->select('leads.*')->where('leads.created_by', '=', \Auth::user()->creatorId())->where('pipeline_id',$id)->where('leads.stage_id', '=', $this->id)->orderBy('leads.order')->get()->filter(function ($lead) use ($a) {
                return $lead->stage_id == $a;
            });
        }else{
            return $query->select('leads.*')->join('user_leads', 'user_leads.lead_id', '=', 'leads.id')->where('leads.created_by', '=', \Auth::user()->creatorId())->where('pipeline_id',$id)->where('user_leads.user_id', '=', \Auth::user()->id)->where('leads.stage_id', '=', $this->id)->orderBy('leads.order')->get()->filter(function ($lead) use ($a) {
                return $lead->stage_id == $a;
            });

        }

    }
}
