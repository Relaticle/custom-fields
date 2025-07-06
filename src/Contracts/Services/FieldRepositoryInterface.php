<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;

/**
 * ABOUTME: Interface for accessing custom field data from the database
 * ABOUTME: Provides methods for querying fields, sections, and their relationships
 */
interface FieldRepositoryInterface extends ServiceInterface
{
    /**
     * Get all custom fields for a specific model class
     *
     * @param  class-string<Model>  $modelClass
     * @param  bool  $activeOnly
     * @return Collection<int, CustomField>
     */
    public function getFieldsForModel(string $modelClass, bool $activeOnly = true): Collection;

    /**
     * Get a custom field by its code
     *
     * @param  string  $code
     * @return CustomField|null
     */
    public function getFieldByCode(string $code): ?CustomField;

    /**
     * Get all fields in a specific section
     *
     * @param  string  $sectionCode
     * @param  bool  $activeOnly
     * @return Collection<int, CustomField>
     */
    public function getFieldsInSection(string $sectionCode, bool $activeOnly = true): Collection;

    /**
     * Get all sections for a specific model class
     *
     * @param  class-string<Model>  $modelClass
     * @param  bool  $activeOnly
     * @return Collection<int, CustomFieldSection>
     */
    public function getSectionsForModel(string $modelClass, bool $activeOnly = true): Collection;

    /**
     * Get fields grouped by sections for a model
     *
     * @param  class-string<Model>  $modelClass
     * @param  bool  $activeOnly
     * @return Collection<string, Collection<int, CustomField>>
     */
    public function getFieldsGroupedBySections(string $modelClass, bool $activeOnly = true): Collection;

    /**
     * Clear any cached data
     *
     * @return void
     */
    public function clearCache(): void;
}