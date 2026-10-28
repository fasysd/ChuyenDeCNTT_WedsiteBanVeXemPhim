@extends('layouts.admin')

@section('title', $title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1 text-light fw-bold">{{ $title }}</h3>
        <p class="text-muted mb-0">Cung cấp tài khoản cho nhân viên làm việc tại rạp.</p>
    </div>
    <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-light">
        &lt; Quay lại danh sách
    </a>
</div>

<div class="card p-4 border-secondary shadow-sm mb-4">
    <form action="{{ $action }}" method="POST">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                <input type="text" name="username" class="form-control" value="{{ old('username', $staff->username) }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $staff->email) }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Họ và tên</label>
                <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $staff->full_name) }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Số điện thoại</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $staff->phone) }}">
            </div>

            @if(!$isEdit)
                <div class="col-md-6">
                    <label class="form-label">Mật khẩu ban đầu <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
            @endif
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top border-secondary">
            <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-secondary">Hủy</a>
            <button type="submit" class="btn btn-primary px-4">
                {{ $isEdit ? 'Lưu thay đổi' : 'Thêm nhân viên' }}
            </button>
        </div>
    </form>
</div>

@if($isEdit)
    <div class="card p-4 border-secondary shadow-sm">
        <h5 class="text-warning mb-3">Đặt lại mật khẩu cho nhân viên này</h5>
        <form action="{{ route('admin.staff.reset-password', $staff) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Mật khẩu mới</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Xác nhận mật khẩu mới</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
            </div>

            <div class="mt-3 text-end">
                <button type="submit" class="btn btn-warning" onclick="return confirm('Bạn có chắc chắn muốn đặt lại mật khẩu cho nhân viên này?')">
                    Đổi mật khẩu
                </button>
            </div>
        </form>
    </div>
@endif
@endsection
