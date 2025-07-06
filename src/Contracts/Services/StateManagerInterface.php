<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomField;

/**
 * ABOUTME: Interface for managing custom field values and state
 * ABOUTME: Handles reading and writing field values for model instances
 */
interface StateManagerInterface extends ServiceInterface
{
    /**
     * Get the value of a custom field for a model instance
     *
     * @param  Model  $model
     * @param  CustomField  $field
     * @return mixed
     */
    public function getValue(Model $model, CustomField $field): mixed;

    /**
     * Set the value of a custom field for a model instance
     *
     * @param  Model  $model
     * @param  CustomField  $field
     * @param  mixed  $value
     * @return void
     */
    public function setValue(Model $model, CustomField $field, mixed $value): void;

    /**
     * Get all custom field values for a model instance
     *
     * @param  Model  $model
     * @return Collection<string, mixed>
     */
    public function getValues(Model $model): Collection;

    /**
     * Set multiple custom field values for a model instance
     *
     * @param  Model  $model
     * @param  array<string, mixed>  $values
     * @return void
     */
    public function setValues(Model $model, array $values): void;

    /**
     * Check if a model has a value for a specific field
     *
     * @param  Model  $model
     * @param  CustomField  $field
     * @return bool
     */
    public function hasValue(Model $model, CustomField $field): bool;

    /**
     * Delete the value of a custom field for a model instance
     *
     * @param  Model  $model
     * @param  CustomField  $field
     * @return void
     */
    public function deleteValue(Model $model, CustomField $field): void;

    /**
     * Delete all custom field values for a model instance
     *
     * @param  Model  $model
     * @return void
     */
    public function deleteAllValues(Model $model): void;
}