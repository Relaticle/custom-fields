<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services;

use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;
use Throwable;

final class FilamentResourceService
{
    /**
     * Get the resource instance for a given model.
     *
     * @throws InvalidArgumentException|Throwable
     */
    public static function getResourceInstance(string $model): Resource
    {
        $modelPath = Relation::getMorphedModel($model) ?? $model;
        $resourceInstance = Filament::getModelResource($modelPath);

        throw_if(! $resourceInstance, new InvalidArgumentException("No resource found for model: {$modelPath}"));

        return App::make($resourceInstance);
    }

    /**
     * Get the model instance for a given model string.
     *
     * @throws InvalidArgumentException|Throwable
     */
    public static function getModelInstance(string $model): Model
    {
        $model = Relation::getMorphedModel($model) ?: $model;

        throw_if(! $model, new InvalidArgumentException("Model class not found: {$model}"));

        return app($model);
    }

    /**
     * Get the record title attribute for a given model.
     *
     * @throws InvalidArgumentException|Throwable
     */
    public static function getRecordTitleAttribute(string $model): string
    {
        $resourceInstance = self::getResourceInstance($model);
        $recordTitleAttribute = $resourceInstance->getRecordTitleAttribute();

        throw_if($recordTitleAttribute === null, new InvalidArgumentException(sprintf(
            "The '%s' resource does not have a record title attribute.",
            get_class($resourceInstance)
        )));

        return $recordTitleAttribute;
    }

    /**
     * Get the globally searchable attributes for a given model.
     *
     * @throws Throwable
     */
    public static function getGlobalSearchableAttributes(string $model): array
    {
        return self::getResourceInstance($model)->getGloballySearchableAttributes();
    }
}
