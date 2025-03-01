<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent;

use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Relaticle\CustomFields\Enums\CustomFieldSectionType;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\Sections\FieldsetComponent;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\Sections\HeadlessComponent;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldsComponent\Sections\SectionComponent;
use Relaticle\CustomFields\Models\CustomFieldSection;
use RuntimeException;

final class SectionComponentFactory
{
    /**
     * @var array<string, class-string<SectionComponentInterface>>
     */
    private array $componentMap = [
        CustomFieldSectionType::SECTION->value => SectionComponent::class,
        CustomFieldSectionType::FIELDSET->value => FieldsetComponent::class,
        CustomFieldSectionType::HEADLESS->value => HeadlessComponent::class,
    ];

    /**
     * @var array<class-string<SectionComponentInterface>, SectionComponentInterface>
     */
    private array $instanceCache = [];

    public function __construct(private readonly Container $container) {}

    public function create(CustomFieldSection $customFieldSection): Section | Fieldset | Grid
    {
        $customFieldSectionType = $customFieldSection->type->value;

        if (! isset($this->componentMap[$customFieldSectionType])) {
            throw new InvalidArgumentException("No component registered for custom field type: {$customFieldSectionType}");
        }

        $componentClass = $this->componentMap[$customFieldSectionType];

        if (! isset($this->instanceCache[$componentClass])) {
            $component = $this->container->make($componentClass);

            if (! $component instanceof SectionComponentInterface) {
                throw new RuntimeException("Component class {$componentClass} must implement SectionComponentInterface");
            }

            $this->instanceCache[$componentClass] = $component;
        } else {
            $component = $this->instanceCache[$componentClass];
        }

        return $component->make($customFieldSection);
    }
}
