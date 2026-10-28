@extends('layouts.app')

@section('content')
<div class="account-page">
    <div class="container py-5">
        <div class="row justify-content-center">

            {{-- Sidebar --}}
            <div class="col-lg-3 col-md-4 mb-4">
                <h3 class="account-title">
                    TÀI KHOẢN CineGo
                </h3>

                <div class="list-group">
                    <a href="{{ route('user.account.general') }}" class="list-group-item account-menu active">
                        Thông tin tài khoản
                    </a>
                    <a href="{{ route('user.account.tickets') }}" class="list-group-item account-menu">
                        Vé của tôi
                    </a>
                </div>
            </div>

            {{-- Content --}}
            <div class="col-lg-8 col-md-8">
                <div class="account-content">
                    <div class="account-header">
                        THÔNG TIN TÀI KHOẢN
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success mt-3">{{ session('success') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger mt-3">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="ticket-table-wrapper mt-3 p-4 bg-dark text-white rounded">
                        <div class="mb-4">
                            <p class="mb-2"><strong>Tên đăng nhập:</strong> {{ $user->username }}</p>
                            <p class="mb-2"><strong>Email:</strong> {{ $user->email }}</p>
                            <p class="mb-2"><strong>Họ và tên:</strong> {{ $user->full_name ?? 'Chưa cập nhật' }}</p>
                            <p class="mb-0"><strong>Số điện thoại:</strong> {{ $user->phone ?? 'Chưa cập nhật' }}</p>
                        </div>

                        <hr class="border-secondary my-4">

                        <div>
                            <h5 class="text-warning mb-3">Sửa thông tin tài khoản</h5>
                            <form method="POST" action="{{ route('user.account.detail.update') }}">
                                @csrf
                                @method('PUT')

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Tên đăng nhập</label>
                                        <input type="text" name="username" class="form-control" value="{{ old('username', $user->username) }}" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Họ và tên</label>
                                        <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $user->full_name) }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Số điện thoại</label>
                                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                                    </div>
                                </div>

                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-warning px-4">Lưu thông tin</button>
                                </div>
                            </form>
                        </div>

                        <hr class="border-secondary my-4">

                        <div>
                            <h5 class="text-warning mb-3">Đổi mật khẩu</h5>
                            <form method="POST" action="{{ route('user.account.password.update') }}">
                                @csrf
                                @method('PUT')

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Mật khẩu mới</label>
                                        <input type="password" name="password" class="form-control" placeholder="Tối thiểu 6 ký tự" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Xác nhận mật khẩu mới</label>
                                        <input type="password" name="password_confirmation" class="form-control" placeholder="Nhập lại mật khẩu" required>
                                    </div>
                                </div>

                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-outline-warning px-4">Đổi mật khẩu</button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
