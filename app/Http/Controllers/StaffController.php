<?php

namespace App\Http\Controllers;

use App\Models\TicketDetail;
use Illuminate\Http\Request;
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
        $ticket = null;

        if ($query !== '') {
            $ticket = TicketDetail::with(['booking.user', 'showtime.movie', 'seat', 'showtime.room'])
                ->where('id', $query)
                ->orWhereHas('booking', function ($builder) use ($query) {
                    $builder->where('id', $query);
                })
                ->first();
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

        return view('staff.tickets', compact('tickets', 'ticket', 'query', 'statuses'));
    }

    /**
     * Xem chi tiết vé
     */
    public function show(TicketDetail $ticket)
    {
        $ticket->load(['booking.user', 'showtime.movie', 'seat', 'showtime.room']);

        // Chỉ cho phép chuyển đổi từ BOOKED sang USED hoặc CANCELLED
        $allowedTransitions = [
            'BOOKED' => [
                'USED' => 'Đã sử dụng',
                'CANCELLED' => 'Đã hủy',
            ],
        ];

        $availableStatuses = $allowedTransitions[$ticket->status] ?? [];

        return view('staff.tickets.show', compact('ticket', 'availableStatuses'));
    }

    /**
     * Cập nhật trạng thái vé (chỉ cho phép sửa từ BOOKED sang USED hoặc CANCELLED)
     */
    public function updateStatus(Request $request, TicketDetail $ticket)
    {
        // Kiểm tra điều kiện chỉ cho phép sửa vé có trạng thái BOOKED
        if ($ticket->status !== 'BOOKED') {
            return redirect()
                ->route('staff.tickets.show', $ticket)
                ->with('error', 'Chỉ được phép cập nhật vé đang ở trạng thái "Đã thanh toán" (BOOKED).');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['USED', 'CANCELLED'])],
        ], [
            'status.in' => 'Chỉ được phép chuyển trạng thái vé sang "Đã sử dụng" hoặc "Đã hủy".',
        ]);

        $ticket->update(['status' => $validated['status']]);

        return redirect()
            ->route('staff.tickets.show', $ticket)
            ->with('success', 'Đã cập nhật trạng thái vé thành công.');
    }
}
