<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Infolists;

use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\TextEntry as BaseTextEntry;
use Relaticle\CustomFields\Contracts\InfolistComponentInterface;
use Relaticle\CustomFields\Filament\Integration\Concerns\Shared\ConfiguresBadgeColors;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\ValueResolver\LookupMultiValueResolver;

final readonly class MultiChoiceEntry implements InfolistComponentInterface
{
    use ConfiguresBadgeColors;

    public function __construct(
        private FieldInfolistsConfigurator $configurator,
        private LookupMultiValueResolver $valueResolver
    ) {}

    public function make(CustomField $customField): Entry
    {
        $entry = BaseTextEntry::make("custom_fields.{$customField->code}");

        $entry = $this->applyBadgeColorsIfEnabled($entry, $customField);

        return $this->configurator->configure(
            $entry,
            $customField
        )
            ->state(fn ($record): array => $this->valueResolver->resolve($record, $customField));
    }
}
