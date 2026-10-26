<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\Release;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReleasesRelationManager extends RelationManager
{
    protected static string $relationship = 'releases';

    protected static ?string $title = 'リリース（配布ZIP）';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('version')->label('バージョン')->required()->maxLength(30)->placeholder('1.0.0')
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->where('product_id', $this->getOwnerRecord()->id)),
            DateTimePicker::make('released_at')->label('公開日時')
                ->helperText('空欄の間は購入者に表示されません（下書き）。無償アップデート期間の判定は、この日時を基準にします。'),
            FileUpload::make('file_path')->label('配布ZIP')->required()
                ->disk('local')->directory(fn () => 'releases/'.$this->getOwnerRecord()->slug)->visibility('private')
                ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])
                ->maxSize(200 * 1024)->downloadable(false)->columnSpanFull()
                ->helperText('購入者以外には公開されません（非公開ストレージに保存）。'),
            Textarea::make('changelog')->label('変更内容')->rows(4)->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('version')->label('バージョン')->weight('bold')->prefix('v'),
                TextColumn::make('released_at')->label('公開日時')->dateTime('Y/m/d H:i')->placeholder('下書き')->sortable(),
                TextColumn::make('size')->label('サイズ')->formatStateUsing(fn ($state) => $state ? number_format($state / 1048576, 1).' MB' : '—'),
                TextColumn::make('sha256')->label('SHA-256')->limit(12)->tooltip(fn ($record) => $record->sha256)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('released_at', 'desc')
            ->headerActions([CreateAction::make()->mutateDataUsing(fn (array $data) => Release::withFileMeta($data))])
            ->recordActions([
                EditAction::make()->mutateDataUsing(fn (array $data) => Release::withFileMeta($data)),
                DeleteAction::make()->modalDescription('配布ZIPも削除されます。ダウンロード履歴も消えます。'),
            ]);
    }
}
