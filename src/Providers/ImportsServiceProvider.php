<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Providers;

use Illuminate\Support\ServiceProvider;
use Override;
use Psr\Log\LoggerInterface;
use Relaticle\CustomFields\Filament\Integration\Actions\Imports\ColumnConfigurators\BasicColumnConfigurator;
use Relaticle\CustomFields\Filament\Integration\Actions\Imports\ColumnConfigurators\MultiSelectColumnConfigurator;
use Relaticle\CustomFields\Filament\Integration\Actions\Imports\ColumnConfigurators\SelectColumnConfigurator;
use Relaticle\CustomFields\Filament\Integration\Actions\Imports\CustomFieldsImporter;
use Relaticle\CustomFields\Filament\Integration\Actions\Imports\Matchers\LookupMatcher;
use Relaticle\CustomFields\Filament\Integration\Actions\Imports\Matchers\LookupMatcherInterface;
use Relaticle\CustomFields\Filament\Integration\Actions\Imports\ValueConverters\ValueConverter;
use Relaticle\CustomFields\Filament\Integration\Actions\Imports\ValueConverters\ValueConverterInterface;
use Relaticle\CustomFields\Filament\Integration\Factories\ColumnFactory;

/**
 * Service provider for custom fields import functionality.
 */
class ImportsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        // Register implementations
        $this->app->singleton(LookupMatcherInterface::class, LookupMatcher::class);
        $this->app->singleton(ValueConverterInterface::class, ValueConverter::class);

        // Register column configurators
        $this->app->singleton(BasicColumnConfigurator::class);
        $this->app->singleton(SelectColumnConfigurator::class);
        $this->app->singleton(MultiSelectColumnConfigurator::class);

        // Register column factory
        $this->app->singleton(ColumnFactory::class);

        // Register the importer
        $this->app->singleton(CustomFieldsImporter::class, fn ($app): CustomFieldsImporter => new CustomFieldsImporter(
            $app->make(ColumnFactory::class),
            $app->make(ValueConverterInterface::class),
            $app->make(LoggerInterface::class)
        ));
    }
}
