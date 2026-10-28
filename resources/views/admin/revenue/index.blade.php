@extends('layouts.admin')

@section('title', 'Quản lý doanh thu - CineGo Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">📈 Báo cáo & Thống kê Doanh thu</h3>
        <p class="text-muted mb-0">Theo dõi doanh thu thanh toán thực tế, tỷ lệ hoàn tiền và hiệu quả kinh doanh</p>
    </div>
</div>

{{-- Bộ lọc thống kê --}}
<div class="card p-3 mb-4">
    <form method="GET" action="{{ route('admin.revenue.index') }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label text-muted small mb-1">Khoảng thời gian:</label>
            <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="today" {{ $selectedPeriod === 'today' ? 'selected' : '' }}>Hôm nay</option>
                <option value="this_week" {{ $selectedPeriod === 'this_week' ? 'selected' : '' }}>Tuần này</option>
                <option value="this_month" {{ $selectedPeriod === 'this_month' ? 'selected' : '' }}>Tháng này</option>
                <option value="this_year" {{ $selectedPeriod === 'this_year' ? 'selected' : '' }}>Năm nay</option>
                <option value="all" {{ $selectedPeriod === 'all' ? 'selected' : '' }}>Toàn bộ thời gian</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label text-muted small mb-1">Lọc theo phim:</label>
            <select name="movie_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">-- Tất cả phim --</option>
                @foreach($movies as $movie)
                    <option value="{{ $movie->id }}" {{ $selectedMovieId == $movie->id ? 'selected' : '' }}>
                        {{ $movie->title }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label text-muted small mb-1">Từ ngày:</label>
            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
        </div>

        <div class="col-md-2">
            <label class="form-label text-muted small mb-1">Đến ngày:</label>
            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
        </div>

        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary w-100">
                🔍 Lọc
            </button>
            <a href="{{ route('admin.revenue.index') }}" class="btn btn-sm btn-outline-secondary">
                ↺
            </a>
        </div>
    </form>
</div>

{{-- Các thẻ tóm tắt doanh số --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card p-3 rounded border-success">
            <div class="text-success small fw-bold">💰 Doanh thu thuần (Thực nhận)</div>
            <div class="fs-4 fw-bold text-success mt-1">
                {{ number_format($netRevenue, 0, ',', '.') }} đ
            </div>
            <small class="text-muted">Đã trừ các khoản hoàn tiền</small>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card p-3 rounded">
            <div class="text-muted small">💵 Tổng doanh thu gộp</div>
            <div class="fs-4 fw-bold text-white mt-1">
                {{ number_format($grossRevenue, 0, ',', '.') }} đ
            </div>
            <small class="text-muted">Tổng số tiền giao dịch PAID</small>
        </div>
    </div>

    <div class="col-md-2">
        <div class="stat-card p-3 rounded border-danger">
            <div class="text-danger small fw-bold">💸 Đã hoàn lại</div>
            <div class="fs-4 fw-bold text-danger mt-1">
                {{ number_format($totalRefundAmount, 0, ',', '.') }} đ
            </div>
            <small class="text-muted">Hoàn tiền duyệt</small>
        </div>
    </div>

    <div class="col-md-2">
        <div class="stat-card p-3 rounded border-info">
            <div class="text-info small fw-bold">🎟️ Vé bán thành công</div>
            <div class="fs-4 fw-bold text-info mt-1">
                {{ number_format($totalTicketsSold) }}
            </div>
            <small class="text-muted">Ghế BOOKED / USED</small>
        </div>
    </div>

    <div class="col-md-2">
        <div class="stat-card p-3 rounded border-primary">
            <div class="text-primary small fw-bold">📦 Đơn đặt vé</div>
            <div class="fs-4 fw-bold text-primary mt-1">
                {{ number_format($totalPaidBookings) }}
            </div>
            <small class="text-muted">Giao dịch hoàn tất</small>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- Biểu đồ doanh thu theo thời gian --}}
    <div class="col-lg-8">
        <div class="card p-3 h-100">
            <h5 class="fw-bold text-info mb-3">📊 Biểu đồ xu hướng doanh thu</h5>
            @if(count($chartLabels) > 0)
                <canvas id="revenueChart" style="max-height: 280px;"></canvas>
            @else
                <div class="text-center py-5 text-muted">
                    Chưa có đủ dữ liệu giao dịch trong khoảng thời gian này để vẽ biểu đồ.
                </div>
            @endif
        </div>
    </div>

    {{-- Bảng doanh thu theo từng phim --}}
    <div class="col-lg-4">
        <div class="card p-3 h-100">
            <h5 class="fw-bold text-warning mb-3">🎬 Doanh thu theo phim</h5>
            <div class="table-responsive p-0" style="background: transparent; border: none;">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Phim</th>
                            <th class="text-center">Vé</th>
                            <th class="text-end">Doanh thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movieRevenueStats as $item)
                            <tr>
                                <td>
                                    <div class="fw-bold small">{{ $item['movie']->title }}</div>
                                </td>
                                <td class="text-center small">
                                    <span class="badge bg-secondary">{{ $item['tickets'] }}</span>
                                </td>
                                <td class="text-end fw-bold text-success small">
                                    {{ number_format($item['revenue'], 0, ',', '.') }} đ
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-3 text-muted small">
                                    Chưa có số liệu phim.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Bảng chi tiết các giao dịch thanh toán thành công gần nhất --}}
<div class="card p-3">
    <h5 class="fw-bold text-white mb-3">📋 Nhật ký giao dịch thanh toán (PAID)</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Mã giao dịch</th>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Phương thức / Cổng</th>
                    <th>Số tiền</th>
                    <th>Thời điểm thanh toán</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    @php
                        $user = $payment->booking?->user;
                        $hasRefund = $payment->refunds->where('status', 'COMPLETED')->first();
                    @endphp
                    <tr>
                        <td>
                            <strong class="text-primary">{{ $payment->payment_code }}</strong>
                            @if($payment->transaction_code)
                                <div class="small text-muted">{{ $payment->transaction_code }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-dark">#{{ $payment->booking_id }}</span>
                        </td>
                        <td>
                            <strong>{{ $user->full_name ?? $user->username ?? 'Khách' }}</strong>
                            <div class="small text-muted">{{ $user->email ?? '' }}</div>
                        </td>
                        <td>
                            <span class="badge {{ $payment->method === 'E_WALLET' ? 'bg-danger' : ($payment->method === 'CARD' ? 'bg-info' : 'bg-success') }}">
                                {{ $payment->method }}
                            </span>
                            <div class="small text-muted">{{ $payment->provider ?? 'Cổng thanh toán' }}</div>
                        </td>
                        <td>
                            <span class="fw-bold text-success fs-6">
                                {{ number_format($payment->amount, 0, ',', '.') }} đ
                            </span>
                            @if($hasRefund)
                                <div class="small text-danger">(Đã hoàn tiền)</div>
                            @endif
                        </td>
                        <td>
                            {{ $payment->paid_at ? $payment->paid_at->format('H:i d/m/Y') : ($payment->created_at ? $payment->created_at->format('H:i d/m/Y') : 'N/A') }}
                        </td>
                        <td>
                            <span class="badge bg-success">PAID</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            Chưa có giao dịch thanh toán nào phù hợp với điều kiện tìm kiếm.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $payments->links() }}
    </div>
</div>

{{-- Script biểu đồ Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@if(count($chartLabels) > 0)
<script>
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: @json($chartValues),
                borderColor: '#38bdf8',
                backgroundColor: 'rgba(56, 189, 248, 0.15)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#0284c7',
                pointRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('vi-VN') + ' đ';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.raw.toLocaleString('vi-VN') + ' VNĐ';
                        }
                    }
                }
            }
        }
    });
</script>
@endif
@endsection
