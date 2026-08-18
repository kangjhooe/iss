<?php

namespace App\Support;

use App\Models\ParentLink;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;

class ParentAccess
{
    public static function normalizeContact(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return preg_replace('/\D+/', '', $value) ?: '';
    }

    /**
     * Students linked via parent_links OR guardian_phone matching parent email digits.
     */
    public static function linkedStudents(User $user): Collection
    {
        if (!$user->isParent()) {
            return collect();
        }

        $linkedIds = ParentLink::query()
            ->where('user_id', $user->id)
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $students = collect();

        if (!empty($linkedIds)) {
            $students = Student::query()
                ->with(['schoolClass:id,name', 'institution:id,name,npsn'])
                ->whereIn('id', $linkedIds)
                ->get();
        }

        $contactDigits = self::normalizeContact($user->email);
        if ($contactDigits !== '' && strlen($contactDigits) >= 8) {
            $byPhone = Student::query()
                ->with(['schoolClass:id,name', 'institution:id,name,npsn'])
                ->when($user->institution_id, fn ($q) => $q->where('institution_id', $user->institution_id))
                ->whereNotNull('guardian_phone')
                ->where('guardian_phone', '!=', '')
                ->get()
                ->filter(fn (Student $s) => self::normalizeContact($s->guardian_phone) === $contactDigits);

            $students = $students->merge($byPhone)->unique('id');
        }

        return $students->sortBy('name')->values();
    }

    public static function linkedStudentIds(User $user): array
    {
        return self::linkedStudents($user)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public static function canAccessStudent(User $user, int $studentId): bool
    {
        return in_array($studentId, self::linkedStudentIds($user), true);
    }

    /**
     * Parent users for an institution (pivot + guardian_phone digit match on email).
     */
    public static function parentUsersForInstitution(int $institutionId): Collection
    {
        $userIds = ParentLink::query()
            ->where('institution_id', $institutionId)
            ->pluck('user_id')
            ->unique()
            ->all();

        $phoneDigits = Student::query()
            ->where('institution_id', $institutionId)
            ->whereNotNull('guardian_phone')
            ->where('guardian_phone', '!=', '')
            ->pluck('guardian_phone')
            ->map(fn ($p) => self::normalizeContact($p))
            ->filter(fn ($d) => strlen($d) >= 8)
            ->unique()
            ->values()
            ->all();

        $parents = User::query()
            ->where('role', 'parent')
            ->where(function ($q) {
                $q->whereNull('is_active')->orWhere('is_active', true);
            })
            ->where(function ($q) use ($userIds, $institutionId) {
                if (!empty($userIds)) {
                    $q->orWhereIn('id', $userIds);
                }
                $q->orWhere('institution_id', $institutionId);
            })
            ->get()
            ->filter(function (User $u) use ($userIds, $phoneDigits, $institutionId) {
                if (in_array($u->id, $userIds, true)) {
                    return true;
                }
                if ((int) $u->institution_id !== (int) $institutionId) {
                    return false;
                }
                $digits = self::normalizeContact($u->email);

                return $digits !== '' && in_array($digits, $phoneDigits, true);
            });

        return $parents->unique('id')->values();
    }
}
