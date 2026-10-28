@extends('layouts.app')

@section('content')
<div class="container-fluid movie-index-page p-0 d-flex flex-column justify-content-start align-items-center">

    {{-- Header --}}
    <div class="container-fluid text-center p-3 text-white" style="background-color: #00c16a;">
        <h3 class="mb-0 fw-bold">ĐẶT VÉ XEM PHIM TRỰC TUYẾN</h3>
    </div>

    @if(session('error'))
        <div class="container mt-3">
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    {{-- Chọn suất + sơ đồ ghế --}}
    <div class="container-fluid px-4 mt-4">
        <div class="row mb-5">

            {{-- Cột trái: Suất chiếu --}}
            <div class="col-lg-3 col-md-12 mb-4 border-end border-secondary">
                <div class="showtime-wrapper p-3">
                    <h4 class="text-white mb-3 fw-bold">
                        Chọn suất chiếu
                    </h4>

                    @php
                        $groupedShowtimes = $showtimes->groupBy('room_id');
                    @endphp

                    @foreach($groupedShowtimes as $roomId => $roomShowtimes)
                        <div class="mb-4">
                            <h5 class="text-info">
                                {{ $roomShowtimes->first()->room->name ?? 'Phòng ' . $roomId }}
                            </h5>

                            <div class="d-flex flex-wrap gap-2 mt-2">
                                @foreach($roomShowtimes as $showtime)
                                    <a
                                        href="{{ route('ticket.booking', $movie->id) }}?showtime={{ $showtime->id }}"
                                        class="btn {{ $selectedShowtime->id == $showtime->id ? 'btn-success text-white' : 'btn-outline-light' }}"
                                    >
                                        {{ \Carbon\Carbon::parse($showtime->start_time)->format('H:i') }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Cột phải: Màn hình + ghế --}}
            <div class="col-lg-9 col-md-12">
                <div class="screen-arc d-flex justify-content-center align-items-center flex-column mb-4">
                    <h4 class="text-white mb-2 text-uppercase tracking-wider">
                        MÀN HÌNH
                    </h4>
                    <svg viewBox="0 0 400 40" style="max-width: 450px;">
                        <path d="M20 30 Q200 -10 380 30" fill="none" stroke="#00c16a" stroke-width="4" />
                    </svg>
                </div>

                @include('seat.index', ['seats' => $seats])
            </div>

        </div>
    </div>

    {{-- Thanh thông tin cố định / dưới cùng --}}
    <div class="container-fluid bg-black text-white py-3 border-top border-secondary sticky-bottom">
        <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">

            <div>
                <a href="{{ route('movie.show', $movie->id) }}" class="btn btn-outline-light">
                    &lt; Quay lại
                </a>
            </div>

            <div class="d-flex align-items-center gap-4 flex-wrap">
                <div>
                    <span class="text-secondary d-block">Phim:</span>
                    <strong>{{ $movie->title }}</strong>
                </div>

                <div>
                    <span class="text-secondary d-block">Suất chiếu:</span>
                    <strong>
                        {{ \Carbon\Carbon::parse($selectedShowtime->start_time)->format('H:i - d/m/Y') }}
                    </strong>
                </div>

                <div>
                    <span class="text-secondary d-block">Phòng:</span>
                    <strong>{{ $selectedShowtime->room->name ?? 'Phòng ' . $selectedShowtime->room_id }}</strong>
                </div>

                <div>
                    <span class="text-secondary d-block">Số ghế chọn:</span>
                    <strong id="selectedSeats" class="text-warning">0</strong>
                </div>

                <div>
                    <span class="text-secondary d-block">Tạm tính:</span>
                    <strong id="totalPrice" class="text-warning fs-5">0 đ</strong>
                </div>
            </div>

            <div>
                <form action="{{ route('ticket.purchase', $movie->id) }}" method="POST" id="bookingForm">
                    @csrf
                    <input type="hidden" name="showtime_id" value="{{ $selectedShowtime->id }}">
                    <input type="hidden" name="seat_ids" id="seat_ids">

                    <button
                        type="submit"
                        class="btn btn-danger btn-lg px-4"
                        id="continueBtn"
                        style="display:none;"
                    >
                        Tiếp tục &gt;
                    </button>
                </form>
            </div>

        </div>
    </div>

</div>

<script>
    const form = document.getElementById('bookingForm');
    const continueBtn = document.getElementById('continueBtn');

    function updateContinueButton() {
        if (window.selectedSeatIds && window.selectedSeatIds.length > 0) {
            continueBtn.style.display = 'inline-block';
        } else {
            continueBtn.style.display = 'none';
        }
    }

    form.addEventListener('submit', function (e) {
        if (!window.selectedSeatIds || window.selectedSeatIds.length === 0) {
            e.preventDefault();
            alert('Vui lòng chọn ít nhất một ghế.');
            return;
        }
        document.getElementById('seat_ids').value = window.selectedSeatIds.join(',');
    });

    document.addEventListener('DOMContentLoaded', updateContinueButton);
</script>
@endsection
