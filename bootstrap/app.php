<?php

use App\Modules\PettyCash\Support\ApiEnvelope;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // CSRF exceptions (keep yours)
        $middleware->validateCsrfTokens(except: [
            '/connect',
            '/connect/*',
        ]);

        // Middleware aliases (PettyCash + Inventory)
        $middleware->alias([
            'petty.auth' => \App\Modules\PettyCash\Middleware\PettyAuth::class,
            'petty.api.auth' => \App\Modules\PettyCash\Middleware\PettyApiAuth::class,
            'petty.api.meta' => \App\Modules\PettyCash\Middleware\PettyApiMetaHeaders::class,
            'petty.permission' => \App\Modules\PettyCash\Middleware\PettyPermission::class,

            // Inventory module middleware aliases
            'inventory.auth' => \App\Modules\Inventory\Http\Middleware\InventoryAuth::class,
            'inventory.force_password_change' => \App\Modules\Inventory\Http\Middleware\InventoryForcePasswordChange::class,
            'inventory.role' => \App\Modules\Inventory\Http\Middleware\InventoryRole::class,
            'audit.log' => \App\Http\Middleware\RecordSystemAudit::class,
        ]);

        $middleware->append(\App\Http\Middleware\RecordSystemAudit::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isPettyApi = static function (Request $request): bool {
            return $request->is('api/petty/v1/*');
        };

        $exceptions->render(function (ValidationException $e, Request $request) use ($isPettyApi) {
            if (!$isPettyApi($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => (object) $e->errors(),
                'meta' => (object) ApiEnvelope::meta($request),
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isPettyApi) {
            if (!$isPettyApi($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'errors' => (object) [],
                'meta' => (object) ApiEnvelope::meta($request),
            ], 401);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($isPettyApi) {
            if (!$isPettyApi($request)) {
                return null;
            }

            $message = trim((string) $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $message !== '' ? $message : 'Forbidden.',
                'errors' => (object) [],
                'meta' => (object) ApiEnvelope::meta($request),
            ], 403);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($isPettyApi) {
            if (!$isPettyApi($request)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Resource not found.',
                'errors' => (object) [],
                'meta' => (object) ApiEnvelope::meta($request),
            ], 404);
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) use ($isPettyApi) {
            if (!$isPettyApi($request)) {
                return null;
            }

            $meta = ApiEnvelope::meta($request);
            if ($e->getHeaders()['Retry-After'] ?? null) {
                $meta['retry_after'] = (int) $e->getHeaders()['Retry-After'];
            }

            return response()->json([
                'success' => false,
                'message' => 'Too many requests.',
                'errors' => (object) [],
                'meta' => (object) $meta,
            ], 429, $e->getHeaders());
        });

        $exceptions->render(function (Throwable $e, Request $request) use ($isPettyApi) {
            if ($isPettyApi($request) || $request->expectsJson() || $request->is('api/*')) {
                return null;
            }

            if ($e instanceof ValidationException || $e instanceof AuthenticationException) {
                return null;
            }

            $status = 500;
            if ($e instanceof HttpExceptionInterface) {
                $status = (int)$e->getStatusCode();
            } elseif ($e instanceof TokenMismatchException) {
                $status = 419;
            }

            if ($status < 400 || $status > 599) {
                $status = 500;
            }

            $view = view()->exists("errors.$status") ? "errors.$status" : 'errors.500';
            return response()->view($view, [
                'statusCode' => $status,
            ], $status);
        });
    })
    ->create();
