<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Relaticle\CustomFields\Filament\Integration\Concerns\Forms\ConfiguresFieldName;
use Relaticle\CustomFields\Models\CustomField;

final readonly class TextInputComponent extends AbstractFormComponent
{
    use ConfiguresFieldName;

    public function create(CustomField $customField): Field
    {
        return TextInput::make($this->getFieldName($customField))
            ->maxLength(255)
            ->placeholder(null);
    }
}
