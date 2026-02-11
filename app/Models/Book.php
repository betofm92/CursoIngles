<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Book extends Model
{
    protected $fillable = [
        'code',
        'name',
    ];

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'book_student', 'book_id', 'student_id')
            ->withTimestamps();
    }
}
