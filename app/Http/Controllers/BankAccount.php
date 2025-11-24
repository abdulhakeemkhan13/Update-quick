<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $fillable = [
        'holder_name',
        'bank_name',
        'account_number',
        'chart_account_id',
        'opening_balance',
        'contact_number',
        'bank_address',
        'created_by',
        'plaid_item_id',
        'plaid_access_token',
        'plaid_public_token',
        'link_session_id',
        'link_token',
        'institution_id',
        'institution_name',
        'account_id',
        'account_name',
        'account_mask',
        'account_type',
        'account_subtype',
        'user_id',
    ];

    public function chartAccount()
    {
        return $this->hasOne('App\Models\ChartOfAccount', 'id', 'chart_account_id');
    }

    //holdings
    public function holdings()
    {
        return $this->hasMany('App\Models\Holdings', 'institution_id', 'institution_id');
    }
    
}

