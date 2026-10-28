@extends('layouts.admin')

@section('title', 'Thêm lịch chiếu mới')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1 text-light fw-bold">Thêm lịch chiếu mới</h3>
        <p class="text-muted mb-0">Thiết lập thời gian chiếu và phòng chiếu cho phim.</p>
    </div>
    <a href="{{ route('admin.showtimes.index') }}" class="btn btn-outline-light">
        &lt; Quay lại danh sách
    </a>
</div>

<div class="card p-4 border-secondary shadow-sm">
    <form action="{{ route('admin.showtimes.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Chọn phim <span class="text-danger">*</span></label>
                <select class="form-select" id="movieSelect" name="movie_id" required>
                    <option value="">-- Chọn phim --</option>
                    @foreach($movies as $movie)
                        <option
                            value="{{ $movie->id }}"
                            data-duration="{{ $movie->duration }}"
                            data-genre="{{ $movie->genre }}"
                            data-release="{{ $movie->release_date ? $movie->release_date->format('d/m/Y') : '' }}"
                            {{ old('movie_id') == $movie->id ? 'selected' : '' }}
                        >
                            {{ $movie->title }} ({{ $movie->duration }} phút)
                        </option>
                    @endforeach
                </select>
                <div id="movieInfo" class="mt-2 small text-info"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Chọn phòng chiếu <span class="text-danger">*</span></label>
                <select class="form-select" id="roomSelect" name="room_id" required>
                    <option value="">-- Chọn phòng --</option>
                    @foreach($rooms as $room)
                        <option
                            value="{{ $room->id }}"
                            data-seats="{{ $room->total_seats }}"
                            data-vip-seats="{{ $room->getVipSeatCount() }}"
                            {{ old('room_id') == $room->id ? 'selected' : '' }}
                        >
                            {{ $room->name }} ({{ $room->total_seats }} ghế)
                        </option>
                    @endforeach
                </select>
                <div id="roomInfo" class="mt-2 small text-info"></div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Thời gian bắt đầu <span class="text-danger">*</span></label>
                <input
                    type="datetime-local"
                    id="startTime"
                    name="start_time"
                    class="form-control"
                    value="{{ old('start_time') }}"
                    required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Thời gian kết thúc (tự tính) <span class="text-danger">*</span></label>
                <input
                    type="datetime-local"
                    id="endTime"
                    name="end_time"
                    class="form-control"
                    value="{{ old('end_time') }}"
                    required>
                <small class="text-muted">Tự động cộng thêm thời lượng phim</small>
            </div>

            <div class="col-md-4">
                <label class="form-label">Giá vé chuẩn (VNĐ) <span class="text-danger">*</span></label>
                <input
                    type="number"
                    name="price_standard"
                    class="form-control"
                    min="0"
                    step="1000"
                    value="{{ old('price_standard', 100000) }}"
                    required>
                <small class="text-muted">Ghế VIP sẽ được tính 120%</small>
            </div>
        </div>

        <div class="mt-4 pt-3 border-top border-secondary d-flex justify-content-end gap-2">
            <a href="{{ route('admin.showtimes.index') }}" class="btn btn-outline-secondary">Hủy bỏ</a>
            <button type="submit" class="btn btn-primary px-4">Lưu lịch chiếu</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    const movieSelect = document.getElementById('movieSelect');
    const roomSelect = document.getElementById('roomSelect');
    const startTimeInput = document.getElementById('startTime');
    const endTimeInput = document.getElementById('endTime');

    function calculateEndTime() {
        const option = movieSelect.options[movieSelect.selectedIndex];
        if (!option || !option.value || !startTimeInput.value) return;

        const duration = parseInt(option.dataset.duration, 10);
        if (!duration) return;

        const startTime = new Date(startTimeInput.value);
        startTime.setMinutes(startTime.getMinutes() + duration);

        const pad = n => n.toString().padStart(2, '0');
        const formatted = `${startTime.getFullYear()}-${pad(startTime.getMonth() + 1)}-${pad(startTime.getDate())}T${pad(startTime.getHours())}:${pad(startTime.getMinutes())}`;
        endTimeInput.value = formatted;
    }

    movieSelect.addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        if (!this.value) {
            document.getElementById('movieInfo').textContent = '';
            return;
        }
        document.getElementById('movieInfo').textContent =
            `Thể loại: ${option.dataset.genre || 'N/A'} | Khởi chiếu: ${option.dataset.release || 'N/A'}`;
        calculateEndTime();
    });

    roomSelect.addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        if (!this.value) {
            document.getElementById('roomInfo').textContent = '';
            return;
        }
        document.getElementById('roomInfo').textContent =
            `Tổng: ${option.dataset.seats} ghế (${option.dataset.vipSeats} ghế VIP)`;
    });

    startTimeInput.addEventListener('change', calculateEndTime);
</script>
@endpush
@endsection
