<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Tables\Columns;

use Filament\Support\Colors\Color;
use Filament\Tables\Columns\Column as BaseColumn;
use Filament\Tables\Columns\TextColumn as BaseTextColumn;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\ValueResolver\LookupMultiValueResolver;
use Relaticle\CustomFields\Support\Utils;

final readonly class MultiValueColumn implements ColumnInterface
{
    public function __construct(public LookupMultiValueResolver $valueResolver) {}

    public function make(CustomField $customField): BaseColumn
    {
        $column = BaseTextColumn::make("custom_fields.$customField->code")
            ->label($customField->name)
            ->sortable(false)
            ->searchable(false);

        $column->getStateUsing(fn ($record) => $this->valueResolver->resolve($record, $customField));

        if (Utils::isSelectOptionColorsFeatureEnabled() && $customField->settings->enable_option_colors && ! $customField->lookup_type) {
            $column->badge()
                ->color(function ($state) use ($customField): ?array {
                    $color = $customField->options->where('name', $state)->first()?->settings->color;

                    return Color::hex($color ?? '#000000');
                });
        }

        return $column;
    }
}
