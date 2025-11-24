<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceBillableExpense extends Model
{
    use HasFactory;
    protected $fillable = [
        'invoice_id',
        'expense_id',
        'type',
        'amount',
        'transaction_date',
        'note',
        'created_by',
    ];
}
