<?php

namespace Insane\Journal;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class JournalServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/Journal.php', 'journal');

        $this->publishConfig();

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->registerRoutes();
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    private function registerRoutes()
    {
        $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
    }

    /**
    * Get route group configuration array.
    *
    * @return array
    */
    private function routeConfiguration()
    {
        return [
            'namespace'  => "Insane\Journal\Http\Controllers",
            'middleware' => 'api',
            'prefix'     => 'api'
        ];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register facade
        $this->app->singleton('journal', function () {
            return new Journal;
        });
    }

    /**
     * Publish Config
     *
     * @return void
     */
    public function publishConfig()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/Journal.php' => config_path('Journal.php'),
            ], 'config');

            $this->publishes([
            __DIR__ . '/database/seeders/accounting.php' => database_path('seeds/accounting.php'),
            ], 'accounting-seeds');

        }
    }
}
