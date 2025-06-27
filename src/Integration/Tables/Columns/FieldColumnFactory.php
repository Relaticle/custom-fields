<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Tables\Columns;

use Filament\Tables\Columns\Column;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\FieldTypeRegistryService;
use RuntimeException;

final class FieldColumnFactory
{
    /**
     * @var array<class-string<ColumnInterface>, ColumnInterface>
     */
    private array $instanceCache = [];

    public function __construct(
        private readonly Container $container,
        private readonly FieldTypeRegistryService $fieldTypeRegistry
    ) {}

    /**
     * @throws BindingResolutionException
     */
    public function create(CustomField $customField): Column
    {
        // Handle both enum and string types
        $customFieldType = $customField->type instanceof \BackedEnum
            ? $customField->type->value
            : $customField->type;

        $fieldTypeConfig = $this->fieldTypeRegistry->getFieldType($customFieldType);

        if ($fieldTypeConfig === null) {
            throw new InvalidArgumentException("No column registered for custom field type: {$customFieldType}");
        }

        $componentClass = $fieldTypeConfig['table_column'];

        if (! isset($this->instanceCache[$componentClass])) {
            $component = $this->container->make($componentClass);

            if (! $component instanceof ColumnInterface) {
                throw new RuntimeException("Component class {$componentClass} must implement ColumnInterface");
            }

            $this->instanceCache[$componentClass] = $component;
        } else {
            $component = $this->instanceCache[$componentClass];
        }

        return $component->make($customField)
            ->columnSpan($customField->width->getSpanValue());
    }
}
