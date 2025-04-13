<?php

namespace App\Filament\Components\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class NTCustomProfile extends Widget
{
    protected static string $view = 'filament.widgets.nt-custom-profile';

    protected int | string | array $columnSpan = 2;

    protected static ?int $sort = 1;

    public $user;

    public function mount(): void
    {
        $this->user = User::find(Auth::id());
    }

    public static function canView(): bool
    {
        return Auth::check();
    }
}
