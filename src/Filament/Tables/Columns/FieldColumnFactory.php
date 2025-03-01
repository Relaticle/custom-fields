<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Tables\Columns;

use Filament\Tables\Columns\Column;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Models\CustomField;
use RuntimeException;

final class FieldColumnFactory
{
    /**
     * @var array<class-string<ColumnInterface>, ColumnInterface>
     */
    private array $instanceCache = [];

    public function __construct(private readonly Container $container) {}

    private function componentMap(CustomFieldType $type): string
    {
        return match ($type) {
            CustomFieldType::SELECT, CustomFieldType::RADIO => SingleValueColumn::class,
            CustomFieldType::COLOR_PICKER => ColorColumn::class,
            CustomFieldType::MULTI_SELECT, CustomFieldType::TOGGLE_BUTTONS, CustomFieldType::CHECKBOX_LIST => MultiValueColumn::class,
            CustomFieldType::CHECKBOX, CustomFieldType::TOGGLE => IconColumn::class,
            CustomFieldType::DATE, CustomFieldType::DATE_TIME => DateTimeColumn::class,
            default => TextColumn::class,
        };
    }

    /**
     * @throws BindingResolutionException
     */
    public function create(CustomField $customField): Column
    {
        $componentClass = $this->componentMap($customField->type);

        if (! isset($this->instanceCache[$componentClass])) {
            $component = $this->container->make($componentClass);

            if (! $component instanceof ColumnInterface) {
                throw new RuntimeException("Component class {$componentClass} must implement FieldColumnInterface");
            }

            $this->instanceCache[$componentClass] = $component;
        } else {
            $component = $this->instanceCache[$componentClass];
        }

        return $component->make($customField)
            ->columnSpan($customField->width->getSpanValue());
    }
}
