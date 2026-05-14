<?php

namespace Tests\Feature;

use App\Http\Modules\Auth\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class AuthRoutesTest extends TestCase
{
    use DatabaseMigrations;

    protected string $apiPrefix;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'jwt.secret' => '1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef',
            'jwt.ttl' => 60,
            'jwt.refresh_ttl' => 20160,
        ]);
        $this->apiPrefix = '/'.trim((string) config('app.api_prefix'), '/');
    }

    public function test_register_route_creates_user_and_returns_token(): void
    {
        $response = $this->postJson($this->apiPrefix.'/auth/register', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Dang ky thanh cong')
            ->assertJsonPath('metadata.user.username', 'newuser');

        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
        ]);
    }

    public function test_login_me_refresh_and_logout_routes_work_with_jwt_guard(): void
    {
        User::query()->create([
            'username' => 'tester',
            'email' => 'tester@example.com',
            'password' => 'secret123',
            'is_active' => true,
            'role' => 'user',
        ]);

        $loginResponse = $this->postJson($this->apiPrefix.'/auth/login', [
            'username' => 'tester@example.com',
            'password' => 'secret123',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('message', 'Dang nhap thanh cong')
            ->assertJsonPath('metadata.user.username', 'tester');

        $token = $loginResponse->json('metadata.access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson($this->apiPrefix.'/auth/me')
            ->assertOk()
            ->assertJsonPath('metadata.user.email', 'tester@example.com');

        $refreshResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson($this->apiPrefix.'/auth/refresh')
            ->assertOk()
            ->assertJsonPath('message', 'Lam moi token thanh cong');

        $refreshedToken = $refreshResponse->json('metadata.access_token');

        $this->withHeader('Authorization', 'Bearer '.$refreshedToken)
            ->postJson($this->apiPrefix.'/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Dang xuat thanh cong');
    }
}
