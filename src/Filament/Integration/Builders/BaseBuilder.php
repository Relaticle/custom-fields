<?php

// ABOUTME: Base builder class that provides common functionality for building Filament components
// ABOUTME: Handles model binding, field filtering (except/only), and section grouping

namespace Relaticle\CustomFields\Filament\Integration\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\QueryBuilders\CustomFieldQueryBuilder;

abstract class BaseBuilder
{
    protected Model $model;

    protected Builder $sections;

    protected array $except = [];

    protected array $only = [];

    public function forModel(Model|string $model): static
    {
        if (is_string($model)) {
            $model = app($model);
        }

        if (! $model instanceof HasCustomFields) {
            throw new InvalidArgumentException('Model must implement HasCustomFields interface.');
        }

        $model->load('customFieldValues.customField');

        $this->model = $model;
        $this->sections = CustomFields::newSectionModel()->query()
            ->forEntityType($model::class)
            ->orderBy('sort_order');

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

    protected function getFilteredSections(): Collection
    {
        return $this->sections
            ->with(['fields' => function (HasMany $query): void {
                $query
                    ->when($this instanceof TableBuilder, fn (CustomFieldQueryBuilder $q): CustomFieldQueryBuilder => $q->visibleInList())
                    ->when($this instanceof InfolistBuilder, fn (CustomFieldQueryBuilder $q): CustomFieldQueryBuilder => $q->visibleInView())
                    ->when($this->only !== [], fn (CustomFieldQueryBuilder $q) => $q->whereIn('code', $this->only))
                    ->when($this->except !== [], fn (CustomFieldQueryBuilder $q) => $q->whereNotIn('code', $this->except))
                    ->orderBy('sort_order');
            }])
            ->get()
            ->filter(fn (CustomFieldSection $section) => $section->fields->isNotEmpty());
    }
}
