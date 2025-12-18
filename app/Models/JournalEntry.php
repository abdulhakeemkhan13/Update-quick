<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = [
        'date',
        'reference',
        'description',
        'journal_id',
        'created_by',
        'voucher_type',
        'reference_id',
        'prod_id',
        'category',
        'owned_by',
        'status',
        'module',
        'source',
    ];


    public function accounts()
    {
        return $this->hasmany('App\Models\JournalItem', 'journal', 'id');
    }

    public function journalItem()
    {
        return $this->hasmany('App\Models\JournalItem', 'journal', 'id');
    }

    public function totalCredit()
    {
        $total = 0;
        foreach($this->accounts as $account)
        {
            $total += $account->credit;
        }

        return $total;
    }

    public function totalDebit()
    {
        $total = 0;
        foreach($this->accounts as $account)
        {
            $total += $account->debit;
        }

        return $total;
    }

    /**
     * Scope to filter active (non-cancelled) entries
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope to filter by module
     */
    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope to filter by reference
     */
    public function scopeByReference($query, $referenceId, $category = null)
    {
        $query->where('reference_id', $referenceId);
        if ($category) {
            $query->where('category', $category);
        }
        return $query;
    }

}
