<?php

namespace Tests\Feature;

use App\Support\InstitutionContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MultiInstitutionTeacherSetup;
use Tests\TestCase;

class TeacherMenuContextSwitchTest extends TestCase
{
    use MultiInstitutionTeacherSetup;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMultiInstitutionTeacher();
        $this->seedExtracurricularAndLab();
    }

    public function test_me_payload_lists_homeroom_and_roles_for_home_institution(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->teacherUser))
            ->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.active_institution_id', $this->homeInstitution->id);

        $homeroomNames = collect($response->json('user.homeroom_classes'))->pluck('name')->all();
        $this->assertContains('VII-A Induk', $homeroomNames);
        $this->assertNotContains('VII-B Tamu', $homeroomNames);

        $ekskulNames = collect($response->json('user.supervised_extracurriculars'))->pluck('name')->all();
        $this->assertContains('Pramuka Induk', $ekskulNames);
        $this->assertNotContains('Basket Tamu', $ekskulNames);

        $labNames = collect($response->json('user.managed_labs'))->pluck('name')->all();
        $this->assertContains('Lab IPA Induk', $labNames);
        $this->assertNotContains('Lab Komputer Tamu', $labNames);
    }

    public function test_me_payload_after_switch_shows_guest_institution_menu_context(): void
    {
        $headers = $this->authHeaders($this->teacherUser);

        $this->withHeaders($headers)
            ->postJson('/api/v1/me/switch-institution', [
                'institution_id' => $this->guestInstitution->id,
            ])
            ->assertStatus(200);

        $response = $this->withHeaders($headers)
            ->withCookie(InstitutionContext::COOKIE_ACTIVE_INSTITUTION, (string) $this->guestInstitution->id)
            ->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.active_institution_id', $this->guestInstitution->id)
            ->assertJsonPath('user.active_affiliation', 'non_induk');

        $homeroomNames = collect($response->json('user.homeroom_classes'))->pluck('name')->all();
        $this->assertContains('VII-B Tamu', $homeroomNames);
        $this->assertNotContains('VII-A Induk', $homeroomNames);

        $ekskulNames = collect($response->json('user.supervised_extracurriculars'))->pluck('name')->all();
        $this->assertContains('Basket Tamu', $ekskulNames);
        $this->assertNotContains('Pramuka Induk', $ekskulNames);

        $labNames = collect($response->json('user.managed_labs'))->pluck('name')->all();
        $this->assertContains('Lab Komputer Tamu', $labNames);
        $this->assertNotContains('Lab IPA Induk', $labNames);

        $this->assertTrue($response->json('user.is_extracurricular_supervisor'));
        $this->assertTrue($response->json('user.is_lab_responsible'));
    }
}
