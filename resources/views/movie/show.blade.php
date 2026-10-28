@extends('layouts.app')

@section('content')
<div class="container py-5">
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="movie-detail-container card bg-dark text-white p-4 shadow-lg border-secondary mb-5">
        <div class="row g-4 align-items-center">
            <div class="col-md-4 text-center">
                <img
                    src="{{ $movie->image_path ? asset($movie->image_path) : asset('images/movieavatar.webp') }}"
                    alt="{{ $movie->title }}"
                    class="img-fluid rounded shadow"
                    style="max-height: 450px; object-fit: cover;"
                    onerror="this.onerror=null;this.src='{{ asset('images/movieavatar.webp') }}';"
                >
            </div>

            <div class="col-md-8">
                <h1 class="text-warning fw-bold mb-3">{{ $movie->title }}</h1>

                <div class="info-item mb-2 fs-5">
                    <span class="text-secondary">Thời lượng:</span>
                    <strong>{{ $movie->duration }} phút</strong>
                </div>

                <div class="info-item mb-2 fs-5">
                    <span class="text-secondary">Ngày khởi chiếu:</span>
                    <strong>{{ $movie->release_date ? \Carbon\Carbon::parse($movie->release_date)->format('d/m/Y') : 'Đang cập nhật' }}</strong>
                </div>

                <div class="info-item mb-3 fs-5">
                    <span class="text-secondary">Thể loại:</span>
                    <strong>{{ $movie->genre ?? 'Đang cập nhật' }}</strong>
                </div>

                <div class="info-item text-light mb-4" style="line-height: 1.8;">
                    {{ $movie->description ?? 'Chưa có mô tả chi tiết cho phim này.' }}
                </div>

                <div class="mt-4">
                    <a href="{{ route('ticket.booking', $movie->id) }}" class="btn btn-danger btn-lg px-5 py-3 fw-bold">
                        🎟️ Đặt vé ngay
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Gợi ý phim khác --}}
    @if(isset($suggestedMovies) && $suggestedMovies->isNotEmpty())
        <x-movie-suggestion-layout :movies="$suggestedMovies" title="Có thể bạn cũng thích" />
    @endif
</div>
@endsection
