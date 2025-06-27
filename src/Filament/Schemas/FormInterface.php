<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Schemas;

use Filament\Schemas\Components\Component;

interface FormInterface
{
    /**
     * @return array<int, Component>
     */
    public static function schema(): array;
}
