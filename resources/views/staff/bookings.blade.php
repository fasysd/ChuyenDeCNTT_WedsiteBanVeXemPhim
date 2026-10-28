@extends('layouts.staff')

@section('title', 'Danh sách đặt vé')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1 text-light fw-bold">Danh sách đặt vé</h3>
        <p class="text-muted mb-0">Theo dõi toàn bộ các vé đã được người dùng đặt trên hệ thống.</p>
    </div>
</div>

<div class="table-responsive rounded-4 shadow-sm mb-4">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Mã vé</th>
                <th>Khách hàng</th>
                <th>Phim</th>
                <th>Phòng</th>
                <th>Ghế</th>
                <th>Giá vé</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $ticket)
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
                <tr>
                    <td><strong>#{{ $ticket->id }}</strong></td>
                    <td>{{ $ticket->booking?->user->full_name ?: $ticket->booking?->user->username ?: 'Khách vãng lai' }}</td>
                    <td>{{ $ticket->showtime?->movie?->title ?? 'N/A' }}</td>
                    <td>{{ $ticket->showtime?->room?->name ?? 'N/A' }}</td>
                    <td>
                        <span class="badge {{ $ticket->seat?->type === 'VIP' ? 'bg-warning text-dark' : 'bg-primary' }}">
                            {{ $ticket->seat?->label ?? 'N/A' }}
                        </span>
                    </td>
                    <td>{{ number_format($ticket->final_price, 0, ',', '.') }} đ</td>
                    <td>
                        <span class="badge {{ $badgeClass }}">{{ $statusText }}</span>
                    </td>
                    <td>
                        <a href="{{ route('staff.tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">
                            Chi tiết / Soát vé
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">Chưa có vé nào trên hệ thống.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(method_exists($tickets, 'links'))
    <div class="d-flex justify-content-center">
        {{ $tickets->links() }}
    </div>
@endif
@endsection
