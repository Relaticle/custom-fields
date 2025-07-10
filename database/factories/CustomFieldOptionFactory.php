<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;

/**
 * @extends Factory<CustomFieldOption>
 */
class CustomFieldOptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<CustomFieldOption>
     */
    protected $model = CustomFieldOption::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'name' => $this->faker->name(),
            'sort_order' => $this->faker->numberBetween(0, 100),

            'custom_field_id' => CustomField::factory(),
        ];
    }
}
