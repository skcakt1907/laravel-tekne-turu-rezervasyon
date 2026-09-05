<?php

namespace App\Filament\Resources\Collections\Tables;

use App\Models\Collection as CollectionModel;
use App\Services\CollectionExporter;
use App\Services\CollectionService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class CollectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period')
                    ->label('Dönem')
                    ->getStateUsing(fn (CollectionModel $record) => $record->periodLabel())
                    ->sortable(query: fn ($query, string $direction) => $query
                        ->orderBy('year', $direction)
                        ->orderBy('month', $direction)),
                TextColumn::make('owner.name')
                    ->label('Tur sahibi')
                    ->description(fn (CollectionModel $record) => $record->owner?->company_name)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reservation_count')->label('Rez.'),
                TextColumn::make('revenue')
                    ->label('Ciro')
                    ->money(fn (CollectionModel $record) => $record->currency)
                    ->sortable(),
                TextColumn::make('commission')
                    ->label('Komisyon')
                    ->money(fn (CollectionModel $record) => $record->currency)
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->color(fn (string $state) => $state === 'collected' ? 'success' : 'warning')
                    ->formatStateUsing(fn (string $state) => $state === 'collected' ? 'Tahsil edildi' : 'Bekliyor'),
                TextColumn::make('collected_at')->label('Tahsil')->date('d.m.Y')->default('—'),
                TextColumn::make('invoice_no')->label('Fatura')->default('—')->toggleable(),
            ])
            ->defaultSort('year', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(['pending' => 'Bekliyor', 'collected' => 'Tahsil edildi']),
                SelectFilter::make('owner')
                    ->label('Tur sahibi')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('year')
                    ->label('Yıl')
                    ->options(fn () => CollectionModel::query()
                        ->distinct()
                        ->orderByDesc('year')
                        ->pluck('year', 'year')
                        ->all()),
            ])
            ->headerActions([
                Action::make('build')
                    ->label('Dönem oluştur')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->schema([
                        Select::make('year')
                            ->label('Yıl')
                            ->options(fn () => collect(range((int) now()->year, (int) now()->year - 3))
                                ->mapWithKeys(fn ($y) => [$y => $y])->all())
                            ->default((int) now()->subMonthNoOverflow()->year)
                            ->required(),
                        Select::make('month')
                            ->label('Ay')
                            ->options(fn () => collect(range(1, 12))
                                ->mapWithKeys(fn ($m) => [$m => now()->setMonth($m)->translatedFormat('F')])->all())
                            ->default((int) now()->subMonthNoOverflow()->month)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $built = app(CollectionService::class)->build((int) $data['year'], (int) $data['month']);

                        Notification::make()
                            ->title($built->isEmpty()
                                ? 'Bu dönemde tamamlanmış rezervasyon yok.'
                                : $built->count().' tur sahibi için döküm hazırlandı.')
                            ->color($built->isEmpty() ? 'warning' : 'success')
                            ->send();
                    }),
                Action::make('exportAll')
                    ->label('Excel indir')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn () => app(CollectionExporter::class)->periods(
                        CollectionModel::with('owner')->orderByDesc('year')->orderByDesc('month')->get()
                    )),
            ])
            ->recordActions([
                Action::make('markCollected')
                    ->label('Tahsil edildi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CollectionModel $record) => $record->status !== 'collected')
                    ->schema([
                        TextInput::make('invoice_no')->label('Fatura no')->maxLength(60),
                        DatePicker::make('collected_at')
                            ->label('Tahsil tarihi')
                            ->default(now())
                            ->displayFormat('d.m.Y'),
                        Textarea::make('note')->label('Not')->rows(2),
                    ])
                    ->action(function (CollectionModel $record, array $data) {
                        app(CollectionService::class)->markCollected(
                            $record,
                            $data['invoice_no'] ?? null,
                            $data['note'] ?? null
                        );

                        if (! empty($data['collected_at'])) {
                            $record->fill(['collected_at' => $data['collected_at']])->save();
                        }

                        Notification::make()->title('Tahsilat işaretlendi.')->success()->send();
                    }),
                Action::make('markPending')
                    ->label('Geri al')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn (CollectionModel $record) => $record->status === 'collected')
                    ->requiresConfirmation()
                    ->action(function (CollectionModel $record) {
                        app(CollectionService::class)->markPending($record);
                        Notification::make()->title('Tahsilat geri alındı.')->success()->send();
                    }),
                Action::make('export')
                    ->label('Dökümü indir')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(fn (CollectionModel $record) => app(CollectionExporter::class)->detail($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('exportSelected')
                        ->label('Seçilenleri Excel indir')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(fn (EloquentCollection $records) => app(CollectionExporter::class)
                            ->periods($records->load('owner'), 'tahsilat-secili.xlsx')),
                ]),
            ])
            ->emptyStateHeading('Henüz döküm yok')
            ->emptyStateDescription('"Dönem oluştur" ile tamamlanmış rezervasyonlardan aylık hakediş üretebilirsiniz.');
    }
}
