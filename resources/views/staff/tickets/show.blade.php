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
                    <span class="text-light-emphasis d-block small">Phòng chiếu:</span>
                    <strong class="text-white">{{ $ticket->showtime?->room?->name ?? 'N/A' }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-light-emphasis d-block small">Vị trí ghế:</span>
                    <strong class="fs-5 text-info">{{ $ticket->seat?->label }} ({{ $ticket->seat?->type }})</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-light-emphasis d-block small">Suất chiếu:</span>
                    <strong class="text-white">{{ $ticket->showtime ? \Carbon\Carbon::parse($ticket->showtime->start_time)->format('H:i - d/m/Y') : 'N/A' }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-light-emphasis d-block small">Giá vé:</span>
                    <strong class="text-warning">{{ number_format($ticket->final_price, 0, ',', '.') }} đ</strong>
                </div>
            </div>

            <hr class="border-secondary my-4">

            <h5 class="text-info mb-3">Thông tin khách hàng & Đơn đặt</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <span class="text-light-emphasis d-block small">Tên khách hàng:</span>
                    <strong class="text-white">{{ $ticket->booking?->user->full_name ?: $ticket->booking?->user->username ?: 'Khách hàng' }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-light-emphasis d-block small">Số điện thoại:</span>
                    <strong class="text-white">{{ $ticket->booking?->user->phone ?? 'Chưa cập nhật' }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-light-emphasis d-block small">Mã đơn đặt hàng:</span>
                    <strong class="text-white">#{{ $ticket->booking_id }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-light-emphasis d-block small">Trạng thái đơn:</span>
                    <span class="badge bg-secondary">{{ $ticket->booking?->status ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card p-4 border-secondary text-center">
            <h5 class="text-light mb-3">Cập nhật trạng thái vé</h5>

            <div class="mb-4">
                <span class="text-light-emphasis d-block mb-1 small">Trạng thái hiện tại:</span>
                @php
                    $status = $ticket->status;
                    $badgeClass = match($status) {
                        'HOLDING' => 'bg-warning text-dark',
                        'BOOKED' => 'bg-success',
                        'USED' => 'bg-info',
                        'CANCELLED' => 'bg-danger',
                        default => 'bg-secondary'
                    };
                    $statusText = match($status) {
                        'HOLDING' => 'Đang giữ chỗ',
                        'BOOKED' => 'Đã thanh toán',
                        'USED' => 'Đã sử dụng',
                        'CANCELLED' => 'Đã hủy',
                        default => $status
                    };
                @endphp
                <span class="badge {{ $badgeClass }} fs-5 py-2 px-3">
                    {{ $statusText }}
                </span>
            </div>

            @if(!empty($availableStatuses))
                <form method="POST" action="{{ route('staff.tickets.updateStatus', $ticket) }}">
                    @csrf
                    <div class="mb-3 text-start">
                        <label class="form-label text-light small">Chuyển sang trạng thái:</label>
                        <select name="status" class="form-select" required>
                            @foreach($availableStatuses as $key => $label)
                                <option value="{{ $key }}">
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">
                            Vé đã thanh toán chỉ được phép chuyển sang "Đã sử dụng" hoặc "Đã hủy".
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        Lưu trạng thái
                    </button>
                </form>

                <div class="alert alert-success mt-3 small mb-0 text-start">
                    Vé đã thanh toán hợp lệ. Nhân viên chọn <strong>"Đã sử dụng"</strong> khi khách qua cửa soát vé.
                </div>
            @else
                <div class="alert alert-secondary mt-3 small mb-0 text-start">
                    Vé hiện ở trạng thái <strong>{{ $statusText }}</strong>. Hệ thống không cho phép thay đổi trạng thái này thêm nữa.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
