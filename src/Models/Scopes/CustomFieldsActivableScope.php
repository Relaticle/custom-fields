<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Models\Scopes;

use Override;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Custom fields activable scope that also checks section activation.
 */
class CustomFieldsActivableScope extends ActivableScope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    #[Override]
    public function apply(Builder $builder, Model $model): void
    {
        if (method_exists($model, 'getQualifiedActiveColumn')) {
            $builder->where($model->getQualifiedActiveColumn(), true)
                ->whereHas('section', function ($query) {
                    /** @phpstan-ignore-next-line */
                    $query->active();
                });
        }
    }
}
