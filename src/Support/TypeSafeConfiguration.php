<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Support;

use Illuminate\Support\Collection;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Enums\FieldCategory;
use Relaticle\CustomFields\Enums\Operator;
use Relaticle\CustomFields\Integration\Forms\Components\CheckboxComponent;
use Relaticle\CustomFields\Integration\Forms\Components\CheckboxListComponent;
use Relaticle\CustomFields\Integration\Forms\Components\ColorPickerComponent;
use Relaticle\CustomFields\Integration\Forms\Components\CurrencyComponent;
use Relaticle\CustomFields\Integration\Forms\Components\DateComponent;
use Relaticle\CustomFields\Integration\Forms\Components\DateTimeComponent;
use Relaticle\CustomFields\Integration\Forms\Components\LinkComponent;
use Relaticle\CustomFields\Integration\Forms\Components\MarkdownEditorComponent;
use Relaticle\CustomFields\Integration\Forms\Components\MultiSelectComponent;
use Relaticle\CustomFields\Integration\Forms\Components\NumberComponent;
use Relaticle\CustomFields\Integration\Forms\Components\RadioComponent;
use Relaticle\CustomFields\Integration\Forms\Components\RichEditorComponent;
use Relaticle\CustomFields\Integration\Forms\Components\SelectComponent;
use Relaticle\CustomFields\Integration\Forms\Components\TagsInputComponent;
use Relaticle\CustomFields\Integration\Forms\Components\TextareaFieldComponent;
use Relaticle\CustomFields\Integration\Forms\Components\TextInputComponent;
use Relaticle\CustomFields\Integration\Forms\Components\ToggleButtonsComponent;
use Relaticle\CustomFields\Integration\Forms\Components\ToggleComponent;

/**
 * Type-safe configuration class for custom field metadata.
 *
 * This readonly class provides immutable, type-safe access to field type
 * configurations, validation rules, and metadata mappings.
 */
readonly class TypeSafeConfiguration
{
    /**
     * Field type to component class mapping.
     *
     * @var array<string, class-string>
     */
    public array $componentMapping;

    /**
     * Field type to validation rules mapping.
     *
     * @var array<string, array<int, string>>
     */
    public array $validationMapping;

    /**
     * Field category to operator mapping.
     *
     * @var array<string, array<int, Operator>>
     */
    public array $operatorMapping;

    /**
     * Icon configuration for field types.
     *
     * @var array<string, string>
     */
    public array $iconConfiguration;

    public function __construct()
    {
        $this->componentMapping = $this->buildComponentMapping();
        $this->validationMapping = $this->buildValidationMapping();
        $this->operatorMapping = $this->buildOperatorMapping();
        $this->iconConfiguration = CustomFieldType::icons();
    }

    /**
     * Get field types by category.
     *
     * @return Collection<string, Collection<int, CustomFieldType>>
     */
    public function getFieldTypesByCategory(): Collection
    {
        return collect(CustomFieldType::cases())
            ->groupBy(fn (CustomFieldType $type): FieldCategory => $type->getCategory());
    }

    /**
     * Get optionable field types.
     *
     * @return Collection<int, CustomFieldType>
     */
    public function getOptionableTypes(): Collection
    {
        return CustomFieldType::optionables();
    }

    /**
     * Get searchable field types.
     *
     * @return Collection<int, CustomFieldType>
     */
    public function getSearchableTypes(): Collection
    {
        return CustomFieldType::searchables();
    }

    /**
     * Get encryptable field types.
     *
     * @return Collection<int, CustomFieldType>
     */
    public function getEncryptableTypes(): Collection
    {
        return CustomFieldType::encryptables();
    }

    /**
     * Get filterable field types.
     *
     * @return Collection<int, CustomFieldType>
     */
    public function getFilterableTypes(): Collection
    {
        return CustomFieldType::filterable();
    }

    /**
     * Check if a field type is numeric.
     */
    public function isNumericType(CustomFieldType $type): bool
    {
        return $type->isNumeric();
    }

    /**
     * Check if a field type supports multiple values.
     */
    public function isMultiValueType(CustomFieldType $type): bool
    {
        return $type->hasMultipleValues();
    }

    /**
     * Get compatible operators for a field type.
     *
     * @return array<int, Operator>
     */
    public function getCompatibleOperators(CustomFieldType $type): array
    {
        return $type->getCompatibleOperators();
    }

    /**
     * Build component class mapping for field types.
     *
     * @return array<string, class-string>
     */
    private function buildComponentMapping(): array
    {
        return [
            CustomFieldType::TEXT->value => TextInputComponent::class,
            CustomFieldType::TEXTAREA->value => TextareaFieldComponent::class,
            CustomFieldType::NUMBER->value => NumberComponent::class,
            CustomFieldType::CURRENCY->value => CurrencyComponent::class,
            CustomFieldType::DATE->value => DateComponent::class,
            CustomFieldType::DATE_TIME->value => DateTimeComponent::class,
            CustomFieldType::CHECKBOX->value => CheckboxComponent::class,
            CustomFieldType::TOGGLE->value => ToggleComponent::class,
            CustomFieldType::SELECT->value => SelectComponent::class,
            CustomFieldType::MULTI_SELECT->value => MultiSelectComponent::class,
            CustomFieldType::RADIO->value => RadioComponent::class,
            CustomFieldType::CHECKBOX_LIST->value => CheckboxListComponent::class,
            CustomFieldType::RICH_EDITOR->value => RichEditorComponent::class,
            CustomFieldType::MARKDOWN_EDITOR->value => MarkdownEditorComponent::class,
            CustomFieldType::TAGS_INPUT->value => TagsInputComponent::class,
            CustomFieldType::COLOR_PICKER->value => ColorPickerComponent::class,
            CustomFieldType::TOGGLE_BUTTONS->value => ToggleButtonsComponent::class,
            CustomFieldType::LINK->value => LinkComponent::class,
        ];
    }

    /**
     * Build validation rules mapping for field types.
     *
     * @return array<string, array<int, string>>
     */
    private function buildValidationMapping(): array
    {
        $mapping = [];

        foreach (CustomFieldType::cases() as $type) {
            $mapping[$type->value] = array_map(
                fn ($rule) => $rule->value,
                $type->allowedValidationRules()
            );
        }

        return $mapping;
    }

    /**
     * Build operator mapping for field categories.
     *
     * @return array<string, array<int, Operator>>
     */
    private function buildOperatorMapping(): array
    {
        $mapping = [];

        foreach (FieldCategory::cases() as $category) {
            $mapping[$category->value] = $category->getCompatibleOperators();
        }

        return $mapping;
    }
}
