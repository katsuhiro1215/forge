<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $modelLabel = '注文';

    protected static ?string $pluralModelLabel = '注文';

    protected static ?int $navigationSort = 20;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('paid_at')->label('決済日時')->dateTime('Y/m/d H:i')->sortable(),
                TextColumn::make('user.email')->label('購入者')->searchable(),
                TextColumn::make('plan.product.name')->label('商品'),
                TextColumn::make('plan.name')->label('プラン'),
                TextColumn::make('amount_jpy')->label('金額')->formatStateUsing(fn ($state) => '¥'.number_format($state))->sortable(),
                TextColumn::make('status')->label('状態')->badge()
                    ->color(fn (string $state) => $state === 'paid' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state) => $state === 'paid' ? '決済済み' : '返金済み'),
                TextColumn::make('stripe_session_id')->label('種別')
                    ->formatStateUsing(fn (string $state) => str_starts_with($state, 'manual_') ? '手動付与' : 'Stripe')
                    ->badge()->color('gray'),
            ])
            ->defaultSort('paid_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('状態')->options(['paid' => '決済済み', 'refunded' => '返金済み']),
            ])
            ->emptyStateHeading('注文はまだありません')
            ->emptyStateDescription('返金はStripeダッシュボードで行うと、自動でここに反映されライセンスが失効します。');
    }

    public static function getPages(): array
    {
        return ['index' => ListOrders::route('/')];
    }
}
