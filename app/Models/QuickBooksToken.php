<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickBooksToken extends Model
{
    protected $table = 'quickbooks_tokens';
    protected $fillable = ['user_id', 'realm_id', 'access_token', 'refresh_token', 'expires_at'];
    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
