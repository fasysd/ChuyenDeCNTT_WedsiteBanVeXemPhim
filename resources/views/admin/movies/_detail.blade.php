<div class="row g-3 text-white">
    <div class="col-md-4">
        <div class="card p-3 bg-dark text-white border-secondary">
            <img src="{{ $movie->image_path ?: asset('images/movieavatar.webp') }}" alt="{{ $movie->title }}" class="img-fluid rounded-3 w-100" onerror="this.onerror=null;this.src='{{ asset('images/movieavatar.webp') }}';">
        </div>
    </div>
    <div class="col-md-8">
        <div class="card p-4 bg-dark text-white border-secondary">
            <h4 class="mb-2 text-warning fw-bold">{{ $movie->title }}</h4>
            <p class="text-white-50 mb-2">Thể loại: <strong class="text-white">{{ $movie->genre ?? 'Chưa rõ' }}</strong></p>
            <p class="text-white-50 mb-2">Ngày phát hành: <strong class="text-white">{{ $movie->release_date ? $movie->release_date->format('d/m/Y') : 'Chưa rõ' }}</strong></p>
            <p class="text-white-50 mb-2">Thời lượng: <strong class="text-white">{{ $movie->duration ? $movie->duration . ' phút' : 'Chưa rõ' }}</strong></p>
            <p class="text-white-50 mb-1">Mô tả:</p>
            <p class="text-white mb-4">{{ $movie->description ?? 'Không có mô tả.' }}</p>

            <div class="mt-2">
                <h6 class="text-info mb-2">Lịch chiếu của phim:</h6>
                @if($movie->showtimes && $movie->showtimes->count())
                    <ul class="list-group list-group-flush rounded">
                        @foreach($movie->showtimes as $showtime)
                            <li class="list-group-item bg-dark border-secondary text-white p-2">
                                <span class="fw-bold">{{ $showtime->start_time ? $showtime->start_time->format('H:i - d/m/Y') : '' }}</span>
                                <div class="small text-white-50">Phòng #{{ $showtime->room_id }} | Giá chuẩn: {{ number_format($showtime->price_standard, 0, ',', '.') }} đ</div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-white-50 small">Hiện chưa có lịch chiếu nào cho phim này.</div>
                @endif
            </div>
        </div>
    </div>
</div>
