<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Console\Commands\Upgrade\Steps;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Relaticle\CustomFields\Console\Commands\Upgrade\UpgradeStep;
use Relaticle\CustomFields\Console\Commands\Upgrade\UpgradeStepResult;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Enums\CustomFieldsFeature;
use Relaticle\CustomFields\Enums\RelationshipCardinality;
use Relaticle\CustomFields\FeatureSystem\FeatureManager;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldLink;
use Relaticle\CustomFields\Models\CustomFieldRelationship;
use Relaticle\CustomFields\Models\CustomFieldValue;

/**
 * Moves 3.x record links out of json_value: a definition per record field, an edge per id.
 * The value rows are kept, so a host can compare both stores and roll back until the
 * separate purge step runs.
 */
final class MigrateRecordLinksStep implements UpgradeStep
{
    private const int CHUNK_SIZE = 500;

    public function name(): string
    {
        return 'Migrate Record Links';
    }

    public function description(): string
    {
        return 'Move record-type field values onto relationship definitions and links';
    }

    public function execute(bool $dryRun, Command $command): UpgradeStepResult
    {
        if (! FeatureManager::isEnabled(CustomFieldsFeature::SYSTEM_RELATIONSHIPS)) {
            return UpgradeStepResult::skipped('The relationships feature is disabled, so record fields keep their value rows.');
        }

        if (! Schema::hasTable($this->table('custom_field_relationships')) || ! Schema::hasTable($this->table('custom_field_links'))) {
            return UpgradeStepResult::skipped(sprintf(
                'Tables %s and %s do not exist yet: publish and run the relationship migrations first.',
                $this->table('custom_field_relationships'),
                $this->table('custom_field_links'),
            ));
        }

        $created = 0;
        $failed = 0;
        $warnings = [];

        foreach ($this->recordFields() as $field) {
            $command->line(sprintf('  Migrating record field <fg=white>%s</>...', $field->code));

            $definition = $this->definitionFor($field);

            if (! $definition instanceof CustomFieldRelationship && blank($this->legacyTargetEntityType($field))) {
                $failed++;
                $warnings[] = sprintf("Field '%s' has no lookup type, so it has no relationship to define", $field->code);
                $command->line(sprintf('  <comment>○</comment> %s: no lookup type, skipped', $field->code));

                continue;
            }

            // Legacy values are always written from the record that holds the field, so a
            // field reading the far end of its definition would migrate to reversed edges.
            if ($definition instanceof CustomFieldRelationship && (string) $definition->from_field_id !== (string) $field->getKey()) {
                $failed++;
                $warnings[] = sprintf("Field '%s' reads the to end of relationship '%s', so its value rows need migrating by hand", $field->code, $definition->code);
                $command->line(sprintf('  <comment>○</comment> %s: reads the to end of %s, skipped', $field->code, $definition->code));

                continue;
            }

            $links = $dryRun
                ? $this->countLinks($field, $definition)
                : $this->migrate($field, $definition);

            $created += $links;
            $command->line(sprintf('  <info>✓</info> %s: %d link(s)%s', $field->code, $links, $dryRun ? ' would be created' : ' created'));
        }

        return new UpgradeStepResult(
            success: $failed === 0,
            itemsProcessed: $created,
            itemsFailed: $failed,
            warnings: $warnings,
        );
    }

    private function migrate(CustomField $field, ?CustomFieldRelationship $definition): int
    {
        return (int) DB::transaction(function () use ($field, $definition): int {
            $definition ??= $this->createDefinition($field);
            $created = 0;

            $this->values($field)->chunkById(self::CHUNK_SIZE, function (EloquentCollection $values) use ($definition, $field, &$created): void {
                $linked = $this->linkedTargets($definition, $values);

                foreach ($values as $value) {
                    foreach ($this->targets($value) as $index => $targetId) {
                        if (in_array($targetId, $linked[(string) $value->entity_id] ?? [], true)) {
                            continue;
                        }

                        $this->insert($definition, $field, $value, $targetId, $index);
                        $linked[(string) $value->entity_id][] = $targetId;
                        $created++;
                    }
                }
            });

            return $created;
        });
    }

    private function countLinks(CustomField $field, ?CustomFieldRelationship $definition): int
    {
        $counted = 0;

        $this->values($field)->chunkById(self::CHUNK_SIZE, function (EloquentCollection $values) use ($definition, &$counted): void {
            $linked = $definition instanceof CustomFieldRelationship ? $this->linkedTargets($definition, $values) : [];

            foreach ($values as $value) {
                foreach ($this->targets($value) as $targetId) {
                    if (in_array($targetId, $linked[(string) $value->entity_id] ?? [], true)) {
                        continue;
                    }

                    $counted++;
                }
            }
        });

        return $counted;
    }

    /**
     * The edges a record already holds on this definition, so a rerun writes nothing twice.
     *
     * @param  EloquentCollection<int, CustomFieldValue>  $values
     * @return array<string, array<int, string>>
     */
    private function linkedTargets(CustomFieldRelationship $definition, EloquentCollection $values): array
    {
        $links = CustomFields::newLinkModel()
            ->newQuery()
            ->withoutGlobalScopes()
            ->where('relationship_id', $definition->getKey())
            ->whereNull('active_until')
            ->whereIn('from_entity_id', $values->pluck('entity_id')->all())
            ->get();

        $linked = [];

        foreach ($links as $link) {
            $linked[(string) $link->from_entity_id][] = (string) $link->to_entity_id;
        }

        return $linked;
    }

    /**
     * @return array<int, string>
     */
    private function targets(CustomFieldValue $value): array
    {
        $ids = $value->json_value?->all() ?? [];

        return array_values(array_unique(array_map(
            static fn (mixed $id): string => (string) $id,
            array_filter($ids, static fn (mixed $id): bool => is_int($id) || (is_string($id) && $id !== '')),
        )));
    }

    private function insert(CustomFieldRelationship $definition, CustomField $field, CustomFieldValue $value, string $targetId, int $index): void
    {
        $attributes = [
            'relationship_id' => $definition->getKey(),
            'from_entity_type' => $value->entity_type,
            'from_entity_id' => $value->entity_id,
            'to_entity_type' => $definition->to_entity_type,
            'to_entity_id' => $targetId,
            'sort_order' => $index,
            'active_from' => now(),
            'source' => CustomFieldLink::SOURCE_MIGRATION,
        ];

        if (FeatureManager::isEnabled(CustomFieldsFeature::SYSTEM_MULTI_TENANCY)) {
            $tenantKey = (string) config('custom-fields.database.column_names.tenant_foreign_key');
            $attributes[$tenantKey] = $field->{$tenantKey};
        }

        CustomFields::newLinkModel()->newQuery()->create($attributes);
    }

    /**
     * A 3.x record field points one way and its multiplicity lived in the settings, so that
     * is the definition it becomes.
     */
    private function createDefinition(CustomField $field): CustomFieldRelationship
    {
        $attributes = [
            'code' => $this->availableCode($field->code),
            'from_entity_type' => $field->entity_type,
            'to_entity_type' => $this->legacyTargetEntityType($field),
            'cardinality' => $field->settings->allow_multiple
                ? RelationshipCardinality::ManyToMany
                : RelationshipCardinality::ManyToOne,
            'is_symmetric' => false,
            'from_field_id' => $field->getKey(),
            'to_field_id' => null,
        ];

        if (FeatureManager::isEnabled(CustomFieldsFeature::SYSTEM_MULTI_TENANCY)) {
            $tenantKey = (string) config('custom-fields.database.column_names.tenant_foreign_key');
            $attributes[$tenantKey] = $field->{$tenantKey};
        }

        return CustomFields::newRelationshipModel()->newQuery()->create($attributes);
    }

    /**
     * A host runs this before the migration that drops lookup_type, so the column is read
     * raw: the model no longer knows about it, and on an upgraded host it is simply gone.
     */
    private function legacyTargetEntityType(CustomField $field): string
    {
        $target = $field->getRawOriginal('lookup_type');

        return is_string($target) ? $target : '';
    }

    /**
     * Field codes are unique per entity type, definition codes per tenant, so the same code
     * can arrive twice from two entities.
     */
    private function availableCode(string $code): string
    {
        $candidate = $code;
        $suffix = 1;

        while ($this->codeIsTaken($candidate)) {
            $candidate = sprintf('%s_%d', $code, $suffix);
            $suffix++;
        }

        return $candidate;
    }

    private function codeIsTaken(string $code): bool
    {
        return CustomFields::newRelationshipModel()
            ->newQuery()
            ->withoutGlobalScopes()
            ->where('code', $code)
            ->exists();
    }

    private function definitionFor(CustomField $field): ?CustomFieldRelationship
    {
        return CustomFields::newRelationshipModel()
            ->newQuery()
            ->withoutGlobalScopes()
            ->where(fn (Builder $query): Builder => $query
                ->where('from_field_id', $field->getKey())
                ->orWhere('to_field_id', $field->getKey()))
            ->first();
    }

    /**
     * @return EloquentCollection<int, CustomField>
     */
    private function recordFields(): EloquentCollection
    {
        return CustomFields::newCustomFieldModel()
            ->newQuery()
            ->withoutGlobalScopes()
            ->where('type', 'record')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Builder<CustomFieldValue>
     */
    private function values(CustomField $field): Builder
    {
        return CustomFields::newValueModel()
            ->newQuery()
            ->withoutGlobalScopes()
            ->where('custom_field_id', $field->getKey())
            ->whereNotNull('json_value');
    }

    private function table(string $key): string
    {
        return (string) config('custom-fields.database.table_names.'.$key);
    }
}
