<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Builders;

use Illuminate\Database\Eloquent\Model;
use Relaticle\CustomFields\Enums\ResolutionKind;

final readonly class FieldResolutionContext
{
    /**
     * @param  class-string<Model>  $entityType
     */
    public function __construct(
        public string $entityType,
        public ResolutionKind $kind,
        public ?Model $record = null,
    ) {}
}
