<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Movie;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\TicketDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    /**
     * Danh sách vé của người dùng đang đăng nhập
     */
    public function index()
    {
        $tickets = TicketDetail::with(['booking', 'seat', 'showtime.movie', 'showtime.room'])
            ->whereHas('booking', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->latest('id')
            ->get();

        return view('user.account.tickets', compact('tickets'));
    }

    /**
     * Xem chi tiết một vé cụ thể
     */
    public function show(string $id)
    {
        $ticket = TicketDetail::with(['booking.user', 'seat', 'showtime.movie', 'showtime.room'])
            ->whereHas('booking', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->findOrFail($id);

        return view('user.account.tickets.show', compact('ticket'));
    }

    /**
     * Màn hình chọn suất chiếu và sơ đồ ghế
     */
    public function booking(Request $request, Movie $movie)
    {
        $showtimes = Showtime::where('movie_id', $movie->id)
            ->where('end_time', '>=', now())
            ->orderBy('start_time')
            ->get();

        if ($showtimes->isEmpty()) {
            return redirect()->route('movie.show', $movie->id)
                ->with('error', 'Phim hiện chưa có suất chiếu khả dụng.');
        }

        $selectedShowtimeId = $request->query('showtime') ?? $showtimes->first()->id;
        $selectedShowtime = $showtimes->firstWhere('id', $selectedShowtimeId);

        if (!$selectedShowtime) {
            abort(404, 'Suất chiếu không tồn tại.');
        }

        // Lấy danh sách tất cả các ghế của phòng chiếu
        $seats = Seat::where('room_id', $selectedShowtime->room_id)
            ->orderBy('seat_row')
            ->orderBy('seat_number')
            ->get();

        // Lấy danh sách ghế đang giữ chỗ (HOLDING) hoặc đã đặt (BOOKED)
        $tickets = TicketDetail::where('showtime_id', $selectedShowtime->id)
            ->whereIn('status', ['HOLDING', 'BOOKED', 'USED'])
            ->get(['seat_id', 'status']);

        $holdingSeatIds = $tickets->where('status', 'HOLDING')->pluck('seat_id')->toArray();
        $bookedSeatIds = $tickets->whereIn('status', ['BOOKED', 'USED'])->pluck('seat_id')->toArray();

        return view('movie.booking', [
            'movie' => $movie,
            'showtimes' => $showtimes,
            'selectedShowtime' => $selectedShowtime,
            'seats' => $seats,
            'holdingSeatIds' => $holdingSeatIds,
            'bookedSeatIds' => $bookedSeatIds,
        ]);
    }

    /**
     * Màn hình xác nhận thông tin trước khi đặt vé
     */
    public function purchase(Request $request, Movie $movie)
    {
        $request->validate([
            'showtime_id' => 'required|exists:showtimes,id',
            'seat_ids' => 'required|string',
        ]);

        $showtime = Showtime::with('room')->findOrFail($request->showtime_id);
        $seatIdArray = array_filter(explode(',', $request->seat_ids));

        if (empty($seatIdArray)) {
            return back()->with('error', 'Vui lòng chọn ít nhất một ghế.');
        }

        $selectedSeats = Seat::whereIn('id', $seatIdArray)->get();

        // Tính giá từng ghế: Ghế VIP = 120% giá chuẩn, Ghế Standard = 100%
        $totalPrice = 0;
        foreach ($selectedSeats as $seat) {
            $seat->calculated_price = $seat->type === 'VIP'
                ? round($showtime->price_standard * 1.2)
                : $showtime->price_standard;
            $totalPrice += $seat->calculated_price;
        }

        return view('movie.purchase', compact('movie', 'showtime', 'selectedSeats', 'totalPrice'));
    }

    /**
     * Lưu thông tin đặt vé vào cơ sở dữ liệu
     */
    public function store(Request $request, Movie $movie)
    {
        $request->validate([
            'showtime_id' => 'required|exists:showtimes,id',
            'seat_ids' => 'required|array|min:1',
            'seat_ids.*' => 'exists:seats,id',
        ], [
            'seat_ids.required' => 'Vui lòng chọn ít nhất một ghế.',
        ]);

        $seatIds = $request->seat_ids;
        $showtime = Showtime::findOrFail($request->showtime_id);

        // Kiểm tra xem ghế đã bị ai khác đặt trước chưa
        $alreadyTaken = TicketDetail::where('showtime_id', $showtime->id)
            ->whereIn('seat_id', $seatIds)
            ->whereIn('status', ['HOLDING', 'BOOKED', 'USED'])
            ->exists();

        if ($alreadyTaken) {
            return redirect()
                ->route('ticket.booking', $movie->id)
                ->with('error', 'Một hoặc nhiều ghế bạn chọn vừa được người khác đặt. Vui lòng chọn ghế khác.');
        }

        $seats = Seat::whereIn('id', $seatIds)->get();

        DB::transaction(function () use ($seats, $showtime) {
            // Tính tổng tiền chính xác phân biệt Standard và VIP
            $totalPrice = 0;
            $seatPrices = [];

            foreach ($seats as $seat) {
                $price = $seat->type === 'VIP'
                    ? round($showtime->price_standard * 1.2)
                    : $showtime->price_standard;
                $seatPrices[$seat->id] = $price;
                $totalPrice += $price;
            }

            // Tạo đơn Booking
            $booking = Booking::create([
                'user_id' => Auth::id(),
                'total_price' => $totalPrice,
                'status' => 'PENDING',
                'expired_at' => now()->addMinutes(10),
            ]);

            // Tạo từng chi tiết vé TicketDetail
            foreach ($seats as $seat) {
                TicketDetail::create([
                    'booking_id' => $booking->id,
                    'showtime_id' => $showtime->id,
                    'seat_id' => $seat->id,
                    'final_price' => $seatPrices[$seat->id],
                    'status' => 'HOLDING',
                ]);
            }
        });

        return redirect()
            ->route('user.account.tickets')
            ->with('success', 'Đặt vé thành công! Vui lòng kiểm tra và thanh toán vé của bạn trong thời gian giữ chỗ.');
    }

    /**
     * Hủy vé giữ chỗ hoặc vé đã đặt
     */
    public function cancel(string $id)
    {
        $ticket = TicketDetail::where('id', $id)
            ->whereHas('booking', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->firstOrFail();

        if (in_array($ticket->status, ['HOLDING', 'BOOKED'])) {
            DB::transaction(function () use ($ticket) {
                $ticket->update(['status' => 'CANCELLED']);

                $booking = $ticket->booking;
                if ($booking) {
                    // Nếu tất cả các vé của đơn này đều đã hủy thì đơn chuyển sang CANCELLED
                    $activeTicketsCount = $booking->ticketDetails()
                        ->whereIn('status', ['HOLDING', 'BOOKED', 'USED'])
                        ->count();

                    if ($activeTicketsCount === 0) {
                        $booking->update(['status' => 'CANCELLED']);
                    }
                }
            });

            return redirect()
                ->route('user.account.tickets.show', $ticket->id)
                ->with('success', 'Hủy vé thành công.');
        }

        return redirect()
            ->route('user.account.tickets.show', $ticket->id)
            ->with('error', 'Vé này không thể hủy ở trạng thái hiện tại.');
    }

    /**
     * Thanh toán vé (mô phỏng thanh toán)
     */
    public function payment(string $id)
    {
        $ticket = TicketDetail::where('id', $id)
            ->whereHas('booking', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->firstOrFail();

        if ($ticket->status === 'HOLDING') {
            DB::transaction(function () use ($ticket) {
                $ticket->update(['status' => 'BOOKED']);

                $booking = $ticket->booking;
                if ($booking) {
                    // Nếu không còn vé nào ở trạng thái HOLDING, chuyển đơn sang PAID
                    $hasHolding = $booking->ticketDetails()->where('status', 'HOLDING')->exists();
                    if (!$hasHolding) {
                        $booking->update(['status' => 'PAID']);
                    }
                }
            });

            return redirect()
                ->route('user.account.tickets.show', $ticket->id)
                ->with('success', 'Thanh toán vé thành công! Chúc bạn xem phim vui vẻ.');
        }

        return redirect()
            ->route('user.account.tickets.show', $ticket->id)
            ->with('error', 'Vé đã được thanh toán hoặc không ở trạng thái chờ thanh toán.');
    }
}
