<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts;

use Filament\Actions\Imports\ImportColumn;

/**
 * ABOUTME: Optional interface for field types that need custom import/export handling
 * ABOUTME: Most field types can rely on FieldDataType defaults, only override when needed
 */
interface FieldImportExportInterface
{
    /**
     * Get an example value for import templates.
     * Return null to use the default based on FieldDataType.
     */
    public function getImportExample(): ?string;

    /**
     * Configure import column behavior.
     * Called after basic configuration based on FieldDataType.
     *
     * @param  ImportColumn  $column  The import column to configure
     */
    public function configureImportColumn(ImportColumn $column): void;

    /**
     * Transform a value during import.
     * Return null to use default transformation based on FieldDataType.
     *
     * @param  mixed  $value  The raw import value
     * @return mixed The transformed value
     */
    public function transformImportValue(mixed $value): mixed;

    /**
     * Transform a value during export.
     * Return null to use default transformation based on FieldDataType.
     *
     * @param  mixed  $value  The raw database value
     * @return mixed The transformed export value
     */
    public function transformExportValue(mixed $value): mixed;
}
