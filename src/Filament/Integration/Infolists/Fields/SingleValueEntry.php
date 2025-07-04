<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Infolists\Fields;

use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\TextEntry as BaseTextEntry;
use Filament\Support\Colors\Color;
use Relaticle\CustomFields\Filament\Integration\Infolists\FieldInfolistsComponentInterface;
use Relaticle\CustomFields\Filament\Integration\Infolists\FieldInfolistsConfigurator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\ValueResolver\LookupSingleValueResolver;
use Relaticle\CustomFields\Support\Utils;

final readonly class SingleValueEntry implements FieldInfolistsComponentInterface
{
    public function __construct(
        private FieldInfolistsConfigurator $configurator,
        private LookupSingleValueResolver $valueResolver
    ) {}

    public function make(CustomField $customField): Entry
    {
        $entry = BaseTextEntry::make("custom_fields.{$customField->code}");

        if (Utils::isSelectOptionColorsFeatureEnabled() && $customField->settings->enable_option_colors && ! $customField->lookup_type) {
            $entry->badge()
                ->color(function ($state) use ($customField): array {
                    $color = $customField->options->where('name', $state)->first()?->settings->color;

                    return Color::hex($color ?? '#000000');
                });
        }

        return $this->configurator->configure(
            $entry,
            $customField
        )
            ->state(fn ($record): string => $this->valueResolver->resolve($record, $customField));
    }
}
