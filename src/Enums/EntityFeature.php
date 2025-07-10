<?php

// ABOUTME: Enum defining available features for entity configurations
// ABOUTME: Replaces string constants with type-safe enum values

declare(strict_types=1);

namespace Relaticle\CustomFields\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum EntityFeature: string implements HasLabel
{
    case CUSTOM_FIELDS = 'custom_fields';
    case LOOKUP_SOURCE = 'lookup_source';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::CUSTOM_FIELDS => 'Custom Fields',
            self::LOOKUP_SOURCE => 'Lookup Source',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::CUSTOM_FIELDS => 'Entity can have custom fields attached',
            self::LOOKUP_SOURCE => 'Entity can be used as a lookup source for choice fields',
        };
    }
}
