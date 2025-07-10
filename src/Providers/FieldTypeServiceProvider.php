<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Providers;

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;

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

    public function boot(): void
    {
        Table::configureUsing(function (Table $table): void {
            $table->modifyQueryUsing(function (Builder $query): void {
                $query->when($query->getModel() instanceof HasCustomFields, fn (Builder $q) => $q->with('customFieldValues.customField'));
            });
        });
    }
}
