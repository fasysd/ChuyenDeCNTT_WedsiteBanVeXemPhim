@extends('layouts.app')

@section('content')

<div class="about-page">

    {{-- Hero --}}
    <section class="about-hero">
        <div class="container text-center">

            <h1 class="about-title">
                Chào mừng đến với CineGo
            </h1>

            <p class="about-subtitle">
                Nền tảng đặt vé xem phim trực tuyến hiện đại, nhanh chóng
                và tiện lợi dành cho mọi tín đồ điện ảnh.
            </p>

        </div>
    </section>

    {{-- Giới thiệu --}}
    <section class="about-section">
        <div class="container">

            <div class="row align-items-center">

                <div class="col-lg-6">
                    <img
                        src="https://picsum.photos/800/500"
                        class="img-fluid rounded shadow"
                        alt="Cinema">
                </div>

                <div class="col-lg-6">

                    <h2 class="section-title">
                        Về CineGo
                    </h2>

                    <p class="section-text">
                        CineGo được xây dựng với mục tiêu mang đến trải nghiệm
                        đặt vé điện ảnh nhanh chóng, minh bạch và tiện lợi.
                        Người dùng có thể dễ dàng tìm kiếm phim, lựa chọn rạp,
                        đặt ghế và thanh toán chỉ trong vài phút.
                    </p>

                    <p class="section-text">
                        Chúng tôi không ngừng cải thiện nền tảng nhằm kết nối
                        khán giả với những bộ phim hấp dẫn nhất tại các cụm rạp
                        trên toàn quốc.
                    </p>

                </div>

            </div>

        </div>
    </section>

    {{-- Giá trị --}}
    <section class="about-features">
        <div class="container">

            <h2 class="text-center section-title mb-5">
                Giá trị chúng tôi mang lại
            </h2>

            <div class="row g-4">

                <div class="col-lg-4">
                    <div class="feature-card">

                        <div class="feature-icon">
                            🎬
                        </div>

                        <h4>Kho phim đa dạng</h4>

                        <p>
                            Cập nhật liên tục các bộ phim đang chiếu
                            và sắp ra mắt từ nhiều thể loại khác nhau.
                        </p>

                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="feature-card">

                        <div class="feature-icon">
                            ⚡
                        </div>

                        <h4>Đặt vé nhanh chóng</h4>

                        <p>
                            Giao diện trực quan giúp người dùng
                            hoàn tất việc đặt vé chỉ trong vài bước.
                        </p>

                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="feature-card">

                        <div class="feature-icon">
                            🔒
                        </div>

                        <h4>Thanh toán an toàn</h4>

                        <p>
                            Hỗ trợ nhiều phương thức thanh toán
                            với tiêu chuẩn bảo mật cao.
                        </p>

                    </div>
                </div>

            </div>

        </div>
    </section>

    {{-- Thống kê --}}
    <section class="about-stats">
        <div class="container">

            <div class="row text-center">

                <div class="col-md-3">
                    <h2>100K+</h2>
                    <p>Người dùng</p>
                </div>

                <div class="col-md-3">
                    <h2>500+</h2>
                    <p>Bộ phim</p>
                </div>

                <div class="col-md-3">
                    <h2>50+</h2>
                    <p>Cụm rạp</p>
                </div>

                <div class="col-md-3">
                    <h2>99%</h2>
                    <p>Khách hàng hài lòng</p>
                </div>

            </div>

        </div>
    </section>

    {{-- CTA --}}
    <section class="about-cta">

        <div class="container text-center">

            <h2 class="mb-3">
                Sẵn sàng cho bộ phim tiếp theo?
            </h2>

            <p class="mb-4">
                Khám phá hàng trăm bộ phim hấp dẫn và đặt vé ngay hôm nay.
            </p>

            <a
                href="{{ route('movies') }}"
                class="btn btn-success btn-lg">
                Khám phá phim
            </a>

        </div>

    </section>

</div>

@endsection