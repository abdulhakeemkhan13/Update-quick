<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    protected $fillable = [
        'name','pipeline_id','created_by','order'
    ];

    public function deals(){
        if(\Auth::user()->type == 'client'){
            return Deal::select('deals.*')->join('client_deals','client_deals.deal_id','=','deals.id')->where('client_deals.client_id', '=', \Auth::user()->id)->where('deals.stage_id', '=', $this->id)->orderBy('deals.order')->get();
        }else {
            return Deal::select('deals.*')->join('user_deals', 'user_deals.deal_id', '=', 'deals.id')->where('user_deals.user_id', '=', \Auth::user()->id)->where('deals.stage_id', '=', $this->id)->orderBy('deals.order')->get();
        }
    }
    public function search($query,$id,$a)
    {
        $query = clone $query;
        $a=$this->id;

        if(\Auth::user()->type=='client'){
            return $query->select('deals.*')->join('client_deals','client_deals.deal_id','=','deals.id')->where('client_deals.client_id', '=', \Auth::user()->id)->where('pipeline_id',$id)->where('deals.stage_id', '=', $this->id)->orderBy('deals.order')->get()->filter(function ($deal) use ($a) {
                return $deal->stage_id == $a;
            });
        }else{
            if(\Auth::user()->type=='company'){
                return $query->select('deals.*')->where('deals.created_by', '=', \Auth::user()->creatorId())->where('pipeline_id',$id)->where('deals.stage_id', '=', $this->id)->orderBy('deals.order')->get()->filter(function ($deal) use ($a) {
                    return $deal->stage_id == $a;
                });
            }else{
                return $query->select('deals.*')->join('user_deals', 'user_deals.deal_id', '=', 'deals.id')->where('user_deals.user_id', '=', \Auth::user()->id)->where('pipeline_id',$id)->where('deals.stage_id', '=', $this->id)->orderBy('deals.order')->get()->filter(function ($deal) use ($a) {
                    return $deal->stage_id == $a;
                });
            }
        }

    }
}
