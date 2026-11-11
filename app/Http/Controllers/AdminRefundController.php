<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $lock = Cache::lock("lock:refund:{$refund->id}", 10);
        if (!$lock->get()) {
            return back()->with('error', 'Yêu cầu hoàn tiền này đang được xử lý bởi quản trị viên khác.');
        }

        try {
            $processed = DB::transaction(function () use ($refund) {
                $lockedRefund = Refund::where('id', $refund->id)->lockForUpdate()->firstOrFail();

                if ($lockedRefund->status !== 'PENDING') {
                    return false;
                }

                // 1. Cập nhật trạng thái Refund thành COMPLETED
                $lockedRefund->update([
                    'status' => 'COMPLETED',
                    'refunded_at' => now(),
                ]);

                // 2. Khóa và cập nhật trạng thái Booking thành REFUNDED
                $bookingId = $lockedRefund->payment?->booking_id;
                $booking = $bookingId ? Booking::where('id', $bookingId)->lockForUpdate()->first() : null;
                if ($booking) {
                    $booking->update(['status' => 'REFUNDED']);

                    // 3. Cập nhật trạng thái các vé thuộc booking thành CANCELLED để giải phóng ghế
                    $booking->ticketDetails()->update(['status' => 'CANCELLED']);
                }

                return true;
            });
        } finally {
            try {
                $lock->release();
            } catch (\Throwable $e) {
            }
        }

        if (!$processed) {
            return back()->with('error', 'Yêu cầu hoàn tiền này đã được xử lý trước đó.');
        }

        return back()->with('success', 'Xác nhận hoàn tiền thành công! Ghế của đơn hàng đã được giải phóng.');
    }

    /**
     * Từ chối hoàn tiền
     */
    public function reject(Refund $refund)
    {
        $lock = Cache::lock("lock:refund:{$refund->id}", 10);
        if (!$lock->get()) {
            return back()->with('error', 'Yêu cầu hoàn tiền này đang được xử lý bởi quản trị viên khác.');
        }

        try {
            $processed = DB::transaction(function () use ($refund) {
                $lockedRefund = Refund::where('id', $refund->id)->lockForUpdate()->firstOrFail();

                if ($lockedRefund->status !== 'PENDING') {
                    return false;
                }

                // Cập nhật trạng thái Refund thành FAILED, Booking vẫn giữ trạng thái PAID
                $lockedRefund->update([
                    'status' => 'FAILED',
                ]);

                return true;
            });
        } finally {
            try {
                $lock->release();
            } catch (\Throwable $e) {
            }
        }

        if (!$processed) {
            return back()->with('error', 'Yêu cầu hoàn tiền này đã được xử lý trước đó.');
        }

        return back()->with('success', 'Đã từ chối yêu cầu hoàn tiền. Đơn đặt vé của khách hàng vẫn giữ nguyên hiệu lực.');
    }
}
