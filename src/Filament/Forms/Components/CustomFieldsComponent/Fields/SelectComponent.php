<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\Fields;

use Filament\Forms\Components\Select;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\FieldComponentInterface;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\FilamentResourceService;
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
            $field->options($customField->options->pluck('name', 'id')->all());
        }

        /** @var Select */
        return $this->configurator->configure($field, $customField);
    }

    /**
     * @throws Throwable
     */
    protected function configureLookup(Select $select, $lookupType): Select
    {
        $entityInstance = FilamentResourceService::getModelInstance($lookupType);
        $recordTitleAttribute = FilamentResourceService::getRecordTitleAttribute($lookupType);
        $globalSearchableAttributes = FilamentResourceService::getGlobalSearchableAttributes($lookupType);

        return $select
            ->options(function () use ($select, $entityInstance, $recordTitleAttribute) {
                if (! $select->isPreloaded()) {
                    return [];
                }

                return $entityInstance::query()
                    ->pluck($recordTitleAttribute, 'id')
                    ->toArray();
            })
            ->getSearchResultsUsing(fn (string $search): array => $entityInstance->query()
                ->whereAny($globalSearchableAttributes, 'like', "%{$search}%")
                ->limit(50)
                ->pluck($recordTitleAttribute, 'id')
                ->toArray())
            ->getOptionLabelUsing(fn ($value) => $entityInstance::query()->find($value)?->{$recordTitleAttribute})
            ->getOptionLabelsUsing(fn (array $values): array => $entityInstance::query()
                ->whereIn('id', $values)
                ->pluck($recordTitleAttribute, 'id')
                ->toArray());
    }
}
