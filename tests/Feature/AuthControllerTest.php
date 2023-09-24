<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\URL;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_registration()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified_at' => now(),
            'password' => 'A64616461a+',
            'password_confirmation' => 'A64616461a+',
        ];

        $response = $this->json('POST', 'api/v1/auth/signup', $userData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_user_login()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('A64616461a+'),
        ]);

        $userData = [
            'email' => 'john@example.com',
            'password' => 'A64616461a+',
        ];

        $response = $this->json('POST', 'api/v1/auth/signin', $userData);

        $response->assertStatus(200);

        $this->assertAuthenticated();
    }

    public function test_email_verification()
    {
        $user = User::factory()->create();
        $verificationUrl = $this->generate_email_verificationUrl($user);

        $response = $this->get($verificationUrl);

        $response->assertStatus(200);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_user_logout()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('POST', 'api/v1/auth/logout');

        $response->assertStatus(200);

        $this->assertGuest();
    }

    protected function generate_email_verificationUrl($user)
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]
        );

        return $verificationUrl;
    }
}