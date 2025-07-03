<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\FieldTypes\Concerns;

use Relaticle\CustomFields\Enums\CustomFieldValidationRule;
use Relaticle\CustomFields\Enums\FieldDataType;

/**
 * Delegates validation rules and storage column determination to the field's data type.
 * This trait reduces duplication by having field types inherit behavior from their data type.
 */
trait DelegatesValidationToDataType
{
    /**
     * Get the allowed validation rules for this field type.
     * Delegates to the data type for consistency.
     *
     * @return array<int, CustomFieldValidationRule>
     */
    public function getAllowedValidationRules(): array
    {
        return $this->getDataType()->getValidationRules();
    }

    /**
     * Get the storage column name for this field type.
     * Delegates to the data type but can be overridden for special cases.
     */
    public function getStorageColumn(): string
    {
        return $this->getDataType()->getStorageColumn();
    }

    /**
     * Get the data type this field uses for storage and validation.
     * This method must be implemented by the class using this trait.
     */
    abstract public function getDataType(): FieldDataType;
}
