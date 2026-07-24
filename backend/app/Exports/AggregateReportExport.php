<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class AggregateReportExport implements WithMultipleSheets
{
    public function __construct(protected array $report) {}

    public function sheets(): array
    {
        $summary = $this->report['summary'] ?? [];

        return [
            new class($summary) implements FromCollection, WithHeadings, WithTitle {
                public function __construct(private array $summary) {}

                public function title(): string
                {
                    return 'Ringkasan';
                }

                public function headings(): array
                {
                    return ['Metrik', 'Jumlah'];
                }

                public function collection(): Collection
                {
                    return collect([
                        ['Total Institusi', (int) ($this->summary['institutions'] ?? 0)],
                        ['Institusi Aktif', (int) ($this->summary['active_institutions'] ?? 0)],
                        ['Siswa Aktif', (int) ($this->summary['students'] ?? 0)],
                        ['Guru Aktif', (int) ($this->summary['teachers'] ?? 0)],
                    ]);
                }
            },
            $this->groupSheet('Per Jenjang', 'Jenjang', $this->report['by_level'] ?? [], 'level'),
            $this->groupSheet('Per Jenis', 'Jenis', $this->report['by_type'] ?? [], 'type'),
            $this->groupSheet('Per Provinsi', 'Provinsi', $this->report['by_province'] ?? [], 'province'),
            new class(collect($this->report['institutions'] ?? [])) implements FromCollection, WithHeadings, WithTitle {
                public function __construct(private Collection $institutions) {}

                public function title(): string
                {
                    return 'Institusi';
                }

                public function headings(): array
                {
                    return ['Nama', 'NPSN', 'Jenjang', 'Jenis', 'Provinsi', 'No HP/WA', 'Email', 'Status', 'Siswa', 'Guru'];
                }

                public function collection(): Collection
                {
                    return $this->institutions->map(fn ($row) => [
                        $row['name'] ?? '',
                        $row['npsn'] ?? '',
                        $row['level'] ?? '',
                        $row['type'] ?? '',
                        $row['province'] ?? '',
                        $row['phone'] ?? '',
                        $row['email'] ?? '',
                        !empty($row['is_active']) ? 'Aktif' : 'Dibekukan',
                        (int) ($row['students'] ?? 0),
                        (int) ($row['teachers'] ?? 0),
                    ]);
                }
            },
        ];
    }

    private function groupSheet(string $title, string $labelHeader, array $rows, string $labelKey): FromCollection
    {
        return new class($title, $labelHeader, $rows, $labelKey) implements FromCollection, WithHeadings, WithTitle {
            public function __construct(
                private string $sheetTitle,
                private string $labelHeader,
                private array $rows,
                private string $labelKey
            ) {}

            public function title(): string
            {
                return $this->sheetTitle;
            }

            public function headings(): array
            {
                return [$this->labelHeader, 'Institusi', 'Siswa', 'Guru'];
            }

            public function collection(): Collection
            {
                return collect($this->rows)->map(fn (array $row) => [
                    $row[$this->labelKey] ?? '',
                    (int) ($row['institutions'] ?? 0),
                    (int) ($row['students'] ?? 0),
                    (int) ($row['teachers'] ?? 0),
                ]);
            }
        };
    }
}
