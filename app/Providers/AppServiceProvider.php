<?php

namespace App\Providers;

use BladeUI\Icons\Factory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        $migrationPaths = [
            database_path('migrations/v1_0_0'),
        ];
    
        // Load migrations from subdirectories
        foreach ($migrationPaths as $path) {
            if (File::isDirectory($path)) {
                $this->loadMigrationsFrom($path);
            }
        }
    }
}
