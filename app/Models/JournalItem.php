<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalItem extends Model
{
    protected $fillable = [
        'journal',
        'account',
        'debit',
        'credit',
        'description',
        'product_id',
        'product_ids',
        'prod_tax_id',
        'type',
        'name',
        'customer_id',
        'vendor_id',
        'employee_id',
        'created_by',
        'created_user',
        'company_id',
        'created_at',
        'updated_at',
        'quickbooks_id',
    ];

    public function accounts()
    {
        return $this->hasOne('App\Models\ChartOfAccount', 'id', 'account');
    }
    public function journalEntry()
    {
        return $this->hasOne('App\Models\JournalEntry', 'id', 'journal');
    }

}
