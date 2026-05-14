<?php

namespace App\Http\Modules\Auth\Interfaces;

use App\Http\Modules\Auth\DTOs\LoginData;
use App\Http\Modules\Auth\DTOs\RegisterData;

interface AuthServiceInterface
{
    public function register(RegisterData $data): array;

    public function login(LoginData $credentials): array;

    public function logout(): array;

    public function me(): array;

    public function refresh(): array;
}
