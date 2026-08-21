<?php

namespace App\Filament\Owner\Resources\Yachts\Pages;

use App\Filament\Owner\Resources\Yachts\YachtResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListYachts extends ListRecords
{
    protected static string $resource = YachtResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Yeni yat ekle'),
        ];
    }
}
