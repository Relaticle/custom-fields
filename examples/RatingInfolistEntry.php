<?php

declare(strict_types=1);

namespace Examples;

use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\TextEntry;
use Relaticle\CustomFields\Integration\Infolists\FieldInfolistsComponentInterface;
use Relaticle\CustomFields\Models\CustomField;

/**
 * Infolist entry component for rating field type.
 *
 * This displays the rating as star symbols in detail views.
 */
class RatingInfolistEntry implements FieldInfolistsComponentInterface
{
    public function make(CustomField $customField): Entry
    {
        return TextEntry::make($customField->code)
            ->label($customField->name)
            ->formatStateUsing(function (?string $state): string {
                if ($state === null || $state === '') {
                    return 'No rating';
                }

                $rating = (int) $state;

                if ($rating < 1 || $rating > 5) {
                    return 'Invalid rating';
                }

                $stars = str_repeat('⭐', $rating);
                $emptyStars = str_repeat('☆', 5 - $rating);

                return $stars.$emptyStars.' ('.$rating.' out of 5)';
            })
            ->color(function (?string $state): string {
                if ($state === null || $state === '') {
                    return 'gray';
                }

                $rating = (int) $state;

                return match ($rating) {
                    1, 2 => 'danger',
                    3 => 'warning',
                    4, 5 => 'success',
                    default => 'gray',
                };
            });
    }
}
