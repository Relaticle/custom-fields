<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts\Builders;

use Illuminate\Database\Eloquent\Model;

/**
 * ABOUTME: Main builder interface for creating custom fields components with fluent API
 * ABOUTME: Provides methods for filtering and configuring which custom fields to include
 */
interface CustomFieldsBuilderInterface extends BuilderInterface
{
    /**
     * Set the model class to build custom fields for
     *
     * @param  class-string<Model>  $modelClass
     * @return static
     */
    public function forModel(string $modelClass): static;

    /**
     * Include only specific fields by their codes
     *
     * @param  array<string>  $fieldCodes
     * @return static
     */
    public function only(array $fieldCodes): static;

    /**
     * Exclude specific fields by their codes
     *
     * @param  array<string>  $fieldCodes
     * @return static
     */
    public function except(array $fieldCodes): static;

    /**
     * Include fields from a specific section
     *
     * @param  string  $sectionCode
     * @return static
     */
    public function withSection(string $sectionCode): static;

    /**
     * Include fields from multiple sections
     *
     * @param  array<string>  $sectionCodes
     * @return static
     */
    public function withSections(array $sectionCodes): static;

    /**
     * Set the model instance for value loading
     *
     * @param  Model  $model
     * @return static
     */
    public function forRecord(Model $model): static;

    /**
     * Configure visibility rules handling
     *
     * @param  bool  $apply
     * @return static
     */
    public function applyVisibilityRules(bool $apply = true): static;
}