<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Relaticle\CustomFields\EntitySystem\EntityConfigurator;
use Relaticle\CustomFields\EntitySystem\EntityManager;
use Relaticle\CustomFields\EntitySystem\EntityModel;
use Relaticle\CustomFields\Enums\CustomFieldsFeature;
use Relaticle\CustomFields\Enums\EntityFeature;
use Relaticle\CustomFields\FeatureSystem\FeatureConfigurator;
use Relaticle\CustomFields\FeatureSystem\FeatureManager;
use Relaticle\CustomFields\Filament\Integration\Components\Forms\RecordSelectInput\RecordSelectInputComponent;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Services\TenantContextService;
use Relaticle\CustomFields\Tests\Fixtures\Models\Post;

/**
 * Replace the registered entities with a single lookup source and rebuild the registry.
 *
 * @param  array<int, string>  $searchAttributes
 */
function registerLookupEntity(string $modelClass, string $primaryAttribute, array $searchAttributes = []): void
{
    config()->set('custom-fields.entity_configuration',
        EntityConfigurator::configure()
            ->autoDiscover(false)
            ->cache(false)
            ->models([
                EntityModel::configure(
                    modelClass: $modelClass,
                    primaryAttribute: $primaryAttribute,
                    searchAttributes: $searchAttributes,
                    features: [EntityFeature::CUSTOM_FIELDS, EntityFeature::LOOKUP_SOURCE],
                ),
            ])
    );

    app()->forgetInstance(EntityManager::class);
}

/**
 * Register the post fixture as the only lookup source, titled and searchable by title.
 */
function registerPostLookupEntity(): void
{
    registerLookupEntity(Post::class, primaryAttribute: 'title', searchAttributes: ['title']);
}

function makeLookupRecord(string $name, ?Carbon $updatedAt = null): Post
{
    $attributes = ['title' => $name];

    if ($updatedAt instanceof Carbon) {
        $attributes['updated_at'] = $updatedAt;
    }

    return Post::factory()->create($attributes);
}

function recordSelectFor(string $modelClass): RecordSelectInputComponent
{
    return RecordSelectInputComponent::make('record')->lookupType($modelClass);
}

/**
 * @return array<int, array{id: string, label: string, avatar: ?string, avatarShape: string}>
 */
function recordSelectInitialOptions(string $modelClass = Post::class): array
{
    return array_values(recordSelectFor($modelClass)->getInitialOptions());
}

/**
 * @return array<int, array{id: string, label: string, avatar: ?string, avatarShape: string}>
 */
function recordSelectSearch(string $term, string $modelClass = Post::class): array
{
    return recordSelectFor($modelClass)->getSearchResultsForJs($term);
}

/**
 * The features block exactly as the package ships it, bypassing the test environment's own.
 */
function shippedFeatureConfigurator(): FeatureConfigurator
{
    /** @var array{features: FeatureConfigurator} $config */
    $config = require dirname(__DIR__).'/config/custom-fields.php';

    return $config['features'];
}

/**
 * A section for the given entity type, so fields created under it survive the activable scope.
 */
function sectionForEntity(string $entityType): CustomFieldSection
{
    $attributes = ['entity_type' => $entityType];

    if (FeatureManager::isEnabled(CustomFieldsFeature::SYSTEM_MULTI_TENANCY)) {
        $attributes[config('custom-fields.database.column_names.tenant_foreign_key')] = TenantContextService::getCurrentTenantId();
    }

    return CustomFieldSection::factory()->create($attributes);
}

/**
 * Rebuild the schema the multi-tenancy feature flag would have migrated, then enter a tenant.
 * MySQL commits DDL implicitly, which ends the test transaction, so callers skip it there.
 */
function useTenantSchema(int|string $tenantId): void
{
    $tenantKey = config('custom-fields.database.column_names.tenant_foreign_key');

    $tables = [
        config('custom-fields.database.table_names.custom_field_sections'),
        config('custom-fields.database.table_names.custom_fields'),
        config('custom-fields.database.table_names.custom_field_relationships'),
        config('custom-fields.database.table_names.custom_field_links'),
    ];

    foreach ($tables as $table) {
        Schema::table($table, function (Blueprint $blueprint) use ($tenantKey): void {
            $blueprint->unsignedBigInteger($tenantKey)->nullable();
        });
    }

    // Eloquent caches each model's column listing statically to decide what is guardable, and
    // an earlier test in this process cached these tables without their tenant column.
    Closure::bind(static function (): void {
        Model::$guardableColumns = [];
    }, null, Model::class)();

    config('custom-fields.features')->enable(CustomFieldsFeature::SYSTEM_MULTI_TENANCY);

    TenantContextService::setTenantId($tenantId);
}
