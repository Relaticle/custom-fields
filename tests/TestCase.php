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
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Override;
use Postare\BladeMdi\BladeMdiServiceProvider;
use Relaticle\CustomFields\CustomFieldsServiceProvider;
use Relaticle\CustomFields\Tests\database\factories\UserFactory;
use Relaticle\CustomFields\Tests\Fixtures\Models\User;
use Relaticle\CustomFields\Tests\Fixtures\Providers\AdminPanelProvider;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;

class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase;
    use WithWorkbench;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => match ($modelName) {
                User::class => UserFactory::class,
                default => 'Relaticle\\CustomFields\\Database\\Factories\\'.class_basename($modelName).'Factory'
            }
        );

        $this->actingAs(User::factory()->create());
    }

    protected function getPackageProviders($app): array
    {
        $providers = [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeMdiServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,

            // Custom service provider for the custom fields package
            LaravelDataServiceProvider::class,

            // Custom service provider for the admin panel
            AdminPanelProvider::class,

            // Custom fields service provider
            CustomFieldsServiceProvider::class,
        ];

        sort($providers);

        return $providers;
    }

    public function defineEnvironment($app): void
    {
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('view.paths', [
            ...$app['config']->get('view.paths'),
            __DIR__.'/../resources/views',
        ]);

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
        $this->app['db']->connection()->getSchemaBuilder()->create('test_models', function ($table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }
}
