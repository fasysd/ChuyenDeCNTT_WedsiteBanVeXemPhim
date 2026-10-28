@extends('layouts.app')

@section('content')
<div class="container py-5 min-vh-100">
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <h2 class="text-center text-white mb-4 fw-bold">
        XÁC NHẬN THÔNG TIN ĐẶT VÉ
    </h2>

    <div class="card bg-dark text-white p-4 shadow-lg border-secondary">
        <div class="row g-4">
            <div class="col-md-3 text-center">
                <img
                    src="{{ $movie->image_path ? asset($movie->image_path) : asset('images/movieavatar.webp') }}"
                    alt="{{ $movie->title }}"
                    class="rounded shadow img-fluid"
                    style="max-height: 320px; object-fit: cover;"
                    onerror="this.onerror=null;this.src='{{ asset('images/movieavatar.webp') }}';"
                >
            </div>

            <div class="col-md-9">
                <h3 class="text-warning fw-bold mb-3">
                    {{ $movie->title }}
                </h3>

                <div class="row mb-3">
                    <div class="col-sm-6">
                        <span class="text-secondary">Thể loại:</span>
                        <strong>{{ $movie->genre ?? 'N/A' }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-secondary">Thời lượng:</span>
                        <strong>{{ $movie->duration }} phút</strong>
                    </div>
                </div>

                <hr class="border-secondary">

                <h5 class="text-info mb-3">
                    Thông tin suất chiếu
                </h5>

                <div class="row mb-3">
                    <div class="col-sm-6">
                        <span class="text-secondary">Phòng chiếu:</span>
                        <strong>{{ $showtime->room->name ?? 'Phòng ' . $showtime->room_id }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-secondary">Thời gian:</span>
                        <strong>
                            {{ \Carbon\Carbon::parse($showtime->start_time)->format('H:i - d/m/Y') }}
                        </strong>
                    </div>
                </div>

                <hr class="border-secondary">

                <h5 class="text-info mb-3">
                    Danh sách ghế chọn & Giá vé
                </h5>

                <div class="table-responsive mb-3">
                    <table class="table table-dark table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Vị trí ghế</th>
                                <th>Loại ghế</th>
                                <th class="text-end">Đơn giá</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($selectedSeats as $seat)
                                <tr>
                                    <td>
                                        <strong>Ghế {{ $seat->seat_row }}{{ $seat->seat_number }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge {{ $seat->type === 'VIP' ? 'bg-warning text-dark' : 'bg-primary' }}">
                                            {{ $seat->type }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($seat->calculated_price, 0, ',', '.') }} đ
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="table-active">
                                <td colspan="2" class="text-end fw-bold fs-5 text-warning">
                                    Tổng cộng:
                                </td>
                                <td class="text-end fw-bold fs-5 text-warning">
                                    {{ number_format($totalPrice, 0, ',', '.') }} đ
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-secondary text-light small">
                    * Lưu ý: Sau khi bấm "Xác nhận đặt vé", vé sẽ được giữ chỗ trong 10 phút để bạn hoàn tất thanh toán.
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <a href="{{ route('ticket.booking', $movie->id) }}?showtime={{ $showtime->id }}" class="btn btn-outline-light px-4 py-2">
            &lt; Chọn lại ghế
        </a>

        <form action="{{ route('ticket.store', $movie->id) }}" method="POST">
            @csrf
            <input type="hidden" name="showtime_id" value="{{ $showtime->id }}">
            @foreach($selectedSeats as $seat)
                <input type="hidden" name="seat_ids[]" value="{{ $seat->id }}">
            @endforeach

            <button type="submit" class="btn btn-danger btn-lg px-5" onclick="return confirm('Bạn xác nhận muốn đặt các ghế này?')">
                Xác nhận đặt vé
            </button>
        </form>
    </div>
</div>
@endsection
