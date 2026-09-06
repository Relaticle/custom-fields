<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Forms;

use Relaticle\CustomFields\Filament\Integration\Base\AbstractFormComponent;
use Relaticle\CustomFields\Filament\Integration\Components\Forms\RecordSelectInput\RecordSelectInputComponent;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldRelationship;
use Relaticle\CustomFields\Services\Relationships\MissingRelationshipDefinitions;

final readonly class RecordSelectComponent extends AbstractFormComponent
{
    private const MAX_MULTIPLE_RECORDS = 100;

    public function create(CustomField $customField): RecordSelectInputComponent
    {
        $definition = $customField->relationshipDefinition();
        $allowMultiple = $customField->allowsMultipleRecords();
        $maxValues = $allowMultiple ? self::MAX_MULTIPLE_RECORDS : 1;

        $component = RecordSelectInputComponent::make($customField->getFieldName())
            ->lookupType($customField->targetEntityType())
            ->allowMultiple($allowMultiple)
            ->maxValues($maxValues)
            ->placeholder(__('custom-fields::custom-fields.record.search_placeholder'))
            ->addLabel(__('custom-fields::custom-fields.record.add_record_placeholder'))
            ->rules($this->valueRules($definition, $maxValues));

        // A hidden field is never dehydrated, so a form that cannot show the field cannot
        // write it either, which is what a missing definition should mean on a write path.
        if (! $definition instanceof CustomFieldRelationship) {
            app(MissingRelationshipDefinitions::class)->report($customField);

            return $component->hidden();
        }

        return $component;
    }

    /**
     * Cardinality caps a relationship slot, and says so in words the user can act on, so a
     * count rule beside it would report one mistake twice. A field with no definition still
     * needs one.
     *
     * @return array<int, string>
     */
    private function valueRules(?CustomFieldRelationship $definition, int $maxValues): array
    {
        return $definition instanceof CustomFieldRelationship
            ? ['array']
            : ['array', 'max:'.$maxValues];
    }
}
