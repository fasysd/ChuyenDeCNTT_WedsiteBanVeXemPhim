@extends('layouts.app')

@section('content')
<div class="container-fluid movie-index-page text-light p-0">

    {{-- Banner quảng cáo điện ảnh: Top Phim Bom Tấn theo Doanh Thu Thực Tế --}}
    <section class="banner-section">
        <div id="homeBanner" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4500">
            {{-- Indicators --}}
            <div class="carousel-indicators" style="z-index: 5;">
                @foreach($blockbusterMovies as $index => $movie)
                    <button type="button" data-bs-target="#homeBanner" data-bs-slide-to="{{ $index }}" class="{{ $index === 0 ? 'active' : '' }}" aria-label="Slide {{ $index + 1 }}"></button>
                @endforeach
            </div>

            {{-- Carousel Slides --}}
            <div class="carousel-inner">
                @foreach($blockbusterMovies as $index => $movie)
                    @php
                        $isUpcoming = $movie->release_date && $movie->release_date->format('Y-m-d') > now()->toDateString();
                        $posterSrc = $movie->image_path ? asset($movie->image_path) : asset('images/movieavatar.webp');
                    @endphp
                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                        <div class="banner-horizontal-wrapper">
                            <div class="container h-100 position-relative">
                                <div class="row h-100 g-0 align-items-center">
                                    {{-- Cột thông tin phim bên trái --}}
                                    <div class="col-lg-7 col-md-7 banner-info-col py-4 pe-md-4">
                                        <div class="mb-3">
                                            @if($isUpcoming)
                                                <span class="badge bg-warning text-dark px-3 py-2 fs-6 fw-bold text-uppercase">
                                                    SẮP CHIẾU
                                                </span>
                                            @else
                                                <span class="badge bg-danger text-white px-3 py-2 fs-6 fw-bold text-uppercase">
                                                    BOM TẤN PHÒNG VÉ
                                                </span>
                                            @endif
                                            @if($movie->duration)
                                                <span class="badge bg-dark border border-secondary text-light px-3 py-2 fs-6 ms-2">
                                                    {{ $movie->duration }} phút
                                                </span>
                                            @endif
                                        </div>

                                        <h2 class="display-5 fw-bold text-white mb-2 banner-movie-title">
                                            {{ $movie->title }}
                                        </h2>

                                        @if($movie->genre)
                                            <div class="text-warning fw-semibold mb-2 fs-6">
                                                Thể loại: <span class="text-white">{{ $movie->genre }}</span>
                                            </div>
                                        @endif

                                        <p class="fs-6 text-light mb-4 banner-movie-desc">
                                            {{ \Illuminate\Support\Str::limit($movie->description ?? 'Trải nghiệm siêu phẩm điện ảnh với công nghệ hình ảnh sắc nét và âm thanh sống động tại CineGo.', 180) }}
                                        </p>

                                        <div>
                                            <a href="{{ route('movie.show', $movie->id) }}" class="btn btn-danger btn-lg px-4 py-2 fw-bold shadow">
                                                {{ $isUpcoming ? 'Xem chi tiết phim' : 'Đặt vé ngay' }}
                                            </a>
                                        </div>
                                    </div>

                                    {{-- Cột hiển thị toàn bộ poster bên phải --}}
                                    <div class="col-lg-5 col-md-5 banner-poster-col text-end position-relative h-100 d-flex justify-content-end align-items-center">
                                        <div class="banner-poster-container">
                                            <img
                                                src="{{ $posterSrc }}"
                                                class="banner-poster-img"
                                                alt="{{ $movie->title }}"
                                                onerror="this.onerror=null;this.src='{{ asset('images/movieavatar.webp') }}';"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Dải chuyển màu mờ dần ở giữa (Gradient Transition) --}}
                            <div class="banner-fade-overlay"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Nút chuyển slide --}}
            <button class="carousel-control-prev banner-nav" type="button" data-bs-target="#homeBanner" data-bs-slide="prev" style="z-index: 5; width: 5%;">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next banner-nav" type="button" data-bs-target="#homeBanner" data-bs-slide="next" style="z-index: 5; width: 5%;">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>
    </section>

    {{-- Các danh mục phim --}}
    <div class="container py-5">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <x-movie-suggestion-layout :movies="$nowShowing" title="Phim đang chiếu tại CineGo" />
        <x-movie-suggestion-layout :movies="$movies" title="Gợi ý cho bạn" />
        <x-movie-suggestion-layout :movies="$upcoming" title="Phim sắp chiếu" />
    </div>

</div>
@endsection
