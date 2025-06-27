<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Tables\Columns;

use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Contracts\Container\BindingResolutionException;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\Utils;

final readonly class CustomFieldsColumn
{
    /**
     * @return array<int, \Filament\Tables\Columns\Column>
     *
     * @throws BindingResolutionException
     */
    public static function all(HasCustomFields $instance): array
    {
        if (Utils::isTableColumnsEnabled() === false) {
            return [];
        }

        $fieldColumnFactory = app(FieldColumnFactory::class);

        return $instance
            ->customFields()
            ->visibleInList()
            ->with('options')
            ->get()
            ->map(
                fn (CustomField $customField) => $fieldColumnFactory
                    ->create($customField)
                    ->toggleable(
                        condition: Utils::isTableColumnsToggleableEnabled(),
                        isToggledHiddenByDefault: $customField->settings
                            ->list_toggleable_hidden
                    )
            )
            ->toArray();
    }

    /**
     * @return array<int, \Filament\Tables\Columns\Column>
     */
    public static function forRelationManager(
        RelationManager $relationManager
    ): array {
        $model = $relationManager->getRelationship()->getModel();
        
        if (! $model instanceof HasCustomFields) {
            return [];
        }
        
        return CustomFieldsColumn::all($model);
    }
}
