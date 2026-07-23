<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeInstitutionAssignment;
use App\Models\Institution;
use App\Models\SchoolClass;
use App\Models\TeacherMutation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherMutationTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $origin;

    protected Institution $target;

    protected User $originAdmin;

    protected User $targetAdmin;

    protected Employee $teacher;

    protected User $teacherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->origin = Institution::create([
            'name' => 'SMP Asal',
            'npsn' => '10101010',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->target = Institution::create([
            'name' => 'SMA Tujuan',
            'npsn' => '20202020',
            'level' => 'SMA',
            'is_active' => true,
        ]);

        $this->originAdmin = User::create([
            'name' => 'Admin Asal',
            'email' => 'admin-asal@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->origin->id,
            'email_verified_at' => now(),
        ]);

        $this->targetAdmin = User::create([
            'name' => 'Admin Tujuan',
            'email' => 'admin-tujuan@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->target->id,
            'email_verified_at' => now(),
        ]);

        $this->teacherUser = User::create([
            'name' => 'Guru Mutasi',
            'email' => 'guru-mutasi@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
            'institution_id' => $this->origin->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->teacher = Employee::create([
            'institution_id' => $this->origin->id,
            'type' => 'Guru',
            'nuptk' => '1234567890123456',
            'nip' => '198001012005011001',
            'name' => 'Guru Mutasi',
            'gender' => 'L',
            'email' => $this->teacherUser->email,
            'status' => 'Aktif',
        ]);
    }

    public function test_push_and_approve_moves_home_institution_and_clears_wali(): void
    {
        SchoolClass::create([
            'institution_id' => $this->origin->id,
            'name' => 'VII A',
            'grade' => 7,
            'academic_year' => '2025/2026',
            'teacher_id' => $this->teacher->id,
            'status' => 'Aktif',
        ]);

        EmployeeInstitutionAssignment::create([
            'employee_id' => $this->teacher->id,
            'institution_id' => $this->target->id,
            'assignment_type' => 'non_induk',
            'status' => 'approved',
            'approved_at' => now(),
            'started_at' => now()->toDateString(),
        ]);

        Sanctum::actingAs($this->originAdmin);

        $create = $this->postJson('/api/v1/teacher-mutations', [
            'target_npsn' => $this->target->npsn,
            'nuptk' => $this->teacher->nuptk,
            'notes' => 'Pindah tugas',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.initiated_by', 'origin');

        $mutationId = $create->json('data.id');

        Sanctum::actingAs($this->targetAdmin);

        $approve = $this->postJson("/api/v1/teacher-mutations/{$mutationId}/approve", [
            'action' => 'approve',
        ]);

        $approve->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->teacher->refresh();
        $this->teacherUser->refresh();

        $this->assertSame($this->target->id, (int) $this->teacher->institution_id);
        $this->assertSame('Aktif', $this->teacher->status);
        $this->assertSame($this->target->id, (int) $this->teacherUser->institution_id);
        $this->assertNull(SchoolClass::where('name', 'VII A')->value('teacher_id'));
        $this->assertSame(
            'ended',
            EmployeeInstitutionAssignment::where('employee_id', $this->teacher->id)
                ->where('institution_id', $this->target->id)
                ->value('status')
        );
    }

    public function test_external_out_marks_teacher_pindah(): void
    {
        Sanctum::actingAs($this->originAdmin);

        $response = $this->postJson('/api/v1/teacher-mutations', [
            'external' => true,
            'target_npsn' => '30303030',
            'target_school_name' => 'SMP Luar Sistem',
            'nuptk' => $this->teacher->nuptk,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.is_external_target', true);

        $this->teacher->refresh();
        $this->teacherUser->refresh();

        $this->assertSame('Pindah', $this->teacher->status);
        $this->assertSame($this->origin->id, (int) $this->teacher->institution_id);
        $this->assertFalse((bool) $this->teacherUser->is_active);
    }

    public function test_pull_requires_origin_admin_approval(): void
    {
        Sanctum::actingAs($this->targetAdmin);

        $create = $this->postJson('/api/v1/teacher-mutations/pull', [
            'origin_npsn' => $this->origin->npsn,
            'nuptk' => $this->teacher->nuptk,
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.initiated_by', 'target');

        $mutationId = $create->json('data.id');

        Sanctum::actingAs($this->originAdmin);

        $approve = $this->postJson("/api/v1/teacher-mutations/{$mutationId}/approve", [
            'action' => 'approve',
        ]);

        $approve->assertOk()->assertJsonPath('data.status', 'approved');

        $this->teacher->refresh();
        $this->assertSame($this->target->id, (int) $this->teacher->institution_id);
    }

    public function test_cancel_pending_then_approve_cancel_rolls_back(): void
    {
        Sanctum::actingAs($this->originAdmin);
        $create = $this->postJson('/api/v1/teacher-mutations', [
            'target_npsn' => $this->target->npsn,
            'nuptk' => $this->teacher->nuptk,
        ]);
        $mutationId = $create->json('data.id');

        Sanctum::actingAs($this->targetAdmin);
        $this->postJson("/api/v1/teacher-mutations/{$mutationId}/approve", ['action' => 'approve'])
            ->assertOk();

        Sanctum::actingAs($this->originAdmin);
        $cancel = $this->postJson("/api/v1/teacher-mutations/{$mutationId}/cancel", [
            'reason' => 'Dibatalkan',
        ]);
        $cancel->assertOk()->assertJsonPath('data.status', 'cancel_pending');

        Sanctum::actingAs($this->targetAdmin);
        $decide = $this->postJson("/api/v1/teacher-mutations/{$mutationId}/cancel-decision", [
            'action' => 'approve',
        ]);
        $decide->assertOk()->assertJsonPath('data.status', 'cancelled');

        $this->teacher->refresh();
        $this->teacherUser->refresh();
        $this->assertSame($this->origin->id, (int) $this->teacher->institution_id);
        $this->assertSame($this->origin->id, (int) $this->teacherUser->institution_id);
        $this->assertSame('cancelled', TeacherMutation::find($mutationId)->status);
    }

    public function test_cross_jenjang_is_allowed(): void
    {
        Sanctum::actingAs($this->originAdmin);

        // origin SMP → target SMA (would be blocked for students)
        $this->postJson('/api/v1/teacher-mutations', [
            'target_npsn' => $this->target->npsn,
            'nuptk' => $this->teacher->nuptk,
        ])->assertCreated();
    }
}
