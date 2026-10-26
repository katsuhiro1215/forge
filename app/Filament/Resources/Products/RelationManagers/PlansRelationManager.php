<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlansRelationManager extends RelationManager
{
    protected static string $relationship = 'plans';

    protected static ?string $title = 'プラン・価格';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('code')->label('種別')->required()->options([
                'single' => '1サイト', 'multi' => '複数サイト（上位）',
            ])->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->where('product_id', $this->getOwnerRecord()->id)),
            TextInput::make('name')->label('プラン名')->required()->maxLength(80),
            TextInput::make('price_jpy')->label('価格（税込）')->required()->numeric()->minValue(0)->prefix('¥')
                ->helperText('変更は今後の新規購入に反映されます。購入済みのライセンスには影響しません。'),
            TextInput::make('site_limit')->label('利用可能サイト数')->numeric()->minValue(1)->helperText('空欄で無制限'),
            TextInput::make('support_months')->label('サポート期間（月）')->numeric()->required()->default(12)->minValue(0),
            TextInput::make('update_months')->label('無償アップデート期間（月）')->numeric()->required()->default(12)->minValue(0),
            Select::make('status')->label('状態')->required()->default('active')->options([
                'active' => '販売中', 'archived' => '販売終了',
            ]),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('プラン名')->weight('bold'),
                TextColumn::make('price_jpy')->label('価格')->formatStateUsing(fn ($state) => '¥'.number_format($state)),
                TextColumn::make('site_limit')->label('サイト数')->formatStateUsing(fn ($state) => $state ? $state.'サイト' : '無制限'),
                TextColumn::make('support_months')->label('サポート')->suffix('か月'),
                TextColumn::make('update_months')->label('アップデート')->suffix('か月'),
                TextColumn::make('status')->label('状態')->badge()
                    ->color(fn (string $state) => $state === 'active' ? 'success' : 'gray')
                    ->formatStateUsing(fn (string $state) => $state === 'active' ? '販売中' : '販売終了'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()]);
    }
}
