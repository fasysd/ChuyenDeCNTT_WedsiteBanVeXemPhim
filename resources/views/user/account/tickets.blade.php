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
                    <div class="account-header">
                        VÉ CỦA TÔI
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success mt-3">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger mt-3">{{ session('error') }}</div>
                    @endif

                    <div class="ticket-table-wrapper mt-3">
                        <table class="ticket-table">
                            <thead>
                                <tr>
                                    <th>Mã vé</th>
                                    <th>Phim / Suất chiếu</th>
                                    <th>Ghế</th>
                                    <th>Giá vé</th>
                                    <th>Trạng thái</th>
                                    <th class="text-end">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tickets as $ticket)
                                    @php
                                        $status = $ticket->status;
                                        $statusClass = match($status) {
                                            'HOLDING' => 'badge-warning',
                                            'BOOKED' => 'badge-success',
                                            'USED' => 'badge-info',
                                            'CANCELLED' => 'badge-danger',
                                            default => 'badge-dark'
                                        };
                                        $statusText = match($status) {
                                            'HOLDING' => 'Đang giữ chỗ',
                                            'BOOKED' => 'Đã thanh toán',
                                            'USED' => 'Đã sử dụng',
                                            'CANCELLED' => 'Đã hủy',
                                            default => $status
                                        };
                                    @endphp

                                    <tr class="ticket-row" data-id="{{ $ticket->id }}" style="cursor: pointer;" onclick="window.location.href='{{ route('user.account.tickets.show', $ticket->id) }}'">
                                        <td>
                                            #{{ $ticket->id }}
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ $ticket->showtime->movie->title ?? 'N/A' }}</strong>
                                            </div>
                                            <small class="text-secondary">
                                                {{ $ticket->showtime ? \Carbon\Carbon::parse($ticket->showtime->start_time)->format('H:i d/m/Y') : '' }}
                                                ({{ $ticket->showtime->room->name ?? 'Phòng chiếu' }})
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $ticket->seat->type === 'VIP' ? 'bg-warning text-dark' : 'bg-secondary' }}">
                                                {{ $ticket->seat->seat_row }}{{ $ticket->seat->seat_number }}
                                                ({{ $ticket->seat->type }})
                                            </span>
                                        </td>
                                        <td>
                                            {{ number_format($ticket->final_price, 0, ',', '.') }} đ
                                        </td>
                                        <td>
                                            <span class="ticket-status {{ $statusClass }}">
                                                {{ $statusText }}
                                            </span>
                                        </td>
                                        <td class="text-end" onclick="event.stopPropagation()">
                                            @if($status === 'HOLDING')
                                                <form method="POST" action="{{ route('user.account.tickets.payment', $ticket->id) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success me-1">
                                                        💳 Thanh toán
                                                    </button>
                                                </form>
                                            @endif
                                            <a href="{{ route('user.account.tickets.show', $ticket->id) }}" class="btn btn-sm btn-outline-info">
                                                Chi tiết
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            Bạn chưa có vé nào được đặt.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
