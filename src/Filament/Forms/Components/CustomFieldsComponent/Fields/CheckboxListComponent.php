<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\Fields;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Field;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\FieldComponentInterface;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\FilamentResourceService;
use Relaticle\CustomFields\Support\Utils;

final readonly class CheckboxListComponent implements FieldComponentInterface
{
    public function __construct(private FieldConfigurator $configurator) {}

    public function make(CustomField $customField): Field
    {
        $field = CheckboxList::make("custom_fields.{$customField->code}");

        if ($customField->lookup_type) {
            $entityInstance = FilamentResourceService::getModelInstance($customField->lookup_type);
            $recordTitleAttribute = FilamentResourceService::getRecordTitleAttribute($customField->lookup_type);

            $options = $entityInstance->query()->limit(50)->pluck($recordTitleAttribute, 'id')->toArray();
        } else {
            $options = $customField->options->pluck('name', 'id')->all();

            // Add color styling if enabled
            if (Utils::isSelectOptionColorsFeatureEnabled() && $customField->settings->enable_option_colors) {
                $optionsWithColor = $customField->options
                    ->filter(fn ($option) => $option->settings?->color)
                    ->mapWithKeys(fn ($option) => [$option->id => $option->name])
                    ->all();

                if (count($optionsWithColor)) {
                    $field->descriptions(
                        array_map(
                            fn ($optionId) => $this->getColoredOptionDescription($optionId, $customField),
                            array_keys($optionsWithColor)
                        )
                    );
                }
            }
        }

        $field->options($options);

        return $this->configurator->configure($field, $customField);
    }

    /**
     * Generate HTML for colored option indicator
     */
    private function getColoredOptionDescription(string $optionId, CustomField $customField): string
    {
        $option = $customField->options->firstWhere('id', $optionId);
        if (! $option || ! $option->settings?->color) {
            return '';
        }

        return "<span style='display: inline-block; width: 12px; height: 12px; background-color: {$option->settings->color}; border-radius: 2px; margin-right: 4px;'></span>";
    }
}
