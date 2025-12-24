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
        'total_amount',
        'description',
        'memo',
        'attachments',
        'is_invoiced',
        'created_by',
        'owned_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'attachments' => 'array',
        'is_invoiced' => 'boolean',
    ];

    /**
     * Get the customer that owns the delayed charge.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    /**
     * Get the line items for the delayed charge.
     */
    public function lines()
    {
        return $this->hasMany(DelayedChargeLines::class, 'delayed_charge_id');
    }

    /**
     * Get the creator of the delayed charge.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate total amount from line items.
     */
    public function getTotal()
    {
        return $this->lines()->sum('amount');
    }
}
