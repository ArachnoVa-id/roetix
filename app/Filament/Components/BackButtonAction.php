<?php

namespace App\Filament\Components;

use Filament\Actions\Action;

class BackButtonAction
{
    public static function make($action): Action
    {
        return $action
            ->label('Back')
            ->action(fn() => self::popAndRedirectUrlStack())
            ->icon('heroicon-o-arrow-left')
            ->color('info');
    }

    protected static function popAndRedirectUrlStack(string $defaultRoute = 'home')
    {
        $urlStack = session()->get('url_stack', []);

        // Pop the current URL
        array_pop($urlStack);

        // Get the last URL from the stack
        $previousUrl = array_pop($urlStack) ?? route($defaultRoute);

        // Save the modified stack
        session(['url_stack' => $urlStack]);

        // redirect
        redirect($previousUrl);
    }
}
