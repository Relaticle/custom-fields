<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services;

use Filament\Facades\Filament;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;

abstract class AbstractOptionsService
{
    protected static string $allowedConfigKey;

    protected static string $disallowedConfigKey;

    /**
     * Get the options for attribute types.
     *
     * @return Collection<string, string>
     */
    public static function getOptions(): Collection
    {
        return static::getFilteredResources()
            ->mapWithKeys(fn (string $resource) => static::mapResourceToOption($resource));
    }

    public static function getDefaultOption(): string
    {
        return static::getOptions()->keys()->first() ?: '';
    }

    protected static function getFilteredResources(): Collection
    {
        return collect(Filament::getResources())
            ->reject(fn (string $resource) => static::shouldRejectResource($resource));
    }

    protected static function shouldRejectResource(string $resource): bool
    {
        $allowedResources = config(static::$allowedConfigKey, []);
        $disallowedResources = config(static::$disallowedConfigKey, []);

        return (! empty($allowedResources) && ! in_array($resource, $allowedResources))
            || in_array($resource, $disallowedResources);
    }

    /**
     * @throws BindingResolutionException
     */
    protected static function mapResourceToOption(string $resource): array
    {
        $resourceInstance = app($resource);
        $model = $resourceInstance->getModel();
        $alias = self::getEntityFromModel($model);

        return [$alias => $resourceInstance::getBreadcrumb()];
    }

    public static function getEntityFromModel(string $model): string
    {
        try {
            $modelInstance = app($model);

            return $modelInstance->getMorphClass();
        } catch (\Exception $e) {
            return $model;
        }
    }
}
