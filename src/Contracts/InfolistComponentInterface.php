<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts;

use Filament\Infolists\Components\Entry;
use Relaticle\CustomFields\Models\CustomField;

interface InfolistComponentInterface
{
    public function make(CustomField $customField): Entry;
}
