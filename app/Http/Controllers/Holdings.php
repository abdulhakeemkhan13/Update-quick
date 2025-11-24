<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holdings extends Model
{
    use HasFactory;
    protected $fillable = [
        'institution_id',
       'holding_id',
       'user_id',
       'cost_basis',
       'price',
    ];
}
