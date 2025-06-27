<?php

declare(strict_types=1);

use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;

arch('Models extend Eloquent Model')
    ->expect([
        'Relaticle\CustomFields\Models\CustomField',
        'Relaticle\CustomFields\Models\CustomFieldSection',
        'Relaticle\CustomFields\Models\CustomFieldOption',
        'Relaticle\CustomFields\Models\CustomFieldValue',
    ])
    ->toExtend(Model::class);

arch('Filament Resource extends base Resource')
    ->expect('Relaticle\CustomFields\Tests\Fixtures\Resources\Posts\PostResource')
    ->toExtend(Resource::class);

arch('Filament Resource Pages extend base Page')
    ->expect('Relaticle\CustomFields\Tests\Fixtures\Resources\Posts\Pages')
    ->toExtend(Page::class);

arch('No debugging functions are used')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('Enums are backed by strings or integers')
    ->expect('Relaticle\CustomFields\Enums')
    ->toBeEnums();

arch('Factories extend Laravel Factory')
    ->expect('Relaticle\CustomFields\Database\Factories')
    ->toExtend(Illuminate\Database\Eloquent\Factories\Factory::class);

arch('Custom field models implement HasCustomFields contract')
    ->expect('Relaticle\CustomFields\Tests\Fixtures\Models\Post')
    ->toImplement(HasCustomFields::class)
    ->toUse('Relaticle\CustomFields\Models\Concerns\UsesCustomFields');

arch('Observers follow naming convention')
    ->expect('Relaticle\CustomFields\Observers')
    ->toHaveSuffix('Observer');

arch('Middleware follows naming convention')
    ->expect('Relaticle\CustomFields\Http\Middleware')
    ->toHaveSuffix('Middleware');

arch('Exceptions follow naming convention')
    ->expect('Relaticle\CustomFields\Exceptions')
    ->toHaveSuffix('Exception');

arch('Main commands follow naming convention')
    ->expect([
        'Relaticle\CustomFields\Commands\FilamentCustomFieldCommand',
        'Relaticle\CustomFields\Commands\OptimizeDatabaseCommand',
        'Relaticle\CustomFields\Commands\UpgradeCommand',
    ])
    ->toHaveSuffix('Command');

arch('Jobs follow proper structure')
    ->expect('Relaticle\CustomFields\Jobs')
    ->not->toHaveSuffix('Job');

arch('Data objects extend Spatie Data')
    ->expect('Relaticle\CustomFields\Data')
    ->toExtend('Spatie\LaravelData\Data');
