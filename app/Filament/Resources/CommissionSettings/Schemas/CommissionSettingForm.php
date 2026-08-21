<?php

namespace App\Filament\Resources\CommissionSettings\Schemas;

use App\Enums\UserRole;
use App\Models\CommissionSetting;
use App\Models\User;
use App\Models\Yacht;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

/**
 * Üç kademeli oran: genel → yat sahibi → yat. En dar tanım geçerlidir.
 * Oran yalnızca YENİ onaylarda geçerli olur; onaylanmış rezervasyonlarda
 * oran dondurulmuştur.
 */
class CommissionSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('scope')
                    ->label('Kapsam')
                    ->options([
                        CommissionSetting::SCOPE_GLOBAL => 'Genel (varsayılan)',
                        CommissionSetting::SCOPE_OWNER => 'Yat sahibi bazlı',
                        CommissionSetting::SCOPE_YACHT => 'Yat bazlı',
                    ])
                    ->default(CommissionSetting::SCOPE_GLOBAL)
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('target_id', null))
                    ->helperText('En dar tanım kazanır: yat > yat sahibi > genel.'),

                Select::make('target_id')
                    ->label(fn (Get $get) => $get('scope') === CommissionSetting::SCOPE_YACHT ? 'Yat' : 'Yat sahibi')
                    ->options(function (Get $get) {
                        return match ($get('scope')) {
                            CommissionSetting::SCOPE_YACHT => Yacht::with('owner')
                                ->get()
                                ->mapWithKeys(fn (Yacht $y) => [
                                    $y->id => $y->getTranslation('name', 'tr').' — '.($y->owner?->name ?? ''),
                                ]),
                            CommissionSetting::SCOPE_OWNER => User::where('role', UserRole::Owner)
                                ->orderBy('name')
                                ->pluck('name', 'id'),
                            default => [],
                        };
                    })
                    ->searchable()
                    ->preload()
                    ->required(fn (Get $get) => $get('scope') !== CommissionSetting::SCOPE_GLOBAL)
                    ->visible(fn (Get $get) => $get('scope') !== CommissionSetting::SCOPE_GLOBAL),

                TextInput::make('rate')
                    ->label('Komisyon oranı')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.5)
                    ->suffix('%'),

                DatePicker::make('effective_from')
                    ->label('Geçerlilik başlangıcı')
                    ->displayFormat('d.m.Y')
                    ->helperText('Boş bırakılırsa hemen geçerli olur.'),

                Textarea::make('note')
                    ->label('Not')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }
}
