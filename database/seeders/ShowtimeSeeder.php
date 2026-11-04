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

        // Suất chiếu cho các phim mới và các phòng mới (Phòng 4 đến 8)
        $newShowtimes = [
            // Oppenheimer - Phòng 3 (IMAX Laser) & Phòng 5 (Dolby Atmos)
            [
                'movie_id' => $movies[6]->id, // Oppenheimer
                'room_id' => $rooms[2]->id,   // Phòng 3 (IMAX Laser)
                'start_time' => now()->setTime(13, 0),
                'end_time' => now()->setTime(16, 0),
                'price_standard' => 150000,
            ],
            [
                'movie_id' => $movies[6]->id, // Oppenheimer
                'room_id' => $rooms[4]->id,   // Phòng 5 (Dolby Atmos)
                'start_time' => now()->addDay()->setTime(18, 30),
                'end_time' => now()->addDay()->setTime(21, 30),
                'price_standard' => 135000,
            ],
            // Avatar: The Way of Water - Phòng 3 (IMAX) & Phòng 7 (Premiere)
            [
                'movie_id' => $movies[7]->id, // Avatar 2
                'room_id' => $rooms[2]->id,   // Phòng 3
                'start_time' => now()->setTime(17, 0),
                'end_time' => now()->setTime(20, 12),
                'price_standard' => 160000,
            ],
            [
                'movie_id' => $movies[7]->id, // Avatar 2
                'room_id' => $rooms[6]->id,   // Phòng 7 (Premiere Grand Stage)
                'start_time' => now()->addDay()->setTime(20, 0),
                'end_time' => now()->addDay()->setTime(23, 12),
                'price_standard' => 140000,
            ],
            // Interstellar - Phòng 4 (VIP Gold Lounge) & Phòng 3 (IMAX)
            [
                'movie_id' => $movies[8]->id, // Interstellar
                'room_id' => $rooms[3]->id,   // Phòng 4 (VIP Gold Lounge)
                'start_time' => now()->setTime(19, 30),
                'end_time' => now()->setTime(22, 19),
                'price_standard' => 180000,
            ],
            // Spider-Man: Across the Spider-Verse - Phòng 7 & Phòng 2
            [
                'movie_id' => $movies[9]->id, // Spider-Verse
                'room_id' => $rooms[6]->id,   // Phòng 7
                'start_time' => now()->setTime(10, 30),
                'end_time' => now()->setTime(12, 50),
                'price_standard' => 110000,
            ],
            [
                'movie_id' => $movies[9]->id, // Spider-Verse
                'room_id' => $rooms[1]->id,   // Phòng 2
                'start_time' => now()->addDay()->setTime(14, 30),
                'end_time' => now()->addDay()->setTime(16, 50),
                'price_standard' => 105000,
            ],
            // Despicable Me 4 - Phòng 1 & Phòng 8 (4DX)
            [
                'movie_id' => $movies[10]->id, // Despicable Me 4
                'room_id' => $rooms[0]->id,    // Phòng 1
                'start_time' => now()->setTime(15, 30),
                'end_time' => now()->setTime(17, 4),
                'price_standard' => 95000,
            ],
            [
                'movie_id' => $movies[10]->id, // Despicable Me 4
                'room_id' => $rooms[7]->id,    // Phòng 8 (Motion 4DX)
                'start_time' => now()->addDay()->setTime(11, 0),
                'end_time' => now()->addDay()->setTime(12, 34),
                'price_standard' => 140000,
            ],
            // Kung Fu Panda 4 - Phòng 5 & Phòng 1
            [
                'movie_id' => $movies[11]->id, // Kung Fu Panda 4
                'room_id' => $rooms[4]->id,    // Phòng 5
                'start_time' => now()->setTime(11, 0),
                'end_time' => now()->setTime(12, 34),
                'price_standard' => 100000,
            ],
            // John Wick 4 - Phòng 7 (Premiere) & Phòng 8 (4DX)
            [
                'movie_id' => $movies[12]->id, // John Wick 4
                'room_id' => $rooms[7]->id,    // Phòng 8 (4DX)
                'start_time' => now()->setTime(21, 0),
                'end_time' => now()->setTime(23, 49),
                'price_standard' => 165000,
            ],
            // Mai - Phòng 4 (VIP Gold Lounge) & Phòng 6 (Studio Cinema)
            [
                'movie_id' => $movies[14]->id, // Mai
                'room_id' => $rooms[3]->id,    // Phòng 4 (VIP Lounge)
                'start_time' => now()->setTime(15, 0),
                'end_time' => now()->setTime(17, 11),
                'price_standard' => 170000,
            ],
            [
                'movie_id' => $movies[14]->id, // Mai
                'room_id' => $rooms[5]->id,    // Phòng 6 (Studio Cinema)
                'start_time' => now()->addDay()->setTime(19, 30),
                'end_time' => now()->addDay()->setTime(21, 41),
                'price_standard' => 105000,
            ],
        ];

        foreach ($newShowtimes as $st) {
            if (isset($st['movie_id']) && isset($st['room_id'])) {
                Showtime::create($st);
            }
        }
    }
}
