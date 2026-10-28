@extends('layouts.staff')

@section('title', 'Soát vé #' . $ticket->id)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1 text-light fw-bold">Chi tiết vé #{{ $ticket->id }}</h3>
        <p class="text-muted mb-0">Kiểm tra thông tin vé và cập nhật trạng thái khi khách vào rạp.</p>
    </div>
    <a href="{{ route('staff.tickets') }}" class="btn btn-outline-light">
        &lt; Quay lại tra cứu
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card p-4 border-secondary">
            <h4 class="text-warning mb-3">{{ $ticket->showtime?->movie?->title ?? 'N/A' }}</h4>

            <div class="row g-3">
                <div class="col-md-6">
                    <span class="text-secondary d-block">Phòng chiếu:</span>
                    <strong>{{ $ticket->showtime?->room?->name ?? 'N/A' }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-secondary d-block">Vị trí ghế:</span>
                    <strong class="fs-5 text-info">{{ $ticket->seat?->label }} ({{ $ticket->seat?->type }})</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-secondary d-block">Suất chiếu:</span>
                    <strong>{{ $ticket->showtime ? \Carbon\Carbon::parse($ticket->showtime->start_time)->format('H:i - d/m/Y') : 'N/A' }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-secondary d-block">Giá vé:</span>
                    <strong class="text-warning">{{ number_format($ticket->final_price, 0, ',', '.') }} đ</strong>
                </div>
            </div>

            <hr class="border-secondary my-4">

            <h5 class="text-info mb-3">Thông tin khách hàng & Đơn đặt</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <span class="text-secondary d-block">Tên khách hàng:</span>
                    <strong>{{ $ticket->booking?->user->full_name ?: $ticket->booking?->user->username ?: 'Khách hàng' }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-secondary d-block">Số điện thoại:</span>
                    <strong>{{ $ticket->booking?->user->phone ?? 'Chưa cập nhật' }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-secondary d-block">Mã đơn đặt hàng:</span>
                    <strong>#{{ $ticket->booking_id }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-secondary d-block">Trạng thái đơn:</span>
                    <span class="badge bg-secondary">{{ $ticket->booking?->status ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card p-4 border-secondary text-center">
            <h5 class="text-light mb-3">Cập nhật trạng thái vé</h5>

            <div class="mb-4">
                <span class="text-secondary d-block mb-1">Trạng thái hiện tại:</span>
                @php
                    $status = $ticket->status;
                    $badgeClass = match($status) {
                        'HOLDING' => 'bg-warning text-dark',
                        'BOOKED' => 'bg-success',
                        'USED' => 'bg-info',
                        'CANCELLED' => 'bg-danger',
                        default => 'bg-secondary'
                    };
                @endphp
                <span class="badge {{ $badgeClass }} fs-5 py-2 px-3">
                    {{ $statuses[$status] ?? $status }}
                </span>
            </div>

            <form method="POST" action="{{ route('staff.tickets.updateStatus', $ticket) }}">
                @csrf
                <div class="mb-3 text-start">
                    <label class="form-label text-secondary small">Chọn trạng thái mới:</label>
                    <select name="status" class="form-select">
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ $ticket->status === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2">
                    Lưu trạng thái
                </button>
            </form>

            @if($ticket->status === 'BOOKED')
                <div class="alert alert-success mt-3 small mb-0">
                    ✅ Vé đã thanh toán hợp lệ. Nhân viên bấm <strong>"Đã sử dụng"</strong> khi khách vào cửa.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
