<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    protected $fillable = [
        'bill_id',
        'vender_id',
        'bill_date',
        'due_date',
        'send_date',
        'category_id',
        'ref_number',
        'status',
        'shipping_display',
        'discount_apply',
        'created_by',
        'workspace',
        'type',
        'user_type',
        'owned_by',
        'terms',
        'currency',
        'exchange_rate',
        'subtotal',
        'discount',
        'discount_type',
        'tax_total',
        'shipping',
        'adjustments',
        'total',
        'paid_amount',
        'balance_due',
        'notes',
    ];

    public static $statues = [
        'Draft', // 0
        'Sent', // 1
        'Unpaid', // 2
        'Partialy Paid', // 3
        'Paid', // 4
        'Pending Approval', // 5
        'Approved', // 6
        'Rejected', // 7
    ];

    public function customer()
    {
        return $this->hasOne('App\\Models\\Customer', 'id', 'vender_id');
    }

    public function employee()
    {
        return $this->hasOne('App\\Models\\Employee', 'id', 'vender_id');
    }

    public function vender()
    {
        return $this->hasOne('App\\Models\\Vender', 'id', 'vender_id');
    }

    /**
     * Get the selected payee value for dropdown selection (format: type_id)
     * This method handles cases where user_type might not be set
     */
    public function getSelectedPayee()
    {
        // If user_type and vender_id are set, use them directly
        if (!empty($this->user_type) && !empty($this->vender_id)) {
            // Normalize user_type to lowercase for consistent comparison
            return strtolower($this->user_type) . '_' . $this->vender_id;
        }
        
        // If user_type is not set but vender_id exists, try to detect the type
        if (!empty($this->vender_id)) {
            // Check if it's a vendor
            if (\App\Models\Vender::find($this->vender_id)) {
                return 'vendor_' . $this->vender_id;
            }
            // Check if it's a customer
            if (\App\Models\Customer::find($this->vender_id)) {
                return 'customer_' . $this->vender_id;
            }
            // Check if it's an employee
            if (\App\Models\Employee::find($this->vender_id)) {
                return 'employee_' . $this->vender_id;
            }
        }
        
        return '';
    }

    public function tax()
    {
        return $this->hasOne('App\\Models\\Tax', 'id', 'tax_id');
    }

    public function accounts()
    {
        return $this->hasMany('App\\Models\\BillAccount', 'ref_id', 'id')->orderBy('order', 'asc');
    }

    public function payments()
    {
        return $this->hasMany('App\\Models\\BillPayment', 'bill_id', 'id');
    }

    /**
     * Get the vendor credits applied to this bill
     */
    public function vendorCredits()
    {
        return $this->belongsToMany(VendorCredit::class, 'bill_credit_applications', 'bill_id', 'vendor_credit_id')
                    ->withPivot('amount_applied', 'applied_by')
                    ->withTimestamps();
    }

    /**
     * Get the total vendor credits applied
     */
    public function getTotalCreditsApplied()
    {
        return $this->vendorCredits()->sum('bill_credit_applications.amount_applied');
    }

    public function getSubTotal()
    {
        $subTotal = 0;

        foreach($this->items as $product)
        {
            $subTotal += ($product->price * $product->quantity);
        }
        
        $accountTotal = 0;
        foreach ($this->accounts as $account)
        {
            $accountTotal += $account->price;
        }

        return $subTotal + $accountTotal;
    }

    public function items()
    {
        return $this->hasMany('App\\Models\\BillProduct', 'bill_id', 'id')->orderBy('order', 'asc');
    }

    public function getTotalTax()
    {
        $taxData = Utility::getTaxData();
        $totalTax = 0;
        foreach($this->items as $product)
        {
            $taxArr = explode(',', $product->tax);
            $taxes = 0;
            foreach ($taxArr as $tax) {
                $taxes += !empty($taxData[$tax]['rate']) ? $taxData[$tax]['rate'] : 0;
            }

            $totalTax += ($taxes / 100) * ($product->price * $product->quantity);
        }

        return $totalTax;
    }
    
    public function getTotalDiscount()
    {
        $totalDiscount = 0;
        foreach($this->items as $product)
        {
            $totalDiscount += $product->discount;
        }

        return $totalDiscount;
    }

    public function getAccountTotal()
    {
        $accountTotal = 0;
        foreach ($this->accounts as $account)
        {
            $accountTotal += $account->price;
        }

        return $accountTotal;
    }

    public function getTotal()
    {
        // return ($this->getSubTotal() - $this->getTotalDiscount()) + $this->getTotalTax();
        return ($this->getSubTotal() - $this->getTotalDiscount());
    }

    public function getDue()
    {
        $due = 0;
        foreach($this->payments as $payment)
        {
            $due += $payment->amount;
        }

        return ($this->getTotal() - $due) - ($this->billTotalDebitNote());
    }

    public function category()
    {
        return $this->hasOne('App\\Models\\ProductServiceCategory', 'id', 'category_id');
    }

    public function debitNote()
    {
        return $this->hasMany('App\\Models\\DebitNote', 'bill', 'id');
    }

    public function billTotalDebitNote()
    {
        return $this->debitNote->sum('amount');
    }

    public function lastPayments()
    {
        return $this->hasOne('App\\Models\\BillPayment', 'id', 'bill_id');
    }

    public function taxes()
    {
        return $this->hasOne('App\\Models\\Tax', 'id', 'tax');
    }
}
