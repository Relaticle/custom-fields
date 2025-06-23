<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Enums;

use Filament\Support\Contracts\HasLabel;

enum ConditionalVisibilityMode: string implements HasLabel
{
    case ALWAYS = 'always';
    case SHOW_WHEN = 'if';
    case HIDE_WHEN = 'unless';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ALWAYS => 'Always Show',
            self::SHOW_WHEN => 'Show When',
            self::HIDE_WHEN => 'Hide When',
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
            self::ALWAYS->value => self::ALWAYS->getLabel(),
            self::SHOW_WHEN->value => self::SHOW_WHEN->getLabel(),
            self::HIDE_WHEN->value => self::HIDE_WHEN->getLabel(),
        ];
    }

    /**
     * Check if the mode requires conditions.
     */
    public function requiresConditions(): bool
    {
        return in_array($this, [self::SHOW_WHEN, self::HIDE_WHEN]);
    }

    /**
     * Check if the field should be shown based on the mode and condition results.
     */
    public function shouldShow(bool $conditionsResult): bool
    {
        return match ($this) {
            self::ALWAYS => true,
            self::SHOW_WHEN => $conditionsResult,
            self::HIDE_WHEN => ! $conditionsResult,
        };
    }
}
