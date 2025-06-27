<?php

declare(strict_types=1);

namespace App\CustomFields\Types;

use Relaticle\CustomFields\Contracts\FieldTypeDefinitionInterface;
use Relaticle\CustomFields\Enums\CustomFieldValidationRule;
use Relaticle\CustomFields\Enums\FieldCategory;

/**
 * Example custom field type: 5-star rating field.
 *
 * This demonstrates how to create a custom field type that integrates
 * seamlessly with the Relaticle Custom Fields package.
 */
class RatingFieldType implements FieldTypeDefinitionInterface
{
    public function getKey(): string
    {
        return 'rating';
    }

    public function getLabel(): string
    {
        return 'Rating (1-5 Stars)';
    }

    public function getIcon(): string
    {
        return 'mdi-star';
    }

    public function getCategory(): FieldCategory
    {
        return FieldCategory::NUMERIC;
    }

    /**
     * @return array<int, CustomFieldValidationRule>
     */
    public function getAllowedValidationRules(): array
    {
        return [
            CustomFieldValidationRule::REQUIRED,
            CustomFieldValidationRule::MIN,
            CustomFieldValidationRule::MAX,
            CustomFieldValidationRule::INTEGER,
        ];
    }

    public function getFormComponentClass(): string
    {
        return RatingFormComponent::class;
    }

    public function getTableColumnClass(): string
    {
        return RatingTableColumn::class;
    }

    public function getInfolistEntryClass(): string
    {
        return RatingInfolistEntry::class;
    }

    public function isSearchable(): bool
    {
        return false; // Ratings are typically not text-searchable
    }

    public function isFilterable(): bool
    {
        return true; // Can filter by rating range
    }

    public function isEncryptable(): bool
    {
        return false; // Ratings don't need encryption
    }

    public function getPriority(): int
    {
        return 50; // Higher priority than built-in types (lower number = higher priority)
    }
}
