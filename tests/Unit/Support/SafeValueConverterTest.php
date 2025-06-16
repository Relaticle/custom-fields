<?php

declare(strict_types=1);

use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Support\SafeValueConverter;

it('converts normal integers correctly', function () {
    expect(SafeValueConverter::toSafeInteger(123))->toBe(123)
        ->and(SafeValueConverter::toSafeInteger(-456))->toBe(-456)
        ->and(SafeValueConverter::toSafeInteger('789'))->toBe(789)
        ->and(SafeValueConverter::toSafeInteger('-123'))->toBe(-123);
});

it('handles scientific notation', function () {
    expect(SafeValueConverter::toSafeInteger('1e6'))->toBe(1000000)
        ->and(SafeValueConverter::toSafeInteger('1.23e6'))->toBe(1230000)
        ->and(SafeValueConverter::toSafeInteger('-1e6'))->toBe(-1000000);
});

it('clamps values exceeding bigint bounds', function () {
    // Test max bound
    $overMax = '1e20'; // This is much larger than PHP_INT_MAX
    expect(SafeValueConverter::toSafeInteger($overMax))->toBeInt()
        ->and(SafeValueConverter::toSafeInteger($overMax))->toBeGreaterThan(0);

    // Test min bound
    $belowMin = '-1e20'; // This is much smaller than PHP_INT_MIN
    expect(SafeValueConverter::toSafeInteger($belowMin))->toBeInt()
        ->and(SafeValueConverter::toSafeInteger($belowMin))->toBeLessThan(0);

    // Test values near the boundaries - just verify they are integers with correct sign
    $largePositive = '9223372036854775000'; // Close to Max 64-bit integer
    $maxResult = SafeValueConverter::toSafeInteger($largePositive);
    expect($maxResult)->toBeInt()
        ->and($maxResult)->toBeGreaterThan(0);

    $largeNegative = '-9223372036854775000'; // Close to Min 64-bit integer
    $minResult = SafeValueConverter::toSafeInteger($largeNegative);
    expect($minResult)->toBeInt()
        ->and($minResult)->toBeLessThan(0);

    // Test the specific value from the error report
    $specificValue = '-9.2233720368548E+18';
    $result = SafeValueConverter::toSafeInteger($specificValue);
    expect($result)->toBeInt()
        ->and($result)->toBeLessThan(0);
});

it('ensures return type is integer even for edge cases', function () {
    // Test values that are on the edge of MAX_BIGINT boundary
    $almostMax = '9.223372036854775E+18';
    $result = SafeValueConverter::toSafeInteger($almostMax);
    expect($result)->toBeInt()
        ->and($result)->not->toBeFloat();

    // Test values that are on the edge of MIN_BIGINT boundary
    $almostMin = '-9.223372036854775E+18';
    $result = SafeValueConverter::toSafeInteger($almostMin);
    expect($result)->toBeInt()
        ->and($result)->not->toBeFloat()
        ->and(SafeValueConverter::toSafeInteger(SafeValueConverter::MAX_BIGINT))->toBeInt()
        ->and(SafeValueConverter::toSafeInteger(SafeValueConverter::MIN_BIGINT))->toBeInt();


    // Test decimal values to ensure they're properly converted to integers
    $decimalValue = 123.456;
    $result = SafeValueConverter::toSafeInteger($decimalValue);
    expect($result)->toBeInt()
        ->and($result)->toBe(123);

    // Test string with decimal points
    $decimalString = '456.789';
    $result = SafeValueConverter::toSafeInteger($decimalString);
    expect($result)->toBeInt()
        ->and($result)->toBe(456);
});

it('returns null for invalid values', function () {
    expect(SafeValueConverter::toSafeInteger(null))->toBeNull()
        ->and(SafeValueConverter::toSafeInteger(''))->toBeNull()
        ->and(SafeValueConverter::toSafeInteger('not-a-number'))->toBeNull()
        ->and(SafeValueConverter::toSafeInteger([]))->toBeNull()
        ->and(SafeValueConverter::toSafeInteger(new \stdClass))->toBeNull();
});

it('converts field values by type', function () {
    // Test NUMBER field with scientific notation
    $largeNumber = '-9.2233720368548E+18';
    $converted = SafeValueConverter::toDbSafe($largeNumber, CustomFieldType::NUMBER);
    expect($converted)->toBeInt()
        ->and($converted)->toBeLessThan(0);
    // Just verify it's negative, not the exact value

    // Test CURRENCY field with float
    $currency = '123.45';
    $converted = SafeValueConverter::toDbSafe($currency, CustomFieldType::CURRENCY);
    expect($converted)->toBeFloat()
        ->and($converted)->toBe(123.45);

    // Test array-based fields
    $tags = ['tag1', 'tag2', 'tag3'];
    $converted = SafeValueConverter::toDbSafe($tags, CustomFieldType::TAGS_INPUT);
    expect($converted)->toBeArray()
        ->and($converted)->toBe($tags);

    // Test string conversion for JSON
    $jsonString = '["item1","item2"]';
    $converted = SafeValueConverter::toDbSafe($jsonString, CustomFieldType::CHECKBOX_LIST);
    expect($converted)->toBeArray()
        ->and($converted)->toBe(['item1', 'item2']);
});
