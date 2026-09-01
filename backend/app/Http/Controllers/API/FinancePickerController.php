<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lightweight class/student lists for keuangan forms.
 * Does not require module:class or module:student (finance staff often lack those).
 */
class FinancePickerController extends Controller
{
    protected function institutionId(Request $request): ?int
    {
        return InstitutionContext::resolveForUser(
            $request->user(),
            $request,
            $request->get('institution_id')
        );
    }

    public function classesLite(Request $request): JsonResponse
    {
        $institutionId = $this->institutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $institution = Institution::find($institutionId);
        $query = SchoolClass::query()
            ->where('institution_id', $institutionId)
            ->orderBy('grade')
            ->orderBy('name');

        if ($institution?->active_academic_year_id) {
            $query->where('academic_year_id', $institution->active_academic_year_id);
        }

        return response()->json([
            'data' => $query->get(['id', 'name', 'grade', 'code']),
        ]);
    }

    /**
     * Filter by class_id and/or q (nama/NIS/NISN/NIK).
     */
    public function studentsLite(Request $request): JsonResponse
    {
        $institutionId = $this->institutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $q = trim((string) $request->get('q', $request->get('search', '')));
        $classId = $request->get('class_id');
        $studentId = $request->get('student_id');

        if (!$classId && $q === '' && !$studentId) {
            return response()->json([
                'message' => 'Pilih kelas atau ketik nama/NIS siswa.',
                'data' => [],
            ]);
        }

        $query = Student::query()
            ->leftJoin('class', 'student.class_id', '=', 'class.id')
            ->where('student.institution_id', $institutionId)
            ->where(function ($w) {
                $w->where('student.status', 'Aktif')->orWhereNull('student.status');
            })
            ->orderBy('student.name')
            ->select([
                'student.id',
                'student.name',
                'student.nis',
                'student.nisn',
                'student.nik',
                'student.class_id',
                'class.name as class_name',
            ]);

        if ($studentId) {
            $query->where('student.id', (int) $studentId)->limit(1);
        } elseif ($classId) {
            $query->where('student.class_id', (int) $classId)->limit(200);
        } else {
            $query->limit(50);
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('student.name', 'like', "%{$q}%")
                    ->orWhere('student.nis', 'like', "%{$q}%")
                    ->orWhere('student.nisn', 'like', "%{$q}%")
                    ->orWhere('student.nik', 'like', "%{$q}%");
            });
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }
}
