<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BookLoan>
 */
class BookLoanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'approved', 'rejected', 'returned']);
        return [
            'user_id'      => User::factory(),
            'book_id'      => Book::factory(),
            'status'       => $status,
            'requested_at' => now(),
            'approved_at'  => in_array($status, ['approved', 'returned']) ? now() : null,
            'due_date'     => in_array($status, ['approved', 'returned']) ? now()->addWeeks(2) : null,
            'returned_at'  => $status === 'returned' ? now()->addWeeks(2) : null,
        ];
    }
}
