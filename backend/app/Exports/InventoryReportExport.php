<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class InventoryReportExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        protected string $reportType,
        protected string $reportTitle,
        protected array $headings,
        protected array $rows
    ) {}

    public function title(): string
    {
        return mb_substr($this->reportTitle, 0, 31);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function collection(): Collection
    {
        return collect($this->rows);
    }

    /**
     * Build export payload from report service arrays.
     *
     * @return array{headings: list<string>, rows: list<list<mixed>>}
     */
    public static function buildRows(string $reportType, array $payload): array
    {
        return match ($reportType) {
            'stock' => self::stockRows($payload['stock'] ?? []),
            'location' => self::locationRows($payload['by_location'] ?? []),
            'category' => self::categoryRows($payload['by_category'] ?? []),
            'asset' => self::assetValueRows($payload['asset_value'] ?? []),
            'damaged' => self::damagedRows($payload['damaged_missing'] ?? []),
            'loaned' => self::loanedRows($payload['loaned'] ?? []),
            'transactions' => self::transactionRows($payload['transactions'] ?? []),
            'asset_movements' => self::assetMovementRows($payload['asset_movements'] ?? []),
            'maintenance' => self::maintenanceRows($payload['maintenance'] ?? []),
            'disposal' => self::disposalRows($payload['disposed'] ?? []),
            default => self::summaryRows($payload),
        };
    }

    private static function summaryRows(array $payload): array
    {
        $stats = $payload['statistics'] ?? [];
        $rows = [
            ['Total master barang', $stats['total_items'] ?? 0],
            ['Total unit', $stats['total_quantity'] ?? 0],
            ['Estimasi nilai', $stats['total_value'] ?? 0],
            ['Aset individual', $stats['individual_asset_count'] ?? 0],
            ['Garansi < 3 bln', $stats['warranty_expiring_soon'] ?? 0],
        ];

        foreach (($stats['by_status'] ?? []) as $label => $row) {
            $rows[] = ["Status: {$label}", ($row['count'] ?? 0) . ' item / ' . ($row['quantity'] ?? 0) . ' unit'];
        }

        return [
            'headings' => ['Metrik', 'Nilai'],
            'rows' => $rows,
        ];
    }

    private static function stockRows(array $stock): array
    {
        $headings = ['Kode', 'Nama', 'Tipe', 'Kategori', 'Jumlah', 'Satuan', 'Kondisi', 'Status', 'Lokasi', 'Harga Beli', 'Total Nilai'];
        $rows = [];
        foreach ($stock['items'] ?? [] as $item) {
            $rows[] = [
                $item['code'] ?? '',
                $item['name'] ?? '',
                $item['tracking_type'] ?? 'stock',
                $item['category'] ?? '',
                $item['quantity'] ?? 0,
                $item['unit'] ?? '',
                $item['condition'] ?? '',
                $item['status'] ?? '',
                $item['location'] ?? '',
                $item['purchase_price'] ?? '',
                $item['total_value'] ?? '',
            ];
        }

        return compact('headings', 'rows');
    }

    private static function locationRows(array $data): array
    {
        $headings = ['Ruangan', 'Kode', 'Nama', 'Kategori', 'Jumlah', 'Tipe'];
        $rows = [];
        foreach ($data['by_room'] ?? [] as $group) {
            foreach ($group['items'] ?? [] as $item) {
                $rows[] = [
                    $group['location_name'] ?? '',
                    $item['code'] ?? '',
                    $item['name'] ?? '',
                    $item['category'] ?? '',
                    $item['quantity'] ?? 0,
                    $item['entity_type'] ?? 'item',
                ];
            }
        }

        return compact('headings', 'rows');
    }

    private static function categoryRows(array $groups): array
    {
        $headings = ['Kategori', 'Kode', 'Nama', 'Jumlah', 'Satuan', 'Kondisi', 'Status', 'Lokasi'];
        $rows = [];
        foreach ($groups as $group) {
            foreach ($group['items'] ?? [] as $item) {
                $rows[] = [
                    $group['category'] ?? '',
                    $item['code'] ?? '',
                    $item['name'] ?? '',
                    $item['quantity'] ?? 0,
                    $item['unit'] ?? '',
                    $item['condition'] ?? '',
                    $item['status'] ?? '',
                    $item['location'] ?? '',
                ];
            }
        }

        return compact('headings', 'rows');
    }

    private static function assetValueRows(array $data): array
    {
        $headings = ['Kategori', 'Kode', 'Nama', 'Jumlah', 'Harga Satuan', 'Total Nilai', 'Tgl Beli', 'Tipe'];
        $rows = [];
        foreach ($data['by_category'] ?? [] as $group) {
            foreach ($group['items'] ?? [] as $item) {
                $rows[] = [
                    $group['category'] ?? '',
                    $item['code'] ?? '',
                    $item['name'] ?? '',
                    $item['quantity'] ?? 0,
                    $item['unit_price'] ?? '',
                    $item['total_value'] ?? '',
                    $item['purchase_date'] ?? '',
                    $item['tracking_type'] ?? 'stock',
                ];
            }
        }

        return compact('headings', 'rows');
    }

    private static function damagedRows(array $data): array
    {
        $headings = ['Kode', 'Nama', 'Kategori', 'Label', 'Kondisi', 'Status', 'Jumlah', 'Lokasi', 'Tipe Entitas'];
        $rows = [];
        foreach ($data['rows'] ?? [] as $item) {
            $rows[] = [
                $item['code'] ?? '',
                $item['name'] ?? '',
                $item['category'] ?? '',
                $item['label'] ?? '',
                $item['condition'] ?? '',
                $item['status'] ?? '',
                $item['quantity'] ?? 0,
                $item['location'] ?? '',
                $item['entity_type'] ?? 'item',
            ];
        }

        return compact('headings', 'rows');
    }

    private static function loanedRows(array $data): array
    {
        $headings = ['Kode Barang', 'Nama', 'No. Aset', 'Peminjam', 'Qty', 'Tgl Pinjam', 'Jatuh Tempo', 'Status'];
        $rows = [];
        foreach ($data['loans'] ?? [] as $loan) {
            $rows[] = [
                $loan['item_code'] ?? '',
                $loan['item_name'] ?? '',
                $loan['asset_number'] ?? '',
                $loan['borrower_name'] ?? '',
                $loan['quantity'] ?? 0,
                $loan['loan_date'] ?? '',
                $loan['expected_return_date'] ?? '',
                $loan['status'] ?? '',
            ];
        }

        return compact('headings', 'rows');
    }

    private static function transactionRows(array $data): array
    {
        $headings = ['Tanggal', 'Kode', 'Nama', 'Jenis', 'Qty', 'No Ref', 'Dari', 'Ke', 'Oleh'];
        $rows = [];
        foreach ($data['transactions'] ?? [] as $row) {
            $rows[] = [
                $row['date'] ?? '',
                $row['item_code'] ?? '',
                $row['item_name'] ?? '',
                $row['type'] ?? '',
                $row['quantity'] ?? 0,
                $row['reference_number'] ?? '',
                $row['from_location'] ?? '',
                $row['to_location'] ?? '',
                $row['created_by'] ?? '',
            ];
        }

        return compact('headings', 'rows');
    }

    private static function assetMovementRows(array $data): array
    {
        $headings = ['Tanggal', 'No Aset', 'Kode Barang', 'Nama', 'Dari', 'Ke', 'No Ref', 'Oleh'];
        $rows = [];
        foreach ($data['movements'] ?? [] as $row) {
            $rows[] = [
                $row['movement_date'] ?? '',
                $row['asset_number'] ?? '',
                $row['item_code'] ?? '',
                $row['item_name'] ?? '',
                $row['from_location'] ?? '',
                $row['to_location'] ?? '',
                $row['reference_number'] ?? '',
                $row['created_by'] ?? '',
            ];
        }

        return compact('headings', 'rows');
    }

    private static function maintenanceRows(array $data): array
    {
        $headings = ['Tgl Jadwal', 'Kode', 'Barang', 'Jenis', 'Biaya', 'Status', 'Teknisi', 'Selesai'];
        $rows = [];
        foreach ($data['maintenances'] ?? [] as $row) {
            $rows[] = [
                $row['scheduled_date'] ?? '',
                $row['item_code'] ?? '',
                $row['item_name'] ?? '',
                $row['type'] ?? '',
                $row['cost'] ?? '',
                $row['status'] ?? '',
                $row['technician_name'] ?? '',
                $row['completed_date'] ?? '',
            ];
        }

        return compact('headings', 'rows');
    }

    private static function disposalRows(array $data): array
    {
        $headings = ['Tgl Hapus', 'Kode', 'Nama', 'Kategori', 'Qty', 'Alasan', 'No SK/BA', 'Lokasi'];
        $rows = [];
        foreach ($data['items'] ?? [] as $row) {
            $rows[] = [
                $row['disposed_at'] ?? '',
                $row['code'] ?? '',
                $row['name'] ?? '',
                $row['category'] ?? '',
                $row['quantity'] ?? 0,
                $row['disposal_reason'] ?? '',
                $row['disposal_document_number'] ?? '',
                $row['location'] ?? '',
            ];
        }

        return compact('headings', 'rows');
    }
}
