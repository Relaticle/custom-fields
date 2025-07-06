<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Support;

use Exception;
use Illuminate\Support\Facades\Log;

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
     * @param  string  $fieldType  The field type
     * @return mixed The converted value
     */
    public static function toDbSafe(mixed $value, string $fieldType): mixed
    {
        // Handle field types by string value
        return match ($fieldType) {
            'number', 'radio', 'select' => self::toSafeInteger($value),
            'currency' => self::toSafeFloat($value),
            'checkbox_list', 'toggle_buttons', 'tags_input', 'multi_select' => self::toSafeArray($value),
            default => $value,
        };
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
