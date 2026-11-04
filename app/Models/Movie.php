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

    /**
     * Lấy đường dẫn banner ngang (backdrop) nếu có, hoặc dùng poster/placeholder
     */
    public function getBannerUrlAttribute(): string
    {
        $bannerMap = [
            1 => 'images/banners/banner_dune2.jpg',
            2 => 'images/banners/banner_avengers.jpg',
            3 => 'images/banners/banner_insideout2.jpg',
            5 => 'images/banners/banner_godzilla.jpg',
            6 => 'images/banners/banner_deadpool.jpg',
            7 => 'images/banners/banner_oppenheimer.jpg',
            8 => 'images/banners/banner_avatar2.jpg',
        ];

        if (isset($bannerMap[$this->id]) && file_exists(public_path($bannerMap[$this->id]))) {
            return asset($bannerMap[$this->id]);
        }

        if ($this->image_path && file_exists(public_path($this->image_path))) {
            return asset($this->image_path);
        }

        return asset('images/movieavatar.webp');
    }
}