<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        Room::create([
            'name' => 'Phòng Chiếu 01 (2D Digital)',
            'total_seats' => 50,
        ]);

        Room::create([
            'name' => 'Phòng Chiếu 02 (VIP Cinema)',
            'total_seats' => 50,
        ]);

        Room::create([
            'name' => 'Phòng Chiếu 03 (IMAX Laser)',
            'total_seats' => 50,
        ]);
    }
}
