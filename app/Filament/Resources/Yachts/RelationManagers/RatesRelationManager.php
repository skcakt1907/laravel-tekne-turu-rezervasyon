<?php

namespace App\Filament\Resources\Yachts\RelationManagers;

use App\Enums\RentalUnit;
use App\Models\YachtRate;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Temel fiyat = sezon tarihleri boş olan kayıt.
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
                Select::make('unit')
                    ->label('Kiralama birimi')
                    ->options(RentalUnit::options())
                    ->required()
                    ->live(),
                TextInput::make('label')
                    ->label('Etiket')
                    ->placeholder('Temel fiyat / Yüksek sezon')
                    ->maxLength(60),
                DatePicker::make('season_start')
                    ->label('Sezon başlangıcı')
                    ->helperText('Boş bırakılırsa temel fiyat olur.')
                    ->displayFormat('d.m.Y')
                    ->live(),
                DatePicker::make('season_end')
                    ->label('Sezon bitişi')
                    ->displayFormat('d.m.Y')
                    ->afterOrEqual('season_start')
                    ->requiredWith('season_start'),
                TextInput::make('price')
                    ->label(fn (Get $get) => match ($get('unit')) {
                        'hour' => 'Saat başı fiyat',
                        'week' => 'Hafta başı fiyat',
                        default => 'Gün başı fiyat',
                    })
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->prefix(fn () => config('yacht.currencies')[$this->getOwnerRecord()->currency] ?? ''),
                TextInput::make('min_duration')
                    ->label(fn (Get $get) => match ($get('unit')) {
                        'hour' => 'En az kaç saat',
                        'week' => 'En az kaç hafta',
                        default => 'En az kaç gün',
                    })
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->defaultSort('unit')
            ->columns([
                TextColumn::make('unit')
                    ->label('Birim')
                    ->badge()
                    ->formatStateUsing(fn (RentalUnit $state) => $state->label()),
                TextColumn::make('label')
                    ->label('Etiket')
                    ->default('—'),
                TextColumn::make('season')
                    ->label('Geçerlilik')
                    ->getStateUsing(fn (YachtRate $record) => $record->isBase()
                        ? 'Temel fiyat (tüm yıl)'
                        : $record->season_start->format('d.m.Y').' – '.$record->season_end->format('d.m.Y')),
                TextColumn::make('price')
                    ->label('Fiyat')
                    ->money(fn () => $this->getOwnerRecord()->currency)
                    ->sortable(),
                TextColumn::make('min_duration')->label('En az süre'),
            ])
            ->filters([
                SelectFilter::make('unit')
                    ->label('Birim')
                    ->options(RentalUnit::options()),
            ])
            ->headerActions([
                CreateAction::make()->label('Fiyat ekle'),
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
