<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MovieController extends Controller
{
    /**
     * Trang chủ hiển thị banner và các danh mục phim
     */
    public function home()
    {
        if (Auth::check() && Auth::user()->role === 'ADMIN') {
            return redirect()->route('admin.dashboard');
        }

        $allMovies = Movie::orderBy('release_date', 'desc')->get();

        $now = now()->toDateString();
        $nowShowing = $allMovies->filter(function ($m) use ($now) {
            return $m->release_date && $m->release_date->format('Y-m-d') <= $now;
        });

        $upcoming = $allMovies->filter(function ($m) use ($now) {
            return $m->release_date && $m->release_date->format('Y-m-d') > $now;
        });

        // Nếu dữ liệu mẫu chưa có phim sắp chiếu hoặc đang chiếu, dùng toàn bộ phim để đảm bảo giao diện đẹp
        if ($nowShowing->isEmpty()) {
            $nowShowing = $allMovies;
        }
        if ($upcoming->isEmpty()) {
            $upcoming = $allMovies;
        }

        // 1. Lấy thống kê doanh thu từ đầu tháng trước đến hiện tại
        $fromLastMonth = now()->subMonth()->startOfMonth();

        $topRevenueStats = \App\Models\TicketDetail::selectRaw('showtimes.movie_id, SUM(ticket_details.final_price) as total_revenue, COUNT(ticket_details.id) as tickets_sold')
            ->join('showtimes', 'ticket_details.showtime_id', '=', 'showtimes.id')
            ->whereIn('ticket_details.status', ['BOOKED', 'USED'])
            ->where('ticket_details.created_at', '>=', $fromLastMonth)
            ->groupBy('showtimes.movie_id')
            ->havingRaw('total_revenue > 0')
            ->orderByDesc('total_revenue')
            ->orderByDesc('tickets_sold')
            ->take(5)
            ->get()
            ->keyBy('movie_id');

        $topMovieIds = $topRevenueStats->keys()->all();
        $topMovies = Movie::whereIn('id', $topMovieIds)->get();

        // Giữ đúng thứ tự sắp xếp theo doanh thu cao nhất
        $blockbusterMovies = collect($topMovieIds)->map(function ($id) use ($topMovies, $topRevenueStats) {
            $movie = $topMovies->firstWhere('id', $id);
            if ($movie) {
                $movie->total_revenue = (float) $topRevenueStats[$id]->total_revenue;
                $movie->tickets_sold = (int) $topRevenueStats[$id]->tickets_sold;
            }
            return $movie;
        })->filter();

        // 2. Nếu số lượng phim có doanh thu < 5, lấy bù các phim mới nhất vào danh sách banner
        if ($blockbusterMovies->count() < 5) {
            $needed = 5 - $blockbusterMovies->count();
            $excludeIds = $blockbusterMovies->pluck('id')->all();

            $fillers = Movie::whereNotIn('id', $excludeIds)
                ->orderByDesc('release_date')
                ->orderByDesc('id')
                ->take($needed)
                ->get();

            $blockbusterMovies = $blockbusterMovies->concat($fillers);
        }

        return view('user.index', [
            'movies' => $allMovies,
            'nowShowing' => $nowShowing,
            'upcoming' => $upcoming,
            'blockbusterMovies' => $blockbusterMovies,
        ]);
    }

    /**
     * Alias backward compatibility cho route cũ
     */
    public function index()
    {
        return $this->home();
    }

    /**
     * Trang Kho phim: Tìm kiếm, lọc và sắp xếp toàn bộ phim
     */
    public function movies(Request $request)
    {
        $query = Movie::query();

        // 1. Tìm kiếm theo tên phim
        if ($request->filled('q')) {
            $keyword = trim($request->query('q'));
            $query->where('title', 'like', "%{$keyword}%");
        }

        // 2. Lọc theo thể loại
        if ($request->filled('genre')) {
            $genre = trim($request->query('genre'));
            $query->where('genre', 'like', "%{$genre}%");
        }

        // 3. Lọc theo trạng thái phim (Đang chiếu / Sắp chiếu)
        $now = now()->toDateString();
        if ($request->query('status') === 'now_showing') {
            $query->where('release_date', '<=', $now);
        } elseif ($request->query('status') === 'upcoming') {
            $query->where('release_date', '>', $now);
        }

        // 4. Sắp xếp
        $sort = $request->query('sort', 'latest');
        match ($sort) {
            'oldest' => $query->orderBy('release_date', 'asc'),
            'title_asc' => $query->orderBy('title', 'asc'),
            'title_desc' => $query->orderBy('title', 'desc'),
            'duration_desc' => $query->orderBy('duration', 'desc'),
            default => $query->orderBy('release_date', 'desc'),
        };

        $movies = $query->get();

        // Lấy danh sách thể loại khả dụng từ CSDL
        $availableGenres = Movie::pluck('genre')
            ->filter()
            ->flatMap(fn($g) => array_map('trim', explode(',', $g)))
            ->unique()
            ->sort()
            ->values();

        return view('movie.index', compact('movies', 'availableGenres'));
    }

    /**
     * Chi tiết một bộ phim
     */
    public function show(string $id)
    {
        $movie = Movie::with(['showtimes' => function ($q) {
            $q->orderBy('start_time');
        }, 'showtimes.room'])->findOrFail($id);

        $suggestedMovies = Movie::where('id', '!=', $movie->id)
            ->latest('id')
            ->get();

        return view('movie.show', compact('movie', 'suggestedMovies'));
    }
}
