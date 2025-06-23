<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Enums;

/**
 * Field categories for unified classification system.
 */
enum FieldCategory: string
{
    case TEXT = 'text';
    case NUMERIC = 'numeric';
    case DATE = 'date';
    case BOOLEAN = 'boolean';
    case SINGLE_OPTION = 'single_option';
    case MULTI_OPTION = 'multi_option';

    /**
     * Check if this category represents optionable fields.
     */
    public function isOptionable(): bool
    {
        return in_array($this, [
            self::SINGLE_OPTION,
            self::MULTI_OPTION,
        ]);
    }

    /**
     * Check if this category supports multiple values.
     */
    public function hasMultipleValues(): bool
    {
        return $this === self::MULTI_OPTION;
    }

    /**
     * Get compatible operators for this field category.
     */
    public function getCompatibleOperators(): array
    {
        return match ($this) {
            self::TEXT => [
                Operator::EQUALS,
                Operator::NOT_EQUALS,
                Operator::CONTAINS,
                Operator::NOT_CONTAINS,
                Operator::IS_EMPTY,
                Operator::IS_NOT_EMPTY,
            ],
            self::NUMERIC, self::DATE => [
                Operator::EQUALS,
                Operator::NOT_EQUALS,
                Operator::GREATER_THAN,
                Operator::LESS_THAN,
                Operator::IS_EMPTY,
                Operator::IS_NOT_EMPTY,
            ],
            self::BOOLEAN => [
                Operator::EQUALS,
                Operator::IS_EMPTY,
                Operator::IS_NOT_EMPTY,
            ],
            self::SINGLE_OPTION => [
                Operator::EQUALS,
                Operator::NOT_EQUALS,
                Operator::IS_EMPTY,
                Operator::IS_NOT_EMPTY,
            ],
            self::MULTI_OPTION => [
                Operator::CONTAINS,
                Operator::NOT_CONTAINS,
                Operator::IS_EMPTY,
                Operator::IS_NOT_EMPTY,
            ],
        };
    }

    /**
     * Get operator values formatted for Filament select options.
     */
    public function getCompatibleOperatorOptions(): array
    {
        return collect($this->getCompatibleOperators())
            ->mapWithKeys(fn (Operator $operator) => [$operator->value => $operator->getLabel()])
            ->toArray();
    }
}