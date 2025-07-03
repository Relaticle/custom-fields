<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\ToggleButtons;
use Relaticle\CustomFields\Integration\Forms\Components\Traits\ConfiguresColorOptions;
use Relaticle\CustomFields\Models\CustomField;

final readonly class ToggleButtonsComponent extends AbstractFormComponent
{
    use ConfiguresColorOptions;

    public function create(CustomField $customField): Field
    {
        $field = ToggleButtons::make("custom_fields.{$customField->code}")->inline(false);

        // ToggleButtons only use field options, no lookup support
        $options = $customField->options->pluck('name', 'id')->all();
        $field->options($options);

        // Add color support if enabled (ToggleButtons use native colors method)
        if ($this->hasColorOptionsEnabled($customField)) {
            $colorMapping = $this->getColorMapping($customField);

            if (count($colorMapping) > 0) {
                $field->colors($colorMapping);
            }
        }

        return $field;
    }
}
