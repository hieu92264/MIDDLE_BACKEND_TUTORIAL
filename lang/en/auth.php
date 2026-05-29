<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    'responses' => [
        'register_success' => 'Registration successful.',
        'login_success' => 'Login successful.',
        'logout_success' => 'Logout successful.',
        'refresh_success' => 'Token refreshed successfully.',
    ],

    'errors' => [
        'account_not_found' => 'The username or email does not exist.',
        'invalid_credentials' => 'The provided credentials are incorrect.',
    ],

    'validation' => [
        'username' => [
            'required' => 'The username field is required.',
            'string' => 'The username must be a string.',
            'max' => 'The username may not be greater than :max characters.',
            'unique' => 'This username has already been taken.',
        ],
        'email' => [
            'required' => 'The email field is required.',
            'string' => 'The email must be a string.',
            'email' => 'The email field must be a valid email address.',
            'max' => 'The email may not be greater than :max characters.',
            'unique' => 'This email has already been taken.',
        ],
        'password' => [
            'required' => 'The password field is required.',
            'string' => 'The password must be a string.',
            'min' => 'The password field must be at least :min characters.',
            'confirmed' => 'The password confirmation does not match.',
        ],
    ],

    'attributes' => [
        'username' => 'username',
        'email' => 'email address',
        'password' => 'password',
    ],

];
