<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Relaticle\CustomFields\Data\FieldTypeData;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Facades\CustomFieldsType;

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
    ): FieldTypeData {
        return CustomFieldsType::getFieldType($value);
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
    ): string {
        return $value->key;
    }
}
