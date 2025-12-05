<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillProduct extends Model
{
    protected $fillable = [
        'bill_id',
        'product_id',
        'customer_id',
        'quantity',
        'tax',
        'discount',
        'discount_type',
        'price',
        'description',
        'account_id',
        'tax_rate_id',
        'class_id',
        'project_id',
        'line_total',
        'order',
    ];

    public function product()
    {
        return $this->hasOne('App\\Models\\ProductService', 'id', 'product_id');
    }

    public function chartAccount()
    {
        return $this->hasOne('App\\Models\\ChartOfAccount', 'id', 'chart_account_id');
    }

    /**
     * Get the customer assigned to this line item
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the tax rate for this line item
     */
    public function taxRate()
    {
        return $this->belongsTo(Tax::class, 'tax_rate_id');
    }

    /**
     * Get the account for this line item
     */
    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
