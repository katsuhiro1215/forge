<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Post;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $modelLabel = 'お知らせ';

    protected static ?string $pluralModelLabel = 'お知らせ';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('タイトル')->required()->maxLength(150)->columnSpanFull()
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set, $get) => $get('slug') ?: $set('slug', Str::limit(Str::slug($state), 60, ''))),
            TextInput::make('slug')->label('スラッグ（URL）')->required()->alphaDash()->maxLength(80)
                ->unique(ignoreRecord: true)->helperText('/news/{スラッグ} になります。日本語タイトルの場合は英数字で入力してください（例: ork-1-1-0）'),
            Select::make('type')->label('種類')->required()->default('news')->options(Post::TYPES),
            Select::make('product_id')->label('関連する商品')->relationship('product', 'name')->placeholder('なし（全体のお知らせ）'),
            Select::make('status')->label('公開状態')->required()->default('draft')->options(['draft' => '下書き', 'published' => '公開']),
            DateTimePicker::make('published_at')->label('公開日時')->default(now())
                ->helperText('未来の日時を入れると、その時刻に自動で公開されます（公開状態が「公開」の場合）。'),
            Textarea::make('excerpt')->label('概要（任意）')->rows(2)->maxLength(300)->columnSpanFull()
                ->helperText('検索結果やSNSで表示される説明文。空欄なら本文の冒頭を使います。'),
            MarkdownEditor::make('body')->label('本文')->required()->columnSpanFull()->minHeight('320px')
                ->toolbarButtons(['bold', 'italic', 'link', 'heading', 'bulletList', 'orderedList', 'blockquote', 'codeBlock', 'undo', 'redo']),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('published_at')->label('公開日時')->dateTime('Y/m/d H:i')->sortable()->placeholder('未設定'),
                TextColumn::make('title')->label('タイトル')->searchable()->weight('bold')->limit(40),
                TextColumn::make('type')->label('種類')->badge()->formatStateUsing(fn (string $state) => Post::TYPES[$state] ?? $state),
                TextColumn::make('product.name')->label('商品')->placeholder('—'),
                TextColumn::make('status')->label('状態')->badge()
                    ->state(fn (Post $record) => $record->status !== 'published' ? '下書き' : ($record->published_at?->isFuture() ? '予約' : '公開中'))
                    ->color(fn (string $state) => match ($state) { '公開中' => 'success', '予約' => 'warning', default => 'gray' }),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                SelectFilter::make('type')->label('種類')->options(Post::TYPES),
                SelectFilter::make('status')->label('公開状態')->options(['draft' => '下書き', 'published' => '公開']),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
