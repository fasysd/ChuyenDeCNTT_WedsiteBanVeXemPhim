<?php

namespace App\Http\Controllers;

use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminRefundController extends Controller
{
    /**
     * Danh sách yêu cầu hoàn tiền
     */
    public function index(Request $request)
    {
        $status = $request->query('status');

        $query = Refund::with([
            'payment.booking.user',
            'payment.booking.ticketDetails.seat',
            'payment.booking.ticketDetails.showtime.movie',
            'payment.booking.ticketDetails.showtime.room',
        ])->latest();

        if ($status && in_array($status, ['PENDING', 'COMPLETED', 'FAILED'])) {
            $query->where('status', $status);
        }

        $refunds = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Refund::count(),
            'pending' => Refund::where('status', 'PENDING')->count(),
            'completed' => Refund::where('status', 'COMPLETED')->count(),
            'failed' => Refund::where('status', 'FAILED')->count(),
            'total_refunded_amount' => Refund::where('status', 'COMPLETED')->sum('amount'),
        ];

        return view('admin.refunds.index', compact('refunds', 'stats', 'status'));
    }

    /**
     * Xác nhận hoàn tiền (Admin Duyệt)
     */
    public function approve(Refund $refund)
    {
        if ($refund->status !== 'PENDING') {
            return back()->with('error', 'Yêu cầu hoàn tiền này đã được xử lý trước đó.');
        }

        DB::transaction(function () use ($refund) {
            // 1. Cập nhật trạng thái Refund thành COMPLETED
            $refund->update([
                'status' => 'COMPLETED',
                'refunded_at' => now(),
            ]);

            // 2. Cập nhật trạng thái Booking thành REFUNDED
            $booking = $refund->payment->booking;
            if ($booking) {
                $booking->update(['status' => 'REFUNDED']);

                // 3. Cập nhật trạng thái các vé thuộc booking thành CANCELLED để giải phóng ghế
                $booking->ticketDetails()->update(['status' => 'CANCELLED']);
            }
        });

        return back()->with('success', 'Xác nhận hoàn tiền thành công! Ghế của đơn hàng đã được giải phóng.');
    }

    /**
     * Từ chối hoàn tiền
     */
    public function reject(Refund $refund)
    {
        if ($refund->status !== 'PENDING') {
            return back()->with('error', 'Yêu cầu hoàn tiền này đã được xử lý trước đó.');
        }

        // Cập nhật trạng thái Refund thành FAILED, Booking vẫn giữ trạng thái PAID
        $refund->update([
            'status' => 'FAILED',
        ]);

        return back()->with('success', 'Đã từ chối yêu cầu hoàn tiền. Đơn đặt vé của khách hàng vẫn giữ nguyên hiệu lực.');
    }
}
