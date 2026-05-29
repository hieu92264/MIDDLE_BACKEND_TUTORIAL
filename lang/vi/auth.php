<?php

return [
    'failed' => 'Thông tin đăng nhập không khớp với hệ thống.',
    'password' => 'Mật khẩu cung cấp không chính xác.',
    'throttle' => 'Bạn đăng nhập quá nhiều lần. Vui lòng thử lại sau :seconds giây.',

    'responses' => [
        'register_success' => 'Đăng ký thành công.',
        'login_success' => 'Đăng nhập thành công.',
        'logout_success' => 'Đăng xuất thành công.',
        'refresh_success' => 'Làm mới token thành công.',
    ],

    'errors' => [
        'account_not_found' => 'Username hoặc email không tồn tại.',
        'invalid_credentials' => 'Thông tin đăng nhập không chính xác.',
    ],

    'validation' => [
        'username' => [
            'required' => 'Trường username là bắt buộc.',
            'string' => 'Username phải là chuỗi.',
            'max' => 'Username không được vượt quá :max ký tự.',
            'unique' => 'Username đã tồn tại.',
        ],
        'email' => [
            'required' => 'Trường email là bắt buộc.',
            'string' => 'Email phải là chuỗi.',
            'email' => 'Email không đúng định dạng.',
            'max' => 'Email không được vượt quá :max ký tự.',
            'unique' => 'Email đã tồn tại.',
        ],
        'password' => [
            'required' => 'Trường mật khẩu là bắt buộc.',
            'string' => 'Mật khẩu phải là chuỗi.',
            'min' => 'Mật khẩu phải có ít nhất :min ký tự.',
            'confirmed' => 'Xác nhận mật khẩu không khớp.',
        ],
    ],

    'attributes' => [
        'username' => 'username',
        'email' => 'email',
        'password' => 'mật khẩu',
    ],
];
