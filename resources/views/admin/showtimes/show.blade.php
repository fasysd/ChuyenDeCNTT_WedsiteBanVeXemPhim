@extends('layouts.admin')

@section('title', 'Chi tiết lịch chiếu #' . $showtime->id)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1 text-light fw-bold">Chi tiết lịch chiếu #{{ $showtime->id }}</h3>
        <p class="text-muted mb-0">Xem thông tin suất chiếu và các vé đã đặt.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.showtimes.edit', $showtime->id) }}" class="btn btn-primary">Sửa</a>
        <a href="{{ route('admin.showtimes.index') }}" class="btn btn-outline-light">&lt; Quay lại</a>
    </div>
</div>

<div class="card p-4 border-secondary shadow-sm mb-4">
    <div class="row g-4">
        <div class="col-md-6">
            <h4 class="text-warning mb-3">{{ $showtime->movie?->title ?? 'N/A' }}</h4>
            <p class="mb-2"><strong>Phòng chiếu:</strong> {{ $showtime->room?->name ?? 'N/A' }} (Tổng {{ $showtime->room?->total_seats ?? 0 }} ghế)</p>
            <p class="mb-2"><strong>Bắt đầu:</strong> {{ \Carbon\Carbon::parse($showtime->start_time)->format('H:i - d/m/Y') }}</p>
            <p class="mb-2"><strong>Kết thúc:</strong> {{ \Carbon\Carbon::parse($showtime->end_time)->format('H:i - d/m/Y') }}</p>
            <p class="mb-0"><strong>Giá vé chuẩn:</strong> {{ number_format($showtime->price_standard, 0, ',', '.') }} đ (VIP: {{ number_format(round($showtime->price_standard * 1.2), 0, ',', '.') }} đ)</p>
        </div>
        <div class="col-md-6 border-start border-secondary ps-4">
            <h5 class="text-info mb-3">Thống kê vé</h5>
            <p class="mb-2"><strong>Tổng số vé đã tạo:</strong> {{ $showtime->ticketDetails->count() }}</p>
            <p class="mb-2"><strong>Đã thanh toán (BOOKED):</strong> {{ $showtime->ticketDetails->where('status', 'BOOKED')->count() }}</p>
            <p class="mb-2"><strong>Đang giữ chỗ (HOLDING):</strong> {{ $showtime->ticketDetails->where('status', 'HOLDING')->count() }}</p>
            <p class="mb-0"><strong>Đã sử dụng (USED):</strong> {{ $showtime->ticketDetails->where('status', 'USED')->count() }}</p>
        </div>
    </div>
</div>
@endsection
