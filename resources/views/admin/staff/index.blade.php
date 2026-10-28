@extends('layouts.admin')

@section('title', 'Quản lý nhân viên')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-column flex-md-row gap-3">
    <div>
        <h3 class="mb-1 text-light fw-bold">Danh sách nhân viên</h3>
        <p class="text-muted mb-0">Quản lý tài khoản nhân viên soát vé tại rạp.</p>
    </div>
    <div>
        <a href="{{ route('admin.staff.create') }}" class="btn btn-success">
            ➕ Thêm nhân viên mới
        </a>
    </div>
</div>

<div class="table-responsive rounded-3 shadow-sm">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Mã NV</th>
                <th>Tên đăng nhập</th>
                <th>Họ và tên</th>
                <th>Email</th>
                <th>Số điện thoại</th>
                <th>Vai trò</th>
                <th class="text-end">Hành động</th>
            </tr>
        </thead>
        <tbody>
            @forelse($staffMembers as $staff)
                <tr>
                    <td><strong>#{{ $staff->id }}</strong></td>
                    <td><strong class="text-info">{{ $staff->username }}</strong></td>
                    <td>{{ $staff->full_name ?? 'Chưa cập nhật' }}</td>
                    <td>{{ $staff->email }}</td>
                    <td>{{ $staff->phone ?? 'Chưa cập nhật' }}</td>
                    <td>
                        <span class="badge bg-info text-dark">STAFF</span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.staff.edit', $staff) }}" class="btn btn-sm btn-outline-primary">Sửa / Đổi mật khẩu</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">Chưa có nhân viên nào.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
