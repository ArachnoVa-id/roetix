<?php

namespace App\Filament\Components\Widgets;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class NTOrderChart extends ChartWidget
{
    protected static ?int $sort = 3;

    public function getColumnSpan(): int | string | array
    {
        return [
            'default' => 2,
            'sm' => 2,
            'md' => 1,
        ];
    }

    public function getHeading(): string
    {
        return 'Orders for ' . " All Orders";
    }

    public static function canView(): bool
    {
        return User::find(Auth::id())->isAllowedInRoles([
            UserRole::ADMIN,
            UserRole::EVENT_ORGANIZER,
        ]);
    }

    protected function getData(): array
    {
        $query = Order::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30));

        $orders = $query
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $labels = collect(range(0, 29))
            ->map(fn($i) => now()->subDays(29 - $i)->format('Y-m-d'));

        $values = $labels->map(fn($date) => $orders[$date] ?? 0);

        return [
            'labels' => $labels->toArray(),
            'datasets' => [
                [
                    'label' => 'Orders per Day',
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
