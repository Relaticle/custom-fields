<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Infolists\Fields;

use Filament\Infolists\Components\ColorEntry as BaseColorEntry;
use Filament\Infolists\Components\Entry;
use Relaticle\CustomFields\Filament\Infolists\FieldInfolistsComponentInterface;
use Relaticle\CustomFields\Filament\Infolists\FieldInfolistsConfigurator;
use Relaticle\CustomFields\Models\CustomField;

final readonly class ColorEntry implements FieldInfolistsComponentInterface
{
    public function __construct(private FieldInfolistsConfigurator $configurator) {}

    public function make(CustomField $customField): Entry
    {
        return $this->configurator->configure(
            BaseColorEntry::make("custom_fields.{$customField->code}"),
            $customField
        );
    }
}
