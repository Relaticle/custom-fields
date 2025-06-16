<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Tests\Pages;

use Relaticle\CustomFields\Filament\Pages\CustomFieldsPage;

class TestCustomFieldsPage extends CustomFieldsPage
{
    public static function canAccess(): bool
    {
        // Allow access for testing
        return true;
    }
}
