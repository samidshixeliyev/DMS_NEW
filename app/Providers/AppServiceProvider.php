<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // When served over HTTPS (behind nginx that terminates TLS), force every
        // generated URL — links, form actions, assets, AJAX endpoints — to https.
        // Without this, behind the proxy Laravel emits http:// URLs and browsers
        // block them as mixed content / warn the form submit is "not secure".
        // Gated on APP_URL so local http development is unaffected.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
