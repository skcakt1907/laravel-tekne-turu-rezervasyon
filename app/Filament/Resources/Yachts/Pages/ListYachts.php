<?php

namespace App\Filament\Resources\Yachts\Pages;

use App\Enums\YachtStatus;
use App\Filament\Resources\Yachts\YachtResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListYachts extends ListRecords
{
    protected static string $resource = YachtResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Yeni ilan'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tümü'),
            'pending' => Tab::make('Onay bekliyor')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', YachtStatus::Pending))
                ->badge(fn () => static::getResource()::getModel()::where('status', YachtStatus::Pending)->count()),
            'published' => Tab::make('Yayında')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', YachtStatus::Published)),
            'draft' => Tab::make('Taslak')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', YachtStatus::Draft)),
            'closed' => Tab::make('Rezervasyona kapalı')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('is_open', false)),
        ];
    }
}
