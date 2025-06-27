<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Migrations;

use BackedEnum;
use Exception;
use Illuminate\Support\Facades\DB;
use Relaticle\CustomFields\Contracts\CustomsFieldsMigrators;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Data\CustomFieldData;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Exceptions\CustomFieldAlreadyExistsException;
use Relaticle\CustomFields\Exceptions\CustomFieldDoesNotExistException;
use Relaticle\CustomFields\Exceptions\FieldTypeNotOptionableException;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\EntityTypeService;
use Relaticle\CustomFields\Services\LookupTypeService;
use Relaticle\CustomFields\Support\Utils;
use Throwable;

class CustomFieldsMigrator implements CustomsFieldsMigrators
{
    private int|string|null $tenantId = null;

    private CustomFieldData $customFieldData;

    private ?CustomField $customField = null;

    public function setTenantId(int|string|null $tenantId = null): void
    {
        $this->tenantId = $tenantId;
    }

    public function find(string $model, string $code): CustomFieldsMigrator
    {
        $this->customField = CustomFields::newCustomFieldModel()
            ->query()
            ->forMorphEntity(EntityTypeService::getEntityFromModel($model))
            ->where('code', $code)
            ->firstOrFail();

        $this->customFieldData = CustomFieldData::from($this->customField);

        return $this;
    }

    /**
     * @param  class-string  $model
     */
    public function new(
        string $model,
        CustomFieldData $fieldData
    ): CustomFieldsMigrator {
        $entityType = EntityTypeService::getEntityFromModel($model);

        $fieldData->entityType = $fieldData->section->entityType = $entityType;

        $this->customFieldData = $fieldData;

        return $this;
    }

    /**
     * @throws FieldTypeNotOptionableException
     */
    public function options(array $options): CustomFieldsMigrator
    {
        if (! $this->isCustomFieldTypeOptionable()) {
            throw new FieldTypeNotOptionableException;
        }

        $this->customFieldData->options = $options;

        return $this;
    }

    /**
     * @throws FieldTypeNotOptionableException
     */
    public function lookupType(string $model): CustomFieldsMigrator
    {
        if (! $this->isCustomFieldTypeOptionable()) {
            throw new FieldTypeNotOptionableException;
        }

        $this->customFieldData->lookupType = LookupTypeService::getEntityFromModel(
            $model
        );

        return $this;
    }

    /**
     * @throws CustomFieldAlreadyExistsException
     * @throws Exception|Throwable
     */
    public function create(): CustomField
    {
        if (
            $this->isCustomFieldExists(
                $this->customFieldData->entityType,
                $this->customFieldData->code,
                $this->tenantId
            )
        ) {
            throw CustomFieldAlreadyExistsException::whenAdding(
                $this->customFieldData->code
            );
        }

        try {
            DB::beginTransaction();

            $data = $this->customFieldData
                ->except('section', 'options')
                ->toArray();

            $sectionData = $this->customFieldData->section->toArray();
            $sectionAttributes = [
                'entity_type' => $this->customFieldData->entityType,
                'code' => $this->customFieldData->section->code,
            ];

            if (Utils::isTenantEnabled()) {
                $data[config('custom-fields.column_names.tenant_foreign_key')] =
                    $this->tenantId;
                $sectionData[
                    config('custom-fields.column_names.tenant_foreign_key')
                ] = $this->tenantId;
                $sectionAttributes[
                    config('custom-fields.column_names.tenant_foreign_key')
                ] = $this->tenantId;
            }

            $section = CustomFields::newSectionModel()->updateOrCreate(
                $sectionAttributes,
                $sectionData
            );

            $data['custom_field_section_id'] = $section->getKey();

            $customField = CustomFields::newCustomFieldModel()
                ->query()
                ->create($data);

            if (
                $this->isCustomFieldTypeOptionable() &&
                ($this->customFieldData->options !== null &&
                    $this->customFieldData->options !== [])
            ) {
                $this->createOptions(
                    $customField,
                    $this->customFieldData->options
                );
            }

            DB::commit();

            return $customField;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * @throws CustomFieldDoesNotExistException|Throwable
     */
    public function update(array $data): void
    {
        if (! $this->customField->exists) {
            throw CustomFieldDoesNotExistException::whenUpdating(
                $this->customFieldData->code
            );
        }

        try {
            DB::beginTransaction();

            // Create a new CustomFieldData instance with the updated data
            $existingData = $this->customFieldData->toArray();
            $mergedData = array_merge($existingData, $data);
            $this->customFieldData = CustomFieldData::from($mergedData);

            $updateData = $this->customFieldData->toArray();

            if (Utils::isTenantEnabled()) {
                $updateData[
                    config('custom-fields.column_names.tenant_foreign_key')
                ] = $this->tenantId;
            }

            $this->customField->update($updateData);

            if (
                $this->isCustomFieldTypeOptionable() &&
                ($this->customFieldData->options !== null &&
                    $this->customFieldData->options !== [])
            ) {
                $this->customField->options()->delete();
                $this->createOptions(
                    $this->customField,
                    $this->customFieldData->options
                );
            }

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * @throws CustomFieldDoesNotExistException
     */
    public function delete(): void
    {
        if (! $this->customField instanceof CustomField) {
            throw CustomFieldDoesNotExistException::whenDeleting(
                $this->customFieldData->code
            );
        }

        $this->customField->delete();
    }

    /**
     * @throws CustomFieldDoesNotExistException
     */
    public function activate(): void
    {
        if (! $this->customField instanceof CustomField) {
            throw CustomFieldDoesNotExistException::whenActivating(
                $this->customFieldData->code
            );
        }

        if ($this->customField->isActive()) {
            return;
        }

        $this->customField->activate();
    }

    /**
     * @throws CustomFieldDoesNotExistException
     */
    public function deactivate(): void
    {
        if (! $this->customField instanceof CustomField) {
            throw CustomFieldDoesNotExistException::whenDeactivating(
                $this->customFieldData->code
            );
        }

        if (! $this->customField->isActive()) {
            return;
        }

        $this->customField->deactivate();
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function createOptions(
        CustomField $customField,
        array $options
    ): void {
        $customField->options()->createMany(
            collect($options)
                ->map(function ($value, $key) {
                    $data = [
                        'name' => $value,
                        'sort_order' => $key,
                    ];

                    if (Utils::isTenantEnabled()) {
                        $data[
                            config(
                                'custom-fields.column_names.tenant_foreign_key'
                            )
                        ] = $this->tenantId;
                    }

                    return $data;
                })
                ->toArray()
        );
    }

    protected function isCustomFieldExists(
        string $model,
        string $code,
        int|string|null $tenantId = null
    ): bool {
        return CustomFields::newCustomFieldModel()
            ->query()
            ->forMorphEntity($model)
            ->where('code', $code)
            ->when(
                Utils::isTenantEnabled() && $tenantId,
                fn ($query) => $query->where(
                    config('custom-fields.column_names.tenant_foreign_key'),
                    $tenantId
                )
            )
            ->exists();
    }

    protected function isCustomFieldTypeOptionable(): bool
    {
        // Handle both enum and string types
        $typeValue =
            $this->customFieldData->type instanceof BackedEnum
                ? $this->customFieldData->type->value
                : $this->customFieldData->type;

        return CustomFieldType::optionables()->contains('value', $typeValue);
    }
}
