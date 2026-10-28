<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\TicketDetail;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('role', 'CUSTOMER')->first();
        $showtime = Showtime::first();

        if (!$customer || !$showtime) {
            return;
        }

        // Tạo 1 đơn đã thanh toán với 2 vé
        $seat1 = Seat::where('room_id', $showtime->room_id)->where('seat_row', 'A')->where('seat_number', 1)->first();
        $seat2 = Seat::where('room_id', $showtime->room_id)->where('seat_row', 'A')->where('seat_number', 2)->first();

        if ($seat1 && $seat2) {
            $total = $showtime->price_standard * 2;
            $booking = Booking::create([
                'user_id' => $customer->id,
                'total_price' => $total,
                'status' => 'PAID',
                'holding_expires_at' => null,
                'expired_at' => null,
            ]);

            TicketDetail::create([
                'booking_id' => $booking->id,
                'showtime_id' => $showtime->id,
                'seat_id' => $seat1->id,
                'final_price' => $showtime->price_standard,
                'status' => 'BOOKED',
            ]);

            TicketDetail::create([
                'booking_id' => $booking->id,
                'showtime_id' => $showtime->id,
                'seat_id' => $seat2->id,
                'final_price' => $showtime->price_standard,
                'status' => 'BOOKED',
            ]);

            // Tạo giao dịch thanh toán thành công tương ứng
            Payment::create([
                'booking_id' => $booking->id,
                'method' => 'BANK_TRANSFER',
                'status' => 'PAID',
                'amount' => $total,
                'payment_code' => 'PAY-' . strtoupper(Str::random(10)),
                'transaction_code' => 'TXN-' . strtoupper(Str::random(12)),
                'provider' => 'VNPAY',
                'paid_at' => now(),
            ]);
        }

        // Tạo 1 đơn đang giữ chỗ (HOLDING) với 1 ghế VIP
        $vipSeat = Seat::where('room_id', $showtime->room_id)->where('type', 'VIP')->first();
        if ($vipSeat) {
            $vipPrice = round($showtime->price_standard * 1.2);
            $bookingPending = Booking::create([
                'user_id' => $customer->id,
                'total_price' => $vipPrice,
                'status' => 'PENDING',
                'holding_expires_at' => now()->addMinutes(10),
                'expired_at' => now()->addMinutes(10),
            ]);

            TicketDetail::create([
                'booking_id' => $bookingPending->id,
                'showtime_id' => $showtime->id,
                'seat_id' => $vipSeat->id,
                'final_price' => $vipPrice,
                'status' => 'HOLDING',
            ]);
        }
    }
}
