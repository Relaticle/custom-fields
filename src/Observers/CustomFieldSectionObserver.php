<?php

namespace Relaticle\CustomFields\Observers;

use Relaticle\CustomFields\Models\CustomFieldSection;

class CustomFieldSectionObserver
{
    public function deleted(CustomFieldSection $customFieldSection): void
    {
        $customFieldSection->fields()->delete();
    }
}
