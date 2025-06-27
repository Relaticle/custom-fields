<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Models\Scopes;

use Override;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CustomFieldsActivableScope extends ActivableScope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    #[Override]
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->getQualifiedActiveColumn(), true)
            ->whereHas('section', fn ($query) => $query->active());
    }
}
