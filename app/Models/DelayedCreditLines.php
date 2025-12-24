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
        'tax',
        'created_by',
        'owned_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'tax' => 'boolean',
    ];

    /**
     * Get the delayed credit that owns the line item.
     */
    public function delayedCredit()
    {
        return $this->belongsTo(DelayedCredits::class, 'delayed_credit_id');
    }

    /**
     * Get the product/service for the line item.
     */
    public function product()
    {
        return $this->belongsTo(ProductService::class, 'product_id');
    }
}
