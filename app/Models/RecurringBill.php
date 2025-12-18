<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecurringBill extends Model
{
    protected $fillable = [
        'bill_id',
        'frequency',
        'next_date',
        'end_date',
        'active',
        'template_fields',
    ];

    protected $casts = [
        'template_fields' => 'array',
        'active' => 'boolean',
        'next_date' => 'date',
        'end_date' => 'date',
    ];

    public function bill()
    {
        return $this->belongsTo('App\Models\Bill');
    }
}
