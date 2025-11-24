<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignTarget extends Model
{
    use HasFactory;

    //user relation
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
