<?php

namespace App\Exports;

use App\Models\ExamParticipant;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ExamSessionResultsExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        protected Collection $participants,
        protected string $sessionName = 'Hasil Ujian'
    ) {}

    public function title(): string
    {
        $title = preg_replace('/[\\\\\/\?\*\[\]:]/', '-', $this->sessionName) ?: 'Hasil Ujian';

        return mb_substr($title, 0, 31);
    }

    public function headings(): array
    {
        return [
            'No.',
            'Nomor peserta',
            'Nama',
            'NIS',
            'NISN',
            'Status',
            'Mulai',
            'Selesai',
            'Nilai',
            'Nilai maks',
            'Persen',
            'Nilai dirilis',
        ];
    }

    public function collection(): Collection
    {
        $statusMap = [
            ExamParticipant::STATUS_REGISTERED => 'Terdaftar',
            ExamParticipant::STATUS_STARTED => 'Mengerjakan',
            ExamParticipant::STATUS_SUBMITTED => 'Selesai',
        ];

        return $this->participants->values()->map(function (ExamParticipant $p, int $idx) use ($statusMap) {
            $score = $p->score !== null ? (float) $p->score : null;
            $scoreMax = $p->score_max !== null ? (float) $p->score_max : null;
            $percent = ($score !== null && $scoreMax && $scoreMax > 0)
                ? round(($score / $scoreMax) * 100, 2)
                : null;

            return [
                $p->participant_order ?? ($idx + 1),
                $p->nomor_peserta_export ?? '',
                $p->student?->name ?? '',
                $p->student?->nis ?? '',
                $p->student?->nisn ?? '',
                $statusMap[$p->status] ?? $p->status,
                $p->started_at?->format('Y-m-d H:i') ?? '',
                $p->submitted_at?->format('Y-m-d H:i') ?? '',
                $score,
                $scoreMax,
                $percent,
                $p->score_released ? 'Ya' : 'Tidak',
            ];
        });
    }
}
