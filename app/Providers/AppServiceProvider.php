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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Score::observe(ScoreObserver::class);
    }
}
