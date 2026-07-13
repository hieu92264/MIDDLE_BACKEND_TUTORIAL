<?php

return [

    'namespace' => env('PROMETHEUS_NAMESPACE', 'laravel'),
    'redis' => [
        'host' => env('PROMETHEUS_REDIS_HOST', '127.0.0.1'),
        'port' => (int) env('PROMETHEUS_REDIS_PORT', 6380),

        'username' => env('PROMETHEUS_REDIS_USERNAME'),
        'password' => env('PROMETHEUS_REDIS_PASSWORD'),

        'prefix' => env(
            'PROMETHEUS_REDIS_PREFIX',
            'laravel_prometheus:'
        ),
    ],
];
