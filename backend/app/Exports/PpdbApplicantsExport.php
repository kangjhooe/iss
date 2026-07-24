<?php

namespace App\Exports;

use App\Models\PpdbApplicant;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PpdbApplicantsExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $applicants,
        protected array $colKeys,
        protected array $headers
    ) {}

    public function headings(): array
    {
        return $this->headers;
    }

    public function collection(): Collection
    {
        return $this->applicants->map(fn (PpdbApplicant $a) => $this->rowFor($a));
    }

    private function rowFor(PpdbApplicant $a): array
    {
        $map = [
            'registration_number' => $a->registration_number ?? '',
            'name' => $a->name ?? '',
            'nik' => $a->nik ?? '',
            'nisn' => $a->nisn ?? '',
            'gender' => $a->gender === 'L' ? 'Laki-laki' : ($a->gender === 'P' ? 'Perempuan' : ''),
            'birth_place' => $a->birth_place ?? '',
            'birth_date' => $a->birth_date?->format('Y-m-d') ?? '',
            'address' => $a->address ?? '',
            'phone' => $a->phone ?? '',
            'email' => $a->email ?? '',
            'previous_school' => $a->previous_school ?? '',
            'previous_school_npsn' => $a->previous_school_npsn ?? '',
            'previous_school_address' => $a->previous_school_address ?? '',
            'channel' => $a->channel?->name ?? '',
            'period' => $a->period?->name ?? '',
            'status' => $a->status ?? '',
            'rank' => $a->rank ?? '',
            'father_name' => $a->father_name ?? '',
            'mother_name' => $a->mother_name ?? '',
            'guardian_name' => $a->guardian_name ?? '',
            'created_at' => $a->created_at?->format('Y-m-d H:i') ?? '',
            'documents_verified' => $a->documents_verified ? 'Ya' : 'Tidak',
            'payment_status' => $a->payment_status ?? 'unpaid',
            'payment_amount' => $a->payment_amount ?? '',
            'notes' => $a->notes ?? '',
        ];
        $row = [];
        foreach ($this->colKeys as $key) {
            $row[] = $map[$key] ?? '';
        }
        return $row;
    }
}
