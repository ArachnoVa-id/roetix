<?php

namespace App\Providers\Filament;

use App\Filament\Components;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Http\Middleware\Filament\UrlHistoryStack;
use Filament\Http\Middleware\AuthenticateSession;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class SharedPanelSetup
{
    public static function getMiddlewares(): array
    {
        return [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
            UrlHistoryStack::class,
        ];
    }

    public static function getAuthMiddlewares(): array
    {
        return [
            Authenticate::class,
        ];
    }

    public static function commonSetup(Panel $panel): Panel
    {
        return $panel
            ->favicon('/images/novatix-logo-white/favicon.ico')
            ->sidebarCollapsibleOnDesktop()
            ->middleware(self::getMiddlewares())
            ->authMiddleware(self::getAuthMiddlewares())
            ->colors([
                'primary' => Color::Amber,
            ])
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                Components\Widgets\NTCustomProfile::class,
                Components\Widgets\NTOrderChart::class,
                Components\Widgets\NTEventChart::class,
                Components\Widgets\NTVenueChart::class,
            ])
        ;
    }
}
