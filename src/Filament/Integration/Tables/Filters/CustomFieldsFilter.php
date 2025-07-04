<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Tables\Filters;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Filters\BaseFilter;
use Illuminate\Contracts\Container\BindingResolutionException;
use Relaticle\CustomFields\Facades\CustomFieldsType;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\Utils;

final readonly class CustomFieldsFilter
{
    private HasCustomFields $instance;

    public function make(string $model): static
    {
        $this->instance = app($model);

        return $this;
    }

    /**
     * @return array<BaseFilter>
     *
     * @throws BindingResolutionException
     */
    /**
     * @return array<BaseFilter>
     */
    public function all(): array
    {
        if (Utils::isTableFiltersEnabled() === false) {
            return [];
        }

        $fieldFilterFactory = new FieldFilterFactory;

        return $this->instance
            ->customFields()
            ->with('options')
            ->whereIn('type', CustomFieldsType::toCollection()->onlyFilterables()->keys())
            ->nonEncrypted()
            ->get()
            ->map(
                fn (
                    CustomField $customField
                ): BaseFilter => $fieldFilterFactory->create($customField)
            )
            ->toArray();
    }

    /**
     * @return array<BaseFilter>
     */
    public function forRelationManager(
        RelationManager $relationManager
    ): array {
        $model = $relationManager->getRelationship()->getModel();

        if (! $model instanceof HasCustomFields) {
            return [];
        }

        $this->instance = $model;

        return $this->all();
    }
}
