<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Traits;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;

/**
 * ABOUTME: Trait providing model context functionality for builders
 * ABOUTME: Handles model class validation and record instance management
 */
trait HasModelContext
{
    /**
     * The model class to build custom fields for
     *
     * @var class-string<Model>|null
     */
    protected ?string $modelClass = null;

    /**
     * The model instance for value loading
     *
     * @var Model|null
     */
    protected ?Model $record = null;

    /**
     * Set the model class to build custom fields for
     *
     * @param  class-string<Model>  $modelClass
     * @return static
     * @throws InvalidArgumentException
     */
    public function forModel(string $modelClass): static
    {
        if (! class_exists($modelClass)) {
            throw new InvalidArgumentException("Model class [{$modelClass}] does not exist.");
        }

        if (! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException("Class [{$modelClass}] must extend Eloquent Model.");
        }

        if (! is_subclass_of($modelClass, HasCustomFields::class)) {
            throw new InvalidArgumentException("Model [{$modelClass}] must implement HasCustomFields interface.");
        }

        $this->modelClass = $modelClass;

        return $this;
    }

    /**
     * Set the model instance for value loading
     *
     * @param  Model  $model
     * @return static
     * @throws InvalidArgumentException
     */
    public function forRecord(Model $model): static
    {
        if (! $model instanceof HasCustomFields) {
            throw new InvalidArgumentException('Model must implement HasCustomFields interface.');
        }

        $this->record = $model;
        $this->modelClass = get_class($model);

        return $this;
    }

    /**
     * Get the configured model class
     *
     * @return class-string<Model>|null
     */
    protected function getModelClass(): ?string
    {
        return $this->modelClass;
    }

    /**
     * Get the configured record instance
     *
     * @return Model|null
     */
    protected function getRecord(): ?Model
    {
        return $this->record;
    }

    /**
     * Check if a model class has been configured
     *
     * @return bool
     */
    protected function hasModelClass(): bool
    {
        return $this->modelClass !== null;
    }

    /**
     * Check if a record instance has been configured
     *
     * @return bool
     */
    protected function hasRecord(): bool
    {
        return $this->record !== null;
    }

    /**
     * Reset model context
     *
     * @return void
     */
    protected function resetModelContext(): void
    {
        $this->modelClass = null;
        $this->record = null;
    }
}