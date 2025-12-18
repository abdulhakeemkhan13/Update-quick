<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorCreditAccount extends Model
{
    use HasFactory;
    protected $fillable = [
        'vendor_credit_id',
        'chart_account_id',
        'price',
        'description',
        'tax',
        'billable',
        'customer_id',
    ];
}
