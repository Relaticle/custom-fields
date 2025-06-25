<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Relaticle\CustomFields\Tests\Fixtures\Models\Post;
use Relaticle\CustomFields\Tests\Fixtures\Models\User;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'author_id' => User::factory(),
            'content' => $this->faker->paragraph(),
            'is_published' => $this->faker->boolean(),
            'tags' => $this->faker->words(),
            'title' => $this->faker->sentence(),
            'rating' => $this->faker->numberBetween(1, 10),
        ];
    }
}