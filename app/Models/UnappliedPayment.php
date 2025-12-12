<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnappliedPayment extends Model
{
    use HasFactory;
    protected $fillable = [
    'qb_payment_id', 'reference', 'vendor_id', 'vendor_qb_id',
    'total_amount', 'applied_amount', 'unapplied_amount', 'txn_date',
    'account_id', 'chart_account_id', 'created_by', 'owned_by', 'linked_bill_txns', 'raw'
    ];
}
