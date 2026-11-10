@extends('layouts.app')

@section('content')
<div class="movie-index-page min-vh-100 py-5 text-light">
    <div class="container">
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Nút quay lại --}}
        <div class="mb-4">
            <a href="{{ route('movies') }}" class="btn btn-outline-light btn-sm px-3">
                &larr; Danh sách phim
            </a>
        </div>

        {{-- Card Thông tin chi tiết phim --}}
        <div class="card bg-dark text-white p-4 p-md-5 shadow-lg border-secondary mb-5 rounded-4">
            <div class="row g-4 align-items-center">
                {{-- Cột Poster --}}
                <div class="col-lg-4 col-md-5 text-center">
                    <img
                        src="{{ $movie->image_path ? asset($movie->image_path) : asset('images/movieavatar.webp') }}"
                        alt="{{ $movie->title }}"
                        class="img-fluid rounded-3 shadow-lg w-100"
                        style="max-height: 460px; object-fit: cover;"
                        onerror="this.onerror=null;this.src='{{ asset('images/movieavatar.webp') }}';"
                    >
                </div>

                {{-- Cột Thông tin chi tiết --}}
                <div class="col-lg-8 col-md-7">
                    <h1 class="text-warning fw-bold mb-3 display-6">{{ $movie->title }}</h1>

                    <div class="mb-2 fs-5">
                        <span class="text-secondary">Thời lượng:</span>
                        <strong class="text-white">{{ $movie->duration }} phút</strong>
                    </div>

                    <div class="mb-2 fs-5">
                        <span class="text-secondary">Ngày khởi chiếu:</span>
                        <strong class="text-white">{{ $movie->release_date ? \Carbon\Carbon::parse($movie->release_date)->format('d/m/Y') : 'Đang cập nhật' }}</strong>
                    </div>

                    <div class="mb-3 fs-5">
                        <span class="text-secondary">Thể loại:</span>
                        <strong class="text-white">{{ $movie->genre ?? 'Đang cập nhật' }}</strong>
                    </div>

                    <div class="mb-4 text-light" style="line-height: 1.8;">
                        <span class="text-secondary d-block fw-bold mb-1">Mô tả nội dung:</span>
                        <p class="mb-0 fs-6 text-white-50">
                            {{ $movie->description ?? 'Chưa có mô tả chi tiết cho phim này.' }}
                        </p>
                    </div>

                    <div class="pt-2">
                        <a href="{{ route('ticket.booking', $movie->id) }}" class="btn btn-danger btn-lg px-5 py-3 fw-bold shadow-lg">
                            Đặt vé ngay
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
</div>
@endsection
