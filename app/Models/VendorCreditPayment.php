<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorCreditPayment extends Model
{
    protected $fillable = [
        'vendor_credit_id',
        'vendor_credit_txn_id',
        'bill_payment_txn_id',
        'bill_payment_id',
        'amount',
        'date',
        'description',
        'created_by',
        'owned_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];

    /**
     * Get the vendor credit this payment belongs to
     */
    public function vendorCredit()
    {
        return $this->belongsTo(VendorCredit::class, 'vendor_credit_id');
    }

    /**
     * Get the bill payment if linked
     */
    public function billPayment()
    {
        return $this->belongsTo(BillPayment::class, 'bill_payment_id');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
