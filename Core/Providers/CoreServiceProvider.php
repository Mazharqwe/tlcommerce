<?php

namespace Core\Providers;

use App\Helpers\SystemHelper;
use Composer\Autoload\ClassLoader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(base_path('Core/Views'), 'core');
    }
}
