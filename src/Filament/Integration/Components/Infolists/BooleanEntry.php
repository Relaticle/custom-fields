<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Infolists;

use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\IconEntry as BaseIconEntry;
use Relaticle\CustomFields\Filament\Integration\Base\AbstractInfolistEntry;
use Relaticle\CustomFields\Filament\Integration\Concerns\Forms\ConfiguresFieldName;
use Relaticle\CustomFields\Models\CustomField;

final class BooleanEntry extends AbstractInfolistEntry
{
    use ConfiguresFieldName;

    public function make(CustomField $customField): Entry
    {
        return BaseIconEntry::make($this->getFieldName($customField))
            ->boolean()
            ->label($customField->name);
    }
}
