<?php

namespace App\Filament\Components\Widgets;

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class NTEventChart extends ChartWidget
{
    protected static ?int $sort = 4;

    public function getHeading(): string
    {
        $team = $team ?? Filament::getTenant();
        return 'Events for ' . ($team?->name ?? " All Teams");
    }

    public static function canView(): bool
    {
        return User::find(Auth::id())->isAllowedInRoles([
            UserRole::ADMIN,
            UserRole::EVENT_ORGANIZER,
            UserRole::VENDOR
        ]);
    }

    protected function getData(): array
    {
        $query = Event::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30));

        $team = $team ?? Filament::getTenant();
        if (!empty($team)) {
            $query->where('team_id', $team->id);
        }

        $events = $query
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $labels = collect(range(0, 29))
            ->map(fn($i) => now()->subDays(29 - $i)->format('Y-m-d'));

        $values = $labels->map(fn($date) => $events[$date] ?? 0);

        return [
            'labels' => $labels->toArray(),
            'datasets' => [
                [
                    'label' => 'Events per Day',
                    'data' => $values->toArray(),
                    'fill' => false,
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'tension' => 0.1,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'min' => 0,
                    'ticks' => [
                        'stepSize' => 1,
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
