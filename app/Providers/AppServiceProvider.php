<?php

namespace App\Providers;

use App\Services\SystemAuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('petty-api', function (Request $request) {
            $perMinute = (int) config('pettycash.api.rate_limit_per_minute', 120);
            if ($perMinute < 1) {
                $perMinute = 120;
            }

            $user = $request->attributes->get('pettyUser');
            $apiToken = $request->attributes->get('pettyApiToken');

            $key = $apiToken?->id
                ? 'petty-token:' . $apiToken->id
                : ($user?->id ? 'petty-user:' . $user->id : 'ip:' . $request->ip());

            return Limit::perMinute($perMinute)->by($key);
        });

        RateLimiter::for('petty-api-login', function (Request $request) {
            $perMinute = (int) config('pettycash.api.login_rate_limit_per_minute', 20);
            if ($perMinute < 1) {
                $perMinute = 20;
            }

            $email = strtolower(trim((string) $request->input('email', '')));
            $key = 'petty-login:' . $request->ip() . ':' . $email;

            return Limit::perMinute($perMinute)->by($key);
        });

        RateLimiter::for('connect-demo-start', function (Request $request) {
            $perMinute = (int) config('netbil.demo.rate_limit_per_minute', 6);
            if ($perMinute < 1) {
                $perMinute = 6;
            }

            $mode = strtolower(trim((string) $request->input('mode', '')));
            $ip = trim((string) $request->ip());
            $key = 'connect-demo:' . ($ip !== '' ? $ip : 'unknown') . ':' . ($mode !== '' ? $mode : 'any');

            return Limit::perMinute($perMinute)
                ->by($key)
                ->response(function (Request $request, array $headers) {
                    $message = 'Too many demo start attempts. Wait a minute and try again.';

                    if ($request->expectsJson()) {
                        return response()->json([
                            'ok' => false,
                            'message' => $message,
                        ], 429, $headers);
                    }

                    return response($message, 429, $headers);
                });
        });

        $this->registerAuditAuthEvents();
    }

    private function registerAuditAuthEvents(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            $request = request();
            app(SystemAuditLogger::class)->log(
                event: 'auth.login',
                description: 'User logged in successfully',
                context: [
                    'guard' => $event->guard,
                ],
                request: $request,
                userId: (int)$event->user->getAuthIdentifier(),
                actor: $this->actorPayload($event->user),
                action: 'auth_login',
                routeName: $request?->route()?->getName(),
                method: $request?->method(),
                path: $request ? '/' . ltrim((string)$request->path(), '/') : '/login',
                statusCode: 200,
            );
        });

        Event::listen(Logout::class, function (Logout $event): void {
            $request = request();
            app(SystemAuditLogger::class)->log(
                event: 'auth.logout',
                description: 'User logged out',
                context: [
                    'guard' => $event->guard,
                ],
                request: $request,
                userId: (int)$event->user->getAuthIdentifier(),
                actor: $this->actorPayload($event->user),
                action: 'auth_logout',
                routeName: $request?->route()?->getName(),
                method: $request?->method(),
                path: $request ? '/' . ltrim((string)$request->path(), '/') : '/logout',
                statusCode: 200,
            );
        });

        Event::listen(Failed::class, function (Failed $event): void {
            $request = request();
            $identifier = (string)(
                $event->credentials['email']
                ?? $event->credentials['login']
                ?? $event->credentials['phone']
                ?? $event->credentials['username']
                ?? ''
            );

            app(SystemAuditLogger::class)->log(
                event: 'auth.failed',
                description: 'Failed login attempt',
                context: [
                    'guard' => $event->guard,
                    'identifier' => $identifier,
                ],
                request: $request,
                userId: $event->user?->getAuthIdentifier(),
                actor: $this->actorPayload($event->user),
                action: 'auth_failed',
                routeName: $request?->route()?->getName(),
                method: $request?->method(),
                path: $request ? '/' . ltrim((string)$request->path(), '/') : '/login',
                statusCode: 401,
            );
        });
    }

    private function actorPayload(?Authenticatable $user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'name' => (string)($user->name ?? ''),
            'email' => (string)($user->email ?? ''),
            'role' => (string)($user->role ?? ''),
        ];
    }
}
