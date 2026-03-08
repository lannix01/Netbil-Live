<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Ad extends Model
{
    protected $fillable = [
        'title',
        'content',
        'media_type',
        'media_url',
        'image_path',
        'cta_text',
        'cta_url',
        'is_active',
        'starts_at',
        'ends_at',
        'priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Scope: only ads that should be visible now
     */
    public function scopeActive(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now)
                    ->orWhereDate('starts_at', '<=', $now->toDateString());
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now)
                    ->orWhereDate('ends_at', '>=', $now->toDateString());
            });
    }

    public function getResolvedMediaUrlAttribute(): ?string
    {
        $candidates = [
            trim((string)($this->attributes['media_url'] ?? '')),
            trim((string)($this->attributes['image_path'] ?? '')),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    public function getResolvedMediaTypeAttribute(): string
    {
        $declared = strtolower(trim((string)($this->attributes['media_type'] ?? '')));
        if (in_array($declared, ['text', 'image', 'video'], true)) {
            return $declared;
        }

        $url = (string)($this->resolved_media_url ?? '');
        if ($url === '') {
            return 'text';
        }

        $path = (string)(parse_url($url, PHP_URL_PATH) ?? '');
        $ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'm4v'], true)) {
            return 'video';
        }

        return 'image';
    }

    public function getResolvedCtaUrlAttribute(): ?string
    {
        $url = trim((string)($this->attributes['cta_url'] ?? ''));
        return $url === '' ? null : $url;
    }

    public function getResolvedCtaTextAttribute(): ?string
    {
        $text = trim((string)($this->attributes['cta_text'] ?? ''));
        if ($text !== '') {
            return $text;
        }

        return $this->resolved_cta_url ? 'Learn more' : null;
    }
}
