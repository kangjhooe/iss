<?php

namespace Tests\Support;

use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\EmployeeInstitutionAssignment;
use App\Models\Extracurricular;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Helper untuk skenario guru multi-sekolah (induk + non-induk).
 *
 * Membutuhkan database MySQL `testing` (lihat phpunit.xml).
 */
trait MultiInstitutionTeacherSetup
{
    protected Institution $homeInstitution;

    protected Institution $guestInstitution;

    protected User $teacherUser;

    protected Employee $teacherEmployee;

    protected function setUpMultiInstitutionTeacher(): void
    {
        $this->homeInstitution = Institution::create([
            'name' => 'Sekolah Induk Uji',
            'npsn' => '11111111',
            'is_active' => true,
        ]);

        $this->guestInstitution = Institution::create([
            'name' => 'Sekolah Non-Induk Uji',
            'npsn' => '22222222',
            'is_active' => true,
        ]);

        $this->teacherUser = User::create([
            'name' => 'Guru Multi Sekolah',
            'email' => 'guru-multi@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
            'institution_id' => $this->homeInstitution->id,
            'email_verified_at' => now(),
        ]);

        $this->teacherEmployee = Employee::create([
            'institution_id' => $this->homeInstitution->id,
            'nik' => '1234567890123456',
            'type' => 'Guru',
            'name' => $this->teacherUser->name,
            'gender' => 'L',
            'email' => $this->teacherUser->email,
            'status' => 'Aktif',
        ]);

        EmployeeInstitutionAssignment::create([
            'employee_id' => $this->teacherEmployee->id,
            'institution_id' => $this->guestInstitution->id,
            'assignment_type' => 'non_induk',
            'status' => 'approved',
            'approved_at' => now(),
            'started_at' => now()->toDateString(),
        ]);

        $this->attachPermissions($this->teacherUser, [
            'institution',
            'report',
            'schedule',
            'teaching_journal',
            'grade_book',
            'extracurricular',
            'facility',
            'bk_report',
        ]);
    }

    /**
     * @param  array<int, string>  $keys
     */
    protected function attachPermissions(User $user, array $keys): void
    {
        $ids = [];
        foreach ($keys as $key) {
            $permission = Permission::firstOrCreate(
                ['key' => $key],
                ['label' => ucfirst(str_replace('_', ' ', $key))]
            );
            $ids[] = $permission->id;
        }

        $user->permissions()->sync($ids);
    }

    protected function seedExtracurricularAndLab(): array
    {
        $year = AcademicYear::create([
            'code' => '2025/2026',
            'name' => '2025/2026',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->addMonths(10),
            'status' => 'Aktif',
        ]);

        $semester = Semester::create([
            'academic_year_id' => $year->id,
            'name' => 'Semester 1',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(5),
            'status' => 'Aktif',
        ]);

        $this->homeInstitution->update([
            'active_academic_year_id' => $year->id,
            'active_semester_id' => $semester->id,
        ]);
        $this->guestInstitution->update([
            'active_academic_year_id' => $year->id,
            'active_semester_id' => $semester->id,
        ]);

        $homeClass = SchoolClass::create([
            'institution_id' => $this->homeInstitution->id,
            'teacher_id' => $this->teacherEmployee->id,
            'name' => 'VII-A Induk',
            'academic_year' => '2025/2026',
            'academic_year_id' => $year->id,
            'status' => 'Aktif',
        ]);

        $guestClass = SchoolClass::create([
            'institution_id' => $this->guestInstitution->id,
            'teacher_id' => $this->teacherEmployee->id,
            'name' => 'VII-B Tamu',
            'academic_year' => '2025/2026',
            'academic_year_id' => $year->id,
            'status' => 'Aktif',
        ]);

        $homeEkskul = Extracurricular::create([
            'institution_id' => $this->homeInstitution->id,
            'name' => 'Pramuka Induk',
            'supervisor_employee_id' => $this->teacherEmployee->id,
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'status' => 'Aktif',
        ]);

        $guestEkskul = Extracurricular::create([
            'institution_id' => $this->guestInstitution->id,
            'name' => 'Basket Tamu',
            'supervisor_employee_id' => $this->teacherEmployee->id,
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'status' => 'Aktif',
        ]);

        $homeLab = Room::create([
            'institution_id' => $this->homeInstitution->id,
            'name' => 'Lab IPA Induk',
            'type' => 'Laboratorium',
            'lab_type' => 'IPA',
            'responsible_employee_id' => $this->teacherEmployee->id,
        ]);

        $guestLab = Room::create([
            'institution_id' => $this->guestInstitution->id,
            'name' => 'Lab Komputer Tamu',
            'type' => 'Laboratorium',
            'lab_type' => 'Komputer',
            'responsible_employee_id' => $this->teacherEmployee->id,
        ]);

        return compact(
            'year',
            'semester',
            'homeClass',
            'guestClass',
            'homeEkskul',
            'guestEkskul',
            'homeLab',
            'guestLab'
        );
    }

    protected function authHeaders(User $user): array
    {
        $token = $user->createToken('auth_token')->plainTextToken;

        return ['Authorization' => 'Bearer '.$token];
    }
}
