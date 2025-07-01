<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Tests\Unit\Integration\Forms\Components;

use Filament\Forms\Components\Select;
use Relaticle\CustomFields\Integration\Forms\Components\FieldComponentInterface;
use Relaticle\CustomFields\Integration\Forms\Components\SelectComponent;
use Relaticle\CustomFields\Models\CustomFieldOption;

class SelectComponentTest extends AbstractFormComponentTest
{
    protected function createComponent(): FieldComponentInterface
    {
        return new SelectComponent($this->configurator);
    }

    protected function getExpectedFieldType(): string
    {
        return Select::class;
    }

    public function test_create_configures_select_correctly(): void
    {
        $component = new SelectComponent($this->configurator);
        $customField = $this->createCustomField(['type' => 'select']);

        $field = $component->create($customField);

        $this->assertInstanceOf(Select::class, $field);
        $this->assertEquals("custom_fields.{$customField->code}", $field->getName());
        $this->assertTrue($field->isSearchable());
    }

    public function test_create_with_field_options(): void
    {
        $component = new SelectComponent($this->configurator);
        $customField = $this->createCustomField(['type' => 'select']);

        // Mock some field options
        $options = collect([
            CustomFieldOption::factory()->make(['value' => 'option1', 'label' => 'Option 1']),
            CustomFieldOption::factory()->make(['value' => 'option2', 'label' => 'Option 2']),
        ]);

        $customField->setRelation('fieldOptions', $options);

        $field = $component->create($customField);

        $this->assertInstanceOf(Select::class, $field);
        $this->assertIsArray($field->getOptions());
    }

    public function test_create_with_lookup_type(): void
    {
        $component = new SelectComponent($this->configurator);
        $customField = $this->createCustomField([
            'type' => 'select',
            'lookup_type' => null,  // No lookup type
        ]);

        $field = $component->create($customField);

        $this->assertInstanceOf(Select::class, $field);
    }
}
