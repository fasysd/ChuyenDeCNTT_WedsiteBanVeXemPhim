<a href="{{ route('movie.show', $id) }}" class="text-decoration-none text-reset d-block h-100">
    <div class="card movie-card h-100 bg-dark shadow-sm border-secondary">
        <img
            src="{{ $image ? asset($image) : asset('images/movieavatar.webp') }}"
            class="card-img-top"
            alt="{{ $title }}"
            style="height: 240px; object-fit: cover;"
            onerror="this.onerror=null;this.src='{{ asset('images/movieavatar.webp') }}';"
        >

        <div class="card-body d-flex flex-column">
            <h5 class="card-title fw-bold text-light mb-2">
                {{ $title }}
            </h5>

            <p class="text-secondary small mb-3">
                Khởi chiếu: {{ $releaseDate ? \Carbon\Carbon::parse($releaseDate)->format('d/m/Y') : 'Đang cập nhật' }}
            </p>

            <div class="mt-auto">
                @foreach($genres as $g)
                    <span class="badge bg-primary me-1 mb-1">
                        {{ $g }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>
</a>
