<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Models\Movie;
use Illuminate\Database\Eloquent\Collection;

class MovieSuggestionLayout extends Component
{
    public Collection $movies;
    public string $title;
    public function __construct(Collection $movies, string $title)
    {
        $this->movies = $movies;
        $this->title = $title;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.movie-suggestion-layout');
    }
}
