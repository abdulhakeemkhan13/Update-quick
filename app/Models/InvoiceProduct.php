<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceProduct extends Model
{
    protected $fillable = [
        'product_id',
        'invoice_id',
        'quantity',
        'tax',
        'discount',
        'total',
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

    public function invoice(){
        return $this->belongsTo('App\Models\Invoice', 'invoice_id', 'id');
    }
}
