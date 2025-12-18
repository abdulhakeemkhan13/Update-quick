<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

    class Tax extends Model
    {
        protected $fillable = [
           'taxid', 'name', 'rate', 'created_by','owned_by'
        ];
    }
