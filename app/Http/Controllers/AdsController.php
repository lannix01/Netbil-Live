<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ad;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class AdsController extends Controller
{
    private static array $schemaCache = [];

    /**
     * Show all ads (for settings tab)
     */
    public function index()
    {
        $ads = Ad::query()
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->get();

        return view('settings.partials.ads', compact('ads'));
    }

    /**
     * Store a new ad
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'ad_type' => 'nullable|in:text,image,video',
            'content' => 'nullable|string',
            'media_url' => 'nullable|url',
            'cta_text' => 'nullable|string|max:80',
            'cta_url' => 'nullable|url',
            'priority' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $adType = strtolower(trim((string)($validated['ad_type'] ?? 'text')));
        if (!in_array($adType, ['text', 'image', 'video'], true)) {
            $adType = 'text';
        }

        $content = trim((string)($validated['content'] ?? ''));
        $mediaUrl = trim((string)($validated['media_url'] ?? ''));

        if ($adType === 'text' && $content === '') {
            return back()->withErrors(['content' => 'Content is required for text ads.'])->withInput();
        }

        if (in_array($adType, ['image', 'video'], true) && $mediaUrl === '') {
            return back()->withErrors(['media_url' => 'Media URL is required for image/video ads.'])->withInput();
        }

        if ($content === '' && $mediaUrl === '') {
            return back()->withErrors(['content' => 'Provide content or media for the ad.'])->withInput();
        }

        $startsAt = !empty($validated['starts_at'])
            ? Carbon::parse((string)$validated['starts_at'])->startOfDay()
            : null;
        $endsAt = !empty($validated['ends_at'])
            ? Carbon::parse((string)$validated['ends_at'])->endOfDay()
            : null;

        $payload = [
            'title' => trim((string)$validated['title']),
            'content' => $content !== '' ? $content : null,
            'media_type' => $adType,
            'media_url' => $mediaUrl !== '' ? $mediaUrl : null,
            'image_path' => $mediaUrl !== '' ? $mediaUrl : null,
            'cta_text' => ($t = trim((string)($validated['cta_text'] ?? ''))) !== '' ? $t : null,
            'cta_url' => ($u = trim((string)($validated['cta_url'] ?? ''))) !== '' ? $u : null,
            'priority' => (int)($validated['priority'] ?? 0),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_active' => true,
        ];

        Ad::create($this->onlyExistingColumns('ads', $payload));

        return back()->with('success', 'Ad created successfully.');
    }

    /**
     * Toggle active/inactive
     */
    public function toggle(Ad $ad)
    {
        $ad->is_active = ! $ad->is_active;
        $ad->save();

        return back()->with('success', 'Ad status updated.');
    }

    /**
     * Delete an ad
     */
    public function destroy(Ad $ad)
    {
        $ad->delete();
        return back()->with('success', 'Ad deleted.');
    }

    private function onlyExistingColumns(string $table, array $payload): array
    {
        $filtered = [];

        foreach ($payload as $column => $value) {
            if ($this->hasColumn($table, $column)) {
                $filtered[$column] = $value;
            }
        }

        return $filtered;
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;

        if (!array_key_exists($key, self::$schemaCache)) {
            try {
                self::$schemaCache[$key] = Schema::hasColumn($table, $column);
            } catch (\Throwable) {
                self::$schemaCache[$key] = false;
            }
        }

        return (bool) self::$schemaCache[$key];
    }
}
