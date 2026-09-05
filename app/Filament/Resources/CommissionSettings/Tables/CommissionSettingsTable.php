<?php

namespace App\Filament\Resources\CommissionSettings\Tables;

use App\Models\CommissionSetting;
use App\Models\User;
use App\Models\Yacht;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommissionSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('scope')
                    ->label('Kapsam')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        CommissionSetting::SCOPE_YACHT => 'success',
                        CommissionSetting::SCOPE_OWNER => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        CommissionSetting::SCOPE_YACHT => 'Tur',
                        CommissionSetting::SCOPE_OWNER => 'Tur sahibi',
                        default => 'Genel',
                    }),
                TextColumn::make('target_id')
                    ->label('Hedef')
                    ->getStateUsing(function (CommissionSetting $record) {
                        if ($record->scope === CommissionSetting::SCOPE_GLOBAL) {
                            return 'Tum platform';
                        }

                        if ($record->scope === CommissionSetting::SCOPE_YACHT) {
                            return Yacht::find($record->target_id)?->getTranslation('name', 'tr') ?? '-';
                        }

                        return User::find($record->target_id)?->name ?? '-';
                    }),
                TextColumn::make('rate')
                    ->label('Oran')
                    ->formatStateUsing(fn ($state) => '%'.rtrim(rtrim(number_format((float) $state, 2, ',', '.'), '0'), ','))
                    ->sortable(),
                TextColumn::make('effective_from')
                    ->label('Gecerlilik')
                    // ->default('Hemen') KULLANMA: date() bunu tarih olarak ayristirmaya calisir
                    ->getStateUsing(fn (CommissionSetting $record) => $record->effective_from?->format('d.m.Y') ?? 'Hemen'),
                TextColumn::make('note')->label('Not')->limit(40)->default('-'),
                TextColumn::make('updated_at')->label('Guncellendi')->since()->toggleable(),
            ])
            ->defaultSort('scope')
            ->filters([
                SelectFilter::make('scope')
                    ->label('Kapsam')
                    ->options([
                        CommissionSetting::SCOPE_GLOBAL => 'Genel',
                        CommissionSetting::SCOPE_OWNER => 'Tur sahibi',
                        CommissionSetting::SCOPE_YACHT => 'Tur',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                // Genel oran silinemez: silinirse komisyon 0 olur.
                DeleteAction::make()
                    ->visible(fn (CommissionSetting $record) => $record->scope !== CommissionSetting::SCOPE_GLOBAL),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Komisyon orani tanimli degil')
            ->emptyStateDescription('En az bir genel oran tanimlayin; yoksa komisyon sifir hesaplanir.');
    }
}
