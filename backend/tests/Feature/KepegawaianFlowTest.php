<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\StructuralPosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KepegawaianFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected User $teacherUser;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMA Kepegawaian Test',
            'npsn' => '80808080',
            'level' => 'SMA',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin TU',
            'email' => 'admin-kepegawaian@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'institution_id' => $this->institution->id,
            'nik' => '3201010101010001',
            'type' => 'Guru',
            'name' => 'Guru Cuti',
            'gender' => 'L',
            'email' => 'guru-cuti@example.com',
            'status' => 'Aktif',
            'employment_status' => 'PNS',
            'join_date' => '2020-01-01',
        ]);

        $this->teacherUser = User::create([
            'name' => 'Guru Cuti',
            'email' => 'guru-cuti@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    public function test_admin_can_manage_leave_decree_position_and_history(): void
    {
        Sanctum::actingAs($this->admin);

        $leaveRes = $this->postJson('/api/v1/employee-leaves', [
            'employee_id' => $this->employee->id,
            'leave_type' => 'tahunan',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-05',
            'reason' => 'Liburan keluarga',
            'status' => 'pending',
        ]);
        $leaveRes->assertCreated();
        $leaveId = $leaveRes->json('data.id');

        $this->postJson("/api/v1/employee-leaves/{$leaveId}/decide", [
            'action' => 'approve',
        ])->assertOk()->assertJsonPath('data.status', 'approved');

        $decreeRes = $this->postJson('/api/v1/employee-decrees', [
            'employee_id' => $this->employee->id,
            'decree_type' => 'jabatan',
            'number' => 'SK/001/2026',
            'title' => 'Pengangkatan Waka Kurikulum',
            'decree_date' => '2026-07-01',
            'effective_date' => '2026-07-15',
        ]);
        $decreeRes->assertCreated();
        $decreeId = $decreeRes->json('data.id');

        $position = StructuralPosition::where('key', 'waka_kurikulum')->first();
        $this->assertNotNull($position);

        $assignRes = $this->postJson('/api/v1/employee-structural-positions', [
            'employee_id' => $this->employee->id,
            'structural_position_id' => $position->id,
            'employee_decree_id' => $decreeId,
            'started_at' => '2026-07-15',
            'decree_number' => 'SK/001/2026',
        ]);
        $assignRes->assertCreated()->assertJsonPath('data.is_active', true);

        $history = $this->getJson('/api/v1/employee-career-history/' . $this->employee->id);
        $history->assertOk();
        $types = collect($history->json('data.timeline'))->pluck('type')->all();
        $this->assertContains('leave', $types);
        $this->assertContains('decree', $types);
        $this->assertContains('structural_position', $types);
        $this->assertContains('join', $types);

        $this->assertDatabaseHas('employee_additional_duties', [
            'employee_id' => $this->employee->id,
            'additional_duty_id' => \App\Models\AdditionalDuty::where('key', 'waka_kurikulum')->value('id'),
        ]);

        $this->teacherUser->unsetRelation('permissions');
        $this->assertTrue($this->teacherUser->fresh()->permissions()->where('key', 'class')->exists());

        $assignmentId = $assignRes->json('data.id');
        $this->postJson("/api/v1/employee-structural-positions/{$assignmentId}/end", [
            'ended_at' => '2026-12-31',
        ])->assertOk();

        $this->assertDatabaseMissing('employee_additional_duties', [
            'employee_id' => $this->employee->id,
            'additional_duty_id' => \App\Models\AdditionalDuty::where('key', 'waka_kurikulum')->value('id'),
        ]);
    }

    public function test_approved_leave_fills_weekday_attendance_and_cancel_clears_it(): void
    {
        Sanctum::actingAs($this->admin);

        $leaveRes = $this->postJson('/api/v1/employee-leaves', [
            'employee_id' => $this->employee->id,
            'leave_type' => 'tahunan',
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-05',
            'reason' => 'Cuti uji absensi',
            'status' => 'pending',
        ]);
        $leaveRes->assertCreated();
        $leaveId = $leaveRes->json('data.id');

        $this->postJson("/api/v1/employee-leaves/{$leaveId}/decide", [
            'action' => 'approve',
        ])->assertOk();

        $this->assertEquals(3, \App\Models\EmployeeAttendance::query()
            ->where('employee_id', $this->employee->id)
            ->where('status', 'cuti')
            ->count());

        $this->postJson("/api/v1/employee-leaves/{$leaveId}/cancel")->assertOk();

        $this->assertEquals(0, \App\Models\EmployeeAttendance::query()
            ->where('employee_id', $this->employee->id)
            ->where('status', 'cuti')
            ->count());
    }

    public function test_teacher_can_submit_own_leave(): void
    {
        Sanctum::actingAs($this->teacherUser);

        $res = $this->postJson('/api/v1/employee-leaves/my', [
            'leave_type' => 'sakit',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
            'reason' => 'Demam',
        ]);

        $res->assertCreated()
            ->assertJsonPath('data.employee_id', $this->employee->id)
            ->assertJsonPath('data.status', 'pending');

        $this->getJson('/api/v1/employee-leaves/my')
            ->assertOk()
            ->assertJsonPath('data.0.leave_type', 'sakit');
    }
}
