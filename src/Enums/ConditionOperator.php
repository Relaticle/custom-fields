<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum ConditionOperator: string implements HasLabel
{
    case EQUALS = '=';
    case NOT_EQUALS = '!=';
    case GREATER_THAN = '>';
    case LESS_THAN = '<';
    case GREATER_OR_EQUAL = '>=';
    case LESS_OR_EQUAL = '<=';
    case CONTAINS = 'contains';
    case NOT_CONTAINS = 'not_contains';
    case STARTS_WITH = 'starts_with';
    case ENDS_WITH = 'ends_with';
    case IS_EMPTY = 'empty';
    case IS_NOT_EMPTY = 'not_empty';
    case IN = 'in';
    case NOT_IN = 'not_in';
    case IS_CHECKED = 'is_checked';
    case IS_UNCHECKED = 'is_unchecked';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::EQUALS => 'Equals',
            self::NOT_EQUALS => 'Not equals',
            self::GREATER_THAN => 'Greater than',
            self::LESS_THAN => 'Less than',
            self::GREATER_OR_EQUAL => 'Greater or equal',
            self::LESS_OR_EQUAL => 'Less or equal',
            self::CONTAINS => 'Contains',
            self::NOT_CONTAINS => 'Not contains',
            self::STARTS_WITH => 'Starts with',
            self::ENDS_WITH => 'Ends with',
            self::IS_EMPTY => 'Is empty',
            self::IS_NOT_EMPTY => 'Is not empty',
            self::IN => 'In list',
            self::NOT_IN => 'Not in list',
            self::IS_CHECKED => 'Is checked',
            self::IS_UNCHECKED => 'Is unchecked',
        };
    }

    /**
     * Get all options for select components.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }

    /**
     * Get common operators (most frequently used).
     *
     * @return array<string, string>
     */
    public static function commonOptions(): array
    {
        return [
            self::EQUALS->value => self::EQUALS->getLabel(),
            self::NOT_EQUALS->value => self::NOT_EQUALS->getLabel(),
            self::GREATER_THAN->value => self::GREATER_THAN->getLabel(),
            self::LESS_THAN->value => self::LESS_THAN->getLabel(),
            self::GREATER_OR_EQUAL->value => self::GREATER_OR_EQUAL->getLabel(),
            self::LESS_OR_EQUAL->value => self::LESS_OR_EQUAL->getLabel(),
            self::CONTAINS->value => self::CONTAINS->getLabel(),
            self::NOT_CONTAINS->value => self::NOT_CONTAINS->getLabel(),
            self::IS_EMPTY->value => self::IS_EMPTY->getLabel(),
            self::IS_NOT_EMPTY->value => self::IS_NOT_EMPTY->getLabel(),
        ];
    }

    /**
     * Check if the operator requires a value input.
     */
    public function requiresValue(): bool
    {
        return ! in_array($this, [self::IS_EMPTY, self::IS_NOT_EMPTY, self::IS_CHECKED, self::IS_UNCHECKED]);
    }

    /**
     * Check if the operator supports multiple values (comma-separated).
     */
    public function supportsMultipleValues(): bool
    {
        return in_array($this, [self::IN, self::NOT_IN]);
    }

    /**
     * Get operators suitable for specific field types.
     *
     * @return array<ConditionOperator>
     */
    public static function forFieldType(CustomFieldType $fieldType): array
    {
        return match ($fieldType) {
            CustomFieldType::TEXT,
            CustomFieldType::TEXTAREA,
            CustomFieldType::LINK,
            CustomFieldType::RICH_EDITOR,
            CustomFieldType::MARKDOWN_EDITOR,
            CustomFieldType::COLOR_PICKER => [
                self::EQUALS,
                self::NOT_EQUALS,
                self::CONTAINS,
                self::NOT_CONTAINS,
                self::STARTS_WITH,
                self::ENDS_WITH,
                self::IS_EMPTY,
                self::IS_NOT_EMPTY,
                self::IN,
                self::NOT_IN,
            ],

            CustomFieldType::NUMBER,
            CustomFieldType::CURRENCY => [
                self::EQUALS,
                self::NOT_EQUALS,
                self::GREATER_THAN,
                self::LESS_THAN,
                self::GREATER_OR_EQUAL,
                self::LESS_OR_EQUAL,
                self::IS_EMPTY,
                self::IS_NOT_EMPTY,
                self::IN,
                self::NOT_IN,
            ],

            CustomFieldType::DATE,
            CustomFieldType::DATE_TIME => [
                self::EQUALS,
                self::NOT_EQUALS,
                self::GREATER_THAN,
                self::LESS_THAN,
                self::GREATER_OR_EQUAL,
                self::LESS_OR_EQUAL,
                self::IS_EMPTY,
                self::IS_NOT_EMPTY,
            ],

            CustomFieldType::SELECT,
            CustomFieldType::RADIO => [
                self::EQUALS,
                self::NOT_EQUALS,
                self::IS_EMPTY,
                self::IS_NOT_EMPTY,
                self::IN,
                self::NOT_IN,
            ],

            CustomFieldType::MULTI_SELECT,
            CustomFieldType::CHECKBOX_LIST,
            CustomFieldType::TAGS_INPUT,
            CustomFieldType::TOGGLE_BUTTONS => [
                self::CONTAINS,
                self::NOT_CONTAINS,
                self::IS_EMPTY,
                self::IS_NOT_EMPTY,
            ],

            CustomFieldType::TOGGLE,
            CustomFieldType::CHECKBOX => [
                self::IS_CHECKED,
                self::IS_UNCHECKED,
            ],
        };
    }

    /**
     * Evaluate the condition between a field value and expected value.
     */
    public function evaluate(mixed $fieldValue, mixed $expectedValue): bool
    {
        // Handle empty checks first
        if ($this === self::IS_EMPTY) {
            return empty($fieldValue);
        }

        if ($this === self::IS_NOT_EMPTY) {
            return ! empty($fieldValue) && $fieldValue !== '';
        }

        return match ($this) {
            self::EQUALS => $fieldValue == $expectedValue,
            self::NOT_EQUALS => $fieldValue != $expectedValue,
            self::IS_CHECKED => (bool) $fieldValue === true,
            self::IS_UNCHECKED => (bool) $fieldValue === false,
            self::GREATER_THAN => is_numeric($fieldValue) && is_numeric($expectedValue) && (float) $fieldValue > (float) $expectedValue,
            self::LESS_THAN => is_numeric($fieldValue) && is_numeric($expectedValue) && (float) $fieldValue < (float) $expectedValue,
            self::GREATER_OR_EQUAL => is_numeric($fieldValue) && is_numeric($expectedValue) && (float) $fieldValue >= (float) $expectedValue,
            self::LESS_OR_EQUAL => is_numeric($fieldValue) && is_numeric($expectedValue) && (float) $fieldValue <= (float) $expectedValue,
            self::CONTAINS => $this->evaluateContains($fieldValue, $expectedValue),
            self::NOT_CONTAINS => ! $this->evaluateContains($fieldValue, $expectedValue),
            self::STARTS_WITH => $this->evaluateStartsWith($fieldValue, $expectedValue),
            self::ENDS_WITH => $this->evaluateEndsWith($fieldValue, $expectedValue),
            self::IN => in_array($fieldValue, $this->parseMultipleValues($expectedValue)),
            self::NOT_IN => ! in_array($fieldValue, $this->parseMultipleValues($expectedValue)),
            default => false,
        };
    }

    /**
     * Evaluate contains operation for different field types.
     */
    private function evaluateContains(mixed $fieldValue, mixed $expectedValue): bool
    {
        if (is_array($fieldValue)) {
            return in_array($expectedValue, $fieldValue);
        }

        $fieldStr = (string) $fieldValue;
        $expectedStr = (string) $expectedValue;

        return Str::contains($fieldStr, $expectedStr);
    }

    /**
     * Evaluate starts with operation.
     */
    private function evaluateStartsWith(mixed $fieldValue, mixed $expectedValue): bool
    {
        if (is_array($fieldValue)) {
            return false; // Arrays don't start with anything
        }

        $fieldStr = (string) $fieldValue;
        $expectedStr = (string) $expectedValue;

        return Str::startsWith($fieldStr, $expectedStr);
    }

    /**
     * Evaluate ends with operation.
     */
    private function evaluateEndsWith(mixed $fieldValue, mixed $expectedValue): bool
    {
        if (is_array($fieldValue)) {
            return false; // Arrays don't end with anything
        }

        $fieldStr = (string) $fieldValue;
        $expectedStr = (string) $expectedValue;

        return Str::endsWith($fieldStr, $expectedStr);
    }


    /**
     * Parse comma-separated values for IN/NOT_IN operators.
     *
     * @return array<string>
     */
    private function parseMultipleValues(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return array_map('trim', explode(',', (string) $value));
    }
}
