<?php

namespace Core\Providers;

use App\Helpers\SystemHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->routes(function () {
            if (env('IS_USER_REGISTERED') == 1) {
                if (!isTenant()) {
                    Route::middleware(['web'])->prefix(getAdminPrefix())->group(base_path('Core/routes/core.php'));
                    Route::middleware(['api'])->prefix('api')->group(base_path('Core/routes/api.php'));
                } else {
                    Route::middleware(['web', 'check.subscriber.auth', InitializeTenancyByDomain::class, PreventAccessFromCentralDomains::class])->prefix(getAdminPrefix())->group(base_path('Core/routes/core.php'));
                    Route::middleware(['web', 'check.subscriber.auth', InitializeTenancyByDomain::class, PreventAccessFromCentralDomains::class])->prefix('api')->group(base_path('Core/routes/api.php'));
                }
            }
        });
    }
}
