<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Movie;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\TicketDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RemainingBusinessAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private User $customer;
    private Movie $movie;
    private Room $room;
    private Seat $seat1;
    private Seat $seat2;
    private Showtime $showtime;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'username' => 'admin_audit',
            'email' => 'admin_audit@example.com',
            'password' => 'password123',
            'role' => 'ADMIN',
        ]);

        $this->staff = User::create([
            'username' => 'staff_audit',
            'email' => 'staff_audit@example.com',
            'password' => 'password123',
            'role' => 'STAFF',
        ]);

        $this->customer = User::create([
            'username' => 'customer_audit',
            'email' => 'customer_audit@example.com',
            'password' => 'password123',
            'role' => 'CUSTOMER',
        ]);

        $this->movie = Movie::create([
            'title' => 'Audit Movie',
            'duration' => 120,
            'release_date' => now()->subDays(5),
        ]);

        $this->room = Room::create([
            'name' => 'Phòng Audit',
            'total_seats' => 2,
        ]);

        $this->seat1 = Seat::create([
            'room_id' => $this->room->id,
            'seat_row' => 'A',
            'seat_number' => 1,
            'type' => 'STANDARD',
        ]);

        $this->seat2 = Seat::create([
            'room_id' => $this->room->id,
            'seat_row' => 'A',
            'seat_number' => 2,
            'type' => 'VIP',
        ]);

        $this->showtime = Showtime::create([
            'movie_id' => $this->movie->id,
            'room_id' => $this->room->id,
            'start_time' => now()->addHours(5),
            'end_time' => now()->addHours(7),
            'price_standard' => 80000,
        ]);
    }

    public function test_staff_cannot_cancel_booked_ticket_and_search_by_booking_returns_all_tickets(): void
    {
        $booking = Booking::create([
            'user_id' => $this->customer->id,
            'total_price' => 180000,
            'status' => 'PAID',
        ]);

        $ticket1 = TicketDetail::create([
            'booking_id' => $booking->id,
            'showtime_id' => $this->showtime->id,
            'seat_id' => $this->seat1->id,
            'final_price' => 80000,
            'status' => 'BOOKED',
        ]);

        $ticket2 = TicketDetail::create([
            'booking_id' => $booking->id,
            'showtime_id' => $this->showtime->id,
            'seat_id' => $this->seat2->id,
            'final_price' => 100000,
            'status' => 'BOOKED',
        ]);

        // Nhân viên không thể chuyển BOOKED -> CANCELLED
        $response = $this->actingAs($this->staff)
            ->post(route('staff.tickets.updateStatus', $ticket1->id), [
                'status' => 'CANCELLED',
            ]);

        $response->assertSessionHasErrors('status');
        $this->assertEquals('BOOKED', $ticket1->fresh()->status);

        // Nhân viên check-in BOOKED -> USED thành công
        $checkinResponse = $this->actingAs($this->staff)
            ->post(route('staff.tickets.updateStatus', $ticket1->id), [
                'status' => 'USED',
            ]);

        $checkinResponse->assertRedirect(route('staff.tickets.show', $ticket1->id));
        $this->assertEquals('USED', $ticket1->fresh()->status);

        // Tìm theo Booking ID trả về đầy đủ cả 2 vé của đơn đó
        $searchResponse = $this->actingAs($this->staff)
            ->get(route('staff.tickets', ['q' => (string) $booking->id]));

        $searchResponse->assertOk();
        $searchResponse->assertViewHas('matchedTickets', function ($matchedTickets) use ($ticket1, $ticket2) {
            return $matchedTickets->count() === 2
                && $matchedTickets->contains('id', $ticket1->id)
                && $matchedTickets->contains('id', $ticket2->id);
        });
    }

    public function test_refund_rules_block_used_tickets_allow_retry_after_failed_and_block_second_pending(): void
    {
        $booking = Booking::create([
            'user_id' => $this->customer->id,
            'total_price' => 80000,
            'status' => 'PAID',
        ]);

        $ticket = TicketDetail::create([
            'booking_id' => $booking->id,
            'showtime_id' => $this->showtime->id,
            'seat_id' => $this->seat1->id,
            'final_price' => 80000,
            'status' => 'USED',
        ]);

        // Không được hoàn tiền nếu có vé USED
        $this->assertTrue($booking->hasUsedTickets());
        $this->assertFalse($booking->canRequestRefund());

        $usedResponse = $this->actingAs($this->customer)
            ->post(route('user.account.bookings.refund', $booking->id));
        $usedResponse->assertSessionHas('error');

        // Đổi lại vé thành BOOKED để kiểm tra rule retry sau FAILED & chặn 2 PENDING
        $ticket->update(['status' => 'BOOKED']);
        $booking->refresh();
        $this->assertTrue($booking->canRequestRefund());

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => 'PAY-REFUND-1',
            'method' => 'CARD',
            'amount' => 80000,
            'status' => 'PAID',
            'paid_at' => now()->subMinutes(30),
        ]);

        // Có 1 refund FAILED -> vẫn được phép gửi lại
        Refund::create([
            'payment_id' => $payment->id,
            'refund_code' => 'REF-OLD-1',
            'amount' => 80000,
            'reason' => 'Yêu cầu cũ bị từ chối',
            'status' => 'FAILED',
        ]);

        $booking->refresh();
        $this->assertTrue($booking->canRequestRefund());

        $retryResponse = $this->actingAs($this->customer)
            ->post(route('user.account.bookings.refund', $booking->id), [
                'reason' => 'Gửi lại lần 2',
            ]);

        $retryResponse->assertSessionHas('success');
        $this->assertEquals(1, Refund::where('payment_id', $payment->id)->where('status', 'PENDING')->count());

        // Đã có 1 PENDING -> không được tạo thêm PENDING thứ 2
        $booking->refresh();
        $this->assertFalse($booking->canRequestRefund());

        $duplicatePendingResponse = $this->actingAs($this->customer)
            ->post(route('user.account.bookings.refund', $booking->id), [
                'reason' => 'Spam hoàn tiền',
            ]);
        $duplicatePendingResponse->assertSessionHas('error');
        $this->assertEquals(1, Refund::where('payment_id', $payment->id)->where('status', 'PENDING')->count());
    }

    public function test_ticket_index_automatically_releases_expired_pending_bookings(): void
    {
        $expiredBooking = Booking::create([
            'user_id' => $this->customer->id,
            'total_price' => 80000,
            'status' => 'PENDING',
            'holding_expires_at' => now()->subMinutes(5),
        ]);

        $holdingTicket = TicketDetail::create([
            'booking_id' => $expiredBooking->id,
            'showtime_id' => $this->showtime->id,
            'seat_id' => $this->seat1->id,
            'final_price' => 80000,
            'status' => 'HOLDING',
        ]);

        $response = $this->actingAs($this->customer)->get(route('user.account.tickets'));
        $response->assertOk();

        $this->assertEquals('CANCELLED', $expiredBooking->fresh()->status);
        $this->assertEquals('CANCELLED', $holdingTicket->fresh()->status);
    }

    public function test_admin_revenue_filters_by_paid_at_handles_invalid_and_reversed_dates_and_caps_chart_to_30_days(): void
    {
        $otherMovie = Movie::create([
            'title' => 'Old Paid Movie',
            'duration' => 110,
            'release_date' => now()->subDays(60),
        ]);
        $otherShowtime = Showtime::create([
            'movie_id' => $otherMovie->id,
            'room_id' => $this->room->id,
            'start_time' => now()->addHours(10),
            'end_time' => now()->addHours(12),
            'price_standard' => 90000,
        ]);

        // Đơn trong khoảng ngày lọc (paid_at = hôm nay)
        $inRangeBooking = Booking::create([
            'user_id' => $this->customer->id,
            'total_price' => 80000,
            'status' => 'PAID',
        ]);
        TicketDetail::create([
            'booking_id' => $inRangeBooking->id,
            'showtime_id' => $this->showtime->id,
            'seat_id' => $this->seat1->id,
            'final_price' => 80000,
            'status' => 'BOOKED',
        ]);
        Payment::create([
            'booking_id' => $inRangeBooking->id,
            'payment_code' => 'PAY-REV-IN',
            'method' => 'CARD',
            'amount' => 80000,
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        // Đơn ngoài khoảng ngày lọc (paid_at = 45 ngày trước, nhưng created_at của ticket là hôm nay)
        $outRangeBooking = Booking::create([
            'user_id' => $this->customer->id,
            'total_price' => 90000,
            'status' => 'PAID',
        ]);
        TicketDetail::create([
            'booking_id' => $outRangeBooking->id,
            'showtime_id' => $otherShowtime->id,
            'seat_id' => $this->seat2->id,
            'final_price' => 90000,
            'status' => 'BOOKED',
        ]);
        Payment::create([
            'booking_id' => $outRangeBooking->id,
            'payment_code' => 'PAY-REV-OUT',
            'method' => 'CARD',
            'amount' => 90000,
            'status' => 'PAID',
            'paid_at' => now()->subDays(45),
        ]);

        // Tạo thêm 34 ngày payment khác để kiểm tra giới hạn 30 ngày gần nhất cho biểu đồ
        for ($d = 1; $d <= 34; $d++) {
            $b = Booking::create([
                'user_id' => $this->customer->id,
                'total_price' => 10000,
                'status' => 'PAID',
            ]);
            Payment::create([
                'booking_id' => $b->id,
                'payment_code' => 'PAY-REV-DAY-' . $d,
                'method' => 'CARD',
                'amount' => 10000,
                'status' => 'PAID',
                'paid_at' => now()->subDays($d),
            ]);
        }

        // 1. Filter trong 7 ngày gần nhất: chỉ tính $inRangeBooking cho movieStats & totalTicketsSold
        $filterResponse = $this->actingAs($this->admin)->get(route('admin.revenue.index', [
            'period' => 'custom',
            'from_date' => now()->subDays(2)->toDateString(),
            'to_date' => now()->toDateString(),
        ]));

        $filterResponse->assertOk();
        $filterResponse->assertViewHas('totalTicketsSold', 1);
        $filterResponse->assertViewHas('movieRevenueStats', function ($stats) {
            return count($stats) === 1
                && $stats[0]['movie']->id === $this->movie->id
                && (int) $stats[0]['tickets'] === 1;
        });

        // 2. Ngày không hợp lệ & ngày đảo ngược không gây 500
        $invalidResponse = $this->actingAs($this->admin)->get(route('admin.revenue.index', [
            'period' => 'custom',
            'from_date' => 'invalid-date',
            'to_date' => 'also-bad',
        ]));
        $invalidResponse->assertOk();

        $reversedResponse = $this->actingAs($this->admin)->get(route('admin.revenue.index', [
            'period' => 'custom',
            'from_date' => now()->toDateString(),
            'to_date' => now()->subDays(3)->toDateString(),
        ]));
        $reversedResponse->assertOk();
        $reversedResponse->assertViewHas('totalTicketsSold', 1);

        // 3. Biểu đồ tối đa 30 ngày gần nhất theo thứ tự tăng dần
        $allResponse = $this->actingAs($this->admin)->get(route('admin.revenue.index', [
            'period' => 'all',
        ]));
        $allResponse->assertOk();
        $allResponse->assertViewHas('chartLabels', function ($labels) {
            return count($labels) === 30 && end($labels) === now()->format('d/m');
        });
    }

    public function test_home_keeps_now_showing_and_upcoming_separate_and_ranks_blockbusters_by_paid_at(): void
    {
        // Chỉ có phim đang chiếu ($this->movie release 5 ngày trước), không có phim sắp chiếu
        $oldMovie = Movie::create([
            'title' => 'Old Blockbuster',
            'duration' => 100,
            'release_date' => now()->subDays(30),
        ]);
        $oldShowtime = Showtime::create([
            'movie_id' => $oldMovie->id,
            'room_id' => $this->room->id,
            'start_time' => now()->addHours(8),
            'end_time' => now()->addHours(10),
            'price_standard' => 150000,
        ]);

        // Vé phim cũ thanh toán từ 3 tháng trước (nên KHÔNG được tính vào doanh thu blockbuster từ đầu tháng trước)
        $oldBooking = Booking::create([
            'user_id' => $this->customer->id,
            'total_price' => 150000,
            'status' => 'PAID',
        ]);
        TicketDetail::create([
            'booking_id' => $oldBooking->id,
            'showtime_id' => $oldShowtime->id,
            'seat_id' => $this->seat2->id,
            'final_price' => 150000,
            'status' => 'BOOKED',
        ]);
        Payment::create([
            'booking_id' => $oldBooking->id,
            'payment_code' => 'PAY-HOME-OLD',
            'method' => 'CARD',
            'amount' => 150000,
            'status' => 'PAID',
            'paid_at' => now()->subMonths(3),
        ]);

        // Vé phim Audit Movie thanh toán hôm nay -> phải đứng #1 blockbusterMovies
        $recentBooking = Booking::create([
            'user_id' => $this->customer->id,
            'total_price' => 80000,
            'status' => 'PAID',
        ]);
        TicketDetail::create([
            'booking_id' => $recentBooking->id,
            'showtime_id' => $this->showtime->id,
            'seat_id' => $this->seat1->id,
            'final_price' => 80000,
            'status' => 'BOOKED',
        ]);
        Payment::create([
            'booking_id' => $recentBooking->id,
            'payment_code' => 'PAY-HOME-NEW',
            'method' => 'CARD',
            'amount' => 80000,
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        $response = $this->get(route('home'));
        $response->assertOk();

        // Upcoming rỗng khi không có phim release_date > hôm nay (không bị trộn với nowShowing)
        $response->assertViewHas('nowShowing', fn ($movies) => $movies->count() === 2);
        $response->assertViewHas('upcoming', fn ($movies) => $movies->isEmpty());
        $response->assertViewHas('blockbusterMovies', function ($movies) {
            return $movies->first()->id === $this->movie->id;
        });
    }

    public function test_password_policy_requires_min_8_chars_current_password_and_login_rate_limits(): void
    {
        // 1. Register từ chối mật khẩu < 8 ký tự
        $shortReg = $this->post(route('register.post'), [
            'username' => 'new_user_short',
            'email' => 'short@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);
        $shortReg->assertSessionHasErrors('password');

        // Register thành công với >= 8 ký tự
        $validReg = $this->post(route('register.post'), [
            'username' => 'new_user_ok',
            'email' => 'ok@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);
        $validReg->assertRedirect(route('home'));

        $this->post(route('logout'));

        // 2. Admin tạo/reset mật khẩu staff từ chối < 8 ký tự
        $staffCreateFail = $this->actingAs($this->admin)->post(route('admin.staff.store'), [
            'username' => 'staff_short',
            'email' => 'staff_short@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);
        $staffCreateFail->assertSessionHasErrors('password');

        $staffResetFail = $this->actingAs($this->admin)->put(route('admin.staff.reset-password', $this->staff->id), [
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);
        $staffResetFail->assertSessionHasErrors('password');

        // 3. Đổi mật khẩu cá nhân bắt buộc current_password đúng
        $wrongCurrent = $this->actingAs($this->customer)->put(route('user.account.password.update'), [
            'current_password' => 'wrongpass123',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ]);
        $wrongCurrent->assertSessionHasErrors('current_password');

        $rightCurrent = $this->actingAs($this->customer)->put(route('user.account.password.update'), [
            'current_password' => 'password123',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ]);
        $rightCurrent->assertSessionHas('success');
        $this->assertTrue(Hash::check('newpass123', $this->customer->fresh()->password));

        // 4. Rate limiting khi đăng nhập sai 5 lần liên tiếp
        $this->post(route('logout'));
        RateLimiter::clear('customer_audit|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.post'), [
                'username' => 'customer_audit',
                'password' => 'wrongpassword',
            ])->assertSessionHasErrors('username');
        }

        $throttled = $this->post(route('login.post'), [
            'username' => 'customer_audit',
            'password' => 'newpass123', // Kể cả nhập đúng mật khẩu ở lần 6 vẫn bị chặn tạm thời
        ]);
        $throttled->assertSessionHasErrors('username');
        $this->assertGuest();
    }
}
