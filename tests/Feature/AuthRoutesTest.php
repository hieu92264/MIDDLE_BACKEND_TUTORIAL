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
            'app.locale' => 'vi',
            'app.fallback_locale' => 'en',
            'app.supported_locales' => ['en', 'vi'],
            'jwt.secret' => '1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef',
            'jwt.ttl' => 60,
            'jwt.refresh_ttl' => 20160,
        ]);

        $this->apiPrefix = '/'.trim((string) config('app.api_prefix'), '/');
    }

    public function test_register_route_creates_user_and_returns_vietnamese_response_by_default(): void
    {
        $response = $this->postJson($this->apiPrefix.'/auth/register', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response
            ->assertCreated()
            ->assertHeader('Content-Language', 'vi')
            ->assertJsonPath('message', 'Đăng ký thành công.')
            ->assertJsonPath('metadata.user.username', 'newuser');

        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
        ]);
    }

    public function test_auth_routes_can_return_english_module_and_global_messages(): void
    {
        $response = $this->withHeader('X-Locale', 'en')
            ->postJson($this->apiPrefix.'/auth/login', [
                'username' => 'missing@example.com',
                'password' => 'secret123',
            ]);

        $response
            ->assertUnprocessable()
            ->assertHeader('Content-Language', 'en')
            ->assertJsonPath('message', 'The submitted data is invalid.')
            ->assertJsonPath('metadata.login.0', 'The username or email does not exist.');
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
            ->assertHeader('Content-Language', 'vi')
            ->assertJsonPath('message', 'Đăng nhập thành công.')
            ->assertJsonPath('metadata.user.username', 'tester');

        $token = $loginResponse->json('metadata.access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson($this->apiPrefix.'/auth/me')
            ->assertOk()
            ->assertHeader('Content-Language', 'vi')
            ->assertJsonPath('message', 'Thành công.')
            ->assertJsonPath('metadata.user.email', 'tester@example.com');

        $refreshResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson($this->apiPrefix.'/auth/refresh')
            ->assertOk()
            ->assertHeader('Content-Language', 'vi')
            ->assertJsonPath('message', 'Làm mới token thành công.');

        $refreshedToken = $refreshResponse->json('metadata.access_token');

        $this->withHeader('Authorization', 'Bearer '.$refreshedToken)
            ->postJson($this->apiPrefix.'/auth/logout')
            ->assertOk()
            ->assertHeader('Content-Language', 'vi')
            ->assertJsonPath('message', 'Đăng xuất thành công.');
    }

    public function test_api_not_found_response_uses_requested_locale(): void
    {
        $this->withHeader('X-Locale', 'en')
            ->getJson($this->apiPrefix.'/missing-route')
            ->assertNotFound()
            ->assertHeader('Content-Language', 'en')
            ->assertJsonPath('message', 'Route or resource not found.');
    }
}
