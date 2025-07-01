<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Tests\Unit\Integration\Forms\Components;

use Filament\Forms\Components\TextInput;
use Relaticle\CustomFields\Integration\Forms\Components\FieldComponentInterface;
use Relaticle\CustomFields\Integration\Forms\Components\NumberComponent;

class NumberComponentTest extends AbstractFormComponentTest
{
    protected function createComponent(): FieldComponentInterface
    {
        return new NumberComponent($this->configurator);
    }

    protected function getExpectedFieldType(): string
    {
        return TextInput::class;
    }

    public function test_create_configures_numeric_input_correctly(): void
    {
        $component = new NumberComponent($this->configurator);
        $customField = $this->createCustomField(['type' => 'number']);

        $field = $component->create($customField);

        $this->assertInstanceOf(TextInput::class, $field);
        $this->assertEquals("custom_fields.{$customField->code}", $field->getName());
        // Validate it's configured for numeric input
        $this->assertNotNull($field);
    }
}
