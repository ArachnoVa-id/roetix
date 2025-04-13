<?php

namespace App\Filament\Components\Widgets;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class NTUserChart extends ChartWidget
{
    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return "Users in the last 30 days";
    }

    public static function canView(): bool
    {
        return User::find(Auth::id())->isAllowedInRoles([
            UserRole::ADMIN,
        ]);
    }

    protected function getData(): array
    {
        $query = User::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30));

        $users = $query
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $labels = collect(range(0, 29))
            ->map(fn($i) => now()->subDays(29 - $i)->format('Y-m-d'));

        $values = $labels->map(fn($date) => $users[$date] ?? 0);

        return [
            'labels' => $labels->toArray(),
            'datasets' => [
                [
                    'label' => 'Users per Day',
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
