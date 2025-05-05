<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\BookLoan;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookLoanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', 'user')->get();
        $books = Book::where('has_physical', true)->get();

        foreach ($users as $user) {
            BookLoan::factory(2)->create([
                'user_id' => $user->id,
                'book_id' => $books->random()->id,
            ]);
        }
    }
}
