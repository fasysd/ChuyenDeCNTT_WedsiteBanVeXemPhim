@extends('layouts.admin')

@section('title', 'Thông tin tài khoản Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1 text-light fw-bold">Thông tin tài khoản Admin</h3>
        <p class="text-muted mb-0">Cập nhật thông tin quản trị viên và đổi mật khẩu.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card p-4 border-secondary shadow-sm">
            <h5 class="text-info mb-3">Thông tin cá nhân</h5>
            <form method="POST" action="{{ route('user.account.detail.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Tên đăng nhập</label>
                    <input type="text" name="username" class="form-control" value="{{ old('username', $user->username) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Họ và tên</label>
                    <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $user->full_name) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-primary px-4">Lưu thông tin</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card p-4 border-secondary shadow-sm">
            <h5 class="text-warning mb-3">Đổi mật khẩu</h5>
            <form method="POST" action="{{ route('user.account.password.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Mật khẩu mới</label>
                    <input type="password" name="password" class="form-control" placeholder="Tối thiểu 6 ký tự" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Xác nhận mật khẩu mới</label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Nhập lại mật khẩu mới" required>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-warning px-4">Đổi mật khẩu</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
