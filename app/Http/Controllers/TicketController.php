<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Movie;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\TicketDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    /**
     * Danh sách đơn đặt vé và vé của người dùng đang đăng nhập
     */
    public function index()
    {
        // Lấy danh sách Booking kèm đầy đủ thông tin vé, ghế, phim, phòng, thanh toán và hoàn tiền
        $bookings = Booking::with([
            'ticketDetails.seat',
            'ticketDetails.showtime.movie',
            'ticketDetails.showtime.room',
            'payments',
            'refunds'
        ])
            ->where('user_id', Auth::id())
            ->latest('id')
            ->get();

        // Đồng thời lấy danh sách vé lẻ để phục vụ giao diện danh sách vé
        $tickets = TicketDetail::with(['booking', 'seat', 'showtime.movie', 'showtime.room'])
            ->whereHas('booking', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->latest('id')
            ->get();

        return view('user.account.tickets', compact('bookings', 'tickets'));
    }

    /**
     * Xem chi tiết đơn đặt vé và tất cả các vé thuộc đơn đó
     */
    public function show(string $id)
    {
        // 1. Tìm theo Booking ID trước
        $booking = Booking::with([
            'user',
            'ticketDetails.seat',
            'ticketDetails.showtime.movie',
            'ticketDetails.showtime.room',
            'payments',
            'refunds'
        ])
            ->where('user_id', Auth::id())
            ->find($id);

        // 2. Nếu không phải Booking ID, kiểm tra xem có phải TicketDetail ID không
        if (!$booking) {
            $ticketDetail = TicketDetail::whereHas('booking', function ($q) {
                $q->where('user_id', Auth::id());
            })->find($id);

            if ($ticketDetail) {
                $booking = Booking::with([
                    'user',
                    'ticketDetails.seat',
                    'ticketDetails.showtime.movie',
                    'ticketDetails.showtime.room',
                    'payments',
                    'refunds'
                ])
                    ->where('user_id', Auth::id())
                    ->find($ticketDetail->booking_id);
            }
        }

        if (!$booking) {
            abort(404, 'Đơn đặt vé không tồn tại hoặc bạn không có quyền truy cập.');
        }

        $tickets = $booking->ticketDetails;
        $ticket = $tickets->first();

        return view('user.account.tickets.show', compact('booking', 'tickets', 'ticket'));
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

            // Tạo đơn Booking với thời gian giữ chỗ 10 phút
            $holdingExpiresAt = now()->addMinutes(10);
            $booking = Booking::create([
                'user_id' => Auth::id(),
                'total_price' => $totalPrice,
                'status' => 'PENDING',
                'holding_expires_at' => $holdingExpiresAt,
                'expired_at' => $holdingExpiresAt,
            ]);

            // Tạo từng chi tiết vé TicketDetail ở trạng thái HOLDING
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
            ->with('success', 'Đặt vé thành công! Bạn có 10 phút để thực hiện thanh toán cho đơn vé này.');
    }

    /**
     * Thanh toán cho toàn bộ Đơn đặt vé (Booking Payment)
     */
    public function payBooking(Request $request, string $id)
    {
        $booking = Booking::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($booking->status === 'PAID') {
            return redirect()
                ->route('user.account.tickets')
                ->with('error', 'Đơn đặt vé này đã được thanh toán trước đó.');
        }

        // Kiểm tra xem đơn hàng còn hạn thanh toán không
        if (!$booking->canPay()) {
            DB::transaction(function () use ($booking) {
                $booking->update(['status' => 'CANCELLED']);
                $booking->ticketDetails()->where('status', 'HOLDING')->update(['status' => 'CANCELLED']);
            });

            return redirect()
                ->route('user.account.tickets')
                ->with('error', 'Thời gian giữ chỗ của đơn hàng đã hết hạn. Các ghế đã được tự động giải phóng.');
        }

        $method = $request->input('payment_method', 'BANK_TRANSFER');
        if (!in_array($method, ['BANK_TRANSFER', 'E_WALLET', 'CARD'])) {
            $method = 'BANK_TRANSFER';
        }

        $provider = match ($method) {
            'E_WALLET' => 'MOMO',
            'CARD' => 'VISA/MASTERCARD',
            default => 'VNPAY',
        };

        DB::transaction(function () use ($booking, $method, $provider) {
            // 1. Tạo bản ghi thanh toán mới trong bảng payments
            Payment::create([
                'booking_id' => $booking->id,
                'method' => $method,
                'status' => 'PAID',
                'amount' => $booking->total_price,
                'payment_code' => 'PAY-' . strtoupper(Str::random(10)),
                'transaction_code' => 'TXN-' . strtoupper(Str::random(12)),
                'provider' => $provider,
                'paid_at' => now(),
            ]);

            // 2. Chuyển trạng thái đơn hàng sang PAID
            $booking->update(['status' => 'PAID']);

            // 3. Chuyển tất cả các vé của đơn từ HOLDING sang BOOKED
            $booking->ticketDetails()
                ->where('status', 'HOLDING')
                ->update(['status' => 'BOOKED']);
        });

        return redirect()
            ->route('user.account.tickets')
            ->with('success', "Thanh toán thành công đơn đặt vé #{$booking->id} qua {$provider}! Chúc bạn xem phim vui vẻ.");
    }

    /**
     * Hủy đơn đặt vé khi còn ở trạng thái PENDING (Cancel Booking)
     */
    public function cancelBooking(Request $request, string $id)
    {
        $booking = Booking::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($booking->status === 'PENDING') {
            DB::transaction(function () use ($booking) {
                $booking->update(['status' => 'CANCELLED']);
                $booking->ticketDetails()->update(['status' => 'CANCELLED']);

                // Đổi trạng thái các phiên thanh toán đang chờ nếu có
                $booking->payments()->where('status', 'PENDING')->update(['status' => 'CANCELLED']);
            });

            return redirect()
                ->route('user.account.tickets')
                ->with('success', 'Đã hủy đơn đặt vé thành công. Toàn bộ ghế đã được giải phóng.');
        }

        return redirect()
            ->route('user.account.tickets')
            ->with('error', 'Đơn đặt vé này không thể hủy ở trạng thái hiện tại.');
    }

    /**
     * Khách hàng gửi Yêu cầu Hoàn tiền (Customer Refund Request)
     */
    public function requestRefund(Request $request, string $id)
    {
        $booking = Booking::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($booking->status !== 'PAID') {
            return redirect()
                ->route('user.account.tickets')
                ->with('error', 'Chỉ những đơn vé đã thanh toán mới có thể gửi yêu cầu hoàn tiền.');
        }

        // Kiểm tra xem đã có yêu cầu hoàn tiền đang chờ hoặc đã duyệt chưa
        $existingRefund = $booking->refunds()
            ->whereIn('refunds.status', ['PENDING', 'COMPLETED'])
            ->exists();

        if ($existingRefund) {
            return redirect()
                ->route('user.account.tickets')
                ->with('error', 'Đơn đặt vé này đã có yêu cầu hoàn tiền đang được Ban quản trị xử lý.');
        }

        // Kiểm tra điều kiện thời gian: phải trước giờ chiếu ít nhất 2 tiếng (120 phút)
        $showtime = $booking->earliestShowtime();
        if (!$showtime || !Carbon::parse($showtime->start_time)->isFuture() || now()->diffInMinutes(Carbon::parse($showtime->start_time), false) < 120) {
            return redirect()
                ->route('user.account.tickets')
                ->with('error', 'Không thể hoàn tiền do vi phạm điều kiện thời gian (chỉ chấp nhận hoàn tiền trước giờ chiếu ít nhất 2 tiếng).');
        }

        // Tìm payment thành công
        $payment = $booking->successfulPayment ?? $booking->payments()->latest()->first();
        if (!$payment) {
            // Trường hợp dữ liệu cũ chưa có payment, tạo payment tự động
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'method' => 'BANK_TRANSFER',
                'status' => 'PAID',
                'amount' => $booking->total_price,
                'payment_code' => 'PAY-' . strtoupper(Str::random(10)),
                'paid_at' => now(),
            ]);
        }

        // Tạo bản ghi hoàn tiền mới ở trạng thái PENDING
        Refund::create([
            'payment_id' => $payment->id,
            'amount' => $booking->total_price,
            'status' => 'PENDING',
            'refund_code' => 'REF-' . strtoupper(Str::random(10)),
        ]);

        return redirect()
            ->route('user.account.tickets')
            ->with('success', 'Yêu cầu hoàn tiền đã được gửi thành công! Ban quản trị sẽ sớm xem xét và phản hồi.');
    }

    /**
     * Hủy vé lẻ (Dự phòng tương thích với các view hoặc test cũ)
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
     * Thanh toán vé lẻ (Dự phòng tương thích với các view hoặc test cũ)
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
                    $hasHolding = $booking->ticketDetails()->where('status', 'HOLDING')->exists();
                    if (!$hasHolding) {
                        $booking->update(['status' => 'PAID']);
                        // Tạo payment tương ứng nếu chưa có
                        if (!$booking->successfulPayment) {
                            Payment::create([
                                'booking_id' => $booking->id,
                                'method' => 'BANK_TRANSFER',
                                'status' => 'PAID',
                                'amount' => $booking->total_price,
                                'payment_code' => 'PAY-' . strtoupper(Str::random(10)),
                                'paid_at' => now(),
                            ]);
                        }
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
