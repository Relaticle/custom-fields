<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Infolists;

use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Relaticle\CustomFields\Enums\CustomFieldSectionType;
use Relaticle\CustomFields\Filament\Infolists\Sections\FieldsetInfolistsComponent;
use Relaticle\CustomFields\Filament\Infolists\Sections\HeadlessInfolistsComponent;
use Relaticle\CustomFields\Filament\Infolists\Sections\SectionInfolistsComponent;
use Relaticle\CustomFields\Models\CustomFieldSection;
use RuntimeException;

final class SectionInfolistsFactory
{
    /**
     * @var array<string, class-string<SectionInfolistsComponentInterface>>
     */
    private array $componentMap = [
        CustomFieldSectionType::SECTION->value => SectionInfolistsComponent::class,
        CustomFieldSectionType::FIELDSET->value => FieldsetInfolistsComponent::class,
        CustomFieldSectionType::HEADLESS->value => HeadlessInfolistsComponent::class,
    ];

    /**
     * @var array<class-string<SectionInfolistsComponentInterface>, SectionInfolistsComponentInterface>
     */
    private array $instanceCache = [];

    public function __construct(private readonly Container $container) {}

    public function create(CustomFieldSection $customFieldSection): Section | Fieldset | Grid
    {
        $customFieldSectionType = $customFieldSection->type->value;

        if (! isset($this->componentMap[$customFieldSectionType])) {
            throw new InvalidArgumentException("No section infolists component registered for custom field type: {$customFieldSectionType}");
        }

        $componentClass = $this->componentMap[$customFieldSectionType];

        if (! isset($this->instanceCache[$componentClass])) {
            $component = $this->container->make($componentClass);

            if (! $component instanceof SectionInfolistsComponentInterface) {
                throw new RuntimeException("Infolists component class {$componentClass} must implement SectionInfolistsComponentInterface");
            }

            $this->instanceCache[$componentClass] = $component;
        } else {
            $component = $this->instanceCache[$componentClass];
        }

        return $component->make($customFieldSection);
    }
}
