<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'credit_number',
        'credit_date',
        'amount',
        'remaining_amount',
        'reason',
        'status',
        'created_by',
        'owned_by',
    ];

    protected $casts = [
        'credit_date' => 'date',
        'amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    /**
     * Get the vendor that owns the credit
     */
    public function vendor()
    {
        return $this->belongsTo(Vender::class, 'vendor_id');
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
     * Check if credit is available for use
     */
    public function isAvailable()
    {
        return $this->status === 'available' && $this->remaining_amount > 0;
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

        // Update remaining amount
        $this->remaining_amount -= $amount;
        
        if ($this->remaining_amount <= 0) {
            $this->status = 'applied';
        }
        
        $this->save();

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
