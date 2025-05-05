<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeedbackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', 'user')->get();
        $books = Book::all();

        foreach ($users as $user) {
            Feedback::factory(3)->create([
                'user_id' => $user->id,
                'book_id' => $books->random()->id,
            ]);
        }
    }

}
