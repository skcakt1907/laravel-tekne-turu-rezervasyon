<?php

namespace App\Filament\Owner\Resources\BlockedPeriods;

use App\Filament\Owner\Resources\BlockedPeriods\Pages\CreateBlockedPeriod;
use App\Filament\Owner\Resources\BlockedPeriods\Pages\EditBlockedPeriod;
use App\Filament\Owner\Resources\BlockedPeriods\Pages\ListBlockedPeriods;
use App\Filament\Owner\Resources\BlockedPeriods\Schemas\BlockedPeriodForm;
use App\Filament\Owner\Resources\BlockedPeriods\Tables\BlockedPeriodsTable;
use App\Filament\Owner\Concerns\ScopedToOwner;
use App\Models\BlockedPeriod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BlockedPeriodResource extends Resource
{
    use ScopedToOwner;

    protected static ?string $model = BlockedPeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'kapalı tarih';

    protected static ?string $pluralModelLabel = 'kapalı tarihler';

    protected static ?string $navigationLabel = 'Kapalı Tarihler';

    protected static ?string $recordTitleAttribute = 'reason';

    /** Blok kaydı yata bağlı; kapsam yacht.owner_id üzerinden daraltılır. */
    protected static function ownerColumn(): string
    {
        return 'yacht.owner_id';
    }

    public static function form(Schema $schema): Schema
    {
        return BlockedPeriodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlockedPeriodsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlockedPeriods::route('/'),
            'create' => CreateBlockedPeriod::route('/create'),
            'edit' => EditBlockedPeriod::route('/{record}/edit'),
        ];
    }
}
