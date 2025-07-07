<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Forms;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\ToggleButtons;
use Relaticle\CustomFields\Filament\Integration\Base\AbstractFormComponent;
use Relaticle\CustomFields\Filament\Integration\Concerns\Forms\ConfiguresColorOptions;
use Relaticle\CustomFields\Models\CustomField;

final readonly class ToggleButtonsComponent extends AbstractFormComponent
{
    use ConfiguresColorOptions;

    public function create(CustomField $customField): Field
    {
        $field = ToggleButtons::make($this->getFieldName($customField))->inline(false);

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
