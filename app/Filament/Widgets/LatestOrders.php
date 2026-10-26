<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = '最近の注文';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Order::query()->latest('paid_at')->limit(8))
            ->columns([
                TextColumn::make('paid_at')->label('決済日時')->dateTime('Y/m/d H:i'),
                TextColumn::make('user.email')->label('購入者'),
                TextColumn::make('plan.product.name')->label('商品'),
                TextColumn::make('amount_jpy')->label('金額')->formatStateUsing(fn ($state) => '¥'.number_format($state)),
                TextColumn::make('status')->label('状態')->badge()
                    ->color(fn (string $state) => $state === 'paid' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state) => $state === 'paid' ? '決済済み' : '返金済み'),
            ])
            ->paginated(false);
    }
}
