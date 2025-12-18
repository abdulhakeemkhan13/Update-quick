<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillAttachment extends Model
{
    protected $fillable = [
        'bill_id',
        'file_path',
        'file_name',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    public function bill()
    {
        return $this->belongsTo('App\Models\Bill');
    }

    public function uploader()
    {
        return $this->belongsTo('App\Models\User', 'uploaded_by');
    }
}
