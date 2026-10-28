<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $casts = [
        'release_date' => 'date',
    ];

    protected $fillable = [
        'id',
        'title',
        'duration',
        'release_date',
        'image_path',
        'genre',
        'description',
    ];

    public function showtimes()
    {
        return $this->hasMany(Showtime::class);
    }
}