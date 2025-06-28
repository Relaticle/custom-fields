<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Relaticle\CustomFields\Integration\Forms\Components\Traits\ConfiguresColorOptions;
use Relaticle\CustomFields\Integration\Forms\Components\Traits\ConfiguresLookups;
use Relaticle\CustomFields\Models\CustomField;

final readonly class SelectComponent extends AbstractFieldComponent
{
    use ConfiguresLookups;
    use ConfiguresColorOptions;

    public function createField(CustomField $customField): Field
    {
        $field = Select::make("custom_fields.{$customField->code}")->searchable();

        if ($this->usesLookupType($customField)) {
            $field = $this->configureAdvancedLookup($field, $customField->lookup_type);
        } else {
            $options = $this->getCustomFieldOptions($customField);
            $field->options($options);

            // Add color support if enabled (Select uses HTML with color indicators)
            if ($this->hasColorOptionsEnabled($customField)) {
                $coloredOptions = $this->getSelectColoredOptions($customField);

                $field
                    ->native(false)
                    ->allowHtml()
                    ->options($coloredOptions);
            }
        }

        return $field;
    }

}
