<?php

namespace App\Filament\Resources\Collections\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/** Dokum otomatik uretilir; elle yalnizca fatura no ve not duzenlenir. */
class CollectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('revenue')->label('Ciro')->disabled(),
                TextInput::make('commission')->label('Komisyon')->disabled(),
                TextInput::make('reservation_count')->label('Rezervasyon sayisi')->disabled(),
                TextInput::make('currency')->label('Para birimi')->disabled(),
                TextInput::make('invoice_no')->label('Fatura no')->maxLength(60),
                Textarea::make('note')->label('Not')->rows(3)->columnSpanFull(),
            ]);
    }
}
