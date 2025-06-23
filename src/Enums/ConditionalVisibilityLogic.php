<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Enums;

use Filament\Support\Contracts\HasLabel;

enum ConditionalVisibilityLogic: string implements HasLabel
{
    case ALL = 'all';
    case ANY = 'any';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ALL => 'All conditions must be met (AND)',
            self::ANY => 'Any condition can be met (OR)',
        };
    }

    /**
     * Get all options for select components.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::ALL->value => self::ALL->getLabel(),
            self::ANY->value => self::ANY->getLabel(),
        ];
    }

    /**
     * Evaluate multiple condition results based on the logic.
     *
     * @param  array<bool>  $results
     */
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
}
