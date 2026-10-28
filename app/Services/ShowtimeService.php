<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use Carbon\Carbon;

class ShowtimeService
{
    public const ERROR_START_TIME = 'START_TIME';
    public const ERROR_BEFORE_RELEASE = 'BEFORE_RELEASE';
    public const ERROR_DURATION = 'DURATION';
    public const ERROR_CONFLICT = 'CONFLICT';
    public const ERROR_HAS_TICKETS = 'HAS_TICKETS';

    public function getAllShowtimes()
    {
        return Showtime::with(['movie', 'room'])
            ->orderBy('start_time', 'desc')
            ->get();
    }

    public function getShowtimeById(int $id)
    {
        return Showtime::with(['movie', 'room'])->findOrFail($id);
    }

    public function createShowtime(array $data)
    {
        $movie = Movie::findOrFail($data['movie_id']);
        Room::findOrFail($data['room_id']);

        $startTime = Carbon::parse($data['start_time']);
        $endTime = Carbon::parse($data['end_time']);

        if (!$this->validateStartTime($startTime)) {
            return self::ERROR_START_TIME;
        }

        if (!$this->validateAfterReleaseDate($startTime, $movie)) {
            return self::ERROR_BEFORE_RELEASE;
        }

        if (!$this->validateShowtimeDuration($startTime, $endTime, $movie->duration)) {
            return self::ERROR_DURATION;
        }

        if (!$this->validateRoomSchedule($data['room_id'], $startTime, $endTime)) {
            return self::ERROR_CONFLICT;
        }

        return Showtime::create([
            'movie_id' => $data['movie_id'],
            'room_id' => $data['room_id'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'price_standard' => $data['price_standard'],
        ]);
    }

    public function updateShowtime(int $id, array $data)
    {
        $showtime = Showtime::findOrFail($id);

        if (!$this->validateNoTicketsExist($showtime)) {
            return self::ERROR_HAS_TICKETS;
        }

        $movie = Movie::findOrFail($data['movie_id']);
        Room::findOrFail($data['room_id']);

        $startTime = Carbon::parse($data['start_time']);
        $endTime = Carbon::parse($data['end_time']);

        if (!$this->validateStartTime($startTime)) {
            return self::ERROR_START_TIME;
        }

        if (!$this->validateAfterReleaseDate($startTime, $movie)) {
            return self::ERROR_BEFORE_RELEASE;
        }

        if (!$this->validateShowtimeDuration($startTime, $endTime, $movie->duration)) {
            return self::ERROR_DURATION;
        }

        if (!$this->validateRoomSchedule($data['room_id'], $startTime, $endTime, $id)) {
            return self::ERROR_CONFLICT;
        }

        $showtime->update([
            'movie_id' => $data['movie_id'],
            'room_id' => $data['room_id'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'price_standard' => $data['price_standard'],
        ]);

        return $showtime->fresh();
    }

    public function deleteShowtime(int $id)
    {
        $showtime = Showtime::findOrFail($id);

        if (!$this->validateNoTicketsExist($showtime)) {
            return self::ERROR_HAS_TICKETS;
        }

        return $showtime->delete();
    }

    private function validateRoomSchedule(
        int $roomId,
        Carbon $startTime,
        Carbon $endTime,
        ?int $ignoreShowtimeId = null
    ): bool {
        $query = Showtime::where('room_id', $roomId);

        if ($ignoreShowtimeId !== null) {
            $query->where('id', '!=', $ignoreShowtimeId);
        }

        $conflict = $query
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->exists();

        return !$conflict;
    }

    private function validateShowtimeDuration(
        Carbon $startTime,
        Carbon $endTime,
        int $movieDuration
    ): bool {
        if (!$endTime->greaterThan($startTime)) {
            return false;
        }

        return $startTime->diffInMinutes($endTime) >= $movieDuration;
    }

    private function validateAfterReleaseDate(
        Carbon $startTime,
        Movie $movie
    ): bool {
        if (!$movie->release_date) {
            return true;
        }
        return $startTime->greaterThanOrEqualTo(Carbon::parse($movie->release_date)->startOfDay());
    }

    private function validateStartTime(Carbon $startTime): bool
    {
        return !$startTime->isPast();
    }

    private function validateNoTicketsExist(Showtime $showtime): bool
    {
        return !$showtime->ticketDetails()->exists();
    }
}
