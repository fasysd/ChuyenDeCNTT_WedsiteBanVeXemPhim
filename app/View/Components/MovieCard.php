<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class MovieCard extends Component
{
    public array $genres = [];

    public function __construct(
        public int $id,
        public ?string $image,
        public string $title,
        public mixed $releaseDate = null,
        mixed $genre = null
    ) {
        if (is_array($genre)) {
            $this->genres = $genre;
        } elseif (is_string($genre) && trim($genre) !== '') {
            $this->genres = array_map('trim', explode(',', $genre));
        }
    }

    public function render(): View|Closure|string
    {
        return view('components.movie-card');
    }
}
