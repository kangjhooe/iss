<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;

class BankSoalShareService
{
    /**
     * Resolve User from teacher NIK (system-wide).
     * Returns the User if the NIK belongs to a Guru (Employee type Guru) who has a user account; null otherwise.
     */
    public function resolveUserByTeacherNik(string $nik): ?User
    {
        $teacher = Employee::where('type', 'Guru')
            ->where('nik', $nik)
            ->first();

        if (!$teacher || !$teacher->email) {
            return null;
        }

        return User::where('email', $teacher->email)->first();
    }
}
