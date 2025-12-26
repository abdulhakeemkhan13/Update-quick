<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryAdjustmentLine extends Model
{
    protected $fillable = [
        'inventory_adjustment_id',
        'product_id',
        'item_name',
        'qty_change',
        'unit_value',
        'total_value',
        'description',
    ];

    /**
     * Get the parent adjustment.
     */
    public function inventoryAdjustment()
    {
        return $this->belongsTo(InventoryAdjustment::class);
    }

    /**
     * Get the product.
     */
    public function product()
    {
        return $this->belongsTo(ProductService::class, 'product_id');
    }
}
