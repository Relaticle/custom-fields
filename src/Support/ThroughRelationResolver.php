<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Relaticle\CustomFields\Exceptions\UnsupportedThroughRelationException;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;

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
