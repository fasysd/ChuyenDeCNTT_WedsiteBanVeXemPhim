<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CineGo') }} - Đặt vé xem phim trực tuyến</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/user/index.css') }}">
    <link rel="stylesheet" href="{{ asset('css/page/movie.css') }}">
    <link rel="stylesheet" href="{{ asset('css/page/ticket.css') }}">
    <link rel="stylesheet" href="{{ asset('css/page/booking.css') }}">
    <link rel="stylesheet" href="{{ asset('css/page/seat.css') }}">
    <link rel="stylesheet" href="{{ asset('css/user/account.css') }}">
    <link rel="stylesheet" href="{{ asset('css/user/about.css') }}">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="logo text-decoration-none" href="{{ route('movies') }}">
                CineGo
            </a>

            <div class="d-flex align-items-center ms-4">
                <a href="{{ route('movies') }}" class="nav-link-custom">Phim</a>
                <a href="{{ route('user.help.about') }}" class="nav-link-custom">Giới thiệu rạp</a>
                @auth
                    @if(Auth::user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="nav-link-custom text-warning">Trang Quản trị</a>
                    @elseif(Auth::user()->isStaff())
                        <a href="{{ route('staff.bookings') }}" class="nav-link-custom text-info">Soát vé (Staff)</a>
                    @else
                        <a href="{{ route('user.account.tickets') }}" class="nav-link-custom">Vé của tôi</a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="nav-link-custom">Vé của tôi</a>
                @endauth
            </div>

            <div class="d-flex align-items-center ms-auto">
                @auth
                    <div class="dropdown">
                        <a class="nav-link-custom dropdown-toggle text-decoration-none" data-bs-toggle="dropdown" href="#" role="button">
                            👤 {{ Auth::user()->full_name ?: Auth::user()->username }}
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                            @if(Auth::user()->isAdmin())
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                        ⚙️ Trang quản trị
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.staff.index') }}">
                                        👥 Quản lý nhân viên
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                            @elseif(Auth::user()->isStaff())
                                <li>
                                    <a class="dropdown-item" href="{{ route('staff.bookings') }}">
                                        📋 Danh sách đặt vé
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('staff.tickets') }}">
                                        🎟️ Tra cứu / Soát vé
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                            @else
                                <li>
                                    <a class="dropdown-item" href="{{ route('user.account.general') }}">
                                        👤 Thông tin tài khoản
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('user.account.tickets') }}">
                                        🎟️ Vé của tôi
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                            @endif

                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        🚪 Đăng xuất
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="nav-link-custom me-2">Đăng nhập</a>
                    <a href="{{ route('register') }}" class="btn btn-sm btn-outline-warning">Đăng ký</a>
                @endauth
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer class="bg-black text-secondary py-4 mt-5 border-top border-dark text-center">
        <div class="container">
            <p class="mb-1 text-light fw-bold">CineGo - Hệ Thống Đặt Vé Xem Phim Trực Tuyến</p>
            <small>Đồ án học tập môn Lập trình Web với Laravel & Bootstrap</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
