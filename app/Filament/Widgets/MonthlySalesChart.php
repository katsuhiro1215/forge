<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class MonthlySalesChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = '月別売上（直近6か月）';

    protected function getData(): array
    {
        $start = now()->startOfMonth()->subMonths(5);
        $orders = Order::where('status', 'paid')->where('amount_jpy', '>', 0)
            ->where('paid_at', '>=', $start)->get(['amount_jpy', 'paid_at']);

        $labels = [];
        $totals = [];
        for ($i = 0; $i < 6; $i++) {
            $month = $start->copy()->addMonths($i);
            $labels[] = $month->format('Y/n');
            $totals[] = $orders->filter(fn ($o) => $o->paid_at->isSameMonth($month))->sum('amount_jpy');
        }

        return [
            'datasets' => [['label' => '売上（円）', 'data' => $totals]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
