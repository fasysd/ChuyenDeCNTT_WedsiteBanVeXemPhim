<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        try {
            DB::transaction(function () use ($movie, $validated) {
                $lockedMovie = Movie::where('id', $movie->id)->lockForUpdate()->firstOrFail();

                $newDuration = (int) $validated['duration'];
                $oldDuration = (int) $lockedMovie->duration;
                $newReleaseDate = !empty($validated['release_date']) ? Carbon::parse($validated['release_date'])->toDateString() : null;
                $oldReleaseDate = $lockedMovie->release_date ? Carbon::parse($lockedMovie->release_date)->toDateString() : null;

                $hasTickets = $lockedMovie->showtimes()->whereHas('ticketDetails')->exists();

                if ($hasTickets && ($newDuration !== $oldDuration || $newReleaseDate !== $oldReleaseDate)) {
                    throw new \DomainException('Không thể thay đổi thời lượng hoặc ngày phát hành vì phim đã có vé được đặt.');
                }

                // Kiểm tra khớp với các suất chiếu hiện tại nếu chỉ sửa khi chưa có vé
                $showtimes = $lockedMovie->showtimes()->get();
                foreach ($showtimes as $showtime) {
                    $start = Carbon::parse($showtime->start_time);
                    $end = Carbon::parse($showtime->end_time);

                    if ($start->diffInMinutes($end) < $newDuration) {
                        throw new \DomainException('Thời lượng phim mới lớn hơn thời lượng của một suất chiếu hiện có.');
                    }

                    if ($newReleaseDate && $start->lt(Carbon::parse($newReleaseDate)->startOfDay())) {
                        throw new \DomainException('Ngày phát hành mới không thể sau thời điểm bắt đầu của suất chiếu hiện có.');
                    }
                }

                $lockedMovie->update($validated);
            });
        } catch (\DomainException $e) {
            return back()
                ->withInput()
                ->withErrors(['general' => $e->getMessage()])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.movies.index')
            ->with('success', 'Đã cập nhật thông tin phim thành công.');
    }

    public function destroy(Movie $movie)
    {
        $deleted = DB::transaction(function () use ($movie) {
            $lockedMovie = Movie::where('id', $movie->id)->lockForUpdate()->firstOrFail();

            if ($lockedMovie->showtimes()->exists()) {
                return false;
            }

            $lockedMovie->delete();
            return true;
        });

        if (!$deleted) {
            return redirect()
                ->route('admin.movies.index')
                ->with('error', 'Không thể xóa phim vì phim này đang có suất chiếu liên quan.');
        }

        return redirect()
            ->route('admin.movies.index')
            ->with('success', 'Đã xóa phim thành công.');
    }
}
