<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Müşteri/lead takibi: telefonla arayan, soru soran müşteriler için serbest not. */
class CustomerNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $title = 'Notlar';

    protected static ?string $modelLabel = 'not';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Başlık')
                    ->maxLength(150)
                    ->placeholder('Örn: Telefon görüşmesi'),
                Textarea::make('body')
                    ->label('İçerik')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Başlık')->default('—'),
                TextColumn::make('body')->label('İçerik')->limit(80)->wrap(),
                TextColumn::make('admin.name')->label('Yazan')->default('—'),
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Not ekle')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['admin_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
