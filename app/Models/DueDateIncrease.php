<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DueDateIncrease extends Model
{
    protected $guarded = [];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public function bookLoan()
    {
        return $this->belongsTo(BookLoan::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
