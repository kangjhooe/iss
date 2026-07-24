<?php

namespace Tests\Feature;

use App\Models\AcademicCalendarEvent;
use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AcademicCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMP Kalender',
            'npsn' => '60606060',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Kalender',
            'email' => 'admin-calendar@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->year = AcademicYear::create([
            'code' => '2025/2026',
            'name' => 'Tahun 2025/2026',
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->addMonths(10)->toDateString(),
            'status' => 'Aktif',
        ]);
    }

    public function test_admin_can_crud_calendar_event(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/academic-calendar', [
            'academic_year_id' => $this->year->id,
            'title' => 'Libur Semester',
            'description' => 'Libur tengah semester',
            'event_type' => 'Libur',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'is_all_day' => true,
            'color' => '#059669',
            'status' => 'Aktif',
            'reminder_days_before' => [3, 7],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.title', 'Libur Semester')
            ->assertJsonPath('data.event_type', 'Libur');

        $id = $create->json('data.id');
        $this->assertDatabaseHas('academic_calendar_events', [
            'id' => $id,
            'institution_id' => $this->institution->id,
            'title' => 'Libur Semester',
        ]);

        $list = $this->getJson('/api/v1/academic-calendar?search=Libur');
        $list->assertOk();
        $this->assertTrue(
            collect($list->json('data'))->contains(fn ($row) => ($row['id'] ?? null) === $id)
        );

        $update = $this->putJson("/api/v1/academic-calendar/{$id}", [
            'academic_year_id' => $this->year->id,
            'title' => 'Libur Semester (Revisi)',
            'event_type' => 'Libur',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
            'is_all_day' => true,
            'status' => 'Aktif',
        ]);
        $update->assertOk()
            ->assertJsonPath('data.title', 'Libur Semester (Revisi)');

        $delete = $this->deleteJson("/api/v1/academic-calendar/{$id}");
        $delete->assertOk();
        $this->assertSoftDeleted('academic_calendar_events', ['id' => $id]);
    }

    public function test_calendar_and_upcoming_endpoints(): void
    {
        Sanctum::actingAs($this->admin);

        AcademicCalendarEvent::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->year->id,
            'title' => 'Ujian Tengah Semester',
            'event_type' => 'Ujian',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'is_all_day' => true,
            'status' => 'Aktif',
            'created_by' => $this->admin->id,
        ]);

        AcademicCalendarEvent::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->year->id,
            'title' => 'Draft Event',
            'event_type' => 'Kegiatan',
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'is_all_day' => true,
            'status' => 'Draft',
            'created_by' => $this->admin->id,
        ]);

        $start = now()->startOfMonth()->format('Y-m-d');
        $end = now()->endOfMonth()->addMonth()->format('Y-m-d');

        $calendar = $this->getJson("/api/v1/academic-calendar/calendar?start_date={$start}&end_date={$end}");
        $calendar->assertOk();
        $titles = collect($calendar->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('Ujian Tengah Semester'));

        $upcoming = $this->getJson('/api/v1/academic-calendar/upcoming?days=30');
        $upcoming->assertOk();
        $upcomingTitles = collect($upcoming->json('data'))->pluck('title');
        $this->assertTrue($upcomingTitles->contains('Ujian Tengah Semester'));
        $this->assertFalse($upcomingTitles->contains('Draft Event'));
    }

    public function test_teacher_without_permission_forbidden(): void
    {
        $teacher = User::create([
            'name' => 'Guru Tanpa Akses',
            'email' => 'guru-calendar@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        // Ensure permission row exists but not granted
        Permission::firstOrCreate(
            ['key' => 'academic_calendar'],
            ['label' => 'Kalender Akademik']
        );

        Sanctum::actingAs($teacher);

        $this->getJson('/api/v1/academic-calendar')->assertForbidden();
        $this->postJson('/api/v1/academic-calendar', [
            'academic_year_id' => $this->year->id,
            'title' => 'Tidak Boleh',
            'event_type' => 'Kegiatan',
            'start_date' => now()->toDateString(),
            'is_all_day' => true,
        ])->assertForbidden();
    }
}
