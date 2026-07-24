<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\InstitutionContext;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class InstitutionContextResolveForUserTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_institution_admin_cannot_resolve_foreign_institution_id(): void
    {
        $emptyRelation = Mockery::mock();
        $emptyRelation->shouldReceive('first')->andReturn(null);

        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('isAdminOrSuperAdmin')->andReturn(false);
        $user->shouldReceive('isSuperAdmin')->andReturn(false);
        $user->shouldReceive('isAdmin')->andReturn(false);
        $user->shouldReceive('isInstitutionAdmin')->andReturn(true);
        $user->shouldReceive('employeeProfile')->andReturn($emptyRelation);
        $user->shouldReceive('teacherProfile')->andReturn($emptyRelation);
        $user->institution_id = 10;
        $user->id = 1;
        $user->role = 'institution_admin';
        $user->email = null;

        $request = Request::create('/api/v1/finance/invoices', 'GET', ['institution_id' => 99]);

        $resolved = InstitutionContext::resolveForUser($user, $request, 99);

        $this->assertSame(10, $resolved);
    }

    public function test_platform_admin_can_resolve_any_institution_id(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('isAdminOrSuperAdmin')->andReturn(true);
        $user->institution_id = 10;
        $user->id = 2;
        $user->role = 'admin';

        $request = Request::create('/api/v1/finance/invoices', 'GET', ['institution_id' => 99]);
        $resolved = InstitutionContext::resolveForUser($user, $request, 99);

        $this->assertSame(99, $resolved);
    }

    public function test_institution_admin_resolves_home_when_no_request_id(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('isAdminOrSuperAdmin')->andReturn(false);
        $user->shouldReceive('isInstitutionAdmin')->andReturn(true);
        $user->institution_id = 10;
        $user->id = 3;
        $user->role = 'institution_admin';
        $user->setRelation('employee', null);

        $request = Request::create('/api/v1/finance/invoices', 'GET');
        $resolved = InstitutionContext::resolveForUser($user, $request, null);

        $this->assertSame(10, $resolved);
    }
}
