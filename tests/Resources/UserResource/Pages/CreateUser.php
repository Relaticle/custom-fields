<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Tests\Resources\UserResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Relaticle\CustomFields\Tests\Resources\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
