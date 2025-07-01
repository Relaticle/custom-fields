<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Tests\Unit\Integration\Forms\Components;

use Filament\Forms\Components\TextInput;
use Relaticle\CustomFields\Integration\Forms\Components\FieldComponentInterface;
use Relaticle\CustomFields\Integration\Forms\Components\TextInputComponent;

class TextInputComponentTest extends AbstractFormComponentTest
{
    protected function createComponent(): FieldComponentInterface
    {
        return new TextInputComponent($this->configurator);
    }

    protected function getExpectedFieldType(): string
    {
        return TextInput::class;
    }

    public function test_create_configures_text_input_correctly(): void
    {
        $component = new TextInputComponent($this->configurator);
        $customField = $this->createCustomField(['type' => 'text']);

        $field = $component->create($customField);

        $this->assertInstanceOf(TextInput::class, $field);
        $this->assertEquals("custom_fields.{$customField->code}", $field->getName());
        // Only test what we can reliably access
        $this->assertNotNull($field);
    }

    public function test_works_with_different_field_codes(): void
    {
        $component = new TextInputComponent($this->configurator);
        $customField = $this->createCustomField([
            'code' => 'user_name',
            'type' => 'text',
        ]);

        $field = $component->create($customField);

        $this->assertEquals('custom_fields.user_name', $field->getName());
    }
}
