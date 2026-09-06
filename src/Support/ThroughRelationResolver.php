<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Relaticle\CustomFields\Exceptions\UnsupportedThroughRelationException;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomField;

/**
 * Decide whether a row model can reach custom fields through one of its relations.
 * Every table surface asks here, so the state, sort, search and filter paths cannot
 * disagree about what a through path supports.
 */
final readonly class ThroughRelationResolver
{
    /**
     * @return Relation<Model, Model, ?Model>
     *
     * @throws UnsupportedThroughRelationException
     */
    public function resolve(Model $model, string $relation): Relation
    {
        $instance = $this->relationInstance($model, $relation);

        if (! $instance instanceof Relation) {
            throw UnsupportedThroughRelationException::missing($model::class, $relation);
        }

        if ($instance instanceof MorphTo) {
            throw UnsupportedThroughRelationException::polymorphicTarget($model::class, $relation);
        }

        if (! $instance instanceof BelongsTo && ! $instance instanceof HasOne && ! $instance instanceof MorphOne) {
            throw UnsupportedThroughRelationException::toMany($model::class, $relation, $instance::class);
        }

        $related = $instance->getRelated();

        if (! $related instanceof HasCustomFields) {
            throw UnsupportedThroughRelationException::withoutCustomFields($model::class, $relation, $related::class);
        }

        return $instance;
    }

    /**
     * The related record a field is read from, or null when the row has none.
     *
     * @return (Model&HasCustomFields)|null
     *
     * @throws UnsupportedThroughRelationException
     */
    public function relatedRecord(Model $record, string $relation): ?Model
    {
        $this->resolve($record, $relation);

        $related = $record->getAttribute($relation);

        return $related instanceof Model && $related instanceof HasCustomFields ? $related : null;
    }

    /**
     * Apply a constraint written against the related model to a row query.
     *
     * @param  Builder<Model>  $query
     * @param  Closure(Builder<Model>): mixed  $constraint
     * @return Builder<Model>
     *
     * @throws UnsupportedThroughRelationException
     */
    public function constrain(Builder $query, string $relation, Closure $constraint): Builder
    {
        $this->resolve($query->getModel(), $relation);

        return $query->whereHas($relation, $constraint);
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     *
     * @throws UnsupportedThroughRelationException
     */
    public function orderByFieldValue(Builder $query, string $relation, CustomField $customField, string $direction): Builder
    {
        $instance = $this->resolve($query->getModel(), $relation);

        $values = $customField->values();
        $entityId = $values->getRelated()->qualifyColumn('entity_id');

        $values->select($customField->getValueColumn())->limit(1);

        if ($instance instanceof BelongsTo) {
            // The foreign key sits on the row table already, so the value correlates without a hop.
            $values->whereColumn($entityId, $instance->getQualifiedForeignKeyName());
        } else {
            // A self relation aliases the inner table, and only the returned builder knows
            // the alias, so the key column is chosen after the correlation is built.
            $keys = $instance->getRelationExistenceQuery(
                $instance->getRelated()->newQueryWithoutRelationships(),
                $query,
            );

            $values->whereIn($entityId, $keys->select($keys->getModel()->getQualifiedKeyName()));
        }

        return $query->orderBy($values->getQuery(), $direction);
    }

    /**
     * @return Relation<Model, Model, ?Model>|null
     */
    private function relationInstance(Model $model, string $relation): ?Relation
    {
        if (! $model->isRelation($relation)) {
            return null;
        }

        /** @var Relation<Model, Model, ?Model>|mixed $instance */
        $instance = $model->{$relation}();

        return $instance instanceof Relation ? $instance : null;
    }
}
