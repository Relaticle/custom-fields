<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\QueryBuilders;

use Illuminate\Database\Eloquent\Builder;
use Relaticle\CustomFields\Models\CustomField;

final readonly class ColumnSearchableQuery
{
    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $builder
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public function builder(Builder $builder, CustomField $customField, string $search): Builder
    {
        $table = $builder->getModel()->getTable();
        $key = $builder->getModel()->getKeyName();

        return $builder->whereHas('customFieldValues', function (Builder $builder) use ($customField, $search, $table, $key): void {
            $builder->where('custom_field_values.custom_field_id', $customField->id)
                ->where($customField->getValueColumn(), 'like', "%$search%")
                ->whereColumn('custom_field_values.entity_id', "$table.$key");
        });
    }
}
