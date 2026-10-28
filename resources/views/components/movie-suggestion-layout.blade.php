<section class="movie-section mb-5">
    <h2 class="movie-section-title text-light fw-bold border-bottom border-danger pb-2 mb-4">
        {{ $title }}
    </h2>

    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-4">
        @forelse($movies->take(5) as $movie)
            <div class="col">
                <x-movie-card
                    :id="$movie->id"
                    :image="$movie->image_path"
                    :title="$movie->title"
                    :release-date="$movie->release_date"
                    :genre="$movie->genre"
                />
            </div>
        @empty
            <div class="col-12 text-secondary">
                Hiện chưa có phim nào trong danh mục này.
            </div>
        @endforelse
    </div>
</section>
