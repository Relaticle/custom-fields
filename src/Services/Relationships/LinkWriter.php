<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services\Relationships;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Relaticle\CustomFields\Contracts\LinkActorResolverInterface;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Enums\CustomFieldsFeature;
use Relaticle\CustomFields\Enums\RelationshipCardinality;
use Relaticle\CustomFields\Events\RelationshipLinkClosed;
use Relaticle\CustomFields\Events\RelationshipLinkCreated;
use Relaticle\CustomFields\FeatureSystem\FeatureManager;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldLink;
use Relaticle\CustomFields\Models\CustomFieldRelationship;
use RuntimeException;

final readonly class LinkWriter
{
    public function __construct(private LinkActorResolverInterface $actorResolver) {}

    /**
     * Apply the ordered payload of one record field as the record's active edges.
     *
     * @param  array<int, int|string>  $targetIds
     *
     * @throws ValidationException
     */
    public function apply(Model $record, CustomField $field, array $targetIds, string $source = CustomFieldLink::SOURCE_USER): void
    {
        $definition = $field->relationshipDefinition();

        if (! $definition instanceof CustomFieldRelationship) {
            throw new InvalidArgumentException(sprintf('Record field [%s] has no relationship definition.', $field->code));
        }

        try {
            DB::transaction(function () use ($record, $definition, $field, $targetIds, $source): void {
                $this->diff($record, $definition, $field, $targetIds, $source);
            });
        } catch (UniqueConstraintViolationException) {
            // Another writer took the edge between our read and our insert.
            throw ValidationException::withMessages([
                $field->getFieldName() => __('custom-fields::custom-fields.relationships.errors.conflict'),
            ]);
        }
    }

    /**
     * @param  array<int, int|string>  $targetIds
     */
    private function diff(Model $record, CustomFieldRelationship $definition, CustomField $field, array $targetIds, string $source): void
    {
        $definition = $this->lock($definition);

        $direction = $definition->is_symmetric
            ? CustomFieldRelationship::DIRECTION_FROM
            : $definition->directionFor($field);

        $this->assertRecordSitsOnEnd($record, $definition, $direction);

        $targets = $this->normalize($targetIds);

        $this->assertTargetsExist($definition, $field, $direction, $targets);

        $now = now();
        $actor = $this->actorResolver->resolve();
        $current = $this->activeLinksFor($definition, $record->getMorphClass(), (string) $record->getKey(), $direction)->get();

        foreach ($current as $link) {
            if (! in_array($this->otherEndId($link, $record), $targets, true)) {
                $this->close($link, $now);
            }
        }

        foreach ($targets as $index => $targetId) {
            $kept = $current->first(fn (CustomFieldLink $link): bool => $link->active_until === null
                && $this->otherEndId($link, $record) === $targetId);

            if ($kept instanceof CustomFieldLink) {
                $this->reorder($kept, $index);

                continue;
            }

            $this->closeDisplaced($definition, $record, $direction, $targetId, $now);

            event(new RelationshipLinkCreated(
                $this->insert($definition, $record, $direction, $targetId, $index, $now, $actor, $source)
            ));
        }
    }

    /**
     * Per-end exclusivity cannot be a static index, so writers serialize on the definition row,
     * which always exists. Many to many needs no lock: the duplicate-edge index is enough.
     */
    private function lock(CustomFieldRelationship $definition): CustomFieldRelationship
    {
        if ($definition->cardinality === RelationshipCardinality::ManyToMany) {
            return $definition;
        }

        return CustomFields::newRelationshipModel()
            ->newQuery()
            ->whereKey($definition->getKey())
            ->lockForUpdate()
            ->first() ?? $definition;
    }

    private function assertRecordSitsOnEnd(Model $record, CustomFieldRelationship $definition, string $direction): void
    {
        $expected = $direction === CustomFieldRelationship::DIRECTION_FROM
            ? $definition->from_entity_type
            : $definition->to_entity_type;

        if ($record->getMorphClass() === $expected) {
            return;
        }

        throw new InvalidArgumentException(sprintf(
            'Record [%s] does not sit on the [%s] end of relationship [%s].',
            $record->getMorphClass(),
            $direction,
            $definition->code,
        ));
    }

    /**
     * @param  array<int, int|string>  $targetIds
     * @return array<int, string>
     */
    private function normalize(array $targetIds): array
    {
        return array_values(array_unique(array_map(
            static fn (int|string $id): string => (string) $id,
            $targetIds,
        )));
    }

    /**
     * The target model's own query decides what is reachable, so a host's tenant scope and
     * its soft deletes rule out foreign ids before any edge is written.
     *
     * @param  array<int, string>  $targets
     */
    private function assertTargetsExist(CustomFieldRelationship $definition, CustomField $field, string $direction, array $targets): void
    {
        if ($targets === []) {
            return;
        }

        $entityType = $this->targetEntityType($definition, $direction);
        $entityClass = Relation::getMorphedModel($entityType) ?? $entityType;

        if (! class_exists($entityClass) || ! is_subclass_of($entityClass, Model::class)) {
            throw new RuntimeException(sprintf(
                'Relationship "%s" references an unresolvable entity type "%s".',
                $definition->code,
                $entityType,
            ));
        }

        $target = new $entityClass;

        $reachable = $target->newQuery()
            ->whereKey($targets)
            ->pluck($target->getKeyName())
            ->map(static fn (mixed $key): string => (string) $key)
            ->all();

        if (array_diff($targets, $reachable) === []) {
            return;
        }

        throw ValidationException::withMessages([
            $field->getFieldName() => __('custom-fields::custom-fields.relationships.errors.unknown_target'),
        ]);
    }

    /**
     * @return Builder<CustomFieldLink>
     */
    private function activeLinksFor(CustomFieldRelationship $definition, string $entityType, string $entityId, string $direction): Builder
    {
        $query = CustomFields::newLinkModel()
            ->newQuery()
            ->where('relationship_id', $definition->getKey())
            ->whereNull('active_until')
            ->orderBy('sort_order');

        if (! $definition->is_symmetric) {
            return $this->matchEnd($query, $entityType, $entityId, $direction);
        }

        return $query->where(function (Builder $nested) use ($entityType, $entityId): void {
            $nested
                ->where(fn (Builder $end): Builder => $this->matchEnd($end, $entityType, $entityId, CustomFieldRelationship::DIRECTION_FROM))
                ->orWhere(fn (Builder $end): Builder => $this->matchEnd($end, $entityType, $entityId, CustomFieldRelationship::DIRECTION_TO));
        });
    }

    /**
     * @param  Builder<CustomFieldLink>  $query
     * @return Builder<CustomFieldLink>
     */
    private function matchEnd(Builder $query, string $entityType, string $entityId, string $direction): Builder
    {
        return $query
            ->where($direction.'_entity_type', $entityType)
            ->where($direction.'_entity_id', $entityId);
    }

    private function otherEndId(CustomFieldLink $link, Model $record): string
    {
        $recordIsFromEnd = $link->from_entity_type === $record->getMorphClass()
            && (string) $link->from_entity_id === (string) $record->getKey();

        return $recordIsFromEnd
            ? (string) $link->to_entity_id
            : (string) $link->from_entity_id;
    }

    /**
     * A record landing in a taken single end replaces what is there: the displaced edge is
     * closed, never deleted, so the history keeps it.
     */
    private function closeDisplaced(CustomFieldRelationship $definition, Model $record, string $direction, string $targetId, Carbon $now): void
    {
        $cardinality = $definition->cardinality;
        $targetType = $this->targetEntityType($definition, $direction);

        if ($definition->is_symmetric) {
            if (! $cardinality->fromSideIsSingle()) {
                return;
            }

            $this->closeAll($this->activeLinksFor($definition, $record->getMorphClass(), (string) $record->getKey(), $direction), $now);
            $this->closeAll($this->activeLinksFor($definition, $targetType, $targetId, $direction), $now);

            return;
        }

        $recordEndIsSingle = $direction === CustomFieldRelationship::DIRECTION_FROM
            ? $cardinality->fromSideIsSingle()
            : $cardinality->toSideIsSingle();

        $targetEndIsSingle = $direction === CustomFieldRelationship::DIRECTION_FROM
            ? $cardinality->toSideIsSingle()
            : $cardinality->fromSideIsSingle();

        if ($recordEndIsSingle) {
            $this->closeAll($this->activeLinksFor($definition, $record->getMorphClass(), (string) $record->getKey(), $direction), $now);
        }

        if ($targetEndIsSingle) {
            $this->closeAll($this->activeLinksFor($definition, $targetType, $targetId, $this->opposite($direction)), $now);
        }
    }

    /**
     * @param  Builder<CustomFieldLink>  $query
     */
    private function closeAll(Builder $query, Carbon $now): void
    {
        foreach ($query->get() as $link) {
            $this->close($link, $now);
        }
    }

    private function close(CustomFieldLink $link, Carbon $now): void
    {
        $link->close($now);

        event(new RelationshipLinkClosed($link));
    }

    private function reorder(CustomFieldLink $link, int $index): void
    {
        if ($link->sort_order === $index) {
            return;
        }

        $link->sort_order = $index;
        $link->save();
    }

    private function insert(CustomFieldRelationship $definition, Model $record, string $direction, string $targetId, int $index, Carbon $now, ?Model $actor, string $source): CustomFieldLink
    {
        [$fromId, $toId] = $this->ends($definition, $record, $direction, $targetId);

        $attributes = [
            'relationship_id' => $definition->getKey(),
            'from_entity_type' => $definition->from_entity_type,
            'from_entity_id' => $fromId,
            'to_entity_type' => $definition->to_entity_type,
            'to_entity_id' => $toId,
            'sort_order' => $index,
            'active_from' => $now,
            'created_by_type' => $actor?->getMorphClass(),
            'created_by_id' => $actor?->getKey(),
            'source' => $source,
        ];

        if (FeatureManager::isEnabled(CustomFieldsFeature::SYSTEM_MULTI_TENANCY)) {
            $tenantKey = config('custom-fields.database.column_names.tenant_foreign_key');
            $attributes[$tenantKey] = $definition->{$tenantKey};
        }

        return CustomFields::newLinkModel()->newQuery()->create($attributes);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function ends(CustomFieldRelationship $definition, Model $record, string $direction, string $targetId): array
    {
        $recordId = (string) $record->getKey();

        // One symmetric row is read from both sides, so its ends are stored least first.
        if ($definition->is_symmetric) {
            return strcmp($recordId, $targetId) <= 0
                ? [$recordId, $targetId]
                : [$targetId, $recordId];
        }

        return $direction === CustomFieldRelationship::DIRECTION_FROM
            ? [$recordId, $targetId]
            : [$targetId, $recordId];
    }

    private function targetEntityType(CustomFieldRelationship $definition, string $direction): string
    {
        return $direction === CustomFieldRelationship::DIRECTION_FROM
            ? $definition->to_entity_type
            : $definition->from_entity_type;
    }

    private function opposite(string $direction): string
    {
        return $direction === CustomFieldRelationship::DIRECTION_FROM
            ? CustomFieldRelationship::DIRECTION_TO
            : CustomFieldRelationship::DIRECTION_FROM;
    }
}
