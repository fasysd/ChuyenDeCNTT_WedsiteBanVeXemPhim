<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Support\Facades\Auth;

class MovieController extends Controller
{
    /**
     * Trang chủ hiển thị danh sách phim
     */
    public function index()
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

        return view('user.index', [
            'movies' => $allMovies,
            'nowShowing' => $nowShowing,
            'upcoming' => $upcoming,
        ]);
    }

    /**
     * Chi tiết một bộ phim
     */
    public function show(string $id)
    {
        $movie = Movie::with('showtimes')->findOrFail($id);

        $suggestedMovies = Movie::where('id', '!=', $movie->id)
            ->latest('id')
            ->take(5)
            ->get();

        return view('movie.show', compact('movie', 'suggestedMovies'));
    }
}
