<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts;

use Relaticle\CustomFields\Enums\CustomFieldValidationRule;
use Relaticle\CustomFields\Enums\FieldCategory;

/**
 * Contract for defining custom field types that can be registered dynamically.
 */
interface FieldTypeDefinitionInterface
{
    /**
     * Get the unique identifier for this field type.
     * This should be unique across all field types.
     */
    public function getKey(): string;

    /**
     * Get the human-readable label for this field type.
     */
    public function getLabel(): string;

    /**
     * Get the icon for this field type (MDI icon name).
     */
    public function getIcon(): string;

    /**
     * Get the field category this type belongs to.
     */
    public function getCategory(): FieldCategory;

    /**
     * Get the allowed validation rules for this field type.
     *
     * @return array<int, CustomFieldValidationRule>
     */
    public function getAllowedValidationRules(): array;

    /**
     * Get the form component class for this field type.
     * Must implement FieldComponentInterface.
     *
     * @return class-string
     */
    public function getFormComponentClass(): string;

    /**
     * Get the table column class for this field type.
     * Must implement ColumnInterface.
     *
     * @return class-string
     */
    public function getTableColumnClass(): string;

    /**
     * Get the infolist entry class for this field type.
     * Must implement FieldInfolistsComponentInterface.
     *
     * @return class-string
     */
    public function getInfolistEntryClass(): string;

    /**
     * Determine if this field type is searchable in tables.
     */
    public function isSearchable(): bool;

    /**
     * Determine if this field type is filterable in tables.
     */
    public function isFilterable(): bool;

    /**
     * Determine if this field type supports encryption.
     */
    public function isEncryptable(): bool;

    /**
     * Get the priority for field type ordering in the admin panel.
     * Lower numbers appear first.
     */
    public function getPriority(): int;
}
