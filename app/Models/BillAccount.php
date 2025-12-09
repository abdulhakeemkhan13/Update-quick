<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'chart_account_id',
        'price',
        'description',
        'type',
        'ref_id',
        'order',
        'billable',
        'customer_id',
        'tax',
        'status',
        'invoiced_at',
        'invoice_id',
    ];

    protected $casts = [
        'billable' => 'boolean',
        'invoiced_at' => 'datetime',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'ref_id', 'id');
    }

    public function chartAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_account_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }
}
