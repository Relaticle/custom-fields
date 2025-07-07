<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Forms\Components;

use Filament\Forms\Components\Select;
use Relaticle\CustomFields\Filament\Integration\Concerns\Forms\ConfiguresFieldName;
use Relaticle\CustomFields\Filament\Integration\Forms\Components\Traits\ConfiguresColorOptions;
use Relaticle\CustomFields\Filament\Integration\Forms\Components\Traits\ConfiguresLookups;
use Relaticle\CustomFields\Models\CustomField;

final readonly class SelectComponent extends AbstractFormComponent
{
    use ConfiguresColorOptions;
    use ConfiguresFieldName;
    use ConfiguresLookups;

    public function create(CustomField $customField): Select
    {
        $field = Select::make($this->getFieldName($customField))->searchable();

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
