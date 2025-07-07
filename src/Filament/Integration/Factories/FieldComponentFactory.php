<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Factories;

use Filament\Forms\Components\Field;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Contracts\FormComponentInterface;
use Relaticle\CustomFields\Models\CustomField;

/**
 * @extends AbstractComponentFactory<FormComponentInterface, Field>
 */
final class FieldComponentFactory extends AbstractComponentFactory
{
    /**
     * @param  array<string>  $dependentFieldCodes
     * @param  Collection<int, CustomField>|null  $allFields
     *
     * @throws BindingResolutionException
     */
    public function create(CustomField $customField, array $dependentFieldCodes = [], ?Collection $allFields = null): Field
    {
        /** @var FormComponentInterface */
        $component = $this->createComponent($customField, 'form_component', FormComponentInterface::class);

        return $component->make($customField, $dependentFieldCodes, $allFields);
    }
}
