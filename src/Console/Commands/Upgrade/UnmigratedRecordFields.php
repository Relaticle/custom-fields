<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Console\Commands\Upgrade;

use Illuminate\Support\Facades\Schema;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldRelationship;

/**
 * Record fields whose links still live in json_value with no definition to read them from.
 * The upgrade gate, the migration step, and the purge step all ask the same question.
 */
final readonly class UnmigratedRecordFields
{
    /**
     * @return array<int, string> field codes, across every tenant
     */
    public function codes(): array
    {
        $fields = $this->recordFieldsWithoutDefinition();

        if ($fields === []) {
            return [];
        }

        $withValues = CustomFields::newValueModel()
            ->newQuery()
            ->withoutGlobalScopes()
            ->whereIn('custom_field_id', array_keys($fields))
            ->whereNotNull('json_value')
            ->distinct()
            ->pluck('custom_field_id')
            ->map(static fn (mixed $key): string => (string) $key)
            ->all();

        return array_values(array_intersect_key($fields, array_flip($withValues)));
    }

    /**
     * @return array<string, string> field key => field code
     */
    private function recordFieldsWithoutDefinition(): array
    {
        $fieldsTable = (string) config('custom-fields.database.table_names.custom_fields');
        $valuesTable = (string) config('custom-fields.database.table_names.custom_field_values');

        if (! Schema::hasTable($fieldsTable) || ! Schema::hasTable($valuesTable)) {
            return [];
        }

        $slotFieldIds = $this->slotFieldIds();

        return CustomFields::newCustomFieldModel()
            ->newQuery()
            ->withoutGlobalScopes()
            ->where('type', 'record')
            ->get()
            ->reject(fn (CustomField $field): bool => in_array((string) $field->getKey(), $slotFieldIds, true))
            ->mapWithKeys(fn (CustomField $field): array => [(string) $field->getKey() => $field->code])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function slotFieldIds(): array
    {
        $definitions = (string) config('custom-fields.database.table_names.custom_field_relationships');

        if (! Schema::hasTable($definitions)) {
            return [];
        }

        return CustomFields::newRelationshipModel()
            ->newQuery()
            ->withoutGlobalScopes()
            ->get(['from_field_id', 'to_field_id'])
            ->flatMap(static fn (CustomFieldRelationship $definition): array => [$definition->from_field_id, $definition->to_field_id])
            ->filter()
            ->map(static fn (mixed $key): string => (string) $key)
            ->all();
    }
}
