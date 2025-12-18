<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DelayedChargeLines extends Model
{
    use HasFactory;
    protected $fillable = [
        'delayed_charge_id',
        'product_id',
        'quantity',
        'rate',
        'amount',
        'description',
        'created_by',
        'owned_by',
    ];
}
