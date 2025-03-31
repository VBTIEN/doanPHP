<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Score;
use App\Observers\ScoreObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DatabaseReaderService::class, function ($app) {
            return new DatabaseReaderService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Score::observe(ScoreObserver::class);

        $this->app->bind(\App\Http\Middleware\CustomSanctumAuth::class, function ($app) {
            return new \App\Http\Middleware\CustomSanctumAuth(
                new Guard($app['auth'], config('sanctum.expiration'), 'users')
            );
        });
    }
}
