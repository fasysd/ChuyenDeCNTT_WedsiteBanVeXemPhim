<?php

namespace Database\Seeders;

use App\Models\Movie;
use Illuminate\Database\Seeder;

class MovieSeeder extends Seeder
{
    public function run(): void
    {
        $movies = [
            [
                'title' => 'Dune: Part Two',
                'duration' => 166,
                'release_date' => now()->subDays(10)->toDateString(),
                'image_path' => 'images/movieavatar.webp',
                'genre' => 'Khoa học viễn tưởng, Hành động',
                'description' => 'Paul Atreides hợp nhất với Chani và người Fremen trên hành trình báo thù những kẻ đã hủy hoại gia đình mình.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Avengers: Endgame',
                'duration' => 181,
                'release_date' => now()->subDays(30)->toDateString(),
                'image_path' => 'images/movieavatar.webp',
                'genre' => 'Hành động, Viễn tưởng',
                'description' => 'Sau thảm họa cú búng tay của Thanos, các Avengers còn sống sót tập hợp để đảo ngược kết cục vũ trụ.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Inside Out 2 (Những Mảnh Ghép Cảm Xúc 2)',
                'duration' => 96,
                'release_date' => now()->subDays(5)->toDateString(),
                'image_path' => 'images/movieavatar.webp',
                'genre' => 'Hoạt hình, Gia đình, Hài hước',
                'description' => 'Riley bước vào tuổi dậy thì, và đại bản doanh cảm xúc chào đón những thành viên mới đầy bất ngờ như Lo Âu và Ganh Tị.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Lật Mặt 7: Một Điều Ước',
                'duration' => 138,
                'release_date' => now()->subDays(15)->toDateString(),
                'image_path' => 'images/movieavatar.webp',
                'genre' => 'Gia đình, Tâm lý',
                'description' => 'Câu chuyện cảm động về tình mẫu tử của bà Hai 73 tuổi và năm người con bận rộn nơi thành thị.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Godzilla x Kong: Đế Chế Mới',
                'duration' => 115,
                'release_date' => now()->subDays(7)->toDateString(),
                'image_path' => 'images/movieavatar.webp',
                'genre' => 'Hành động, Phiêu lưu',
                'description' => 'Hai quái thú huyền thoại Godzilla và Kong buộc phải liên minh trước hiểm họa đe dọa sự diệt vong của Trái Đất.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Deadpool & Wolverine (Sắp Chiếu)',
                'duration' => 127,
                'release_date' => now()->addDays(14)->toDateString(),
                'image_path' => 'images/movieavatar.webp',
                'genre' => 'Hành động, Hài hước',
                'description' => 'Sự kết hợp bùng nổ giữa hai dị nhân được yêu thích nhất của vũ trụ Marvel.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($movies as $movie) {
            Movie::create($movie);
        }
    }
}
