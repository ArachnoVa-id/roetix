<?php
// app/Helpers/RoutingHelper.php

namespace App\Helpers;

class RoutingHelper
{
    public static function redirectToDashboard($user)
    {
        $firstTeam = optional($user->teams()->first())->name;

        return $firstTeam
            ? redirect()->route('filament.admin.pages.dashboard', ['tenant' => $firstTeam])
            : redirect()->route('login');
    }
}
