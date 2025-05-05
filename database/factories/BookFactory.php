<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title'        => fake()->sentence(3),
            'author'       => fake()->name(),
            'description'  => fake()->paragraph(),
            'category_id'  => Category::factory(),
            'has_pdf'      => fake()->boolean(),
            'has_physical' => fake()->boolean(),
        ];
    }
}
