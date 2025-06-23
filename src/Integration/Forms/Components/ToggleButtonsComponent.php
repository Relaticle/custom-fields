<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\ToggleButtons;
use Relaticle\CustomFields\Integration\Forms\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\Utils;

final readonly class ToggleButtonsComponent implements FieldComponentInterface
{
    public function __construct(private FieldConfigurator $configurator) {}

    /**
     * @param  array<string>  $dependentFieldCodes
     */
    public function make(CustomField $customField, array $dependentFieldCodes = []): Field
    {
        $field = ToggleButtons::make("custom_fields.{$customField->code}")->inline(false);

        $options = $customField->options->pluck('name', 'id')->all();
        $field->options($options);

        // Add color support if enabled
        if (Utils::isSelectOptionColorsFeatureEnabled() && $customField->settings->enable_option_colors) {
            $optionsWithColor = $customField->options
                ->filter(fn ($option) => $option->settings?->color)
                ->mapWithKeys(fn ($option) => [$option->id => $option->settings->color])
                ->all();

            if (count($optionsWithColor)) {
                $field->colors($optionsWithColor);
            }
        }

        return $this->configurator->configure($field, $customField, $dependentFieldCodes);
    }
}
