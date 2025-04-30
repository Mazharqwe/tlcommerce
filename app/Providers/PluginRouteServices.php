<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class PluginRouteServices extends ServiceProvider
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
        if (env('IS_USER_REGISTERED') == 1) {
            $this->routes(function () {
                $plugins = getActivePlugins();

                foreach ($plugins as $plugin) {
                    if (isTenant() && $plugin->type != 'saas') {
                        if (file_exists(base_path('plugins/' . $plugin->location . '/routes/api.php'))) {
                            Route::middleware([
                                'api',
                                InitializeTenancyByDomain::class,
                                PreventAccessFromCentralDomains::class,
                                'check.subscriber.auth'
                            ])->prefix('api')->group(base_path('plugins/' . $plugin->location . '/routes/api.php'));
                        }

                        if (file_exists(base_path('plugins/' . $plugin->location . '/routes/web.php'))) {
                            Route::middleware([
                                'web',
                                InitializeTenancyByDomain::class,
                                PreventAccessFromCentralDomains::class,
                                'check.subscriber.auth'
                            ])->group(base_path('plugins/' . $plugin->location . '/routes/web.php'));
                        }
                    } elseif (!isTenant() && $plugin->type == 'saas') {
                        if (file_exists(base_path('plugins/' . $plugin->location . '/routes/api.php'))) {
                            Route::middleware('api')->prefix('api')->group(base_path('plugins/' . $plugin->location . '/routes/api.php'));
                        }

                        if (file_exists(base_path('plugins/' . $plugin->location . '/routes/web.php'))) {
                            Route::middleware('web')->group(base_path('plugins/' . $plugin->location . '/routes/web.php'));
                        }

                        if (file_exists(base_path('plugins/' . $plugin->location . '/routes/user.php'))) {
                            Route::middleware('web')->group(base_path('plugins/' . $plugin->location . '/routes/user.php'));
                        }
                    }
                }
            });
        }
    }

    protected function centralDomains(): array
    {
        return config('tenancy.central_domains');
    }
}
