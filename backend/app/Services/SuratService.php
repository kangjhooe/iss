<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\Surat;
use App\Models\TemplateSurat;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SuratService
{
    public function __construct(
        private PlaceholderEngine $placeholderEngine,
        private CorrespondenceService $correspondenceService,
        private SuratLayoutService $layoutService
    ) {}

    public function list(array $filters, ?int $institutionId, int $perPage = 20): LengthAwarePaginator
    {
        $query = Surat::with([
            'template:id,nama,kode,subject_type',
            'student:id,name,nis,nisn',
            'employee:id,name,nip,nuptk,type',
            'creator:id,name',
            'kop:id,nama',
            'tandaTangan:id,nama,jenis',
            'stempel:id,nama,jenis',
            'correspondence:id,letter_number,status,type',
        ])->orderByDesc('created_at');

        if ($institutionId) {
            $query->forInstitution($institutionId);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('nomor', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['template_id'])) {
            $query->where('template_id', $filters['template_id']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Surat
    {
        return Surat::with([
            'template',
            'student.class',
            'employee',
            'creator',
            'institution',
            'kop',
            'tandaTangan',
            'stempel',
            'correspondence',
        ])->find($id);
    }

    public function create(array $data): Surat
    {
        // Draft: belum punya nomor resmi
        $data['status'] = 'draft';
        $data['nomor'] = null;
        $data['correspondence_id'] = null;
        $data['published_at'] = null;

        return Surat::create($data);
    }

    public function update(Surat $surat, array $data): Surat
    {
        // Nomor & status terbit hanya lewat proses terbitkan
        unset($data['nomor'], $data['correspondence_id'], $data['published_at']);

        if ($surat->status === 'terbit') {
            // Setelah terbit, jenis surat tidak diubah (sudah terikat register)
            unset($data['letter_type_code'], $data['status']);
        } else {
            // Jangan izinkan set status terbit lewat update biasa
            if (isset($data['status']) && $data['status'] === 'terbit') {
                unset($data['status']);
            }
        }

        $surat->update($data);

        return $surat->fresh([
            'template', 'student', 'employee', 'creator', 'kop', 'tandaTangan', 'stempel', 'correspondence',
        ]);
    }

    public function delete(Surat $surat): bool
    {
        if ($surat->status === 'terbit') {
            throw new \RuntimeException('Surat yang sudah diterbitkan tidak dapat dihapus. Batalkan dari Arsip bila diperlukan.');
        }

        if ($surat->pdf && Storage::disk('public')->exists($surat->pdf)) {
            Storage::disk('public')->delete($surat->pdf);
        }

        return $surat->delete();
    }

    /**
     * Generate draft dari template + siswa/pegawai.
     * Nomor resmi BELUM dibuat — muncul saat terbitkan.
     */
    public function generate(array $payload, int $userId, int $institutionId): Surat
    {
        return DB::transaction(function () use ($payload, $userId, $institutionId) {
            $template = TemplateSurat::aktif()
                ->forInstitution($institutionId)
                ->findOrFail($payload['template_id']);

            $subjectType = $payload['subject_type']
                ?? $template->subject_type
                ?? 'siswa';

            $student = null;
            $employee = null;

            if ($subjectType === 'siswa' && !empty($payload['student_id'])) {
                $student = Student::with('class')
                    ->where('institution_id', $institutionId)
                    ->findOrFail($payload['student_id']);
            }

            if ($subjectType === 'pegawai' && !empty($payload['employee_id'])) {
                $employee = Employee::query()
                    ->where('institution_id', $institutionId)
                    ->findOrFail($payload['employee_id']);
            }

            // Fallback: terima ID eksplisit tanpa subject_type ketat
            if (!$student && !$employee) {
                if (!empty($payload['student_id'])) {
                    $student = Student::with('class')
                        ->where('institution_id', $institutionId)
                        ->findOrFail($payload['student_id']);
                }
                if (!empty($payload['employee_id'])) {
                    $employee = Employee::query()
                        ->where('institution_id', $institutionId)
                        ->findOrFail($payload['employee_id']);
                }
            }

            if ($subjectType === 'siswa' && !$student) {
                throw new \InvalidArgumentException('Pilih siswa untuk template ini.');
            }
            if ($subjectType === 'pegawai' && !$employee) {
                throw new \InvalidArgumentException('Pilih guru/pegawai untuk template ini.');
            }

            $institution = Institution::findOrFail($institutionId);
            $date = !empty($payload['tanggal'])
                ? Carbon::parse($payload['tanggal'])
                : Carbon::now();

            $letterTypeCode = $payload['letter_type_code']
                ?? $template->letter_type_code
                ?? '09';

            // Ganti placeholder subjek/institusi, biarkan {{nomor_surat}} utuh
            $isiHtml = $this->placeholderEngine->replaceFromModels(
                $template->isi_html,
                $student,
                $institution,
                null,
                $date,
                $payload['extra'] ?? [],
                true,
                $employee
            );

            return Surat::create([
                'institution_id' => $institutionId,
                'template_id' => $template->id,
                'student_id' => $student?->id,
                'employee_id' => $employee?->id,
                'nomor' => null,
                'judul' => $payload['judul'] ?? $template->nama,
                'tanggal' => $date->toDateString(),
                'isi_html' => $isiHtml,
                'letter_type_code' => $letterTypeCode,
                'status' => 'draft',
                'created_by' => $userId,
                'kop_id' => $payload['kop_id'] ?? null,
                'tampilkan_kop' => array_key_exists('tampilkan_kop', $payload)
                    ? (bool) $payload['tampilkan_kop']
                    : true,
                'tanda_tangan_id' => $payload['tanda_tangan_id'] ?? null,
                'tampilkan_tanda_tangan' => (bool) ($payload['tampilkan_tanda_tangan'] ?? false),
                'stempel_id' => $payload['stempel_id'] ?? null,
                'tampilkan_stempel' => (bool) ($payload['tampilkan_stempel'] ?? false),
                'posisi_ttd' => $payload['posisi_ttd'] ?? 'kanan',
            ]);
        });
    }

    /**
     * Terbitkan surat: buat register di correspondence (penomoran resmi),
     * lalu tautkan ke surat editor.
     */
    public function publish(Surat $surat, int $userId, array $payload = []): Surat
    {
        if ($surat->status === 'terbit' || $surat->correspondence_id) {
            throw new \RuntimeException('Surat ini sudah diterbitkan.');
        }

        return DB::transaction(function () use ($surat, $userId, $payload) {
            $surat->loadMissing(['student', 'employee', 'institution']);

            $letterTypeCode = $payload['letter_type_code']
                ?? $surat->letter_type_code
                ?? '09';

            $tanggal = !empty($payload['tanggal'])
                ? Carbon::parse($payload['tanggal'])->toDateString()
                : ($surat->tanggal?->format('Y-m-d') ?? Carbon::now()->toDateString());

            $to = $payload['to']
                ?? $surat->student?->name
                ?? $surat->employee?->name
                ?? 'Yang berkepentingan';

            $correspondence = $this->correspondenceService->create([
                'institution_id' => $surat->institution_id,
                'type' => 'keluar',
                'letter_type_code' => $letterTypeCode,
                'letter_number' => null, // auto via LetterHelper
                'subject' => $surat->judul,
                'to' => $to,
                'date' => $tanggal,
                'priority' => $payload['priority'] ?? 'biasa',
                'status' => 'draft',
                'description' => 'Diterbitkan dari Editor Persuratan (surat #' . $surat->id . ')',
            ], $userId);

            $nomor = $correspondence->letter_number;
            if (empty($nomor)) {
                throw new \RuntimeException('Gagal mendapatkan nomor surat resmi. Pastikan NPSN institusi sudah diisi.');
            }

            $isiHtml = $this->placeholderEngine->applyNomor($surat->isi_html, $nomor);

            $surat->update([
                'correspondence_id' => $correspondence->id,
                'nomor' => $nomor,
                'isi_html' => $isiHtml,
                'letter_type_code' => $letterTypeCode,
                'tanggal' => $tanggal,
                'status' => 'terbit',
                'published_at' => now(),
            ]);

            return $surat->fresh([
                'template', 'student', 'employee', 'creator', 'kop', 'tandaTangan', 'stempel', 'correspondence',
            ]);
        });
    }

    public function exportPdf(Surat $surat): string
    {
        $surat->loadMissing('institution');
        $filename = 'surat/' . $surat->id . '_' . time() . '.pdf';
        Storage::disk('public')->put($filename, $this->loadSuratPdf($surat)->output());

        $surat->update(['pdf' => $filename]);

        return $filename;
    }

    /**
     * Satu sumber render PDF A4 untuk export dan cetak.
     */
    public function loadSuratPdf(Surat $surat): \Barryvdh\DomPDF\PDF
    {
        $surat->refresh();
        $surat->loadMissing('institution');
        $parts = $this->layoutService->buildPrintParts($surat, true);

        return DomPDF::loadView('surat.print', [
            'surat' => $surat,
            'kopHtml' => $parts['kopHtml'],
            'isiHtml' => $parts['isiHtml'],
            'ttdHtml' => $parts['ttdHtml'],
        ])
            ->setPaper('a4', 'portrait');
    }

    public function streamPdf(Surat $surat, ?string $filename = null)
    {
        $safeName = $filename ?: 'Surat_' . preg_replace('/[^\w\-]+/', '_', $surat->nomor ?? (string) $surat->id) . '.pdf';

        return $this->loadSuratPdf($surat)->stream($safeName);
    }

    public function printViewData(Surat $surat): array
    {
        $parts = $this->layoutService->buildPrintParts($surat, true);
        return [
            'surat' => $surat,
            'kopHtml' => $parts['kopHtml'],
            'isiHtml' => $parts['isiHtml'],
            'ttdHtml' => $parts['ttdHtml'],
        ];
    }

    public function previewParts(Surat $surat): array
    {
        return $this->layoutService->buildPrintParts($surat, false);
    }

    public function downloadPdf(Surat $surat)
    {
        $this->exportPdf($surat);
        $surat->refresh();

        $safeName = 'Surat_' . preg_replace('/[^\w\-]+/', '_', $surat->nomor ?? (string) $surat->id) . '.pdf';

        return Storage::disk('public')->download($surat->pdf, $safeName);
    }
}
