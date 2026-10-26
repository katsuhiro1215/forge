<?php

namespace App\Filament\Resources\Licenses;

use App\Filament\Resources\Licenses\Pages\CreateLicense;
use App\Filament\Resources\Licenses\Pages\ListLicenses;
use App\Models\License;
use App\Models\Plan;
use App\Models\Product;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class LicenseResource extends Resource
{
    protected static ?string $model = License::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $modelLabel = 'ライセンス';

    protected static ?string $pluralModelLabel = 'ライセンス';

    protected static ?int $navigationSort = 30;

    /** 手動付与フォーム（検証用・モニター提供・補償など、決済を伴わない付与） */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')->label('購入者（メールアドレス）')->required()->searchable()
                ->getSearchResultsUsing(fn (string $search) => User::where('email', 'like', "%{$search}%")->limit(20)->pluck('email', 'id'))
                ->getOptionLabelUsing(fn ($value) => User::find($value)?->email)
                ->createOptionForm([
                    TextInput::make('name')->label('お名前')->required(),
                    TextInput::make('email')->label('メールアドレス')->email()->required()->unique('users', 'email'),
                ])
                ->createOptionUsing(fn (array $data) => User::create($data + ['password' => Str::random(40)])->getKey())
                ->helperText('新規の方は「＋」から作成できます。パスワードはご本人が「パスワードをお忘れの方」から設定します。'),
            Select::make('product_id')->label('商品')->required()->live()
                ->options(fn () => Product::orderBy('sort')->pluck('name', 'id')),
            Select::make('plan_id')->label('プラン')->required()
                ->options(fn (Get $get) => Plan::where('product_id', $get('product_id'))->pluck('name', 'id')),
            DateTimePicker::make('support_until')->label('サポート期限')->required()->default(now()->addYear()),
            DateTimePicker::make('updates_until')->label('無償アップデート期限')->required()->default(now()->addYear()),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('発行日')->dateTime('Y/m/d')->sortable(),
                TextColumn::make('user.email')->label('購入者')->searchable(),
                TextColumn::make('product.name')->label('商品'),
                TextColumn::make('plan.name')->label('プラン'),
                TextColumn::make('updates_until')->label('更新期限')->date('Y/m/d')->sortable()
                    ->color(fn ($record) => $record->updates_until->isPast() ? 'danger' : null),
                TextColumn::make('status')->label('状態')->badge()
                    ->color(fn (string $state) => $state === 'active' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state) => $state === 'active' ? '有効' : '失効'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('状態')->options(['active' => '有効', 'revoked' => '失効']),
            ])
            ->recordActions([
                Action::make('revoke')->label('失効')->icon(Heroicon::OutlinedNoSymbol)->color('danger')
                    ->visible(fn (License $record) => $record->isActive())
                    ->requiresConfirmation()->modalDescription('ダウンロードできなくなります。後から再有効化できます。')
                    ->action(fn (License $record) => $record->update(['status' => 'revoked'])),
                Action::make('restore')->label('再有効化')->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->visible(fn (License $record) => ! $record->isActive())
                    ->requiresConfirmation()
                    ->action(fn (License $record) => $record->update(['status' => 'active'])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLicenses::route('/'),
            'create' => CreateLicense::route('/create'),
        ];
    }
}
