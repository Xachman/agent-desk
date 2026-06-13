<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSystemTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_access_login_page()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_access_admin_page_when_logged_in_as_admin()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin');
        $response->assertStatus(200);
    }

    /** @test */
    public function non_admin_cannot_access_admin_page()
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $this->actingAs($user);

        $response = $this->get('/admin');
        $response->assertStatus(403);
    }

    /** @test */
    public function user_cannot_access_admin_page_when_not_logged_in()
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }
}