<?php

namespace App\Http\Controllers;

use App\Models\QboBookmark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QboMenuController extends Controller
{
    /**
     * Get user's menu configuration
     */
    public function getMenuConfig()
    {
        $userId = Auth::id();
        
        // Get user's custom menu config or use defaults
        $userConfig = QboBookmark::getMenuConfig($userId);
        
        if ($userConfig->isEmpty()) {
            // Return default config from file
            return response()->json([
                'items' => config('qbo-menu.items', []),
                'is_default' => true,
            ]);
        }
        
        return response()->json([
            'items' => $userConfig,
            'is_default' => false,
        ]);
    }

    /**
     * Save user's menu configuration
     */
    public function saveMenuConfig(Request $request)
    {
        $userId = Auth::id();
        $items = $request->input('items', []);
        
        foreach ($items as $item) {
            QboBookmark::updateOrCreate(
                [
                    'user_id' => $userId,
                    'key' => $item['key'],
                    'type' => 'menu',
                ],
                [
                    'label' => $item['label'],
                    'route' => $item['route'] ?? null,
                    'icon' => $item['icon'] ?? null,
                    'color' => $item['color'] ?? null,
                    'position' => $item['position'] ?? 0,
                    'is_visible' => $item['is_visible'] ?? true,
                ]
            );
        }
        
        return response()->json(['success' => true, 'message' => 'Menu configuration saved']);
    }

    /**
     * Get user's bookmarks
     */
    public function getBookmarks()
    {
        $userId = Auth::id();
        $bookmarks = QboBookmark::getBookmarks($userId);
        
        return response()->json([
            'bookmarks' => $bookmarks,
        ]);
    }

    /**
     * Save user's bookmarks
     */
    public function saveBookmarks(Request $request)
    {
        $userId = Auth::id();
        $bookmarks = $request->input('bookmarks', []);
        
        // Delete existing bookmarks
        QboBookmark::where('user_id', $userId)->where('type', 'bookmark')->delete();
        
        // Create new bookmarks
        foreach ($bookmarks as $index => $bookmark) {
            QboBookmark::create([
                'user_id' => $userId,
                'key' => $bookmark['key'],
                'label' => $bookmark['label'],
                'route' => $bookmark['route'] ?? null,
                'icon' => $bookmark['icon'] ?? null,
                'color' => $bookmark['color'] ?? null,
                'type' => 'bookmark',
                'position' => $index,
                'is_visible' => true,
            ]);
        }
        
        return response()->json(['success' => true, 'message' => 'Bookmarks saved']);
    }

    /**
     * Get user's pinned items
     */
    public function getPinnedItems()
    {
        $userId = Auth::id();
        $pinned = QboBookmark::getPinnedItems($userId);
        
        return response()->json([
            'pinned' => $pinned,
        ]);
    }

    /**
     * Toggle pin status for an item
     */
    public function togglePin(Request $request)
    {
        $userId = Auth::id();
        $key = $request->input('key');
        
        $existing = QboBookmark::where('user_id', $userId)
            ->where('key', $key)
            ->where('type', 'pinned')
            ->first();
        
        if ($existing) {
            $existing->delete();
            return response()->json(['pinned' => false, 'message' => 'Item unpinned']);
        }
        
        // Get item config from defaults
        $defaultItems = config('qbo-menu.items', []);
        $itemConfig = collect($defaultItems)->firstWhere('key', $key);
        
        if ($itemConfig) {
            $maxPosition = QboBookmark::where('user_id', $userId)
                ->where('type', 'pinned')
                ->max('position') ?? 0;
            
            QboBookmark::create([
                'user_id' => $userId,
                'key' => $key,
                'label' => $itemConfig['label'],
                'route' => $itemConfig['route'] ?? null,
                'icon' => $itemConfig['icon'] ?? null,
                'color' => $itemConfig['color'] ?? null,
                'type' => 'pinned',
                'position' => $maxPosition + 1,
                'is_visible' => true,
            ]);
        }
        
        return response()->json(['pinned' => true, 'message' => 'Item pinned']);
    }

    /**
     * Reset menu to default
     */
    public function resetToDefault()
    {
        $userId = Auth::id();
        
        // Delete all user's menu customizations
        QboBookmark::where('user_id', $userId)->where('type', 'menu')->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Menu reset to default',
            'items' => config('qbo-menu.items', []),
        ]);
    }
}
