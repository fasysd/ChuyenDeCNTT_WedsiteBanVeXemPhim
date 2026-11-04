<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Seat;
use Illuminate\Database\Seeder;

class SeatSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = Room::orderBy('id')->get();

        $roomConfigs = [
            0 => [ // Phòng 01: Standard Cinema (6 hàng A-F, 10 ghế: 60 ghế)
                'rows' => ['A', 'B', 'C', 'D', 'E', 'F'],
                'seats_per_row' => 10,
                'vip_condition' => fn($row, $col) => in_array($row, ['C', 'D']) && $col >= 4 && $col <= 7,
            ],
            1 => [ // Phòng 02: Deluxe Cinema (7 hàng A-G, 12 ghế: 84 ghế)
                'rows' => ['A', 'B', 'C', 'D', 'E', 'F', 'G'],
                'seats_per_row' => 12,
                'vip_condition' => fn($row, $col) => in_array($row, ['D', 'E']) && $col >= 4 && $col <= 9,
            ],
            2 => [ // Phòng 03: IMAX Laser (8 hàng A-H, 14 ghế: 112 ghế)
                'rows' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'],
                'seats_per_row' => 14,
                'vip_condition' => fn($row, $col) => in_array($row, ['D', 'E', 'F']) && $col >= 4 && $col <= 11,
            ],
            3 => [ // Phòng 04: VIP Gold Lounge (4 hàng A-D, 8 ghế: 32 ghế)
                'rows' => ['A', 'B', 'C', 'D'],
                'seats_per_row' => 8,
                'vip_condition' => fn($row, $col) => in_array($row, ['B', 'C']) && $col >= 3 && $col <= 6,
            ],
            4 => [ // Phòng 05: Dolby Atmos Cinema (6 hàng A-F, 11 ghế: 66 ghế)
                'rows' => ['A', 'B', 'C', 'D', 'E', 'F'],
                'seats_per_row' => 11,
                'vip_condition' => fn($row, $col) => in_array($row, ['C', 'D', 'E']) && $col >= 4 && $col <= 8,
            ],
            5 => [ // Phòng 06: Studio Cinema (5 hàng A-E, 8 ghế: 40 ghế)
                'rows' => ['A', 'B', 'C', 'D', 'E'],
                'seats_per_row' => 8,
                'vip_condition' => fn($row, $col) => in_array($row, ['C', 'D']) && $col >= 3 && $col <= 6,
            ],
            6 => [ // Phòng 07: Premiere Grand Stage (9 hàng A-I, 12 ghế: 108 ghế)
                'rows' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'],
                'seats_per_row' => 12,
                'vip_condition' => fn($row, $col) => in_array($row, ['E', 'F', 'G']) && $col >= 4 && $col <= 9,
            ],
            7 => [ // Phòng 08: Motion 4DX Cinema (5 hàng A-E, 10 ghế: 50 ghế)
                'rows' => ['A', 'B', 'C', 'D', 'E'],
                'seats_per_row' => 10,
                'vip_condition' => fn($row, $col) => in_array($row, ['C', 'D']) && $col >= 3 && $col <= 8,
            ],
        ];

        $seatsToInsert = [];
        $now = now();

        foreach ($rooms as $index => $room) {
            $config = $roomConfigs[$index] ?? [
                'rows' => ['A', 'B', 'C', 'D', 'E'],
                'seats_per_row' => 10,
                'vip_condition' => fn($row, $col) => $row === 'C' && $col >= 3 && $col <= 8,
            ];

            $totalSeatsInRoom = 0;

            foreach ($config['rows'] as $row) {
                for ($number = 1; $number <= $config['seats_per_row']; $number++) {
                    $isVip = ($config['vip_condition'])($row, $number);
                    $seatsToInsert[] = [
                        'room_id' => $room->id,
                        'seat_row' => $row,
                        'seat_number' => $number,
                        'type' => $isVip ? 'VIP' : 'STANDARD',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $totalSeatsInRoom++;
                }
            }

            // Đồng bộ lại total_seats chính xác cho phòng
            $room->update(['total_seats' => $totalSeatsInRoom]);
        }

        // Chèn hàng loạt để tối ưu hiệu năng
        foreach (array_chunk($seatsToInsert, 200) as $chunk) {
            Seat::insert($chunk);
        }
    }
}
