<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Showtime;
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
        if ($request->boolean('reset')) {
            $request->session()->forget('staff_tickets_filters');
            return redirect()->route('staff.tickets');
        }

        $filterKeys = ['q', 'status', 'movie_id', 'showtime_id', 'show_date'];
        $hasFilterInput = collect($filterKeys)->contains(fn ($key) => $request->query->has($key));

        $defaultFilters = [
            'q' => '',
            'status' => 'BOOKED',
            'movie_id' => '',
            'showtime_id' => '',
            'show_date' => now()->toDateString(),
        ];

        if ($hasFilterInput) {
            $filters = [
                'q' => trim((string) $request->query('q', '')),
                'status' => trim((string) $request->query('status', '')),
                'movie_id' => trim((string) $request->query('movie_id', '')),
                'showtime_id' => trim((string) $request->query('showtime_id', '')),
                'show_date' => trim((string) $request->query('show_date', '')),
            ];
            $request->session()->put('staff_tickets_filters', $filters);
        } else {
            $filters = $request->session()->get('staff_tickets_filters', $defaultFilters);
        }

        $query = $filters['q'] ?? '';
        $statusFilter = $filters['status'] ?? 'BOOKED';
        $movieIdFilter = $filters['movie_id'] ?? '';
        $showtimeIdFilter = $filters['showtime_id'] ?? '';
        $showDateFilter = $filters['show_date'] ?? now()->toDateString();
        $hasActiveFilters = $query !== ''
            || $statusFilter !== $defaultFilters['status']
            || $movieIdFilter !== ''
            || $showtimeIdFilter !== ''
            || $showDateFilter !== $defaultFilters['show_date'];

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

        $ticketsQuery = TicketDetail::with(['showtime.movie', 'seat', 'booking.user', 'showtime.room']);

        if ($query !== '') {
            $ticketsQuery->where(function ($builder) use ($query) {
                if (ctype_digit($query)) {
                    $builder->where('id', (int) $query)
                        ->orWhere('booking_id', (int) $query);
                }
                $builder->orWhereHas('booking.user', function ($userQuery) use ($query) {
                    $userQuery->where('full_name', 'like', "%{$query}%")
                        ->orWhere('username', 'like', "%{$query}%")
                        ->orWhere('phone', 'like', "%{$query}%");
                });
            });
        }

        if ($statusFilter !== '') {
            $ticketsQuery->where('status', $statusFilter);
        }

        if ($movieIdFilter !== '' && ctype_digit($movieIdFilter)) {
            $ticketsQuery->whereHas('showtime', function ($showtimeQuery) use ($movieIdFilter) {
                $showtimeQuery->where('movie_id', (int) $movieIdFilter);
            });
        }

        if ($showtimeIdFilter !== '' && ctype_digit($showtimeIdFilter)) {
            $ticketsQuery->where('showtime_id', (int) $showtimeIdFilter);
        }

        if ($showDateFilter !== '') {
            $ticketsQuery->whereHas('showtime', function ($showtimeQuery) use ($showDateFilter) {
                $showtimeQuery->whereDate('start_time', $showDateFilter);
            });
        }

        $tickets = $ticketsQuery->latest('id')->limit(30)->get();
        $movies = Movie::orderBy('title')->get(['id', 'title']);

        $showtimes = collect();
        if ($movieIdFilter !== '' && ctype_digit($movieIdFilter)) {
            $showtimesQuery = Showtime::with('room:id,name')
                ->where('movie_id', (int) $movieIdFilter)
                ->orderBy('start_time');

            if ($showDateFilter !== '') {
                $showtimesQuery->whereDate('start_time', $showDateFilter);
            }

            $showtimes = $showtimesQuery->get(['id', 'movie_id', 'room_id', 'start_time']);
        }

        $statuses = [
            'HOLDING' => 'Đang giữ chỗ',
            'BOOKED' => 'Đã thanh toán',
            'USED' => 'Đã sử dụng',
            'CANCELLED' => 'Đã hủy',
        ];

        return view('staff.tickets', compact(
            'tickets',
            'ticket',
            'matchedTickets',
            'query',
            'statusFilter',
            'movieIdFilter',
            'showtimeIdFilter',
            'showDateFilter',
            'hasActiveFilters',
            'movies',
            'showtimes',
            'statuses'
        ));
    }

    /**
     * Lấy danh sách suất chiếu theo phim (và ngày chiếu nếu có) cho bộ lọc
     */
    public function showtimes(Request $request)
    {
        $movieId = $request->query('movie_id');
        if (!$movieId || !ctype_digit((string) $movieId)) {
            return response()->json([]);
        }

        $query = Showtime::with('room:id,name')
            ->where('movie_id', (int) $movieId)
            ->orderBy('start_time');

        $showDate = trim((string) $request->query('show_date', ''));
        if ($showDate !== '') {
            $query->whereDate('start_time', $showDate);
        }

        $items = $query->get(['id', 'room_id', 'start_time'])->map(function ($st) {
            return [
                'id' => $st->id,
                'label' => Carbon::parse($st->start_time)->format('H:i d/m/Y') . ' - ' . ($st->room?->name ?? 'N/A'),
            ];
        });

        return response()->json($items);
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
