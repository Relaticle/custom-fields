<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Forms;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Toggle;
use Relaticle\CustomFields\Models\CustomField;

final readonly class ToggleComponent extends AbstractFormComponent
{
    public function create(CustomField $customField): Field
    {
        return Toggle::make($this->getFieldName($customField))
            ->onColor('success')
            ->offColor('danger')
            ->inline(false);
    }
}
