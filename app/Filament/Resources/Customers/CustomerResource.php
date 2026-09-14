<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\RelationManagers\CustomerNotesRelationManager;
use App\Filament\Resources\Customers\RelationManagers\ReservationsRelationManager;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Filament\Resources\Customers\Tables\CustomersTable;
use App\Models\Customer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * MÜŞTERİLER — sitenin CRM'i.
 *
 * Üyelik yok; buradaki kayıtlar rezervasyon formundan oluşuyor. Aynı
 * e-postayla gelen her rezervasyon aynı müşteriye bağlandığı için kişinin
 * tüm geçmişi tek ekranda toplanıyor.
 */
class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Kullanıcılar';

    protected static ?int $navigationSort = 0;

    protected static ?string $modelLabel = 'müşteri';

    protected static ?string $pluralModelLabel = 'müşteriler';

    protected static ?string $navigationLabel = 'Müşteriler';

    protected static ?string $recordTitleAttribute = 'name';

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone', 'whatsapp'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    /** @return array<string, string> */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return array_filter([
            'E-posta' => $record->email,
            'Telefon' => $record->phone,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CustomerNotesRelationManager::class,
            ReservationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
