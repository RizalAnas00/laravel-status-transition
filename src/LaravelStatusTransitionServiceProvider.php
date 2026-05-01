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
        $this->mergeConfigFrom(__DIR__.'/../config/status-flow.php', 'status-flow');
    }

    /**
     * Bootstrap the application events.
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/status-flow.php' => config_path('status-flow.php'),
            ], 'status-flow-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'status-flow-migrations');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}