<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Enums\Logic;
use Relaticle\CustomFields\Enums\Mode;
use Relaticle\CustomFields\Enums\Operator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\VisibilityService;
use Relaticle\CustomFields\Tests\TestCase;

class SimpleVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private VisibilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VisibilityService;
    }

    #[Test]
    public function it_evaluates_simple_visibility_conditions_correctly(): void
    {
        // Create a field with simple visibility
        $field = CustomField::factory()->create([
            'code' => 'conditional_field',
            'type' => CustomFieldType::TEXT,
            'entity_type' => 'App\Models\Company',
            'settings' => [
                'visibility' => [
                    'mode' => Mode::SHOW_WHEN->value,
                    'logic' => Logic::ALL->value,
                    'conditions' => [
                        [
                            'field' => 'status',
                            'operator' => Operator::EQUALS->value,
                            'value' => 'active',
                        ],
                    ],
                    'always_save' => false,
                ],
            ],
        ]);

        // Test when condition is met
        $this->assertTrue($this->service->shouldShowField($field, ['status' => 'active']));

        // Test when condition is not met
        $this->assertFalse($this->service->shouldShowField($field, ['status' => 'inactive']));

        // Test case insensitive matching
        $this->assertTrue($this->service->shouldShowField($field, ['status' => 'ACTIVE']));
    }

    #[Test]
    public function it_handles_hide_when_mode_correctly(): void
    {
        $field = CustomField::factory()->create([
            'code' => 'conditional_field',
            'type' => CustomFieldType::TEXT,
            'entity_type' => 'App\Models\Company',
            'settings' => [
                'visibility' => [
                    'mode' => Mode::HIDE_WHEN->value,
                    'logic' => Logic::ALL->value,
                    'conditions' => [
                        [
                            'field' => 'status',
                            'operator' => Operator::EQUALS->value,
                            'value' => 'inactive',
                        ],
                    ],
                ],
            ],
        ]);

        // Should hide when condition is met
        $this->assertFalse($this->service->shouldShowField($field, ['status' => 'inactive']));

        // Should show when condition is not met
        $this->assertTrue($this->service->shouldShowField($field, ['status' => 'active']));
    }

    #[Test]
    public function it_handles_multiple_conditions_with_all_logic(): void
    {
        $field = CustomField::factory()->create([
            'code' => 'conditional_field',
            'type' => CustomFieldType::TEXT,
            'entity_type' => 'App\Models\Company',
            'settings' => [
                'visibility' => [
                    'mode' => Mode::SHOW_WHEN->value,
                    'logic' => Logic::ALL->value,
                    'conditions' => [
                        [
                            'field' => 'status',
                            'operator' => Operator::EQUALS->value,
                            'value' => 'active',
                        ],
                        [
                            'field' => 'type',
                            'operator' => Operator::EQUALS->value,
                            'value' => 'premium',
                        ],
                    ],
                ],
            ],
        ]);

        // Both conditions must be met
        $this->assertTrue($this->service->shouldShowField($field, [
            'status' => 'active',
            'type' => 'premium',
        ]));

        // If one condition fails, should not show
        $this->assertFalse($this->service->shouldShowField($field, [
            'status' => 'active',
            'type' => 'basic',
        ]));

        $this->assertFalse($this->service->shouldShowField($field, [
            'status' => 'inactive',
            'type' => 'premium',
        ]));
    }

    #[Test]
    public function it_handles_multiple_conditions_with_any_logic(): void
    {
        $field = CustomField::factory()->create([
            'code' => 'conditional_field',
            'type' => CustomFieldType::TEXT,
            'entity_type' => 'App\Models\Company',
            'settings' => [
                'visibility' => [
                    'mode' => Mode::SHOW_WHEN->value,
                    'logic' => Logic::ANY->value,
                    'conditions' => [
                        [
                            'field' => 'status',
                            'operator' => Operator::EQUALS->value,
                            'value' => 'active',
                        ],
                        [
                            'field' => 'type',
                            'operator' => Operator::EQUALS->value,
                            'value' => 'premium',
                        ],
                    ],
                ],
            ],
        ]);

        // Any condition met should show
        $this->assertTrue($this->service->shouldShowField($field, [
            'status' => 'active',
            'type' => 'basic',
        ]));

        $this->assertTrue($this->service->shouldShowField($field, [
            'status' => 'inactive',
            'type' => 'premium',
        ]));

        // Both conditions met should show
        $this->assertTrue($this->service->shouldShowField($field, [
            'status' => 'active',
            'type' => 'premium',
        ]));

        // No conditions met should not show
        $this->assertFalse($this->service->shouldShowField($field, [
            'status' => 'inactive',
            'type' => 'basic',
        ]));
    }

    #[Test]
    public function it_handles_numeric_comparisons(): void
    {
        $field = CustomField::factory()->create([
            'code' => 'conditional_field',
            'type' => CustomFieldType::TEXT,
            'entity_type' => 'App\Models\Company',
            'settings' => [
                'visibility' => [
                    'mode' => Mode::SHOW_WHEN->value,
                    'conditions' => [
                        [
                            'field' => 'quantity',
                            'operator' => Operator::GREATER_THAN->value,
                            'value' => '10',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertTrue($this->service->shouldShowField($field, ['quantity' => 15]));
        $this->assertTrue($this->service->shouldShowField($field, ['quantity' => '20']));
        $this->assertFalse($this->service->shouldShowField($field, ['quantity' => 5]));
        $this->assertFalse($this->service->shouldShowField($field, ['quantity' => '8']));
    }

    #[Test]
    public function it_handles_contains_operations(): void
    {
        $field = CustomField::factory()->create([
            'code' => 'conditional_field',
            'type' => CustomFieldType::TEXT,
            'entity_type' => 'App\Models\Company',
            'settings' => [
                'visibility' => [
                    'mode' => Mode::SHOW_WHEN->value,
                    'conditions' => [
                        [
                            'field' => 'features',
                            'operator' => Operator::CONTAINS->value,
                            'value' => 'api',
                        ],
                    ],
                ],
            ],
        ]);

        // Array contains
        $this->assertTrue($this->service->shouldShowField($field, ['features' => ['API Access', 'Premium Support']]));
        $this->assertTrue($this->service->shouldShowField($field, ['features' => ['api access']]));
        $this->assertFalse($this->service->shouldShowField($field, ['features' => ['Premium Support']]));

        // String contains
        $this->assertTrue($this->service->shouldShowField($field, ['features' => 'API Access Available']));
        $this->assertFalse($this->service->shouldShowField($field, ['features' => 'Premium Support Only']));
    }

    #[Test]
    public function it_handles_empty_operations(): void
    {
        $field = CustomField::factory()->create([
            'code' => 'conditional_field',
            'type' => CustomFieldType::TEXT,
            'entity_type' => 'App\Models\Company',
            'settings' => [
                'visibility' => [
                    'mode' => Mode::SHOW_WHEN->value,
                    'conditions' => [
                        [
                            'field' => 'description',
                            'operator' => Operator::IS_EMPTY->value,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertTrue($this->service->shouldShowField($field, ['description' => null]));
        $this->assertTrue($this->service->shouldShowField($field, ['description' => '']));
        $this->assertTrue($this->service->shouldShowField($field, ['description' => '   ']));
        $this->assertFalse($this->service->shouldShowField($field, ['description' => 'Some text']));
    }

    #[Test]
    public function it_calculates_dependencies_correctly(): void
    {
        $field1 = CustomField::factory()->create([
            'code' => 'field1',
            'settings' => [
                'visibility' => [
                    'mode' => Mode::SHOW_WHEN->value,
                    'conditions' => [
                        ['field' => 'status', 'operator' => Operator::EQUALS->value, 'value' => 'active'],
                        ['field' => 'type', 'operator' => Operator::EQUALS->value, 'value' => 'premium'],
                    ],
                ],
            ],
        ]);

        $field2 = CustomField::factory()->create([
            'code' => 'field2',
            'settings' => [
                'visibility' => [
                    'mode' => Mode::SHOW_WHEN->value,
                    'conditions' => [
                        ['field' => 'status', 'operator' => Operator::EQUALS->value, 'value' => 'active'],
                    ],
                ],
            ],
        ]);

        $dependencies = $this->service->calculateDependencies(collect([$field1, $field2]));

        $expected = [
            'status' => ['field1', 'field2'],
            'type' => ['field1'],
        ];

        $this->assertEquals($expected, $dependencies);
    }

    #[Test]
    public function it_handles_always_visible_fields(): void
    {
        $field = CustomField::factory()->create([
            'code' => 'always_visible_field',
            'type' => CustomFieldType::TEXT,
            'entity_type' => 'App\Models\Company',
            'settings' => [
                'visibility' => [
                    'mode' => Mode::ALWAYS_VISIBLE->value,
                ],
            ],
        ]);

        $this->assertTrue($this->service->shouldShowField($field, []));
        $this->assertTrue($this->service->shouldShowField($field, ['any' => 'value']));
    }

    #[Test]
    public function it_handles_corrupted_data_gracefully(): void
    {
        $field = CustomField::factory()->create([
            'code' => 'corrupted_field',
            'settings' => [
                'visibility' => [
                    'mode' => Mode::SHOW_WHEN->value,
                    'conditions' => [
                        'invalid_condition',
                        ['field' => null, 'operator' => 'invalid'],
                        ['field' => 'status', 'operator' => Operator::EQUALS->value, 'value' => 'active'],
                    ],
                ],
            ],
        ]);

        // Should still work with the valid condition
        $this->assertTrue($this->service->shouldShowField($field, ['status' => 'active']));
        $this->assertFalse($this->service->shouldShowField($field, ['status' => 'inactive']));
    }
}
