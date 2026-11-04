@extends('layouts.app')

@section('content')
<div class="movie-index-page min-vh-100 py-5 text-light">
    <div class="container">

        {{-- Tiêu đề trang Kho phim --}}
        <div class="mb-4">
            <h1 class="fw-bold text-warning display-6 mb-2">Kho phim CineGo</h1>
            <p class="text-secondary mb-0">Tìm kiếm, lọc và khám phá tất cả các bộ phim đang chiếu và sắp chiếu tại cụm rạp CineGo.</p>
        </div>

        {{-- Khung Tìm kiếm & Lọc phim --}}
        <div class="card bg-dark text-white border-secondary shadow-lg p-4 mb-4 rounded-4">
            <form method="GET" action="{{ route('movies') }}">
                <div class="row g-3 align-items-end">
                    {{-- 1. Ô tìm kiếm theo tên phim --}}
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label text-light small fw-bold">Tên phim</label>
                        <input
                            type="text"
                            name="q"
                            class="form-control bg-black text-white border-secondary"
                            placeholder="Nhập tên phim cần tìm..."
                            value="{{ request('q') }}"
                        >
                    </div>

                    {{-- 2. Bộ lọc theo thể loại --}}
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label text-light small fw-bold">Thể loại</label>
                        <select name="genre" class="form-select bg-black text-white border-secondary">
                            <option value="">Tất cả thể loại</option>
                            @foreach($availableGenres as $genre)
                                <option value="{{ $genre }}" {{ request('genre') === $genre ? 'selected' : '' }}>
                                    {{ $genre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 3. Bộ lọc theo trạng thái (Đang chiếu / Sắp chiếu) --}}
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label text-light small fw-bold">Trạng thái</label>
                        <select name="status" class="form-select bg-black text-white border-secondary">
                            <option value="">Tất cả trạng thái</option>
                            <option value="now_showing" {{ request('status') === 'now_showing' ? 'selected' : '' }}>Đang chiếu</option>
                            <option value="upcoming" {{ request('status') === 'upcoming' ? 'selected' : '' }}>Sắp chiếu</option>
                        </select>
                    </div>

                    {{-- 4. Bộ lọc sắp xếp --}}
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label text-light small fw-bold">Sắp xếp theo</label>
                        <select name="sort" class="form-select bg-black text-white border-secondary">
                            <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>Mới nhất</option>
                            <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                            <option value="title_asc" {{ request('sort') === 'title_asc' ? 'selected' : '' }}>Tên A - Z</option>
                            <option value="title_desc" {{ request('sort') === 'title_desc' ? 'selected' : '' }}>Tên Z - A</option>
                            <option value="duration_desc" {{ request('sort') === 'duration_desc' ? 'selected' : '' }}>Thời lượng dài nhất</option>
                        </select>
                    </div>

                    {{-- 5. Nút submit & reset --}}
                    <div class="col-lg-1 col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-success w-100 py-2 fw-semibold" title="Tìm kiếm">
                            Lọc
                        </button>
                    </div>
                </div>

                @if(request()->hasAny(['q', 'genre', 'status', 'sort']))
                    <div class="mt-3 pt-3 border-top border-secondary d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="small text-secondary">
                            Đang lọc:
                            @if(request('q')) <span class="badge bg-secondary me-1">Từ khóa: "{{ request('q') }}"</span> @endif
                            @if(request('genre')) <span class="badge bg-primary me-1">Thể loại: {{ request('genre') }}</span> @endif
                            @if(request('status')) <span class="badge bg-info text-dark me-1">{{ request('status') === 'now_showing' ? 'Đang chiếu' : 'Sắp chiếu' }}</span> @endif
                            @if(request('sort') && request('sort') !== 'latest') <span class="badge bg-warning text-dark me-1">Sắp xếp tùy chọn</span> @endif
                        </div>
                        <a href="{{ route('movies') }}" class="btn btn-sm btn-outline-secondary">
                            Xóa tất cả bộ lọc
                        </a>
                    </div>
                @endif
            </form>
        </div>

        {{-- Thanh thông tin số lượng kết quả --}}
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-secondary">
            <div>
                <span class="text-secondary">Tìm thấy:</span>
                <strong class="text-white fs-5 ms-1">{{ $movies->count() }}</strong>
                <span class="text-secondary">bộ phim</span>
            </div>
            <a href="{{ route('home') }}" class="text-secondary text-decoration-none small">
                &larr; Về trang chủ
            </a>
        </div>

        {{-- Lưới hiển thị danh sách phim --}}
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4">
            @forelse($movies as $movie)
                <div class="col">
                    <x-movie-card
                        :id="$movie->id"
                        :image="$movie->image_path"
                        :title="$movie->title"
                        :release-date="$movie->release_date"
                        :genre="$movie->genre"
                    />
                </div>
            @empty
                <div class="col-12 py-5 text-center">
                    <div class="card bg-dark border-secondary p-5 text-center">
                        <h4 class="text-warning mb-3">Không tìm thấy bộ phim nào phù hợp</h4>
                        <p class="text-secondary mb-4">
                            Hãy thử thay đổi từ khóa tìm kiếm hoặc bỏ bớt các điều kiện lọc để xem thêm phim.
                        </p>
                        <div>
                            <a href="{{ route('movies') }}" class="btn btn-outline-warning px-4 py-2">
                                Xem tất cả các phim
                            </a>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

    </div>
</div>
@endsection
