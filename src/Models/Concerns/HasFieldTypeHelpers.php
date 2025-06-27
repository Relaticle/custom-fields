<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Models\Concerns;

use Relaticle\CustomFields\Enums\Operator;
use Relaticle\CustomFields\Services\FieldTypeHelperService;

/**
 * Trait to provide field type helper methods to CustomField models.
 * This keeps the model clean while providing easy access to field type operations.
 */
trait HasFieldTypeHelpers
{
    /**
     * Get the field type helper service.
     */
    protected function getFieldTypeHelper(): FieldTypeHelperService
    {
        return app(FieldTypeHelperService::class);
    }

    /**
     * Get the icon for this field type.
     */
    public function getFieldTypeIcon(): string
    {
        return $this->getFieldTypeHelper()->getFieldIcon($this);
    }

    /**
     * Get the label for this field type.
     */
    public function getFieldTypeLabel(): string
    {
        return $this->getFieldTypeHelper()->getFieldLabel($this);
    }

    /**
     * Get the category for this field type.
     */
    public function getFieldTypeCategory(): string
    {
        return $this->getFieldTypeHelper()->getFieldCategory($this);
    }

    /**
     * Check if this field type is optionable.
     */
    public function isFieldTypeOptionable(): bool
    {
        return $this->getFieldTypeHelper()->isFieldOptionable($this);
    }

    /**
     * Check if this field type is boolean.
     */
    public function isFieldTypeBoolean(): bool
    {
        return $this->getFieldTypeHelper()->isFieldBoolean($this);
    }

    /**
     * Check if this field type has multiple values.
     */
    public function hasFieldTypeMultipleValues(): bool
    {
        return $this->getFieldTypeHelper()->fieldHasMultipleValues($this);
    }

    /**
     * Get compatible operators for this field type.
     *
     * @return array<int, Operator>
     */
    public function getFieldTypeCompatibleOperators(): array
    {
        return $this->getFieldTypeHelper()->getFieldCompatibleOperators($this);
    }

    /**
     * Get the field type value as string.
     */
    public function getFieldTypeValue(): string
    {
        return $this->getFieldTypeHelper()->getFieldValue($this);
    }
}
