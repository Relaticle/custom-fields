<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Infolists;

use BackedEnum;
use Filament\Infolists\Components\Entry;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\FieldTypeRegistryService;
use RuntimeException;

final class FieldInfolistsFactory
{
    /**
     * @var array<class-string<FieldInfolistsComponentInterface>, FieldInfolistsComponentInterface>
     */
    private array $instanceCache = [];

    public function __construct(
        private readonly Container $container,
        private readonly FieldTypeRegistryService $fieldTypeRegistry
    ) {}

    public function create(CustomField $customField): Entry
    {
        // Handle both enum and string types
        $customFieldType = $customField->type instanceof BackedEnum
            ? $customField->type->value
            : $customField->type;

        $fieldTypeConfig = $this->fieldTypeRegistry->getFieldType($customFieldType);

        if ($fieldTypeConfig === null) {
            throw new InvalidArgumentException("No infolists component registered for custom field type: {$customFieldType}");
        }

        $componentClass = $fieldTypeConfig['infolist_entry'];

        if (! isset($this->instanceCache[$componentClass])) {
            $component = $this->container->make($componentClass);

            if (! $component instanceof FieldInfolistsComponentInterface) {
                throw new RuntimeException("Infolists component class {$componentClass} must implement FieldInfolistsComponentInterface");
            }

            $this->instanceCache[$componentClass] = $component;
        } else {
            $component = $this->instanceCache[$componentClass];
        }

        return $component->make($customField)
            ->columnSpan($customField->width->getSpanValue())
            ->inlineLabel(false);
    }
}
