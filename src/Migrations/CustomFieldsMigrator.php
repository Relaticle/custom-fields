<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Migrations;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Relaticle\CustomFields\Contracts\CustomsFieldsMigrators;
use Relaticle\CustomFields\Data\CustomFieldData;
use Relaticle\CustomFields\Data\CustomFieldSectionData;
use Relaticle\CustomFields\Enums\CustomFieldSectionType;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Enums\CustomFieldWidth;
use Relaticle\CustomFields\Exceptions\CustomFieldAlreadyExistsException;
use Relaticle\CustomFields\Exceptions\CustomFieldDoesNotExistException;
use Relaticle\CustomFields\Exceptions\FieldTypeNotOptionableException;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Services\EntityTypeService;
use Relaticle\CustomFields\Services\LookupTypeService;
use Relaticle\CustomFields\Support\Utils;

class CustomFieldsMigrator implements CustomsFieldsMigrators
{
    private int|string|null $tenantId = null;

    private CustomFieldData $customFieldData;

    private ?CustomField $customField;

    public function setTenantId(int|string|null $tenantId = null): void
    {
        $this->tenantId = $tenantId;
    }

    public function find(string $model, string $code): CustomFieldsMigrator
    {
        $this->customField = CustomField::query()
            ->forMorphEntity(EntityTypeService::getEntityFromModel($model))
            ->where('code', $code)
            ->firstOrFail();

        $this->customFieldData = CustomFieldData::from($this->customField);

        return $this;
    }

    /**
     * @param class-string $model
     */
    public function new(string $model, CustomFieldData $fieldData): CustomFieldsMigrator
    {
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
        if (!$this->isCustomFieldTypeOptionable()) {
            throw new FieldTypeNotOptionableException();
        }

        $this->customFieldData->options = $options;

        return $this;
    }

    /**
     * @throws FieldTypeNotOptionableException
     */
    public function lookupType(string $model): CustomFieldsMigrator
    {
        if (!$this->isCustomFieldTypeOptionable()) {
            throw new FieldTypeNotOptionableException();
        }

        $this->customFieldData->lookupType = LookupTypeService::getEntityFromModel($model);

        return $this;
    }

    /**
     * @throws CustomFieldAlreadyExistsException
     * @throws Exception
     */
    public function create(): void
    {
        if ($this->isCustomFieldExists($this->customFieldData->entityType, $this->customFieldData->code, $this->tenantId)) {
            throw CustomFieldAlreadyExistsException::whenAdding($this->customFieldData->code);
        }

        try {
            DB::beginTransaction();

            $data = $this->customFieldData->except('section', 'options')->toArray();

            $sectionData = $this->customFieldData->section->toArray();
            $sectionAttributes = [
                'entity_type' => $this->customFieldData->entityType,
                'code' => $this->customFieldData->section->code
            ];

            if (Utils::isTenantEnabled()) {
                $data[config('custom-fields.column_names.tenant_foreign_key')] = $this->tenantId;
                $sectionData[config('custom-fields.column_names.tenant_foreign_key')] = $this->tenantId;
                $sectionAttributes[config('custom-fields.column_names.tenant_foreign_key')] = $this->tenantId;
            }

            $section = CustomFieldSection::firstOrCreate(
                $sectionAttributes,
                $sectionData
            );

            $data['custom_field_section_id'] = $section->id;

            $customField = CustomField::query()->create($data);

            if ($this->isCustomFieldTypeOptionable() && !empty($this->customFieldData->options)) {
                $this->createOptions($customField, $this->customFieldData->options);
            }

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * @param array $data
     * @return void
     * @throws CustomFieldDoesNotExistException
     */
    public function update(array $data): void
    {
        if (!$this->customField->exists) {
            throw CustomFieldDoesNotExistException::whenUpdating($this->customFieldData->code);
        }

        try {
            DB::beginTransaction();

            collect($data)->each(fn($value, $key) => $this->customFieldData->$key = $value);

            $data = $this->customFieldData->toArray();

            if (Utils::isTenantEnabled()) {
                $data[config('custom-fields.column_names.tenant_foreign_key')] = $this->tenantId;
            }

            $this->customField->update($data);

            if ($this->isCustomFieldTypeOptionable() && !empty($this->customFieldData->options)) {
                $this->customField->options()->delete();
                $this->createOptions($this->customField, $this->customFieldData->options);
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
        if (!$this->customField) {
            throw CustomFieldDoesNotExistException::whenDeleting($this->customField->code);
        }

        $this->customField->delete();
    }

    /**
     * @throws CustomFieldDoesNotExistException
     */
    public function activate(): void
    {
        if (!$this->customField) {
            throw CustomFieldDoesNotExistException::whenActivating($this->customField->code);
        }

        if ($this->customField->isActive()) {
            return;
        }

        $this->customField->activate();
    }

    /**
     * @return void
     * @throws CustomFieldDoesNotExistException
     */
    public function deactivate(): void
    {
        if (!$this->customField) {
            throw CustomFieldDoesNotExistException::whenDeactivating($this->customField->code);
        }

        if (!$this->customField->isActive()) {
            return;
        }

        $this->customField->deactivate();
    }

    /**
     * @param CustomField $customField
     * @param array $options
     * @return void
     */
    protected function createOptions(CustomField $customField, array $options): void
    {
        $customField->options()->createMany(
            collect($options)
                ->map(function ($value, int $key) {
                    $data = [
                        'name' => $value,
                        'sort_order' => $key
                    ];

                    if (Utils::isTenantEnabled()) {
                        $data[config('custom-fields.column_names.tenant_foreign_key')] = $this->tenantId;
                    }

                    return $data;
                })
                ->toArray()
        );
    }

    /**
     * @param string $model
     * @param string $code
     * @param int|string|null $tenantId
     * @return bool
     */
    protected function isCustomFieldExists(string $model, string $code, int|string|null $tenantId = null): bool
    {
        return CustomField::query()
            ->forMorphEntity($model)
            ->where('code', $code)
            ->when(Utils::isTenantEnabled() && $tenantId, fn($query) => $query->where(config('custom-fields.column_names.tenant_foreign_key'), $tenantId))
            ->exists();
    }

    /**
     * @return bool
     */
    protected function isCustomFieldTypeOptionable(): bool
    {
        return CustomFieldType::optionables()->contains('value', $this->customFieldData->type->value);
    }
}
