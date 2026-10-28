@extends('layouts.app')

@section('content')
<div class="account-page">
    <div class="container py-5">
        <div class="row justify-content-center">

            {{-- Sidebar --}}
            <div class="col-lg-3 col-md-4 mb-4">
                <h3 class="account-title">
                    TÀI KHOẢN CineGo
                </h3>

                <div class="list-group">
                    <a href="{{ route('user.account.general') }}" class="list-group-item account-menu">
                        Thông tin tài khoản
                    </a>
                    <a href="{{ route('user.account.tickets') }}" class="list-group-item account-menu active">
                        Vé của tôi
                    </a>
                </div>
            </div>

            {{-- Content --}}
            <div class="col-lg-9 col-md-8">
                <div class="account-content">
                    <div class="account-header d-flex justify-content-between align-items-center">
                        <span>ĐƠN ĐẶT VÉ & VÉ CỦA TÔI</span>
                        <a href="{{ route('movies') }}" class="btn btn-sm btn-outline-warning">
                            + Đặt thêm vé
                        </a>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success mt-3 alert-dismissible fade show">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger mt-3 alert-dismissible fade show">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{-- Danh sách Đơn đặt vé (Bookings) --}}
                    <div class="mt-4">
                        @forelse($bookings as $booking)
                            @php
                                $status = $booking->status;
                                $statusBadge = match($status) {
                                    'PENDING' => 'bg-warning text-dark',
                                    'PAID' => 'bg-success',
                                    'CANCELLED' => 'bg-danger',
                                    'REFUNDED' => 'bg-info text-dark',
                                    default => 'bg-secondary'
                                };
                                $statusText = match($status) {
                                    'PENDING' => 'Chờ thanh toán',
                                    'PAID' => 'Đã thanh toán',
                                    'CANCELLED' => 'Đã hủy',
                                    'REFUNDED' => 'Đã hoàn tiền',
                                    default => $status
                                };

                                $firstTicket = $booking->ticketDetails->first();
                                $movie = $firstTicket?->showtime?->movie;
                                $showtime = $firstTicket?->showtime;
                                $pendingRefund = $booking->refunds->where('status', 'PENDING')->first();
                                $completedRefund = $booking->refunds->where('status', 'COMPLETED')->first();
                            @endphp

                            <div class="card bg-dark text-white border-secondary mb-4 shadow-sm">
                                <div class="card-header border-secondary d-flex justify-content-between align-items-center py-3 bg-black bg-opacity-25">
                                    <div>
                                        <span class="fw-bold fs-5 text-warning">Đơn đặt vé #{{ $booking->id }}</span>
                                        <small class="text-secondary ms-2">
                                            ({{ $booking->created_at ? $booking->created_at->format('H:i d/m/Y') : '' }})
                                        </small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($pendingRefund)
                                            <span class="badge bg-warning text-dark px-2 py-1">⏳ Đang chờ hoàn tiền</span>
                                        @endif
                                        <span class="badge {{ $statusBadge }} px-3 py-2 fs-6">
                                            {{ $statusText }}
                                        </span>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <div class="row align-items-center">
                                        {{-- Cột thông tin phim & suất chiếu --}}
                                        <div class="col-lg-6 mb-3 mb-lg-0">
                                            <h4 class="text-white mb-2 fw-bold">
                                                {{ $movie->title ?? 'N/A' }}
                                            </h4>
                                            <div class="text-secondary mb-1">
                                                ⏰ Suất chiếu: <strong>{{ $showtime ? \Carbon\Carbon::parse($showtime->start_time)->format('H:i - d/m/Y') : 'N/A' }}</strong>
                                            </div>
                                            <div class="text-secondary mb-2">
                                                🏢 Phòng: <strong>{{ $showtime->room->name ?? 'Phòng chiếu' }}</strong>
                                            </div>

                                            {{-- Danh sách ghế đã chọn --}}
                                            <div class="d-flex flex-wrap gap-1 align-items-center mt-2">
                                                <span class="text-secondary small me-1">Ghế ({{ $booking->ticketDetails->count() }}):</span>
                                                @foreach($booking->ticketDetails as $td)
                                                    <span class="badge {{ $td->seat->type === 'VIP' ? 'bg-warning text-dark' : 'bg-primary' }}">
                                                        {{ $td->seat->seat_row }}{{ $td->seat->seat_number }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>

                                        {{-- Cột tổng tiền & Đồng hồ đếm ngược --}}
                                        <div class="col-lg-3 text-lg-center mb-3 mb-lg-0 border-start border-secondary ps-lg-3">
                                            <div class="text-secondary small">Tổng tiền đơn vé:</div>
                                            <div class="fs-4 text-warning fw-bold my-1">
                                                {{ number_format($booking->total_price, 0, ',', '.') }} đ
                                            </div>

                                            @if($status === 'PENDING' && $booking->holding_expires_at)
                                                <div class="mt-2 p-2 bg-black bg-opacity-50 rounded">
                                                    <small class="text-danger d-block fw-bold">Thời gian giữ ghế còn lại:</small>
                                                    <div class="countdown-timer fs-5 text-danger fw-bold" data-expire="{{ $booking->holding_expires_at->toIso8601String() }}">
                                                        --:--
                                                    </div>
                                                </div>
                                            @elseif($status === 'PAID')
                                                <small class="text-success d-block mt-1">✓ Đã thanh toán đầy đủ</small>
                                            @endif
                                        </div>

                                        {{-- Cột các nút thao tác --}}
                                        <div class="col-lg-3 text-end border-start border-secondary ps-lg-3">
                                            @if($status === 'PENDING')
                                                {{-- Nút xem chi tiết các vé --}}
                                                <a href="{{ route('user.account.tickets.show', $booking->id) }}" class="btn btn-sm btn-outline-light w-100 mb-2">
                                                    👁️ Xem chi tiết các vé
                                                </a>

                                                {{-- Form Thanh toán Đơn hàng --}}
                                                <form method="POST" action="{{ route('user.account.bookings.pay', $booking->id) }}" class="mb-2">
                                                    @csrf
                                                    <div class="mb-2 text-start">
                                                        <select name="payment_method" class="form-select form-select-sm bg-dark text-white border-secondary">
                                                            <option value="BANK_TRANSFER">🏦 Chuyển khoản (VNPAY)</option>
                                                            <option value="E_WALLET">📱 Ví điện tử (MoMo)</option>
                                                            <option value="CARD">💳 Thẻ ngân hàng / Visa</option>
                                                        </select>
                                                    </div>
                                                    <button type="submit" class="btn btn-success w-100 py-2 fw-bold">
                                                        💳 Thanh toán ngay
                                                    </button>
                                                </form>

                                                {{-- Nút Hủy Đơn giữ chỗ --}}
                                                <form method="POST" action="{{ route('user.account.bookings.cancel', $booking->id) }}" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn đặt vé này? Các ghế đang giữ sẽ được giải phóng.')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                                        ✕ Hủy đơn
                                                    </button>
                                                </form>

                                            @elseif($status === 'PAID')
                                                {{-- Đơn đã thanh toán: Nút xem tất cả các vé trong đơn & Nút Yêu cầu hoàn tiền --}}
                                                <a href="{{ route('user.account.tickets.show', $booking->id) }}" class="btn btn-sm btn-outline-info w-100 mb-2">
                                                    🎟️ Xem chi tiết vé
                                                </a>

                                                @if($booking->canRequestRefund())
                                                    <form method="POST" action="{{ route('user.account.bookings.refund', $booking->id) }}" onsubmit="return confirm('Bạn có chắc chắn muốn gửi YÊU CẦU HOÀN TIỀN cho đơn vé này? (Áp dụng trước giờ chiếu ít nhất 2 tiếng)')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-warning w-100">
                                                            💸 Yêu cầu hoàn tiền
                                                        </button>
                                                    </form>
                                                @elseif($pendingRefund)
                                                    <div class="small text-warning text-center">
                                                        Yêu cầu hoàn tiền đang chờ Admin duyệt
                                                    </div>
                                                @elseif($completedRefund)
                                                    <div class="small text-success text-center">
                                                        Đã được hoàn tiền thành công
                                                    </div>
                                                @else
                                                    <div class="small text-muted text-center">
                                                        (Quá hạn hoàn tiền &lt; 2h trước giờ chiếu)
                                                    </div>
                                                @endif

                                            @else
                                                <div class="text-muted small text-center py-2">
                                                    Đơn hàng không có thao tác bổ sung.
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Chân card: Liệt kê chi tiết từng vé trong đơn --}}
                                <div class="card-footer border-secondary bg-black bg-opacity-25 py-2">
                                    <div class="d-flex justify-content-between align-items-center small">
                                        <span class="text-secondary">Chi tiết từng vé trong đơn:</span>
                                        <div class="d-flex gap-2">
                                            @foreach($booking->ticketDetails as $td)
                                                <a href="{{ route('user.account.tickets.show', $booking->id) }}" class="text-info text-decoration-none">
                                                    Vé #{{ $td->id }} (Ghế {{ $td->seat->seat_row }}{{ $td->seat->seat_number }} - {{ number_format($td->final_price, 0, ',', '.') }}đ)
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted border border-secondary rounded">
                                <div class="fs-1 mb-3">🎬</div>
                                <h5>Bạn chưa có đơn đặt vé nào.</h5>
                                <p class="small">Hãy chọn một bộ phim yêu thích và trải nghiệm đặt vé xem phim ngay!</p>
                                <a href="{{ route('movies') }}" class="btn btn-warning mt-2">
                                    Xem danh sách phim đang chiếu
                                </a>
                            </div>
                        @endforelse
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

{{-- Script đếm ngược thời gian giữ chỗ theo từng booking --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const timers = document.querySelectorAll('.countdown-timer');

        function updateTimers() {
            const now = new Date().getTime();

            timers.forEach(function (timer) {
                const expireStr = timer.getAttribute('data-expire');
                if (!expireStr) return;

                const expireTime = new Date(expireStr).getTime();
                const diff = expireTime - now;

                if (diff <= 0) {
                    timer.textContent = 'Hết hạn giữ chỗ';
                    timer.classList.remove('text-danger');
                    timer.classList.add('text-secondary');
                } else {
                    const minutes = Math.floor(diff / (1000 * 60));
                    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                    timer.textContent = `${minutes < 10 ? '0' : ''}${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
                }
            });
        }

        updateTimers();
        setInterval(updateTimers, 1000);
    });
</script>
@endsection
