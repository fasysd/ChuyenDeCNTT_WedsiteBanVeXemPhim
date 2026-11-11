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
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SystemSecurityAndIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'username' => 'admin_sec',
            'email' => 'admin_sec@example.com',
            'password' => 'password123',
            'role' => 'ADMIN',
        ]);

        $this->staff = User::create([
            'username' => 'staff_sec',
            'email' => 'staff_sec@example.com',
            'password' => 'password123',
            'role' => 'STAFF',
        ]);

        $this->customer = User::create([
            'username' => 'cust_sec',
            'email' => 'cust_sec@example.com',
            'password' => 'password123',
            'role' => 'CUSTOMER',
        ]);
    }

    /**
     * Vấn đề 1: Bảo vệ dữ liệu lịch sử vé khi sửa / xóa Phòng chiếu & Ghế
     */
    public function test_cannot_modify_seat_layout_or_delete_room_with_historical_tickets(): void
    {
        $movie = Movie::create(['title' => 'Dune', 'duration' => 150, 'release_date' => now()->subDays(10)]);
        $room = Room::create(['name' => 'Phòng Lịch Sử', 'total_seats' => 4]);

        $seatA1 = Seat::create(['room_id' => $room->id, 'seat_row' => 'A', 'seat_number' => 1, 'type' => 'STANDARD']);
        Seat::create(['room_id' => $room->id, 'seat_row' => 'A', 'seat_number' => 2, 'type' => 'STANDARD']);
        Seat::create(['room_id' => $room->id, 'seat_row' => 'B', 'seat_number' => 1, 'type' => 'STANDARD']);
        Seat::create(['room_id' => $room->id, 'seat_row' => 'B', 'seat_number' => 2, 'type' => 'VIP']);

        // Suất chiếu đã ở quá khứ nhưng đã có vé bán
        $pastShowtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHours(3),
            'price_standard' => 100000,
        ]);

        $booking = Booking::create([
            'user_id' => $this->customer->id,
            'total_price' => 100000,
            'status' => 'PAID',
        ]);

        $ticket = TicketDetail::create([
            'booking_id' => $booking->id,
            'showtime_id' => $pastShowtime->id,
            'seat_id' => $seatA1->id,
            'final_price' => 100000,
            'status' => 'USED',
        ]);

        $this->actingAs($this->admin);

        // 1. Thử sửa sơ đồ ghế (thêm VIP hoặc đổi số hàng) -> Phải bị chặn, vé lịch sử không được mất
        $resChangeLayout = $this->put(route('admin.rooms.update', $room->id), [
            'name' => 'Phòng Đổi Sơ Đồ',
            'row_count' => 3,
            'seat_per_row' => 2,
            'vip_seats' => json_encode(['A1']),
        ]);
        $resChangeLayout->assertSessionHasErrors('general');
        $this->assertDatabaseHas('ticket_details', ['id' => $ticket->id, 'status' => 'USED']);
        $this->assertDatabaseHas('seats', ['id' => $seatA1->id, 'type' => 'STANDARD']);

        // 2. Thử đổi tên phòng nhưng GIỮ NGUYÊN sơ đồ ghế -> Cho phép và giữ nguyên vé lịch sử
        $resRenameOnly = $this->put(route('admin.rooms.update', $room->id), [
            'name' => 'Phòng Lịch Sử VIP',
            'row_count' => 2,
            'seat_per_row' => 2,
            'vip_seats' => json_encode(['B2']),
        ]);
        $resRenameOnly->assertRedirect(route('admin.rooms.index'));
        $resRenameOnly->assertSessionHas('success');
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'name' => 'Phòng Lịch Sử VIP']);
        $this->assertDatabaseHas('ticket_details', ['id' => $ticket->id, 'status' => 'USED']);

        // 3. Thử xóa phòng đã có vé lịch sử -> Phải bị chặn
        $resDelete = $this->delete(route('admin.rooms.destroy', $room->id));
        $resDelete->assertSessionHasErrors('general');
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
        $this->assertDatabaseHas('ticket_details', ['id' => $ticket->id]);
    }

    /**
     * Bảo vệ thông tin liên quan đến vé ở Showtime và Movie (giá vé, giờ chiếu, thời lượng, ngày phát hành)
     */
    public function test_cannot_edit_or_delete_showtime_with_tickets_and_movie_duration_is_locked(): void
    {
        $movie = Movie::create([
            'title' => 'Interstellar',
            'duration' => 120,
            'release_date' => now()->subDays(5)->toDateString(),
        ]);
        $room = Room::create(['name' => 'Phòng IMAX', 'total_seats' => 1]);
        $seat = Seat::create(['room_id' => $room->id, 'seat_row' => 'A', 'seat_number' => 1, 'type' => 'STANDARD']);

        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addMinutes(130),
            'price_standard' => 150000,
        ]);

        $booking = Booking::create([
            'user_id' => $this->customer->id,
            'total_price' => 150000,
            'status' => 'PAID',
        ]);

        TicketDetail::create([
            'booking_id' => $booking->id,
            'showtime_id' => $showtime->id,
            'seat_id' => $seat->id,
            'final_price' => 150000,
            'status' => 'BOOKED',
        ]);

        $this->actingAs($this->admin);

        // 1. Cấm sửa giá vé / giờ chiếu của suất chiếu đã có vé
        $resUpdateShowtime = $this->put(route('admin.showtimes.update', $showtime->id), [
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->addDays(3)->toDateTimeString(),
            'end_time' => now()->addDays(3)->addMinutes(150)->toDateTimeString(),
            'price_standard' => 200000,
        ]);
        $resUpdateShowtime->assertSessionHasErrors('room_id');
        $this->assertDatabaseHas('showtimes', ['id' => $showtime->id, 'price_standard' => 150000]);

        // 2. Cấm xóa suất chiếu đã có vé
        $resDeleteShowtime = $this->delete(route('admin.showtimes.destroy', $showtime->id));
        $resDeleteShowtime->assertSessionHasErrors('showtime');
        $this->assertDatabaseHas('showtimes', ['id' => $showtime->id]);

        // 3. Cấm sửa duration hoặc release_date của Phim đã có vé
        $resUpdateMovieDuration = $this->put(route('admin.movies.update', $movie->id), [
            'title' => 'Interstellar Extended',
            'duration' => 180,
            'release_date' => $movie->release_date->toDateString(),
        ]);
        $resUpdateMovieDuration->assertSessionHas('error');
        $this->assertDatabaseHas('movies', ['id' => $movie->id, 'duration' => 120]);

        // 4. Cho phép sửa tiêu đề/mô tả phim nếu giữ nguyên duration và release_date
        $resUpdateMovieTitle = $this->put(route('admin.movies.update', $movie->id), [
            'title' => 'Interstellar (Special Edition)',
            'duration' => 120,
            'release_date' => $movie->release_date->toDateString(),
        ]);
        $resUpdateMovieTitle->assertRedirect(route('admin.movies.index'));
        $this->assertDatabaseHas('movies', ['id' => $movie->id, 'title' => 'Interstellar (Special Edition)']);
    }

    /**
     * Vấn đề 2: Chống thanh toán trùng lặp (Double Payment) và tôn trọng Cache Lock
     */
    public function test_pay_booking_prevents_double_payment_and_respects_cache_lock(): void
    {
        $booking = Booking::create([
            'user_id' => $this->customer->id,
            'total_price' => 100000,
            'status' => 'PENDING',
            'holding_expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($this->customer);

        // 1. Khi Cache Lock đang bị tiến trình khác chiếm -> Fail-Fast
        $lock = Cache::lock("lock:booking:{$booking->id}", 10);
        $this->assertTrue($lock->get());
        try {
            $resLocked = $this->post(route('user.account.bookings.pay', $booking->id));
            $resLocked->assertSessionHas('error');
            $this->assertDatabaseCount('payments', 0);
        } finally {
            $lock->release();
        }

        // 2. Thanh toán lần 1 -> Thành công, tạo đúng 1 Payment
        $resFirst = $this->post(route('user.account.bookings.pay', $booking->id), ['payment_method' => 'E_WALLET']);
        $resFirst->assertSessionHas('success');
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'PAID']);

        // 3. Thanh toán lần 2 (bấm đúp) -> Bị chặn, vẫn chỉ có đúng 1 Payment
        $resSecond = $this->post(route('user.account.bookings.pay', $booking->id), ['payment_method' => 'E_WALLET']);
        $resSecond->assertSessionHas('error');
        $this->assertDatabaseCount('payments', 1);
    }

    /**
     * Vấn đề 3: Validation đặt vé - Chặn đặt ghế sai phòng, sai phim hoặc suất chiếu đã bắt đầu
     */
    public function test_booking_validation_blocks_wrong_room_wrong_movie_and_started_showtime(): void
    {
        $movie1 = Movie::create(['title' => 'Phim 1', 'duration' => 100]);
        $movie2 = Movie::create(['title' => 'Phim 2', 'duration' => 100]);
        $room1 = Room::create(['name' => 'Phòng 1', 'total_seats' => 1]);
        $room2 = Room::create(['name' => 'Phòng 2', 'total_seats' => 1]);

        $seatRoom1 = Seat::create(['room_id' => $room1->id, 'seat_row' => 'A', 'seat_number' => 1, 'type' => 'STANDARD']);
        $seatRoom2 = Seat::create(['room_id' => $room2->id, 'seat_row' => 'B', 'seat_number' => 1, 'type' => 'STANDARD']);

        $futureShowtime = Showtime::create([
            'movie_id' => $movie1->id,
            'room_id' => $room1->id,
            'start_time' => now()->addHours(2),
            'end_time' => now()->addHours(4),
            'price_standard' => 100000,
        ]);

        $startedShowtime = Showtime::create([
            'movie_id' => $movie1->id,
            'room_id' => $room1->id,
            'start_time' => now()->subMinutes(5),
            'end_time' => now()->addHours(2),
            'price_standard' => 100000,
        ]);

        $this->actingAs($this->customer);

        // 1. Đặt ghế thuộc Phòng 2 cho suất chiếu Phòng 1 -> Bị chặn
        $resWrongRoom = $this->post(route('ticket.store', $movie1->id), [
            'showtime_id' => $futureShowtime->id,
            'seat_ids' => [$seatRoom2->id],
        ]);
        $resWrongRoom->assertSessionHas('error');
        $this->assertDatabaseCount('bookings', 0);

        // 2. Đặt suất chiếu của Phim 1 qua URL của Phim 2 -> Bị chặn
        $resWrongMovie = $this->post(route('ticket.store', $movie2->id), [
            'showtime_id' => $futureShowtime->id,
            'seat_ids' => [$seatRoom1->id],
        ]);
        $resWrongMovie->assertSessionHas('error');
        $this->assertDatabaseCount('bookings', 0);

        // 3. Đặt vé cho suất chiếu đã quá giờ bắt đầu -> Bị chặn
        $resStarted = $this->post(route('ticket.store', $movie1->id), [
            'showtime_id' => $startedShowtime->id,
            'seat_ids' => [$seatRoom1->id],
        ]);
        $resStarted->assertSessionHas('error');
        $this->assertDatabaseCount('bookings', 0);
    }

    /**
     * Vấn đề 4: Chống trùng yêu cầu hoàn tiền, chặn hoàn vé đã USED, và chống Admin duyệt/từ chối đè nhau
     */
    public function test_refund_concurrency_blocks_used_tickets_and_admin_conflict(): void
    {
        $movie = Movie::create(['title' => 'Avatar', 'duration' => 160]);
        $room = Room::create(['name' => 'Phòng R1', 'total_seats' => 2]);
        $seat1 = Seat::create(['room_id' => $room->id, 'seat_row' => 'A', 'seat_number' => 1, 'type' => 'STANDARD']);
        $seat2 = Seat::create(['room_id' => $room->id, 'seat_row' => 'A', 'seat_number' => 2, 'type' => 'STANDARD']);

        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->addHours(5),
            'end_time' => now()->addHours(8),
            'price_standard' => 100000,
        ]);

        // Case A: Vé đã USED -> Cấm hoàn tiền
        $bookingUsed = Booking::create(['user_id' => $this->customer->id, 'total_price' => 100000, 'status' => 'PAID']);
        TicketDetail::create([
            'booking_id' => $bookingUsed->id,
            'showtime_id' => $showtime->id,
            'seat_id' => $seat1->id,
            'final_price' => 100000,
            'status' => 'USED',
        ]);
        Payment::create([
            'booking_id' => $bookingUsed->id,
            'method' => 'CARD',
            'status' => 'PAID',
            'amount' => 100000,
            'payment_code' => 'PAY-USED1',
            'paid_at' => now(),
        ]);

        $this->actingAs($this->customer);
        $resUsedRefund = $this->post(route('user.account.bookings.refund', $bookingUsed->id));
        $resUsedRefund->assertSessionHas('error');
        $this->assertDatabaseCount('refunds', 0);

        // Case B: Chống gửi trùng Refund và xung đột Admin Duyệt / Từ chối
        $bookingValid = Booking::create(['user_id' => $this->customer->id, 'total_price' => 100000, 'status' => 'PAID']);
        TicketDetail::create([
            'booking_id' => $bookingValid->id,
            'showtime_id' => $showtime->id,
            'seat_id' => $seat2->id,
            'final_price' => 100000,
            'status' => 'BOOKED',
        ]);
        $paymentValid = Payment::create([
            'booking_id' => $bookingValid->id,
            'method' => 'CARD',
            'status' => 'PAID',
            'amount' => 100000,
            'payment_code' => 'PAY-VAL1',
            'paid_at' => now(),
        ]);

        // Gửi hoàn tiền lần 1 -> Thành công
        $this->post(route('user.account.bookings.refund', $bookingValid->id))->assertSessionHas('success');
        $this->assertDatabaseCount('refunds', 1);

        // Gửi hoàn tiền lần 2 -> Bị chặn
        $this->post(route('user.account.bookings.refund', $bookingValid->id))->assertSessionHas('error');
        $this->assertDatabaseCount('refunds', 1);

        $refund = Refund::first();

        // Admin 1 duyệt -> Thành công
        $this->actingAs($this->admin);
        $this->post(route('admin.refunds.approve', $refund->id))->assertSessionHas('success');
        $this->assertDatabaseHas('refunds', ['id' => $refund->id, 'status' => 'COMPLETED']);

        // Admin 2 bấm Từ chối ngay sau đó -> Phải bị chặn, không được ghi đè thành FAILED
        $this->post(route('admin.refunds.reject', $refund->id))->assertSessionHas('error');
        $this->assertDatabaseHas('refunds', ['id' => $refund->id, 'status' => 'COMPLETED']);
        $this->assertDatabaseHas('bookings', ['id' => $bookingValid->id, 'status' => 'REFUNDED']);
    }

    /**
     * Vấn đề 5: Hủy vé lẻ chỉ cho phép khi HOLDING (tự trừ total_price) và chặn thanh toán vé hết hạn
     */
    public function test_single_ticket_cancel_only_allows_holding_and_recalculates_total_price(): void
    {
        $movie = Movie::create(['title' => 'Matrix', 'duration' => 136]);
        $room = Room::create(['name' => 'Phòng M', 'total_seats' => 2]);
        $seat1 = Seat::create(['room_id' => $room->id, 'seat_row' => 'A', 'seat_number' => 1, 'type' => 'STANDARD']);
        $seat2 = Seat::create(['room_id' => $room->id, 'seat_row' => 'A', 'seat_number' => 2, 'type' => 'STANDARD']);

        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->addHours(4),
            'end_time' => now()->addHours(7),
            'price_standard' => 100000,
        ]);

        $this->actingAs($this->customer);
        $this->post(route('ticket.store', $movie->id), [
            'showtime_id' => $showtime->id,
            'seat_ids' => [$seat1->id, $seat2->id],
        ]);

        $booking = Booking::first();
        $this->assertEquals(200000, $booking->total_price);

        $tickets = $booking->ticketDetails;
        $ticket1 = $tickets[0];
        $ticket2 = $tickets[1];

        // 1. Hủy 1 vé HOLDING -> Thành công, total_price của Booking tự trừ còn 100.000
        $this->post(route('user.account.tickets.cancel', $ticket1->id))->assertSessionHas('success');
        $this->assertDatabaseHas('ticket_details', ['id' => $ticket1->id, 'status' => 'CANCELLED']);
        $this->assertEquals(100000, $booking->fresh()->total_price);

        // 2. Thanh toán vé thứ 2 -> Thành công, Booking thành PAID với Payment 100.000
        $this->post(route('user.account.tickets.payment', $ticket2->id))->assertSessionHas('success');
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'PAID']);
        $this->assertDatabaseHas('payments', ['booking_id' => $booking->id, 'amount' => 100000]);

        // 3. Cố tình gọi hủy trực tiếp vé đã BOOKED -> Bị chặn
        $this->post(route('user.account.tickets.cancel', $ticket2->id))->assertSessionHas('error');
        $this->assertDatabaseHas('ticket_details', ['id' => $ticket2->id, 'status' => 'BOOKED']);
    }

    /**
     * Vấn đề 6: Chống Staff check-in trùng 1 mã vé và chặn check-in suất chiếu đã kết thúc
     */
    public function test_staff_checkin_prevents_duplicate_and_expired_showtime(): void
    {
        $movie = Movie::create(['title' => 'Top Gun', 'duration' => 130]);
        $room = Room::create(['name' => 'Phòng TG', 'total_seats' => 2]);
        $seat1 = Seat::create(['room_id' => $room->id, 'seat_row' => 'A', 'seat_number' => 1, 'type' => 'STANDARD']);
        $seat2 = Seat::create(['room_id' => $room->id, 'seat_row' => 'A', 'seat_number' => 2, 'type' => 'STANDARD']);

        $activeShowtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->addMinutes(30),
            'end_time' => now()->addHours(3),
            'price_standard' => 100000,
        ]);

        $endedShowtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->subHours(5),
            'end_time' => now()->subHours(2),
            'price_standard' => 100000,
        ]);

        $booking = Booking::create(['user_id' => $this->customer->id, 'total_price' => 200000, 'status' => 'PAID']);

        $activeTicket = TicketDetail::create([
            'booking_id' => $booking->id,
            'showtime_id' => $activeShowtime->id,
            'seat_id' => $seat1->id,
            'final_price' => 100000,
            'status' => 'BOOKED',
        ]);

        $endedTicket = TicketDetail::create([
            'booking_id' => $booking->id,
            'showtime_id' => $endedShowtime->id,
            'seat_id' => $seat2->id,
            'final_price' => 100000,
            'status' => 'BOOKED',
        ]);

        $this->actingAs($this->staff);

        // 1. Check-in vé của suất chiếu đã kết thúc -> Bị chặn
        $this->post(route('staff.tickets.updateStatus', $endedTicket->id), ['status' => 'USED'])
            ->assertSessionHas('error');
        $this->assertDatabaseHas('ticket_details', ['id' => $endedTicket->id, 'status' => 'BOOKED']);

        // 2. Check-in vé hợp lệ khi Cache Lock đang bận -> Bị chặn
        $lock = Cache::lock("lock:ticket:checkin:{$activeTicket->id}", 5);
        $this->assertTrue($lock->get());
        try {
            $this->post(route('staff.tickets.updateStatus', $activeTicket->id), ['status' => 'USED'])
                ->assertSessionHas('error');
            $this->assertDatabaseHas('ticket_details', ['id' => $activeTicket->id, 'status' => 'BOOKED']);
        } finally {
            $lock->release();
        }

        // 3. Check-in lần 1 -> Thành công
        $this->post(route('staff.tickets.updateStatus', $activeTicket->id), ['status' => 'USED'])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('ticket_details', ['id' => $activeTicket->id, 'status' => 'USED']);

        // 4. Check-in lần 2 (cửa soát vé thứ 2 quét cùng mã) -> Bị chặn
        $this->post(route('staff.tickets.updateStatus', $activeTicket->id), ['status' => 'USED'])
            ->assertSessionHas('error');
    }
}
