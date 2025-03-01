<?php

namespace Relaticle\CustomFields\Data;

use Relaticle\CustomFields\Support\Utils;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class CustomFieldSettingsData extends Data
{
    public function __construct(
        public bool $visible_in_list = true,
        public ?bool $list_toggleable_hidden = null,
        public bool $visible_in_view = true,
        public bool $searchable = false,
        public bool $encrypted = false,
    ) {
        if ($this->list_toggleable_hidden === null) {
            $this->list_toggleable_hidden = Utils::isTableColumnsToggleableHiddenByDefault();
        }
    }
}
