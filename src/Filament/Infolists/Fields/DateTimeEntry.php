<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Infolists\Fields;

use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\TextEntry;
use Relaticle\CustomFields\Filament\Infolists\FieldInfolistsComponentInterface;
use Relaticle\CustomFields\Filament\Infolists\FieldInfolistsConfigurator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\FieldTypeUtils;

final readonly class DateTimeEntry implements FieldInfolistsComponentInterface
{
    public function __construct(private FieldInfolistsConfigurator $configurator) {}

    public function make(CustomField $customField): Entry
    {
        $field = TextEntry::make("custom_fields.{$customField->code}")
            ->dateTime(FieldTypeUtils::getDateTimeFormat())
            ->placeholder(FieldTypeUtils::getDateTimeFormat());

        return $this->configurator->configure(
            $field,
            $customField
        );
    }
}
