<?php

namespace App\Filament\Resources\Downloads;

use App\Filament\Resources\Downloads\Pages\ListDownloads;
use App\Models\Download;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DownloadResource extends Resource
{
    protected static ?string $model = Download::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $modelLabel = 'ダウンロード履歴';

    protected static ?string $pluralModelLabel = 'ダウンロード履歴';

    protected static ?int $navigationSort = 40;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('downloaded_at')->label('日時')->dateTime('Y/m/d H:i')->sortable(),
                TextColumn::make('license.user.email')->label('購入者')->searchable(),
                TextColumn::make('license.product.name')->label('商品'),
            ])
            ->defaultSort('downloaded_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListDownloads::route('/')];
    }
}
