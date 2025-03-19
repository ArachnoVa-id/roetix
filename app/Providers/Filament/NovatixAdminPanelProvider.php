<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;

class NovatixAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return
            SharedPanelSetup::commonSetup(
                $panel
                    ->id('novatix-admin')
                    ->path('novatix-admin')
                    ->colors([
                        'primary' => Color::Amber,
                    ])
                    ->discoverResources(in: app_path('Filament/NovatixAdmin/Resources'), for: 'App\\Filament\\NovatixAdmin\\Resources')
                    ->discoverPages(in: app_path('Filament/NovatixAdmin/Pages'), for: 'App\\Filament\\NovatixAdmin\\Pages')
                    ->pages([
                        Pages\Dashboard::class,
                    ])
                    ->discoverWidgets(in: app_path('Filament/NovatixAdmin/Widgets'), for: 'App\\Filament\\NovatixAdmin\\Widgets')
                    ->widgets([
                        Widgets\AccountWidget::class,
                    ])
            );
    }
}
