<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services;

use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Enums\FieldCategory;
use Relaticle\CustomFields\Enums\Operator;
use Relaticle\CustomFields\Models\CustomField;

/**
 * 🚀 OPTIMIZED: Enhanced helper service for field type operations
 * Provides unified interface for both built-in and custom field types.
 */
final class FieldTypeHelperService
{
    public function __construct(
        private readonly FieldTypeRegistryService $registry
    ) {}

    /**
     * 🚀 PERFORMANCE: Optimized icon getter with enum delegation
     */
    public function getIcon(CustomFieldType|string $type): string
    {
        // If it's a built-in enum, use its optimized method
        if ($type instanceof CustomFieldType) {
            return $type->getIcon();
        }

        // For custom types, check registry
        $customType = $this->registry->getFieldType($type);
        
        return $customType['icon'] ?? 'mdi-help-circle';
    }

    /**
     * 🚀 PERFORMANCE: Optimized label getter with enum delegation
     */
    public function getLabel(CustomFieldType|string $type): string
    {
        // If it's a built-in enum, use its optimized method
        if ($type instanceof CustomFieldType) {
            return $type->getLabel();
        }

        // For custom types, check registry
        $customType = $this->registry->getFieldType($type);
        
        return $customType['label'] ?? 'Unknown Type';
    }

    /**
     * 🚀 ENHANCED: Category getter with better type safety
     */
    public function getCategory(CustomFieldType|string $type): string
    {
        // If it's a built-in enum, use its category method
        if ($type instanceof CustomFieldType) {
            return $type->getCategory()->value;
        }

        // For custom types, check registry
        $customType = $this->registry->getFieldType($type);
        
        return $customType['category'] ?? 'unknown';
    }

    /**
     * 🚀 ENHANCED: Better category-based type checking
     */
    public function getCategoryEnum(CustomFieldType|string $type): ?FieldCategory
    {
        // If it's a built-in enum, use its category method
        if ($type instanceof CustomFieldType) {
            return $type->getCategory();
        }

        // For custom types, try to convert category string to enum
        $customType = $this->registry->getFieldType($type);
        
        return $customType ? FieldCategory::tryFrom($customType['category']) : null;
    }

    /**
     * 🚀 PERFORMANCE: Optimized optionable check with enum delegation
     */
    public function isOptionable(CustomFieldType|string $type): bool
    {
        // If it's a built-in enum, use its optimized method
        if ($type instanceof CustomFieldType) {
            return $type->isOptionable();
        }

        // For custom types, check based on category
        $category = $this->getCategoryEnum($type);
        
        return $category?->isOptionable() ?? false;
    }

    /**
     * 🚀 PERFORMANCE: Optimized boolean check with enum delegation
     */
    public function isBoolean(CustomFieldType|string $type): bool
    {
        // If it's a built-in enum, use its optimized method
        if ($type instanceof CustomFieldType) {
            return $type->isBoolean();
        }

        // For custom types, check based on category
        $category = $this->getCategoryEnum($type);
        
        return $category === FieldCategory::BOOLEAN;
    }

    /**
     * 🚀 PERFORMANCE: Optimized multiple values check with enum delegation
     */
    public function hasMultipleValues(CustomFieldType|string $type): bool
    {
        // If it's a built-in enum, use its optimized method
        if ($type instanceof CustomFieldType) {
            return $type->hasMultipleValues();
        }

        // For custom types, check based on category
        $category = $this->getCategoryEnum($type);
        
        return $category?->hasMultipleValues() ?? false;
    }

    /**
     * 🚀 PERFORMANCE: Optimized operators with enum delegation
     * 
     * @return array<Operator>
     */
    public function getCompatibleOperators(CustomFieldType|string $type): array
    {
        // If it's a built-in enum, use its optimized method
        if ($type instanceof CustomFieldType) {
            return $type->getCompatibleOperators();
        }

        // For custom types, check based on category
        $category = $this->getCategoryEnum($type);
        
        return $category?->getCompatibleOperators() ?? [
            Operator::EQUALS,
            Operator::NOT_EQUALS,
        ];
    }

    /**
     * 🚀 PERFORMANCE: Optimized value getter
     */
    public function getValue(CustomFieldType|string $type): string
    {
        return $type instanceof CustomFieldType
            ? $type->value
            : $type;
    }

    /**
     * 🚀 NEW: Type-safe converter from string to enum
     */
    public function toEnum(string $type): CustomFieldType|string
    {
        return CustomFieldType::safeFrom($type) ?? $type;
    }

    /**
     * 🚀 NEW: Check if type is built-in
     */
    public function isBuiltInType(CustomFieldType|string $type): bool
    {
        if ($type instanceof CustomFieldType) {
            return true;
        }

        return CustomFieldType::isBuiltInType($type);
    }

    /**
     * 🚀 NEW: Check if type is custom
     */
    public function isCustomType(CustomFieldType|string $type): bool
    {
        if ($type instanceof CustomFieldType) {
            return false;
        }

        return $this->registry->hasFieldType($type) && !CustomFieldType::isBuiltInType($type);
    }

    /**
     * 🚀 NEW: Get all validation rules for a type (built-in or custom)
     * 
     * @return array<string>
     */
    public function getValidationRules(CustomFieldType|string $type): array
    {
        // If it's a built-in enum, get its validation rules
        if ($type instanceof CustomFieldType) {
            return array_map(
                fn ($rule) => $rule->value,
                $type->allowedValidationRules()
            );
        }

        // For custom types, check registry
        $customType = $this->registry->getFieldType($type);
        
        return $customType['validation_rules'] ?? [];
    }

    /**
     * 🚀 NEW: Check if a field type is searchable
     */
    public function isSearchable(CustomFieldType|string $type): bool
    {
        // If it's a built-in enum, check if it's in the searchables collection
        if ($type instanceof CustomFieldType) {
            return CustomFieldType::searchables()->contains($type);
        }

        // For custom types, check registry
        $customType = $this->registry->getFieldType($type);
        
        return $customType['searchable'] ?? false;
    }

    /**
     * 🚀 NEW: Check if a field type is filterable
     */
    public function isFilterable(CustomFieldType|string $type): bool
    {
        // If it's a built-in enum, check if it's in the filterable collection
        if ($type instanceof CustomFieldType) {
            return CustomFieldType::filterable()->contains($type);
        }

        // For custom types, check registry
        $customType = $this->registry->getFieldType($type);
        
        return $customType['filterable'] ?? false;
    }

    /**
     * 🚀 NEW: Check if a field type is encryptable
     */
    public function isEncryptable(CustomFieldType|string $type): bool
    {
        // If it's a built-in enum, check if it's in the encryptables collection
        if ($type instanceof CustomFieldType) {
            return CustomFieldType::encryptables()->contains($type);
        }

        // For custom types, check registry
        $customType = $this->registry->getFieldType($type);
        
        return $customType['encryptable'] ?? false;
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

    public function getFieldCategoryEnum(CustomField $field): ?FieldCategory
    {
        return $this->getCategoryEnum($field->type);
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

    public function isFieldBuiltIn(CustomField $field): bool
    {
        return $this->isBuiltInType($field->type);
    }

    public function isFieldCustom(CustomField $field): bool
    {
        return $this->isCustomType($field->type);
    }

    /**
     * @return array<string>
     */
    public function getFieldValidationRules(CustomField $field): array
    {
        return $this->getValidationRules($field->type);
    }

    public function isFieldSearchable(CustomField $field): bool
    {
        return $this->isSearchable($field->type);
    }

    public function isFieldFilterable(CustomField $field): bool
    {
        return $this->isFilterable($field->type);
    }

    public function isFieldEncryptable(CustomField $field): bool
    {
        return $this->isEncryptable($field->type);
    }
}
