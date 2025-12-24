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
        'memo',
        'attachments',
        'created_by',
        'owned_by',
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'attachments' => 'array',
    ];

    /**
     * Get the customer that owns the delayed credit.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    /**
     * Get the line items for the delayed credit.
     */
    public function lines()
    {
        return $this->hasMany(DelayedCreditLines::class, 'delayed_credit_id');
    }

    /**
     * Get the creator of the delayed credit.
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
