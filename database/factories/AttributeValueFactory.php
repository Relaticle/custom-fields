<?php

namespace ManukMinasyan\FilamentCustomField\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use ManukMinasyan\FilamentCustomField\Models\CustomField;
use ManukMinasyan\FilamentCustomField\Models\CustomFieldValue;

/**
 * @extends Factory<CustomFieldValue>
 */
class AttributeValueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<CustomFieldValue>
     */
    protected $model = CustomFieldValue::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entity_id' => $this->faker->randomNumber(),
            'entity_type' => $this->faker->word(),
            'text_value' => $this->faker->text(),
            'boolean_value' => $this->faker->boolean(),
            'integer_value' => $this->faker->randomNumber(),
            'float_value' => $this->faker->randomFloat(),
            'datetime_value' => Carbon::now(),
            'date_value' => Carbon::now(),
            'json_value' => $this->faker->words(),

            'custom_field_id' => CustomField::factory(),
        ];
    }
}
