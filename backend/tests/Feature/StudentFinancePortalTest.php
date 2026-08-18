<?php

namespace Tests\Feature;

use App\Models\FinanceFeeType;
use App\Models\FinanceInvoice;
use App\Models\FinancePayment;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentFinancePortalTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected Student $student;

    protected Student $otherStudent;

    protected User $studentUser;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMP Portal Keuangan',
            'npsn' => '60606060',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Keuangan',
            'email' => 'admin-student-finance@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $nik = '3201010101010001';
        $this->student = Student::create([
            'institution_id' => $this->institution->id,
            'nis' => '2001',
            'nik' => $nik,
            'name' => 'Siswa Portal',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);

        $this->otherStudent = Student::create([
            'institution_id' => $this->institution->id,
            'nis' => '2002',
            'nik' => '3201010101010002',
            'name' => 'Siswa Lain',
            'gender' => 'P',
            'status' => 'Aktif',
        ]);

        $this->studentUser = User::create([
            'name' => 'Siswa Portal',
            'email' => 'siswa-portal-finance@example.com',
            'password' => Hash::make('01012010'),
            'role' => 'student',
            'institution_id' => $this->institution->id,
            'login_nik' => $nik,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    protected function seedInvoiceAndPayment(): array
    {
        $fee = FinanceFeeType::create([
            'institution_id' => $this->institution->id,
            'name' => 'SPP',
            'code' => 'SPP',
            'frequency' => 'monthly',
            'scope' => 'school',
            'default_amount' => 150000,
            'is_active' => true,
        ]);

        $ownInvoice = FinanceInvoice::create([
            'institution_id' => $this->institution->id,
            'fee_type_id' => $fee->id,
            'student_id' => $this->student->id,
            'title' => 'SPP Juli 2026',
            'period_label' => '2026-07',
            'amount' => 150000,
            'amount_paid' => 50000,
            'status' => 'partial',
            'created_by' => $this->admin->id,
        ]);

        $otherInvoice = FinanceInvoice::create([
            'institution_id' => $this->institution->id,
            'fee_type_id' => $fee->id,
            'student_id' => $this->otherStudent->id,
            'title' => 'SPP Juli 2026',
            'period_label' => '2026-07',
            'amount' => 150000,
            'amount_paid' => 0,
            'status' => 'unpaid',
            'created_by' => $this->admin->id,
        ]);

        $ownPayment = FinancePayment::create([
            'institution_id' => $this->institution->id,
            'invoice_id' => $ownInvoice->id,
            'amount' => 50000,
            'paid_at' => now(),
            'method' => 'cash',
            'recorded_by' => $this->admin->id,
        ]);

        $otherPayment = FinancePayment::create([
            'institution_id' => $this->institution->id,
            'invoice_id' => $otherInvoice->id,
            'amount' => 10000,
            'paid_at' => now(),
            'method' => 'transfer',
            'recorded_by' => $this->admin->id,
        ]);

        return compact('ownInvoice', 'otherInvoice', 'ownPayment', 'otherPayment');
    }

    public function test_student_can_view_own_finance_summary_invoices_and_payments(): void
    {
        $this->seedInvoiceAndPayment();
        Sanctum::actingAs($this->studentUser);

        $summary = $this->getJson('/api/v1/finance/my/summary');
        $summary->assertOk()
            ->assertJsonPath('data.billed', 150000)
            ->assertJsonPath('data.paid', 50000)
            ->assertJsonPath('data.outstanding', 100000)
            ->assertJsonPath('data.counts.outstanding', 1);

        $invoices = $this->getJson('/api/v1/finance/my/invoices');
        $invoices->assertOk();
        $ids = collect($invoices->json('data'))->pluck('id')->all();
        $this->assertCount(1, $ids);
        $this->assertEquals($this->student->id, $invoices->json('data.0.student_id'));

        $payments = $this->getJson('/api/v1/finance/my/payments');
        $payments->assertOk();
        $this->assertCount(1, $payments->json('data'));
        $this->assertEquals(50000, $payments->json('data.0.amount'));
    }

    public function test_student_cannot_open_other_student_receipt(): void
    {
        $seeded = $this->seedInvoiceAndPayment();
        Sanctum::actingAs($this->studentUser);

        $this->get('/api/v1/finance/my/payments/' . $seeded['otherPayment']->id . '/receipt')
            ->assertForbidden();
    }

    public function test_non_student_cannot_access_student_finance_endpoints(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/finance/my/summary')->assertForbidden();
        $this->getJson('/api/v1/finance/my/invoices')->assertForbidden();
        $this->getJson('/api/v1/finance/my/payments')->assertForbidden();
    }

    public function test_student_without_module_finance_can_still_access_own_endpoints(): void
    {
        $this->seedInvoiceAndPayment();
        Sanctum::actingAs($this->studentUser);

        // Students do not have module:finance; portal routes must remain reachable.
        $this->getJson('/api/v1/finance/invoices')->assertForbidden();
        $this->getJson('/api/v1/finance/my/invoices')->assertOk();
    }
}
