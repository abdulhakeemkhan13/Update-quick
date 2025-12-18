<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DelayedCreditLines extends Model
{
    use HasFactory;
    protected $fillable = [
        'delayed_credit_id',
        'product_id',
        'quantity',
        'rate',
        'amount',
        'description',
        'created_by',
        'owned_by',
    ];
}
