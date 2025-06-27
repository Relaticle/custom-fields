<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Support;

use Relaticle\CustomFields\Services\FieldTypeRegistryService;
use Exception;
use Illuminate\Support\Facades\Log;
use Relaticle\CustomFields\Enums\CustomFieldType;

/**
 * Handles safe conversion of values to database-compatible formats
 * to prevent issues like numeric overflow.
 */
class SafeValueConverter
{
    /**
     * Maximum allowable integer for BIGINT in most SQL databases
     */
    public const MAX_BIGINT = PHP_INT_MAX; // Explicit value instead of PHP_INT_MAX

    /**
     * Minimum allowable integer for BIGINT in most SQL databases
     */
    public const MIN_BIGINT = PHP_INT_MIN; // Explicit value instead of PHP_INT_MIN

    /**
     * Safely convert a value to the appropriate type for database storage.
     *
     * @param  mixed  $value  The value to convert
     * @param  CustomFieldType|string  $fieldType  The field type
     * @return mixed The converted value
     */
    public static function toDbSafe(mixed $value, CustomFieldType|string $fieldType): mixed
    {
        // Handle enum types
        if ($fieldType instanceof CustomFieldType) {
            return match ($fieldType) {
                CustomFieldType::NUMBER, CustomFieldType::RADIO, CustomFieldType::SELECT => self::toSafeInteger($value),
                CustomFieldType::CURRENCY => self::toSafeFloat($value),
                CustomFieldType::CHECKBOX_LIST, CustomFieldType::TOGGLE_BUTTONS, CustomFieldType::TAGS_INPUT, CustomFieldType::MULTI_SELECT => self::toSafeArray($value),
                default => $value,
            };
        }

        // Handle custom field types (strings) - determine conversion based on category
        if (app()->bound(FieldTypeRegistryService::class)) {
            $registry = app(FieldTypeRegistryService::class);
            $fieldTypeConfig = $registry->getFieldType($fieldType);

            if ($fieldTypeConfig !== null) {
                return match ($fieldTypeConfig['category']) {
                    'numeric' => self::toSafeInteger($value),
                    'multi_option' => self::toSafeArray($value),
                    default => $value,
                };
            }
        }

        // Fallback - return value as-is
        return $value;
    }

    /**
     * Convert a value to a safe integer within BIGINT bounds.
     *
     * @param  mixed  $value  The value to convert
     * @return int|null The safe integer value or null if invalid
     */
    public static function toSafeInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Handle string numbers including scientific notation
        if (is_string($value) && preg_match('/^-?[0-9.]+(?:e[+-]?[0-9]+)?$/i', $value)) {
            // Convert to float first to handle scientific notation
            $floatVal = (float) $value;
            // Check bounds
            if ($floatVal > self::MAX_BIGINT) {
                Log::warning("Integer value too large for database: {$value}, clamping to max BIGINT");
                return (int) self::MAX_BIGINT;
            }

            // Check bounds
            if ($floatVal < self::MIN_BIGINT) {
                Log::warning("Integer value too small for database: {$value}, clamping to min BIGINT");
                return (int) self::MIN_BIGINT;
            }

            // Ensure we return an integer by explicit casting
            return (int) $floatVal;
        }

        // For numeric values, check bounds directly
        if (is_numeric($value)) {
            $numericVal = (float) $value;
            if ($numericVal > self::MAX_BIGINT) {
                return (int) self::MAX_BIGINT;
            }
            if ($numericVal < self::MIN_BIGINT) {
                return (int) self::MIN_BIGINT;
            }

            // Explicitly cast to integer to ensure the correct return type
            return (int) $numericVal;
        }

        // For non-numeric values, return null
        return null;
    }

    /**
     * Convert a value to a safe float within database bounds.
     *
     * @param  mixed  $value  The value to convert
     * @return float|null The safe float value or null if invalid
     */
    public static function toSafeFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value) && preg_match('/^-?[0-9.]+(?:e[+-]?[0-9]+)?$/i', $value)) {
            return (float) $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    /**
     * Convert a value to a safe array for JSON storage.
     *
     * @param  mixed  $value  The value to convert
     * @return array<int, mixed>|null The safe array value or null if invalid
     */
    public static function toSafeArray(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            try {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            } catch (Exception $e) {
                Log::warning("Failed to decode JSON value: {$e->getMessage()}");
            }

            // Fallback for string - try to split by comma
            return array_map('trim', explode(',', $value));
        }

        if (is_array($value)) {
            return $value;
        }

        // If it's a single non-array value, wrap it in an array
        return [$value];
    }
}
