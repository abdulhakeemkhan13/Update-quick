<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerType extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'qb_type_id',
        'name'
    ];

    public function customers()
    {
        return $this->hasMany(Customer::class, 'type_id');
    }
}
