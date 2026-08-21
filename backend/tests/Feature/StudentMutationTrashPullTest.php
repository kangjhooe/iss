<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentMutationTrashPullTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $origin;

    protected Institution $target;

    protected User $originAdmin;

    protected User $targetAdmin;

    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->origin = Institution::create([
            'name' => 'SMP Asal',
            'npsn' => '11111111',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->target = Institution::create([
            'name' => 'SMP Tujuan',
            'npsn' => '22222222',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->originAdmin = User::create([
            'name' => 'Admin Asal',
            'email' => 'admin-asal-siswa@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->origin->id,
            'email_verified_at' => now(),
        ]);

        $this->targetAdmin = User::create([
            'name' => 'Admin Tujuan',
            'email' => 'admin-tujuan-siswa@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->target->id,
            'email_verified_at' => now(),
        ]);

        $this->student = Student::create([
            'institution_id' => $this->origin->id,
            'nik' => '3271010101010001',
            'nisn' => '0011223344',
            'name' => 'Siswa Mutasi',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);
    }

    public function test_pull_trashed_student_restores_at_target_on_approve(): void
    {
        $this->student->delete();
        $this->assertSoftDeleted('student', ['id' => $this->student->id]);

        Sanctum::actingAs($this->targetAdmin);

        $this->getJson('/api/v1/student-mutations/lookup-student-at-origin?origin_npsn='.$this->origin->npsn.'&nik='.$this->student->nik)
            ->assertOk()
            ->assertJsonPath('data.in_trash', true)
            ->assertJsonPath('data.name', 'Siswa Mutasi')
            ->assertJsonPath('data.nik', $this->student->nik);

        $create = $this->postJson('/api/v1/student-mutations/pull', [
            'origin_npsn' => $this->origin->npsn,
            'nik' => $this->student->nik,
        ]);
        $create->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.student.in_trash', true)
            ->assertJsonPath('data.student.nik', $this->student->nik);

        Sanctum::actingAs($this->originAdmin);
        $this->postJson('/api/v1/student-mutations/'.$create->json('data.id').'/approve', [
            'action' => 'approve',
        ])->assertOk()->assertJsonPath('data.status', 'approved');

        $this->student->refresh();
        $this->assertFalse($this->student->trashed());
        $this->assertSame($this->target->id, (int) $this->student->institution_id);
        $this->assertSame('Aktif', $this->student->status);
    }

    public function test_outgoing_lookup_rejects_trashed_student(): void
    {
        $this->student->delete();

        Sanctum::actingAs($this->originAdmin);

        $this->getJson('/api/v1/student-mutations/lookup-student?nik='.$this->student->nik)
            ->assertNotFound()
            ->assertJsonPath('message', 'Siswa ada di kotak sampah. Pulihkan dulu dari Data Siswa, lalu ajukan mutasi.');
    }

    public function test_outgoing_lookup_finds_student_without_nisn(): void
    {
        $this->student->update(['nisn' => null]);

        Sanctum::actingAs($this->originAdmin);

        $this->getJson('/api/v1/student-mutations/lookup-student?nik='.$this->student->nik)
            ->assertOk()
            ->assertJsonPath('data.name', 'Siswa Mutasi')
            ->assertJsonPath('data.nik', $this->student->nik);

        $this->postJson('/api/v1/student-mutations', [
            'target_npsn' => $this->target->npsn,
            'nik' => $this->student->nik,
        ])->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.student.nik', $this->student->nik);
    }

    public function test_external_pull_rejects_nik_still_in_origin_trash(): void
    {
        $this->student->delete();

        Sanctum::actingAs($this->targetAdmin);

        $this->postJson('/api/v1/student-mutations/pull', [
            'external' => true,
            'origin_npsn' => '33333333',
            'origin_school_name' => 'SMP Luar',
            'student_name' => 'Siswa Baru',
            'nik' => $this->student->nik,
            'student_gender' => 'L',
        ])->assertStatus(422);
    }
}
