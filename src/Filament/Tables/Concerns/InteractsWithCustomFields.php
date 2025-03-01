<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Tables\Concerns;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\CustomFields\Filament\Tables\Columns\CustomFieldsColumn;
use Relaticle\CustomFields\Filament\Tables\Filter\CustomFieldsFilter;

trait InteractsWithCustomFields
{
    public function table(Table $table): Table
    {
        $model = $this instanceof RelationManager ? $this->getRelationship()->getModel()::class : $this->getModel();
        $instance = app($model);

        return static::getResource()::table($table)
            ->modifyQueryUsing(function (Builder $query) {
                $query->with('customFieldValues.customField');
            })
            ->pushColumns(CustomFieldsColumn::all($instance))
            ->pushFilters(CustomFieldsFilter::all($instance));
    }
}
