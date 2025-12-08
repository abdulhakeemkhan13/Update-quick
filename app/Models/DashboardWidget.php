<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends Model
{
    protected $fillable = [
        'user_id', 'key', 'x', 'y', 'w', 'h', 'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create default widget layout for a new user
     * Layout based on 12-column grid (like Bootstrap)
     * Each widget width: 3 = 25%, 4 = 33%, 6 = 50%, 12 = 100%
     */
    public static function createDefaultForUser(int $userId)
    {
        $defaults = [
            // Row 0: 4 widgets (each w=3 spans 25% width)
            ['key' => 'profit_loss',    'x' => 0, 'y' => 0, 'w' => 3, 'h' => 1],
            ['key' => 'expenses',       'x' => 3, 'y' => 0, 'w' => 3, 'h' => 1],
            ['key' => 'invoices',       'x' => 6, 'y' => 0, 'w' => 3, 'h' => 1],
            ['key' => 'bank_accounts',  'x' => 9, 'y' => 0, 'w' => 3, 'h' => 1],
            
            // Row 1: 2 wide widgets (each w=6 spans 50% width)
            ['key' => 'sales_funnel',   'x' => 0, 'y' => 1, 'w' => 6, 'h' => 1],
            ['key' => 'cashflow',       'x' => 6, 'y' => 1, 'w' => 6, 'h' => 1],
            
            // Row 2: 3 widgets (w=3, w=3, w=6)
            ['key' => 'accounts_receivable', 'x' => 0, 'y' => 2, 'w' => 3, 'h' => 1],
            ['key' => 'accounts_payable',    'x' => 3, 'y' => 2, 'w' => 3, 'h' => 1],
            ['key' => 'sales_trend',         'x' => 6, 'y' => 2, 'w' => 6, 'h' => 1],
        ];

        foreach ($defaults as $d) {
            self::create($d + ['user_id' => $userId, 'enabled' => true]);
        }

        return self::where('user_id', $userId)->where('enabled', true)->get();
    }
}
