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
            <div class="col-lg-8 col-md-8">
                <div class="account-content">

                    <div class="account-header d-flex justify-content-between align-items-center">
                        <span>CHI TIẾT VÉ #{{ $ticket->id }}</span>
                        <a href="{{ route('user.account.tickets') }}" class="btn btn-sm btn-outline-light">
                            &lt; Danh sách vé
                        </a>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success mt-3">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger mt-3">{{ session('error') }}</div>
                    @endif

                    @php
                        $status = $ticket->status;
                        $statusText = match($status) {
                            'HOLDING' => 'Đang giữ chỗ',
                            'BOOKED' => 'Đã thanh toán',
                            'USED' => 'Đã sử dụng',
                            'CANCELLED' => 'Đã hủy',
                            default => $status
                        };
                        $statusBadge = match($status) {
                            'HOLDING' => 'bg-warning text-dark',
                            'BOOKED' => 'bg-success',
                            'USED' => 'bg-info',
                            'CANCELLED' => 'bg-danger',
                            default => 'bg-secondary'
                        };
                    @endphp

                    <div class="ticket-detail-box p-4 bg-dark text-white rounded mt-3">
                        <div class="row">
                            <div class="col-md-8">
                                <h3 class="text-warning mb-3">
                                    {{ $ticket->showtime->movie->title ?? 'N/A' }}
                                </h3>

                                <p class="mb-2">
                                    <strong>Phòng chiếu:</strong>
                                    {{ $ticket->showtime->room->name ?? 'Phòng chiếu' }}
                                </p>

                                <p class="mb-2">
                                    <strong>Suất chiếu:</strong>
                                    {{ $ticket->showtime ? \Carbon\Carbon::parse($ticket->showtime->start_time)->format('H:i - d/m/Y') : 'N/A' }}
                                </p>

                                <p class="mb-2">
                                    <strong>Ghế:</strong>
                                    <span class="badge {{ $ticket->seat->type === 'VIP' ? 'bg-warning text-dark' : 'bg-primary' }} fs-6">
                                        {{ $ticket->seat->seat_row }}{{ $ticket->seat->seat_number }} ({{ $ticket->seat->type }})
                                    </span>
                                </p>

                                <p class="mb-2">
                                    <strong>Giá vé:</strong>
                                    <span class="fs-5 text-warning fw-bold">
                                        {{ number_format($ticket->final_price, 0, ',', '.') }} đ
                                    </span>
                                </p>

                                <p class="mb-0">
                                    <strong>Trạng thái:</strong>
                                    <span class="badge {{ $statusBadge }} fs-6">
                                        {{ $statusText }}
                                    </span>
                                </p>
                            </div>

                            <div class="col-md-4 text-center d-flex flex-column justify-content-center border-start border-secondary ps-4">
                                @if($status === 'HOLDING' && $ticket->booking && $ticket->booking->expired_at)
                                    <div class="mb-3">
                                        <small class="text-secondary d-block">Thời gian giữ chỗ còn lại:</small>
                                        <div id="countdown" class="fs-4 text-danger fw-bold mt-1">--:--</div>
                                    </div>
                                @endif

                                <div class="mt-3">
                                    @if($status === 'HOLDING')
                                        <form method="POST" action="{{ route('user.account.tickets.payment', $ticket->id) }}" class="mb-2">
                                            @csrf
                                            <button type="submit" class="btn btn-success w-100 py-2">
                                                Thanh toán ngay
                                            </button>
                                        </form>

                                        <form id="cancel-ticket-form" method="POST" action="{{ route('user.account.tickets.cancel', $ticket->id) }}" onsubmit="return confirm('Bạn có chắc chắn muốn hủy giữ chỗ chiếc vé này?')">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger w-100">
                                                Hủy giữ chỗ
                                            </button>
                                        </form>
                                    @elseif($status === 'BOOKED')
                                        <form method="POST" action="{{ route('user.account.tickets.cancel', $ticket->id) }}" onsubmit="return confirm('Bạn có chắc chắn muốn hủy vé đã thanh toán này?')">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger w-100">
                                                Yêu cầu hủy vé
                                            </button>
                                        </form>
                                    @else
                                        <div class="text-muted small">
                                            Vé này không thể thực hiện thêm thao tác.
                                        </div>
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

@if($status === 'HOLDING' && $ticket->booking && $ticket->booking->expired_at)
<script>
    const expireTime = new Date(@json(\Carbon\Carbon::parse($ticket->booking->expired_at)->toIso8601String())).getTime();

    function updateCountdown() {
        const now = new Date().getTime();
        const diff = expireTime - now;

        if (diff <= 0) {
            document.getElementById('countdown').textContent = 'Hết hạn';
            return;
        }

        const minutes = Math.floor(diff / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        document.getElementById('countdown').textContent =
            `${minutes < 10 ? '0' : ''}${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
</script>
@endif
@endsection
