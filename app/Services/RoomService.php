<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Seat;
use Illuminate\Support\Facades\DB;

class RoomService
{
    public const ERROR_DUPLICATE_NAME = 'DUPLICATE_NAME';
    public const ERROR_HAS_SHOWTIMES = 'HAS_SHOWTIMES';
    public const ERROR_HAS_HISTORY = 'HAS_HISTORY';

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
        return DB::transaction(function () use ($data) {
            if (!$this->validateRoomName($data['name'])) {
                return self::ERROR_DUPLICATE_NAME;
            }

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
        return DB::transaction(function () use ($id, $data) {
            $room = Room::where('id', $id)->lockForUpdate()->firstOrFail();

            if (!$this->validateRoomName($data['name'], $id)) {
                return self::ERROR_DUPLICATE_NAME;
            }

            $hasHistory = $this->hasRoomHistory($room);

            if ($hasHistory) {
                if ($this->isSeatLayoutChanged($room, $data)) {
                    return self::ERROR_HAS_HISTORY;
                }

                // Phòng đã có lịch sử suất chiếu/vé -> chỉ cho phép đổi tên phòng, giữ nguyên ghế
                $room->update([
                    'name' => $data['name'],
                ]);

                return $room->fresh();
            }

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
        return DB::transaction(function () use ($id) {
            $room = Room::where('id', $id)->lockForUpdate()->firstOrFail();

            if ($this->hasRoomHistory($room)) {
                return self::ERROR_HAS_HISTORY;
            }

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
     * Kiểm tra phòng đã từng có suất chiếu hoặc vé được tạo chưa
     */
    private function hasRoomHistory(Room $room): bool
    {
        return $room->showtimes()->exists()
            || $room->seats()->whereHas('ticketDetails')->exists();
    }

    /**
     * Kiểm tra xem sơ đồ ghế (số hàng, số ghế mỗi hàng, danh sách ghế VIP) có bị thay đổi không
     */
    private function isSeatLayoutChanged(Room $room, array $data): bool
    {
        $currentSeats = $room->seats()->orderBy('seat_row')->orderBy('seat_number')->get();
        $currentSeatPerRow = $currentSeats->where('seat_row', 'A')->count();
        $currentRowCount = $currentSeatPerRow > 0 ? (int) ($currentSeats->count() / $currentSeatPerRow) : 0;

        $currentVipSeats = $currentSeats->where('type', 'VIP')
            ->map(fn ($seat) => $seat->seat_row . $seat->seat_number)
            ->sort()
            ->values()
            ->all();

        $newVipSeats = collect($data['vip_seats'] ?? [])
            ->map(fn ($code) => strtoupper(trim((string) $code)))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return ((int) $data['row_count'] !== $currentRowCount)
            || ((int) $data['seat_per_row'] !== $currentSeatPerRow)
            || ($newVipSeats !== $currentVipSeats);
    }
}
