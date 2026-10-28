@extends('layouts.app')

@section('content')
<div class="auth-page">
    <div class="container">
        <div class="row justify-content-center g-4 align-items-stretch">
            <div class="col-lg-5 d-flex">
                <div class="auth-card w-100">
                    <div class="auth-tabs">
                        <a href="{{ route('login') }}" class="auth-tab active ajax-link">ĐĂNG NHẬP</a>
                        <a href="{{ route('register') }}" class="auth-tab ajax-link">ĐĂNG KÝ</a>
                    </div>

                    <form class="auth-form" method="POST" action="{{ route('login.post') }}">
                        @csrf

                        <h2 class="login-title">Chào mừng trở lại</h2>
                        <p class="login-subtitle">Đăng nhập để đặt vé, xem lịch sử giao dịch và nhận ưu đãi thành viên CineGo.</p>

                        @if($errors->any())
                            <div class="alert alert-danger">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        @if(session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <div class="mb-4">
                            <label class="form-label">Tên đăng nhập</label>
                            <input type="text" name="username" class="form-control auth-input" placeholder="Nhập tên đăng nhập" value="{{ old('username') }}" required autofocus>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Mật khẩu</label>
                            <input type="password" name="password" class="form-control auth-input" placeholder="Nhập mật khẩu" required>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                                <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                            </div>
                        </div>

                        <button class="btn-main" type="submit">ĐĂNG NHẬP</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-6 d-flex">
                <div class="promo-box w-100">
                    <div class="promo-content">
                        <div class="promo-badge">THÀNH VIÊN CINEGO+</div>
                        <h2 class="promo-title">Đặt vé nhanh hơn, nhận ưu đãi nhiều hơn</h2>
                        <p class="promo-desc">Trải nghiệm hệ thống đặt vé hiện đại, lưu lịch sử giao dịch, theo dõi các bộ phim yêu thích và nhận những chương trình khuyến mại đặc quyền dành riêng cho thành viên CineGo.</p>
                        <div class="promo-stats">
                            <div>
                                <div class="promo-stat-value">500+</div>
                                <div class="promo-stat-label">Bộ phim</div>
                            </div>

                            <div>
                                <div class="promo-stat-value">1M+</div>
                                <div class="promo-stat-label">Thành viên</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
