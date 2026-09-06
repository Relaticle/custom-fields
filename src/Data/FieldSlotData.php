<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class FieldSlotData extends Data
{
    /**
     * Create a new instance of the FieldSlotData class.
     *
     * @param  string  $name  The display name of the record field rendering this end.
     * @param  int|string|null  $sectionId  The section the field belongs to.
     */
    public function __construct(
        public string $name,
        public int|string|null $sectionId = null,
    ) {}
}
