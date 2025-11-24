<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositLines extends Model
{
    use HasFactory;
    protected $fillable = [
        'deposit_id',
        'amount',
        'detail_type',
        'customer_id',
        'chart_account_id',
        'payment_method',
        'check_num',
        'linked_txns',
    ];
    public function deposit()
    {
        return $this->belongsTo(Deposit::class, 'deposit_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function chartAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_account_id');
    }
}
