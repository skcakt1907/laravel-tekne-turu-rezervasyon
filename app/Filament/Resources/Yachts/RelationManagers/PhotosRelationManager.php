<?php

namespace App\Filament\Resources\Yachts\RelationManagers;

use App\Filament\Support\Translatable;
use App\Models\YachtPhoto;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    protected static ?string $title = 'Galeri';

    protected static ?string $modelLabel = 'fotoğraf';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('path')
                ->label('Görsel')
                ->image()
                ->disk('uploads')
                ->directory('yachts')
                ->imageEditor()
                ->maxSize(6144)
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->required()
                ->columnSpanFull(),
            Translatable::tabs(fn (string $locale) => [
                TextInput::make("alt.{$locale}")
                    ->label('Alternatif metin (SEO)')
                    ->maxLength(160),
            ]),
            TextInput::make('sort')->label('Sıra')->numeric()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('path')
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                ImageColumn::make('path')
                    ->label('Görsel')
                    ->disk('uploads')
                    ->height(64),
                IconColumn::make('is_cover')->label('Kapak')->boolean(),
                TextColumn::make('sort')->label('Sıra')->sortable(),
            ])
            ->headerActions([
                CreateAction::make()->label('Fotoğraf ekle'),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('makeCover')
                    ->label('Kapak yap')
                    ->icon('heroicon-o-star')
                    ->visible(fn (YachtPhoto $record) => ! $record->is_cover)
                    ->action(function (YachtPhoto $record) {
                        $record->yacht->photos()->update(['is_cover' => false]);
                        $record->update(['is_cover' => true]);
                    }),
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, YachtPhoto $record) => array_merge($data, [
                        'alt' => $record->getTranslations('alt'),
                    ])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
