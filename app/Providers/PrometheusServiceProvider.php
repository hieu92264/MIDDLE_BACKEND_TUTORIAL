<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Predis as PrometheusPredis;

class PrometheusServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CollectorRegistry::class, function () {

            $storage = new PrometheusPredis(
                parameters: [
                    'scheme' => 'tcp',
                    'host' => config('prometheus.redis.host'),
                    'port' => config('prometheus.redis.port'),
                    'username' => config('prometheus.redis.username'),
                    'password' => config('prometheus.redis.password'),
                ],
                options: [
                    'prefix' => config('prometheus.redis.prefix'),
                ],
            );

            return new CollectorRegistry($storage);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
