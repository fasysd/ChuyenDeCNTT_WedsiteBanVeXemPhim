<?php

namespace Database\Seeders;

use App\Models\Movie;
use Illuminate\Database\Seeder;

class MovieSeeder extends Seeder
{
    public function run(): void
    {
        // Movie::truncate();

        Movie::insert([
            [
                'title' => 'Avengers: Endgame',
                'duration' => 181,
                'release_date' => '2019-04-26',
                'genre' => 'Action',
                'description' => 'Marvel movie',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Dune Part Two',
                'duration' => 166,
                'release_date' => '2024-03-01',
                'genre' => 'Sci-Fi',
                'description' => 'Dune movie',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Inside Out 2',
                'duration' => 96,
                'release_date' => '2024-06-14',
                'genre' => 'Animation',
                'description' => 'Pixar animation movie',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'The Flash',
                'duration' => 144,
                'release_date' => '2023-06-16',
                'genre' => 'Action',
                'description' => 'DC movie',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'The Little Mermaid',
                'duration' => 135,
                'release_date' => '2023-05-26',
                'genre' => 'Fantasy',
                'description' => 'Disney movie',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'The Marvels',
                'duration' => 105,
                'release_date' => '2023-07-28',
                'genre' => 'Action',
                'description' => 'Marvel movie',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}