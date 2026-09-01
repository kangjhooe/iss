<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\Extracurricular;
use App\Models\Institution;
use App\Models\LessonSchedule;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Data menu dinamis guru: kelas wali, mapel ajar, ekskul dibina, lab diampu.
 * Selalu di-scope ke institusi aktif (induk / non-induk).
 */
class TeacherMenuContext
{
    public static function employeeFor(User $user, ?Request $request = null): ?Employee
    {
        $request = $request ?? request();

        return InstitutionContext::employeeForInstitution($user, null, $request);
    }

    /**
     * @return Collection<int, array{id:int,name:?string,grade:?string}>
     */
    public static function homeroomClasses(User $user, ?Request $request = null): Collection
    {
        $employee = self::employeeFor($user);
        if (!$employee) {
            return collect();
        }

        $request = $request ?? request();
        $institutionId = InstitutionContext::resolveActiveInstitutionId($user, $request);
        if (!$institutionId) {
            return collect();
        }

        $query = SchoolClass::query()
            ->where('teacher_id', $employee->id)
            ->where('institution_id', $institutionId)
            ->select('id', 'name', 'grade', 'academic_year_id');

        $activeYearId = Institution::where('id', $institutionId)->value('active_academic_year_id');
        if ($activeYearId) {
            $query->where('academic_year_id', $activeYearId);
        }

        return $query
            ->orderBy('grade')
            ->orderBy('name')
            ->get()
            ->map(fn (SchoolClass $class) => [
                'id' => (int) $class->id,
                'name' => $class->name,
                'grade' => $class->grade,
            ])
            ->values();
    }

    /**
     * Pair unik (subject, class) dari jadwal semester aktif di sekolah aktif.
     *
     * @return Collection<int, array{
     *   subject_id:int,
     *   subject_name:?string,
     *   class_id:int,
     *   class_name:?string,
     *   semester_id:?int,
     *   label:string
     * }>
     */
    public static function teachingAssignments(User $user, ?Request $request = null): Collection
    {
        $employee = self::employeeFor($user);
        if (!$employee) {
            return collect();
        }

        $request = $request ?? request();
        $institutionId = InstitutionContext::resolveActiveInstitutionId($user, $request);
        if (!$institutionId) {
            return collect();
        }

        $activeSemesterId = Institution::where('id', $institutionId)->value('active_semester_id');

        $query = LessonSchedule::query()
            ->with(['subject:id,name', 'schoolClass:id,name'])
            ->where('employee_id', $employee->id)
            ->where('institution_id', $institutionId);

        if ($activeSemesterId) {
            $query->where('semester_id', $activeSemesterId);
        }

        $seen = [];
        $items = collect();

        foreach ($query->get() as $schedule) {
            $subjectId = (int) $schedule->subject_id;
            $classId = (int) $schedule->class_id;
            if (!$subjectId || !$classId) {
                continue;
            }

            $key = $subjectId . '-' . $classId;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $subjectName = $schedule->subject?->name ?: 'Mapel';
            $className = $schedule->schoolClass?->name ?: 'Kelas';

            $items->push([
                'subject_id' => $subjectId,
                'subject_name' => $schedule->subject?->name,
                'class_id' => $classId,
                'class_name' => $schedule->schoolClass?->name,
                'semester_id' => $activeSemesterId ? (int) $activeSemesterId : ($schedule->semester_id ? (int) $schedule->semester_id : null),
                'label' => $subjectName . ' — ' . $className,
            ]);
        }

        return $items
            ->sortBy(fn ($row) => mb_strtolower(($row['subject_name'] ?? '') . ' ' . ($row['class_name'] ?? '')))
            ->values();
    }

    /**
     * Ekskul yang dibina guru di sekolah aktif.
     *
     * @return Collection<int, array{id:int,name:?string,status:?string}>
     */
    public static function supervisedExtracurriculars(User $user, ?Request $request = null): Collection
    {
        $employee = self::employeeFor($user);
        if (!$employee) {
            return collect();
        }

        $request = $request ?? request();
        $institutionId = InstitutionContext::resolveActiveInstitutionId($user, $request);
        if (!$institutionId) {
            return collect();
        }

        return Extracurricular::query()
            ->where('supervisor_employee_id', $employee->id)
            ->where('institution_id', $institutionId)
            ->orderBy('name')
            ->get(['id', 'name', 'status'])
            ->map(fn (Extracurricular $item) => [
                'id' => (int) $item->id,
                'name' => $item->name,
                'status' => $item->status,
            ])
            ->values();
    }

    /**
     * Lab yang diampu (penanggung jawab) di sekolah aktif.
     *
     * @return Collection<int, array{id:int,name:?string,code:?string,lab_type:?string}>
     */
    public static function managedLabs(User $user, ?Request $request = null): Collection
    {
        $employee = self::employeeFor($user);
        if (!$employee) {
            return collect();
        }

        $request = $request ?? request();
        $institutionId = InstitutionContext::resolveActiveInstitutionId($user, $request);
        if (!$institutionId) {
            return collect();
        }

        return Room::query()
            ->where('type', 'Laboratorium')
            ->where('responsible_employee_id', $employee->id)
            ->where('institution_id', $institutionId)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'lab_type'])
            ->map(fn (Room $room) => [
                'id' => (int) $room->id,
                'name' => $room->name,
                'code' => $room->code,
                'lab_type' => $room->lab_type,
            ])
            ->values();
    }
}
