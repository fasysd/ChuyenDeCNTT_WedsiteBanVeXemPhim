<section class="movie-section mb-5">
    <div class="d-flex justify-content-between align-items-center border-bottom border-danger pb-2 mb-3">
        <h2 class="movie-section-title mb-0">
            {{ $title }}
        </h2>

        {{-- Nút điều hướng chuyển phim sang Trái / Phải --}}
        <div class="slider-nav-btns d-flex gap-2">
            <button type="button" class="btn btn-outline-light btn-sm rounded-circle slider-btn-prev" aria-label="Xem phim trước đó" title="Xem phim trước đó" style="width: 38px; height: 38px; display: none; align-items: center; justify-content: center; font-size: 1.1rem; line-height: 1;">
                &larr;
            </button>
            <button type="button" class="btn btn-outline-light btn-sm rounded-circle slider-btn-next" aria-label="Xem phim tiếp theo" title="Xem phim tiếp theo" style="width: 38px; height: 38px; display: none; align-items: center; justify-content: center; font-size: 1.1rem; line-height: 1;">
                &rarr;
            </button>
        </div>
    </div>

    <div class="movie-slider-container position-relative">
        <div class="movie-slider-track d-flex gap-3 overflow-auto py-2" style="scroll-behavior: smooth;">
            @forelse($movies as $movie)
                <div class="movie-slide-item flex-shrink-0" style="width: 230px;">
                    <x-movie-card
                        :id="$movie->id"
                        :image="$movie->image_path"
                        :title="$movie->title"
                        :release-date="$movie->release_date"
                        :genre="$movie->genre"
                    />
                </div>
            @empty
                <div class="col-12 text-secondary py-3">
                    Hiện chưa có phim nào trong danh mục này.
                </div>
            @endforelse
        </div>
    </div>
</section>
