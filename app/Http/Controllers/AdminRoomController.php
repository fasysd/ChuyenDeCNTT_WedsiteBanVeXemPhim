<?php

namespace App\Http\Controllers;

use App\Services\RoomService;
use Illuminate\Http\Request;

class AdminRoomController extends Controller
{
    private RoomService $roomService;

    public function __construct(RoomService $roomService)
    {
        $this->roomService = $roomService;
    }

    /**
     * Danh sách phòng
     */
    public function index()
    {
        $rooms = $this->roomService->getAllRooms();

        return view('admin.rooms.index', compact('rooms'));
    }

    public function show(int $id)
    {
        $room = $this->roomService->getRoomById($id);

        $seatPerRow = $room->seats()
            ->where('seat_row', 'A')
            ->count();

        $rowCount = (int) (
            $room->total_seats / max($seatPerRow, 1)
        );

        $vipSeats = $room->seats()
            ->where('type', 'VIP')
            ->get()
            ->map(function ($seat) {
                return $seat->seat_row . $seat->seat_number;
            })
            ->toArray();

        return view(
            'admin.rooms.show',
            compact(
                'room',
                'rowCount',
                'seatPerRow',
                'vipSeats'
            )
        );
    }

    /**
     * Form thêm phòng
     */
    public function create()
    {
        return view('admin.rooms.create');
    }

    /**
     * Thêm phòng
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255',
            'row_count' => 'required|integer|min:1|max:26',
            'seat_per_row' => 'required|integer|min:1',
            'vip_seats' => 'nullable|string',
        ]);

        $result = $this->roomService->createRoom([
            'name' => $request->name,
            'row_count' => $request->row_count,
            'seat_per_row' => $request->seat_per_row,
            'vip_seats' => json_decode(
                $request->vip_seats ?? '[]',
                true
            ),
        ]);

        if ($result === RoomService::ERROR_DUPLICATE_NAME) {
            return back()
                ->withInput()
                ->withErrors([
                    'name' => 'Tên phòng đã tồn tại.'
                ]);
        }

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', 'Thêm phòng thành công.');
    }

    /**
     * Xem thông tin phòng
     */
    public function edit(int $id)
    {
        $room = $this->roomService->getRoomById($id);
        $seatPerRow = $room->seats()
            ->where('seat_row', 'A')
            ->count();

        $rowCount = (int) ($room->seats()->count() / max($seatPerRow, 1));

        $vipSeats = $room->seats()
            ->where('type', 'VIP')
            ->get()
            ->map(function ($seat) {
                return $seat->seat_row . $seat->seat_number;
            })
            ->values()
            ->toArray();

        return view(
            'admin.rooms.edit',
            compact('room', 'rowCount', 'seatPerRow', 'vipSeats')
        );
    }

    /**
     * Cập nhật phòng
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'name' => 'required|max:255',
            'row_count' => 'required|integer|min:1|max:26',
            'seat_per_row' => 'required|integer|min:1',
            'vip_seats' => 'nullable|string',
        ]);

        $result = $this->roomService->updateRoom(
            $id,
            [
                'name' => $request->name,
                'row_count' => $request->row_count,
                'seat_per_row' => $request->seat_per_row,
                'vip_seats' => json_decode(
                    $request->vip_seats ?? '[]',
                    true
                ),
            ]
        );

        switch ($result) {

            case RoomService::ERROR_DUPLICATE_NAME:
                return back()
                    ->withInput()
                    ->withErrors([
                        'name' => 'Tên phòng đã tồn tại.'
                    ]);

            case RoomService::ERROR_HAS_SHOWTIMES:
            case RoomService::ERROR_HAS_HISTORY:
                return back()
                    ->withInput()
                    ->withErrors([
                        'general' => 'Không thể thay đổi sơ đồ hoặc loại ghế của phòng đã có suất chiếu hoặc vé đã đặt.'
                    ]);
        }

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', 'Cập nhật phòng thành công.');
    }

    /**
     * Xóa phòng
     */
    public function destroy(int $id)
    {
        $result = $this->roomService->deleteRoom($id);

        if (in_array($result, [RoomService::ERROR_HAS_SHOWTIMES, RoomService::ERROR_HAS_HISTORY], true)) {
            return back()->withErrors([
                'general' => 'Không thể xóa phòng đã có suất chiếu hoặc lịch sử vé đặt.'
            ]);
        }

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', 'Xóa phòng thành công.');
    }
}