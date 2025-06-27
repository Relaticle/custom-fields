<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Providers;

use Override;
use Illuminate\Support\ServiceProvider;
use Relaticle\CustomFields\Services\ValidationService;

class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    #[Override]
    public function register(): void
    {
        $this->app->singleton(ValidationService::class, fn($app): ValidationService => new ValidationService);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // No boot functionality needed
    }
}
