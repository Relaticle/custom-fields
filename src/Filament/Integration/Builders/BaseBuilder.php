<?php

// ABOUTME: Base builder class that provides common functionality for building Filament components
// ABOUTME: Handles model binding, field filtering (except/only), and section grouping

namespace Relaticle\CustomFields\Filament\Integration\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomField;

abstract class BaseBuilder
{
    protected Model $model;

    protected Builder $fields;

    protected array $except = [];

    protected array $only = [];

    public function forModel(Model | string $model): static
    {
        if (is_string($model)) {
            $model = app($model);
        }

        if (! $model instanceof HasCustomFields) {
            throw new InvalidArgumentException('Model must implement HasCustomFields interface.');
        }

        $model->load('customFieldValues.customField');

        $this->model = $model;
        $this->fields = $model->customFields()->with(['options', 'section']);

        return $this;
    }

    public function except(array $fieldCodes): static
    {
        $this->except = $fieldCodes;

        return $this;
    }

    public function only(array $fieldCodes): static
    {
        $this->only = $fieldCodes;

        return $this;
    }

    /**
     * @return Builder<CustomField>
     */
    protected function getFilteredFields(): Builder
    {
        return $this->fields
            ->when(! empty($this->only), fn (Builder $collection) => $collection->whereIn('code', $this->only))
            ->when(! empty($this->except), fn (Builder $collection) => $collection->whereNotIn('code', $this->except));
    }

    protected function groupFieldsBySection(): Collection
    {
        $filteredFields = $this->getFilteredFields();

        // Group fields by their sections
        return $filteredFields->groupBy(function (CustomField $field) {
            return $field->section->id;
        });
    }
}
