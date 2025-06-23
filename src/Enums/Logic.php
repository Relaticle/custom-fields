<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Enums;

/**
 * Logic for combining multiple conditions.
 */
enum Logic: string
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

    public function evaluate(array $results): bool
    {
        if (empty($results)) {
            return false;
        }

        return match ($this) {
            self::ALL => ! in_array(false, $results, true),
            self::ANY => in_array(true, $results, true),
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $logic) => [$logic->value => $logic->getLabel()])
            ->toArray();
    }
}
