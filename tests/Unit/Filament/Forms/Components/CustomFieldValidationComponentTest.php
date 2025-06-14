<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Tests\Unit\Filament\Forms\Components;

use Filament\Schemas\Components\Component;
use Filament\Forms\Components\Repeater;
use Relaticle\CustomFields\Filament\Forms\Components\CustomFieldValidationComponent;
use Relaticle\CustomFields\Tests\TestCase;

class CustomFieldValidationComponentTest extends TestCase
{
    private CustomFieldValidationComponent $component;

    protected function setUp(): void
    {
        parent::setUp();
        $this->component = CustomFieldValidationComponent::make();
    }

    /** @test */
    public function it_is_a_filament_component()
    {
        expect($this->component)->toBeInstanceOf(Component::class);
    }

    /** @test */
    public function it_uses_group_view()
    {
        $reflection = new \ReflectionClass($this->component);
        $viewProperty = $reflection->getProperty('view');
        $viewProperty->setAccessible(true);
        
        expect($viewProperty->getValue($this->component))->toBe('filament-schemas::components.grid');
    }

    /** @test */
    public function it_has_validation_rules_repeater_in_schema()
    {
        $schema = $this->component->getChildComponents();
        
        expect($schema)->toHaveCount(1);
        expect($schema[0])->toBeInstanceOf(Repeater::class);
        expect($schema[0]->getName())->toBe('validation_rules');
    }

    /** @test */
    public function parameters_repeater_contains_text_input()
    {
        $schema = $this->component->getChildComponents();
        $repeater = $schema[0];
        $grid = $repeater->getChildComponents()[0];
        $parametersRepeater = $grid->getChildComponents()[2];
        $textInput = $parametersRepeater->getChildComponents()[0];
        
        expect($textInput->getName())->toBe('value');
        expect($textInput->isRequired())->toBeTrue();
    }

    /** @test */
    public function make_method_creates_instance_from_container()
    {
        $component = CustomFieldValidationComponent::make();
        
        expect($component)->toBeInstanceOf(CustomFieldValidationComponent::class);
        expect($component)->not->toBe($this->component); // Different instance
    }
}