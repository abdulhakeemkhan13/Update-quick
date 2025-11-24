<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkFlowAction extends Model
{
    use HasFactory;
    protected $fillable = ['workflow_id','level_id','node_id','node_actual_id','type','inputs','outputs','assigned_users','applied_conditions','status'];

}
