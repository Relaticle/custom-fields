<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts\Components;

use Filament\Infolists\Components\Entry;
use Relaticle\CustomFields\Models\CustomField;

interface InfolistEntryInterface
{
    public function makeInfolistEntry(CustomField $customField): Entry;
}
