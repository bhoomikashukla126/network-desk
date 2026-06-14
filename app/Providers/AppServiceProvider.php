<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        foreach ([
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
        ] as $path) {
            if (! is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }

        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
