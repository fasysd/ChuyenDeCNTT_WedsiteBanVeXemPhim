@php
    $seatRows = $seats->groupBy('seat_row');
@endphp

<div class="container-fluid">
    <div class="d-flex justify-content-center">
        <div class="seat-layout">
            @foreach($seatRows as $rowName => $rowSeats)
                <div class="d-flex align-items-center mb-3">
                    <div class="seat-row-label me-3 text-secondary fw-bold" style="width: 25px;">
                        {{ $rowName }}
                    </div>

                    @foreach($rowSeats->sortBy('seat_number') as $seat)
                        @php
                            $isHolding = in_array($seat->id, $holdingSeatIds);
                            $isBooked = in_array($seat->id, $bookedSeatIds);
                            $price = $seat->type === 'VIP'
                                ? round($selectedShowtime->price_standard * 1.2)
                                : $selectedShowtime->price_standard;
                        @endphp

                        <div
                            class="seat-box
                                {{ $seat->type === 'VIP' ? 'seat-vip' : 'seat-standard' }}
                                {{ $isHolding ? 'seat-holding' : '' }}
                                {{ $isBooked ? 'seat-booked' : '' }}"
                            data-seat-id="{{ $seat->id }}"
                            data-price="{{ $price }}"
                            title="Ghế {{ $seat->seat_row }}{{ $seat->seat_number }} ({{ $seat->type }} - {{ number_format($price, 0, ',', '.') }} đ)"
                        >
                            {{ $seat->seat_row }}{{ $seat->seat_number }}
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    {{-- Chú thích loại ghế --}}
    <div class="d-flex justify-content-center gap-4 mt-4 flex-wrap">
        <div class="seat-legend-item d-flex align-items-center gap-2">
            <div class="seat-legend-color seat-standard" style="width: 24px; height: 24px; border-radius: 4px;"></div>
            <span class="text-light small">Ghế thường ({{ number_format($selectedShowtime->price_standard, 0, ',', '.') }} đ)</span>
        </div>

        <div class="seat-legend-item d-flex align-items-center gap-2">
            <div class="seat-legend-color seat-vip" style="width: 24px; height: 24px; border-radius: 4px;"></div>
            <span class="text-light small">Ghế VIP ({{ number_format(round($selectedShowtime->price_standard * 1.2), 0, ',', '.') }} đ)</span>
        </div>

        <div class="seat-legend-item d-flex align-items-center gap-2">
            <div class="seat-legend-color seat-holding" style="width: 24px; height: 24px; border-radius: 4px;"></div>
            <span class="text-light small">Đang giữ chỗ</span>
        </div>

        <div class="seat-legend-item d-flex align-items-center gap-2">
            <div class="seat-legend-color seat-booked" style="width: 24px; height: 24px; border-radius: 4px;"></div>
            <span class="text-light small">Đã bán</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    window.selectedSeatIds = [];

    document.querySelectorAll('.seat-box').forEach(seat => {
        seat.addEventListener('click', function () {
            if (this.classList.contains('seat-holding') || this.classList.contains('seat-booked')) {
                return;
            }

            const seatId = this.dataset.seatId;

            if (this.classList.contains('seat-selected')) {
                this.classList.remove('seat-selected');
                const index = window.selectedSeatIds.indexOf(seatId);
                if (index > -1) {
                    window.selectedSeatIds.splice(index, 1);
                }
            } else {
                this.classList.add('seat-selected');
                window.selectedSeatIds.push(seatId);
            }

            updateBookingInfo();
            if (typeof updateContinueButton === 'function') {
                updateContinueButton();
            }
        });
    });

    function updateBookingInfo() {
        const selectedSeatsElement = document.getElementById('selectedSeats');
        const totalPriceElement = document.getElementById('totalPrice');

        if (selectedSeatsElement) {
            selectedSeatsElement.textContent = window.selectedSeatIds.length;
        }

        if (totalPriceElement) {
            let totalPrice = 0;
            document.querySelectorAll('.seat-selected').forEach(seat => {
                totalPrice += Number(seat.dataset.price);
            });
            totalPriceElement.textContent = totalPrice.toLocaleString('vi-VN') + ' đ';
        }
    }
});
</script>
