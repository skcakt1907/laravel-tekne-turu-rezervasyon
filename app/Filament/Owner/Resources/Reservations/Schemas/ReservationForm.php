<?php

namespace App\Filament\Owner\Resources\Reservations\Schemas;

use Filament\Schemas\Schema;

/** Yat sahibi rezervasyon kaydini duzenlemez; islemler aksiyonlarla yapilir. */
class ReservationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([]);
    }
}
