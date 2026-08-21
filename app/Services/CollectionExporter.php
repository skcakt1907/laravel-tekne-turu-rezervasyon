<?php

namespace App\Services;

use App\Models\Collection as CollectionModel;
use App\Models\Reservation;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tahsilat dökümünün Excel çıktısı. openspout Filament ile zaten kurulu,
 * ek bağımlılık yok. Dosya bellekte tutulmaz, doğrudan indirilir.
 */
class CollectionExporter
{
    /** Dönem listesi (yat sahibi başına bir satır). */
    public function periods(iterable $collections, string $filename = 'tahsilat.xlsx'): StreamedResponse
    {
        $header = ['Dönem', 'Yat sahibi', 'Firma', 'Rezervasyon', 'Ciro', 'Komisyon', 'Net', 'Para birimi', 'Durum', 'Tahsil tarihi', 'Fatura no'];

        $rows = [];

        foreach ($collections as $collection) {
            $rows[] = [
                $collection->periodLabel(),
                $collection->owner?->name ?? '—',
                $collection->owner?->company_name ?? '',
                $collection->reservation_count,
                (float) $collection->revenue,
                (float) $collection->commission,
                (float) $collection->revenue - (float) $collection->commission,
                $collection->currency,
                $collection->status === 'collected' ? 'Tahsil edildi' : 'Bekliyor',
                $collection->collected_at?->format('d.m.Y') ?? '',
                $collection->invoice_no ?? '',
            ];
        }

        return $this->stream($filename, $header, $rows);
    }

    /** Tek dönemin rezervasyon kırılımı. */
    public function detail(CollectionModel $collection): StreamedResponse
    {
        $header = ['Kod', 'Yat', 'Müşteri', 'Başlangıç', 'Bitiş', 'Kişi', 'Tutar', 'Komisyon oranı (%)', 'Komisyon', 'Para birimi'];

        $rows = Reservation::where('collection_id', $collection->id)
            ->with('yacht')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Reservation $r) => [
                $r->code,
                $r->yacht?->getTranslation('name', 'tr') ?? '—',
                $r->customer_name,
                $r->starts_at->format('d.m.Y H:i'),
                $r->ends_at->format('d.m.Y H:i'),
                $r->guests,
                (float) $r->estimated_total,
                (float) $r->commission_rate,
                (float) $r->commission_amount,
                $r->currency,
            ])
            ->all();

        $name = 'tahsilat-'.$collection->year.'-'.str_pad((string) $collection->month, 2, '0', STR_PAD_LEFT).'.xlsx';

        return $this->stream($name, $header, $rows);
    }

    private function stream(string $filename, array $header, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $writer = new Writer;
            $writer->openToFile('php://output');

            $bold = (new Style)->setFontBold();
            $writer->addRow(Row::fromValues($header, $bold));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues($row));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
