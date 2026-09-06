<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\QueryBuilders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use function Filament\Support\generate_search_column_expression;
use function Filament\Support\generate_search_term_expression;

/**
 * Search a lookup entity by the attributes its own configuration declares, case handling
 * borrowed from Filament so Postgres stays insensitive like the other drivers.
 */
final readonly class EntitySearchQuery
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<int, string>  $attributes
     * @return Builder<TModel>
     */
    public function apply(Builder $query, string $search, array $attributes): Builder
    {
        if ($attributes === []) {
            return $query;
        }

        $connection = $query->getModel()->getConnection();
        $term = generate_search_term_expression($search, null, $connection);

        return $query->where(function (Builder $nested) use ($attributes, $term, $connection): void {
            foreach ($attributes as $attribute) {
                if (! str_contains($attribute, '.')) {
                    $nested->orWhere(
                        generate_search_column_expression($nested->qualifyColumn($attribute), null, $connection),
                        'like',
                        sprintf('%%%s%%', $term),
                    );

                    continue;
                }

                $nested->orWhereHas(
                    Str::beforeLast($attribute, '.'),
                    fn (Builder $related): Builder => $related->where(
                        generate_search_column_expression($related->qualifyColumn(Str::afterLast($attribute, '.')), null, $connection),
                        'like',
                        sprintf('%%%s%%', $term),
                    ),
                );
            }
        });
    }
}
