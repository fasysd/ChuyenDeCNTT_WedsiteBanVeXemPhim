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
            $startDate = Carbon::parse($fromDate)->startOfDay();
            $endDate = Carbon::parse($toDate)->endOfDay();
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

        // 5. Tổng số vé đã bán thành công
        $ticketQuery = TicketDetail::whereIn('status', ['BOOKED', 'USED']);
        if ($startDate && $endDate) {
            $ticketQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        if ($movieId) {
            $ticketQuery->whereHas('showtime', function ($q) use ($movieId) {
                $q->where('movie_id', $movieId);
            });
        }
        $totalTicketsSold = $ticketQuery->count();

        // Thống kê doanh thu theo từng phim (Top phim có doanh thu cao nhất)
        $movies = Movie::all();
        $movieRevenueStats = [];
        foreach ($movies as $movie) {
            $movieRev = TicketDetail::whereHas('showtime', function ($q) use ($movie) {
                $q->where('movie_id', $movie->id);
            })->whereIn('status', ['BOOKED', 'USED'])->sum('final_price');

            $ticketsCount = TicketDetail::whereHas('showtime', function ($q) use ($movie) {
                $q->where('movie_id', $movie->id);
            })->whereIn('status', ['BOOKED', 'USED'])->count();

            if ($movieRev > 0 || $ticketsCount > 0) {
                $movieRevenueStats[] = [
                    'movie' => $movie,
                    'revenue' => $movieRev,
                    'tickets' => $ticketsCount,
                ];
            }
        }
        usort($movieRevenueStats, fn($a, $b) => $b['revenue'] <=> $a['revenue']);

        // Dữ liệu cho biểu đồ (Chart Data theo ngày)
        $chartData = (clone $paymentQuery)
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as daily_revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->limit(30)
            ->get();

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
