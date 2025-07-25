<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SortOrderScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->orderBy('sort_order');
    }
}
