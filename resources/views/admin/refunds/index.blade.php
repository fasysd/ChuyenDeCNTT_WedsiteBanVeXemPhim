@extends('layouts.admin')

@section('title', 'Quản lý hoàn tiền - CineGo Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">💸 Quản lý yêu cầu hoàn tiền</h3>
        <p class="text-muted mb-0">Theo dõi, xét duyệt và xử lý hoàn tiền các đơn đặt vé của khách hàng</p>
    </div>
</div>

{{-- Thẻ thống kê chỉ số --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card p-3 rounded">
            <div class="text-muted small">Tổng yêu cầu</div>
            <div class="fs-4 fw-bold text-white mt-1">{{ number_format($stats['total']) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3 rounded border-warning">
            <div class="text-warning small fw-bold">⏳ Chờ xem xét</div>
            <div class="fs-4 fw-bold text-warning mt-1">{{ number_format($stats['pending']) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3 rounded border-success">
            <div class="text-success small fw-bold">✅ Đã hoàn tiền</div>
            <div class="fs-4 fw-bold text-success mt-1">{{ number_format($stats['completed']) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3 rounded border-info">
            <div class="text-info small fw-bold">💰 Tổng tiền đã hoàn</div>
            <div class="fs-4 fw-bold text-info mt-1">{{ number_format($stats['total_refunded_amount'], 0, ',', '.') }} đ</div>
        </div>
    </div>
</div>

{{-- Bộ lọc trạng thái --}}
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('admin.refunds.index') }}" class="btn btn-sm {{ empty($status) ? 'btn-primary' : 'btn-outline-light' }}">
        Tất cả ({{ $stats['total'] }})
    </a>
    <a href="{{ route('admin.refunds.index', ['status' => 'PENDING']) }}" class="btn btn-sm {{ $status === 'PENDING' ? 'btn-warning text-dark' : 'btn-outline-warning' }}">
        Chờ duyệt ({{ $stats['pending'] }})
    </a>
    <a href="{{ route('admin.refunds.index', ['status' => 'COMPLETED']) }}" class="btn btn-sm {{ $status === 'COMPLETED' ? 'btn-success' : 'btn-outline-success' }}">
        Đã hoàn ({{ $stats['completed'] }})
    </a>
    <a href="{{ route('admin.refunds.index', ['status' => 'FAILED']) }}" class="btn btn-sm {{ $status === 'FAILED' ? 'btn-danger' : 'btn-outline-danger' }}">
        Đã từ chối ({{ $stats['failed'] }})
    </a>
</div>

{{-- Bảng danh sách yêu cầu hoàn tiền --}}
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Mã yêu cầu</th>
                <th>Khách hàng</th>
                <th>Thông tin vé / Suất chiếu</th>
                <th>Số tiền hoàn</th>
                <th>Thời điểm gửi</th>
                <th>Trạng thái</th>
                <th class="text-end">Hành động</th>
            </tr>
        </thead>
        <tbody>
            @forelse($refunds as $refund)
                @php
                    $booking = $refund->payment?->booking;
                    $user = $booking?->user;
                    $firstTicket = $booking?->ticketDetails?->first();
                    $movieTitle = $firstTicket?->showtime?->movie?->title ?? 'N/A';
                    $startTime = $firstTicket?->showtime?->start_time ? \Carbon\Carbon::parse($firstTicket->showtime->start_time)->format('H:i d/m/Y') : 'N/A';
                    $seats = $booking?->ticketDetails?->map(fn($t) => $t->seat ? $t->seat->seat_row . $t->seat->seat_number : '')->filter()->join(', ');
                @endphp
                <tr>
                    <td>
                        <strong class="text-primary">{{ $refund->refund_code }}</strong>
                        @if($refund->payment?->payment_code)
                            <div class="small text-muted">{{ $refund->payment->payment_code }}</div>
                        @endif
                        <div class="small text-muted">Đơn #{{ $booking?->id ?? 'N/A' }}</div>
                        <div class="small text-muted">{{ $refund->payment?->method ?? 'BANK_TRANSFER' }}</div>
                    </td>
                    <td>
                        <strong>{{ $user->full_name ?? $user->username ?? 'Khách' }}</strong>
                        <div class="small text-muted">{{ $user->email ?? '' }}</div>
                        <div class="small text-muted">{{ $user->phone ?? '' }}</div>
                    </td>
                    <td>
                        <div class="fw-bold">{{ $movieTitle }}</div>
                        <div class="small text-muted">Suất: {{ $startTime }}</div>
                        <div class="small text-muted">Ghế: <span class="badge bg-secondary">{{ $seats ?: 'N/A' }}</span></div>
                    </td>
                    <td>
                        <span class="fw-bold text-danger fs-6">
                            {{ number_format($refund->amount, 0, ',', '.') }} đ
                        </span>
                    </td>
                    <td>
                        <div>{{ $refund->created_at ? $refund->created_at->format('H:i d/m/Y') : 'N/A' }}</div>
                        @if($refund->refunded_at)
                            <small class="text-success d-block">Duyệt: {{ $refund->refunded_at->format('H:i d/m/Y') }}</small>
                        @endif
                    </td>
                    <td>
                        @if($refund->status === 'PENDING')
                            <span class="badge bg-warning text-dark px-2 py-1">Chờ xét duyệt</span>
                        @elseif($refund->status === 'COMPLETED')
                            <span class="badge bg-success px-2 py-1">Đã hoàn tiền</span>
                        @else
                            <span class="badge bg-danger px-2 py-1">Từ chối</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @if($refund->status === 'PENDING')
                            <div class="d-flex justify-content-end gap-1">
                                <form method="POST" action="{{ route('admin.refunds.approve', $refund->id) }}" onsubmit="return confirm('Bạn có chắc chắn muốn XÁC NHẬN HOÀN TIỀN cho đơn hàng này? Ghế sẽ được tự động giải phóng.')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">
                                        ✓ Xác nhận hoàn
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.refunds.reject', $refund->id) }}" onsubmit="return confirm('Bạn có chắc chắn muốn TỪ CHỐI yêu cầu hoàn tiền này? Vé của khách vẫn giữ nguyên hiệu lực.')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        ✗ Từ chối
                                    </button>
                                </form>
                            </div>
                        @else
                            <span class="text-muted small">Đã xử lý</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        Không có yêu cầu hoàn tiền nào phù hợp với bộ lọc.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $refunds->links() }}
</div>
@endsection
