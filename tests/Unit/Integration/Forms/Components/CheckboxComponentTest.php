<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Tests\Unit\Integration\Forms\Components;

use Filament\Forms\Components\Checkbox;
use Relaticle\CustomFields\Integration\Forms\Components\CheckboxComponent;
use Relaticle\CustomFields\Integration\Forms\Components\FieldComponentInterface;

class CheckboxComponentTest extends AbstractFormComponentTest
{
    protected function createComponent(): FieldComponentInterface
    {
        return new CheckboxComponent($this->configurator);
    }

    protected function getExpectedFieldType(): string
    {
        return Checkbox::class;
    }

    public function test_create_configures_checkbox_correctly(): void
    {
        $component = new CheckboxComponent($this->configurator);
        $customField = $this->createCustomField(['type' => 'checkbox']);

        $field = $component->create($customField);

        $this->assertInstanceOf(Checkbox::class, $field);
        $this->assertEquals("custom_fields.{$customField->code}", $field->getName());
        // Validate checkbox is created properly
        $this->assertNotNull($field);
    }
}
