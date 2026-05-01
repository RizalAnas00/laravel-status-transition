<?php 

namespace Rizalsaja\LaravelStatusTransition;

use Illuminate\Support\ServiceProvider;

class LaravelStatusTransitionServiceProvider extends ServiceProvider
{
    /**
     * R
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-status-transition.php', 'laravel-status-transition');
    }

    /**
     * Bootstrap the application events.
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/laravel-status-transition.php' => config_path('laravel-status-transition.php'),
            ], 'laravel-status-transition-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'laravel-status-transition-migrations');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}