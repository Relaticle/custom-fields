<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Forms;

use Filament\Forms\Components\Field;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Filament\Integration\AbstractComponentFactory;
use Relaticle\CustomFields\Filament\Integration\Forms\Components\FieldComponentInterface;
use Relaticle\CustomFields\Models\CustomField;

/**
 * @extends AbstractComponentFactory<FieldComponentInterface, Field>
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
        /** @var FieldComponentInterface */
        $component = $this->createComponent($customField, 'form_component', FieldComponentInterface::class);

        return $component->make($customField, $dependentFieldCodes, $allFields);
    }
}
