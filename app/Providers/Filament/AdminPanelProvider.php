<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Tenancy\RegisterTeam;
use App\Models\Team;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return
            SharedPanelSetup::commonSetup(
                $panel
                    ->default()
                    ->id('admin')
                    ->domain(config('app.domain'))
                    ->path('admin')
                    ->colors([
                        'primary' => Color::Amber,
                    ])
                    ->tenant(Team::class, slugAttribute: 'code')
                    ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
                    ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
                    ->pages([
                        Pages\Dashboard::class,
                    ])
                    ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
                    ->widgets([
                        Widgets\AccountWidget::class,
                    ])
            );
    }
}
