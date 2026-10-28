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

                    {{-- Tiêu đề & Nút quay lại --}}
                    <div class="account-header d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fs-4 fw-bold">CHI TIẾT ĐƠN ĐẶT VÉ #{{ $booking->id }}</span>
                            <small class="text-secondary d-block mt-1">
                                Thời điểm đặt: {{ $booking->created_at ? $booking->created_at->format('H:i - d/m/Y') : 'N/A' }}
                            </small>
                        </div>
                        <a href="{{ route('user.account.tickets') }}" class="btn btn-sm btn-outline-light">
                            &lt; Danh sách vé & đơn hàng
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

                    @php
                        $status = $booking->status;
                        $bookingBadge = match($status) {
                            'PENDING' => 'bg-warning text-dark',
                            'PAID' => 'bg-success',
                            'CANCELLED' => 'bg-danger',
                            'REFUNDED' => 'bg-info text-dark',
                            default => 'bg-secondary'
                        };
                        $bookingStatusText = match($status) {
                            'PENDING' => 'Chờ thanh toán',
                            'PAID' => 'Đã thanh toán thành công',
                            'CANCELLED' => 'Đơn đã hủy',
                            'REFUNDED' => 'Đã hoàn tiền',
                            default => $status
                        };

                        $firstTicket = $tickets->first();
                        $movie = $firstTicket?->showtime?->movie;
                        $showtime = $firstTicket?->showtime;
                        $room = $showtime?->room;
                        $payment = $booking->successfulPayment ?? $booking->payments->first();
                        $pendingRefund = $booking->refunds->where('status', 'PENDING')->first();
                        $holdingExpires = $booking->holding_expires_at ?? $booking->expired_at;
                    @endphp

                    {{-- Card 1: Thông tin Suất chiếu & Phim --}}
                    <div class="card bg-dark text-white border-secondary mb-4 mt-3 shadow-sm">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <span class="badge bg-primary mb-2">Thông tin phim</span>
                                    <h3 class="text-warning mb-2 fw-bold">
                                        {{ $movie->title ?? 'N/A' }}
                                    </h3>
                                    <div class="text-light mb-1">
                                        ⏰ Suất chiếu: <strong>{{ $showtime ? \Carbon\Carbon::parse($showtime->start_time)->format('H:i - d/m/Y') : 'N/A' }}</strong>
                                        @if($showtime && $showtime->end_time)
                                            <span class="text-secondary">(kết thúc: {{ \Carbon\Carbon::parse($showtime->end_time)->format('H:i') }})</span>
                                        @endif
                                    </div>
                                    <div class="text-light mb-1">
                                        🏢 Phòng chiếu: <strong>{{ $room->name ?? 'Phòng chiếu' }}</strong>
                                    </div>
                                    <div class="text-secondary small">
                                        Thời lượng: {{ $movie->duration ?? 0 }} phút | Thể loại: {{ $movie->genre ?? 'Chưa cập nhật' }}
                                    </div>
                                </div>
                                <div class="col-md-4 text-md-end mt-3 mt-md-0 border-start border-secondary ps-md-4">
                                    <div class="text-secondary small">Trạng thái đơn:</div>
                                    <span class="badge {{ $bookingBadge }} fs-6 px-3 py-2 mt-1 mb-2 d-inline-block">
                                        {{ $bookingStatusText }}
                                    </span>
                                    <div class="text-secondary small">Tổng tiền đơn vé:</div>
                                    <div class="fs-4 text-warning fw-bold">
                                        {{ number_format($booking->total_price, 0, ',', '.') }} đ
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card 2: DANH SÁCH TẤT CẢ CÁC VÉ TRONG ĐƠN (HIỂN THỊ ĐẦY ĐỦ CÁC VÉ) --}}
                    <div class="card bg-dark text-white border-secondary mb-4 shadow-sm">
                        <div class="card-header border-secondary bg-black bg-opacity-25 py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold text-info">
                                🎟️ Danh sách tất cả các vé trong đơn ({{ $tickets->count() }} vé)
                            </h5>
                            <span class="badge bg-secondary">
                                Tổng: {{ $tickets->count() }} ghế
                            </span>
                        </div>

                        <div class="card-body p-4">
                            <div class="row g-3">
                                @forelse($tickets as $t)
                                    @php
                                        $ticketStatus = $t->status;
                                        $tBadge = match($ticketStatus) {
                                            'HOLDING' => 'bg-warning text-dark',
                                            'BOOKED' => 'bg-success',
                                            'USED' => 'bg-info',
                                            'CANCELLED' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        $tStatusText = match($ticketStatus) {
                                            'HOLDING' => 'Đang giữ chỗ',
                                            'BOOKED' => 'Đã thanh toán',
                                            'USED' => 'Đã soát vé (USED)',
                                            'CANCELLED' => 'Đã hủy',
                                            default => $ticketStatus
                                        };
                                        $isVip = $t->seat && $t->seat->type === 'VIP';
                                    @endphp

                                    <div class="col-md-6 col-lg-4">
                                        <div class="card h-100 bg-black bg-opacity-50 border border-secondary shadow-sm text-center">
                                            <div class="card-header border-secondary d-flex justify-content-between align-items-center py-2 px-3">
                                                <strong class="text-white small">Mã vé: #{{ $t->id }}</strong>
                                                <span class="badge {{ $tBadge }} small">{{ $tStatusText }}</span>
                                            </div>

                                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                                <div>
                                                    <div class="text-secondary small">Vị trí ghế ngồi:</div>
                                                    <h2 class="text-warning fw-bold my-1">
                                                        {{ $t->seat ? $t->seat->seat_row . $t->seat->seat_number : 'N/A' }}
                                                    </h2>
                                                    <span class="badge {{ $isVip ? 'bg-warning text-dark' : 'bg-primary' }} mb-2">
                                                        {{ $isVip ? 'Ghế VIP (120%)' : 'Ghế Thường (Standard)' }}
                                                    </span>

                                                    <div class="text-white fs-5 fw-bold mb-3">
                                                        {{ number_format($t->final_price, 0, ',', '.') }} đ
                                                    </div>
                                                </div>

                                                {{-- Khung mã QR hoặc trạng thái của từng vé --}}
                                                <div class="pt-2 border-top border-secondary">
                                                    @if($ticketStatus === 'BOOKED' || $ticketStatus === 'USED')
                                                        <div class="bg-white p-2 rounded text-dark d-inline-block shadow-sm">
                                                            <div style="font-family: monospace; font-size: 0.95rem; font-weight: bold; padding: 6px; border: 2px dashed #000; letter-spacing: 1px;">
                                                                QR-CINEGO-{{ $t->id }}
                                                            </div>
                                                        </div>
                                                        @if($ticketStatus === 'USED')
                                                            <div class="text-info fw-bold small mt-1">
                                                                ✓ Đã qua cổng soát vé
                                                            </div>
                                                        @else
                                                            <div class="text-secondary small mt-1">
                                                                Đưa mã này cho nhân viên soát vé
                                                            </div>
                                                        @endif
                                                    @elseif($ticketStatus === 'HOLDING')
                                                        <div class="text-warning small py-2">
                                                            ⏳ Vé đang được giữ tạm thời
                                                        </div>
                                                    @else
                                                        <div class="text-danger small py-2">
                                                            ✕ Vé đã bị hủy
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12 text-center py-4 text-muted">
                                        Không có vé nào thuộc đơn đặt vé này.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- Card 3: Thông tin Thanh toán & Các thao tác cho Đơn hàng --}}
                    <div class="card bg-dark text-white border-secondary shadow-sm">
                        <div class="card-header border-secondary bg-black bg-opacity-25 py-3">
                            <h5 class="mb-0 fw-bold text-white">
                                💳 Thông tin giao dịch & Thao tác đơn hàng
                            </h5>
                        </div>

                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-md-7 mb-3 mb-md-0">
                                    @if($status === 'PAID')
                                        <div class="d-flex align-items-center text-success mb-2">
                                            <span class="fs-4 me-2">✓</span>
                                            <span class="fw-bold fs-6">Đơn hàng đã được thanh toán đầy đủ</span>
                                        </div>
                                        @if($payment)
                                            <div class="text-secondary small mb-1">
                                                Phương thức: <strong>{{ $payment->method }} ({{ $payment->provider ?? 'Cổng thanh toán' }})</strong>
                                            </div>
                                            <div class="text-secondary small mb-1">
                                                Mã thanh toán: <strong class="text-white">{{ $payment->payment_code }}</strong>
                                            </div>
                                            @if($payment->transaction_code)
                                                <div class="text-secondary small mb-1">
                                                    Mã giao dịch cổng: <strong class="text-white">{{ $payment->transaction_code }}</strong>
                                                </div>
                                            @endif
                                            <div class="text-secondary small">
                                                Thời gian thanh toán: <strong>{{ $payment->paid_at ? $payment->paid_at->format('H:i - d/m/Y') : 'N/A' }}</strong>
                                            </div>
                                        @endif
                                    @elseif($status === 'PENDING')
                                        <div class="text-warning fw-bold mb-2">
                                            ⏳ Đơn hàng đang chờ thanh toán
                                        </div>
                                        @if($holdingExpires)
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <span class="text-secondary small">Thời gian giữ chỗ còn lại:</span>
                                                <span id="countdown" class="badge bg-danger fs-6 fw-bold">--:--</span>
                                            </div>
                                        @endif
                                        <small class="text-secondary d-block">
                                            Sau thời gian này, nếu chưa thanh toán, các ghế sẽ tự động giải phóng cho người khác đặt.
                                        </small>
                                    @elseif($status === 'REFUNDED')
                                        <div class="text-info fw-bold mb-2">
                                            ✓ Đơn hàng đã được hoàn tiền thành công
                                        </div>
                                        <small class="text-secondary">
                                            Số tiền đã được hoàn lại và các vé trong đơn đã được giải phóng.
                                        </small>
                                    @else
                                        <div class="text-danger fw-bold mb-2">
                                            ✕ Đơn hàng đã bị hủy
                                        </div>
                                        <small class="text-secondary">
                                            Đơn hàng đã hết hạn hoặc được hủy thành công.
                                        </small>
                                    @endif
                                </div>

                                <div class="col-md-5 text-md-end border-start border-secondary ps-md-4">
                                    @if($status === 'PENDING')
                                        {{-- Form thanh toán toàn bộ đơn hàng --}}
                                        <form method="POST" action="{{ route('user.account.bookings.pay', $booking->id) }}" class="mb-2">
                                            @csrf
                                            <div class="mb-2 text-start">
                                                <label class="form-label text-secondary small mb-1">Chọn phương thức:</label>
                                                <select name="payment_method" class="form-select form-select-sm bg-dark text-white border-secondary">
                                                    <option value="BANK_TRANSFER">🏦 Chuyển khoản (VNPAY)</option>
                                                    <option value="E_WALLET">📱 Ví điện tử (MoMo)</option>
                                                    <option value="CARD">💳 Thẻ ngân hàng / Visa</option>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-success w-100 py-2 fw-bold">
                                                💳 Thanh toán đơn vé ({{ number_format($booking->total_price, 0, ',', '.') }} đ)
                                            </button>
                                        </form>

                                        {{-- Nút hủy toàn bộ đơn --}}
                                        <form method="POST" action="{{ route('user.account.bookings.cancel', $booking->id) }}" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn đặt vé này? Toàn bộ các ghế đang giữ sẽ được giải phóng.')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                                ✕ Hủy đơn đặt vé
                                            </button>
                                        </form>
                                    @elseif($status === 'PAID')
                                        @if($booking->canRequestRefund())
                                            <form method="POST" action="{{ route('user.account.bookings.refund', $booking->id) }}" onsubmit="return confirm('Bạn có chắc chắn muốn gửi YÊU CẦU HOÀN TIỀN cho toàn bộ đơn đặt vé này? (Áp dụng trước giờ chiếu ít nhất 2 tiếng)')">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-warning w-100 py-2">
                                                    💸 Yêu cầu hoàn tiền đơn vé
                                                </button>
                                            </form>
                                            <small class="text-secondary d-block mt-2 text-center">
                                                (Áp dụng khi yêu cầu trước giờ chiếu $\ge$ 2 tiếng)
                                            </small>
                                        @elseif($pendingRefund)
                                            <div class="alert alert-warning mb-0 text-center py-2">
                                                ⏳ Yêu cầu hoàn tiền đang chờ Admin xét duyệt
                                            </div>
                                        @else
                                            <div class="text-muted small text-center">
                                                Không thể hoàn tiền (quá hạn trước giờ chiếu &lt; 2h).
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

@if($status === 'PENDING' && $holdingExpires)
<script>
    const expireTime = new Date(@json(\Carbon\Carbon::parse($holdingExpires)->toIso8601String())).getTime();

    function updateCountdown() {
        const now = new Date().getTime();
        const diff = expireTime - now;

        const cdEl = document.getElementById('countdown');
        if (!cdEl) return;

        if (diff <= 0) {
            cdEl.textContent = 'Hết hạn';
            return;
        }

        const minutes = Math.floor(diff / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        cdEl.textContent = `${minutes < 10 ? '0' : ''}${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
</script>
@endif
@endsection
