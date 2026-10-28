@extends('layouts.admin')

@section('title', 'Sửa phòng chiếu: ' . $room->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1 text-light fw-bold">Sửa phòng chiếu: {{ $room->name }}</h3>
        <p class="text-muted mb-0">Cập nhật kích thước và ghế VIP cho phòng chiếu.</p>
    </div>
    <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-light">
        &lt; Quay lại danh sách
    </a>
</div>

<div class="card p-4 border-secondary shadow-sm">
    <form action="{{ route('admin.rooms.update', $room->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tên phòng chiếu <span class="text-danger">*</span></label>
                <input
                    type="text"
                    name="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $room->name) }}"
                    required
                >
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Số dãy ghế <span class="text-danger">*</span></label>
                <input
                    type="number"
                    id="row_count"
                    name="row_count"
                    class="form-control @error('row_count') is-invalid @enderror"
                    value="{{ old('row_count', $rowCount) }}"
                    min="1"
                    max="26"
                    required
                >
                @error('row_count')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Số ghế mỗi dãy <span class="text-danger">*</span></label>
                <input
                    type="number"
                    id="seat_per_row"
                    name="seat_per_row"
                    class="form-control @error('seat_per_row') is-invalid @enderror"
                    value="{{ old('seat_per_row', $seatPerRow) }}"
                    min="1"
                    max="26"
                    required
                >
                @error('seat_per_row')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mt-4">
            <h5 class="text-light">Sơ đồ phòng chiếu & Đánh dấu ghế VIP</h5>
            <small class="text-muted d-block mb-3">Bấm vào ghế để chuyển đổi trạng thái giữa Ghế Thường và Ghế VIP.</small>

            <div id="room-preview" class="room-preview text-center p-4 rounded bg-dark border border-secondary"></div>
        </div>

        <input type="hidden" name="vip_seats" id="vip_seats">

        <div class="mt-4 pt-3 border-top border-secondary d-flex justify-content-end gap-2">
            <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary">Hủy bỏ</a>
            <button type="submit" class="btn btn-primary px-4">Cập nhật phòng</button>
        </div>
    </form>
</div>

<style>
.room-preview .screen {
    background: #1e293b;
    border: 1px solid #334155;
    padding: .5rem;
    margin-bottom: 2rem;
    border-radius: .25rem;
    color: #94a3b8;
    letter-spacing: 2px;
}
.room-preview .seat-row {
    display: flex;
    justify-content: center;
    gap: .5rem;
    margin-bottom: .5rem;
}
.room-preview .seat {
    width: 38px;
    height: 38px;
    background: #0284c7;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: .35rem;
    font-size: .8rem;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
}
.room-preview .seat.vip {
    background: #f59e0b;
    color: #000;
    font-weight: bold;
}
.room-preview .vip-legend {
    margin-top: 1.5rem;
    font-size: .9rem;
    color: #94a3b8;
}
</style>

@push('scripts')
<script>
    const rowInput = document.getElementById('row_count');
    const seatInput = document.getElementById('seat_per_row');
    const preview = document.getElementById('room-preview');
    const vipInput = document.getElementById('vip_seats');
    const vipSeats = new Set(@json($vipSeats));

    function updateVipInput() {
        vipInput.value = JSON.stringify([...vipSeats]);
    }

    function renderRoom() {
        const rows = parseInt(rowInput.value);
        const seatsPerRow = parseInt(seatInput.value);

        if (isNaN(rows) || isNaN(seatsPerRow) || rows < 1 || rows > 26 || seatsPerRow < 1) {
            preview.innerHTML = '<p class="text-muted mb-0">Nhập số dãy ghế và số ghế mỗi dãy hợp lệ để xem trước.</p>';
            return;
        }

        let html = '<div class="screen">MÀN HÌNH CHIẾU</div>';
        for (let row = 0; row < rows; row++) {
            const rowLetter = String.fromCharCode(65 + row);
            html += '<div class="seat-row">';
            for (let seat = 1; seat <= seatsPerRow; seat++) {
                const seatCode = `${rowLetter}${seat}`;
                const isVip = vipSeats.has(seatCode);
                html += `<div class="seat ${isVip ? 'vip' : ''}" data-seat="${seatCode}">${seatCode}</div>`;
            }
            html += '</div>';
        }

        html += '<div class="vip-legend">🟦 Ghế thường &nbsp;&nbsp;&nbsp; 🟧 Ghế VIP (nhấn để chọn)</div>';
        preview.innerHTML = html;

        preview.querySelectorAll('.seat').forEach(seat => {
            seat.addEventListener('click', () => {
                const seatCode = seat.dataset.seat;
                if (vipSeats.has(seatCode)) {
                    vipSeats.delete(seatCode);
                    seat.classList.remove('vip');
                } else {
                    vipSeats.add(seatCode);
                    seat.classList.add('vip');
                }
                updateVipInput();
            });
        });
        updateVipInput();
    }

    rowInput.addEventListener('input', renderRoom);
    seatInput.addEventListener('input', renderRoom);
    renderRoom();
</script>
@endpush
@endsection
