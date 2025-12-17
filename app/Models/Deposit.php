<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    use HasFactory;
    protected $fillable = [
        'deposit_id',
        'doc_number',
        'txn_date',
        'total_amt',
        'private_note',
        'currency',
        'bank_id',
        'customer_id',
        'entity_type',
        'chart_account_id',
        'other_account_id',
    ];
    public function lines()
    {
        return $this->hasMany(DepositLine::class, 'deposit_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function bank()
    {
        return $this->belongsTo(BankAccount::class, 'bank_id');
    }

    public function chartAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_account_id');
    }

    public function otherAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'other_account_id');
    }
}
