<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Forms;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Field;
use Relaticle\CustomFields\Filament\Integration\Base\AbstractFormComponent;
use Relaticle\CustomFields\Models\CustomField;

final readonly class CheckboxComponent extends AbstractFormComponent
{
    public function create(CustomField $customField): Field
    {
        return Checkbox::make($this->getFieldName($customField))->inline(false);
    }
}
