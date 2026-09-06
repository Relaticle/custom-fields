<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Console\Commands\Upgrade\Steps;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Relaticle\CustomFields\Console\Commands\Upgrade\UnmigratedRecordFields;
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

    public function __construct(private readonly UnmigratedRecordFields $records) {}

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

        foreach ($this->records->recordFields() as $field) {
            $command->line(sprintf('  Migrating record field <fg=white>%s</>...', $field->code));

            $definition = $this->records->definitionFor($field);

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

            $this->records->values($field)->chunkById(self::CHUNK_SIZE, function (EloquentCollection $values) use ($definition, $field, &$created): void {
                $ledger = $this->records->ledgerTargets($definition, $values);

                foreach ($values as $value) {
                    foreach ($this->records->targets($value) as $index => $targetId) {
                        if (in_array($targetId, $ledger[(string) $value->entity_id] ?? [], true)) {
                            continue;
                        }

                        $this->insert($definition, $field, $value, $targetId, $index);
                        $ledger[(string) $value->entity_id][] = $targetId;
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

        $this->records->values($field)->chunkById(self::CHUNK_SIZE, function (EloquentCollection $values) use ($definition, &$counted): void {
            $ledger = $definition instanceof CustomFieldRelationship ? $this->records->ledgerTargets($definition, $values) : [];

            foreach ($values as $value) {
                foreach ($this->records->targets($value) as $targetId) {
                    if (in_array($targetId, $ledger[(string) $value->entity_id] ?? [], true)) {
                        continue;
                    }

                    $counted++;
                }
            }
        });

        return $counted;
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

    private function table(string $key): string
    {
        return (string) config('custom-fields.database.table_names.'.$key);
    }
}
