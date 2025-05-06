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
    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'due_date' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dueDateIncreases()
    {
        return $this->hasMany(DueDateIncrease::class);
    }
}
