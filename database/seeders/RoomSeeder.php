<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            [
                'name' => 'Phòng 01 - Standard Cinema',
                'total_seats' => 60, // 6 hàng x 10 ghế
            ],
            [
                'name' => 'Phòng 02 - Deluxe Cinema',
                'total_seats' => 84, // 7 hàng x 12 ghế
            ],
            [
                'name' => 'Phòng 03 - IMAX Laser',
                'total_seats' => 112, // 8 hàng x 14 ghế
            ],
            [
                'name' => 'Phòng 04 - VIP Gold Lounge',
                'total_seats' => 32, // 4 hàng x 8 ghế (ghế sofa cao cấp)
            ],
            [
                'name' => 'Phòng 05 - Dolby Atmos Cinema',
                'total_seats' => 66, // 6 hàng x 11 ghế
            ],
            [
                'name' => 'Phòng 06 - Studio Cinema',
                'total_seats' => 40, // 5 hàng x 8 ghế (rạp mini)
            ],
            [
                'name' => 'Phòng 07 - Premiere Grand Stage',
                'total_seats' => 108, // 9 hàng x 12 ghế
            ],
            [
                'name' => 'Phòng 08 - Motion 4DX Cinema',
                'total_seats' => 50, // 5 hàng x 10 ghế (ghế chuyển động)
            ],
        ];

        foreach ($rooms as $room) {
            Room::create($room);
        }
    }
}
