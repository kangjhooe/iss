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
     * Canonical phone for comparison (strip 62 / leading 0).
     * "081234567890", "+62 812-3456-7890", "6281234567890" → "81234567890"
     */
    public static function canonicalPhone(?string $value): string
    {
        $digits = self::normalizeContact($value);
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '62') && strlen($digits) >= 11) {
            $digits = substr($digits, 2);
        }

        return ltrim($digits, '0');
    }

    public static function contactsMatch(?string $a, ?string $b): bool
    {
        $ca = self::canonicalPhone($a);
        $cb = self::canonicalPhone($b);
        if ($ca === '' || $cb === '') {
            return false;
        }
        if (strlen($ca) < 8 || strlen($cb) < 8) {
            return false;
        }

        return $ca === $cb;
    }

    /**
     * Students linked via parent_links OR guardian_phone matching parent email digits.
     */
    public static function linkedStudents(User $user): Collection
    {
        if (! $user->isParent()) {
            return collect();
        }

        $linkedIds = ParentLink::query()
            ->where('user_id', $user->id)
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $students = collect();

        if (! empty($linkedIds)) {
            $students = Student::query()
                ->with(['schoolClass:id,name', 'institution:id,name,npsn'])
                ->whereIn('id', $linkedIds)
                ->get();
        }

        // Match guardian_phone to parent login (email often stores phone digits) across schools.
        $canonical = self::canonicalPhone($user->email);
        if ($canonical !== '' && strlen($canonical) >= 8) {
            $suffix = substr($canonical, -10);
            $byPhone = Student::query()
                ->with(['schoolClass:id,name', 'institution:id,name,npsn'])
                ->whereNotNull('guardian_phone')
                ->where('guardian_phone', '!=', '')
                ->where(function ($q) use ($suffix, $canonical) {
                    $q->where('guardian_phone', 'like', '%'.$suffix)
                        ->orWhere('guardian_phone', 'like', '%0'.$canonical)
                        ->orWhere('guardian_phone', 'like', '%62'.$canonical);
                })
                ->get()
                ->filter(fn (Student $s) => self::contactsMatch($s->guardian_phone, $user->email));

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
     * Includes parents whose home institution is elsewhere if phone matches local students.
     */
    public static function parentUsersForInstitution(int $institutionId): Collection
    {
        $userIds = ParentLink::query()
            ->where('institution_id', $institutionId)
            ->pluck('user_id')
            ->unique()
            ->all();

        $phoneCanonicals = Student::query()
            ->where('institution_id', $institutionId)
            ->whereNotNull('guardian_phone')
            ->where('guardian_phone', '!=', '')
            ->pluck('guardian_phone')
            ->map(fn ($p) => self::canonicalPhone($p))
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
                if (! empty($userIds)) {
                    $q->orWhereIn('id', $userIds);
                }
                $q->orWhere('institution_id', $institutionId);
            })
            ->get();

        // Phone-matched parents may belong to another institution_id
        if (! empty($phoneCanonicals)) {
            $already = $parents->pluck('id')->all();
            $phoneQuery = User::query()
                ->where('role', 'parent')
                ->where(function ($q) {
                    $q->whereNull('is_active')->orWhere('is_active', true);
                })
                ->when(! empty($already), fn ($q) => $q->whereNotIn('id', $already))
                ->where(function ($q) use ($phoneCanonicals) {
                    foreach ($phoneCanonicals as $canon) {
                        $suffix = substr($canon, -10);
                        $q->orWhere('email', 'like', '%'.$suffix)
                            ->orWhere('email', 'like', '%0'.$canon)
                            ->orWhere('email', 'like', '%62'.$canon);
                    }
                })
                ->limit(500)
                ->get()
                ->filter(fn (User $u) => in_array(self::canonicalPhone($u->email), $phoneCanonicals, true));

            $parents = $parents->merge($phoneQuery);
        }

        return $parents->unique('id')->values();
    }
}
