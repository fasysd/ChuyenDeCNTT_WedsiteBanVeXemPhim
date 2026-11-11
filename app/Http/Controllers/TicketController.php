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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    /**
     * Danh sách đơn đặt vé và vé của người dùng đang đăng nhập
     */
    public function index()
    {
        // Tự động giải phóng các đơn giữ chỗ đã quá hạn 10 phút trước khi hiển thị
        Booking::releaseExpiredBookings();

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
        Booking::releaseExpiredBookings();

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
        // Tự động giải phóng các ghế giữ chỗ đã hết hạn trước khi hiển thị sơ đồ ghế
        Booking::releaseExpiredBookings();

        $showtimes = Showtime::where('movie_id', $movie->id)
            ->where('start_time', '>', now())
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

        if ((int) $showtime->movie_id !== (int) $movie->id) {
            return redirect()->route('ticket.booking', $movie->id)
                ->with('error', 'Suất chiếu không thuộc bộ phim này.');
        }

        if (!Carbon::parse($showtime->start_time)->isFuture()) {
            return redirect()->route('ticket.booking', $movie->id)
                ->with('error', 'Suất chiếu này đã bắt đầu hoặc kết thúc, không thể đặt vé.');
        }

        $seatIdArray = collect(explode(',', $request->seat_ids))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($seatIdArray)) {
            return back()->with('error', 'Vui lòng chọn ít nhất một ghế.');
        }

        $selectedSeats = Seat::whereIn('id', $seatIdArray)
            ->where('room_id', $showtime->room_id)
            ->get();

        if ($selectedSeats->count() !== count($seatIdArray)) {
            return redirect()->route('ticket.booking', $movie->id)
                ->with('error', 'Một hoặc nhiều ghế được chọn không thuộc phòng chiếu của suất này.');
        }

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
     * Sử dụng mô hình bảo vệ kép (Atomic Cache Lock + DB Pessimistic Lock) chống Race Condition
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

        // 1. Sắp xếp danh sách ID ghế tăng dần và loại trùng để chống Deadlock tuần tự
        $seatIds = collect($request->seat_ids)
            ->map(fn($id) => (int)$id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $showtime = Showtime::findOrFail($request->showtime_id);

        if ((int) $showtime->movie_id !== (int) $movie->id) {
            return redirect()
                ->route('ticket.booking', $movie->id)
                ->with('error', 'Suất chiếu không thuộc bộ phim này.');
        }

        if (!Carbon::parse($showtime->start_time)->isFuture()) {
            return redirect()
                ->route('ticket.booking', $movie->id)
                ->with('error', 'Suất chiếu này đã bắt đầu hoặc kết thúc, không thể đặt vé.');
        }

        $validSeatsCount = Seat::whereIn('id', $seatIds)
            ->where('room_id', $showtime->room_id)
            ->count();

        if ($validSeatsCount !== count($seatIds)) {
            return redirect()
                ->route('ticket.booking', $movie->id)
                ->with('error', 'Một hoặc nhiều ghế được chọn không thuộc phòng chiếu của suất này.');
        }

        // Tự động giải phóng các ghế giữ chỗ đã hết hạn trước khi kiểm tra
        Booking::releaseExpiredBookings();

        // 2. Tầng 1: Cache Atomic Lock (Fail-Fast: Chặn ngay từ tầng ứng dụng)
        $acquiredLocks = [];
        $lockAcquired = true;

        foreach ($seatIds as $seatId) {
            $lockKey = "lock:showtime:{$showtime->id}:seat:{$seatId}";
            $lock = Cache::lock($lockKey, 10); // Khóa trong 10 giây
            if (!$lock->get()) {
                $lockAcquired = false;
                break;
            }
            $acquiredLocks[] = $lock;
        }

        if (!$lockAcquired) {
            foreach ($acquiredLocks as $lock) {
                try {
                    $lock->release();
                } catch (\Throwable $e) {
                }
            }

            return redirect()
                ->route('ticket.booking', $movie->id)
                ->with('error', 'Một hoặc nhiều ghế bạn chọn đang được người khác giữ chỗ. Vui lòng chọn ghế khác hoặc thử lại sau giây lát.');
        }

        // 3. Tầng 2: Database Transaction + Pessimistic Lock (ACID Guarantee)
        try {
            DB::transaction(function () use ($seatIds, $showtime) {
                // Khóa các bản ghi ghế theo thứ tự để tuần tự hóa giao dịch
                $seats = Seat::whereIn('id', $seatIds)->orderBy('id')->lockForUpdate()->get();

                // Kiểm tra xem ghế đã bị ai khác đặt hoặc giữ chỗ hợp lệ chưa (với lock)
                $alreadyTaken = TicketDetail::where('showtime_id', $showtime->id)
                    ->whereIn('seat_id', $seatIds)
                    ->whereIn('status', ['HOLDING', 'BOOKED', 'USED'])
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyTaken) {
                    throw new \DomainException('Một hoặc nhiều ghế bạn chọn vừa được người khác đặt. Vui lòng chọn ghế khác.');
                }

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

                // Tạo hoặc cập nhật từng chi tiết vé (xử lý an toàn khi ghế từng bị CANCELLED trước đó)
                foreach ($seats as $seat) {
                    TicketDetail::updateOrCreate(
                        [
                            'showtime_id' => $showtime->id,
                            'seat_id' => $seat->id,
                        ],
                        [
                            'booking_id' => $booking->id,
                            'final_price' => $seatPrices[$seat->id],
                            'status' => 'HOLDING',
                        ]
                    );
                }
            });
        } catch (\DomainException $e) {
            return redirect()
                ->route('ticket.booking', $movie->id)
                ->with('error', $e->getMessage());
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()
                ->route('ticket.booking', $movie->id)
                ->with('error', 'Một hoặc nhiều ghế bạn chọn vừa được người khác đặt trước. Vui lòng chọn ghế khác.');
        } catch (\Throwable $e) {
            report($e);
            return redirect()
                ->route('ticket.booking', $movie->id)
                ->with('error', 'Đã xảy ra lỗi trong quá trình đặt vé. Vui lòng thử lại sau giây lát.');
        } finally {
            // Luôn giải phóng tất cả Cache Lock
            foreach ($acquiredLocks as $lock) {
                try {
                    $lock->release();
                } catch (\Throwable $e) {
                }
            }
        }

        return redirect()
            ->route('user.account.tickets')
            ->with('success', 'Đặt vé thành công! Bạn có 10 phút để thực hiện thanh toán cho đơn vé này.');
    }

    /**
     * Thanh toán cho toàn bộ Đơn đặt vé (Booking Payment)
     */
    public function payBooking(Request $request, string $id)
    {
        $method = $request->input('payment_method', 'BANK_TRANSFER');
        if (!in_array($method, ['BANK_TRANSFER', 'E_WALLET', 'CARD'])) {
            $method = 'BANK_TRANSFER';
        }

        $provider = match ($method) {
            'E_WALLET' => 'MOMO',
            'CARD' => 'VISA/MASTERCARD',
            default => 'VNPAY',
        };

        $lock = Cache::lock("lock:booking:{$id}", 10);
        if (!$lock->get()) {
            return redirect()
                ->route('user.account.tickets')
                ->with('error', 'Đơn đặt vé đang được xử lý. Vui lòng thử lại sau giây lát.');
        }

        try {
            $result = DB::transaction(function () use ($id, $method, $provider) {
                $booking = Booking::where('id', $id)
                    ->where('user_id', Auth::id())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($booking->status === 'PAID') {
                    return [
                        'ok' => false,
                        'message' => 'Đơn đặt vé này đã được thanh toán trước đó.',
                    ];
                }

                if (in_array($booking->status, ['CANCELLED', 'REFUNDED'], true)) {
                    return [
                        'ok' => false,
                        'message' => 'Đơn đặt vé này đã bị hủy hoặc đã hoàn tiền, không thể thanh toán.',
                    ];
                }

                // Kiểm tra xem đơn hàng còn hạn thanh toán không
                if (!$booking->canPay()) {
                    $booking->update(['status' => 'CANCELLED']);
                    $booking->ticketDetails()->where('status', 'HOLDING')->update(['status' => 'CANCELLED']);
                    $booking->payments()->where('status', 'PENDING')->update(['status' => 'CANCELLED']);

                    return [
                        'ok' => false,
                        'message' => 'Thời gian giữ chỗ của đơn hàng đã hết hạn. Các ghế đã được tự động giải phóng.',
                    ];
                }

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

                return [
                    'ok' => true,
                    'message' => "Thanh toán thành công đơn đặt vé #{$booking->id} qua {$provider}! Chúc bạn xem phim vui vẻ.",
                ];
            });
        } finally {
            try {
                $lock->release();
            } catch (\Throwable $e) {
            }
        }

        return redirect()
            ->route('user.account.tickets')
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Hủy đơn đặt vé khi còn ở trạng thái PENDING (Cancel Booking)
     */
    public function cancelBooking(Request $request, string $id)
    {
        $lock = Cache::lock("lock:booking:{$id}", 10);
        if (!$lock->get()) {
            return redirect()
                ->route('user.account.tickets')
                ->with('error', 'Đơn đặt vé đang được xử lý. Vui lòng thử lại sau giây lát.');
        }

        try {
            $cancelled = DB::transaction(function () use ($id) {
                $booking = Booking::where('id', $id)
                    ->where('user_id', Auth::id())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($booking->status !== 'PENDING') {
                    return false;
                }

                $booking->update(['status' => 'CANCELLED']);
                $booking->ticketDetails()->update(['status' => 'CANCELLED']);

                // Đổi trạng thái các phiên thanh toán đang chờ nếu có
                $booking->payments()->where('status', 'PENDING')->update(['status' => 'CANCELLED']);

                return true;
            });
        } finally {
            try {
                $lock->release();
            } catch (\Throwable $e) {
            }
        }

        if ($cancelled) {
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
        $lock = Cache::lock("lock:booking:{$id}", 10);
        if (!$lock->get()) {
            return redirect()
                ->route('user.account.tickets')
                ->with('error', 'Yêu cầu của bạn đang được xử lý. Vui lòng không gửi lại liên tiếp.');
        }

        try {
            $result = DB::transaction(function () use ($id) {
                $booking = Booking::where('id', $id)
                    ->where('user_id', Auth::id())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($booking->status !== 'PAID') {
                    return [
                        'ok' => false,
                        'message' => 'Chỉ những đơn vé đã thanh toán mới có thể gửi yêu cầu hoàn tiền.',
                    ];
                }

                // Không cho phép hoàn tiền nếu đã có vé check-in sử dụng (USED)
                if ($booking->ticketDetails()->where('status', 'USED')->exists()) {
                    return [
                        'ok' => false,
                        'message' => 'Không thể hoàn tiền vì có vé trong đơn đã được sử dụng (check-in).',
                    ];
                }

                // Kiểm tra xem đã có yêu cầu hoàn tiền đang chờ hoặc đã duyệt chưa (với lock)
                $existingRefund = $booking->refunds()
                    ->whereIn('refunds.status', ['PENDING', 'COMPLETED'])
                    ->exists();

                if ($existingRefund) {
                    return [
                        'ok' => false,
                        'message' => 'Đơn đặt vé này đã có yêu cầu hoàn tiền đang được Ban quản trị xử lý.',
                    ];
                }

                // Kiểm tra điều kiện thời gian: phải trước giờ chiếu ít nhất 2 tiếng (120 phút)
                $showtime = $booking->earliestShowtime();
                if (!$showtime || !Carbon::parse($showtime->start_time)->isFuture() || now()->diffInMinutes(Carbon::parse($showtime->start_time), false) < 120) {
                    return [
                        'ok' => false,
                        'message' => 'Không thể hoàn tiền do vi phạm điều kiện thời gian (chỉ chấp nhận hoàn tiền trước giờ chiếu ít nhất 2 tiếng).',
                    ];
                }

                // Tìm payment thành công
                $payment = $booking->successfulPayment()->first() ?? $booking->payments()->latest()->first();
                if (!$payment) {
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

                return [
                    'ok' => true,
                    'message' => 'Yêu cầu hoàn tiền đã được gửi thành công! Ban quản trị sẽ sớm xem xét và phản hồi.',
                ];
            });
        } finally {
            try {
                $lock->release();
            } catch (\Throwable $e) {
            }
        }

        return redirect()
            ->route('user.account.tickets')
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Hủy vé lẻ (Chỉ áp dụng cho vé đang giữ chỗ HOLDING; vé đã thanh toán phải dùng Hoàn tiền)
     */
    public function cancel(string $id)
    {
        $result = DB::transaction(function () use ($id) {
            $ticket = TicketDetail::where('id', $id)
                ->whereHas('booking', function ($query) {
                    $query->where('user_id', Auth::id());
                })
                ->lockForUpdate()
                ->firstOrFail();

            if ($ticket->status === 'BOOKED') {
                return [
                    'ok' => false,
                    'ticket_id' => $ticket->id,
                    'message' => 'Vé đã thanh toán không thể hủy trực tiếp. Vui lòng sử dụng chức năng Yêu cầu Hoàn tiền.',
                ];
            }

            if ($ticket->status !== 'HOLDING') {
                return [
                    'ok' => false,
                    'ticket_id' => $ticket->id,
                    'message' => 'Vé này không thể hủy ở trạng thái hiện tại.',
                ];
            }

            $ticket->update(['status' => 'CANCELLED']);

            $booking = Booking::where('id', $ticket->booking_id)->lockForUpdate()->first();
            if ($booking) {
                $activeTickets = $booking->ticketDetails()
                    ->whereIn('status', ['HOLDING', 'BOOKED', 'USED'])
                    ->get();

                if ($activeTickets->isEmpty()) {
                    $booking->update(['status' => 'CANCELLED', 'total_price' => 0]);
                } else {
                    $booking->update(['total_price' => $activeTickets->sum('final_price')]);
                }
            }

            return [
                'ok' => true,
                'ticket_id' => $ticket->id,
                'message' => 'Hủy vé thành công.',
            ];
        });

        return redirect()
            ->route('user.account.tickets.show', $result['ticket_id'])
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Thanh toán vé lẻ (Dự phòng tương thích với các view hoặc test cũ)
     */
    public function payment(string $id)
    {
        $lock = Cache::lock("lock:ticket:pay:{$id}", 10);
        if (!$lock->get()) {
            return redirect()
                ->route('user.account.tickets.show', $id)
                ->with('error', 'Giao dịch thanh toán cho vé này đang được xử lý. Vui lòng chờ giây lát.');
        }

        try {
            $result = DB::transaction(function () use ($id) {
                $ticket = TicketDetail::where('id', $id)
                    ->whereHas('booking', function ($query) {
                        $query->where('user_id', Auth::id());
                    })
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($ticket->status !== 'HOLDING') {
                    return [
                        'ok' => false,
                        'ticket_id' => $ticket->id,
                        'message' => 'Vé đã được thanh toán hoặc không ở trạng thái chờ thanh toán.',
                    ];
                }

                $booking = Booking::where('id', $ticket->booking_id)->lockForUpdate()->first();
                if ($booking && !$booking->canPay()) {
                    $booking->update(['status' => 'CANCELLED']);
                    $booking->ticketDetails()->where('status', 'HOLDING')->update(['status' => 'CANCELLED']);
                    $booking->payments()->where('status', 'PENDING')->update(['status' => 'CANCELLED']);

                    return [
                        'ok' => false,
                        'ticket_id' => $ticket->id,
                        'message' => 'Thời gian giữ chỗ của đơn hàng đã hết hạn. Các ghế đã được tự động giải phóng.',
                    ];
                }

                $ticket->update(['status' => 'BOOKED']);

                if ($booking) {
                    $hasHolding = $booking->ticketDetails()->where('status', 'HOLDING')->exists();
                    if (!$hasHolding) {
                        $booking->update(['status' => 'PAID']);
                        if (!$booking->successfulPayment()->exists()) {
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

                return [
                    'ok' => true,
                    'ticket_id' => $ticket->id,
                    'message' => 'Thanh toán vé thành công! Chúc bạn xem phim vui vẻ.',
                ];
            });
        } finally {
            try {
                $lock->release();
            } catch (\Throwable $e) {
            }
        }

        return redirect()
            ->route('user.account.tickets.show', $result['ticket_id'])
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
