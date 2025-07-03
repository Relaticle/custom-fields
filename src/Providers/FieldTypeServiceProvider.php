<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Providers;

use Illuminate\Support\ServiceProvider;
use Relaticle\CustomFields\Facades\CustomFieldsType;
use Relaticle\CustomFields\FieldTypes\SelectFieldType;

class FieldTypeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
//        $this->app->singleton(
//            'field-type-registry',
//            \Relaticle\CustomFields\Services\FieldTypeRegistry::class
//        );
//        CustomFieldsType::register();
    }
}
