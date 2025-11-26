<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_requires_name()
    {
        $response = $this->postJson(route('v1.auth.register'), [
            'email' => 'user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    public function test_register_requires_email()
    {
        $response = $this->postJson(route('v1.auth.register'), [
            'name' => 'John Doe',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_register_requires_valid_email()
    {
        $response = $this->postJson(route('v1.auth.register'), [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_register_requires_password_confirmation()
    {
        $response = $this->postJson(route('v1.auth.register'), [
            'name' => 'John Doe',
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');
    }

    public function test_register_success()
    {
        $response = $this->postJson(route('v1.auth.register'), [
            'name' => 'John Doe',
            'email' => 'user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'user' => [
                    'id', 'name', 'email', 'created_at', 'updated_at'
                ],
                'token',
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
        ]);
    }

    public function test_register_requires_unique_email()
    {
        User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->postJson(route('v1.auth.register'), [
            'name' => 'Jane Doe',
            'email' => 'user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_register_requires_minimum_password_length()
    {
        $response = $this->postJson(route('v1.auth.register'), [
            'name' => 'John Doe',
            'email' => 'user@example.com',
            'password' => 'pass',
            'password_confirmation' => 'pass',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');
    }

    public function test_login_success()
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson(route('v1.auth.login'), [
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'user' => [
                    'id', 'name', 'email', 'created_at', 'updated_at'
                ],
                'token',
            ],
        ]);
    }

    public function test_login_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson(route('v1.auth.login'), [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'The provided credentials are incorrect.']);
    }

    public function test_profile_authenticated_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson(route('v1.auth.profile'));

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function test_profile_unauthenticated_user()
    {
        $response = $this->getJson(route('v1.auth.profile'));

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }
}
