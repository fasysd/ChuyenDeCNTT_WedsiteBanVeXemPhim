@extends('layouts.staff')

@section('title', 'Tra cứu & Soát vé')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1 text-light fw-bold">Tra cứu & Soát vé</h3>
        <p class="text-muted mb-0">Nhập mã vé hoặc mã đơn đặt để kiểm tra thông tin và soát vé cho khách hàng.</p>
    </div>
</div>

<div class="card p-4 mb-4 border-secondary">
    <form method="GET" action="{{ route('staff.tickets') }}">
        <div class="input-group">
            <input type="text" name="q" class="form-control" placeholder="Nhập mã vé (ví dụ: 1) hoặc mã đơn đặt..." value="{{ $query }}">
            <button class="btn btn-info px-4" type="submit">🔍 Tìm kiếm</button>
            @if($query !== '')
                <a href="{{ route('staff.tickets') }}" class="btn btn-outline-secondary">Xóa bộ lọc</a>
            @endif
        </div>
    </form>
</div>

@if($query !== '')
    <h4 class="text-light mb-3">Kết quả tìm kiếm cho: "{{ $query }}"</h4>
    @if($ticket)
        <div class="card p-4 border-info mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="text-warning mb-2">{{ $ticket->showtime?->movie?->title ?? 'N/A' }}</h4>
                    <p class="mb-1"><strong>Mã vé:</strong> #{{ $ticket->id }} | <strong>Mã đơn:</strong> #{{ $ticket->booking_id }}</p>
                    <p class="mb-1"><strong>Khách hàng:</strong> {{ $ticket->booking?->user->full_name ?: $ticket->booking?->user->username ?: 'Khách hàng' }} (SĐT: {{ $ticket->booking?->user->phone ?? 'Chưa cập nhật' }})</p>
                    <p class="mb-1"><strong>Phòng:</strong> {{ $ticket->showtime?->room?->name ?? 'N/A' }} | <strong>Ghế:</strong> {{ $ticket->seat?->label }} ({{ $ticket->seat?->type }})</p>
                    <p class="mb-1"><strong>Suất chiếu:</strong> {{ $ticket->showtime ? \Carbon\Carbon::parse($ticket->showtime->start_time)->format('H:i d/m/Y') : 'N/A' }}</p>
                    <p class="mb-0"><strong>Trạng thái:</strong> <span class="badge bg-warning text-dark">{{ $statuses[$ticket->status] ?? $ticket->status }}</span></p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('staff.tickets.show', $ticket) }}" class="btn btn-primary btn-lg">
                        Xử lý / Soát vé
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-warning mb-4">Không tìm thấy vé nào khớp với mã "{{ $query }}".</div>
    @endif
@endif

<h5 class="text-light mb-3">Vé mới đặt gần đây</h5>
<div class="table-responsive rounded-4 shadow-sm">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Mã vé</th>
                <th>Khách hàng</th>
                <th>Phim</th>
                <th>Ghế</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $t)
                <tr>
                    <td><strong>#{{ $t->id }}</strong></td>
                    <td>{{ $t->booking?->user->full_name ?: $t->booking?->user->username ?: 'N/A' }}</td>
                    <td>{{ $t->showtime?->movie?->title ?? 'N/A' }}</td>
                    <td>{{ $t->seat?->label }}</td>
                    <td>
                        <span class="badge bg-secondary">{{ $statuses[$t->status] ?? $t->status }}</span>
                    </td>
                    <td>
                        <a href="{{ route('staff.tickets.show', $t) }}" class="btn btn-sm btn-outline-info">Chi tiết</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-3 text-muted">Chưa có vé nào.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
