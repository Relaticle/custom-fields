<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Models\CustomField;

/**
 * @extends Factory<CustomField>
 */
final class CustomFieldFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<CustomField>
     */
    protected $model = CustomField::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->word(),
            'name' => $this->faker->name(),
            'type' => $this->faker->randomElement(CustomFieldType::cases()),
            'entity_type' => 'App\\Models\\User',
            'sort_order' => $this->faker->numberBetween(0, 100),
            'validation' => [],
            'is_required' => $this->faker->boolean(),
            'settings' => [
                'visible_in_list' => true,
                'visible_in_view' => true,
                'searchable' => false,
                'encrypted' => false,
            ],
            'is_unique' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
