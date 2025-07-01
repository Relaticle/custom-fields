<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Tests\Unit\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Relaticle\CustomFields\Integration\Forms\Components\FieldComponentInterface;
use Relaticle\CustomFields\Integration\Forms\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Tests\TestCase;

abstract class AbstractFormComponentTest extends TestCase
{
    protected FieldConfigurator $configurator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurator = $this->app->make(FieldConfigurator::class);
    }

    abstract protected function createComponent(): FieldComponentInterface;

    abstract protected function getExpectedFieldType(): string;

    protected function createCustomField(array $attributes = []): CustomField
    {
        return CustomField::factory()->make(array_merge([
            'code' => 'test_field',
            'label' => 'Test Field',
            'type' => 'text',
        ], $attributes));
    }

    public function test_implements_field_component_interface(): void
    {
        $component = $this->createComponent();

        $this->assertInstanceOf(FieldComponentInterface::class, $component);
    }

    public function test_make_returns_configured_field(): void
    {
        $component = $this->createComponent();
        $customField = $this->createCustomField();

        $field = $component->make($customField);

        $this->assertInstanceOf(Field::class, $field);
        // Note: FieldConfigurator sets the name, so we validate the full flow works
        $this->assertNotEmpty($field->getName());
    }

    public function test_make_accepts_dependent_fields(): void
    {
        $component = $this->createComponent();
        $customField = $this->createCustomField();
        $dependentFields = ['field1', 'field2'];

        $field = $component->make($customField, $dependentFields);

        $this->assertInstanceOf(Field::class, $field);
    }

    public function test_make_accepts_all_fields_collection(): void
    {
        $component = $this->createComponent();
        $customField = $this->createCustomField();
        $allFields = collect([
            CustomField::factory()->make(['code' => 'field1']),
            CustomField::factory()->make(['code' => 'field2']),
        ]);

        $field = $component->make($customField, [], $allFields);

        $this->assertInstanceOf(Field::class, $field);
    }

    public function test_create_returns_expected_field_type(): void
    {
        $component = $this->createComponent();
        $customField = $this->createCustomField();

        $field = $component->create($customField);

        $this->assertInstanceOf($this->getExpectedFieldType(), $field);
    }
}
