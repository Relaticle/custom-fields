<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Imports;

use Filament\Actions\Imports\ImportColumn;
use Illuminate\Database\Eloquent\Model;
use Psr\Log\LoggerInterface;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Filament\Imports\Exceptions\UnsupportedColumnTypeException;
use Relaticle\CustomFields\Filament\Imports\Matchers\LookupMatcherInterface;
use Relaticle\CustomFields\Filament\Imports\ValueConverters\ValueConverterInterface;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\EntityTypeService;

final class CustomFieldsImporter
{
    /**
     * Constructor with property promotion for dependency injection.
     */
    public function __construct(
        private readonly ColumnFactory $columnFactory,
        private readonly ValueConverterInterface $valueConverter,
        private readonly LookupMatcherInterface $lookupMatcher,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Get import columns for all custom fields of a model.
     *
     * @param  string  $modelClass  The fully qualified class name of the model
     * @param  Model|null  $tenant  Optional tenant for multi-tenancy support
     * @return array<int, ImportColumn> Array of import columns for custom fields
     *
     * @throws UnsupportedColumnTypeException
     */
    public function getColumns(string $modelClass, ?Model $tenant = null): array
    {
        $model = app($modelClass);

        return $model->customFields()
            ->with('options')
            ->active()
            ->get()
            ->map(fn (CustomField $field) => $this->columnFactory->create($field))
            ->toArray();
    }

    /**
     * Get custom field import columns for a specific model and set of field codes.
     *
     * @param  string  $modelClass  The fully qualified class name of the model
     * @param  array<string>  $fieldCodes  List of custom field codes to include
     * @return array<int, ImportColumn> Array of import columns for the specified custom fields
     *
     * @throws UnsupportedColumnTypeException
     */
    public function getColumnsByFieldCodes(string $modelClass, array $fieldCodes): array
    {
        $entityType = EntityTypeService::getEntityFromModel($modelClass);

        return CustomFields::newCustomFieldModel()->query()
            ->forMorphEntity($entityType)
            ->with('options')
            ->whereIn('code', $fieldCodes)
            ->active()
            ->get()
            ->map(fn (CustomField $field) => $this->columnFactory->create($field))
            ->toArray();
    }

    /**
     * Save custom field values from imported data.
     *
     * Call this method in your importer's afterFill() method to save
     * the custom field values that were imported.
     *
     * @param  Model  $record  The model record to save custom fields for
     * @param  array<string, mixed>  $data  The import data containing custom fields values
     * @param  Model|null  $tenant  Optional tenant for multi-tenancy support
     */
    public function saveCustomFieldValues(Model $record, array $data, ?Model $tenant = null): void
    {
        $customFieldsData = $this->extractCustomFieldsData($data);

        $this->logger->info('Custom fields data to save', [
            'record' => $record::class.'#'.$record->getKey(),
            'customFieldsData' => $customFieldsData,
            'tenant' => $tenant ? $tenant::class.'#'.$tenant->getKey() : null,
        ]);

        if (! empty($customFieldsData)) {
            // Process string values for select fields before saving
            $customFieldsData = $this->valueConverter->convertValues($record, $customFieldsData, $tenant);
            $record->saveCustomFields($customFieldsData, $tenant);
        }
    }

    /**
     * Get custom fields data from import data.
     *
     * This method extracts custom field values from the import data
     * and returns them in a format ready to be saved with saveCustomFields().
     *
     * @param  array<string, mixed>  $data  The import data
     * @return array<string, mixed> Custom fields data
     */
    public function extractCustomFieldsData(array $data): array
    {
        $customFieldsData = [];

        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'custom_fields_')) {
                $fieldCode = str_replace('custom_fields_', '', $key);
                $customFieldsData[$fieldCode] = $value;
            }
        }

        return $customFieldsData;
    }

    /**
     * Filter out custom fields from the data that will be used to fill the model.
     *
     * This method should be called in the beforeFill() hook to remove custom fields
     * data from the data array before the model is filled.
     *
     * @param  array<string, mixed>  $data  The import data to filter
     * @return array<string, mixed> Filtered data without custom fields
     */
    public function filterCustomFieldsFromData(array $data): array
    {
        return array_filter(
            $data,
            fn ($key) => ! str_starts_with($key, 'custom_fields_'),
            ARRAY_FILTER_USE_KEY
        );
    }
}
