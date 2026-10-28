@extends('layouts.admin')

@section('title', 'Trang chủ Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-column flex-md-row gap-3">
    <div>
        <h3 class="mb-1 text-light fw-bold">Tổng quan rạp phim CineGo</h3>
        <p class="text-muted mb-0">Theo dõi nhanh danh sách phim, suất chiếu và hoạt động quản lý rạp.</p>
    </div>
    <div>
        <a href="{{ route('admin.movies.create') }}" class="btn btn-success">
            ➕ Thêm phim mới
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md">
        <div class="card p-3 border-secondary">
            <span class="text-secondary small">Tổng số phim</span>
            <h3 class="text-info fw-bold mb-0 mt-1">{{ $movies->count() }}</h3>
        </div>
    </div>
    <div class="col-md">
        <div class="card p-3 border-secondary">
            <span class="text-secondary small">Phim đang chiếu</span>
            <h3 class="text-success fw-bold mb-0 mt-1">{{ $currentShowings->count() }}</h3>
        </div>
    </div>
    <div class="col-md">
        <div class="card p-3 border-secondary">
            <span class="text-secondary small">Phim sắp chiếu</span>
            <h3 class="text-warning fw-bold mb-0 mt-1">{{ $upcomingMovies->count() }}</h3>
        </div>
    </div>
    <div class="col-md">
        <a href="{{ route('admin.revenue.index') }}" class="text-decoration-none">
            <div class="card p-3 border-success h-100 hover-card">
                <span class="text-success small fw-bold">📈 Quản lý doanh thu</span>
                <div class="text-white small mt-1">Báo cáo & Biểu đồ →</div>
            </div>
        </a>
    </div>
    <div class="col-md">
        <a href="{{ route('admin.refunds.index') }}" class="text-decoration-none">
            <div class="card p-3 border-warning h-100 hover-card">
                <span class="text-warning small fw-bold">💸 Quản lý hoàn tiền</span>
                <div class="text-white small mt-1">Duyệt yêu cầu →</div>
            </div>
        </a>
    </div>
</div>

{{-- Bảng phim --}}
<div class="card p-4 border-secondary mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="text-light mb-0">Phim đang chiếu</h4>
        <a href="{{ route('admin.movies.index') }}" class="btn btn-sm btn-outline-info">Xem tất cả</a>
    </div>

    <div class="table-responsive rounded-3">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Poster</th>
                    <th>Tên phim</th>
                    <th>Thời lượng</th>
                    <th>Thể loại</th>
                    <th>Ngày phát hành</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse($currentShowings as $m)
                    <tr>
                        <td style="width: 80px;">
                            <img src="{{ $m->image_path ? asset($m->image_path) : asset('images/movieavatar.webp') }}" class="movie-poster" alt="{{ $m->title }}" onerror="this.onerror=null;this.src='{{ asset('images/movieavatar.webp') }}';">
                        </td>
                        <td><strong>{{ $m->title }}</strong></td>
                        <td>{{ $m->duration }} phút</td>
                        <td>{{ $m->genre ?? 'N/A' }}</td>
                        <td>{{ $m->release_date ? \Carbon\Carbon::parse($m->release_date)->format('d/m/Y') : 'N/A' }}</td>
                        <td>
                            <a href="{{ route('admin.movies.edit', $m) }}" class="btn btn-sm btn-outline-primary me-1">Sửa</a>
                            <a href="{{ route('admin.movies.show', $m) }}" class="btn btn-sm btn-outline-info">Chi tiết</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-3 text-muted">Hiện chưa có phim nào đang chiếu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
