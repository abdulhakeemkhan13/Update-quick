<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_type',
        'user_id',
        'customer_id',
        'service_id',
        'date',
        'start_time',
        'end_time',
        'duration',
        'break_duration',
        'billable',
        'rate',
        'taxable',
        'notes',
        'created_by',
        'invoiced_at',
        'invoice_id',
    ];

    protected $casts = [
        'billable' => 'boolean',
        'taxable' => 'boolean',
        'invoiced_at' => 'datetime',
    ];


    public function employee()
    {
        return $this->hasOne(Employee::class, 'id', 'user_id');
    }

    public function vendor()
    {
        return $this->hasOne(Vender::class, 'id', 'user_id');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'customer_id');
    }

    public function project()
    {
        return $this->hasOne(Project::class, 'id', 'customer_id');
    }

    public function service()
    {
        return $this->hasOne(ProductService::class, 'id', 'service_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }
}
