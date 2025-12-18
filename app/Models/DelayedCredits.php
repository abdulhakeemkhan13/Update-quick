<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DelayedCredits extends Model
{
    use HasFactory;
    protected $fillable = [
        'credit_id',
        'type',
        'customer_id',
        'date',
        'total_amount',
        'remaining_balance',
        'private_note',
        'created_by',
        'owned_by',
    ];
}
