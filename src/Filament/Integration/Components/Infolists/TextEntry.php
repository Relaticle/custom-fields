<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Infolists;

use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\TextEntry as BaseTextEntry;
use Relaticle\CustomFields\Filament\Integration\Base\AbstractInfolistEntry;
use Relaticle\CustomFields\Filament\Integration\Concerns\Forms\ConfiguresFieldName;
use Relaticle\CustomFields\Models\CustomField;

final class TextEntry extends AbstractInfolistEntry
{
    use ConfiguresFieldName;

    public function make(CustomField $customField): Entry
    {
        return BaseTextEntry::make($this->getFieldName($customField))
            ->label($customField->name);
    }
}
