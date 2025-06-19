<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Schemas;

interface SectionFormInterface
{
    public static function entityType(string $entityType): self;
}
