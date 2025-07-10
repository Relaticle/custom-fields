<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Actions\Imports\ColumnConfigurators;

use Carbon\Carbon;
use Exception;
use Filament\Actions\Imports\ImportColumn;
use Relaticle\CustomFields\Contracts\FieldImportExportInterface;
use Relaticle\CustomFields\Facades\CustomFieldsType;
use Relaticle\CustomFields\Models\CustomField;

/**
 * Configures basic columns based on the custom field type.
 */
final class BasicColumnConfigurator implements ColumnConfiguratorInterface
{
    /**
     * Configure a basic import column based on a custom field.
     *
     * @param  ImportColumn  $column  The column to configure
     * @param  CustomField  $customField  The custom field to base configuration on
     */
    public function configure(ImportColumn $column, CustomField $customField): void
    {
        // Check if field type implements import/export interface
        $fieldTypeInstance = CustomFieldsType::getFieldTypeInstance($customField->type);

        if ($fieldTypeInstance instanceof FieldImportExportInterface) {
            // Let the field type configure itself
            $fieldTypeInstance->configureImportColumn($column);

            // Set example if provided
            $example = $fieldTypeInstance->getImportExample();
            if ($example !== null) {
                $column->example($example);
            }

            return;
        }

        // Apply default configuration based on data type
        match ($customField->typeData->dataType->value) {
            'numeric' => $column->numeric(),
            'boolean' => $column->boolean(),
            'date' => $this->configureDateColumn($column),
            'date_time' => $this->configureDateTimeColumn($column),
            default => $this->setExampleValue($column, $customField),
        };
    }

    /**
     * Configure a date column with proper parsing.
     */
    private function configureDateColumn(ImportColumn $column): void
    {
        $column->castStateUsing(function ($state): ?string {
            if (blank($state)) {
                return null;
            }

            try {
                return Carbon::parse($state)->format('Y-m-d');
            } catch (Exception) {
                return null;
            }
        });
    }

    /**
     * Configure a datetime column with proper parsing.
     */
    private function configureDateTimeColumn(ImportColumn $column): void
    {
        $column->castStateUsing(function ($state): ?string {
            if (blank($state)) {
                return null;
            }

            try {
                return Carbon::parse($state)->format('Y-m-d H:i:s');
            } catch (Exception) {
                return null;
            }
        });
    }

    /**
     * Set example values for a column based on the field type.
     *
     * @param  ImportColumn  $column  The column to set example for
     * @param  CustomField  $customField  The custom field to extract example values from
     */
    private function setExampleValue(ImportColumn $column, CustomField $customField): void
    {
        // Generate appropriate example values based on field type
        $example = match ($customField->type) {
            'text' => 'Sample text',
            'number' => '42',
            'currency' => '99.99',
            'checkbox', 'toggle' => 'Yes',
            'date' => now()->format('Y-m-d'),
            'datetime' => now()->format('Y-m-d H:i:s'),
            'textarea' => 'Sample longer text with multiple words',
            'rich_editor', 'markdown_editor' => "# Sample Header\nSample content with **formatting**",
            'link' => 'https://example.com',
            'color_picker' => '#3366FF',
            default => null,
        };

        if ($example !== null) {
            $column->example($example);
        }
    }
}
