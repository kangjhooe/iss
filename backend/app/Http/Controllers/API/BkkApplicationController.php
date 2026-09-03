<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Models\AlumniDestination;
use App\Models\BkkApplication;
use App\Models\BkkVacancy;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BkkApplicationController extends Controller
{
    use ResolvesInstitution;

    public function index(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $query = $this->filteredQuery($request, $institutionId)
                ->with([
                    'student:id,name,nis,nisn,graduation_year,status',
                    'vacancy:id,title,position,company_name,industry_partner_id,status',
                    'vacancy.industryPartner:id,name',
                ])
                ->orderByDesc('id');

            $perPage = min((int) $request->get('per_page', 20), 100);

            return response()->json($query->paginate($perPage));
        } catch (\Exception $e) {
            Log::error('BkkApplication index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil lamaran BKK.'], 500);
        }
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $rows = $this->filteredQuery($request, $institutionId)
            ->with([
                'student:id,name,nis,nisn,graduation_year,status',
                'vacancy:id,title,position,company_name,industry_partner_id',
                'vacancy.industryPartner:id,name',
            ])
            ->orderBy('bkk_vacancy_id')
            ->orderBy('id')
            ->get();

        $filename = 'bkk-lamaran-' . date('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, [
                'No', 'Alumni', 'NIS', 'NISN', 'Tahun Lulus', 'Lowongan', 'Posisi',
                'Perusahaan', 'Tanggal Lamar', 'Status', 'Catatan',
            ]);
            $no = 1;
            foreach ($rows as $a) {
                fputcsv($out, [
                    $no++,
                    $a->student?->name ?? '-',
                    $a->student?->nis ?? '-',
                    $a->student?->nisn ?? '-',
                    $a->student?->graduation_year ?? '-',
                    $a->vacancy?->title ?? '-',
                    $a->vacancy?->position ?? '-',
                    $a->vacancy?->industryPartner?->name ?: ($a->vacancy?->company_name ?? '-'),
                    $a->applied_at?->format('Y-m-d') ?? '-',
                    $a->status ?? '-',
                    $a->notes ?? '',
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $data = $request->validate([
            'bkk_vacancy_id' => 'required|integer|exists:bkk_vacancies,id',
            'student_id' => 'required|integer|exists:student,id',
            'status' => ['nullable', Rule::in(BkkApplication::STATUSES)],
            'applied_at' => 'nullable|date',
            'notes' => 'nullable|string|max:5000',
        ]);

        $vacancy = BkkVacancy::findOrFail($data['bkk_vacancy_id']);
        if ((int) $vacancy->institution_id !== (int) $institutionId) {
            return response()->json(['message' => 'Lowongan tidak valid.'], 422);
        }

        $student = Student::findOrFail($data['student_id']);
        if ((int) $student->institution_id !== (int) $institutionId) {
            return response()->json(['message' => 'Alumni tidak berada di institusi Anda.'], 422);
        }
        if ($student->status !== 'Lulus') {
            return response()->json(['message' => 'Hanya alumni (siswa lulus) yang dapat didaftarkan ke BKK oleh staff.'], 422);
        }

        $exists = BkkApplication::where('bkk_vacancy_id', $data['bkk_vacancy_id'])
            ->where('student_id', $data['student_id'])
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Alumni sudah terdaftar pada lowongan ini.'], 422);
        }

        $status = $data['status'] ?? 'diajukan';
        $application = null;

        DB::transaction(function () use ($data, $institutionId, $status, $vacancy, $student, &$application) {
            $application = BkkApplication::create([
                'institution_id' => $institutionId,
                'bkk_vacancy_id' => $data['bkk_vacancy_id'],
                'student_id' => $data['student_id'],
                'status' => $status,
                'applied_at' => $data['applied_at'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
            ]);

            if ($status === 'diterima') {
                $this->syncAlumniDestination($application, $vacancy, $student);
            }
        });

        $application->load([
            'student:id,name,nis,nisn,graduation_year,status',
            'vacancy:id,title,position,company_name,industry_partner_id',
            'vacancy.industryPartner:id,name',
        ]);

        return response()->json([
            'message' => 'Lamaran BKK berhasil dicatat.',
            'data' => $application,
        ], 201);
    }

    public function update(Request $request, BkkApplication $bkk_application): JsonResponse
    {
        if ($denied = $this->denyOutside($request, $bkk_application->institution_id)) {
            return $denied;
        }

        $data = $request->validate([
            'status' => ['sometimes', Rule::in(BkkApplication::STATUSES)],
            'applied_at' => 'nullable|date',
            'notes' => 'nullable|string|max:5000',
        ]);

        DB::transaction(function () use ($data, $bkk_application) {
            $previous = $bkk_application->status;
            $bkk_application->update($data);

            if (($data['status'] ?? null) === 'diterima' && $previous !== 'diterima') {
                $bkk_application->load(['vacancy.industryPartner', 'student']);
                $this->syncAlumniDestination(
                    $bkk_application,
                    $bkk_application->vacancy,
                    $bkk_application->student
                );
            }
        });

        $bkk_application->load([
            'student:id,name,nis,nisn,graduation_year,status',
            'vacancy:id,title,position,company_name,industry_partner_id',
            'vacancy.industryPartner:id,name',
            'alumniDestination',
        ]);

        return response()->json([
            'message' => 'Lamaran BKK berhasil diperbarui.',
            'data' => $bkk_application,
        ]);
    }

    public function destroy(Request $request, BkkApplication $bkk_application): JsonResponse
    {
        if ($denied = $this->denyOutside($request, $bkk_application->institution_id)) {
            return $denied;
        }

        $bkk_application->delete();

        return response()->json(['message' => 'Lamaran BKK dihapus.']);
    }

    /**
     * Portal siswa/alumni: lamaran sendiri.
     */
    public function myIndex(Request $request): JsonResponse
    {
        $ctx = $this->resolveStudentPortal($request);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }
        [, $student, $institutionId] = $ctx;

        $rows = BkkApplication::forInstitution($institutionId)
            ->where('student_id', $student->id)
            ->with([
                'vacancy:id,title,position,company_name,industry_partner_id,status,deadline',
                'vacancy.industryPartner:id,name',
            ])
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $rows]);
    }

    /**
     * Portal siswa/alumni: lamar lowongan terbuka.
     */
    public function myStore(Request $request): JsonResponse
    {
        $ctx = $this->resolveStudentPortal($request);
        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }
        [, $student, $institutionId] = $ctx;

        if (!in_array($student->status, ['Aktif', 'Lulus'], true)) {
            return response()->json(['message' => 'Status siswa tidak memungkinkan untuk melamar BKK.'], 422);
        }

        $data = $request->validate([
            'bkk_vacancy_id' => 'required|integer|exists:bkk_vacancies,id',
            'notes' => 'nullable|string|max:5000',
        ]);

        $vacancy = BkkVacancy::findOrFail($data['bkk_vacancy_id']);
        if ((int) $vacancy->institution_id !== (int) $institutionId) {
            return response()->json(['message' => 'Lowongan tidak valid.'], 422);
        }
        if ($vacancy->status !== 'buka') {
            return response()->json(['message' => 'Lowongan sudah ditutup.'], 422);
        }
        if ($vacancy->deadline && $vacancy->deadline->lt(now()->startOfDay())) {
            return response()->json(['message' => 'Deadline lowongan sudah lewat.'], 422);
        }

        $exists = BkkApplication::where('bkk_vacancy_id', $vacancy->id)
            ->where('student_id', $student->id)
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Anda sudah melamar lowongan ini.'], 422);
        }

        $application = BkkApplication::create([
            'institution_id' => $institutionId,
            'bkk_vacancy_id' => $vacancy->id,
            'student_id' => $student->id,
            'status' => 'diajukan',
            'applied_at' => now()->toDateString(),
            'notes' => $data['notes'] ?? null,
        ]);

        $application->load([
            'vacancy:id,title,position,company_name,industry_partner_id,status,deadline',
            'vacancy.industryPartner:id,name',
        ]);

        return response()->json([
            'message' => 'Lamaran berhasil dikirim.',
            'data' => $application,
        ], 201);
    }

    /**
     * @return array{0: \App\Models\User, 1: Student, 2: int}|JsonResponse
     */
    private function resolveStudentPortal(Request $request): array|JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isStudent()) {
            return response()->json(['message' => 'Hanya siswa/alumni yang dapat mengakses data ini.'], 403);
        }

        $student = $user->studentProfile;
        if (!$student) {
            return response()->json(['message' => 'Profil siswa tidak ditemukan untuk akun ini.'], 404);
        }

        $institutionId = (int) ($student->institution_id ?: $user->institution_id);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        return [$user, $student, $institutionId];
    }

    private function syncAlumniDestination(BkkApplication $application, BkkVacancy $vacancy, Student $student): void
    {
        $company = $vacancy->industryPartner?->name ?: ($vacancy->company_name ?: $vacancy->title);
        $position = $vacancy->position ?: $vacancy->title;

        if ($application->alumni_destination_id) {
            $dest = AlumniDestination::find($application->alumni_destination_id);
            if ($dest) {
                $existingNotes = $dest->cleanApprovedNotes();
                $dest->update([
                    'destination_type' => 'Kerja',
                    'destination_name' => $company,
                    'program_or_position' => $position,
                    'year_entered' => (int) date('Y'),
                    'notes' => trim(($existingNotes ? $existingNotes . "\n" : '') . 'Dari BKK: ' . $vacancy->title),
                ]);

                return;
            }
        }

        $dest = AlumniDestination::create([
            'institution_id' => (int) $student->institution_id,
            'student_id' => $student->id,
            'destination_type' => 'Kerja',
            'destination_name' => $company,
            'program_or_position' => $position,
            'year_entered' => (int) date('Y'),
            'notes' => 'Dari BKK: ' . $vacancy->title,
        ]);

        $application->update(['alumni_destination_id' => $dest->id]);
    }

    private function filteredQuery(Request $request, int $institutionId)
    {
        return BkkApplication::forInstitution($institutionId)
            ->when($request->filled('bkk_vacancy_id'), fn ($q) => $q->where('bkk_vacancy_id', $request->get('bkk_vacancy_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->get('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = '%' . $request->get('search') . '%';
                $q->whereHas('student', function ($sq) use ($s) {
                    $sq->where('name', 'like', $s)
                        ->orWhere('nis', 'like', $s)
                        ->orWhere('nisn', 'like', $s);
                });
            });
    }

    private function denyOutside(Request $request, int $recordInstitutionId): ?JsonResponse
    {
        $user = $request->user();
        if ($user->isAdminOrSuperAdmin()) {
            return null;
        }
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId || (int) $recordInstitutionId !== (int) $institutionId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return null;
    }
}
