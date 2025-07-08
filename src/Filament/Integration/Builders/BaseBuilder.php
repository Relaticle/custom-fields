<?php

// ABOUTME: Base builder class that provides common functionality for building Filament components
// ABOUTME: Handles model binding, field filtering (except/only), and section grouping

namespace Relaticle\CustomFields\Filament\Integration\Builders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomField;

abstract class BaseBuilder
{
    protected Model $model;

    protected Collection $fields;

    protected array $except = [];

    protected array $only = [];

    public function forModel(Model $model): static
    {
//        $model->load('customFieldValues.customField');

        $this->model = $model;
        $this->fields = $model->customFields()
            ->with(['options', 'section'])
            ->get();

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

    protected function getFilteredFields(): Collection
    {
        return $this->fields
            ->when(! empty($this->only), fn (Collection $collection) => $collection->whereIn('code', $this->only))
            ->when(! empty($this->except), fn (Collection $collection) => $collection->whereNotIn('code', $this->except));
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
