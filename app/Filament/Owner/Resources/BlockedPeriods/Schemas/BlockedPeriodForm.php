<?php

namespace App\Filament\Owner\Resources\BlockedPeriods\Schemas;

use App\Models\Yacht;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class BlockedPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('yacht_id')
                    ->label('Tur')
                    ->options(fn () => Yacht::where('owner_id', auth()->id())
                        ->get()
                        ->mapWithKeys(fn (Yacht $y) => [$y->id => $y->getTranslation('name', 'tr')]))
                    ->required()
                    ->searchable(),
                Select::make('reason')
                    ->label('Sebep')
                    ->options([
                        'manual' => 'Ozel kullanim',
                        'maintenance' => 'Bakim',
                    ])
                    ->default('manual')
                    ->required(),
                DateTimePicker::make('starts_at')
                    ->label('Baslangic')
                    ->seconds(false)
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->label('Bitis')
                    ->seconds(false)
                    ->required()
                    ->after('starts_at'),
                Textarea::make('note')
                    ->label('Not')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }
}
