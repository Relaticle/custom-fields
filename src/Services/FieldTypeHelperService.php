<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services;

use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Enums\Operator;
use Relaticle\CustomFields\Models\CustomField;

/**
 * Service to handle field type operations for both built-in and custom field types.
 * This extracts field type logic from the CustomField model to keep it focused.
 */
final readonly class FieldTypeHelperService
{
    public function __construct(
        private FieldTypeRegistryService $fieldTypeRegistry
    ) {}

    /**
     * Get the icon for a field type (works with both enum and string types).
     */
    public function getIcon(CustomFieldType|string $type): string
    {
        // If type is an enum, use its getIcon method
        if ($type instanceof CustomFieldType) {
            return $type->getIcon();
        }
        // If type is a string (custom field type), get icon from registry
        $fieldTypeConfig = $this->fieldTypeRegistry->getFieldType($type);
        if ($fieldTypeConfig !== null) {
            return $fieldTypeConfig['icon'];
        }

        // Fallback icon for unknown types
        return 'heroicon-o-question-mark-circle';
    }

    /**
     * Get the label for a field type (works with both enum and string types).
     */
    public function getLabel(CustomFieldType|string $type): string
    {
        // If type is an enum, use its getLabel method
        if ($type instanceof CustomFieldType) {
            return $type->getLabel();
        }
        // If type is a string (custom field type), get label from registry
        $fieldTypeConfig = $this->fieldTypeRegistry->getFieldType($type);
        if ($fieldTypeConfig !== null) {
            return $fieldTypeConfig['label'];
        }

        // Fallback to the raw type value
        return is_string($type) ? ucwords(str_replace(['_', '-'], ' ', $type)) : 'Unknown';
    }

    /**
     * Get the category for a field type (works with both enum and string types).
     */
    public function getCategory(CustomFieldType|string $type): string
    {
        // If type is an enum, use its getCategory method
        if ($type instanceof CustomFieldType) {
            return $type->getCategory()->value;
        }
        // If type is a string (custom field type), get category from registry
        $fieldTypeConfig = $this->fieldTypeRegistry->getFieldType($type);
        if ($fieldTypeConfig !== null) {
            return $fieldTypeConfig['category'];
        }

        // Fallback to text category
        return 'text';
    }

    /**
     * Check if a field type is optionable (works with both enum and string types).
     */
    public function isOptionable(CustomFieldType|string $type): bool
    {
        // If type is an enum, use its isOptionable method
        if ($type instanceof CustomFieldType) {
            return $type->isOptionable();
        }

        // If type is a string (custom field type), get category from registry and check
        $category = $this->getCategory($type);

        return in_array($category, ['single_option', 'multi_option'], true);
    }

    /**
     * Check if a field type is boolean (works with both enum and string types).
     */
    public function isBoolean(CustomFieldType|string $type): bool
    {
        // If type is an enum, use its isBoolean method
        if ($type instanceof CustomFieldType) {
            return $type->isBoolean();
        }

        // If type is a string (custom field type), check category
        return $this->getCategory($type) === 'boolean';
    }

    /**
     * Check if a field type has multiple values (works with both enum and string types).
     */
    public function hasMultipleValues(CustomFieldType|string $type): bool
    {
        // If type is an enum, use its hasMultipleValues method
        if ($type instanceof CustomFieldType) {
            return $type->hasMultipleValues();
        }

        // If type is a string (custom field type), check category
        return $this->getCategory($type) === 'multi_option';
    }

    /**
     * Get compatible operators for a field type (works with both enum and string types).
     * @return array<Operator>
     */
    public function getCompatibleOperators(CustomFieldType|string $type): array
    {
        // If type is an enum, use its getCompatibleOperators method
        if ($type instanceof CustomFieldType) {
            return $type->getCompatibleOperators();
        }

        // If type is a string (custom field type), get operators based on category
        $category = $this->getCategory($type);

        // Map categories to operators (based on FieldCategory enum logic)
        return match ($category) {
            'text' => [
                Operator::EQUALS,
                Operator::NOT_EQUALS,
                Operator::CONTAINS,
                Operator::NOT_CONTAINS,
            ],
            'numeric', 'date' => [
                Operator::EQUALS,
                Operator::NOT_EQUALS,
                Operator::GREATER_THAN,
                Operator::LESS_THAN,
            ],
            default => [
                Operator::EQUALS,
                Operator::NOT_EQUALS,
            ],
        };
    }

    /**
     * Get the field type value as string (works with both enum and string types).
     */
    public function getValue(CustomFieldType|string $type): string
    {
        return $type instanceof CustomFieldType
            ? $type->value
            : $type;
    }

    /**
     * Convenience methods for working with CustomField models.
     */
    public function getFieldIcon(CustomField $field): string
    {
        return $this->getIcon($field->type);
    }

    public function getFieldLabel(CustomField $field): string
    {
        return $this->getLabel($field->type);
    }

    public function getFieldCategory(CustomField $field): string
    {
        return $this->getCategory($field->type);
    }

    public function isFieldOptionable(CustomField $field): bool
    {
        return $this->isOptionable($field->type);
    }

    public function isFieldBoolean(CustomField $field): bool
    {
        return $this->isBoolean($field->type);
    }

    public function fieldHasMultipleValues(CustomField $field): bool
    {
        return $this->hasMultipleValues($field->type);
    }

    /**
     * @return array<Operator>
     */
    public function getFieldCompatibleOperators(CustomField $field): array
    {
        return $this->getCompatibleOperators($field->type);
    }

    public function getFieldValue(CustomField $field): string
    {
        return $this->getValue($field->type);
    }
}
