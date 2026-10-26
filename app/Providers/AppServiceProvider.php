<?php

namespace App\Providers;

use App\Services\Site;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        View::composer(['layouts.public', 'admin.login'], function ($view) {
            $view->with('site', Site::data());
        });
    }
}
