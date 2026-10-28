@extends('layouts.admin')

@section('title', 'Sơ đồ phòng chiếu: ' . $room->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1 text-light fw-bold">Sơ đồ phòng: {{ $room->name }}</h3>
        <p class="text-muted mb-0">Tổng cộng: {{ $room->total_seats }} ghế ({{ count($vipSeats) }} ghế VIP).</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.rooms.edit', $room->id) }}" class="btn btn-primary">Sửa phòng</a>
        <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-light">&lt; Quay lại</a>
    </div>
</div>

<div class="card p-4 border-secondary shadow-sm text-center">
    <div class="screen mb-4 p-2 rounded bg-dark border border-secondary text-secondary" style="letter-spacing: 2px;">
        MÀN HÌNH CHIẾU
    </div>

    <div class="d-flex flex-column align-items-center gap-2 mb-4">
        @for($row = 0; $row < $rowCount; $row++)
            @php $rowLetter = chr(65 + $row); @endphp
            <div class="d-flex gap-2">
                @for($seat = 1; $seat <= $seatPerRow; $seat++)
                    @php
                        $code = $rowLetter . $seat;
                        $isVip = in_array($code, $vipSeats);
                    @endphp
                    <div
                        class="seat-box {{ $isVip ? 'bg-warning text-dark fw-bold' : 'bg-primary text-white' }}"
                        style="width: 38px; height: 38px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;"
                        title="{{ $code }} ({{ $isVip ? 'VIP' : 'Thường' }})"
                    >
                        {{ $code }}
                    </div>
                @endfor
            </div>
        @endfor
    </div>

    <div class="d-flex justify-content-center gap-4 text-light small pt-3 border-top border-secondary">
        <div class="d-flex align-items-center gap-2">
            <span class="bg-primary d-inline-block rounded" style="width: 20px; height: 20px;"></span>
            <span>Ghế thường</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="bg-warning d-inline-block rounded" style="width: 20px; height: 20px;"></span>
            <span>Ghế VIP</span>
        </div>
    </div>
</div>
@endsection
