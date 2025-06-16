<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\Fields;

use Filament\Forms\Components\Select;
use ReflectionException;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\FieldComponentInterface;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\FilamentResourceService;
use Relaticle\CustomFields\Support\Utils;
use Throwable;

final readonly class SelectComponent implements FieldComponentInterface
{
    public function __construct(private FieldConfigurator $configurator) {}

    /**
     * @throws Throwable
     */
    public function make(CustomField $customField): Select
    {
        $field = Select::make("custom_fields.{$customField->code}")->searchable();

        if ($customField->lookup_type) {
            $field = $this->configureLookup($field, $customField->lookup_type);
        } else {
            $options = $customField->options->pluck('name', 'id')->all();
            $field->options($options);

            // Add color support if enabled
            if (Utils::isSelectOptionColorsFeatureEnabled() && $customField->settings->enable_option_colors) {
                $coloredOptions = $customField->options
                    ->mapWithKeys(function ($option) {
                        $color = $option->settings?->color;
                        $text = $option->name;

                        if ($color) {
                            return [
                                $option->id => str(
                                    '<div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full" style="background-color:{BACKGROUND_COLOR}"></span>
                                    <span>{LABEL}</span>
                                    </div>'
                                )
                                    ->replace(['{BACKGROUND_COLOR}', '{LABEL}'], [e($color), e($text)]),
                            ];
                        }

                        return [$option->id => $text];
                    })
                    ->all();

                $field
                    ->native(false)
                    ->allowHtml()
                    ->options($coloredOptions);
            }
        }

        /** @var Select */
        return $this->configurator->configure($field, $customField);
    }

    /**
     * @throws Throwable
     * @throws ReflectionException
     */
    protected function configureLookup(Select $select, $lookupType): Select
    {
        $resource = FilamentResourceService::getResourceInstance($lookupType);
        $entityInstanceQuery = FilamentResourceService::getModelInstanceQuery($lookupType);
        $entityInstanceKeyName = $entityInstanceQuery->getModel()->getKeyName();
        $recordTitleAttribute = FilamentResourceService::getRecordTitleAttribute($lookupType);
        $globalSearchableAttributes = FilamentResourceService::getGlobalSearchableAttributes($lookupType);

        return $select
            ->options(function () use ($select, $entityInstanceQuery, $recordTitleAttribute, $entityInstanceKeyName) {
                if (! $select->isPreloaded()) {
                    return [];
                }

                return $entityInstanceQuery
                    ->pluck($recordTitleAttribute, $entityInstanceKeyName)
                    ->toArray();
            })
            ->getSearchResultsUsing(function (string $search) use ($entityInstanceQuery, $entityInstanceKeyName, $recordTitleAttribute, $globalSearchableAttributes, $resource): array {
                FilamentResourceService::invokeMethodByReflection($resource, 'applyGlobalSearchAttributeConstraints', [
                    $entityInstanceQuery, $search, $globalSearchableAttributes,
                ]);

                return $entityInstanceQuery
                    ->limit(50)
                    ->pluck($recordTitleAttribute, $entityInstanceKeyName)
                    ->toArray();
            })
            ->getOptionLabelUsing(fn ($value) => $entityInstanceQuery->find($value)?->{$recordTitleAttribute})
            ->getOptionLabelsUsing(function (array $values) use ($entityInstanceQuery, $entityInstanceKeyName, $recordTitleAttribute): array {
                return $entityInstanceQuery
                    ->whereIn($entityInstanceKeyName, $values)
                    ->pluck($recordTitleAttribute, $entityInstanceKeyName)
                    ->toArray();
            });
    }
}
