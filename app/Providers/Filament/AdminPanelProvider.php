<?php

namespace App\Providers\Filament;

use App\Enums\UserRole;
use App\Models\Team;
use Filament\Panel;
use Filament\PanelProvider;
use App\Filament\Components;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return
            SharedPanelSetup::commonSetup(
                $panel
                    ->brandName(function () {
                        $user = User::find(Auth::id());
                        return 'NovaTix ' . UserRole::tryFrom($user->role)->getLabel() ?? 'Unknown';
                    })
                    ->default()
                    ->id('admin')
                    ->domain(config('app.domain'))
                    ->path('admin')
                    ->tenant(
                        Team::class,
                        slugAttribute: 'code'
                    )
                    ->discoverResources(
                        in: app_path('Filament/Admin/Resources'),
                        for: 'App\\Filament\\Admin\\Resources'
                    )
                    ->discoverPages(
                        in: app_path('Filament/Admin/Pages'),
                        for: 'App\\Filament\\Admin\\Pages'
                    )
                    ->pages([])
                    ->discoverWidgets(
                        in: app_path('Filament/Admin/Widgets'),
                        for: 'App\\Filament\\Admin\\Widgets'
                    )
                    ->widgets([])
            );
    }
}
