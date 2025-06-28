<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Integration\Forms\Components\Traits\ConfiguresColorOptions;
use Relaticle\CustomFields\Integration\Forms\Components\Traits\ConfiguresLookups;
use Relaticle\CustomFields\Models\CustomField;

final readonly class RadioComponent extends AbstractFieldComponent
{
    use ConfiguresLookups;
    use ConfiguresColorOptions;

    public function createField(CustomField $customField): Field
    {
        $field = Radio::make("custom_fields.{$customField->code}")->inline(false);

        // Get options from lookup or field options
        $options = $this->getFieldOptions($customField);
        $field->options($options);

        // Add color styling if enabled (only for non-lookup fields)
        if (!$this->usesLookupType($customField) && $this->hasColorOptionsEnabled($customField)) {
            $coloredOptions = $this->getColoredOptions($customField);

            if (count($coloredOptions) > 0) {
                $field->descriptions(
                    $this->getColorDescriptions(array_keys($coloredOptions), $customField)
                );
            }
        }

        return $field;
    }
}
