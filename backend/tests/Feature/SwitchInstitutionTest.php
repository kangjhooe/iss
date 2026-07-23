<?php

namespace Tests\Feature;

use App\Models\Correspondence;
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
            ->assertJsonPath('user.active_affiliation', 'non_induk')
            ->assertCookie(InstitutionContext::COOKIE_ACTIVE_INSTITUTION, (string) $this->guestInstitution->id);

        $guestPermissions = $switch->json('user.permissions') ?? [];
        $this->assertNotContains('institution', $guestPermissions);
        $this->assertNotContains('report', $guestPermissions);
        $this->assertContains('correspondence', $guestPermissions);
        $this->assertContains('grade_book', $guestPermissions);

        $cookie = $switch->headers->getCookies()[0] ?? null;
        $this->assertNotNull($cookie);

        $followUp = $this->withHeaders($headers)
            ->withCookie(InstitutionContext::COOKIE_ACTIVE_INSTITUTION, (string) $this->guestInstitution->id)
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

    public function test_correspondence_list_scoped_to_active_institution_after_switch(): void
    {
        Correspondence::create([
            'institution_id' => $this->homeInstitution->id,
            'type' => 'keluar',
            'subject' => 'Surat Induk',
            'letter_number' => '001/INDUK/2026',
            'date' => now()->toDateString(),
            'status' => 'draft',
            'created_by' => $this->teacherUser->id,
        ]);

        Correspondence::create([
            'institution_id' => $this->guestInstitution->id,
            'type' => 'keluar',
            'subject' => 'Surat Tamu',
            'letter_number' => '001/TAMU/2026',
            'date' => now()->toDateString(),
            'status' => 'draft',
            'created_by' => $this->teacherUser->id,
        ]);

        $headers = $this->authHeaders($this->teacherUser);

        $this->withHeaders($headers)
            ->postJson('/api/v1/me/switch-institution', [
                'institution_id' => $this->guestInstitution->id,
            ])
            ->assertStatus(200);

        $list = $this->withHeaders($headers)
            ->withHeader('X-Institution-Id', (string) $this->guestInstitution->id)
            ->getJson('/api/v1/correspondence?per_page=50');

        $list->assertStatus(200);
        $subjects = collect($list->json('data'))->pluck('subject')->all();
        $this->assertContains('Surat Tamu', $subjects);
        $this->assertNotContains('Surat Induk', $subjects);
    }
}
