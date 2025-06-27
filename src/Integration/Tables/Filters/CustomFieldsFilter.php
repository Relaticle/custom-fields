<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Tables\Filters;

use Filament\Tables\Filters\BaseFilter;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\Utils;

final readonly class CustomFieldsFilter
{
    /**
     * @throws BindingResolutionException
     */
    public static function all(Model&HasCustomFields $instance): array
    {
        if (Utils::isTableFiltersEnabled() === false) {
            return [];
        }

        $fieldFilterFactory = new FieldFilterFactory(app());

        return $instance->customFields()
            ->with('options')
            ->whereIn('type', CustomFieldType::filterable()->pluck('value'))
            ->nonEncrypted()
            ->get()
            ->map(fn (CustomField $customField): BaseFilter => $fieldFilterFactory->create($customField))
            ->toArray();
    }

    /**
     * @throws BindingResolutionException
     */
    public static function forRelationManager(RelationManager $relationManager): array
    {
        return CustomFieldsFilter::all($relationManager->getRelationship()->getModel());
    }
}
