<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\BukuIndukService;
use App\Services\StudentService;
use App\Support\InstitutionContext;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BukuIndukController extends Controller
{
    public function __construct(
        private BukuIndukService $bukuIndukService,
        private StudentService $studentService
    ) {}

    /**
     * Get buku induk data for a student (JSON).
     */
    public function show(Request $request, $id)
    {
        try {
            $student = $this->studentService->find($id);

            if (!$this->userCanAccessStudent($request, $student)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $data = $this->bukuIndukService->getDataForStudent((int) $id);

            return response()->json([
                'message' => 'Data buku induk berhasil diambil',
                'data' => [
                    'student' => $data['student']->toArray(),
                    'institution' => $data['institution'] ? $data['institution']->toArray() : null,
                    'class_history' => $data['class_history']->toArray(),
                    'mutations' => $data['mutations']->values()->all(),
                    'achievements' => $data['achievements']->toArray(),
                    'violations' => $data['violations']->toArray(),
                    'counseling_sessions' => $data['counseling_sessions']->toArray(),
                    'document_pickups' => $data['document_pickups']->toArray(),
                    'attendance_summary' => $data['attendance_summary'],
                    'grades_summary' => $data['grades_summary'],
                    'extracurriculars' => $data['extracurriculars']->values()->all(),
                    'alumni_destinations' => $data['alumni_destinations']->toArray(),
                    'library_loans_summary' => $data['library_loans_summary'],
                    'health_records' => $data['health_records'],
                    'printed_at' => $data['printed_at'],
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Siswa tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Buku induk show failed', ['student_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data buku induk',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Print buku induk as PDF.
     */
    public function print(Request $request, $id)
    {
        try {
            $student = $this->studentService->find($id);

            if (!$this->userCanAccessStudent($request, $student)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $data = $this->bukuIndukService->getDataForStudent((int) $id);

            $pdf = DomPDF::loadView('buku_induk.print', $data);

            $filename = 'Buku_Induk_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $student->name ?? $student->id) . '_' . ($student->nis ?? $student->id) . '.pdf';

            return $pdf->download($filename);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Siswa tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Buku induk print failed', ['student_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Terjadi kesalahan saat mencetak buku induk',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function userCanAccessStudent(Request $request, $student): bool
    {
        $user = $request->user();
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        $institutionId = InstitutionContext::resolveForUser(
            $user,
            $request,
            $request->get('institution_id')
        );

        return $this->studentService->canAccess($student, $institutionId, false);
    }
}
