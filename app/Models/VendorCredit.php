<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorCredit extends Model
{
    use HasFactory;
    
    // Status constants
    const STATUS_OPEN = 'Open';
    const STATUS_PARTIALLY_PAID = 'Partially Paid';
    const STATUS_PAID = 'Paid';
    
    protected $fillable = [
        'vendor_credit_id',
        'vender_id',
        'date',
        'amount',
        'memo',
        'status',
        'created_by',
        'owned_by',
    ];

    /**
     * Get the vendor credit payments (how this credit was applied)
     */
    public function payments()
    {
        return $this->hasMany(VendorCreditPayment::class, 'vendor_credit_id');
    }

    /**
     * Get the vendor that owns the credit
     */
    public function vendor()
    {
        return $this->belongsTo(Vender::class, 'vender_id');
    }

    /**
     * Get the bills this credit has been applied to
     */
    public function bills()
    {
        return $this->belongsToMany(Bill::class, 'bill_credit_applications', 'vendor_credit_id', 'bill_id')
                    ->withPivot('amount_applied', 'applied_by')
                    ->withTimestamps();
    }

    /**
     * Get the user who created this credit
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get total amount applied from vendor_credit_payments
     */
    public function getTotalAppliedAttribute()
    {
        return $this->payments()->sum('amount');
    }

    /**
     * Get remaining amount (total - applied)
     */
    public function getRemainingAmountAttribute()
    {
        return max(0, $this->amount - $this->total_applied);
    }

    /**
     * Check if credit is available for use
     */
    public function isAvailable()
    {
        return $this->status !== self::STATUS_PAID && $this->remaining_amount > 0;
    }

    /**
     * Update the status based on applied payments
     */
    public function updatePaymentStatus()
    {
        $totalApplied = $this->payments()->sum('amount');
        $creditAmount = (float) $this->amount;

        if ($totalApplied >= $creditAmount) {
            $this->status = self::STATUS_PAID;
        } elseif ($totalApplied > 0) {
            $this->status = self::STATUS_PARTIALLY_PAID;
        } else {
            $this->status = self::STATUS_OPEN;
        }

        $this->save();
        return $this->status;
    }

    /**
     * Apply credit to a bill
     */
    public function applyToBill(Bill $bill, $amount)
    {
        if (!$this->isAvailable()) {
            throw new \Exception('Credit is not available');
        }

        if ($amount > $this->remaining_amount) {
            throw new \Exception('Amount exceeds remaining credit');
        }

        // Record the application
        $this->bills()->attach($bill->id, [
            'amount_applied' => $amount,
            'applied_by' => auth()->id(),
        ]);

        // Update status
        $this->updatePaymentStatus();

        return true;
    }

    /**
     * Generate next credit number
     */
    public static function generateCreditNumber()
    {
        $prefix = 'VC-';
        $lastCredit = static::where('credit_number', 'like', $prefix . '%')
                           ->orderBy('created_at', 'desc')
                           ->first();

        if ($lastCredit) {
            $lastNumber = (int) str_replace($prefix, '', $lastCredit->credit_number);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
