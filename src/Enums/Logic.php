<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Logic for combining multiple conditions.
 */
enum Logic: string implements HasLabel
{
    case ALL = 'all';
    case ANY = 'any';

    public function getLabel(): string
    {
        return match ($this) {
            self::ALL => 'All conditions must be met (AND)',
            self::ANY => 'Any condition must be met (OR)',
        };
    }

    /**
     * @param array<int, bool> $results
     */
    public function evaluate(array $results): bool
    {
        if ($results === []) {
            return false;
        }

        return match ($this) {
            self::ALL => ! in_array(false, $results, true),
            self::ANY => in_array(true, $results, true),
        };
    }
}
