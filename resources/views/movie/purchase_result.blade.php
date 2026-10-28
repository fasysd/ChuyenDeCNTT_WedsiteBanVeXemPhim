@extends('layouts.app')

@section('content')
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center">

    <div class="card bg-dark text-white border-secondary shadow-lg" style="max-width: 700px; width: 100%;">

        <div class="card-body text-center p-5">

            <div class="display-1 mb-3">
                🎬
            </div>

            <h1 class="text-success fw-bold mb-3">
                Đặt vé thành công!
            </h1>

            <p class="text-light fs-5 mb-4">
                Vé của bạn đã được ghi nhận trong hệ thống.
                Bạn có thể xem chi tiết trong mục
                <strong class="text-warning">Vé của tôi</strong>.
            </p>

            <div class="alert alert-success">
                Cảm ơn bạn đã sử dụng dịch vụ của CineGo.
            </div>

            <div class="d-flex justify-content-center gap-3 mt-4">

                <a href="{{ route('user.account.tickets') }}"
                   class="btn btn-warning btn-lg">
                    Vé của tôi
                </a>

                <a href="{{ route('movies') }}"
                   class="btn btn-outline-light btn-lg">
                    Danh sách phim
                </a>

            </div>

        </div>

    </div>

</div>
@endsection