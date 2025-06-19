<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Tables\Columns;

use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\Utils;

final readonly class CustomFieldsColumn
{
    /**
     * @throws BindingResolutionException
     */
    public static function all(Model $instance): array
    {
        if (Utils::isTableColumnsEnabled() === false) {
            return [];
        }

        $fieldColumnFactory = new FieldColumnFactory(app());

        return $instance->customFields()
            ->visibleInList()
            ->with('options')
            ->get()
            ->map(fn (CustomField $customField) => $fieldColumnFactory->create($customField)
                ->toggleable(
                    condition: Utils::isTableColumnsToggleableEnabled(),
                    isToggledHiddenByDefault: $customField->settings->list_toggleable_hidden
                )
            )
            ->toArray();
    }

    public static function forRelationManager(RelationManager $relationManager): array
    {
        return CustomFieldsColumn::all($relationManager->getRelationship()->getModel());
    }
}
