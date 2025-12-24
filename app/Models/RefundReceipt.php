<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundReceipt extends Model
{
    protected $fillable = [
        'refund_receipt_id',
        'customer_id',
        'customer_email',
        'issue_date',
        'ref_number',
        'payment_method',
        'refund_from',
        'location_of_sale',
        'billing_address',
        'status',
        'category_id',
        'created_by',
        'owned_by',
        'subtotal',
        'taxable_subtotal',
        'discount_type',
        'discount_value',
        'total_discount',
        'sales_tax_rate',
        'total_tax',
        'sales_tax_amount',
        'total_amount',
        'total_amount_refunded',
        'logo',
        'attachments',
        'memo',
        'statement_memo',
        'voucher_id',
    ];

    public static $statues = [
        'Draft', // 0
        'Sent', // 1
        'Approved', // 2
    ];

    public function items()
    {
        return $this->hasMany('App\Models\RefundReceiptProduct', 'refund_receipt_id', 'id');
    }

    public function customer()
    {
        return $this->hasOne('App\Models\Customer', 'id', 'customer_id');
    }

    public function getTotal()
    {
        return ($this->getSubTotal() - $this->getTotalDiscount()) + $this->getTotalTax();
    }

    public function getSubTotal()
    {
        $subTotal = 0;
        foreach ($this->items as $product) {
            $subTotal += ($product->price * $product->quantity);
        }
        return $subTotal;
    }

    public function getTotalTax()
    {
        $taxData = Utility::getTaxData();
        $totalTax = 0;
        foreach ($this->items as $product) {
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
        foreach ($this->items as $product) {
            $totalDiscount += $product->discount;
        }
        return $totalDiscount;
    }

    public function category()
    {
        return $this->hasOne('App\Models\ProductServiceCategory', 'id', 'category_id');
    }

    public function products()
    {
        return $this->hasMany(RefundReceiptProduct::class);
    }
}
