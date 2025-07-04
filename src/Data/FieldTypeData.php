<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Data;

use Relaticle\CustomFields\Enums\FieldDataType;
use Spatie\LaravelData\Data;

final class FieldTypeData extends Data
{
    public function __construct(
        public string $key,
        public string $label,
        public string $icon,
        public FieldDataType $dataType,
        public string $tableColumn,
        public ?string $tableFilter = null,
        public string $formComponent,
        public string $infolistEntry,
        public bool $searchable = false,
        public bool $sortable = false,
        public bool $filterable = false,
    ) {}

    public function __toString(): string
    {
        return $this->key;
    }
}
