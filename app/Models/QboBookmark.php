<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QboBookmark extends Model
{
    use HasFactory;

    protected $table = 'qbo_bookmarks';

    protected $fillable = [
        'user_id',
        'key',
        'label',
        'route',
        'icon',
        'color',
        'type',
        'position',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * Get the user that owns the bookmark
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get all bookmarks for a user
     */
    public static function getForUser(int $userId, string $type = null)
    {
        $query = self::where('user_id', $userId);
        
        if ($type) {
            $query->where('type', $type);
        }
        
        return $query->orderBy('position')->get();
    }

    /**
     * Get user's pinned items
     */
    public static function getPinnedItems(int $userId)
    {
        return self::getForUser($userId, 'pinned');
    }

    /**
     * Get user's bookmarks
     */
    public static function getBookmarks(int $userId)
    {
        return self::getForUser($userId, 'bookmark');
    }

    /**
     * Get user's menu configuration
     */
    public static function getMenuConfig(int $userId)
    {
        return self::getForUser($userId, 'menu');
    }

    /**
     * Create default menu items for a user
     */
    public static function createDefaultsForUser(int $userId)
    {
        $defaults = config('qbo-menu.items', []);
        
        foreach ($defaults as $index => $item) {
            self::firstOrCreate(
                [
                    'user_id' => $userId,
                    'key' => $item['key'],
                    'type' => $item['type'] ?? 'menu',
                ],
                [
                    'label' => $item['label'],
                    'route' => $item['route'] ?? null,
                    'icon' => $item['icon'] ?? null,
                    'color' => $item['color'] ?? null,
                    'position' => $item['position'] ?? $index,
                    'is_visible' => $item['is_visible'] ?? true,
                ]
            );
        }
    }
}
