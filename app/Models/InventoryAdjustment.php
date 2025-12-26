<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryAdjustment extends Model
{
    protected $fillable = [
        'adjustment_id',
        'txn_date',
        'ref_number',
        'adjustment_account_id',
        'private_note',
        'total_amount',
        'created_by',
        'owned_by',
    ];

    protected $casts = [
        'txn_date' => 'date',
    ];

    /**
     * Get the line items for this adjustment.
     */
    public function lines()
    {
        return $this->hasMany(InventoryAdjustmentLine::class);
    }

    /**
     * Get the adjustment account.
     */
    public function adjustmentAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'adjustment_account_id');
    }
}
