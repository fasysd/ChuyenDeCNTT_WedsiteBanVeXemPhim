<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
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
        $customers = User::where('role', 'CUSTOMER')->get();
        $showtimes = Showtime::with('room')->get();

        if ($customers->isEmpty() || $showtimes->isEmpty()) {
            return;
        }

        $cust1 = $customers[0];
        $cust2 = $customers[1] ?? $customers[0];
        $cust3 = $customers[2] ?? $customers[0];

        // =========================================================================
        // 1. ĐƠN HÀNG ĐÃ THANH TOÁN (PAID) QUA VNPAY - 2 VÉ THƯỜNG (BOOKED)
        // =========================================================================
        $st1 = $showtimes[0]; // Suất 1 (Phòng 1)
        $seatA1 = Seat::where('room_id', $st1->room_id)->where('seat_row', 'A')->where('seat_number', 1)->first();
        $seatA2 = Seat::where('room_id', $st1->room_id)->where('seat_row', 'A')->where('seat_number', 2)->first();

        if ($seatA1 && $seatA2) {
            $total1 = $st1->price_standard * 2;
            $b1 = Booking::create([
                'user_id' => $cust1->id,
                'total_price' => $total1,
                'status' => 'PAID',
                'created_at' => now()->subHours(5),
            ]);

            TicketDetail::create([
                'booking_id' => $b1->id,
                'showtime_id' => $st1->id,
                'seat_id' => $seatA1->id,
                'final_price' => $st1->price_standard,
                'status' => 'BOOKED',
            ]);

            TicketDetail::create([
                'booking_id' => $b1->id,
                'showtime_id' => $st1->id,
                'seat_id' => $seatA2->id,
                'final_price' => $st1->price_standard,
                'status' => 'BOOKED',
            ]);

            Payment::create([
                'booking_id' => $b1->id,
                'method' => 'BANK_TRANSFER',
                'status' => 'PAID',
                'amount' => $total1,
                'payment_code' => 'PAY-VNPAY-' . strtoupper(Str::random(6)),
                'transaction_code' => 'VNP' . date('YmdHis') . '01',
                'provider' => 'VNPAY',
                'paid_at' => now()->subHours(5),
                'created_at' => now()->subHours(5),
            ]);
        }

        // =========================================================================
        // 2. ĐƠN HÀNG ĐÃ THANH TOÁN (PAID) QUA MOMO - GHẾ VIP ĐÃ QUA SOÁT VÉ (USED)
        // =========================================================================
        $seatE1 = Seat::where('room_id', $st1->room_id)->where('seat_row', 'E')->where('seat_number', 1)->first();
        if ($seatE1) {
            $priceVip = round($st1->price_standard * 1.2);
            $b2 = Booking::create([
                'user_id' => $cust2->id,
                'total_price' => $priceVip,
                'status' => 'PAID',
                'created_at' => now()->subHours(3),
            ]);

            TicketDetail::create([
                'booking_id' => $b2->id,
                'showtime_id' => $st1->id,
                'seat_id' => $seatE1->id,
                'final_price' => $priceVip,
                'status' => 'USED', // Đã check-in qua cổng soát vé
            ]);

            Payment::create([
                'booking_id' => $b2->id,
                'method' => 'E_WALLET',
                'status' => 'PAID',
                'amount' => $priceVip,
                'payment_code' => 'PAY-MOMO-' . strtoupper(Str::random(6)),
                'transaction_code' => 'MOMO' . date('YmdHis') . '02',
                'provider' => 'MOMO',
                'paid_at' => now()->subHours(3),
                'created_at' => now()->subHours(3),
            ]);
        }

        // =========================================================================
        // 3. ĐƠN HÀNG ĐANG GIỮ CHỖ (PENDING) - CÒN 10 PHÚT ĐỂ THANH TOÁN (HOLDING)
        // =========================================================================
        $st2 = $showtimes[1] ?? $st1;
        $seatB1 = Seat::where('room_id', $st2->room_id)->where('seat_row', 'B')->where('seat_number', 1)->first();
        $seatB2 = Seat::where('room_id', $st2->room_id)->where('seat_row', 'B')->where('seat_number', 2)->first();

        if ($seatB1 && $seatB2) {
            $total3 = $st2->price_standard * 2;
            $b3 = Booking::create([
                'user_id' => $cust1->id,
                'total_price' => $total3,
                'status' => 'PENDING',
                'holding_expires_at' => now()->addMinutes(10),
                'expired_at' => now()->addMinutes(10),
                'created_at' => now(),
            ]);

            TicketDetail::create([
                'booking_id' => $b3->id,
                'showtime_id' => $st2->id,
                'seat_id' => $seatB1->id,
                'final_price' => $st2->price_standard,
                'status' => 'HOLDING',
            ]);

            TicketDetail::create([
                'booking_id' => $b3->id,
                'showtime_id' => $st2->id,
                'seat_id' => $seatB2->id,
                'final_price' => $st2->price_standard,
                'status' => 'HOLDING',
            ]);
        }

        // =========================================================================
        // 4. ĐƠN ĐÃ THANH TOÁN & ĐANG CÓ YÊU CẦU HOÀN TIỀN CHỜ DUYỆT (PENDING REFUND)
        // =========================================================================
        $stTomorrow = $showtimes->firstWhere('id', '>', 4) ?? $st1; // Suất chiếu ngày mai (thỏa mãn >= 2 tiếng)
        $seatC1 = Seat::where('room_id', $stTomorrow->room_id)->where('seat_row', 'C')->where('seat_number', 1)->first();
        $seatC2 = Seat::where('room_id', $stTomorrow->room_id)->where('seat_row', 'C')->where('seat_number', 2)->first();

        if ($seatC1 && $seatC2) {
            $total4 = $stTomorrow->price_standard * 2;
            $b4 = Booking::create([
                'user_id' => $cust3->id,
                'total_price' => $total4,
                'status' => 'PAID',
                'created_at' => now()->subHours(2),
            ]);

            TicketDetail::create([
                'booking_id' => $b4->id,
                'showtime_id' => $stTomorrow->id,
                'seat_id' => $seatC1->id,
                'final_price' => $stTomorrow->price_standard,
                'status' => 'BOOKED',
            ]);

            TicketDetail::create([
                'booking_id' => $b4->id,
                'showtime_id' => $stTomorrow->id,
                'seat_id' => $seatC2->id,
                'final_price' => $stTomorrow->price_standard,
                'status' => 'BOOKED',
            ]);

            $pay4 = Payment::create([
                'booking_id' => $b4->id,
                'method' => 'CARD',
                'status' => 'PAID',
                'amount' => $total4,
                'payment_code' => 'PAY-VISA-' . strtoupper(Str::random(6)),
                'transaction_code' => 'VISA' . date('YmdHis') . '04',
                'provider' => 'VISA',
                'paid_at' => now()->subHours(2),
                'created_at' => now()->subHours(2),
            ]);

            // Tạo yêu cầu hoàn tiền đang PENDING
            Refund::create([
                'payment_id' => $pay4->id,
                'amount' => $total4,
                'status' => 'PENDING',
                'refund_code' => 'REF-' . strtoupper(Str::random(8)),
                'created_at' => now()->subMinutes(30),
            ]);
        }

        // =========================================================================
        // 5. ĐƠN ĐÃ ĐƯỢC DUYỆT HOÀN TIỀN (REFUNDED) - GHẾ ĐÃ ĐƯỢC GIẢI PHÓNG (CANCELLED)
        // =========================================================================
        $seatD1 = Seat::where('room_id', $st1->room_id)->where('seat_row', 'D')->where('seat_number', 1)->first();
        if ($seatD1) {
            $total5 = $st1->price_standard;
            $b5 = Booking::create([
                'user_id' => $cust2->id,
                'total_price' => $total5,
                'status' => 'REFUNDED',
                'created_at' => now()->subDays(2),
            ]);

            TicketDetail::create([
                'booking_id' => $b5->id,
                'showtime_id' => $st1->id,
                'seat_id' => $seatD1->id,
                'final_price' => $total5,
                'status' => 'CANCELLED',
            ]);

            $pay5 = Payment::create([
                'booking_id' => $b5->id,
                'method' => 'BANK_TRANSFER',
                'status' => 'PAID',
                'amount' => $total5,
                'payment_code' => 'PAY-VNPAY-REFUNDED',
                'transaction_code' => 'VNP' . date('YmdHis') . '05',
                'provider' => 'VNPAY',
                'paid_at' => now()->subDays(2),
                'created_at' => now()->subDays(2),
            ]);

            Refund::create([
                'payment_id' => $pay5->id,
                'amount' => $total5,
                'status' => 'COMPLETED',
                'refund_code' => 'REF-COMPLETED-01',
                'refunded_at' => now()->subDay(),
                'created_at' => now()->subDays(2),
            ]);
        }

        // =========================================================================
        // 6. TẠO THÊM CÁC ĐƠN HÀNG QUÁ KHỨ ĐỂ BÁO CÁO DOANH THU & BIỂU ĐỒ HIỂN THỊ ĐẸP
        // =========================================================================
        for ($daysAgo = 1; $daysAgo <= 4; $daysAgo++) {
            $stPast = $showtimes[$daysAgo % $showtimes->count()];
            $seatPast = Seat::where('room_id', $stPast->room_id)
                ->where('seat_row', 'D')
                ->where('seat_number', $daysAgo + 2)
                ->first();

            if ($seatPast) {
                $revAmount = $stPast->price_standard;
                $pastDate = now()->subDays($daysAgo)->setTime(10 + $daysAgo * 2, 0);

                $bPast = Booking::create([
                    'user_id' => $cust1->id,
                    'total_price' => $revAmount,
                    'status' => 'PAID',
                    'created_at' => $pastDate,
                ]);

                TicketDetail::create([
                    'booking_id' => $bPast->id,
                    'showtime_id' => $stPast->id,
                    'seat_id' => $seatPast->id,
                    'final_price' => $revAmount,
                    'status' => 'BOOKED',
                    'created_at' => $pastDate,
                ]);

                Payment::create([
                    'booking_id' => $bPast->id,
                    'method' => $daysAgo % 2 === 0 ? 'BANK_TRANSFER' : 'E_WALLET',
                    'status' => 'PAID',
                    'amount' => $revAmount,
                    'payment_code' => 'PAY-STAT-' . strtoupper(Str::random(6)),
                    'transaction_code' => 'TXN-STAT-' . strtoupper(Str::random(8)),
                    'provider' => $daysAgo % 2 === 0 ? 'VNPAY' : 'MOMO',
                    'paid_at' => $pastDate,
                    'created_at' => $pastDate,
                ]);
            }
        }
    }
}
