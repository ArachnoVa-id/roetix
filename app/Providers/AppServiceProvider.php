<?php

namespace App\Providers;

use Filament\Infolists\Infolist;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Number;
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

        $migrationPaths = glob(database_path('migrations/*'), GLOB_ONLYDIR);
        $migrationPaths[] = database_path('migrations');

        // Load migrations from subdirectories
        foreach ($migrationPaths as $path) {
            if (File::isDirectory($path)) {
                $this->loadMigrationsFrom($path);
            }
        }

        Number::useLocale('id');
        Infolist::$defaultCurrency = 'IDR';
        Infolist::$defaultNumberLocale = 'id';
        Table::$defaultCurrency = 'IDR';
        Table::$defaultNumberLocale = 'id';
    }
}
