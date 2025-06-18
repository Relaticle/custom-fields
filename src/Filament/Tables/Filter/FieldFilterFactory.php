<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Tables\Filter;

use Filament\Tables\Filters\BaseFilter;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Models\CustomField;
use RuntimeException;

final class FieldFilterFactory
{
    /**
     * @var array<string, class-string<FilterInterface>>
     */
    private array $componentMap = [
        CustomFieldType::SELECT->value => SelectFilter::class,
        CustomFieldType::MULTI_SELECT->value => SelectFilter::class,
        CustomFieldType::CHECKBOX->value => TernaryFilter::class,
        CustomFieldType::CHECKBOX_LIST->value => SelectFilter::class,
        CustomFieldType::TOGGLE->value => TernaryFilter::class,
        CustomFieldType::TOGGLE_BUTTONS->value => SelectFilter::class,
        CustomFieldType::RADIO->value => SelectFilter::class,
    ];

    /**
     * @var array<class-string<FilterInterface>, FilterInterface>
     */
    private array $instanceCache = [];

    public function __construct(private readonly Container $container) {}

    /**
     * @throws BindingResolutionException
     */
    public function create(CustomField $customField): BaseFilter
    {
        $customFieldType = $customField->type->value;

        if (! isset($this->componentMap[$customFieldType])) {
            throw new InvalidArgumentException("No filter registered for custom field type: {$customFieldType}");
        }

        $filterClass = $this->componentMap[$customFieldType];

        if (! isset($this->instanceCache[$filterClass])) {
            $component = $this->container->make($filterClass);

            if (! $component instanceof FilterInterface) {
                throw new RuntimeException("Component class {$filterClass} must implement FieldFilterInterface");
            }

            $this->instanceCache[$filterClass] = $component;
        } else {
            $component = $this->instanceCache[$filterClass];
        }

        return $component->make($customField);
    }
}
