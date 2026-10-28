<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Seat;
use Illuminate\Database\Seeder;

class SeatSeeder extends Seeder
{
    public function run(): void
    {
        // Seat::truncate();

        $rooms = Room::all();

        foreach ($rooms as $room) {

            foreach (['A', 'B', 'C', 'D', 'E'] as $row) {

                for ($number = 1; $number <= 10; $number++) {

                    Seat::create([
                        'room_id' => $room->id,
                        'seat_row' => $row,
                        'seat_number' => $number,
                        'type' => $row === 'E'
                            ? 'VIP'
                            : 'STANDARD',
                    ]);
                }
            }
        }
    }
}