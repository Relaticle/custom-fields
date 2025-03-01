<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\Fields;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TagsInput;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\FieldComponentInterface;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\FilamentResourceService;

final readonly class TagsInputComponent implements FieldComponentInterface
{
    public function __construct(private FieldConfigurator $configurator) {}

    public function make(CustomField $customField): Field
    {
        $field = TagsInput::make("custom_fields.{$customField->code}");

        if ($customField->lookup_type) {
            $entityInstance = FilamentResourceService::getModelInstance($customField->lookup_type);
            $recordTitleAttribute = FilamentResourceService::getRecordTitleAttribute($customField->lookup_type);

            $suggestions = $entityInstance::query()->pluck($recordTitleAttribute, 'id')->toArray();
        } else {
            $suggestions = $customField->options->pluck('name', 'id')->all();
        }

        $field->suggestions($suggestions);

        return $this->configurator->configure($field, $customField);
    }
}
