<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\Fields;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Radio;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\FieldComponentInterface;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\FilamentResourceService;

final readonly class RadioComponent implements FieldComponentInterface
{
    public function __construct(private FieldConfigurator $configurator) {}

    public function make(CustomField $customField): Field
    {
        $field = Radio::make("custom_fields.{$customField->code}")->inline(false);

        if ($customField->lookup_type) {
            $entityInstance = FilamentResourceService::getModelInstance($customField->lookup_type);
            $recordTitleAttribute = FilamentResourceService::getRecordTitleAttribute($customField->lookup_type);

            $options = $entityInstance->query()->limit(50)->pluck($recordTitleAttribute, 'id')->toArray();
        } else {
            $options = $customField->options->pluck('name', 'id')->all();
        }

        $field->options($options);

        return $this->configurator->configure($field, $customField);
    }
}
