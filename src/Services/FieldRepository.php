<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Relaticle\CustomFields\Contracts\Services\FieldRepositoryInterface;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;

/**
 * ABOUTME: Service for accessing custom field data from the database with caching
 * ABOUTME: Provides optimized queries for fields and sections with tenant awareness
 */
class FieldRepository implements FieldRepositoryInterface
{
    /**
     * Cache key prefix
     */
    protected const CACHE_PREFIX = 'custom_fields:repository:';

    /**
     * Cache duration in seconds (1 hour)
     */
    protected const CACHE_TTL = 3600;

    /**
     * Whether the service is initialized
     */
    protected bool $initialized = false;

    /**
     * Configuration array
     *
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * Check if the service is properly initialized
     *
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Initialize the service with configuration
     *
     * @param  array<string, mixed>  $config
     * @return void
     */
    public function initialize(array $config = []): void
    {
        $this->config = $config;
        $this->initialized = true;
    }

    /**
     * Get all custom fields for a specific model class
     *
     * @param  class-string<Model>  $modelClass
     * @param  bool  $activeOnly
     * @return Collection<int, CustomField>
     */
    public function getFieldsForModel(string $modelClass, bool $activeOnly = true): Collection
    {
        $cacheKey = $this->getCacheKey("fields:{$modelClass}:{$activeOnly}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($modelClass, $activeOnly) {
            $query = CustomField::query()
                ->with(['section', 'options'])
                ->where('model_type', $modelClass);

            if ($activeOnly) {
                $query->where('is_active', true);
            }

            return $query->orderBy('sort_order')->get();
        });
    }

    /**
     * Get a custom field by its code
     *
     * @param  string  $code
     * @return CustomField|null
     */
    public function getFieldByCode(string $code): ?CustomField
    {
        $cacheKey = $this->getCacheKey("field:code:{$code}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($code) {
            return CustomField::query()
                ->with(['section', 'options'])
                ->where('code', $code)
                ->first();
        });
    }

    /**
     * Get all fields in a specific section
     *
     * @param  string  $sectionCode
     * @param  bool  $activeOnly
     * @return Collection<int, CustomField>
     */
    public function getFieldsInSection(string $sectionCode, bool $activeOnly = true): Collection
    {
        $cacheKey = $this->getCacheKey("fields:section:{$sectionCode}:{$activeOnly}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($sectionCode, $activeOnly) {
            $query = CustomField::query()
                ->with(['section', 'options'])
                ->whereHas('section', function ($query) use ($sectionCode) {
                    $query->where('code', $sectionCode);
                });

            if ($activeOnly) {
                $query->where('is_active', true);
            }

            return $query->orderBy('sort_order')->get();
        });
    }

    /**
     * Get all sections for a specific model class
     *
     * @param  class-string<Model>  $modelClass
     * @param  bool  $activeOnly
     * @return Collection<int, CustomFieldSection>
     */
    public function getSectionsForModel(string $modelClass, bool $activeOnly = true): Collection
    {
        $cacheKey = $this->getCacheKey("sections:{$modelClass}:{$activeOnly}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($modelClass, $activeOnly) {
            $query = CustomFieldSection::query()
                ->with(['fields' => function ($query) {
                    $query->orderBy('sort_order');
                }])
                ->where('model_type', $modelClass);

            if ($activeOnly) {
                $query->where('is_active', true);
            }

            return $query->orderBy('sort_order')->get();
        });
    }

    /**
     * Get fields grouped by sections for a model
     *
     * @param  class-string<Model>  $modelClass
     * @param  bool  $activeOnly
     * @return Collection<string, Collection<int, CustomField>>
     */
    public function getFieldsGroupedBySections(string $modelClass, bool $activeOnly = true): Collection
    {
        $cacheKey = $this->getCacheKey("fields:grouped:{$modelClass}:{$activeOnly}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($modelClass, $activeOnly) {
            $sections = $this->getSectionsForModel($modelClass, $activeOnly);
            
            $grouped = new Collection();
            
            foreach ($sections as $section) {
                $fields = $section->fields;
                
                if ($activeOnly) {
                    $fields = $fields->filter(fn (CustomField $field) => $field->is_active);
                }
                
                $grouped->put($section->code, $fields);
            }

            // Add fields without sections
            $orphanFields = CustomField::query()
                ->where('model_type', $modelClass)
                ->whereNull('custom_field_section_id')
                ->when($activeOnly, fn ($query) => $query->where('is_active', true))
                ->orderBy('sort_order')
                ->get();

            if ($orphanFields->isNotEmpty()) {
                $grouped->put('_no_section', $orphanFields);
            }

            return $grouped;
        });
    }

    /**
     * Clear any cached data
     *
     * @return void
     */
    public function clearCache(): void
    {
        // Clear all cache keys with our prefix
        Cache::flush(); // In production, implement a more targeted cache clearing
    }

    /**
     * Get cache key with prefix
     *
     * @param  string  $key
     * @return string
     */
    protected function getCacheKey(string $key): string
    {
        $tenantId = $this->config['tenant_id'] ?? 'global';
        
        return self::CACHE_PREFIX . $tenantId . ':' . $key;
    }
}