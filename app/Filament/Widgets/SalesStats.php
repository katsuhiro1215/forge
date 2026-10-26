<?php

namespace App\Filament\Widgets;

use App\Models\License;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // 売上は返金・手動付与（0円）を除いた決済済みの合計
        $paid = fn () => Order::where('status', 'paid')->where('amount_jpy', '>', 0);
        $monthStart = now()->startOfMonth();

        return [
            Stat::make('今月の売上', '¥'.number_format($paid()->where('paid_at', '>=', $monthStart)->sum('amount_jpy')))
                ->description($paid()->where('paid_at', '>=', $monthStart)->count().'件'),
            Stat::make('累計売上', '¥'.number_format($paid()->sum('amount_jpy')))
                ->description($paid()->count().'件'),
            Stat::make('有効なライセンス', License::where('status', 'active')->count()),
            Stat::make('返金', Order::where('status', 'refunded')->count().'件')
                ->color(Order::where('status', 'refunded')->exists() ? 'danger' : 'gray'),
        ];
    }
}
