<?php

namespace Database\Seeders;

use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use Illuminate\Database\Seeder;

class ShowtimeSeeder extends Seeder
{
    public function run(): void
    {
        $movies = Movie::all();
        $rooms = Room::all();

        if ($movies->count() < 5 || $rooms->count() < 3) {
            return;
        }

        // Suất chiếu hôm nay
        Showtime::create([
            'movie_id' => $movies[0]->id, // Dune 2
            'room_id' => $rooms[0]->id,   // Phòng 1
            'start_time' => now()->setTime(9, 30),
            'end_time' => now()->setTime(12, 16),
            'price_standard' => 100000,
        ]);

        Showtime::create([
            'movie_id' => $movies[1]->id, // Avengers
            'room_id' => $rooms[1]->id,   // Phòng 2
            'start_time' => now()->setTime(14, 0),
            'end_time' => now()->setTime(17, 1),
            'price_standard' => 120000,
        ]);

        Showtime::create([
            'movie_id' => $movies[2]->id, // Inside Out 2
            'room_id' => $rooms[0]->id,   // Phòng 1
            'start_time' => now()->setTime(18, 0),
            'end_time' => now()->setTime(19, 36),
            'price_standard' => 90000,
        ]);

        Showtime::create([
            'movie_id' => $movies[3]->id, // Lật Mặt 7
            'room_id' => $rooms[2]->id,   // Phòng 3 IMAX
            'start_time' => now()->setTime(20, 15),
            'end_time' => now()->setTime(22, 33),
            'price_standard' => 110000,
        ]);

        // Suất chiếu ngày mai
        Showtime::create([
            'movie_id' => $movies[4]->id, // Godzilla x Kong
            'room_id' => $rooms[0]->id,   // Phòng 1
            'start_time' => now()->addDay()->setTime(10, 0),
            'end_time' => now()->addDay()->setTime(11, 55),
            'price_standard' => 95000,
        ]);

        Showtime::create([
            'movie_id' => $movies[0]->id, // Dune 2
            'room_id' => $rooms[2]->id,   // Phòng 3 IMAX
            'start_time' => now()->addDay()->setTime(15, 30),
            'end_time' => now()->addDay()->setTime(18, 16),
            'price_standard' => 130000,
        ]);

        Showtime::create([
            'movie_id' => $movies[2]->id, // Inside Out 2
            'room_id' => $rooms[1]->id,   // Phòng 2
            'start_time' => now()->addDay()->setTime(19, 0),
            'end_time' => now()->addDay()->setTime(20, 36),
            'price_standard' => 100000,
        ]);

        // Suất chiếu ngày mốt
        Showtime::create([
            'movie_id' => $movies[3]->id, // Lật Mặt 7
            'room_id' => $rooms[0]->id,
            'start_time' => now()->addDays(2)->setTime(14, 0),
            'end_time' => now()->addDays(2)->setTime(16, 18),
            'price_standard' => 90000,
        ]);

        Showtime::create([
            'movie_id' => $movies[1]->id, // Avengers
            'room_id' => $rooms[2]->id,
            'start_time' => now()->addDays(2)->setTime(20, 0),
            'end_time' => now()->addDays(2)->setTime(23, 1),
            'price_standard' => 140000,
        ]);
    }
}
