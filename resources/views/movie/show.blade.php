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

        {{-- Phần Lịch chiếu phim khả dụng --}}
        <div class="card bg-dark text-white p-4 shadow-lg border-secondary mb-5 rounded-4">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary pb-3">
                <div>
                    <h3 class="fw-bold text-info mb-1">Lịch chiếu phim</h3>
                    <p class="text-secondary mb-0 small">Bấm vào khung giờ bạn muốn xem để chọn phòng và ghế ngồi</p>
                </div>
            </div>

            @if($movie->showtimes && $movie->showtimes->isNotEmpty())
                @php
                    $groupedByDate = $movie->showtimes->groupBy(function($item) {
                        return \Carbon\Carbon::parse($item->start_time)->format('Y-m-d');
                    });
                @endphp

                @foreach($groupedByDate as $dateStr => $dayShowtimes)
                    @php
                        $carbonDate = \Carbon\Carbon::parse($dateStr);
                        $dateLabel = $carbonDate->isToday() 
                            ? 'Hôm nay - ' . $carbonDate->format('d/m/Y') 
                            : ($carbonDate->isTomorrow() 
                                ? 'Ngày mai - ' . $carbonDate->format('d/m/Y') 
                                : 'Ngày ' . $carbonDate->format('d/m/Y'));
                    @endphp

                    <div class="mb-4">
                        <div class="text-warning fw-bold fs-5 mb-3">
                            {{ $dateLabel }}
                        </div>

                        <div class="d-flex flex-wrap gap-3">
                            @foreach($dayShowtimes as $st)
                                <a href="{{ route('ticket.booking', $movie->id) }}?showtime={{ $st->id }}"
                                   class="btn btn-outline-success text-white p-3 text-start text-decoration-none shadow-sm hover-showtime rounded-3"
                                   style="min-width: 150px; border-width: 2px;">
                                    <div class="fs-4 fw-bold text-success mb-1">
                                        {{ \Carbon\Carbon::parse($st->start_time)->format('H:i') }}
                                    </div>
                                    <div class="small text-white-50">
                                        {{ $st->room->name ?? 'Phòng ' . $st->room_id }}
                                    </div>
                                    <div class="small text-warning fw-semibold mt-1">
                                        {{ number_format($st->price_standard, 0, ',', '.') }} đ
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center py-5 text-secondary">
                    <p class="mb-0 fs-5">Phim hiện chưa có suất chiếu khả dụng sắp tới.</p>
                    <small>Vui lòng quay lại sau hoặc tham khảo các phim khác bên dưới.</small>
                </div>
            @endif
        </div>

        {{-- Gợi ý phim khác --}}
        @if(isset($suggestedMovies) && $suggestedMovies->isNotEmpty())
            <x-movie-suggestion-layout :movies="$suggestedMovies" title="Có thể bạn cũng thích" />
        @endif
    </div>
</div>
@endsection
