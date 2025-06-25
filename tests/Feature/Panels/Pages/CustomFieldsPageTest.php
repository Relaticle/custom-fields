<?php

use Relaticle\CustomFields\Filament\Pages\CustomFieldsPage;

it('can render page', function (): void {
    $this->get(CustomFieldsPage::getUrl())
        ->assertSuccessful();
});