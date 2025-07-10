<?php

namespace Relaticle\CustomFields\Observers;

use Relaticle\CustomFields\Models\CustomFieldSection;

class CustomFieldSectionObserver
{
    public function deleted(CustomFieldSection $customFieldSection): void
    {
        /** @phpstan-ignore-next-line */
        $customFieldSection->fields()->withDeactivated()->delete();
    }
}
