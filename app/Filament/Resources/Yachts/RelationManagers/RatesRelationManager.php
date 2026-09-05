<?php

namespace App\Filament\Resources\Yachts\RelationManagers;

use App\Models\YachtRate;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Kişi başı günlük tur fiyatı. Temel fiyat = sezon tarihleri boş olan kayıt.
 * Sezon fiyatı = tarih aralığı dolu kayıt. Çakışırsa dar aralık geniş aralığı ezer.
 */
class RatesRelationManager extends RelationManager
{
    protected static string $relationship = 'rates';

    protected static ?string $title = 'Fiyatlar';

    protected static ?string $modelLabel = 'fiyat';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('label')
                    ->label('Etiket')
                    ->placeholder('Temel fiyat / Yüksek sezon')
                    ->maxLength(60),
                DatePicker::make('season_start')
                    ->label('Sezon başlangıcı')
                    ->helperText('Boş bırakılırsa temel fiyat olur.')
                    ->displayFormat('d.m.Y'),
                DatePicker::make('season_end')
                    ->label('Sezon bitişi')
                    ->displayFormat('d.m.Y')
                    ->afterOrEqual('season_start')
                    ->requiredWith('season_start'),
                TextInput::make('price')
                    ->label('Yetişkin fiyatı (kişi başı)')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->prefix(fn () => config('yacht.currencies')[$this->getOwnerRecord()->currency] ?? ''),
                TextInput::make('price_child')
                    ->label('Çocuk fiyatı (kişi başı)')
                    ->helperText('Boş bırakılırsa yetişkin fiyatıyla aynı sayılır.')
                    ->numeric()
                    ->minValue(0)
                    ->prefix(fn () => config('yacht.currencies')[$this->getOwnerRecord()->currency] ?? ''),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                TextColumn::make('label')
                    ->label('Etiket')
                    ->default('—'),
                TextColumn::make('season')
                    ->label('Geçerlilik')
                    ->getStateUsing(fn (YachtRate $record) => $record->isBase()
                        ? 'Temel fiyat (tüm yıl)'
                        : $record->season_start->format('d.m.Y').' – '.$record->season_end->format('d.m.Y')),
                TextColumn::make('price')
                    ->label('Yetişkin')
                    ->money(fn () => $this->getOwnerRecord()->currency)
                    ->sortable(),
                TextColumn::make('price_child')
                    ->label('Çocuk')
                    ->money(fn () => $this->getOwnerRecord()->currency)
                    ->placeholder('Yetişkinle aynı'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Fiyat ekle')
                    ->mutateFormDataUsing(fn (array $data) => array_merge($data, ['unit' => 'day', 'min_duration' => 1])),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
