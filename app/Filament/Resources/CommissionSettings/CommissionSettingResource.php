<?php

namespace App\Filament\Resources\CommissionSettings;

use App\Filament\Resources\CommissionSettings\Pages\CreateCommissionSetting;
use App\Filament\Resources\CommissionSettings\Pages\EditCommissionSetting;
use App\Filament\Resources\CommissionSettings\Pages\ListCommissionSettings;
use App\Filament\Resources\CommissionSettings\Schemas\CommissionSettingForm;
use App\Filament\Resources\CommissionSettings\Tables\CommissionSettingsTable;
use App\Models\CommissionSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CommissionSettingResource extends Resource
{
    protected static ?string $model = CommissionSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|\UnitEnum|null $navigationGroup = 'Finans';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'komisyon oranı';

    protected static ?string $pluralModelLabel = 'komisyon oranları';

    protected static ?string $navigationLabel = 'Komisyon Oranları';

    protected static ?string $recordTitleAttribute = 'rate';

    public static function form(Schema $schema): Schema
    {
        return CommissionSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommissionSettingsTable::configure($table);
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
            'index' => ListCommissionSettings::route('/'),
            'create' => CreateCommissionSetting::route('/create'),
            'edit' => EditCommissionSetting::route('/{record}/edit'),
        ];
    }
}
