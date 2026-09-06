<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Forms;

use Relaticle\CustomFields\Filament\Integration\Base\AbstractFormComponent;
use Relaticle\CustomFields\Filament\Integration\Components\Forms\RecordSelectInput\RecordSelectInputComponent;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldRelationship;

final readonly class RecordSelectComponent extends AbstractFormComponent
{
    private const MAX_MULTIPLE_RECORDS = 100;

    public function create(CustomField $customField): RecordSelectInputComponent
    {
        $allowMultiple = $customField->allowsMultipleRecords();
        $maxValues = $allowMultiple ? self::MAX_MULTIPLE_RECORDS : 1;

        return RecordSelectInputComponent::make($customField->getFieldName())
            ->lookupType($customField->targetEntityType())
            ->allowMultiple($allowMultiple)
            ->maxValues($maxValues)
            ->placeholder(__('custom-fields::custom-fields.record.search_placeholder'))
            ->addLabel(__('custom-fields::custom-fields.record.add_record_placeholder'))
            ->rules($this->valueRules($customField, $maxValues));
    }

    /**
     * Cardinality caps a relationship slot, and says so in words the user can act on, so a
     * count rule beside it would report one mistake twice. A field with no definition still
     * needs one.
     *
     * @return array<int, string>
     */
    private function valueRules(CustomField $customField, int $maxValues): array
    {
        return $customField->relationshipDefinition() instanceof CustomFieldRelationship
            ? ['array']
            : ['array', 'max:'.$maxValues];
    }
}
