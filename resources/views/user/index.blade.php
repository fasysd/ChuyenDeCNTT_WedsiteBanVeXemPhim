@extends('layouts.app')

@section('content')
@php
    $slides = [
        'https://picsum.photos/1200/400?random=11',
        'https://picsum.photos/1200/400?random=12',
        'https://picsum.photos/1200/400?random=13',
    ];
@endphp

<div class="container-fluid movie-index-page text-light p-0">

    {{-- Banner quảng cáo --}}
    <section class="banner-section">
        <div id="homeBanner" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3500">
            <div class="carousel-inner">
                @foreach($slides as $index => $slide)
                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                        <img src="{{ $slide }}" class="d-block w-100 banner-image" style="max-height: 420px; object-fit: cover;" alt="Banner {{ $index + 1 }}">
                    </div>
                @endforeach
            </div>

            <button class="carousel-control-prev banner-nav" type="button" data-bs-target="#homeBanner" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next banner-nav" type="button" data-bs-target="#homeBanner" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>

            <div class="carousel-indicators">
                @foreach($slides as $index => $slide)
                    <button type="button" data-bs-target="#homeBanner" data-bs-slide-to="{{ $index }}" class="{{ $index === 0 ? 'active' : '' }}"></button>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Các danh mục phim --}}
    <div class="container py-5">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <x-movie-suggestion-layout :movies="$nowShowing" title="Phim đang chiếu tại CineGo" />
        <x-movie-suggestion-layout :movies="$movies" title="Gợi ý cho bạn" />
        <x-movie-suggestion-layout :movies="$upcoming" title="Phim sắp chiếu" />
    </div>

</div>
@endsection
