<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderAccount extends Model
{
    protected $fillable = [
        'ref_id',
        'type',
        'chart_account_id',
        'description',
        'price',
        'tax',
        'tax_rate_id',
        'billable',
        'customer_id',
        'quantity_ordered',
        'quantity_received',
        'order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'tax' => 'boolean',
        'billable' => 'boolean',
        'quantity_ordered' => 'decimal:2',
        'quantity_received' => 'decimal:2',
    ];

    /**
     * Get the purchase order this account line belongs to
     */
    public function purchaseOrder()
    {
        return $this->belongsTo('App\\Models\\PurchaseOrder', 'ref_id');
    }

    /**
     * Get the chart of account
     */
    public function chartAccount()
    {
        return $this->hasOne('App\\Models\\ChartOfAccount', 'id', 'chart_account_id');
    }

    /**
     * Get the customer assigned to this line item (for billable expenses)
     */
    public function customer()
    {
        return $this->belongsTo('App\\Models\\Customer', 'customer_id');
    }

    /**
     * Get the tax rate for this line item
     */
    public function taxRate()
    {
        return $this->belongsTo('App\\Models\\Tax', 'tax_rate_id');
    }

    /**
     * Get remaining quantity that can be received
     */
    public function getRemainingQuantity()
    {
        return $this->quantity_ordered - $this->quantity_received;
    }

    /**
     * Check if this account line can receive more
     */
    public function canBeReceived()
    {
        return $this->quantity_received < $this->quantity_ordered;
    }
}
