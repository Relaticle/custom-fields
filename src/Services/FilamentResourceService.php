<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services;

use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use Relaticle\CustomFields\Support\Utils;
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
        $model = Relation::getMorphedModel($model) ?? $model;

        throw_if(! $model, new InvalidArgumentException("Model class not found: {$model}"));

        return app($model);
    }

    /**
     * @return Builder<Model>
     *
     * @throws Throwable
     * @throws ReflectionException
     */
    public static function getModelInstanceQuery(string $model): Builder
    {
        $modelInstance = self::getModelInstance($model);
        $query = $modelInstance->newQuery();

        if (Utils::isTenantEnabled() && Filament::getTenant()) {
            return self::invokeMethodByReflection(
                resource: self::getResourceInstance($model),
                methodName: 'scopeEloquentQueryToTenant',
                args: [
                    'query' => $query,
                    'tenant' => Filament::getTenant(),
                ]);
        }

        return $query;
    }

    /**
     * Get the record title attribute for a given model.
     *
     * @throws InvalidArgumentException|Throwable
     */
    public static function getRecordTitleAttribute(string $model): string
    {
        $resourceInstance = self::getResourceInstance($model);
        /** @var string|null $recordTitleAttribute */
        $recordTitleAttribute = $resourceInstance->getRecordTitleAttribute();

        throw_if($recordTitleAttribute === null, new InvalidArgumentException(sprintf(
            "The '%s' resource does not have a record title attribute.",
            $resourceInstance::class
        )));

        return $recordTitleAttribute;
    }

    /**
     * Get the globally searchable attributes for a given model.
     *
     * @return array<string>
     *
     * @throws Throwable
     */
    public static function getGlobalSearchableAttributes(string $model): array
    {
        /** @var array<string> $attributes */
        $attributes = self::getResourceInstance($model)->getGloballySearchableAttributes();

        return $attributes;
    }

    /**
     * Invoke a method on a Resource class using reflection
     *
     * @param  resource  $resource  The resource instance or class name
     * @param  string  $methodName  The name of the method to call
     * @param  array<string, mixed>  $args  The arguments to pass to the method
     * @return mixed The return value from the method
     *
     * @throws ReflectionException
     */
    public static function invokeMethodByReflection(Resource $resource, string $methodName, array $args = []): mixed
    {
        $reflectionClass = new ReflectionClass($resource);

        if ($reflectionClass->hasMethod($methodName)) {
            $method = $reflectionClass->getMethod($methodName);
            $method->setAccessible(true);

            return $method->invoke($resource, ...$args);
        }

        return null;
    }
}
