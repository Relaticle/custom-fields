<?php

namespace Relaticle\CustomFields\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class CustomFieldConditionsData extends Data
{
    public function __construct(
        public string $enabled = 'always', // 'always', 'if', or 'unless' (Always show, Show when conditions are met, or Hide when conditions are met)
        public string $logic = 'all', // 'all'(All the conditions must be met) or 'any' (Any of the conditions can be met)
        public ?array $conditions = null, // Array of conditions, each condition is an array with 'field', 'operator', and 'value'
        public ?bool $always_save = false, // Whether to always save the field value even if hidden
    ) {
    }
}
