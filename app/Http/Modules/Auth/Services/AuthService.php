<?php

namespace App\Http\Modules\Auth\Services;

use App\Http\Modules\Auth\DTOs\LoginData;
use App\Http\Modules\Auth\DTOs\RegisterData;
use App\Http\Modules\Auth\Interfaces\AuthServiceInterface;
use App\Http\Modules\Auth\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService implements AuthServiceInterface
{
    public function register(RegisterData $data): array
    {
        $user = User::query()->create([
            'username' => $data->username,
            'email' => $data->email,
            'password' => $data->password,
            'is_active' => true,
            'role' => 'user',
        ]);

        $token = auth('api')->login($user);

        return $this->buildAuthPayload($token, $user);
    }

    public function login(LoginData $credentials): array
    {
        $user = User::query()
            ->where('username', $credentials->username)
            ->orWhere('email', $credentials->username)
            ->first();

        if (! $user || ! $user->is_active || ! Hash::check($credentials->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Thong tin dang nhap khong chinh xac.'],
            ]);
        }

        $token = auth('api')->login($user);

        return $this->buildAuthPayload($token, $user);
    }

    public function logout(): array
    {
        auth('api')->logout();

        return [];
    }

    public function me(): array
    {
        return [
            'user' => auth('api')->user(),
        ];
    }

    public function refresh(): array
    {
        $guard = auth('api');
        $token = $guard->refresh();
        $user = $guard->setToken($token)->user();

        return $this->buildAuthPayload($token, $user);
    }

    protected function buildAuthPayload(string $token, User $user): array
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => $user,
        ];
    }
}
