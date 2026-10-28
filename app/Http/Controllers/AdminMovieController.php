<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminMovieController extends Controller
{
    public function home(Request $request)
    {
        $now = now();

        $movies = Movie::with('showtimes')
            ->orderBy('release_date', 'desc')
            ->get();

        $currentShowings = Movie::whereHas('showtimes', function ($query) use ($now) {
            $query->where('start_time', '<=', $now)
                ->where('end_time', '>=', $now);
        })
        ->with('showtimes')
        ->orderBy('release_date', 'desc')
        ->get();

        $upcomingMovies = Movie::where(function ($query) use ($now) {
            $query->where('release_date', '>', $now)
                ->orWhereHas('showtimes', function ($query) use ($now) {
                    $query->where('start_time', '>', $now);
                });
        })
        ->with('showtimes')
        ->orderBy('release_date', 'asc')
        ->get();

        return view('admin.home', compact('movies', 'currentShowings', 'upcomingMovies'));
    }

    public function index(Request $request)
    {
        $movies = Movie::withCount('showtimes')
            ->when($request->query('q'), function ($query, $q) {
                return $query->where('title', 'like', "%{$q}%");
            })
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.movies.index', compact('movies'));
    }

    public function create(Request $request)
    {
        $movie = new Movie();
        $viewData = [
            'movie' => $movie,
            'title' => 'Thêm phim mới',
            'action' => route('admin.movies.store'),
            'method' => 'POST',
            'isEdit' => false,
            'useModalCancel' => (bool) $request->query('modal'),
        ];

        if ($request->query('modal')) {
            return view('admin.movies._form', $viewData);
        }

        return view('admin.movie-form', $viewData);
    }

    public function show(Request $request, Movie $movie)
    {
        $movie->load('showtimes');

        if ($request->query('modal')) {
            return view('admin.movies._detail', compact('movie'));
        }

        return view('admin.movies.show', compact('movie'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'genre' => ['nullable', 'string', 'max:255'],
            'release_date' => ['nullable', 'date'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'duration' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ], [
            'title.required' => 'Vui lòng nhập tên phim.',
            'duration.required' => 'Vui lòng nhập thời lượng phim.',
        ]);

        Movie::create($validated);

        return redirect()
            ->route('admin.movies.index')
            ->with('success', 'Đã thêm phim mới thành công.');
    }

    public function edit(Request $request, Movie $movie)
    {
        $viewData = [
            'movie' => $movie,
            'title' => 'Sửa phim',
            'action' => route('admin.movies.update', $movie),
            'method' => 'PUT',
            'isEdit' => true,
            'useModalCancel' => (bool) $request->query('modal'),
        ];

        if ($request->query('modal')) {
            return view('admin.movies._form', $viewData);
        }

        return view('admin.movie-form', $viewData);
    }

    public function update(Request $request, Movie $movie)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'genre' => ['nullable', 'string', 'max:255'],
            'release_date' => ['nullable', 'date'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'duration' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ], [
            'title.required' => 'Vui lòng nhập tên phim.',
            'duration.required' => 'Vui lòng nhập thời lượng phim.',
        ]);

        $movie->update($validated);

        return redirect()
            ->route('admin.movies.index')
            ->with('success', 'Đã cập nhật thông tin phim thành công.');
    }

    public function destroy(Movie $movie)
    {
        if ($movie->showtimes()->exists()) {
            return redirect()
                ->route('admin.movies.index')
                ->with('error', 'Không thể xóa phim vì phim này đang có suất chiếu liên quan.');
        }

        $movie->delete();

        return redirect()
            ->route('admin.movies.index')
            ->with('success', 'Đã xóa phim thành công.');
    }
}
