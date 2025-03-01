<?php

namespace Relaticle\CustomFields\Tests\Resources\PageResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Relaticle\CustomFields\Tests\Resources\PageResource;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
