<?php

namespace App\Http\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Modules\Auth\DTOs\LoginData;
use App\Http\Modules\Auth\DTOs\RegisterData;
use App\Http\Modules\Auth\Interfaces\AuthServiceInterface;
use App\Http\Modules\Auth\Requests\LoginRequest;
use App\Http\Modules\Auth\Requests\RegisterRequest;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(protected readonly AuthServiceInterface $authService)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = RegisterData::fromRequest($request);

        return $this->success(
            metadata: $this->authService->register($data),
            message: 'Dang ky thanh cong',
            statusCode: 201
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = LoginData::fromRequest($request);

        return $this->success(
            metadata: $this->authService->login($data),
            message: 'Dang nhap thanh cong'
        );
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return $this->success(message: 'Dang xuat thanh cong');
    }

    public function me(): JsonResponse
    {
        return $this->success(metadata: $this->authService->me());
    }

    public function refresh(): JsonResponse
    {
        return $this->success(
            metadata: $this->authService->refresh(),
            message: 'Lam moi token thanh cong'
        );
    }
}
