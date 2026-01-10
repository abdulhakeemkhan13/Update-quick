<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorCreditProduct extends Model
{
    use HasFactory;
    protected $fillable = [
        'vendor_credit_id',
        'product_id',
        'quantity',
        'price',
        'description',
        'tax',
        'billable',
        'customer_id',
    ];
    public function product()
    {
        return $this->hasOne('App\\Models\\ProductService', 'id', 'product_id');
    }
}
