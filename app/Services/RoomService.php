<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Seat;
use Illuminate\Support\Facades\DB;

class RoomService
{
    public const ERROR_DUPLICATE_NAME = 'DUPLICATE_NAME';
    public const ERROR_HAS_SHOWTIMES = 'HAS_SHOWTIMES';

    public function getAllRooms()
    {
        return Room::all();
    }

    public function getRoomById(int $id)
    {
        return Room::findOrFail($id);
    }

    public function createRoom(array $data)
    {
        if (!$this->validateRoomName($data['name'])) {
            return self::ERROR_DUPLICATE_NAME;
        }

        return DB::transaction(function () use ($data) {
            $room = Room::create([
                'name' => $data['name'],
                'total_seats' => $data['row_count'] * $data['seat_per_row']
            ]);

            $this->generateSeats(
                $room->id,
                $data['row_count'],
                $data['seat_per_row'],
                $data['vip_seats'] ?? []
            );

            return $room;
        });
    }

    public function updateRoom(int $id, array $data)
    {
        $room = Room::findOrFail($id);

        if (!$this->validateRoomName($data['name'], $id)) {
            return self::ERROR_DUPLICATE_NAME;
        }

        if (!$this->validateRoomCanModify($room)) {
            return self::ERROR_HAS_SHOWTIMES;
        }

        return DB::transaction(function () use ($room, $data) {
            $room->update([
                'name' => $data['name'],
                'total_seats' => $data['row_count'] * $data['seat_per_row']
            ]);

            Seat::where('room_id', $room->id)->delete();

            $this->generateSeats(
                $room->id,
                $data['row_count'],
                $data['seat_per_row'],
                $data['vip_seats'] ?? []
            );

            return $room->fresh();
        });
    }

    public function deleteRoom(int $id)
    {
        $room = Room::findOrFail($id);

        if (!$this->validateRoomCanDelete($room)) {
            return self::ERROR_HAS_SHOWTIMES;
        }

        return DB::transaction(function () use ($room) {
            Seat::where('room_id', $room->id)->delete();
            $room->delete();
            return true;
        });
    }

    private function generateSeats(
        int $roomId,
        int $rowCount,
        int $seatPerRow,
        array $vipSeats = []
    ): void {
        $seats = [];

        foreach ($vipSeats as $key => $seatCode) {
            $vipSeats[$key] = strtoupper($seatCode);
        }

        for ($row = 0; $row < $rowCount; $row++) {
            $rowName = chr(65 + $row);

            for ($seat = 1; $seat <= $seatPerRow; $seat++) {
                $seatCode = $rowName . $seat;

                $seats[] = [
                    'room_id' => $roomId,
                    'seat_row' => $rowName,
                    'seat_number' => $seat,
                    'type' => in_array($seatCode, $vipSeats) ? 'VIP' : 'STANDARD',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        Seat::insert($seats);
    }

    /**
     * Tên phòng không được trùng lặp
     */
    private function validateRoomName(string $name, ?int $ignoreRoomId = null): bool
    {
        $query = Room::where('name', $name);

        if ($ignoreRoomId !== null) {
            $query->where('id', '!=', $ignoreRoomId);
        }

        return !$query->exists();
    }

    /**
     * Không cho sửa phòng nếu còn suất chiếu trong tương lai
     */
    private function validateRoomCanModify(Room $room): bool
    {
        return !$room->showtimes()
            ->where('start_time', '>', now())
            ->exists();
    }

    /**
     * Không cho xóa phòng nếu còn suất chiếu trong tương lai
     */
    private function validateRoomCanDelete(Room $room): bool
    {
        return !$room->showtimes()
            ->where('start_time', '>', now())
            ->exists();
    }
}
