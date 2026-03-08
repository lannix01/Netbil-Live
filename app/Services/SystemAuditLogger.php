<?php

namespace App\Services;

use App\Models\SystemAuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SystemAuditLogger
{
    private static ?bool $tableExists = null;

    public function log(
        string $event,
        ?string $description = null,
        array $context = [],
        ?Request $request = null,
        ?int $userId = null,
        ?array $actor = null,
        ?string $action = null,
        ?string $routeName = null,
        ?string $method = null,
        ?string $path = null,
        ?int $statusCode = null
    ): void {
        if (!$this->hasAuditTable()) {
            return;
        }

        $request = $request ?: request();
        $routeName = $routeName ?? $request?->route()?->getName();
        $method = $method ?? $request?->method();
        $path = $path ?? ($request ? '/' . ltrim((string) $request->path(), '/') : null);

        $payload = [
            'user_id' => $userId,
            'actor_name' => (string)($actor['name'] ?? ''),
            'actor_email' => (string)($actor['email'] ?? ''),
            'actor_role' => (string)($actor['role'] ?? ''),
            'event' => $event,
            'action' => $action,
            'description' => $description,
            'method' => strtoupper((string) $method),
            'path' => $path,
            'route_name' => $routeName,
            'status_code' => $statusCode,
            'ip_address' => $request?->ip(),
            'user_agent' => substr((string) ($request?->userAgent() ?? ''), 0, 1000),
            'context' => $this->sanitizeContext($context),
        ];

        $payload = $this->trimEmptyStrings($payload);

        try {
            SystemAuditLog::query()->create($payload);
        } catch (\Throwable $e) {
            Log::warning('System audit log write failed', [
                'event' => $event,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function sanitizeContext(array $context): array
    {
        return $this->sanitizeValue($context, 0);
    }

    public function sanitizeValue(mixed $value, int $depth = 0): mixed
    {
        if ($depth >= 4) {
            return '[truncated-depth]';
        }

        if (is_array($value)) {
            $clean = [];
            $count = 0;
            foreach ($value as $key => $item) {
                $count++;
                if ($count > 80) {
                    $clean['__truncated__'] = 'too-many-items';
                    break;
                }

                $keyStr = is_string($key) ? $key : (string) $key;
                if ($this->isSensitiveKey($keyStr)) {
                    $clean[$key] = '[REDACTED]';
                    continue;
                }

                $clean[$key] = $this->sanitizeValue($item, $depth + 1);
            }

            return $clean;
        }

        if ($value instanceof UploadedFile) {
            return [
                'file' => $value->getClientOriginalName(),
                'size' => $value->getSize(),
                'mime' => $value->getClientMimeType(),
            ];
        }

        if (is_object($value)) {
            return method_exists($value, '__toString')
                ? $this->limitString((string) $value)
                : '[object ' . $value::class . ']';
        }

        if (is_string($value)) {
            return $this->limitString($value);
        }

        return $value;
    }

    private function hasAuditTable(): bool
    {
        if (self::$tableExists !== null) {
            return self::$tableExists;
        }

        try {
            self::$tableExists = Schema::hasTable('system_audit_logs');
        } catch (\Throwable) {
            self::$tableExists = false;
        }

        return self::$tableExists;
    }

    private function isSensitiveKey(string $key): bool
    {
        $key = strtolower($key);
        return str_contains($key, 'password')
            || str_contains($key, 'passwd')
            || str_contains($key, 'pin')
            || str_contains($key, 'secret')
            || str_contains($key, 'token')
            || $key === '_token';
    }

    private function limitString(string $value): string
    {
        if (mb_strlen($value) <= 1500) {
            return $value;
        }

        return mb_substr($value, 0, 1500) . '...';
    }

    private function trimEmptyStrings(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_string($value) && trim($value) === '') {
                $payload[$key] = null;
            }
        }

        return $payload;
    }
}
