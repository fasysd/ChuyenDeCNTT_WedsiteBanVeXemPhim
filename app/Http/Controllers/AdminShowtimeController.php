<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use App\Services\ShowtimeService;
use Illuminate\Http\Request;

class AdminShowtimeController extends Controller
{
    private ShowtimeService $showtimeService;

    public function __construct(ShowtimeService $showtimeService)
    {
        $this->showtimeService = $showtimeService;
    }

    /**
     * Danh sách suất chiếu
     */
    public function index()
    {
        $showtimes = $this->showtimeService->getAllShowtimes();

        return view('admin.showtimes.index', compact('showtimes'));
    }

    public function show(int $id)
    {
        $showtime = $this->showtimeService->getShowtimeById($id);

        return view(
            'admin.showtimes.show',
            compact('showtime')
        );
    }

    /**
     * Form thêm suất chiếu
     */
    public function create()
    {
        $movies = Movie::all();
        $rooms = Room::all();

        return view('admin.showtimes.create', compact(
            'movies',
            'rooms'
        ));
    }

    /**
     * Thêm suất chiếu
     */
    public function store(Request $request)
    {
        $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'price_standard' => 'required|numeric|min:0'
        ]);

        $result = $this->showtimeService->createShowtime([
            'movie_id' => $request->movie_id,
            'room_id' => $request->room_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'price_standard' => $request->price_standard
        ]);

        switch ($result) {
            case ShowtimeService::ERROR_START_TIME:
                return back()
                    ->withInput()
                    ->withErrors([
                        'start_time' => 'Thời gian bắt đầu phải ở tương lai.'
                    ]);
            
            case ShowtimeService::ERROR_BEFORE_RELEASE:
                return back()
                    ->withInput()
                    ->withErrors([
                        'start_time' => 'Thời gian chiếu phải sau ngày phát hành của phim.'
                    ]);

            case ShowtimeService::ERROR_DURATION:
                return back()
                    ->withInput()
                    ->withErrors([
                        'end_time' => 'Thời lượng suất chiếu ngắn hơn thời lượng phim.'
                    ]);

            case ShowtimeService::ERROR_CONFLICT:
                return back()
                    ->withInput()
                    ->withErrors([
                        'room_id' => 'Phòng đã có suất chiếu trong khoảng thời gian này.'
                    ]);
        }

        return redirect()
            ->route('admin.showtimes.index')
            ->with('success', 'Thêm lịch chiếu thành công.');
    }

    /**
     * Xem thông tin suất chiếu
     */
    public function edit(int $id)
    {
        $movies = Movie::all();
        $rooms = Room::all();

        $showtime = $this->showtimeService->getShowtimeById($id);

        return view(
            'admin.showtimes.edit',
            compact('showtime', 'movies', 'rooms')
        );
    }

    /**
     * Cập nhật suất chiếu
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'movie_id' => 'required|integer',
            'room_id' => 'required|integer',
            'start_time' => 'required|date',
            'end_time' => 'required|date',
            'price_standard' => 'required|numeric|min:0'
        ]);

        $result = $this->showtimeService->updateShowtime(
            $id,
            [
                'movie_id' => $request->movie_id,
                'room_id' => $request->room_id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'price_standard' => $request->price_standard
            ]
        );

        switch ($result) {

            case ShowtimeService::ERROR_START_TIME:
                return back()
                    ->withInput()
                    ->withErrors([
                        'start_time' => 'Thời gian bắt đầu phải ở tương lai.'
                    ]);

            case ShowtimeService::ERROR_BEFORE_RELEASE:
                return back()
                    ->withInput()
                    ->withErrors([
                        'start_time' => 'Thời gian chiếu phải sau ngày phát hành của phim.'
                    ]);

            case ShowtimeService::ERROR_DURATION:
                return back()
                    ->withInput()
                    ->withErrors([
                        'end_time' => 'Thời lượng suất chiếu ngắn hơn thời lượng phim.'
                    ]);

            case ShowtimeService::ERROR_CONFLICT:
                return back()
                    ->withInput()
                    ->withErrors([
                        'room_id' => 'Phòng đã có suất chiếu trong khoảng thời gian này.'
                    ]);

            case ShowtimeService::ERROR_HAS_TICKETS:
                return back()
                    ->withInput()
                    ->withErrors([
                        'room_id' => 'Không thể sửa suất chiếu đã có vé.'
                    ]);
        }

        return redirect()
            ->route('admin.showtimes.index')
            ->with('success', 'Cập nhật lịch chiếu thành công.');
    }

    /**
     * Xóa suất chiếu
     */
    public function destroy(int $id)
    {
        $result = $this->showtimeService->deleteShowtime($id);

        if ($result === ShowtimeService::ERROR_HAS_TICKETS) {
            return back()
                ->withErrors([
                    'showtime' => 'Không thể xóa suất chiếu đã có vé.'
                ]);
        }

        return redirect()
            ->route('admin.showtimes.index')
            ->with('success', 'Xóa suất chiếu thành công.');
    }
}