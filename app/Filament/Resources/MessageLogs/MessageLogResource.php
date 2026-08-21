<?php

namespace App\Filament\Resources\MessageLogs;

use App\Filament\Resources\MessageLogs\Pages\ListMessageLogs;
use App\Filament\Resources\MessageLogs\Schemas\MessageLogForm;
use App\Filament\Resources\MessageLogs\Tables\MessageLogsTable;
use App\Models\MessageLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MessageLogResource extends Resource
{
    protected static ?string $model = MessageLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static string|\UnitEnum|null $navigationGroup = 'Rezervasyonlar';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'mesaj kaydı';

    protected static ?string $pluralModelLabel = 'mesaj kayıtları';

    protected static ?string $navigationLabel = 'Mesaj Kaydı';

    protected static ?string $recordTitleAttribute = 'recipient';

    public static function form(Schema $schema): Schema
    {
        return MessageLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MessageLogsTable::configure($table);
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
            'index' => ListMessageLogs::route('/'),
        ];
    }
}
