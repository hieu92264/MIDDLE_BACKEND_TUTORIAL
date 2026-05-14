<?php

namespace App\Http\Modules\Auth\DTOs;

use App\Http\Modules\Auth\Requests\LoginRequest;

readonly class LoginData {
    public function __construct(public string $username, public string $password)
    {
    }

    public static function fromRequest(LoginRequest $request): self
    {
        return new self(
            username: $request->username,
            password: $request->password
        );
    }

    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'password' => $this->password
        ];
    }
}

