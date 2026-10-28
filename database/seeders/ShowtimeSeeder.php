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
        // Showtime::truncate();

        $movies = Movie::all();
        $rooms = Room::all();

        $movie1 = $movies[0];
        $movie2 = $movies[1];
        $movie3 = $movies[2];

        $room1 = $rooms[0];
        $room2 = $rooms[1];

        Showtime::create([
            'movie_id' => $movie1->id,
            'room_id' => $room1->id,
            'start_time' => now()->addDay()->setTime(9, 0),
            'end_time' => now()->addDay()->setTime(12, 1),
            'price_standard' => 100000,
        ]);

        Showtime::create([
            'movie_id' => $movie2->id,
            'room_id' => $room2->id,
            'start_time' => now()->addDay()->setTime(14, 0),
            'end_time' => now()->addDay()->setTime(16, 46),
            'price_standard' => 120000,
        ]);

        Showtime::create([
            'movie_id' => $movie3->id,
            'room_id' => $room1->id,
            'start_time' => now()->addDays(2)->setTime(19, 0),
            'end_time' => now()->addDays(2)->setTime(20, 36),
            'price_standard' => 90000,
        ]);

        Showtime::create([
            'movie_id' => $movie1->id,
            'room_id' => $room2->id,
            'start_time' => now()->addDays(2)->setTime(21, 0),
            'end_time' => now()->addDays(3)->setTime(0, 1),
            'price_standard' => 110000,
        ]);
    }
}