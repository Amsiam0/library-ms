<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookLoan extends Model
{
    /** @use HasFactory<\Database\Factories\BookLoanFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'status',
        'requested_at',
        'approved_at',
        'due_date',
        'returned_at',
    ];
}
