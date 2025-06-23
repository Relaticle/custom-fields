<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Enums;

/**
 * Simple visibility modes for conditional fields.
 */
enum Mode: string
{
    case ALWAYS_VISIBLE = 'always_visible';
    case SHOW_WHEN = 'show_when';
    case HIDE_WHEN = 'hide_when';

    public function getLabel(): string
    {
        return match ($this) {
            self::ALWAYS_VISIBLE => 'Always visible',
            self::SHOW_WHEN => 'Show when conditions are met',
            self::HIDE_WHEN => 'Hide when conditions are met',
        };
    }

    public function requiresConditions(): bool
    {
        return $this !== self::ALWAYS_VISIBLE;
    }

    public function shouldShow(bool $conditionsMet): bool
    {
        return match ($this) {
            self::ALWAYS_VISIBLE => true,
            self::SHOW_WHEN => $conditionsMet,
            self::HIDE_WHEN => ! $conditionsMet,
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $mode) => [$mode->value => $mode->getLabel()])
            ->toArray();
    }
}
