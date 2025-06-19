<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Actions\Imports\ColumnConfigurators;

use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Relaticle\CustomFields\Integration\Actions\Imports\Matchers\LookupMatcherInterface;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\FilamentResourceService;
use Throwable;

/**
 * Configures select columns that use either lookup relationships or options.
 */
final class SelectColumnConfigurator implements ColumnConfiguratorInterface
{
    /**
     * Constructor with dependency injection.
     */
    public function __construct(
        private readonly LookupMatcherInterface $lookupMatcher
    ) {}

    /**
     * Configure a select column based on a custom field.
     *
     * @param  ImportColumn  $column  The column to configure
     * @param  CustomField  $customField  The custom field to base configuration on
     */
    public function configure(ImportColumn $column, CustomField $customField): void
    {
        if ($customField->lookup_type) {
            $this->configureLookupColumn($column, $customField);
        } else {
            $this->configureOptionsColumn($column, $customField);
        }
    }

    /**
     * Configure a column that uses a lookup relationship.
     *
     * @param  ImportColumn  $column  The column to configure
     * @param  CustomField  $customField  The custom field to base configuration on
     */
    private function configureLookupColumn(ImportColumn $column, CustomField $customField): void
    {
        // Configure column to use lookup relationship
        $column->castStateUsing(function ($state) use ($customField) {
            if (blank($state)) {
                return null;
            }

            try {
                $entityInstance = FilamentResourceService::getModelInstance($customField->lookup_type);

                $record = $this->lookupMatcher
                    ->find(
                        entityInstance: $entityInstance,
                        value: (string) $state
                    );

                if ($record) {
                    return (int) $record->getKey();
                }

                throw new RowImportFailedException(
                    "No {$customField->lookup_type} record found matching '{$state}'"
                );
            } catch (Throwable $e) {
                if ($e instanceof RowImportFailedException) {
                    throw $e;
                }

                throw new RowImportFailedException(
                    "Error resolving lookup value for {$customField->name}: {$e->getMessage()}"
                );
            }
        });

        // Set example values for lookup types
        $this->setLookupTypeExamples($column, $customField);
    }

    /**
     * Configure a column that uses options.
     *
     * @param  ImportColumn  $column  The column to configure
     * @param  CustomField  $customField  The custom field to base configuration on
     */
    private function configureOptionsColumn(ImportColumn $column, CustomField $customField): void
    {
        // Configure column to use options
        $column->castStateUsing(function ($state) use ($customField) {
            if (blank($state)) {
                return null;
            }

            // Try exact match first
            $option = $customField->options
                ->where('name', $state)
                ->first();

            // If no match, try case-insensitive match
            if (! $option) {
                $option = $customField->options
                    ->first(fn ($opt) => strtolower($opt->name) === strtolower($state));
            }

            if (! $option) {
                throw new RowImportFailedException(
                    "Invalid option value '{$state}' for {$customField->name}. Valid options are: ".
                    $customField->options->pluck('name')->implode(', ')
                );
            }

            return $option->getKey();
        });

        // Set example options
        $this->setOptionExamples($column, $customField);
    }

    /**
     * Set example values for a lookup type column.
     *
     * @param  ImportColumn  $column  The column to set examples for
     * @param  CustomField  $customField  The custom field
     */
    private function setLookupTypeExamples(ImportColumn $column, CustomField $customField): void
    {
        try {
            $entityInstance = FilamentResourceService::getModelInstance($customField->lookup_type);
            $recordTitleAttribute = FilamentResourceService::getRecordTitleAttribute($customField->lookup_type);

            // Get sample values from the lookup model
            $sampleValues = $entityInstance::query()
                ->limit(2)
                ->pluck($recordTitleAttribute)
                ->toArray();

            if (! empty($sampleValues)) {
                $column->example($sampleValues[0]);
            }
        } catch (Throwable) {
            // If there's an error getting example lookup values, provide generic example
            $column->example('Example value');
        }
    }

    /**
     * Set example values for an options-based column.
     *
     * @param  ImportColumn  $column  The column to set examples for
     * @param  CustomField  $customField  The custom field
     */
    private function setOptionExamples(ImportColumn $column, CustomField $customField): void
    {
        $options = $customField->options->pluck('name')->toArray();

        if (! empty($options)) {
            $column->example($options[0]);
            $column->helperText('Valid options: '.implode(', ', $options));
        }
    }
}
