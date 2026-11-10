<?php

use App\Models\Booking;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('tickets:release-expired-holds', function () {
    $count = Booking::releaseExpiredBookings();
    $this->info("Đã giải phóng {$count} đơn giữ chỗ quá hạn.");
})->purpose('Giải phóng ghế và hủy các đơn giữ chỗ quá hạn 10 phút');

Schedule::command('tickets:release-expired-holds')->everyMinute();

