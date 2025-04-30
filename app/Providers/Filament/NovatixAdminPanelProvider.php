<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use App\Filament\Components;

class NovatixAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return
            SharedPanelSetup::commonSetup(
                $panel
                    ->brandName('NovaTix Admin')
                    ->id('novatix-admin')
                    ->domain(config('app.domain'))
                    ->path('novatix-admin')
                    ->discoverResources(in: app_path('Filament/NovatixAdmin/Resources'), for: 'App\\Filament\\NovatixAdmin\\Resources')
                    ->discoverPages(in: app_path('Filament/NovatixAdmin/Pages'), for: 'App\\Filament\\NovatixAdmin\\Pages')
                    ->pages([])
                    ->discoverWidgets(in: app_path('Filament/NovatixAdmin/Widgets'), for: 'App\\Filament\\NovatixAdmin\\Widgets')
                    ->widgets([
                        Components\Widgets\NTUserChart::class
                    ])
            );
    }
}
