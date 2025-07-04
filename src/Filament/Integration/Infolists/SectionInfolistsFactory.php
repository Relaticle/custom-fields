<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Infolists;

use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use InvalidArgumentException;
use Relaticle\CustomFields\Enums\CustomFieldSectionType;
use Relaticle\CustomFields\Filament\Integration\AbstractComponentFactory;
use Relaticle\CustomFields\Filament\Integration\Infolists\Sections\FieldsetInfolistsComponent;
use Relaticle\CustomFields\Filament\Integration\Infolists\Sections\HeadlessInfolistsComponent;
use Relaticle\CustomFields\Filament\Integration\Infolists\Sections\SectionInfolistsComponent;
use Relaticle\CustomFields\Models\CustomFieldSection;
use RuntimeException;

/**
 * @extends AbstractComponentFactory<SectionInfolistsComponentInterface, Section|Fieldset|Grid>
 */
final class SectionInfolistsFactory extends AbstractComponentFactory
{
    /**
     * @var array<string, class-string<SectionInfolistsComponentInterface>>
     */
    private array $componentMap = [
        CustomFieldSectionType::SECTION->value => SectionInfolistsComponent::class,
        CustomFieldSectionType::FIELDSET->value => FieldsetInfolistsComponent::class,
        CustomFieldSectionType::HEADLESS->value => HeadlessInfolistsComponent::class,
    ];

    public function create(CustomFieldSection $customFieldSection): Section|Fieldset|Grid
    {
        $customFieldSectionType = $customFieldSection->type->value;

        if (! isset($this->componentMap[$customFieldSectionType])) {
            throw new InvalidArgumentException("No section infolists component registered for custom field type: {$customFieldSectionType}");
        }

        $componentClass = $this->componentMap[$customFieldSectionType];

        // Use inherited caching mechanism
        $component = $this->getOrCreateInstance($componentClass, SectionInfolistsComponentInterface::class);

        return $component->make($customFieldSection);
    }

    /**
     * Get or create cached instance.
     */
    private function getOrCreateInstance(string $componentClass, string $expectedInterface): object
    {
        if (! isset($this->instanceCache[$componentClass])) {
            $component = $this->container->make($componentClass);

            if (! $component instanceof $expectedInterface) {
                throw new RuntimeException("Component class {$componentClass} must implement {$expectedInterface}");
            }

            $this->instanceCache[$componentClass] = $component;
        }

        return $this->instanceCache[$componentClass];
    }
}
