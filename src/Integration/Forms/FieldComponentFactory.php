<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms;

use BackedEnum;
use Filament\Forms\Components\Field;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Relaticle\CustomFields\Integration\Forms\Components\FieldComponentInterface;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\FieldTypeRegistryService;
use RuntimeException;

final class FieldComponentFactory
{
    /**
     * @var array<class-string<FieldComponentInterface>, FieldComponentInterface>
     */
    private array $instanceCache = [];

    public function __construct(
        private readonly Container $container,
        private readonly FieldTypeRegistryService $fieldTypeRegistry
    ) {}

    /**
     * @param  array<string>  $dependentFieldCodes
     * @param  Collection<int, CustomField>|null  $allFields
     *
     * @throws BindingResolutionException
     */
    public function create(CustomField $customField, array $dependentFieldCodes = [], ?Collection $allFields = null): Field
    {
        // Handle both enum and string types
        $customFieldType = $customField->type instanceof BackedEnum
            ? $customField->type->value
            : $customField->type;

        $fieldTypeConfig = $this->fieldTypeRegistry->getFieldType($customFieldType);

        if ($fieldTypeConfig === null) {
            throw new InvalidArgumentException("No component registered for custom field type: {$customFieldType}");
        }

        $componentClass = $fieldTypeConfig['form_component'];

        if (! isset($this->instanceCache[$componentClass])) {
            $component = $this->container->make($componentClass);

            if (! $component instanceof FieldComponentInterface) {
                throw new RuntimeException("Component class {$componentClass} must implement FieldComponentInterface");
            }

            $this->instanceCache[$componentClass] = $component;
        } else {
            $component = $this->instanceCache[$componentClass];
        }

        return $component->make($customField, $dependentFieldCodes, $allFields);
    }
}
