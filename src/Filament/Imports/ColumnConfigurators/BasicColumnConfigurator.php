<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Imports\ColumnConfigurators;

use Filament\Actions\Imports\ImportColumn;
use Relaticle\CustomFields\Enums\CustomFieldType;
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
        // Apply specific configuration based on field type
        match ($customField->type) {
            // Numeric types
            CustomFieldType::NUMBER => $column->numeric(),

            // Currency with special formatting
            CustomFieldType::CURRENCY => $this->configureCurrencyColumn($column),

            // Boolean fields
            CustomFieldType::CHECKBOX, CustomFieldType::TOGGLE => $column->boolean(),

            // Date fields
            CustomFieldType::DATE => $column->date(),
            CustomFieldType::DATE_TIME => $column->dateTime(),

            // Default for all other field types
            default => $this->setExampleValue($column, $customField),
        };
    }

    /**
     * Configure a currency column with special formatting.
     *
     * @param  ImportColumn  $column  The column to configure
     * @return ImportColumn The configured column
     */
    private function configureCurrencyColumn(ImportColumn $column): ImportColumn
    {
        return $column->numeric()->castStateUsing(function ($state) {
            if (blank($state)) {
                return null;
            }

            // Remove currency symbols and formatting chars
            if (is_string($state)) {
                $state = preg_replace('/[^0-9.-]/', '', $state);
            }

            return round(floatval($state), 2);
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
            CustomFieldType::TEXT => 'Sample text',
            CustomFieldType::NUMBER => '42',
            CustomFieldType::CURRENCY => '99.99',
            CustomFieldType::CHECKBOX, CustomFieldType::TOGGLE => 'Yes',
            CustomFieldType::DATE => now()->format('Y-m-d'),
            CustomFieldType::DATE_TIME => now()->format('Y-m-d H:i:s'),
            CustomFieldType::TEXTAREA => 'Sample longer text with multiple words',
            CustomFieldType::RICH_EDITOR,
            CustomFieldType::MARKDOWN_EDITOR => "# Sample Header\nSample content with **formatting**",
            CustomFieldType::LINK => 'https://example.com',
            CustomFieldType::COLOR_PICKER => '#3366FF',
            default => null,
        };

        if ($example !== null) {
            $column->example($example);
        }
    }
}
