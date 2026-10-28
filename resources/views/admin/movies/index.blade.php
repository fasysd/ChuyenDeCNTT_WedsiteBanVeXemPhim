@extends('layouts.admin')

@section('title', 'Quản lý phim')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-column flex-md-row gap-3">
    <div>
        <h3 class="mb-1 text-light fw-bold">Danh sách phim</h3>
        <p class="text-muted mb-0">Quản lý các bộ phim được chiếu tại các rạp CineGo.</p>
    </div>
    <div>
        <a href="{{ route('admin.movies.create') }}" class="btn btn-success">
            ➕ Thêm phim mới
        </a>
    </div>
</div>

<div class="card p-3 border-secondary mb-4">
    <form method="GET" action="{{ route('admin.movies.index') }}">
        <div class="input-group">
            <input type="text" name="q" class="form-control" placeholder="Tìm kiếm theo tên phim..." value="{{ request('q') }}">
            <button class="btn btn-primary" type="submit">Tìm kiếm</button>
            @if(request('q'))
                <a href="{{ route('admin.movies.index') }}" class="btn btn-outline-secondary">Xóa tìm kiếm</a>
            @endif
        </div>
    </form>
</div>

<div class="table-responsive rounded-3 shadow-sm">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Poster</th>
                <th>Tên phim</th>
                <th>Thể loại</th>
                <th>Thời lượng</th>
                <th>Ngày khởi chiếu</th>
                <th>Số suất chiếu</th>
                <th class="text-end">Hành động</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movies as $movie)
                <tr>
                    <td style="width: 80px;">
                        <img src="{{ $movie->image_path ? asset($movie->image_path) : asset('images/movieavatar.webp') }}" class="movie-poster" alt="{{ $movie->title }}" onerror="this.onerror=null;this.src='{{ asset('images/movieavatar.webp') }}';">
                    </td>
                    <td>
                        <strong class="fs-6">{{ $movie->title }}</strong>
                    </td>
                    <td>{{ $movie->genre ?? 'Chưa rõ' }}</td>
                    <td>{{ $movie->duration }} phút</td>
                    <td>{{ $movie->release_date ? \Carbon\Carbon::parse($movie->release_date)->format('d/m/Y') : 'N/A' }}</td>
                    <td>
                        <span class="badge bg-secondary">{{ $movie->showtimes_count ?? 0 }}</span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.movies.edit', $movie) }}" class="btn btn-sm btn-outline-primary me-1">Sửa</a>
                        <form action="{{ route('admin.movies.destroy', $movie) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa phim này?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">Không tìm thấy bộ phim nào.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
