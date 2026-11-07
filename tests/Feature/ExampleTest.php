<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guest_is_redirected_to_login_and_authenticated_user_sees_dashboard(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertStatus(200);

        $user = User::firstOrFail();
        $this->actingAs($user)->get('/')->assertStatus(200);
    }
}
