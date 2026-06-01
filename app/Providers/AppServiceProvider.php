<?php

namespace App\Providers;

use App\Http\Modules\Auth\Interfaces\AuthServiceInterface;
use App\Http\Modules\Auth\Services\AuthService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //register service
        $this->app->singleton(AuthServiceInterface::class, AuthService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blueprint::macro('baseMetadataColumns', function (): void {
            /** @var Blueprint $this */
            $this->boolean('is_active')->default(true);
            $this->unsignedBigInteger('user_name_created')->nullable();
            $this->unsignedBigInteger('user_name_updated')->nullable();
        });
    }
}
