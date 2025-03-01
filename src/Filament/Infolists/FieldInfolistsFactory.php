<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Infolists;

use Filament\Infolists\Components\Entry;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Filament\Infolists\Fields\BooleanEntry;
use Relaticle\CustomFields\Filament\Infolists\Fields\ColorEntry;
use Relaticle\CustomFields\Filament\Infolists\Fields\DateTimeEntry;
use Relaticle\CustomFields\Filament\Infolists\Fields\HtmlEntry;
use Relaticle\CustomFields\Filament\Infolists\Fields\MultiValueEntry;
use Relaticle\CustomFields\Filament\Infolists\Fields\SingleValueEntry;
use Relaticle\CustomFields\Filament\Infolists\Fields\TagsEntry;
use Relaticle\CustomFields\Filament\Infolists\Fields\TextEntry;
use Relaticle\CustomFields\Models\CustomField;
use RuntimeException;

final class FieldInfolistsFactory
{
    /**
     * @var array<string, class-string<FieldInfolistsComponentInterface>>
     */
    private array $componentMap = [
        CustomFieldType::TEXT->value => TextEntry::class,
        CustomFieldType::TOGGLE->value => BooleanEntry::class,
        CustomFieldType::LINK->value => TextEntry::class,
        CustomFieldType::SELECT->value => SingleValueEntry::class,
        CustomFieldType::NUMBER->value => TextEntry::class,
        CustomFieldType::CHECKBOX->value => BooleanEntry::class,
        CustomFieldType::CHECKBOX_LIST->value => MultiValueEntry::class,
        CustomFieldType::RADIO->value => SingleValueEntry::class,
        CustomFieldType::RICH_EDITOR->value => HtmlEntry::class,
        CustomFieldType::MARKDOWN_EDITOR->value => TextEntry::class,
        CustomFieldType::TAGS_INPUT->value => TagsEntry::class,
        CustomFieldType::COLOR_PICKER->value => ColorEntry::class,
        CustomFieldType::TOGGLE_BUTTONS->value => MultiValueEntry::class,
        CustomFieldType::TEXTAREA->value => TextEntry::class,
        CustomFieldType::CURRENCY->value => TextEntry::class,
        CustomFieldType::DATE->value => TextEntry::class,
        CustomFieldType::MULTI_SELECT->value => MultiValueEntry::class,
        CustomFieldType::DATE_TIME->value => DateTimeEntry::class,
    ];

    /**
     * @var array<class-string<FieldInfolistsComponentInterface>, FieldInfolistsComponentInterface>
     */
    private array $instanceCache = [];

    public function __construct(private readonly Container $container) {}

    public function create(CustomField $customField): Entry
    {
        $customFieldType = $customField->type->value;

        if (! isset($this->componentMap[$customFieldType])) {
            throw new InvalidArgumentException("No infolists component registered for custom field type: {$customFieldType}");
        }

        $componentClass = $this->componentMap[$customFieldType];

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
