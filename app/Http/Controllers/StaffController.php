<?php

namespace App\Http\Controllers;

use App\Models\TicketDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    /**
     * Danh sách tất cả các vé đã đặt (phục vụ quản lý)
     */
    public function bookings(Request $request)
    {
        $tickets = TicketDetail::with(['booking.user', 'showtime.movie', 'seat', 'showtime.room'])
            ->latest('id')
            ->paginate(20);

        return view('staff.bookings', compact('tickets'));
    }

    /**
     * Soát vé / tra cứu vé theo mã vé hoặc mã đơn đặt
     */
    public function tickets(Request $request)
    {
        $query = trim($request->query('q', ''));
        $matchedTickets = collect();
        $ticket = null;

        if ($query !== '' && ctype_digit($query)) {
            $matchedTickets = TicketDetail::with(['booking.user', 'showtime.movie', 'seat', 'showtime.room'])
                ->where(function ($builder) use ($query) {
                    $builder->where('id', (int) $query)
                        ->orWhere('booking_id', (int) $query);
                })
                ->orderBy('id')
                ->get();

            $ticket = $matchedTickets->first();
        }

        $tickets = TicketDetail::with(['showtime.movie', 'seat', 'booking.user', 'showtime.room'])
            ->latest('id')
            ->limit(20)
            ->get();

        $statuses = [
            'HOLDING' => 'Đang giữ chỗ',
            'BOOKED' => 'Đã thanh toán',
            'USED' => 'Đã sử dụng',
            'CANCELLED' => 'Đã hủy',
        ];

        return view('staff.tickets', compact('tickets', 'ticket', 'matchedTickets', 'query', 'statuses'));
    }

    /**
     * Xem chi tiết vé
     */
    public function show(TicketDetail $ticket)
    {
        $ticket->load(['booking.user', 'showtime.movie', 'seat', 'showtime.room']);

        // Nhân viên chỉ được phép check-in (chuyển từ BOOKED sang USED)
        $allowedTransitions = [
            'BOOKED' => [
                'USED' => 'Đã sử dụng',
            ],
        ];

        $availableStatuses = $allowedTransitions[$ticket->status] ?? [];

        return view('staff.tickets.show', compact('ticket', 'availableStatuses'));
    }

    /**
     * Cập nhật trạng thái vé (chỉ cho phép check-in từ BOOKED sang USED)
     */
    public function updateStatus(Request $request, TicketDetail $ticket)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['USED'])],
        ], [
            'status.in' => 'Nhân viên chỉ được phép xác nhận vé sang trạng thái "Đã sử dụng".',
        ]);

        $lock = Cache::lock("lock:ticket:checkin:{$ticket->id}", 5);
        if (!$lock->get()) {
            return redirect()
                ->route('staff.tickets.show', $ticket)
                ->with('error', 'Vé này đang được xử lý bởi nhân viên khác. Vui lòng kiểm tra lại.');
        }

        try {
            $result = DB::transaction(function () use ($ticket, $validated) {
                $lockedTicket = TicketDetail::with('showtime')
                    ->where('id', $ticket->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Kiểm tra điều kiện chỉ cho phép sửa vé có trạng thái BOOKED
                if ($lockedTicket->status !== 'BOOKED') {
                    return [
                        'ok' => false,
                        'message' => 'Chỉ được phép cập nhật vé đang ở trạng thái "Đã thanh toán" (BOOKED).',
                    ];
                }

                // Khi check-in (USED), chặn nếu suất chiếu đã kết thúc
                if ($lockedTicket->showtime && Carbon::parse($lockedTicket->showtime->end_time)->isPast()) {
                    return [
                        'ok' => false,
                        'message' => 'Suất chiếu của vé này đã kết thúc, không thể check-in.',
                    ];
                }

                $lockedTicket->update(['status' => $validated['status']]);

                return [
                    'ok' => true,
                    'message' => 'Đã cập nhật trạng thái vé thành công.',
                ];
            });
        } finally {
            try {
                $lock->release();
            } catch (\Throwable $e) {
            }
        }

        return redirect()
            ->route('staff.tickets.show', $ticket)
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
