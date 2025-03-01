<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Infolists\Fields;

use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\TextEntry as BaseTextEntry;
use Relaticle\CustomFields\Filament\Infolists\FieldInfolistsComponentInterface;
use Relaticle\CustomFields\Filament\Infolists\FieldInfolistsConfigurator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\ValueResolver\LookupSingleValueResolver;

final readonly class SingleValueEntry implements FieldInfolistsComponentInterface
{
    public function __construct(
        private FieldInfolistsConfigurator $configurator,
        private LookupSingleValueResolver $valueResolver
    ) {}

    public function make(CustomField $customField): Entry
    {
        return $this->configurator->configure(
            BaseTextEntry::make("custom_fields.{$customField->code}"),
            $customField
        )
            ->getStateUsing(fn ($record) => $this->valueResolver->resolve($record, $customField));
    }
}
