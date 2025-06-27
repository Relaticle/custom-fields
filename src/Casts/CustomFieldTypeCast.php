<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Services\FieldTypeRegistryService;

/**
 * Custom cast that handles both built-in and custom field types.
 *
 * @implements CastsAttributes<CustomFieldType|string|null, CustomFieldType|string|null>
 */
class CustomFieldTypeCast implements CastsAttributes
{
    /**
     * Cast the given value to a field type.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(
        Model $model,
        string $key,
        mixed $value,
        array $attributes
    ): CustomFieldType|string|null {
        if ($value === null) {
            return null;
        }

        // Try to cast to built-in enum first
        $builtInType = CustomFieldType::tryFrom($value);
        if ($builtInType !== null) {
            return $builtInType;
        }

        // If not a built-in type, check if it's a registered custom type
        if (app()->bound(FieldTypeRegistryService::class)) {
            $registry = app(FieldTypeRegistryService::class);
            if ($registry->hasFieldType($value)) {
                return $value; // Return as string for custom types
            }
        }

        // If neither built-in nor custom, return as string (for backward compatibility)
        return $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(
        Model $model,
        string $key,
        mixed $value,
        array $attributes
    ): ?string {
        if ($value === null) {
            return null;
        }

        // Handle enum values
        if ($value instanceof CustomFieldType) {
            return $value->value;
        }

        // All other values should be strings, convert to string if needed
        return (string) $value;
    }
}
