<?php

namespace App\Http\Middleware;

use App\Services\SystemAuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordSystemAudit
{
    public function __construct(
        private readonly SystemAuditLogger $logger
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$this->shouldAudit($request)) {
            return $response;
        }

        $user = $request->user();
        if (!$user) {
            return $response;
        }

        $routeName = $request->route()?->getName();
        $method = strtoupper($request->method());
        $path = '/' . ltrim((string)$request->path(), '/');
        $action = $this->resolveAction($routeName, $method, $path);

        $context = [
            'query' => $this->logger->sanitizeValue($request->query()),
            'input' => $this->logger->sanitizeValue($request->except(['password', 'password_confirmation', '_token'])),
            'route_params' => $this->logger->sanitizeValue($request->route()?->parametersWithoutNulls() ?? []),
        ];

        $description = $this->resolveDescription($action, $routeName, $path);

        $this->logger->log(
            event: 'action.performed',
            description: $description,
            context: $context,
            request: $request,
            userId: (int)$user->getAuthIdentifier(),
            actor: [
                'name' => (string)($user->name ?? ''),
                'email' => (string)($user->email ?? ''),
                'role' => (string)($user->role ?? ''),
            ],
            action: $action,
            routeName: $routeName,
            method: $method,
            path: $path,
            statusCode: $response->getStatusCode(),
        );

        return $response;
    }

    private function shouldAudit(Request $request): bool
    {
        if (!$request->user()) {
            return false;
        }

        $method = strtoupper($request->method());
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return false;
        }

        $routeName = (string)($request->route()?->getName() ?? '');
        if ($routeName !== '' && str_starts_with($routeName, 'logs.')) {
            return false;
        }

        if (in_array($routeName, ['login', 'logout'], true)) {
            return false;
        }

        return true;
    }

    private function resolveAction(?string $routeName, string $method, string $path): string
    {
        $routeName = (string)($routeName ?? '');

        $map = [
            'packages.store' => 'package.create',
            'packages.update' => 'package.update',
            'packages.destroy' => 'package.delete',
            'customers.user.extend-package' => 'package.extend',
            'customers.user.disable' => 'user.disable',
            'customers.user.enable' => 'user.enable',
            'customers.user.update' => 'user.update',
            'customers.user.create' => 'user.create',
            'customers.disconnect' => 'session.disconnect',
            'ads.store' => 'ads.create',
            'ads.toggle' => 'ads.toggle',
            'ads.destroy' => 'ads.delete',
            'invoices.request-payment' => 'payment.request',
            'invoices.status' => 'invoice.status.update',
            'invoices.delete' => 'invoice.delete',
            'settings.index' => 'settings.update',
            'users.panel' => 'user-management.view',
        ];

        if (isset($map[$routeName])) {
            return $map[$routeName];
        }

        if ($routeName !== '') {
            return str_replace('.', '_', $routeName);
        }

        return strtolower($method . ':' . trim($path, '/'));
    }

    private function resolveDescription(string $action, ?string $routeName, string $path): string
    {
        return match ($action) {
            'package.create' => 'Created a package',
            'package.update' => 'Updated a package',
            'package.delete' => 'Deleted a package',
            'package.extend' => 'Extended a customer package',
            'user.disable' => 'Disabled a user account',
            'user.enable' => 'Enabled a user account',
            'ads.create' => 'Created a captive ad',
            'ads.toggle' => 'Changed ad status',
            'ads.delete' => 'Deleted a captive ad',
            'payment.request' => 'Requested a payment',
            'invoice.status.update' => 'Updated invoice status',
            'invoice.delete' => 'Deleted an invoice',
            default => 'Performed action on ' . (($routeName && $routeName !== '') ? $routeName : $path),
        };
    }
}
