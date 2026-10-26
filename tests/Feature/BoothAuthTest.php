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
        $this->get('/bilik')->assertRedirect('/login'); // alias -> tampil form /masuk
        $this->getJson('/api/voters/'.(string) \Illuminate\Support\Str::uuid())->assertUnauthorized();
    }

    public function test_operatorscan_login_reaches_scan(): void
    {
        User::factory()->create(['username' => 'operatorscan', 'password' => 'operatorscan123']);
        $this->post('/masuk', ['username' => 'operatorscan', 'password' => 'operatorscan123'])
            ->assertRedirect('/bilik');
        $this->assertAuthenticated();
        $this->get('/bilik')->assertOk()->assertSee('Bilik Suara', false);
    }

    public function test_wrong_password_fails(): void
    {
        User::factory()->create(['username' => 'operatorscan', 'password' => 'operatorscan123']);
        $this->post('/masuk', ['username' => 'operatorscan', 'password' => 'salah'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_logout_returns_home(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/keluar')->assertRedirect('/');
        $this->assertGuest();
    }
}
