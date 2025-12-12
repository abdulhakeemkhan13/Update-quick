<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    protected $fillable = [
        'credit_note_id',
        'customer',
        'date',
        'amount',
        'description',
        'created_by',
        'owned_by',
        'payment_id',
    ];

    public function customer()
    {
        return $this->hasOne('App\Models\Customer', 'customer_id', 'customer');
    }
    public function invoice()
    {
        return $this->hasOne('App\Models\Invoice', 'id', 'invoice');
    }
}
