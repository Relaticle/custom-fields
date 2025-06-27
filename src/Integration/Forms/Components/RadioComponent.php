<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Throwable;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Integration\Forms\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\FilamentResourceService;
use Relaticle\CustomFields\Support\Utils;

final readonly class RadioComponent implements FieldComponentInterface
{
    public function __construct(private FieldConfigurator $configurator) {}

    /**
     * @param  array<string>  $dependentFieldCodes
     *
     * @throws Throwable
     */
    public function make(CustomField $customField, array $dependentFieldCodes = [], ?Collection $allFields = null): Field
    {
        $field = Radio::make("custom_fields.{$customField->code}")->inline(false);

        if ($customField->lookup_type) {
            /** @var Model $entityInstance */
            $entityInstance = FilamentResourceService::getModelInstance($customField->lookup_type);
            $recordTitleAttribute = FilamentResourceService::getRecordTitleAttribute($customField->lookup_type);

            /** @var Builder<Model> $query */
            $query = $entityInstance->newQuery();
            $options = $query->limit(50)->pluck($recordTitleAttribute, 'id')->toArray();
        } else {
            $options = $customField->options->pluck('name', 'id')->all();

            // Add color styling if enabled
            if (Utils::isSelectOptionColorsFeatureEnabled() && $customField->settings->enable_option_colors) {
                $optionsWithColor = $customField->options
                    ->filter(fn ($option) => $option->settings?->color)
                    ->mapWithKeys(fn ($option) => [$option->id => $option->name])
                    ->all();

                if (count($optionsWithColor) > 0) {
                    $field->descriptions(
                        array_map(
                            fn ($optionId): string => $this->getColoredOptionDescription($optionId, $customField),
                            array_keys($optionsWithColor)
                        )
                    );
                }
            }
        }

        $field->options($options);

        return $this->configurator->configure($field, $customField, $dependentFieldCodes, $allFields);
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
