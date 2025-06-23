<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Enums\FieldCategory;
use Relaticle\CustomFields\Enums\Operator;

class EnhancedVisibilitySystemTest extends TestCase
{
    #[Test]
    public function field_category_correctly_identifies_optionable_fields(): void
    {
        $this->assertTrue(FieldCategory::SINGLE_OPTION->isOptionable());
        $this->assertTrue(FieldCategory::MULTI_OPTION->isOptionable());
        $this->assertFalse(FieldCategory::TEXT->isOptionable());
        $this->assertFalse(FieldCategory::NUMERIC->isOptionable());
        $this->assertFalse(FieldCategory::DATE->isOptionable());
        $this->assertFalse(FieldCategory::BOOLEAN->isOptionable());
    }

    #[Test]
    public function field_category_correctly_identifies_multi_value_fields(): void
    {
        $this->assertTrue(FieldCategory::MULTI_OPTION->hasMultipleValues());
        $this->assertFalse(FieldCategory::SINGLE_OPTION->hasMultipleValues());
        $this->assertFalse(FieldCategory::TEXT->hasMultipleValues());
        $this->assertFalse(FieldCategory::NUMERIC->hasMultipleValues());
        $this->assertFalse(FieldCategory::DATE->hasMultipleValues());
        $this->assertFalse(FieldCategory::BOOLEAN->hasMultipleValues());
    }

    #[Test]
    public function custom_field_types_map_to_correct_categories(): void
    {
        // Text-based fields
        $this->assertEquals(FieldCategory::TEXT, CustomFieldType::TEXT->getCategory());
        $this->assertEquals(FieldCategory::TEXT, CustomFieldType::TEXTAREA->getCategory());
        $this->assertEquals(FieldCategory::TEXT, CustomFieldType::LINK->getCategory());
        $this->assertEquals(FieldCategory::TEXT, CustomFieldType::RICH_EDITOR->getCategory());
        $this->assertEquals(FieldCategory::TEXT, CustomFieldType::MARKDOWN_EDITOR->getCategory());
        $this->assertEquals(FieldCategory::TEXT, CustomFieldType::COLOR_PICKER->getCategory());

        // Numeric fields
        $this->assertEquals(FieldCategory::NUMERIC, CustomFieldType::NUMBER->getCategory());
        $this->assertEquals(FieldCategory::NUMERIC, CustomFieldType::CURRENCY->getCategory());

        // Date fields
        $this->assertEquals(FieldCategory::DATE, CustomFieldType::DATE->getCategory());
        $this->assertEquals(FieldCategory::DATE, CustomFieldType::DATE_TIME->getCategory());

        // Boolean fields
        $this->assertEquals(FieldCategory::BOOLEAN, CustomFieldType::TOGGLE->getCategory());
        $this->assertEquals(FieldCategory::BOOLEAN, CustomFieldType::CHECKBOX->getCategory());

        // Single option fields
        $this->assertEquals(FieldCategory::SINGLE_OPTION, CustomFieldType::SELECT->getCategory());
        $this->assertEquals(FieldCategory::SINGLE_OPTION, CustomFieldType::RADIO->getCategory());

        // Multi option fields
        $this->assertEquals(FieldCategory::MULTI_OPTION, CustomFieldType::MULTI_SELECT->getCategory());
        $this->assertEquals(FieldCategory::MULTI_OPTION, CustomFieldType::CHECKBOX_LIST->getCategory());
        $this->assertEquals(FieldCategory::MULTI_OPTION, CustomFieldType::TAGS_INPUT->getCategory());
        $this->assertEquals(FieldCategory::MULTI_OPTION, CustomFieldType::TOGGLE_BUTTONS->getCategory());
    }

    #[Test]
    public function custom_field_types_correctly_identify_optionable_status(): void
    {
        // Optionable fields
        $optionableTypes = [
            CustomFieldType::SELECT,
            CustomFieldType::MULTI_SELECT,
            CustomFieldType::CHECKBOX_LIST,
            CustomFieldType::TAGS_INPUT,
            CustomFieldType::TOGGLE_BUTTONS,
            CustomFieldType::RADIO,
        ];

        foreach ($optionableTypes as $type) {
            $this->assertTrue($type->isOptionable(), "Field type {$type->value} should be optionable");
        }

        // Non-optionable fields
        $nonOptionableTypes = [
            CustomFieldType::TEXT,
            CustomFieldType::TEXTAREA,
            CustomFieldType::NUMBER,
            CustomFieldType::CURRENCY,
            CustomFieldType::DATE,
            CustomFieldType::DATE_TIME,
            CustomFieldType::TOGGLE,
            CustomFieldType::CHECKBOX,
            CustomFieldType::LINK,
            CustomFieldType::RICH_EDITOR,
            CustomFieldType::MARKDOWN_EDITOR,
            CustomFieldType::COLOR_PICKER,
        ];

        foreach ($nonOptionableTypes as $type) {
            $this->assertFalse($type->isOptionable(), "Field type {$type->value} should not be optionable");
        }
    }

    #[Test]
    public function custom_field_types_correctly_identify_multi_value_status(): void
    {
        // Multi-value fields
        $multiValueTypes = [
            CustomFieldType::MULTI_SELECT,
            CustomFieldType::CHECKBOX_LIST,
            CustomFieldType::TAGS_INPUT,
            CustomFieldType::TOGGLE_BUTTONS,
        ];

        foreach ($multiValueTypes as $type) {
            $this->assertTrue($type->hasMultipleValues(), "Field type {$type->value} should support multiple values");
        }

        // Single-value fields
        $singleValueTypes = [
            CustomFieldType::TEXT,
            CustomFieldType::TEXTAREA,
            CustomFieldType::NUMBER,
            CustomFieldType::CURRENCY,
            CustomFieldType::DATE,
            CustomFieldType::DATE_TIME,
            CustomFieldType::TOGGLE,
            CustomFieldType::CHECKBOX,
            CustomFieldType::SELECT,
            CustomFieldType::RADIO,
            CustomFieldType::LINK,
            CustomFieldType::RICH_EDITOR,
            CustomFieldType::MARKDOWN_EDITOR,
            CustomFieldType::COLOR_PICKER,
        ];

        foreach ($singleValueTypes as $type) {
            $this->assertFalse($type->hasMultipleValues(), "Field type {$type->value} should not support multiple values");
        }
    }

    #[Test]
    public function field_categories_return_appropriate_compatible_operators(): void
    {
        // Text fields should support text-based operators
        $textOperators = FieldCategory::TEXT->getCompatibleOperators();
        $this->assertContains(Operator::EQUALS, $textOperators);
        $this->assertContains(Operator::NOT_EQUALS, $textOperators);
        $this->assertContains(Operator::CONTAINS, $textOperators);
        $this->assertContains(Operator::NOT_CONTAINS, $textOperators);
        $this->assertContains(Operator::IS_EMPTY, $textOperators);
        $this->assertContains(Operator::IS_NOT_EMPTY, $textOperators);

        // Numeric fields should support comparison operators
        $numericOperators = FieldCategory::NUMERIC->getCompatibleOperators();
        $this->assertContains(Operator::EQUALS, $numericOperators);
        $this->assertContains(Operator::NOT_EQUALS, $numericOperators);
        $this->assertContains(Operator::GREATER_THAN, $numericOperators);
        $this->assertContains(Operator::LESS_THAN, $numericOperators);
        $this->assertContains(Operator::IS_EMPTY, $numericOperators);
        $this->assertContains(Operator::IS_NOT_EMPTY, $numericOperators);

        // Date fields should support comparison operators (same as numeric)
        $dateOperators = FieldCategory::DATE->getCompatibleOperators();
        $this->assertEquals($numericOperators, $dateOperators);

        // Boolean fields should support limited operators
        $booleanOperators = FieldCategory::BOOLEAN->getCompatibleOperators();
        $this->assertContains(Operator::EQUALS, $booleanOperators);
        $this->assertContains(Operator::IS_EMPTY, $booleanOperators);
        $this->assertContains(Operator::IS_NOT_EMPTY, $booleanOperators);
        $this->assertCount(3, $booleanOperators);

        // Single option fields should support equality and emptiness checks
        $singleOptionOperators = FieldCategory::SINGLE_OPTION->getCompatibleOperators();
        $this->assertContains(Operator::EQUALS, $singleOptionOperators);
        $this->assertContains(Operator::NOT_EQUALS, $singleOptionOperators);
        $this->assertContains(Operator::IS_EMPTY, $singleOptionOperators);
        $this->assertContains(Operator::IS_NOT_EMPTY, $singleOptionOperators);
        $this->assertCount(4, $singleOptionOperators);

        // Multi option fields should support containment checks
        $multiOptionOperators = FieldCategory::MULTI_OPTION->getCompatibleOperators();
        $this->assertContains(Operator::CONTAINS, $multiOptionOperators);
        $this->assertContains(Operator::NOT_CONTAINS, $multiOptionOperators);
        $this->assertContains(Operator::IS_EMPTY, $multiOptionOperators);
        $this->assertContains(Operator::IS_NOT_EMPTY, $multiOptionOperators);
        $this->assertCount(4, $multiOptionOperators);
    }

    #[Test]
    public function custom_field_types_get_correct_compatible_operators(): void
    {
        // Test that field types delegate to their categories correctly
        $selectOperators = CustomFieldType::SELECT->getCompatibleOperators();
        $expectedSingleOptionOperators = FieldCategory::SINGLE_OPTION->getCompatibleOperators();
        $this->assertEquals($expectedSingleOptionOperators, $selectOperators);

        $multiSelectOperators = CustomFieldType::MULTI_SELECT->getCompatibleOperators();
        $expectedMultiOptionOperators = FieldCategory::MULTI_OPTION->getCompatibleOperators();
        $this->assertEquals($expectedMultiOptionOperators, $multiSelectOperators);

        $textOperators = CustomFieldType::TEXT->getCompatibleOperators();
        $expectedTextOperators = FieldCategory::TEXT->getCompatibleOperators();
        $this->assertEquals($expectedTextOperators, $textOperators);

        $numberOperators = CustomFieldType::NUMBER->getCompatibleOperators();
        $expectedNumericOperators = FieldCategory::NUMERIC->getCompatibleOperators();
        $this->assertEquals($expectedNumericOperators, $numberOperators);
    }

    #[Test]
    public function field_categories_return_formatted_operator_options(): void
    {
        $textOptions = FieldCategory::TEXT->getCompatibleOperatorOptions();
        $this->assertIsArray($textOptions);
        $this->assertArrayHasKey('equals', $textOptions);
        $this->assertEquals('Equals', $textOptions['equals']);
        $this->assertArrayHasKey('contains', $textOptions);
        $this->assertEquals('Contains', $textOptions['contains']);

        $booleanOptions = FieldCategory::BOOLEAN->getCompatibleOperatorOptions();
        $this->assertIsArray($booleanOptions);
        $this->assertArrayHasKey('equals', $booleanOptions);
        $this->assertEquals('Equals', $booleanOptions['equals']);
        $this->assertArrayNotHasKey('contains', $booleanOptions);
        $this->assertArrayNotHasKey('greater_than', $booleanOptions);
    }

    #[Test]
    public function operator_enum_delegates_to_field_category_correctly(): void
    {
        // Test that the forFieldType method works correctly
        $selectOptions = Operator::forFieldType(CustomFieldType::SELECT);
        $expectedOptions = FieldCategory::SINGLE_OPTION->getCompatibleOperatorOptions();
        $this->assertEquals($expectedOptions, $selectOptions);

        $multiSelectOptions = Operator::forFieldType(CustomFieldType::MULTI_SELECT);
        $expectedMultiOptions = FieldCategory::MULTI_OPTION->getCompatibleOperatorOptions();
        $this->assertEquals($expectedMultiOptions, $multiSelectOptions);

        $textOptions = Operator::forFieldType(CustomFieldType::TEXT);
        $expectedTextOptions = FieldCategory::TEXT->getCompatibleOperatorOptions();
        $this->assertEquals($expectedTextOptions, $textOptions);
    }
}