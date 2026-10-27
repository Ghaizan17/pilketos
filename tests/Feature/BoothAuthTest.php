<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoothAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_home_with_candidates_and_login_button(): void
    {
        Candidate::create(['number' => 1, 'ketua_name' => 'A', 'wakil_name' => 'B']);
        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('PILKETOS');
        $response->assertSee('Login Operator Bilik');
        $response->assertSee('No. 1');
    }

    public function test_guest_cannot_access_scan_or_api(): void
    {
        $this->get('/bilik')->assertRedirect('/login');
        $this->getJson('/api/voters/'.(string) \Illuminate\Support\Str::uuid())->assertUnauthorized();
    }

    public function test_operatorscan_login_reaches_scan(): void
    {
        User::factory()->create(['username' => 'operatorscan', 'password' => 'operatorscan123']);
        $this->post('/login', ['username' => 'operatorscan', 'password' => 'operatorscan123'])
            ->assertRedirect('/bilik');
        $this->assertAuthenticated();
        $this->get('/bilik')->assertOk()->assertSee('Bilik Suara', false);
    }

    public function test_wrong_password_fails(): void
    {
        User::factory()->create(['username' => 'operatorscan', 'password' => 'operatorscan123']);
        $this->post('/login', ['username' => 'operatorscan', 'password' => 'salah'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }
    public function test_logout_returns_home(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/keluar')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_scanner_reaches_scan_but_not_admin_or_qr_pdf(): void
    {
        User::factory()->create([
            'username' => 'scanner1',
            'password' => 'scanner123',
            'role' => User::ROLE_SCANNER,
        ]);
        $this->post('/login', ['username' => 'scanner1', 'password' => 'scanner123'])
            ->assertRedirect('/bilik');
        $this->get('/bilik')->assertOk();
        // Scanner ditolak panel admin.
        $this->get('/admin')->assertForbidden();
        // Scanner ditolak unduhan QR.
        $this->get(route('students.qr-cards.pdf', ['scope' => 'all']))->assertForbidden();
    }

    public function test_operator_reaches_scan_and_qr_pdf(): void
    {
        $classroom = \App\Models\Classroom::create(['name' => 'X RPL A', 'grade' => 'X', 'major' => 'RPL A']);
        \App\Models\Student::create(['name' => 'Siswa A', 'nipd' => 'OP001', 'classroom_id' => $classroom->id]);
        User::factory()->create([
            'username' => 'operator1',
            'password' => 'operator123',
            'role' => User::ROLE_OPERATOR,
        ]);
        $this->post('/login', ['username' => 'operator1', 'password' => 'operator123'])
            ->assertRedirect('/bilik');
        $this->get('/bilik')->assertOk();
        $this->get(route('students.qr-cards.pdf', ['scope' => 'all']))->assertOk();
        $this->get('/admin')->assertOk();
    }
}
