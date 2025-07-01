<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use Relaticle\CustomFields\Models\CustomField;

final readonly class CurrencyComponent extends AbstractFormComponent
{
    public function create(CustomField $customField): Field
    {
        return TextInput::make("custom_fields.{$customField->code}")
            ->prefix('$')
            ->numeric()
            ->inputMode('decimal')
            ->step(0.01)
            ->minValue(0)
            ->default(0)
            ->rules(['numeric', 'min:0'])
            ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2))
            ->dehydrateStateUsing(fn (mixed $state): float => Str::of($state)->replace(['$', ','], '')->toFloat());
    }
}
