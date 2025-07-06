<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Tests\Unit\Factories;

use PHPUnit\Framework\TestCase;
use Relaticle\CustomFields\Filament\Integration\Factories\UnsupportedFieldTypeException;

class UnsupportedFieldTypeExceptionTest extends TestCase
{
    public function test_missing_registration_creates_correct_message(): void
    {
        $exception = UnsupportedFieldTypeException::missingRegistration('custom_type', 'form');

        $expectedMessage = "No form component registered for field type 'custom_type'. " .
            "Please register a component for this field type or check if the field type is supported.";

        $this->assertInstanceOf(UnsupportedFieldTypeException::class, $exception);
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }

    public function test_invalid_component_class_creates_correct_message(): void
    {
        $exception = UnsupportedFieldTypeException::invalidComponentClass(
            'text',
            'App\Components\CustomTextComponent',
            'Relaticle\CustomFields\Contracts\Components\FormComponentInterface'
        );

        $expectedMessage = "The component class 'App\Components\CustomTextComponent' registered for field type 'text' " .
            "must implement Relaticle\CustomFields\Contracts\Components\FormComponentInterface.";

        $this->assertInstanceOf(UnsupportedFieldTypeException::class, $exception);
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }

    public function test_class_not_found_creates_correct_message(): void
    {
        $exception = UnsupportedFieldTypeException::classNotFound(
            'custom_field',
            'App\Components\NonExistentComponent'
        );

        $expectedMessage = "The component class 'App\Components\NonExistentComponent' registered for field type 'custom_field' does not exist. " .
            "Please check the class name and namespace.";

        $this->assertInstanceOf(UnsupportedFieldTypeException::class, $exception);
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }
}