@extends('layouts.admin')

@section('title', 'Quản lý phòng chiếu')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-column flex-md-row gap-3">
    <div>
        <h3 class="mb-1 text-light fw-bold">Danh sách phòng chiếu</h3>
        <p class="text-muted mb-0">Quản lý sơ đồ phòng chiếu, số lượng ghế thường và ghế VIP.</p>
    </div>
    <div>
        <a href="{{ route('admin.rooms.create') }}" class="btn btn-success">
            ➕ Thêm phòng mới
        </a>
    </div>
</div>

<div class="table-responsive rounded-3 shadow-sm">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Mã phòng</th>
                <th>Tên phòng</th>
                <th>Tổng số ghế</th>
                <th>Số ghế VIP</th>
                <th class="text-end">Hành động</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rooms as $room)
                <tr>
                    <td><strong>#{{ $room->id }}</strong></td>
                    <td><strong class="text-info">{{ $room->name }}</strong></td>
                    <td>{{ $room->total_seats }} ghế</td>
                    <td>
                        <span class="badge bg-warning text-dark">{{ $room->getVipSeatCount() }} ghế VIP</span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.rooms.show', $room->id) }}" class="btn btn-sm btn-outline-info me-1">Sơ đồ ghế</a>
                        <a href="{{ route('admin.rooms.edit', $room->id) }}" class="btn btn-sm btn-outline-primary me-1">Sửa</a>
                        <form action="{{ route('admin.rooms.destroy', $room->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa phòng chiếu này?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">Chưa có phòng chiếu nào.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
