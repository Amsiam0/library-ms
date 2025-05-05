<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\PhysicalStock;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PhysicalStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Book::where('has_physical', true)->get()->each(function ($book) {
            PhysicalStock::factory()->create(['book_id' => $book->id]);
        });
    }
}
