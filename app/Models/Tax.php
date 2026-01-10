<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

    class Tax extends Model
    {
        protected $fillable = [
           'taxid', 'name', 'rate', 'chart_account_id', 'created_by', 'owned_by'
        ];

        /**
         * Get the chart of account associated with this tax.
         */
        public function chartAccount()
        {
            return $this->belongsTo(ChartOfAccount::class, 'chart_account_id');
        }
    }
