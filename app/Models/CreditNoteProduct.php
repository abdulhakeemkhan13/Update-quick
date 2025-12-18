<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditNoteProduct extends Model
{
    use HasFactory;
    protected $fillable = [
        'credit_note_id',
        'product_id',
        'quantity',
        'tax',
        'discount',
        'price',
        'description',
    ];
}
