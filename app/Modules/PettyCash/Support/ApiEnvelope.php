<?php

namespace App\Modules\PettyCash\Support;

use Illuminate\Http\Request;

class ApiEnvelope
{
    public static function meta(?Request $request = null, array $extra = []): array
    {
        $request = $request ?: request();

        $meta = [
            'request_id' => (string) ($request?->attributes->get('petty_request_id') ?: $request?->headers->get('X-Request-Id', '')),
            'api_version' => (string) ($request?->attributes->get('petty_api_version') ?: config('pettycash.api.version', 'v1')),
            'timestamp' => now()->toIso8601String(),
        ];

        if ($meta['request_id'] === '') {
            unset($meta['request_id']);
        }

        if (empty($extra)) {
            return $meta;
        }

        return array_replace_recursive($meta, $extra);
    }
}
