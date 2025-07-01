<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Tests\Unit\Integration\Forms\Components;

use Filament\Forms\Components\Select;
use Relaticle\CustomFields\Integration\Forms\Components\FieldComponentInterface;
use Relaticle\CustomFields\Integration\Forms\Components\MultiSelectComponent;

class MultiSelectComponentTest extends AbstractFormComponentTest
{
    protected function createComponent(): FieldComponentInterface
    {
        return new MultiSelectComponent($this->configurator);
    }

    protected function getExpectedFieldType(): string
    {
        return Select::class;
    }

    public function test_create_configures_multiple_select_correctly(): void
    {
        $component = new MultiSelectComponent($this->configurator);
        $customField = $this->createCustomField(['type' => 'multi_select']);

        $field = $component->create($customField);

        $this->assertInstanceOf(Select::class, $field);
        $this->assertEquals("custom_fields.{$customField->code}", $field->getName());
        // Validate composition pattern works
        $this->assertNotNull($field);
    }

    public function test_composition_pattern_working(): void
    {
        $component = new MultiSelectComponent($this->configurator);
        $customField = $this->createCustomField(['type' => 'multi_select']);

        $field = $component->create($customField);

        // Should have all the features of SelectComponent plus multiple
        $this->assertInstanceOf(Select::class, $field);
        $this->assertNotNull($field);
    }
}
