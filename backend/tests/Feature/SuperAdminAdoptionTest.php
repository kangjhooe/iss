<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperAdminAdoptionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $schoolAdmin;

    protected Institution $activeSchool;

    protected Institution $atRiskSchool;

    protected Institution $churnedSchool;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeSchool = Institution::create([
            'name' => 'SMP Aktif',
            'npsn' => '11111111',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->atRiskSchool = Institution::create([
            'name' => 'SMA Risiko',
            'npsn' => '22222222',
            'level' => 'SMA',
            'is_active' => true,
        ]);
        $this->atRiskSchool->forceFill([
            'created_at' => now()->subMonths(3),
            'updated_at' => now()->subMonths(3),
        ])->saveQuietly();

        $this->churnedSchool = Institution::create([
            'name' => 'SD Dibekukan',
            'npsn' => '33333333',
            'level' => 'SD',
            'is_active' => false,
        ]);
        $this->churnedSchool->forceFill([
            'updated_at' => now()->subDays(5),
        ])->saveQuietly();

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super-adoption@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super_admin',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->schoolAdmin = User::create([
            'name' => 'Admin Sekolah',
            'email' => 'admin-adoption@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->activeSchool->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $studentPerm = Permission::firstOrCreate(
            ['key' => 'student'],
            ['label' => 'Data Siswa']
        );
        Permission::firstOrCreate(
            ['key' => 'attendance'],
            ['label' => 'Absensi']
        );

        $teacher = User::create([
            'name' => 'Guru Aktif',
            'email' => 'guru-adoption@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
            'institution_id' => $this->activeSchool->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $teacher->permissions()->sync([$studentPerm->id]);

        // Drop auto-audits from Institution::create so fixtures control activity windows
        AuditLog::query()->delete();

        $student = Student::create([
            'institution_id' => $this->activeSchool->id,
            'nis' => '1001',
            'nisn' => '0010010010',
            'name' => 'Siswa Satu',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);

        // Keep only intentional activity windows for assertions
        AuditLog::query()->delete();

        $this->insertAudit(
            $this->activeSchool->id,
            $teacher->id,
            Student::class,
            $student->id,
            now()->subDays(2)
        );
        $this->insertAudit(
            $this->activeSchool->id,
            $teacher->id,
            Student::class,
            $student->id,
            now()->subDay()
        );

        // Previous period activity at at-risk school (decline)
        foreach ([45, 40, 35] as $daysAgo) {
            $this->insertAudit(
                $this->atRiskSchool->id,
                null,
                Student::class,
                $student->id,
                now()->subDays($daysAgo)
            );
        }
    }

    private function insertAudit(
        int $institutionId,
        ?int $userId,
        string $type,
        int $auditableId,
        $at
    ): void {
        $log = new AuditLog([
            'user_id' => $userId,
            'institution_id' => $institutionId,
            'action' => 'updated',
            'auditable_type' => $type,
            'auditable_id' => $auditableId,
        ]);
        $log->created_at = $at;
        $log->updated_at = $at;
        $log->save();
    }

    public function test_non_super_admin_cannot_access_adoption(): void
    {
        Sanctum::actingAs($this->schoolAdmin);

        $this->getJson('/api/v1/super-admin/adoption')
            ->assertStatus(403);
    }

    public function test_super_admin_gets_adoption_usage_and_churn(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->getJson('/api/v1/super-admin/adoption?inactive_days=30&usage_days=30');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'summary' => [
                        'institutions',
                        'active_institutions',
                        'inactive_activity_count',
                        'at_risk_count',
                        'churned_count',
                        'events_period',
                        'active_institutions_period',
                        'usage_days',
                    ],
                    'modules' => [
                        '*' => [
                            'key',
                            'label',
                            'institutions_count',
                            'adoption_pct',
                            'usage_institutions_count',
                            'usage_pct',
                            'events_count',
                            'has_usage_tracking',
                        ],
                    ],
                    'usage' => [
                        'days',
                        'trend',
                        'by_module',
                        'totals' => [
                            'events',
                            'active_institutions',
                            'avg_daily_events',
                            'avg_daily_institutions',
                        ],
                    ],
                    'churn' => [
                        'churn_rate_pct',
                        'recently_churned_count',
                        'new_institutions_count',
                        'net_institutions',
                        'at_risk_count',
                        'risk_breakdown',
                        'at_risk',
                        'declining',
                    ],
                    'institutions',
                ],
            ]);

        $data = $response->json('data');

        $this->assertSame(3, $data['summary']['institutions']);
        $this->assertSame(2, $data['summary']['active_institutions']);
        $this->assertSame(1, $data['summary']['churned_count']);
        $this->assertGreaterThanOrEqual(2, $data['summary']['events_period']);
        $this->assertCount(30, $data['usage']['trend']);

        $studentModule = collect($data['modules'])->firstWhere('key', 'student');
        $this->assertNotNull($studentModule);
        $this->assertSame(1, $studentModule['institutions_count']);
        $this->assertGreaterThanOrEqual(1, $studentModule['usage_institutions_count']);
        $this->assertTrue($studentModule['has_usage_tracking']);

        $active = collect($data['institutions'])->firstWhere('id', $this->activeSchool->id);
        $this->assertSame('low', $active['churn_risk']);
        $this->assertGreaterThanOrEqual(2, $active['events_current']);

        $atRisk = collect($data['institutions'])->firstWhere('id', $this->atRiskSchool->id);
        $this->assertContains($atRisk['churn_risk'], ['high', 'medium']);
        $this->assertTrue($atRisk['is_inactive']);

        $churned = collect($data['institutions'])->firstWhere('id', $this->churnedSchool->id);
        $this->assertSame('churned', $churned['churn_risk']);
    }
}
