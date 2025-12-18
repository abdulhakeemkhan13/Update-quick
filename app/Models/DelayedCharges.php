<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DelayedCharges extends Model
{
    use HasFactory;
    protected $fillable = [
        'charge_id',
        'customer_id',
        'date',
        'amount',
        'description',
        'is_invoiced',
        'created_by',
        'owned_by',
    ];
}
