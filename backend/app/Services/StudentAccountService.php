<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StudentAccountService
{
    public const SYNTHETIC_EMAIL_DOMAIN = 'siswa.local';

    /**
     * Default password: birth date as DDMMYYYY.
     */
    public function defaultPasswordFromBirthDate(Student $student): string
    {
        if (empty($student->birth_date)) {
            throw ValidationException::withMessages([
                'birth_date' => ['Tanggal lahir siswa wajib diisi untuk membuat/reset akun login.'],
            ]);
        }

        return Carbon::parse($student->birth_date)->format('dmY');
    }

    /**
     * Ensure student has a login account (role=student, login via NIK).
     *
     * @return array{user_created: bool, user_updated: bool, user: ?User, default_password: ?string, skipped_reason: ?string}
     */
    public function ensureAccount(Student $student, ?string $previousNik = null): array
    {
        $result = [
            'user_created' => false,
            'user_updated' => false,
            'user' => null,
            'default_password' => null,
            'skipped_reason' => null,
        ];

        $nik = trim((string) ($student->nik ?? ''));
        if ($nik === '' || !preg_match('/^\d{16}$/', $nik)) {
            $result['skipped_reason'] = 'NIK tidak valid';
            return $result;
        }

        if (empty($student->birth_date)) {
            $result['skipped_reason'] = 'Tanggal lahir kosong';
            return $result;
        }

        $account = $this->findAccount($student, $previousNik);

        if ($account && $account->role !== 'student') {
            $result['skipped_reason'] = 'Email/NIK bentrok dengan akun non-siswa';
            Log::warning('Student account ensure skipped: role conflict', [
                'student_id' => $student->id,
                'user_id' => $account->id,
                'role' => $account->role,
            ]);
            return $result;
        }

        $email = $this->resolveAccountEmail($student, $account);
        $plainPassword = $this->defaultPasswordFromBirthDate($student);

        if ($account) {
            $updates = [
                'institution_id' => $student->institution_id,
                'name' => $student->name,
                'login_nik' => $nik,
                'email' => $email,
            ];
            $account->fill($updates);
            if ($account->isDirty()) {
                $account->save();
                $result['user_updated'] = true;
            }
            $result['user'] = $account;
            return $result;
        }

        $user = User::create([
            'institution_id' => $student->institution_id,
            'name' => $student->name,
            'email' => $email,
            'login_nik' => $nik,
            'password' => $plainPassword,
            'role' => 'student',
            'email_verified_at' => now(),
            'must_change_password' => true,
            'is_active' => true,
        ]);

        Log::info('Student user account created', [
            'student_id' => $student->id,
            'user_id' => $user->id,
        ]);

        $result['user_created'] = true;
        $result['user'] = $user;
        $result['default_password'] = $plainPassword;

        return $result;
    }

    /**
     * Reset password to birth date and force change on next login.
     */
    public function resetPasswordToBirthDate(Student $student): User
    {
        $ensure = $this->ensureAccount($student);
        $user = $ensure['user'] ?? $this->findAccount($student);

        if (!$user || $user->role !== 'student') {
            throw ValidationException::withMessages([
                'account' => ['Akun login siswa tidak ditemukan atau tidak dapat dibuat.'],
            ]);
        }

        $plain = $this->defaultPasswordFromBirthDate($student);
        $user->forceFill([
            'password' => $plain,
            'must_change_password' => true,
            'login_nik' => $student->nik,
        ])->save();
        $user->resetFailedLoginAttempts();

        Log::info('Student password reset to birth date', [
            'student_id' => $student->id,
            'user_id' => $user->id,
        ]);

        return $user->fresh();
    }

    public function findAccount(Student $student, ?string $previousNik = null): ?User
    {
        if (!empty($student->nik)) {
            $byNik = User::where('login_nik', $student->nik)->first();
            if ($byNik) {
                return $byNik;
            }
        }

        if ($previousNik) {
            $byPrev = User::where('login_nik', $previousNik)->where('role', 'student')->first();
            if ($byPrev) {
                return $byPrev;
            }
        }

        if (!empty($student->email)) {
            $byEmail = User::where('email', $student->email)->where('role', 'student')->first();
            if ($byEmail) {
                return $byEmail;
            }
        }

        $synthetic = $this->syntheticEmail((string) $student->nik);
        if ($student->nik) {
            return User::where('email', $synthetic)->where('role', 'student')->first();
        }

        return null;
    }

    public function resolveAccountEmail(Student $student, ?User $existing = null): string
    {
        if (!empty($student->email)) {
            $conflictQuery = User::where('email', $student->email);
            if ($existing) {
                $conflictQuery->where('id', '!=', $existing->id);
            }
            if (!$conflictQuery->exists()) {
                return $student->email;
            }
        }

        return $this->syntheticEmail((string) $student->nik);
    }

    public function syntheticEmail(string $nik): string
    {
        return $nik . '@' . self::SYNTHETIC_EMAIL_DOMAIN;
    }

    public function isSyntheticEmail(?string $email): bool
    {
        if (!$email) {
            return false;
        }

        return str_ends_with(strtolower($email), '@' . self::SYNTHETIC_EMAIL_DOMAIN);
    }

    /**
     * Credentials shown once after student account create/reset.
     */
    public function loginHintFor(Student $student): array
    {
        $hint = [
            'login' => 'NIK',
            'login_value' => $student->nik,
            'default_password' => 'Tanggal lahir (DDMMYYYY)',
            'must_change_password' => true,
        ];

        if (!empty($student->birth_date)) {
            try {
                $hint['password'] = $this->defaultPasswordFromBirthDate($student);
            } catch (\Throwable) {
                // Birth date invalid — omit plaintext password.
            }
        }

        return $hint;
    }

    /**
     * Ensure accounts for many students.
     *
     * @param  iterable<Student>  $students
     * @return array{
     *     processed: int,
     *     created: int,
     *     updated: int,
     *     skipped: int,
     *     errors: list<array{student_id: int|null, name: string|null, reason: string}>,
     *     created_accounts: list<array{name: string|null, nik: string|null, password: string|null}>
     * }
     */
    public function bulkEnsure(iterable $students): array
    {
        $result = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'created_accounts' => [],
        ];

        foreach ($students as $student) {
            if (!$student instanceof Student) {
                continue;
            }

            $result['processed']++;

            try {
                $ensure = $this->ensureAccount($student);
                if ($ensure['user_created']) {
                    $result['created']++;
                    $result['created_accounts'][] = [
                        'name' => $student->name,
                        'nik' => $student->nik,
                        'password' => $ensure['default_password'],
                    ];
                } elseif ($ensure['user_updated']) {
                    $result['updated']++;
                } elseif ($ensure['user']) {
                    // Already present, no changes
                    $result['updated']++;
                } else {
                    $result['skipped']++;
                    $result['errors'][] = [
                        'student_id' => $student->id,
                        'name' => $student->name,
                        'reason' => $ensure['skipped_reason'] ?? 'Dilewati',
                    ];
                }
            } catch (\Throwable $e) {
                $result['skipped']++;
                $result['errors'][] = [
                    'student_id' => $student->id,
                    'name' => $student->name,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return $result;
    }
}
