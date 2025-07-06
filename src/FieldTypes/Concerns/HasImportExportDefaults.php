<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\FieldTypes\Concerns;

use Filament\Actions\Imports\ImportColumn;

/**
 * ABOUTME: Provides default import/export implementations based on FieldDataType
 * ABOUTME: Field types can override these methods when they need custom behavior
 */
trait HasImportExportDefaults
{
    /**
     * Get an example value for import templates.
     * Returns null by default, which means the system will generate based on FieldDataType.
     */
    public function getImportExample(): ?string
    {
        return null;
    }

    /**
     * Configure import column behavior.
     * Empty by default as most configuration is handled by FieldDataType.
     *
     * @param  ImportColumn  $column  The import column to configure
     */
    public function configureImportColumn(ImportColumn $column): void
    {
        // Most field types don't need additional configuration
        // Override this method when needed
    }

    /**
     * Transform a value during import.
     * Returns the value unchanged by default.
     *
     * @param  mixed  $value  The raw import value
     * @return mixed The transformed value
     */
    public function transformImportValue(mixed $value): mixed
    {
        return $value;
    }

    /**
     * Transform a value during export.
     * Returns the value unchanged by default.
     *
     * @param  mixed  $value  The raw database value
     * @return mixed The transformed export value
     */
    public function transformExportValue(mixed $value): mixed
    {
        return $value;
    }
}
