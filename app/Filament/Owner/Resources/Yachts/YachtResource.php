<?php

namespace App\Filament\Owner\Resources\Yachts;

use App\Filament\Owner\Resources\Yachts\Pages\CreateYacht;
use App\Filament\Owner\Resources\Yachts\Pages\EditYacht;
use App\Filament\Owner\Resources\Yachts\Pages\ListYachts;
use App\Filament\Owner\Resources\Yachts\Schemas\YachtForm;
use App\Filament\Owner\Resources\Yachts\Tables\YachtsTable;
use App\Filament\Owner\Concerns\ScopedToOwner;
use App\Filament\Resources\Yachts\RelationManagers\ExtrasRelationManager;
use App\Filament\Resources\Yachts\RelationManagers\PhotosRelationManager;
use App\Filament\Resources\Yachts\RelationManagers\RatesRelationManager;
use App\Models\Yacht;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class YachtResource extends Resource
{
    use ScopedToOwner;

    protected static ?string $model = Yacht::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'tur';

    protected static ?string $pluralModelLabel = 'turlarım';

    protected static ?string $navigationLabel = 'Turlarım';

    protected static ?string $recordTitleAttribute = 'slug';

    /** Baslikta slug degil, turun adi gorunsun. */
    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): ?string
    {
        return $record?->getTranslation('name', 'tr') ?? parent::getRecordTitle($record);
    }

    public static function form(Schema $schema): Schema
    {
        return YachtForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return YachtsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PhotosRelationManager::class,
            RatesRelationManager::class,
            ExtrasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListYachts::route('/'),
            'create' => CreateYacht::route('/create'),
            'edit' => EditYacht::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
