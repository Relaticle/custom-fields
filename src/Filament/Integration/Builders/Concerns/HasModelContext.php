<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Builders\Concerns;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * ABOUTME: Trait for setting model context in builders
 * ABOUTME: Provides forModel() method to specify entity type and instance
 */
trait HasModelContext
{
    /**
     * The entity type (class name) for the custom fields
     *
     * @var string|null
     */
    protected ?string $entityType = null;

    /**
     * The model instance (optional)
     *
     * @var Model|null
     */
    protected ?Model $modelInstance = null;

    /**
     * Set the model context for the builder
     *
     * @param  Model|string  $model  Model instance or class name
     * @return $this
     */
    public function forModel(Model|string $model): static
    {
        if ($model instanceof Model) {
            $this->modelInstance = $model;
            $this->entityType = $model->getMorphClass();
        } elseif (is_string($model)) {
            if (! class_exists($model)) {
                throw new InvalidArgumentException("Class {$model} does not exist");
            }

            if (! is_subclass_of($model, Model::class)) {
                throw new InvalidArgumentException("Class {$model} must extend " . Model::class);
            }

            $this->entityType = $model;
            $this->modelInstance = null;
        }

        return $this;
    }

    /**
     * Get the entity type
     *
     * @return string
     * @throws \LogicException
     */
    protected function getEntityType(): string
    {
        if ($this->entityType === null) {
            throw new \LogicException('Entity type not set. Call forModel() first.');
        }

        return $this->entityType;
    }

    /**
     * Get the model instance if available
     *
     * @return Model|null
     */
    protected function getModelInstance(): ?Model
    {
        return $this->modelInstance;
    }

    /**
     * Check if a model instance is set
     *
     * @return bool
     */
    protected function hasModelInstance(): bool
    {
        return $this->modelInstance !== null;
    }

    /**
     * Reset the model context
     *
     * @return $this
     */
    public function withoutModel(): static
    {
        $this->entityType = null;
        $this->modelInstance = null;

        return $this;
    }
}