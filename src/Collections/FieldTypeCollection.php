<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Collections;

use Illuminate\Support\Collection;
use Relaticle\CustomFields\Data\FieldTypeData;

/**
 * @extends Collection<array-key, FieldTypeData>
 */
final class FieldTypeCollection extends Collection
{
    public function acceptsArbitraryValues(): static
    {
        return $this->filter(fn (FieldTypeData $fieldType): bool => $fieldType->acceptsArbitraryValues);
    }
}
