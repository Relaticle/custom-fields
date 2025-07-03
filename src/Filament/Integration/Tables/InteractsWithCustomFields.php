<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Tables;

use Exception;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\CustomFields\Integration\Tables\Columns\CustomFieldsColumn;
use Relaticle\CustomFields\Integration\Tables\Filters\CustomFieldsFilter;

trait InteractsWithCustomFields
{
    /**
     * @throws BindingResolutionException
     */
    public function table(Table $table): Table
    {
        $model = $this instanceof RelationManager ? $this->getRelationship()->getModel()::class : $this->getModel();
        $instance = app($model);

        try {
            $table = static::getResource()::table($table);
        } catch (Exception) {
            $table = parent::table($table);
        }

        return $table->modifyQueryUsing(function (Builder $query): void {
            $query->with('customFieldValues.customField');
        })
            ->deferFilters(false)
            ->pushColumns(CustomFieldsColumn::all($instance))
            ->pushFilters(CustomFieldsFilter::all($instance));
    }
}
