<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PettyCashRouteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // View namespace: view('pettycash::auth.login')
        $this->loadViewsFrom(base_path('app/Modules/PettyCash/Views'), 'pettycash');
    }

    public function boot(): void
    {
        Route::middleware('web')
            ->prefix('pettycash')
            ->group(base_path('app/Modules/PettyCash/Routes/auth.php'));

        Route::middleware([
            'web',
            \App\Modules\PettyCash\Middleware\PettyAuth::class,
            \App\Modules\PettyCash\Middleware\PettyPermission::class,
        ])
            ->prefix('pettycash')
            ->group(base_path('app/Modules/PettyCash/Routes/web.php'));
    }
}
