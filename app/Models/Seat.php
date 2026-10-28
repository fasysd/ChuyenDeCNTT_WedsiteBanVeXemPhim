<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'seat_row',
        'seat_number',
        'type',
    ];

    public function getLabelAttribute(): string
    {
        return $this->seat_row . $this->seat_number;
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function ticketDetails()
    {
        return $this->hasMany(TicketDetail::class);
    }
}
