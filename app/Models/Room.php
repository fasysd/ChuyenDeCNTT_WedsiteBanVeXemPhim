<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'total_seats',
    ];

    public function seats()
    {
        return $this->hasMany(Seat::class);
    }

    public function getVipSeatCount()
    {
        return $this->seats()
            ->where('type', 'VIP')
            ->count();
    }

    public function showtimes()
    {
        return $this->hasMany(Showtime::class);
    }

}
