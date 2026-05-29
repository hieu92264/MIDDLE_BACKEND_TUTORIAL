<?php

namespace App\Http\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => __('auth.validation.username.required'),
            'username.string' => __('auth.validation.username.string'),
            'password.required' => __('auth.validation.password.required'),
            'password.string' => __('auth.validation.password.string'),
            'password.min' => __('auth.validation.password.min'),
        ];
    }

    public function attributes(): array
    {
        $attributes = trans('auth.attributes');

        return is_array($attributes) ? $attributes : [];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->count() > 0) {
                return;
            }

            $credentials = $this->only('username', 'password');

            $exists = DB::table('users')
                ->where('username', $credentials['username'] ?? null)
                ->orWhere('email', $credentials['username'] ?? null)
                ->exists();

            if (! $exists) {
                $validator->errors()->add('login', __('auth.errors.account_not_found'));
            }
        });
    }
}
