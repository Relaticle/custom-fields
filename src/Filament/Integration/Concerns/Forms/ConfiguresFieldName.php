<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Concerns\Forms;

use Relaticle\CustomFields\Models\CustomField;

trait ConfiguresFieldName
{
    protected function getFieldName(CustomField $customField): string
    {
        return "custom_fields.{$customField->code}";
    }
}
