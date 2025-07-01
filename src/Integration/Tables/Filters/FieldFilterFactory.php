<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Tables\Filters;

use Filament\Tables\Filters\BaseFilter;
use Illuminate\Contracts\Container\BindingResolutionException;
use InvalidArgumentException;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Integration\AbstractComponentFactory;
use Relaticle\CustomFields\Models\CustomField;
use RuntimeException;

/**
 * @extends AbstractComponentFactory<FilterInterface, BaseFilter>
 */
final class FieldFilterFactory extends AbstractComponentFactory
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
     * @throws BindingResolutionException
     */
    public function create(CustomField $customField): BaseFilter
    {
        $customFieldType = $customField->getFieldTypeValue();

        if (! isset($this->componentMap[$customFieldType])) {
            throw new InvalidArgumentException("No filter registered for custom field type: {$customFieldType}");
        }

        $filterClass = $this->componentMap[$customFieldType];

        // Use inherited caching mechanism
        $component = $this->getOrCreateInstance($filterClass, FilterInterface::class);

        return $component->make($customField);
    }

    /**
     * Get or create cached instance.
     */
    private function getOrCreateInstance(string $filterClass, string $expectedInterface): object
    {
        if (! isset($this->instanceCache[$filterClass])) {
            $component = $this->container->make($filterClass);

            if (! $component instanceof $expectedInterface) {
                throw new RuntimeException("Component class {$filterClass} must implement {$expectedInterface}");
            }

            $this->instanceCache[$filterClass] = $component;
        }

        return $this->instanceCache[$filterClass];
    }
}
