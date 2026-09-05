<?php

namespace App\Filament\Owner\Resources\Yachts\Tables;

use App\Enums\YachtStatus;
use App\Models\Yacht;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class YachtsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover')
                    ->label('')
                    ->getStateUsing(fn (Yacht $record) => $record->coverUrl())
                    ->defaultImageUrl(asset('images/yacht-placeholder.svg'))
                    ->height(44),
                TextColumn::make('name')
                    ->label('Tur')
                    ->getStateUsing(fn (Yacht $record) => $record->getTranslation('name', 'tr'))
                    ->searchable(query: fn ($query, string $search) => $query->where('name', 'like', "%{$search}%")),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (YachtStatus $state) => $state->label())
                    ->color(fn (YachtStatus $state) => $state->color())
                    ->description(fn (Yacht $record) => $record->status === YachtStatus::Rejected
                        ? $record->reject_reason
                        : null),
                TextColumn::make('photos_count')
                    ->label('Foto')
                    ->counts('photos')
                    ->badge()
                    ->color(fn (int $state) => $state >= config('yacht.min_photos', 4) ? 'success' : 'warning'),
                TextColumn::make('price_from')
                    ->label('Fiyat')
                    ->getStateUsing(fn (Yacht $record) => $record->price_from
                        ? money($record->price_from, $record->currency)
                        : 'Fiyat girilmedi')
                    ->color(fn (Yacht $record) => $record->price_from ? null : 'warning'),
                ToggleColumn::make('is_open')
                    ->label('Rezervasyona açık')
                    ->beforeStateUpdated(function (Yacht $record, bool $state) {
                        Notification::make()
                            ->title($state ? 'Rezervasyona açıldı.' : 'Rezervasyona kapatıldı.')
                            ->success()
                            ->send();
                    }),
                TextColumn::make('reservations_count')
                    ->label('Rezervasyon')
                    ->counts('reservations')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make()->label('Düzenle'),
                Action::make('submit')
                    ->label('Onaya gönder')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (Yacht $record) => in_array($record->status, [
                        YachtStatus::Draft, YachtStatus::Rejected,
                    ], true))
                    ->requiresConfirmation()
                    ->modalDescription('İlanınız yönetici onayından sonra sitede yayınlanır.')
                    ->action(function (Yacht $record) {
                        $missing = static::missingRequirements($record);

                        if ($missing) {
                            Notification::make()
                                ->title('İlan onaya gönderilemedi')
                                ->body(implode(' ', $missing))
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->forceFill([
                            'status' => YachtStatus::Pending,
                            'reject_reason' => null,
                        ])->save();

                        Notification::make()->title('İlanınız onaya gönderildi.')->success()->send();
                    }),
                Action::make('view')
                    ->label('Sitede gör')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Yacht $record) => route('tours.show', $record->slug))
                    ->openUrlInNewTab()
                    ->visible(fn (Yacht $record) => $record->status === YachtStatus::Published),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('Henüz tur eklemediniz')
            ->emptyStateDescription('İlanınızı ekleyin, fotoğraf ve fiyatları girin, onaya gönderin.');
    }

    /** @return array<int, string> */
    private static function missingRequirements(Yacht $yacht): array
    {
        $missing = [];
        $minPhotos = (int) config('yacht.min_photos', 4);

        if ($yacht->photos()->count() < $minPhotos) {
            $missing[] = "En az {$minPhotos} fotoğraf gerekiyor.";
        }

        if ($yacht->rates()->count() === 0) {
            $missing[] = 'En az bir fiyat girmelisiniz.';
        }

        if (! $yacht->capacity) {
            $missing[] = 'Günlük tur kapasitesi girilmeli.';
        }

        return $missing;
    }
}
