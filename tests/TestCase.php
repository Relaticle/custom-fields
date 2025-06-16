<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Relaticle\CustomFields\CustomFieldsServiceProvider;
use Relaticle\CustomFields\Tests\Factories\UserFactory;
use Relaticle\CustomFields\Tests\Models\User;
use Spatie\LaravelData\LaravelDataServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => match ($modelName) {
                User::class => UserFactory::class,
                default => 'Relaticle\\CustomFields\\Database\\Factories\\'.class_basename($modelName).'Factory'
            }
        );

        // Start session for Livewire tests
        $this->startSession();

        $this->setUpFilament();
    }

    protected function setUpFilament(): void
    {
        // In Filament V4, panels are configured through PanelProviders
        // The TestPanelProvider will be automatically registered via getPackageProviders()

        // Optionally set the current panel if testing multiple panels
        // Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    protected function getPackageProviders($app): array
    {
        return [
            // Spatie Laravel Data Service Provider
            LaravelDataServiceProvider::class,
//
            CustomFieldsServiceProvider::class,
            LivewireServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
//
//            // Filament Core Service Providers
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,

            // Test Panel Provider
            TestPanelProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        // Database configuration
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Authentication configuration for testing
        config()->set('auth.providers.users.model', User::class);

        // Custom fields configuration
        config()->set('custom-fields.table_names.custom_field_sections', 'custom_field_sections');
        config()->set('custom-fields.table_names.custom_fields', 'custom_fields');
        config()->set('custom-fields.table_names.custom_field_values', 'custom_field_values');
        config()->set('custom-fields.table_names.custom_field_options', 'custom_field_options');

        // Filament configuration
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        
        // Fix Spatie Laravel Data configuration for testing
        config()->set('data.throw_when_max_depth_reached', false);
        config()->set('data.max_transformation_depth', null);
        config()->set('data.validation_strategy', 'only_requests');

    }

    protected function defineDatabaseMigrations(): void
    {
        // Load package migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load test migrations (like users table)
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    protected function createTestModelTable(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('test_models', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }
}
