<?php

namespace App\Providers;

use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Settings docker-compose.yml passes to the backend.
     */
    public const DOCKER_SETTINGS = [
        'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD',
        'MDM_JAMF_MOCK_PATH',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Laravel's development server (php artisan serve) only passes a short list of
        // environment variables on to web requests; the rest come from .env. Pass the
        // ones docker-compose.yml sets, so the website uses them too (e.g. a scenario file).
        ServeCommand::$passthroughVariables = array_values(array_unique([
            ...ServeCommand::$passthroughVariables,
            ...self::DOCKER_SETTINGS,
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
