<?php

declare(strict_types=1);

namespace Examples;

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Relaticle\CustomFields\Integration\Tables\Columns\ColumnInterface;
use Relaticle\CustomFields\Models\CustomField;

/**
 * Table column component for rating field type.
 *
 * This displays the rating as star symbols in table views.
 */
class RatingTableColumn implements ColumnInterface
{
    public function make(CustomField $customField): Column
    {
        return TextColumn::make($customField->code)
            ->label($customField->name)
            ->formatStateUsing(function (?string $state): string {
                if ($state === null || $state === '') {
                    return '—';
                }

                $rating = (int) $state;

                if ($rating < 1 || $rating > 5) {
                    return '—';
                }

                return str_repeat('⭐', $rating).' ('.$rating.')';
            })
            ->sortable()
            ->searchable(false)
            ->toggleable()
            ->alignCenter();
    }
}
