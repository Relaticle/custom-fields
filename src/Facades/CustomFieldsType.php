<?php

namespace Relaticle\CustomFields\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use Relaticle\CustomFields\FieldTypes\FieldTypeManager;

/**
 * @method static array<string, array<int | string, string | int>> getColors()
 * @method static ?array<int | string, string | int> getColor(string $color)
 * @method static array<string> getComponentClasses(class-string<HasColor> | HasColor $component, ?string $color)
 * @method static array<string> getComponentCustomStyles(class-string<HasColor> | HasColor $component, array<string> $color)
 * @method static void addShades(string $alias, array<int> $shades)
 * @method static array<int> | null getAddedShades(string $alias)
 * @method static array<int> | null getOverridingShades(string $alias)
 * @method static array<int> | null getRemovedShades(string $alias)
 * @method static void overrideShades(string $alias, array<int> $shades)
 * @method static void removeShades(string $alias, array<int> $shades)
 *
 * @see FieldTypeManager
 */
class CustomFieldsType extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FieldTypeManager::class;
    }

    /**
     * @param  array<string, array<int | string, string | int> | string> | Closure  $fieldTypes
     */
    public static function register(array | Closure $fieldTypes): void
    {
        static::resolved(function (FieldTypeManager $fieldTypeManager) use ($fieldTypes): void {
            $fieldTypeManager->register($fieldTypes);
        });
    }
}
