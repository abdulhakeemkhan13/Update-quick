<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditCardPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_card_account_id',
        'bank_account_id',
        'payee_id',
        'payee_type',
        'amount',
        'payment_date',
        'reference',
        'memo',
        'attachments',
        'status',
        'cleared_status',
        'currency',
        'exchange_rate',
        'created_by',
        'owned_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'attachments' => 'array',
    ];

    /**
     * Status options
     */
    public static $statues = [
        0 => 'Draft',
        1 => 'Cleared',
        2 => 'Reconciled',
    ];

    /**
     * Get the credit card account (Chart of Account - Credit Card type)
     */
    public function creditCardAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'credit_card_account_id');
    }

    /**
     * Get the bank account used for payment
     */
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    /**
     * Get the payee (Vendor)
     */
    
    public function payee()
    {
        // if ($this->payee_type === 'vendor') {
            return $this->belongsTo(Vender::class, 'payee_id');
        // }
        // return null;
    }
       
    /**
     * Get payee name
     */
    public function getPayeeNameAttribute()
    {
        if ($this->payee_type === 'vendor' && $this->payee_id) {
            $vendor = Vender::find($this->payee_id);
            return $vendor ? $vendor->name : '-';
        }
        return '-';
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2);
    }

    /**
     * Scope for current user's company
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('created_by', $companyId);
    }
}
