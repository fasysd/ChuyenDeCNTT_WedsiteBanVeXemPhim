@extends('layouts.admin')

@section('title', 'Sửa lịch chiếu #' . $showtime->id)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1 text-light fw-bold">Sửa lịch chiếu #{{ $showtime->id }}</h3>
        <p class="text-muted mb-0">Cập nhật thông tin suất chiếu.</p>
    </div>
    <a href="{{ route('admin.showtimes.index') }}" class="btn btn-outline-light">
        &lt; Quay lại danh sách
    </a>
</div>

<div class="card p-4 border-secondary shadow-sm">
    <form action="{{ route('admin.showtimes.update', $showtime->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Chọn phim <span class="text-danger">*</span></label>
                <select class="form-select" name="movie_id" required>
                    @foreach($movies as $movie)
                        <option value="{{ $movie->id }}" {{ old('movie_id', $showtime->movie_id) == $movie->id ? 'selected' : '' }}>
                            {{ $movie->title }} ({{ $movie->duration }} phút)
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Chọn phòng chiếu <span class="text-danger">*</span></label>
                <select class="form-select" name="room_id" required>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}" {{ old('room_id', $showtime->room_id) == $room->id ? 'selected' : '' }}>
                            {{ $room->name }} ({{ $room->total_seats }} ghế)
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Thời gian bắt đầu <span class="text-danger">*</span></label>
                <input
                    type="datetime-local"
                    name="start_time"
                    class="form-control"
                    value="{{ old('start_time', \Carbon\Carbon::parse($showtime->start_time)->format('Y-m-d\TH:i')) }}"
                    required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Thời gian kết thúc <span class="text-danger">*</span></label>
                <input
                    type="datetime-local"
                    name="end_time"
                    class="form-control"
                    value="{{ old('end_time', \Carbon\Carbon::parse($showtime->end_time)->format('Y-m-d\TH:i')) }}"
                    required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Giá vé chuẩn (VNĐ) <span class="text-danger">*</span></label>
                <input
                    type="number"
                    name="price_standard"
                    class="form-control"
                    min="0"
                    step="1000"
                    value="{{ old('price_standard', $showtime->price_standard) }}"
                    required>
            </div>
        </div>

        <div class="mt-4 pt-3 border-top border-secondary d-flex justify-content-end gap-2">
            <a href="{{ route('admin.showtimes.index') }}" class="btn btn-outline-secondary">Hủy bỏ</a>
            <button type="submit" class="btn btn-primary px-4">Cập nhật lịch chiếu</button>
        </div>
    </form>
</div>
@endsection
