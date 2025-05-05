<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PhysicalStock>
 */
class PhysicalStockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'book_id'  => Book::factory(),
            'quantity' => fake()->numberBetween(0, 20),
            'updated_at' => now(),
        ];
    }
}
