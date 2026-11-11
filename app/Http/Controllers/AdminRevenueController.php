<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\TicketDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminRevenueController extends Controller
{
    /**
     * Báo cáo & Thống kê Doanh thu
     */
    public function index(Request $request)
    {
        $period = $request->query('period', 'this_month');
        $movieId = $request->query('movie_id');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        // Khởi tạo truy vấn Payment thành công (status = 'PAID')
        $paymentQuery = Payment::with([
            'booking.user',
            'booking.ticketDetails.seat',
            'booking.ticketDetails.showtime.movie',
            'refunds'
        ])->where('status', 'PAID');

        // Xử lý bộ lọc thời gian
        $startDate = null;
        $endDate = null;

        if ($fromDate && $toDate) {
            try {
                $startDate = Carbon::parse($fromDate)->startOfDay();
                $endDate = Carbon::parse($toDate)->endOfDay();
                if ($startDate->gt($endDate)) {
                    [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
                }
            } catch (\Throwable $e) {
                $startDate = null;
                $endDate = null;
                $fromDate = null;
                $toDate = null;
            }
        } else {
            switch ($period) {
                case 'today':
                    $startDate = now()->startOfDay();
                    $endDate = now()->endOfDay();
                    break;
                case 'this_week':
                    $startDate = now()->startOfWeek();
                    $endDate = now()->endOfWeek();
                    break;
                case 'this_month':
                    $startDate = now()->startOfMonth();
                    $endDate = now()->endOfMonth();
                    break;
                case 'this_year':
                    $startDate = now()->startOfYear();
                    $endDate = now()->endOfYear();
                    break;
                case 'all':
                default:
                    // Toàn thời gian
                    break;
            }
        }

        if ($startDate && $endDate) {
            $paymentQuery->whereBetween('paid_at', [$startDate, $endDate]);
        }

        // Xử lý bộ lọc theo phim
        if ($movieId) {
            $paymentQuery->whereHas('booking.ticketDetails.showtime', function ($q) use ($movieId) {
                $q->where('movie_id', $movieId);
            });
        }

        // Lấy danh sách giao dịch gần nhất
        $payments = (clone $paymentQuery)->latest('paid_at')->paginate(15)->withQueryString();

        // 1. Tổng doanh thu gộp từ các lượt thanh toán thành công
        $grossRevenue = (clone $paymentQuery)->sum('amount');

        // 2. Tổng số tiền đã hoàn tiền cho các giao dịch trong phạm vi lọc
        $refundQuery = Refund::where('status', 'COMPLETED');
        if ($startDate && $endDate) {
            $refundQuery->whereBetween('refunded_at', [$startDate, $endDate]);
        }
        if ($movieId) {
            $refundQuery->whereHas('payment.booking.ticketDetails.showtime', function ($q) use ($movieId) {
                $q->where('movie_id', $movieId);
            });
        }
        $totalRefundAmount = $refundQuery->sum('amount');

        // 3. Doanh thu thuần (Doanh thu ròng)
        $netRevenue = max(0, $grossRevenue - $totalRefundAmount);

        // 4. Tổng số đơn hàng thanh toán thành công
        $totalPaidBookings = (clone $paymentQuery)->count();

        // 5. Tổng số vé đã bán thành công (tính theo thời điểm thanh toán paid_at)
        $ticketQuery = TicketDetail::whereIn('status', ['BOOKED', 'USED']);
        if ($startDate && $endDate) {
            $ticketQuery->whereHas('booking.payments', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'PAID')
                    ->whereBetween('paid_at', [$startDate, $endDate]);
            });
        }
        if ($movieId) {
            $ticketQuery->whereHas('showtime', function ($q) use ($movieId) {
                $q->where('movie_id', $movieId);
            });
        }
        $totalTicketsSold = $ticketQuery->count();

        // Thống kê doanh thu theo từng phim trong khoảng thời gian đã chọn
        $movies = Movie::all();
        $moviesById = $movies->keyBy('id');

        $movieStatsQuery = TicketDetail::query()
            ->join('showtimes', 'ticket_details.showtime_id', '=', 'showtimes.id')
            ->join('bookings', 'ticket_details.booking_id', '=', 'bookings.id')
            ->whereIn('ticket_details.status', ['BOOKED', 'USED'])
            ->where('bookings.status', 'PAID');

        if ($startDate && $endDate) {
            $movieStatsQuery->whereExists(function ($sub) use ($startDate, $endDate) {
                $sub->select(DB::raw(1))
                    ->from('payments')
                    ->whereColumn('payments.booking_id', 'bookings.id')
                    ->where('payments.status', 'PAID')
                    ->whereBetween('payments.paid_at', [$startDate, $endDate]);
            });
        }

        if ($movieId) {
            $movieStatsQuery->where('showtimes.movie_id', $movieId);
        }

        $rawMovieStats = $movieStatsQuery
            ->selectRaw('showtimes.movie_id, SUM(ticket_details.final_price) as revenue, COUNT(ticket_details.id) as tickets')
            ->groupBy('showtimes.movie_id')
            ->orderByDesc('revenue')
            ->get();

        $movieRevenueStats = [];
        foreach ($rawMovieStats as $row) {
            $movie = $moviesById->get($row->movie_id);
            if ($movie && ($row->revenue > 0 || $row->tickets > 0)) {
                $movieRevenueStats[] = [
                    'movie' => $movie,
                    'revenue' => (float) $row->revenue,
                    'tickets' => (int) $row->tickets,
                ];
            }
        }

        // Dữ liệu cho biểu đồ (30 ngày gần nhất theo paid_at)
        $chartData = (clone $paymentQuery)
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as daily_revenue')
            ->groupBy('date')
            ->orderByDesc('date')
            ->limit(30)
            ->get()
            ->reverse()
            ->values();

        $chartLabels = $chartData->pluck('date')->map(fn($d) => Carbon::parse($d)->format('d/m'))->toArray();
        $chartValues = $chartData->pluck('daily_revenue')->toArray();

        return view('admin.revenue.index', [
            'payments' => $payments,
            'grossRevenue' => $grossRevenue,
            'totalRefundAmount' => $totalRefundAmount,
            'netRevenue' => $netRevenue,
            'totalPaidBookings' => $totalPaidBookings,
            'totalTicketsSold' => $totalTicketsSold,
            'movies' => $movies,
            'movieRevenueStats' => $movieRevenueStats,
            'selectedMovieId' => $movieId,
            'selectedPeriod' => $period,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'chartLabels' => $chartLabels,
            'chartValues' => $chartValues,
        ]);
    }
}
