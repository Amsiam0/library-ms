<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhysicalStock extends Model
{
    /** @use HasFactory<\Database\Factories\PhysicalStockFactory> */
    use HasFactory;

    protected $fillable = [
        'book_id',
        'quantity',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
