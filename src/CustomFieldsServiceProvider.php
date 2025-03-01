<?php

declare(strict_types=1);

namespace Relaticle\CustomFields;

use Filament\Facades\Filament;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Relaticle\CustomFields\Commands\FilamentCustomFieldCommand;
use Relaticle\CustomFields\Commands\UpgradeCommand;
use Relaticle\CustomFields\Contracts\CustomsFieldsMigrators;
use Relaticle\CustomFields\Contracts\ValueResolvers;
use Relaticle\CustomFields\Livewire\ManageCustomField;
use Relaticle\CustomFields\Livewire\ManageCustomFieldSection;
use Relaticle\CustomFields\Livewire\ManageCustomFieldWidth;
use Relaticle\CustomFields\Migrations\CustomFieldsMigrator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Services\ValueResolver\ValueResolver;
use Relaticle\CustomFields\Support\Utils;
use Relaticle\CustomFields\Testing\TestsFilamentCustomField;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CustomFieldsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'custom-fields';

    public static string $viewNamespace = 'custom-fields';

    public function bootingPackage(): void
    {
        $this->app->singleton(CustomsFieldsMigrators::class, CustomFieldsMigrator::class);
        $this->app->singleton(ValueResolvers::class, ValueResolver::class);

        if (Utils::isTenantEnabled()) {
            foreach (Filament::getPanels() as $panel) {
                if ($tenantModel = $panel->getTenantModel()) {
                    $tenantModelInstance = app($tenantModel);

                    CustomFieldSection::resolveRelationUsing('team', function (CustomField $customField) use ($tenantModel) {
                        return $customField->belongsTo($tenantModel, config('custom-fields.column_names.tenant_foreign_key'));
                    });

                    CustomField::resolveRelationUsing('team', function (CustomField $customField) use ($tenantModel) {
                        return $customField->belongsTo($tenantModel, config('custom-fields.column_names.tenant_foreign_key'));
                    });

                    $tenantModelInstance->resolveRelationUsing('customFields', function (Model $tenantModel) {
                        return $tenantModel->hasMany(CustomField::class, config('custom-fields.column_names.tenant_foreign_key'));
                    });
                }
            }
        }

        Livewire::component('manage-custom-field-section', ManageCustomFieldSection::class);
        Livewire::component('manage-custom-field', ManageCustomField::class);
        Livewire::component('manage-custom-field-width', ManageCustomFieldWidth::class);
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations();
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void {}

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/custom-fields/{$file->getFilename()}"),
                ], 'custom-fields-stubs');
            }
        }

        // Testing
        Testable::mixin(new TestsFilamentCustomField);
    }

    protected function getAssetPackageName(): ?string
    {
        return 'relaticle/custom-fields';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            // AlpineComponent::make('custom-fields', __DIR__ . '/../resources/dist/components/custom-fields.js'),
            Css::make('custom-fields', __DIR__ . '/../resources/dist/custom-fields.css')->loadedOnRequest(),
            // Js::make('custom-fields-scripts', __DIR__ . '/../resources/dist/custom-fields.js'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            FilamentCustomFieldCommand::class,
            UpgradeCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_custom_fields_table',
        ];
    }
}
