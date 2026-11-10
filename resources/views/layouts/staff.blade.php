<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Nhân viên soát vé') - CineGo Staff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0b1220; color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; }
        .sidebar { min-height: 100vh; background: #0f172a; border-right: 1px solid #1f2937; }
        .sidebar .nav-link { color: #94a3b8; padding: .65rem 1rem; border-radius: .5rem; margin-bottom: .25rem; font-weight: 500; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { color: #f8fafc; background: rgba(56,189,248,0.15); }
        .card, .content-card { background: #131d2e; border: 1px solid #2a3850; color: #f8fafc; }
        .form-label { color: #f8fafc !important; font-weight: 600; font-size: 0.95rem; margin-bottom: 0.4rem; }
        .form-control, .form-select {
            background-color: #1e293b !important;
            color: #ffffff !important;
            border: 1px solid #475569 !important;
            font-size: 0.95rem;
        }
        .form-control:focus, .form-select:focus {
            background-color: #0f172a !important;
            color: #ffffff !important;
            border-color: #38bdf8 !important;
            box-shadow: 0 0 0 .25rem rgba(56,189,248,.25) !important;
        }
        .form-control::placeholder { color: #94a3b8 !important; opacity: 1; }
        .form-select option { background-color: #1e293b; color: #ffffff; }
        .text-muted { color: #cbd5e1 !important; }
        .table-responsive { background: #ffffff; border-radius: .75rem; padding: 1rem; border: 1px solid #cbd5e1; }
        .table { color: #000000 !important; }
        .table thead th { background: #f8fafc; border-bottom: 1px solid #cbd5e1; color: #000000 !important; }
        .table tbody tr { background: #ffffff; }
        .table tbody td { border-color: #e2e8f0; color: #000000 !important; vertical-align: middle; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            {{-- Sidebar Staff --}}
            <aside class="col-lg-2 col-md-3 sidebar p-3">
                <div class="mb-4 text-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-0 fw-bold text-info">CineGo Staff</h5>
                            <small class="text-muted">Nhân viên soát vé</small>
                        </div>
                        <div class="rounded-circle bg-info text-dark d-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;">
                            {{ substr(Auth::user()->username ?? 'S', 0, 1) }}
                        </div>
                    </div>
                </div>

                <nav class="nav flex-column">
                    <a class="nav-link {{ request()->routeIs('staff.bookings') ? 'active' : '' }}" href="{{ route('staff.bookings') }}">
                        Danh sách đặt vé
                    </a>
                    <a class="nav-link {{ request()->routeIs('staff.tickets*') ? 'active' : '' }}" href="{{ route('staff.tickets') }}">
                        Tra cứu & Soát vé
                    </a>

                    <hr class="border-secondary my-3">

                    <a class="nav-link" href="{{ route('movies') }}" target="_blank">
                        Xem trang người dùng
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100 text-start">
                            Đăng xuất
                        </button>
                    </form>
                </nav>
            </aside>

            {{-- Main Content --}}
            <main class="col-lg-10 col-md-9 p-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show mb-4">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show mb-4">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
