<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Data\FieldSlotData;
use Relaticle\CustomFields\Data\RelationshipDefinitionData;
use Relaticle\CustomFields\Enums\CustomFieldsFeature;
use Relaticle\CustomFields\FeatureSystem\FeatureManager;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldRelationship;
use Relaticle\CustomFields\Services\TenantContextService;
use Relaticle\CustomFields\Support\CodeGenerator;

final readonly class CreateRelationshipDefinition
{
    public function execute(RelationshipDefinitionData $data): CustomFieldRelationship
    {
        $this->assertDefinable($data);

        return DB::transaction(function () use ($data): CustomFieldRelationship {
            $tenantId = TenantContextService::getCurrentTenantId();

            $fromField = $data->fromField instanceof FieldSlotData
                ? $this->createSlotField($data->fromField, $data->fromEntityType, $tenantId)
                : null;

            $toField = $data->toField instanceof FieldSlotData
                ? $this->createSlotField($data->toField, $data->toEntityType, $tenantId)
                : null;

            // A symmetric relationship renders one field that reads both ends, so both slots
            // point at it and directionFor() answers 'from' for either direction.
            $attributes = [
                'code' => $data->code,
                'from_entity_type' => $data->fromEntityType,
                'to_entity_type' => $data->toEntityType,
                'cardinality' => $data->cardinality,
                'is_symmetric' => $data->isSymmetric,
                'from_field_id' => $fromField?->getKey(),
                'to_field_id' => $data->isSymmetric ? $fromField?->getKey() : $toField?->getKey(),
            ];

            if (FeatureManager::isEnabled(CustomFieldsFeature::SYSTEM_MULTI_TENANCY)) {
                $attributes[config('custom-fields.database.column_names.tenant_foreign_key')] = $tenantId;
            }

            return CustomFields::newRelationshipModel()->newQuery()->create($attributes);
        });
    }

    private function assertDefinable(RelationshipDefinitionData $data): void
    {
        $this->assertEndResolves($data->fromEntityType, $data->code);
        $this->assertEndResolves($data->toEntityType, $data->code);

        if ($data->isSymmetric && $data->fromEntityType !== $data->toEntityType) {
            throw new InvalidArgumentException('A symmetric relationship requires matching entity types.');
        }

        if ($data->isSymmetric && $data->toField instanceof FieldSlotData) {
            throw new InvalidArgumentException('A symmetric relationship has a single field slot.');
        }

        if ($data->isSymmetric && $data->cardinality->fromSideIsSingle() !== $data->cardinality->toSideIsSingle()) {
            throw new InvalidArgumentException(sprintf('A symmetric relationship cannot use the directional cardinality [%s].', $data->cardinality->value));
        }

        if ($this->codeIsTaken($data->code)) {
            throw new InvalidArgumentException(sprintf('A relationship with the code [%s] already exists.', $data->code));
        }
    }

    /**
     * Ends are locked once a definition exists, so an unusable one is rejected here rather
     * than at the first write. The resolution mirrors the writer's.
     */
    private function assertEndResolves(string $entityType, string $code): void
    {
        $entityClass = Relation::getMorphedModel($entityType) ?? $entityType;

        if (class_exists($entityClass) && is_subclass_of($entityClass, Model::class)) {
            return;
        }

        throw new InvalidArgumentException(sprintf('A relationship cannot end on the unresolvable entity type [%s] (relationship [%s]).', $entityType, $code));
    }

    private function codeIsTaken(string $code): bool
    {
        return CustomFields::newRelationshipModel()
            ->newQuery()
            ->where('code', $code)
            ->exists();
    }

    private function createSlotField(FieldSlotData $slot, string $entityType, int|string|null $tenantId): CustomField
    {
        $attributes = [
            'code' => CodeGenerator::generateUniqueFieldCode($slot->name, $entityType, sectionId: $slot->sectionId),
            'name' => $slot->name,
            'type' => 'record',
            'entity_type' => $entityType,
            'active' => true,
        ];

        if (FeatureManager::isEnabled(CustomFieldsFeature::SYSTEM_SECTIONS)) {
            $attributes['custom_field_section_id'] = $slot->sectionId;
        }

        if (FeatureManager::isEnabled(CustomFieldsFeature::SYSTEM_MULTI_TENANCY)) {
            $attributes[config('custom-fields.database.column_names.tenant_foreign_key')] = $tenantId;
        }

        return CustomFields::newCustomFieldModel()->newQuery()->create($attributes);
    }
}
