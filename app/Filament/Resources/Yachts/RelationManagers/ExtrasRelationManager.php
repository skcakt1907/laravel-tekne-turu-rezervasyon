<?php

namespace App\Filament\Resources\Yachts\RelationManagers;

use App\Enums\ExtraCalculation;
use App\Filament\Support\Translatable;
use App\Models\YachtExtra;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Yakıt, temizlik, kaptan, teslim limanı farkı, su sporları ekipmanı... */
class ExtrasRelationManager extends RelationManager
{
    protected static string $relationship = 'extras';

    protected static ?string $title = 'Ek Ücretler';

    protected static ?string $modelLabel = 'ek ücret';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Translatable::tabs(fn (string $locale, bool $isDefault) => [
                    TextInput::make("name.{$locale}")
                        ->label('Kalem adı')
                        ->required($isDefault)
                        ->maxLength(120),
                ]),
                TextInput::make('amount')
                    ->label('Tutar')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->prefix(fn () => config('yacht.currencies')[$this->getOwnerRecord()->currency] ?? ''),
                Select::make('calculation')
                    ->label('Hesaplama')
                    ->options(collect(ExtraCalculation::cases())
                        ->mapWithKeys(fn (ExtraCalculation $c) => [$c->value => $c->label()])->all())
                    ->default(ExtraCalculation::Fixed->value)
                    ->required(),
                Toggle::make('is_required')
                    ->label('Zorunlu')
                    ->helperText('Zorunlu kalemler tahmini tutara otomatik eklenir.'),
                TextInput::make('sort')->label('Sıra')->numeric()->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')
                    ->label('Kalem')
                    ->getStateUsing(fn (YachtExtra $record) => $record->getTranslation('name', 'tr')),
                TextColumn::make('amount')
                    ->label('Tutar')
                    ->money(fn () => $this->getOwnerRecord()->currency),
                TextColumn::make('calculation')
                    ->label('Hesaplama')
                    ->badge()
                    ->formatStateUsing(fn (ExtraCalculation $state) => $state->label()),
                IconColumn::make('is_required')->label('Zorunlu')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label('Ek ücret ekle'),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, YachtExtra $record) => array_merge($data, [
                        'name' => $record->getTranslations('name'),
                    ])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
