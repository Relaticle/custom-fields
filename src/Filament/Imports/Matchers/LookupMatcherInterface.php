<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Imports\Matchers;

use Illuminate\Database\Eloquent\Model;

interface LookupMatcherInterface
{
    public function find(mixed $entityInstance, string $value): ?Model;
}
