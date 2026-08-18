<?php

namespace Tests\Feature;

use App\Models\Correspondence;
use App\Models\User;
use App\Support\InstitutionContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MultiInstitutionTeacherSetup;
use Tests\TestCase;

class SwitchInstitutionTest extends TestCase
{
    use MultiInstitutionTeacherSetup;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMultiInstitutionTeacher();
    }

    public function test_switch_institution_sets_cookie_and_strips_elevated_permissions_on_non_induk(): void
    {
        $headers = $this->authHeaders($this->teacherUser);

        $homeMe = $this->withHeaders($headers)->getJson('/api/v1/me');
        $homeMe->assertStatus(200)
            ->assertJsonPath('user.active_institution_id', $this->homeInstitution->id)
            ->assertJsonPath('user.active_affiliation', 'induk');

        $homePermissions = $homeMe->json('user.permissions') ?? [];
        $this->assertContains('institution', $homePermissions);
        $this->assertContains('report', $homePermissions);

        $switch = $this->withHeaders($headers)->postJson('/api/v1/me/switch-institution', [
            'institution_id' => $this->guestInstitution->id,
        ]);

        $switch->assertStatus(200)
            ->assertJsonPath('user.active_institution_id', $this->guestInstitution->id)
            ->assertJsonPath('user.active_affiliation', 'non_induk');

        $guestPermissions = $switch->json('user.permissions') ?? [];
        $this->assertNotContains('institution', $guestPermissions);
        $this->assertNotContains('report', $guestPermissions);
        $this->assertNotContains('correspondence', $guestPermissions);
        $this->assertContains('grade_book', $guestPermissions);

        $hasInstitutionCookie = collect($switch->headers->getCookies())
            ->contains(fn ($cookie) => $cookie->getName() === InstitutionContext::COOKIE_ACTIVE_INSTITUTION);
        $this->assertTrue($hasInstitutionCookie);

        $followUp = $this->withHeaders($headers)
            ->withHeader('X-Institution-Id', (string) $this->guestInstitution->id)
            ->getJson('/api/v1/me');

        $followUp->assertStatus(200)
            ->assertJsonPath('user.active_institution_id', $this->guestInstitution->id)
            ->assertJsonPath('user.active_affiliation', 'non_induk');
    }

    public function test_switch_to_inaccessible_institution_returns_403(): void
    {
        $other = \App\Models\Institution::create([
            'name' => 'Sekolah Lain',
            'npsn' => '33333333',
            'is_active' => true,
        ]);

        $response = $this->withHeaders($this->authHeaders($this->teacherUser))
            ->postJson('/api/v1/me/switch-institution', [
                'institution_id' => $other->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_correspondence_module_forbidden_for_teacher_without_permission(): void
    {
        $headers = $this->authHeaders($this->teacherUser);

        $this->withHeaders($headers)
            ->getJson('/api/v1/correspondence?per_page=50')
            ->assertStatus(403);

        $this->withHeaders($headers)
            ->postJson('/api/v1/me/switch-institution', [
                'institution_id' => $this->guestInstitution->id,
            ])
            ->assertStatus(200);

        $this->withHeaders($headers)
            ->withHeader('X-Institution-Id', (string) $this->guestInstitution->id)
            ->getJson('/api/v1/correspondence?per_page=50')
            ->assertStatus(403);
    }

    public function test_teacher_can_complete_disposition_without_correspondence_module(): void
    {
        $admin = User::create([
            'name' => 'Admin Disposisi',
            'email' => 'admin-disposisi@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('Password123!'),
            'role' => 'admin',
            'institution_id' => $this->homeInstitution->id,
            'email_verified_at' => now(),
        ]);

        $letter = Correspondence::create([
            'institution_id' => $this->homeInstitution->id,
            'type' => 'keluar',
            'letter_type_code' => '16',
            'subject' => 'Surat Disposisi',
            'letter_number' => '010/DSP/2026',
            'date' => now()->toDateString(),
            'status' => 'approved',
            'created_by' => $admin->id,
        ]);

        $disposition = \App\Models\CorrespondenceDisposition::create([
            'correspondence_id' => $letter->id,
            'from_user_id' => $admin->id,
            'to_user_id' => $this->teacherUser->id,
            'instruction' => 'Mohon ditindaklanjuti',
            'status' => 'pending',
        ]);

        $headers = $this->authHeaders($this->teacherUser);

        $pending = $this->withHeaders($headers)->getJson('/api/v1/dispositions/pending');
        $pending->assertStatus(200);
        $ids = collect($pending->json('data'))->pluck('id')->all();
        $this->assertContains($disposition->id, $ids);

        $this->withHeaders($headers)
            ->postJson('/api/v1/correspondence/dispositions/'.$disposition->id.'/complete')
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');
    }
}
