<?php

namespace App\Exports;

use App\Services\InventoryItemImportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class InventoryItemsExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(protected array $rows) {}

    public function title(): string
    {
        return 'Master Barang';
    }

    public function headings(): array
    {
        return [
            'kode_kategori',
            'nama',
            'tipe_pelacakan',
            'kode_barang',
            'merk',
            'model',
            'jumlah',
            'satuan',
            'kondisi',
            'status',
            'tanggal_beli',
            'harga_beli',
            'supplier',
            'sumber_dana',
            'cara_perolehan',
            'ruangan',
            'catatan_lokasi',
            'deskripsi',
        ];
    }

    public function collection(): Collection
    {
        return collect($this->rows);
    }
}
