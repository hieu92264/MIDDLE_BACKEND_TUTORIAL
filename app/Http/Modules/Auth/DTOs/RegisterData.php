<?php

namespace App\Http\Modules\Auth\DTOs;

use App\Http\Modules\Auth\Requests\RegisterRequest;

readonly class RegisterData
{
    public function __construct(
        public string $username,
        public string $email,
        public string $password,
    ) {
    }

    public static function fromRequest(RegisterRequest $request): self
    {
        return new self(
            username: $request->username,
            email: $request->email,
            password: $request->password,
        );
    }
}
