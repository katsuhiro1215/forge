<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\PlansRelationManager;
use App\Filament\Resources\Products\RelationManagers\ReleasesRelationManager;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = '商品';

    protected static ?string $pluralModelLabel = '商品';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('商品名')->required()->maxLength(100),
            TextInput::make('slug')->label('スラッグ（URL）')->required()->alphaDash()->maxLength(60)
                ->unique(ignoreRecord: true)->helperText('/theme/{スラッグ} になります。公開後の変更は避けてください。'),
            Select::make('type')->label('種別')->required()->default('theme')->options([
                'theme' => 'テーマ', 'block' => 'ブロック', 'ui' => 'UIキット', 'dashboard' => 'ダッシュボード',
            ]),
            Select::make('status')->label('公開状態')->required()->default('draft')->options([
                'draft' => '下書き', 'published' => '公開',
            ]),
            TextInput::make('tagline')->label('キャッチコピー')->maxLength(150),
            TextInput::make('demo_path')->label('デモのパス')->maxLength(60)
                ->helperText('demo.forge.smartsprouts.jp/{パス}/ にリンクします（例: ork）'),
            TextInput::make('sort')->label('並び順')->numeric()->required()->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('商品名')->searchable()->weight('bold'),
                TextColumn::make('slug')->label('スラッグ')->color('gray'),
                TextColumn::make('type')->label('種別')->badge()
                    ->formatStateUsing(fn (string $state) => ['theme' => 'テーマ', 'block' => 'ブロック', 'ui' => 'UIキット', 'dashboard' => 'ダッシュボード'][$state] ?? $state),
                TextColumn::make('status')->label('公開状態')->badge()
                    ->color(fn (string $state) => $state === 'published' ? 'success' : 'gray')
                    ->formatStateUsing(fn (string $state) => $state === 'published' ? '公開' : '下書き'),
                TextColumn::make('plans_count')->label('プラン数')->counts('plans'),
                TextColumn::make('releases_count')->label('リリース数')->counts('releases'),
                TextColumn::make('sort')->label('並び順')->sortable(),
            ])
            ->defaultSort('sort')
            ->recordActions([EditAction::make()]);
        // 購入履歴との整合のため、商品は削除せず「下書き」で非公開にする運用
    }

    public static function getRelations(): array
    {
        return [PlansRelationManager::class, ReleasesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
