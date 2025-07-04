<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Tables;

use Exception;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\CustomFields\Facades\CustomFields;

trait InteractsWithCustomFields
{
    /**
     * @throws BindingResolutionException
     */
    public function table(Table $table): Table
    {
        $model = $this instanceof RelationManager ? $this->getRelationship()->getModel()::class : $this->getModel();

        try {
            $table = static::getResource()::table($table);
        } catch (Exception) {
            $table = parent::table($table);
        }

        $columns = CustomFields::tableColumns()->make($model)->all();
        $filters = CustomFields::tableFilters()->make($model)->all();

        return $table->modifyQueryUsing(function (Builder $query): void {
            $query->with('customFieldValues.customField');
        })
            ->deferFilters(false)
            ->pushColumns($columns)
            ->pushFilters($filters);
    }
}
