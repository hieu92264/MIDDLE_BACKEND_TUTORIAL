<?php

namespace App\Http\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => __('auth.validation.username.required'),
            'username.string' => __('auth.validation.username.string'),
            'username.max' => __('auth.validation.username.max'),
            'username.unique' => __('auth.validation.username.unique'),
            'email.required' => __('auth.validation.email.required'),
            'email.string' => __('auth.validation.email.string'),
            'email.email' => __('auth.validation.email.email'),
            'email.max' => __('auth.validation.email.max'),
            'email.unique' => __('auth.validation.email.unique'),
            'password.required' => __('auth.validation.password.required'),
            'password.string' => __('auth.validation.password.string'),
            'password.min' => __('auth.validation.password.min'),
            'password.confirmed' => __('auth.validation.password.confirmed'),
        ];
    }

    public function attributes(): array
    {
        $attributes = trans('auth.attributes');

        return is_array($attributes) ? $attributes : [];
    }
}
