<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Tables\Columns;

use Filament\Tables\Columns\Column as BaseColumn;
use Filament\Tables\Columns\TextColumn as BaseTextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;
use Relaticle\CustomFields\Services\ValueResolver\LookupSingleValueResolver;
use Relaticle\CustomFields\Support\Utils;
use Filament\Support\Colors\Color;

final readonly class SingleValueColumn implements ColumnInterface
{
    public function __construct(public LookupSingleValueResolver $valueResolver)
    {
    }

    public function make(CustomField $customField): BaseColumn
    {
        $column = BaseTextColumn::make("custom_fields.$customField->code")
            ->label($customField->name)
            ->sortable(
                condition: !$customField->settings->encrypted,
                query: function (Builder $query, string $direction) use ($customField): Builder {
                    $table = $query->getModel()->getTable();
                    $key = $query->getModel()->getKeyName();

                    return $query->orderBy(
                        $customField->values()
                            ->select($customField->getValueColumn())
                            ->whereColumn('custom_field_values.entity_id', "$table.$key")
                            ->limit(1),
                        $direction
                    );
                }
            )
            ->getStateUsing(fn($record) => $this->valueResolver->resolve($record, $customField))
            ->searchable(false);

        // Use colored badge for field with enabled option colors
        if (Utils::isOptionColorsFeatureEnabled() && $customField->settings->enable_option_colors && !$customField->lookup_type) {
            $column->badge()
                ->color(function ($state) use ($customField): ?array {
                    $color = $customField->options->where('name', $state)->first()?->settings->color;

                    return Color::hex($color ?? '#000000');
                });
        }

        return $column;
    }
}
