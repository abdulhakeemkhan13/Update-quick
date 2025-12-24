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
     * Get the delayed charge that owns the line item.
     */
    public function delayedCharge()
    {
        return $this->belongsTo(DelayedCharges::class, 'delayed_charge_id');
    }

    /**
     * Get the product/service for the line item.
     */
    public function product()
    {
        return $this->belongsTo(ProductService::class, 'product_id');
    }
}
