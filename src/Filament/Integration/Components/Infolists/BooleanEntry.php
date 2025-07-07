<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Infolists;

use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\IconEntry as BaseIconEntry;
use Relaticle\CustomFields\Filament\Integration\Infolists\FieldInfolistsConfigurator;
use Relaticle\CustomFields\Models\CustomField;

final readonly class BooleanEntry implements FieldInfolistsComponentInterface
{
    public function __construct(private FieldInfolistsConfigurator $configurator) {}

    public function make(CustomField $customField): Entry
    {
        return $this->configurator->configure(
            BaseIconEntry::make("custom_fields.{$customField->code}")
                ->boolean(),
            $customField
        );
    }
}
