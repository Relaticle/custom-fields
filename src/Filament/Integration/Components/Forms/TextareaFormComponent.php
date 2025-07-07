<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Forms;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Relaticle\CustomFields\Filament\Integration\Base\AbstractFormComponent;
use Relaticle\CustomFields\Models\CustomField;

final readonly class TextareaFormComponent extends AbstractFormComponent
{
    public function create(CustomField $customField): Field
    {
        return Textarea::make($this->getFieldName($customField))
            ->rows(3)
            ->maxLength(50000)
            ->placeholder(null);
    }
}
