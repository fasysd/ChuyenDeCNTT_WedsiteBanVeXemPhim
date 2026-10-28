<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        // Room::truncate();

        Room::insert([
            [
                'name' => 'Room 1',
                'total_seats' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Room 2',
                'total_seats' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}