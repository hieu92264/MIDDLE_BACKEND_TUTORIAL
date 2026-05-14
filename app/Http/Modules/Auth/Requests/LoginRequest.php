<?php

namespace App\Http\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => 'required|string|min:6',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $credentials = $this->only('username', 'password');

            $exists = DB::table('users')
                ->where('username', $credentials['username'])
                ->orWhere('email', $credentials['username'])
                ->exists();

            if (! $exists) {
                $validator->errors()->add(
                    'login',
                    'Username hoac email khong ton tai'
                );
            }
        });
    }
}
