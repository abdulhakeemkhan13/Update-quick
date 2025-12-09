<?php

namespace App\Http\Controllers;

use App\Models\QboBookmark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QboMenuController extends Controller
{
    /**
     * Get user's menu configuration (includes both menu and pinned items for customization)
     */
    public function getMenuConfig()
    {
        $userId = Auth::id();
        
        // Get user's custom menu config - includes both 'menu' and 'pinned' types
        $userConfig = QboBookmark::where('user_id', $userId)
            ->whereIn('type', ['menu', 'pinned'])
            ->orderBy('position')
            ->get();
        
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
        
        // Get default config for looking up missing properties
        $defaultItems = config('qbo-menu.items', []);
        $defaultConfig = collect($defaultItems)->keyBy('key');
        
        // Delete existing menu/pinned items for this user to prevent duplicates
        QboBookmark::where('user_id', $userId)
            ->whereIn('type', ['menu', 'pinned'])
            ->delete();
        
        // Insert fresh items
        foreach ($items as $item) {
            $key = $item['key'];
            $default = $defaultConfig->get($key, []);
            
            QboBookmark::create([
                'user_id' => $userId,
                'key' => $key,
                'type' => $item['type'] ?? 'menu',
                'label' => $item['label'] ?? $default['label'] ?? $key,
                'route' => $item['route'] ?? $default['route'] ?? null,
                'icon' => $item['icon'] ?? $default['icon'] ?? null,
                'color' => $item['color'] ?? $default['color'] ?? null,
                'position' => $item['position'] ?? 0,
                'is_visible' => $item['is_visible'] ?? true,
            ]);
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

    /**
     * Get all available pages for bookmarking
     */
    public function getAvailablePages()
    {
        return response()->json([
            'pages' => config('qbo-menu.bookmarkable_pages', []),
        ]);
    }

    /**
     * Update a single bookmark's label
     */
    public function updateBookmark(Request $request, $id)
    {
        $userId = Auth::id();
        $bookmark = QboBookmark::where('id', $id)
            ->where('user_id', $userId)
            ->first();
        
        if (!$bookmark) {
            return response()->json(['success' => false, 'message' => 'Bookmark not found'], 404);
        }
        
        $bookmark->label = $request->input('label', $bookmark->label);
        $bookmark->save();
        
        return response()->json(['success' => true, 'message' => 'Bookmark updated', 'bookmark' => $bookmark]);
    }

    /**
     * Delete a single bookmark
     */
    public function deleteBookmark($id)
    {
        $userId = Auth::id();
        $bookmark = QboBookmark::where('id', $id)
            ->where('user_id', $userId)
            ->first();
        
        if (!$bookmark) {
            return response()->json(['success' => false, 'message' => 'Bookmark not found'], 404);
        }
        
        $bookmark->delete();
        
        return response()->json(['success' => true, 'message' => 'Bookmark deleted']);
    }

    /**
     * Reset bookmarks to last saved arrangement
     */
    public function resetBookmarks()
    {
        $userId = Auth::id();
        
        // Delete all user's bookmarks
        QboBookmark::where('user_id', $userId)->where('type', 'bookmark')->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Bookmarks reset',
            'bookmarks' => [],
        ]);
    }
}
