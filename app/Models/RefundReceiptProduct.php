<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundReceiptProduct extends Model
{
    protected $fillable = [
        'product_id',
        'refund_receipt_id',
        'quantity',
        'tax',
        'discount',
        'price',
        'description',
        'taxable',
        'item_tax_price',
        'item_tax_rate',
        'amount',
    ];

    public function product(){
        return $this->hasOne('App\Models\ProductService', 'id', 'product_id');
    }

    public function refundReceipt(){
        return $this->belongsTo('App\Models\RefundReceipt', 'refund_receipt_id', 'id');
    }
}
