<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Tests\Fixtures\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class Settings extends Page
{
    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                TextInput::make('name')->required(),
            ]);
    }

    public function save()
    {
        $this->form->getState();
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
