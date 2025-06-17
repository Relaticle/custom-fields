<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Tests\Resources\UserResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Relaticle\CustomFields\Filament\Tables\Concerns\InteractsWithCustomFields;
use Relaticle\CustomFields\Tests\Resources\UserResource;

class ListUsers extends ListRecords
{
    use InteractsWithCustomFields;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
