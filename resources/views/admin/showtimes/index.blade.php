@extends('layouts.admin')

@section('title', 'Quản lý lịch chiếu')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-column flex-md-row gap-3">
    <div>
        <h3 class="mb-1 text-light fw-bold">Danh sách lịch chiếu</h3>
        <p class="text-muted mb-0">Quản lý các suất chiếu của phim tại các phòng.</p>
    </div>
    <div>
        <a href="{{ route('admin.showtimes.create') }}" class="btn btn-success">
            ➕ Thêm lịch chiếu mới
        </a>
    </div>
</div>

<div class="table-responsive rounded-3 shadow-sm">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Mã</th>
                <th>Phim</th>
                <th>Phòng</th>
                <th>Thời gian bắt đầu</th>
                <th>Thời gian kết thúc</th>
                <th>Giá vé chuẩn</th>
                <th class="text-end">Hành động</th>
            </tr>
        </thead>
        <tbody>
            @forelse($showtimes as $showtime)
                <tr>
                    <td><strong>#{{ $showtime->id }}</strong></td>
                    <td>
                        <strong class="text-info">{{ $showtime->movie?->title ?? 'N/A' }}</strong>
                    </td>
                    <td>{{ $showtime->room?->name ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($showtime->start_time)->format('H:i - d/m/Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($showtime->end_time)->format('H:i - d/m/Y') }}</td>
                    <td>{{ number_format($showtime->price_standard, 0, ',', '.') }} đ</td>
                    <td class="text-end">
                        <a href="{{ route('admin.showtimes.edit', $showtime->id) }}" class="btn btn-sm btn-outline-primary me-1">Sửa</a>
                        <form action="{{ route('admin.showtimes.destroy', $showtime->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa lịch chiếu này?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">Chưa có lịch chiếu nào.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
