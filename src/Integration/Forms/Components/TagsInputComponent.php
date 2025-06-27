<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TagsInput;
use Illuminate\Support\Collection;
use ReflectionException;
use Relaticle\CustomFields\Integration\Forms\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\FilamentResourceService;
use Throwable;

final readonly class TagsInputComponent implements FieldComponentInterface
{
    public function __construct(private FieldConfigurator $configurator) {}

    /**
     * @param  array<string>  $dependentFieldCodes
     *
     * @throws ReflectionException
     * @throws Throwable
     */
    public function make(CustomField $customField, array $dependentFieldCodes = [], ?Collection $allFields = null): Field
    {
        $field = TagsInput::make("custom_fields.{$customField->code}");

        if ($customField->lookup_type) {
            $entityInstanceQuery = FilamentResourceService::getModelInstanceQuery($customField->lookup_type);
            $entityInstanceKeyName = $entityInstanceQuery->getModel()->getKeyName();
            $recordTitleAttribute = FilamentResourceService::getRecordTitleAttribute($customField->lookup_type);

            $suggestions = $entityInstanceQuery->pluck($recordTitleAttribute, $entityInstanceKeyName)->toArray();
        } else {
            $suggestions = $customField->options->pluck('name', 'id')->all();
        }

        $field->suggestions($suggestions);

        return $this->configurator->configure($field, $customField, $allFields, $dependentFieldCodes);
    }
}
