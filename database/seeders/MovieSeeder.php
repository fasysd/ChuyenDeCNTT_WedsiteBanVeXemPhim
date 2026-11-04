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
                'image_path' => 'images/Dune_Part_Two_poster.jpeg',
                'genre' => 'Khoa học viễn tưởng, Hành động',
                'description' => 'Paul Atreides hợp nhất với Chani và người Fremen trên hành trình báo thù những kẻ đã hủy hoại gia đình mình.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Avengers: Endgame',
                'duration' => 181,
                'release_date' => now()->subDays(30)->toDateString(),
                'image_path' => 'images/AvengersEndgame_poster.jpg',
                'genre' => 'Hành động, Viễn tưởng',
                'description' => 'Sau thảm họa cú búng tay của Thanos, các Avengers còn sống sót tập hợp để đảo ngược kết cục vũ trụ.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Inside Out 2 (Những Mảnh Ghép Cảm Xúc 2)',
                'duration' => 96,
                'release_date' => now()->subDays(5)->toDateString(),
                'image_path' => 'images/InsideOut2_poster.jpg',
                'genre' => 'Hoạt hình, Gia đình, Hài hước',
                'description' => 'Riley bước vào tuổi dậy thì, và đại bản doanh cảm xúc chào đón những thành viên mới đầy bất ngờ như Lo Âu và Ganh Tị.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Lật Mặt 7: Một Điều Ước',
                'duration' => 138,
                'release_date' => now()->subDays(15)->toDateString(),
                'image_path' => 'images/LatMat7_poster.jpg',
                'genre' => 'Gia đình, Tâm lý',
                'description' => 'Câu chuyện cảm động về tình mẫu tử của bà Hai 73 tuổi và năm người con bận rộn nơi thành thị.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Godzilla x Kong: Đế Chế Mới',
                'duration' => 115,
                'release_date' => now()->subDays(7)->toDateString(),
                'image_path' => 'images/GodzillaxKong_DeCheMoi_poster.jpg',
                'genre' => 'Hành động, Phiêu lưu',
                'description' => 'Hai quái thú huyền thoại Godzilla và Kong buộc phải liên minh trước hiểm họa đe dọa sự diệt vong của Trái Đất.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Deadpool & Wolverine (Sắp Chiếu)',
                'duration' => 127,
                'release_date' => now()->addDays(14)->toDateString(),
                'image_path' => 'images/Deadpool&Wolverine_poster.jpg',
                'genre' => 'Hành động, Hài hước',
                'description' => 'Sự kết hợp bùng nổ giữa hai dị nhân được yêu thích nhất của vũ trụ Marvel.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Oppenheimer',
                'duration' => 180,
                'release_date' => now()->subDays(60)->toDateString(),
                'image_path' => 'images/Oppenheimer_poster.jpg',
                'genre' => 'Tiểu sử, Lịch sử, Kịch tính',
                'description' => 'Câu chuyện về nhà vật lý lý thuyết J. Robert Oppenheimer, người dẫn đầu Dự án Manhattan phát triển vũ khí nguyên tử đầu tiên.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Avatar: Dòng Chảy Của Nước',
                'duration' => 192,
                'release_date' => now()->subDays(45)->toDateString(),
                'image_path' => 'images/Avatar2_poster.jpg',
                'genre' => 'Khoa học viễn tưởng, Hành động, Phiêu lưu',
                'description' => 'Jake Sully và Neytiri cùng các con phải rời bỏ ngôi nhà quen thuộc để tìm kiếm sự bảo vệ tại các vùng biển Pandora kỳ vĩ.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Interstellar (Hố Đen Tử Thần)',
                'duration' => 169,
                'release_date' => now()->subDays(90)->toDateString(),
                'image_path' => 'images/Interstellar_poster.jpg',
                'genre' => 'Khoa học viễn tưởng, Phiêu lưu, Tâm lý',
                'description' => 'Khi Trái Đất đứng trước bờ vực tuyệt chủng, một nhóm nhà thám hiểm vượt qua hố sâu không gian tìm kiếm hành tinh mới cho nhân loại.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Người Nhện: Du Hành Vũ Trụ Nhện',
                'duration' => 140,
                'release_date' => now()->subDays(20)->toDateString(),
                'image_path' => 'images/SpiderVerse2_poster.jpg',
                'genre' => 'Hoạt hình, Hành động, Phiêu lưu',
                'description' => 'Miles Morales bị cuốn vào đa vũ trụ đầy hiểm nguy, nơi cậu phải đối đầu với một liên minh Người Nhện để bảo vệ những người thân yêu.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Kẻ Trộm Mặt Trăng 4 (Despicable Me 4)',
                'duration' => 94,
                'release_date' => now()->subDays(3)->toDateString(),
                'image_path' => 'images/DespicableMe4_poster.jpeg',
                'genre' => 'Hoạt hình, Hài hước, Gia đình',
                'description' => 'Gru và đại gia đình chào đón thành viên mới Gru Jr., đồng thời phải đối mặt với âm mưu nguy hiểm từ kẻ thù mới Maxime Le Mal.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Kung Fu Panda 4',
                'duration' => 94,
                'release_date' => now()->subDays(12)->toDateString(),
                'image_path' => 'images/KungFuPanda4_poster.jpg',
                'genre' => 'Hoạt hình, Hành động, Hài hước',
                'description' => 'Po bước vào hành trình mới để trở thành Thủ lĩnh Tinh thần của Thung lũng Bình Yên và đào tạo một Chiến binh Rồng kế nhiệm.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Sát Thủ John Wick: Phần 4',
                'duration' => 169,
                'release_date' => now()->subDays(40)->toDateString(),
                'image_path' => 'images/JohnWick4_poster.jpg',
                'genre' => 'Hành động, Giật gân',
                'description' => 'John Wick tìm ra con đường đánh bại Hội Đồng Tối Cao, nhưng trước khi có tự do, anh phải đối đầu với liên minh sát thủ toàn cầu.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Hành Tinh Khỉ: Vương Quốc Mới (Sắp Chiếu)',
                'duration' => 145,
                'release_date' => now()->addDays(10)->toDateString(),
                'image_path' => 'images/PlanetOfTheApes4_poster.jpg',
                'genre' => 'Khoa học viễn tưởng, Hành động, Phiêu lưu',
                'description' => 'Nhiều thế hệ sau triều đại của Caesar, một chú tinh tinh trẻ tuổi bắt đầu cuộc hành trình định hình lại tương lai của vượn và loài người.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Mai',
                'duration' => 131,
                'release_date' => now()->subDays(25)->toDateString(),
                'image_path' => 'images/Mai_poster.jpg',
                'genre' => 'Tâm lý, Tình cảm',
                'description' => 'Mai luôn mang nỗi mặc cảm quá khứ và khao khát được yêu thương, cho đến khi cô gặp gỡ Dương - chàng trai lãng tử sống vô tư.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Nhà Bà Nữ (Sắp Chiếu)',
                'duration' => 102,
                'release_date' => now()->addDays(20)->toDateString(),
                'image_path' => 'images/NhaBaNu_poster.jpg',
                'genre' => 'Tâm lý, Gia đình, Hài hước',
                'description' => 'Câu chuyện cảm xúc về gia đình ba thế hệ với gánh bánh canh cua gia truyền, nơi yêu thương vô tình biến thành những rào cản áp đặt.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($movies as $movie) {
            Movie::create($movie);
        }
    }
}
