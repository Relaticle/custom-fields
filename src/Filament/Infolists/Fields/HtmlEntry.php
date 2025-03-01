<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Infolists\Fields;

use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\TextEntry as BaseTextEntry;
use Relaticle\CustomFields\Filament\Infolists\FieldInfolistsComponentInterface;
use Relaticle\CustomFields\Filament\Infolists\FieldInfolistsConfigurator;
use Relaticle\CustomFields\Models\CustomField;

final readonly class HtmlEntry implements FieldInfolistsComponentInterface
{
    public function __construct(private FieldInfolistsConfigurator $configurator) {}

    public function make(CustomField $customField): Entry
    {
        return $this->configurator->configure(
            BaseTextEntry::make("custom_fields.{$customField->code}")
                ->html(),
            $customField
        );
    }
}
